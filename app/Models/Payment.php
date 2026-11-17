<?php

namespace App\Models;

use App\Core\Model;
use App\Services\AuditLogger;
use PDO;
use Exception;

class Payment extends Model
{
    protected string $table = 'payments';

    public function generatePaymentNumber(): string
    {
        $year = date('Y');
        $month = date('m');
        $prefix = "PAY/{$year}/{$month}/";

        if (!$this->db) {
            return $prefix . str_pad((string)rand(1, 9999), 4, '0', STR_PAD_LEFT);
        }

        $stmt = $this->db->prepare("SELECT payment_number FROM payments WHERE payment_number LIKE :p ORDER BY id DESC LIMIT 1");
        $stmt->execute(['p' => "{$prefix}%"]);
        $lastNum = $stmt->fetchColumn();

        if ($lastNum) {
            $parts = explode('/', $lastNum);
            $next = (int)end($parts) + 1;
        } else {
            $next = 1;
        }

        return $prefix . str_pad((string)$next, 4, '0', STR_PAD_LEFT);
    }

    public function processPayment(array $data): array
    {
        if (!$this->db) {
            return ['success' => false, 'message' => 'Koneksi database terputus.'];
        }

        $billId = (int)$data['bill_id'];
        $amount = (float)$data['amount'];
        $method = strtolower($data['payment_method'] ?? 'cash');
        $recordedBy = (int)$data['recorded_by'];
        $notes = trim($data['notes'] ?? '');

        if ($amount <= 0) {
            return ['success' => false, 'message' => 'Nominal pembayaran harus lebih besar dari Rp 0.'];
        }

        try {
            $this->db->beginTransaction();

            // 1. Lock dan ambil data tagihan
            $billStmt = $this->db->prepare("SELECT b.*, s.nim, s.full_name AS student_name, c.name AS category_name 
                                           FROM bills b 
                                           JOIN students s ON b.student_id = s.id 
                                           JOIN categories c ON b.category_id = c.id 
                                           WHERE b.id = :id FOR UPDATE");
            $billStmt->execute(['id' => $billId]);
            $bill = $billStmt->fetch(PDO::FETCH_ASSOC);

            if (!$bill) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Tagihan yang dipilih tidak ditemukan.'];
            }

            if (in_array($bill['status'], ['PAID', 'CANCELLED'])) {
                $this->db->rollBack();
                return ['success' => false, 'message' => "Tagihan ini sudah berstatus {$bill['status']} dan tidak dapat menerima pembayaran."];
            }

            $remaining = (float)$bill['remaining_amount'];
            if ($amount > $remaining) {
                $this->db->rollBack();
                return ['success' => false, 'message' => "Nominal pembayaran (Rp " . number_format($amount) . ") melebihi sisa tagihan (Rp " . number_format($remaining) . ")."];
            }

            // 2. Generate No Kuitansi
            $payNum = $this->generatePaymentNumber();

            // 3. Insert ke payments
            $payInsert = $this->db->prepare("INSERT INTO payments (payment_number, student_id, bill_id, amount, payment_method, payment_date, status, receipt_issued, notes, recorded_by)
                                             VALUES (:num, :sid, :bid, :amt, :method, NOW(), 'completed', 1, :notes, :rec_by)");
            $payInsert->execute([
                'num' => $payNum,
                'sid' => (int)$bill['student_id'],
                'bid' => $billId,
                'amt' => $amount,
                'method' => in_array($method, ['cash', 'transfer', 'other', 'qris', 'bank_transfer', 'edc']) ? $method : 'cash',
                'notes' => $notes,
                'rec_by' => $recordedBy
            ]);
            $paymentId = (int)$this->db->lastInsertId();

            // 4. Generate no transaksi kas & insert ke cash_transactions (Buku Kas Masuk)
            $cashModel = new CashTransaction();
            $transNum = $cashModel->generateTransactionNumber('income');
            $cashDesc = "Pembayaran " . $bill['title'] . " NIM " . $bill['nim'] . " (" . $bill['student_name'] . ")";

            $cashInsert = $this->db->prepare("INSERT INTO cash_transactions (transaction_number, category_id, type, amount, transaction_date, description, reference_number, payment_method, status, created_by)
                                              VALUES (:tnum, :cid, 'income', :amt, NOW(), :desc, :ref, :method, 'completed', :uid)");
            $cashInsert->execute([
                'tnum' => $transNum,
                'cid' => (int)$bill['category_id'],
                'amt' => $amount,
                'desc' => $cashDesc,
                'ref' => $payNum,
                'method' => in_array($method, ['cash', 'qris', 'bank_transfer', 'edc']) ? $method : 'cash',
                'uid' => $recordedBy
            ]);
            $cashTransId = (int)$this->db->lastInsertId();

            // Update payments with cash_transaction_id
            $this->db->prepare("UPDATE payments SET cash_transaction_id = :cid WHERE id = :pid")->execute([
                'cid' => $cashTransId,
                'pid' => $paymentId
            ]);

            // 5. Update bills: paid_amount, remaining_amount, status
            $newPaid = (float)$bill['paid_amount'] + $amount;
            $newRemaining = (float)$bill['amount'] - $newPaid;
            $newStatus = ($newRemaining <= 0.001) ? 'PAID' : 'PARTIAL';

            $billUpdate = $this->db->prepare("UPDATE bills SET paid_amount = :paid, remaining_amount = :rem, status = :status WHERE id = :id");
            $billUpdate->execute([
                'paid' => $newPaid,
                'rem' => max(0, $newRemaining),
                'status' => $newStatus,
                'id' => $billId
            ]);

            // 6. Catat Audit Log
            AuditLogger::log('PROCESS_PAYMENT', 'payments', $paymentId, null, [
                'payment_number' => $payNum,
                'invoice_number' => $bill['invoice_number'],
                'student_nim' => $bill['nim'],
                'amount' => $amount,
                'method' => $method,
                'new_bill_status' => $newStatus
            ]);

            // 7. Commit Database Transaction
            $this->db->commit();

            // 8. WhatsApp Notification (Fail-Safe & Non-Blocking)
            try {
                $studentStmt = $this->db->prepare("SELECT * FROM students WHERE id = :sid");
                $studentStmt->execute(['sid' => $bill['student_id']]);
                $studentRow = $studentStmt->fetch(PDO::FETCH_ASSOC) ?: [];

                $payData = [
                    'id' => $paymentId,
                    'payment_number' => $payNum,
                    'amount' => $amount,
                    'payment_method' => $method,
                    'invoice_number' => $bill['invoice_number'],
                    'bill_title' => $bill['title'],
                    'student_name' => $bill['student_name'],
                    'nim' => $bill['nim']
                ];
                $billData = [
                    'id' => $bill['id'] ?? null,
                    'invoice_number' => $bill['invoice_number'],
                    'title' => $bill['title'],
                    'amount' => $bill['amount'],
                    'remaining_amount' => max(0, $newRemaining),
                    'status' => $newStatus
                ];
                \App\Services\WhatsAppService::notifyPaymentSuccess($payData, $billData, $studentRow, \App\Services\AuthService::id());
            } catch (\Exception $waEx) {
                error_log("WhatsApp notifyPaymentSuccess error: " . $waEx->getMessage());
            }


            return [
                'success' => true,
                'payment_id' => $paymentId,
                'payment_number' => $payNum,
                'invoice_number' => $bill['invoice_number'],
                'student_name' => $bill['student_name'],
                'amount' => $amount,
                'new_bill_status' => $newStatus,
                'message' => 'Pembayaran berhasil diproses dan kuitansi resmi telah diterbitkan!'
            ];
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return ['success' => false, 'message' => 'Gagal memproses transaksi: ' . $e->getMessage()];
        }
    }

    public function paginate(int $page = 1, int $perPage = 10, array $filters = []): array
    {
        if (!$this->db) {
            return ['data' => [], 'total' => 0, 'totalPages' => 1, 'page' => $page, 'perPage' => $perPage];
        }

        $conditions = ["1=1"];
        $params = [];

        if (!empty($filters['search'])) {
            $conditions[] = "(p.payment_number LIKE :q OR s.nim LIKE :q2 OR s.full_name LIKE :q3 OR b.invoice_number LIKE :q4)";
            $params['q'] = "%" . $filters['search'] . "%";
            $params['q2'] = "%" . $filters['search'] . "%";
            $params['q3'] = "%" . $filters['search'] . "%";
            $params['q4'] = "%" . $filters['search'] . "%";
        }

        if (!empty($filters['recorded_by'])) {
            $conditions[] = "p.recorded_by = :rec_by";
            $params['rec_by'] = (int)$filters['recorded_by'];
        }

        if (!empty($filters['student_id'])) {
            $conditions[] = "p.student_id = :sid";
            $params['sid'] = (int)$filters['student_id'];
        }

        if (!empty($filters['date'])) {
            $conditions[] = "DATE(p.payment_date) = :date";
            $params['date'] = $filters['date'];
        }

        if (!empty($filters['start_date']) && !empty($filters['end_date'])) {
            $conditions[] = "DATE(p.payment_date) BETWEEN :start AND :end";
            $params['start'] = $filters['start_date'];
            $params['end'] = $filters['end_date'];
        }

        if (!empty($filters['method'])) {
            $conditions[] = "p.payment_method = :method";
            $params['method'] = $filters['method'];
        }

        $whereClause = implode(" AND ", $conditions);

        // Count
        $countStmt = $this->db->prepare("SELECT COUNT(*) FROM payments p JOIN students s ON p.student_id = s.id LEFT JOIN bills b ON p.bill_id = b.id WHERE {$whereClause}");
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        $totalPages = max(1, (int)ceil($total / $perPage));
        $page = max(1, min($page, $totalPages));
        $offset = ($page - 1) * $perPage;

        // Records
        $sql = "SELECT p.*, s.nim, s.full_name AS student_name, s.study_program, b.invoice_number, b.title AS bill_title, u.full_name AS cashier_name
                FROM payments p
                JOIN students s ON p.student_id = s.id
                LEFT JOIN bills b ON p.bill_id = b.id
                LEFT JOIN users u ON p.recorded_by = u.id
                WHERE {$whereClause}
                ORDER BY p.payment_date DESC, p.id DESC
                LIMIT {$perPage} OFFSET {$offset}";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'data' => $data,
            'total' => $total,
            'totalPages' => $totalPages,
            'page' => $page,
            'perPage' => $perPage
        ];
    }

    public function findWithDetails(int $id): ?array
    {
        if (!$this->db) return null;

        $sql = "SELECT p.*, s.nim, s.full_name AS student_name, s.faculty, s.study_program, s.academic_year, s.class_group, s.phone AS student_phone,
                       b.invoice_number, b.title AS bill_title, b.amount AS bill_total_amount, b.remaining_amount AS bill_remaining, b.status AS bill_status,
                       c.name AS category_name, u.full_name AS cashier_name, u.username AS cashier_username
                FROM payments p
                JOIN students s ON p.student_id = s.id
                LEFT JOIN bills b ON p.bill_id = b.id
                LEFT JOIN categories c ON b.category_id = c.id
                LEFT JOIN users u ON p.recorded_by = u.id
                WHERE p.id = :id
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function voidPayment(int $paymentId, int $userId, string $reason): array
    {
        if (!$this->db) {
            return ['success' => false, 'message' => 'Database tidak terhubung.'];
        }

        try {
            $this->db->beginTransaction();

            $payStmt = $this->db->prepare("SELECT * FROM payments WHERE id = :id FOR UPDATE");
            $payStmt->execute(['id' => $paymentId]);
            $payment = $payStmt->fetch(PDO::FETCH_ASSOC);

            if (!$payment || $payment['status'] === 'refunded') {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Transaksi tidak ditemukan atau sudah dibatalkan sebelumnya.'];
            }

            // Lock bill
            $billStmt = $this->db->prepare("SELECT * FROM bills WHERE id = :bid FOR UPDATE");
            $billStmt->execute(['bid' => $payment['bill_id']]);
            $bill = $billStmt->fetch(PDO::FETCH_ASSOC);

            if ($bill) {
                $revertedPaid = max(0, (float)$bill['paid_amount'] - (float)$payment['amount']);
                $revertedRemaining = (float)$bill['amount'] - $revertedPaid;
                $revertedStatus = ($revertedPaid <= 0) ? 'UNPAID' : 'PARTIAL';

                $this->db->prepare("UPDATE bills SET paid_amount = :paid, remaining_amount = :rem, status = :st WHERE id = :id")->execute([
                    'paid' => $revertedPaid,
                    'rem' => $revertedRemaining,
                    'st' => $revertedStatus,
                    'id' => $bill['id']
                ]);
            }

            // Update payment status
            $this->db->prepare("UPDATE payments SET status = 'refunded', notes = CONCAT(COALESCE(notes, ''), ' [VOID: ', :reason, ']') WHERE id = :id")->execute([
                'id' => $paymentId,
                'reason' => $reason
            ]);

            // Mark cash_transaction voided
            if ($payment['cash_transaction_id']) {
                $this->db->prepare("UPDATE cash_transactions SET status = 'voided', description = CONCAT(description, ' [VOID: ', :reason, ']') WHERE id = :cid")->execute([
                    'cid' => $payment['cash_transaction_id'],
                    'reason' => $reason
                ]);
            }

            AuditLogger::log('VOID_PAYMENT', 'payments', $paymentId, [
                'status' => 'completed',
                'amount' => $payment['amount']
            ], [
                'status' => 'refunded',
                'reason' => $reason,
                'voided_by' => $userId
            ]);

            $this->db->commit();
            return ['success' => true, 'message' => 'Transaksi pembayaran berhasil dibatalkan (void) dan saldo tagihan telah dipulihkan.'];
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return ['success' => false, 'message' => 'Gagal membatalkan transaksi: ' . $e->getMessage()];
        }
    }
}

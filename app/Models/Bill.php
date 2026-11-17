<?php

namespace App\Models;

use App\Core\Model;
use PDO;

class Bill extends Model
{
    protected string $table = 'bills';

    public function paginate(int $page = 1, int $perPage = 10, array $filters = []): array
    {
        if (!$this->db) {
            return ['data' => [], 'total' => 0, 'totalPages' => 1, 'page' => $page, 'perPage' => $perPage];
        }

        // Otomatis sinkronisasi status OVERDUE
        $this->syncOverdueStatus();

        $conditions = ["1=1"];
        $params = [];

        if (!empty($filters['search'])) {
            $conditions[] = "(b.invoice_number LIKE :q OR s.nim LIKE :q2 OR s.full_name LIKE :q3 OR b.title LIKE :q4)";
            $params['q'] = "%" . $filters['search'] . "%";
            $params['q2'] = "%" . $filters['search'] . "%";
            $params['q3'] = "%" . $filters['search'] . "%";
            $params['q4'] = "%" . $filters['search'] . "%";
        }

        if (!empty($filters['status'])) {
            $conditions[] = "b.status = :status";
            $params['status'] = strtoupper($filters['status']);
        }

        if (!empty($filters['period'])) {
            $conditions[] = "b.period = :period";
            $params['period'] = $filters['period'];
        }

        if (!empty($filters['student_id'])) {
            $conditions[] = "b.student_id = :sid";
            $params['sid'] = (int)$filters['student_id'];
        }

        $whereClause = implode(" AND ", $conditions);

        // Total count
        $countStmt = $this->db->prepare("SELECT COUNT(*) FROM bills b JOIN students s ON b.student_id = s.id WHERE {$whereClause}");
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        $totalPages = max(1, (int)ceil($total / $perPage));
        $page = max(1, min($page, $totalPages));
        $offset = ($page - 1) * $perPage;

        // Fetch records
        $sql = "SELECT b.*, s.nim, s.full_name AS student_name, s.study_program, c.name AS category_name, u.full_name AS creator_name
                FROM bills b
                JOIN students s ON b.student_id = s.id
                JOIN categories c ON b.category_id = c.id
                JOIN users u ON b.created_by = u.id
                WHERE {$whereClause}
                ORDER BY b.due_date ASC, b.id DESC
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
        $this->syncOverdueStatus();

        $sql = "SELECT b.*, s.nim, s.full_name AS student_name, s.faculty, s.study_program, s.academic_year, s.class_group, s.email AS student_email, s.phone AS student_phone,
                       c.name AS category_name, c.code AS category_code, u.full_name AS creator_name
                FROM bills b
                JOIN students s ON b.student_id = s.id
                JOIN categories c ON b.category_id = c.id
                JOIN users u ON b.created_by = u.id
                WHERE b.id = :id
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function generateInvoiceNumber(): string
    {
        $year = date('Y');
        $month = date('m');
        $prefix = "INV/{$year}/{$month}/";

        if (!$this->db) {
            return $prefix . str_pad((string)rand(1, 9999), 4, '0', STR_PAD_LEFT);
        }

        $stmt = $this->db->prepare("SELECT invoice_number FROM bills WHERE invoice_number LIKE :p ORDER BY id DESC LIMIT 1");
        $stmt->execute(['p' => "{$prefix}%"]);
        $lastInv = $stmt->fetchColumn();

        if ($lastInv) {
            $parts = explode('/', $lastInv);
            $lastNum = (int)end($parts);
            $nextNum = $lastNum + 1;
        } else {
            $nextNum = 1;
        }

        return $prefix . str_pad((string)$nextNum, 4, '0', STR_PAD_LEFT);
    }

    public function isInvoiceNumberTaken(string $invoiceNumber, ?int $excludeId = null): bool
    {
        if (!$this->db) return false;
        $sql = "SELECT COUNT(*) FROM bills WHERE invoice_number = :inv" . ($excludeId ? " AND id != :excludeId" : "");
        $stmt = $this->db->prepare($sql);
        $params = ['inv' => trim($invoiceNumber)];
        if ($excludeId) $params['excludeId'] = $excludeId;
        $stmt->execute($params);
        return (int)$stmt->fetchColumn() > 0;
    }

    public function create(array $data): ?int
    {
        if (!$this->db) return null;

        $amount = (float)$data['amount'];
        $sql = "INSERT INTO bills (invoice_number, student_id, category_id, title, period, description, notes, amount, paid_amount, remaining_amount, due_date, status, created_by)
                VALUES (:inv, :sid, :cid, :title, :period, :desc, :notes, :amt, 0.00, :amt2, :due, 'UNPAID', :uid)";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'inv' => trim($data['invoice_number']),
            'sid' => (int)$data['student_id'],
            'cid' => (int)$data['category_id'],
            'title' => trim($data['title']),
            'period' => trim($data['period'] ?? '2026/2027 Gasal'),
            'desc' => trim($data['description'] ?? ''),
            'notes' => trim($data['notes'] ?? ''),
            'amt' => $amount,
            'amt2' => $amount,
            'due' => $data['due_date'],
            'uid' => (int)$data['created_by']
        ]);

        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        if (!$this->db) return false;

        $bill = $this->find($id);
        if (!$bill || in_array($bill['status'], ['PAID', 'CANCELLED'])) {
            return false;
        }

        $newAmount = (float)$data['amount'];
        $paidAmount = (float)$bill['paid_amount'];
        if ($newAmount < $paidAmount) {
            return false; // Nominal tidak boleh lebih kecil dari yang sudah dibayar
        }
        $remaining = $newAmount - $paidAmount;
        $status = ($remaining <= 0) ? 'PAID' : (($paidAmount > 0) ? 'PARTIAL' : 'UNPAID');

        $sql = "UPDATE bills SET
                    invoice_number = :inv,
                    category_id = :cid,
                    title = :title,
                    period = :period,
                    notes = :notes,
                    amount = :amt,
                    remaining_amount = :rem,
                    due_date = :due,
                    status = :status
                WHERE id = :id";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'id' => $id,
            'inv' => trim($data['invoice_number']),
            'cid' => (int)$data['category_id'],
            'title' => trim($data['title']),
            'period' => trim($data['period'] ?? '2026/2027 Gasal'),
            'notes' => trim($data['notes'] ?? ''),
            'amt' => $newAmount,
            'rem' => $remaining,
            'due' => $data['due_date'],
            'status' => $status
        ]);
    }

    public function cancel(int $id, string $reason = ''): bool
    {
        if (!$this->db) return false;
        $bill = $this->find($id);
        if (!$bill || $bill['status'] === 'PAID' || (float)$bill['paid_amount'] > 0) {
            return false; // Tagihan yang sudah dibayar/dicicil tidak boleh dibatalkan langsung
        }

        $notes = trim($bill['notes'] . " [DIBATALKAN: {$reason}]");
        $stmt = $this->db->prepare("UPDATE bills SET status = 'CANCELLED', notes = :notes WHERE id = :id");
        return $stmt->execute(['id' => $id, 'notes' => $notes]);
    }

    public function syncOverdueStatus(): void
    {
        if (!$this->db) return;
        $this->db->exec("UPDATE bills SET status = 'OVERDUE' WHERE due_date < CURDATE() AND status = 'UNPAID'");
    }

    public function getDistinctPeriods(): array
    {
        if (!$this->db) return [];
        $stmt = $this->db->query("SELECT DISTINCT period FROM bills WHERE period IS NOT NULL AND period != '' ORDER BY period DESC");
        return $stmt->fetchAll(PDO::FETCH_COLUMN) ?: ['2026/2027 Gasal', '2025/2026 Genap'];
    }
}

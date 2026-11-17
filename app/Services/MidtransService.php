<?php

namespace App\Services;

use App\Core\Database;
use PDO;
use Exception;

class MidtransService
{
    public static function getServerKey(): string
    {
        return env('MIDTRANS_SERVER_KEY', '');
    }

    public static function getClientKey(): string
    {
        return env('MIDTRANS_CLIENT_KEY', '');
    }

    public static function isProduction(): bool
    {
        return (bool)env('MIDTRANS_IS_PRODUCTION', false);
    }

    public static function getSnapApiUrl(): string
    {
        return self::isProduction()
            ? 'https://app.midtrans.com/snap/v1/transactions'
            : 'https://app.sandbox.midtrans.com/snap/v1/transactions';
    }

    public static function getSnapJsUrl(): string
    {
        return self::isProduction()
            ? 'https://app.midtrans.com/snap/snap.js'
            : 'https://app.sandbox.midtrans.com/snap/snap.js';
    }

    /**
     * Buat Transaksi Snap Midtrans untuk Tagihan Mahasiswa
     */
    public static function createSnapTransaction(array $bill, array $student, ?float $amount = null): array
    {
        $serverKey = self::getServerKey();
        $pdo = Database::getConnection();

        if (!$pdo) {
            return ['success' => false, 'message' => 'Database tidak terhubung.'];
        }

        $remaining = (float)$bill['remaining_amount'];
        $payAmount = ($amount !== null && $amount > 0) ? min($amount, $remaining) : $remaining;

        if ($payAmount <= 0) {
            return ['success' => false, 'message' => 'Nominal tagihan sudah lunas atau tidak valid.'];
        }

        // Generate Order ID Unik
        $orderId = 'SCC-' . $bill['id'] . '-' . time() . '-' . rand(100, 999);

        // Simpan sesi awal ke tabel midtrans_transactions
        $stmt = $pdo->prepare("INSERT INTO midtrans_transactions (order_id, bill_id, student_id, gross_amount, transaction_status, created_at)
                               VALUES (:oid, :bid, :sid, :amt, 'pending', NOW())");
        $stmt->execute([
            'oid' => $orderId,
            'bid' => (int)$bill['id'],
            'sid' => (int)$student['id'],
            'amt' => $payAmount
        ]);
        $txRecordId = (int)$pdo->lastInsertId();

        // Jika Server Key tidak diatur atau dummy test, siapkan mock snap token & redirect url untuk testing
        if (empty($serverKey) || str_starts_with($serverKey, 'SB-Mid-server-TESTKEY')) {
            $mockToken = 'MOCK-SNAP-' . md5($orderId);
            $mockRedirect = 'https://app.sandbox.midtrans.com/snap/v2/vtweb/' . $mockToken;

            $pdo->prepare("UPDATE midtrans_transactions SET snap_token = :tk, redirect_url = :rd WHERE id = :id")->execute([
                'tk' => $mockToken,
                'rd' => $mockRedirect,
                'id' => $txRecordId
            ]);

            return [
                'success' => true,
                'order_id' => $orderId,
                'snap_token' => $mockToken,
                'redirect_url' => $mockRedirect,
                'gross_amount' => $payAmount,
                'client_key' => self::getClientKey(),
                'snap_js_url' => self::getSnapJsUrl(),
                'is_mock' => true
            ];
        }

        // Payload Snap Midtrans API
        $payload = [
            'transaction_details' => [
                'order_id' => $orderId,
                'gross_amount' => (int)round($payAmount)
            ],
            'item_details' => [
                [
                    'id' => 'BILL-' . $bill['id'],
                    'price' => (int)round($payAmount),
                    'quantity' => 1,
                    'name' => substr($bill['title'], 0, 50)
                ]
            ],
            'customer_details' => [
                'first_name' => substr($student['full_name'], 0, 50),
                'email' => !empty($student['email']) ? $student['email'] : 'student@campus.ac.id',
                'phone' => !empty($student['phone']) ? $student['phone'] : '08123456789'
            ]
        ];

        try {
            $ch = curl_init(self::getSnapApiUrl());
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Accept: application/json',
                'Content-Type: application/json',
                'Authorization: Basic ' . base64_encode($serverKey . ':')
            ]);
            curl_setopt($ch, CURLOPT_TIMEOUT, 15);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

            $raw = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlErr = curl_error($ch);
            curl_close($ch);

            if (!empty($curlErr)) {
                return ['success' => false, 'message' => 'Gagal menghubungi Midtrans Snap: ' . $curlErr];
            }

            $res = json_decode($raw, true) ?: [];

            if ($httpCode >= 200 && $httpCode < 300 && !empty($res['token'])) {
                $snapToken = $res['token'];
                $redirectUrl = $res['redirect_url'] ?? '';

                $pdo->prepare("UPDATE midtrans_transactions SET snap_token = :tk, redirect_url = :rd, raw_response = :raw WHERE id = :id")->execute([
                    'tk' => $snapToken,
                    'rd' => $redirectUrl,
                    'raw' => $raw,
                    'id' => $txRecordId
                ]);

                return [
                    'success' => true,
                    'order_id' => $orderId,
                    'snap_token' => $snapToken,
                    'redirect_url' => $redirectUrl,
                    'gross_amount' => $payAmount,
                    'client_key' => self::getClientKey(),
                    'snap_js_url' => self::getSnapJsUrl()
                ];
            }

            $errMsg = $res['error_messages'][0] ?? "HTTP {$httpCode}: Gagal membuat transaksi Midtrans.";
            return ['success' => false, 'message' => $errMsg, 'raw' => $res];
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Terjadi kesalahan sistem: ' . $e->getMessage()];
        }
    }

    /**
     * Validasi Cryptographic Signature SHA512 Notifikasi Midtrans
     */
    public static function verifySignature(string $orderId, string $statusCode, string $grossAmount, string $signatureKey): bool
    {
        $serverKey = self::getServerKey();
        if (empty($serverKey)) {
            return false;
        }

        // Format standar gross_amount pada signature SHA512 Midtrans (misal "1500000.00" atau integer string)
        $expectedSignature = hash('sha512', $orderId . $statusCode . $grossAmount . $serverKey);

        return hash_equals($expectedSignature, $signatureKey);
    }

    /**
     * Memproses Webhook/Notification Midtrans dengan Idempotency & Database Transaction
     */
    public static function handleNotification(array $payload): array
    {
        $pdo = Database::getConnection();
        if (!$pdo) {
            return ['success' => false, 'http_code' => 500, 'message' => 'Koneksi database terputus.'];
        }

        $orderId = trim($payload['order_id'] ?? '');
        $statusCode = trim((string)($payload['status_code'] ?? ''));
        $grossAmount = trim((string)($payload['gross_amount'] ?? ''));
        $signatureKey = trim($payload['signature_key'] ?? '');
        $transactionStatus = strtolower(trim($payload['transaction_status'] ?? ''));
        $fraudStatus = strtolower(trim($payload['fraud_status'] ?? ''));
        $paymentType = strtolower(trim($payload['payment_type'] ?? 'midtrans'));
        $midtransTxId = trim($payload['transaction_id'] ?? '');

        if (empty($orderId) || empty($statusCode) || empty($grossAmount) || empty($signatureKey)) {
            return ['success' => false, 'http_code' => 400, 'message' => 'Parameter payload tidak lengkap.'];
        }

        // 1. Verifikasi Signature SHA512
        if (!self::verifySignature($orderId, $statusCode, $grossAmount, $signatureKey)) {
            error_log("Midtrans Webhook: Invalid signature for Order ID {$orderId}");
            return ['success' => false, 'http_code' => 403, 'message' => 'Signature Key tidak valid.'];
        }

        try {
            $pdo->beginTransaction();

            // 2. Lock & Ambil data dari midtrans_transactions untuk Idempotency Guard
            $stmt = $pdo->prepare("SELECT * FROM midtrans_transactions WHERE order_id = :oid FOR UPDATE");
            $stmt->execute(['oid' => $orderId]);
            $midTx = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$midTx) {
                $pdo->rollBack();
                return ['success' => false, 'http_code' => 404, 'message' => 'Order ID tidak terdaftar di sistem.'];
            }

            // 3. IDEMPOTENCY CHECK: Jika transaksi sudah pernah berstatus settlement/capture, skip agar tidak duplikasi
            if (in_array($midTx['transaction_status'], ['settlement', 'capture']) && !empty($midTx['payment_id'])) {
                $pdo->commit();
                return [
                    'success' => true,
                    'http_code' => 200,
                    'already_processed' => true,
                    'message' => 'Notifikasi sudah pernah diproses sebelumnya (Idempotent OK).'
                ];
            }

            $billId = (int)$midTx['bill_id'];
            $paidAmt = (float)$grossAmount;
            $rawPayloadJson = json_encode($payload, JSON_UNESCAPED_UNICODE);

            $paymentData = null;
            $billData = null;
            $studentData = null;

            // 4. Evaluasi Status Transaksi
            $isPaid = false;
            if ($transactionStatus === 'settlement') {
                $isPaid = true;
            } elseif ($transactionStatus === 'capture') {
                if ($fraudStatus === 'accept' || empty($fraudStatus)) {
                    $isPaid = true;
                }
            }

            if ($isPaid) {
                // Lock data Tagihan
                $billStmt = $pdo->prepare("SELECT b.*, s.nim, s.full_name AS student_name, s.phone AS student_phone, c.name AS category_name
                                           FROM bills b
                                           JOIN students s ON b.student_id = s.id
                                           JOIN categories c ON b.category_id = c.id
                                           WHERE b.id = :id FOR UPDATE");
                $billStmt->execute(['id' => $billId]);
                $bill = $billStmt->fetch(PDO::FETCH_ASSOC);

                if ($bill && $bill['status'] !== 'CANCELLED') {
                    // Generate nomor kuitansi pembayaran
                    $year = date('Y');
                    $month = date('m');
                    $prefix = "PAY/{$year}/{$month}/";
                    $lastPayStmt = $pdo->prepare("SELECT payment_number FROM payments WHERE payment_number LIKE :p ORDER BY id DESC LIMIT 1");
                    $lastPayStmt->execute(['p' => "{$prefix}%"]);
                    $lastPayNum = $lastPayStmt->fetchColumn();
                    if ($lastPayNum) {
                        $payParts = explode('/', (string)$lastPayNum);
                        $nextNum = (int)end($payParts) + 1;
                    } else {
                        $nextNum = 1;
                    }
                    $payNum = $prefix . str_pad((string)$nextNum, 4, '0', STR_PAD_LEFT);


                    // Insert ke payments
                    $methodMapped = in_array($paymentType, ['qris', 'bank_transfer', 'edc']) ? $paymentType : 'qris';
                    $payInsert = $pdo->prepare("INSERT INTO payments (payment_number, student_id, bill_id, amount, payment_method, payment_date, status, receipt_issued, notes, recorded_by)
                                                VALUES (:pnum, :sid, :bid, :amt, :method, NOW(), 'completed', 1, :notes, 1)");
                    $payInsert->execute([
                        'pnum' => $payNum,
                        'sid' => (int)$bill['student_id'],
                        'bid' => $billId,
                        'amt' => $paidAmt,
                        'method' => $methodMapped,
                        'notes' => "Pembayaran Online Midtrans [Order ID: {$orderId}]"
                    ]);
                    $paymentId = (int)$pdo->lastInsertId();

                    // Insert ke cash_transactions (Buku Kas Masuk)
                    $trxPrefix = "TRX/" . date('Y/m/');
                    $lastTrxStmt = $pdo->prepare("SELECT transaction_number FROM cash_transactions WHERE transaction_number LIKE :p ORDER BY id DESC LIMIT 1");
                    $lastTrxStmt->execute(['p' => "{$trxPrefix}%"]);
                    $lastTrxNum = $lastTrxStmt->fetchColumn();
                    if ($lastTrxNum) {
                        $trxParts = explode('/', (string)$lastTrxNum);
                        $nextTrxNum = (int)end($trxParts) + 1;
                    } else {
                        $nextTrxNum = 1;
                    }
                    $transNum = $trxPrefix . str_pad((string)$nextTrxNum, 4, '0', STR_PAD_LEFT);

                    $cashDesc = "Pembayaran Online (Midtrans) " . $bill['title'] . " NIM " . $bill['nim'] . " (" . $bill['student_name'] . ")";


                    $cashInsert = $pdo->prepare("INSERT INTO cash_transactions (transaction_number, category_id, type, amount, transaction_date, description, reference_number, payment_method, status, created_by)
                                                 VALUES (:tnum, :cid, 'income', :amt, NOW(), :desc, :ref, :method, 'completed', 1)");
                    $cashInsert->execute([
                        'tnum' => $transNum,
                        'cid' => (int)$bill['category_id'],
                        'amt' => $paidAmt,
                        'desc' => $cashDesc,
                        'ref' => $payNum,
                        'method' => $methodMapped
                    ]);
                    $cashTransId = (int)$pdo->lastInsertId();

                    // Update payment with cash_transaction_id
                    $pdo->prepare("UPDATE payments SET cash_transaction_id = :cid WHERE id = :pid")->execute([
                        'cid' => $cashTransId,
                        'pid' => $paymentId
                    ]);

                    // Update saldo dan status pada bills
                    $newPaidAmount = (float)$bill['paid_amount'] + $paidAmt;
                    $newRemainingAmount = (float)$bill['amount'] - $newPaidAmount;
                    $newBillStatus = ($newRemainingAmount <= 0.001) ? 'PAID' : 'PARTIAL';

                    $pdo->prepare("UPDATE bills SET paid_amount = :paid, remaining_amount = :rem, status = :st WHERE id = :id")->execute([
                        'paid' => $newPaidAmount,
                        'rem' => max(0, $newRemainingAmount),
                        'st' => $newBillStatus,
                        'id' => $billId
                    ]);

                    // Update midtrans_transactions
                    $pdo->prepare("UPDATE midtrans_transactions SET
                                       transaction_status = :st,
                                       payment_type = :ptype,
                                       midtrans_transaction_id = :txid,
                                       fraud_status = :fraud,
                                       payment_id = :pid,
                                       payload_notification = :payload
                                   WHERE order_id = :oid")->execute([
                        'st' => $transactionStatus,
                        'ptype' => $paymentType,
                        'txid' => $midtransTxId,
                        'fraud' => $fraudStatus,
                        'pid' => $paymentId,
                        'payload' => $rawPayloadJson,
                        'oid' => $orderId
                    ]);

                    // Siapkan data untuk trigger WhatsApp
                    $paymentData = [
                        'payment_number' => $payNum,
                        'amount' => $paidAmt,
                        'payment_method' => $paymentType,
                        'bill_title' => $bill['title'],
                        'invoice_number' => $bill['invoice_number'],
                        'student_name' => $bill['student_name'],
                        'student_phone' => $bill['student_phone'],
                        'nim' => $bill['nim']
                    ];
                    $billData = [
                        'invoice_number' => $bill['invoice_number'],
                        'title' => $bill['title'],
                        'amount' => $bill['amount'],
                        'remaining_amount' => max(0, $newRemainingAmount),
                        'status' => $newBillStatus
                    ];
                    $studentData = [
                        'full_name' => $bill['student_name'],
                        'phone' => $bill['student_phone'],
                        'nim' => $bill['nim']
                    ];
                }
            } else {
                // Status pending, expire, cancel, deny
                $pdo->prepare("UPDATE midtrans_transactions SET
                                   transaction_status = :st,
                                   payment_type = :ptype,
                                   midtrans_transaction_id = :txid,
                                   fraud_status = :fraud,
                                   payload_notification = :payload
                               WHERE order_id = :oid")->execute([
                    'st' => $transactionStatus,
                    'ptype' => $paymentType,
                    'txid' => $midtransTxId,
                    'fraud' => $fraudStatus,
                    'payload' => $rawPayloadJson,
                    'oid' => $orderId
                ]);
            }

            // Commit transaksi database
            $pdo->commit();

            // 5. Trigger WhatsApp Notification secara Non-Blocking & Fail-Safe
            if ($isPaid && $paymentData && $billData && $studentData) {
                try {
                    WhatsAppService::notifyPaymentSuccess($paymentData, $billData, $studentData);
                } catch (Exception $waEx) {
                    error_log("WhatsApp notification skipped/failed: " . $waEx->getMessage());
                }
            }

            return [
                'success' => true,
                'http_code' => 200,
                'status' => $transactionStatus,
                'message' => "Notifikasi Midtrans status '{$transactionStatus}' berhasil diproses."
            ];
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log("Midtrans notification processing error: " . $e->getMessage());
            return ['success' => false, 'http_code' => 500, 'message' => 'Gagal memproses notifikasi: ' . $e->getMessage()];
        }
    }
}

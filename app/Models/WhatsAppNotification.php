<?php

namespace App\Models;

use App\Core\Model;
use PDO;

class WhatsAppNotification extends Model
{
    protected string $table = 'whatsapp_notifications';

    /**
     * Sensor nomor telepon untuk tampilan yang aman (Privacy Guard)
     * Contoh: 081234567890 -> 0812****7890 atau 6281234567890 -> 62812****7890
     */
    public static function maskPhone(?string $phone): string
    {
        if (empty($phone)) {
            return '-';
        }
        $clean = preg_replace('/[^0-9]/', '', $phone);
        $len = strlen($clean);
        if ($len <= 6) {
            return $clean;
        }
        $start = substr($clean, 0, min(5, (int)floor($len / 3)));
        $end = substr($clean, -min(4, (int)floor($len / 3)));
        $maskedCount = max(3, $len - strlen($start) - strlen($end));
        return $start . str_repeat('*', $maskedCount) . $end;
    }

    /**
     * Buat record notifikasi baru dengan proteksi idempotency
     */
    public function createNotification(array $data): ?int
    {
        if (!$this->db) {
            return null;
        }

        $idempotencyKey = $data['idempotency_key'] ?? null;
        if (!empty($idempotencyKey)) {
            $existing = $this->findByIdempotencyKey($idempotencyKey);
            if ($existing) {
                return (int)$existing['id'];
            }
        }

        $sql = "INSERT INTO whatsapp_notifications 
                (recipient_phone, recipient_name, recipient_type, student_id, user_id, bill_id, payment_id, 
                 category, template_code, title, message, status, provider_message_id, error_message, 
                 raw_response, attempts, idempotency_key, sent_at)
                VALUES 
                (:phone, :name, :type, :sid, :uid, :bid, :pid, 
                 :cat, :tmpl, :title, :msg, :status, :p_msg_id, :err, 
                 :raw, :attempts, :idem, :sent_at)";

        $stmt = $this->db->prepare($sql);
        $success = $stmt->execute([
            'phone' => trim($data['recipient_phone'] ?? ''),
            'name' => trim($data['recipient_name'] ?? 'Mahasiswa'),
            'type' => $data['recipient_type'] ?? 'student',
            'sid' => !empty($data['student_id']) ? (int)$data['student_id'] : null,
            'uid' => !empty($data['user_id']) ? (int)$data['user_id'] : null,
            'bid' => !empty($data['bill_id']) ? (int)$data['bill_id'] : null,
            'pid' => !empty($data['payment_id']) ? (int)$data['payment_id'] : null,
            'cat' => $data['category'] ?? 'financial_announcement',
            'tmpl' => $data['template_code'] ?? null,
            'title' => trim($data['title'] ?? 'Notifikasi WhatsApp'),
            'msg' => trim($data['message'] ?? ''),
            'status' => $data['status'] ?? 'queued',
            'p_msg_id' => $data['provider_message_id'] ?? null,
            'err' => $data['error_message'] ?? null,
            'raw' => !empty($data['raw_response']) ? (is_string($data['raw_response']) ? $data['raw_response'] : json_encode($data['raw_response'])) : null,
            'attempts' => (int)($data['attempts'] ?? 0),
            'idem' => $idempotencyKey,
            'sent_at' => !empty($data['sent_at']) ? $data['sent_at'] : null,
        ]);

        return $success ? (int)$this->db->lastInsertId() : null;
    }

    /**
     * Cari notifikasi berdasarkan idempotency key
     */
    public function findByIdempotencyKey(string $key): ?array
    {
        if (!$this->db || empty($key)) {
            return null;
        }

        $stmt = $this->db->prepare("SELECT * FROM whatsapp_notifications WHERE idempotency_key = :k LIMIT 1");
        $stmt->execute(['k' => $key]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Cari notifikasi berdasarkan ID Provider (wamid)
     */
    public function findByProviderMessageId(string $providerMessageId): ?array
    {
        if (!$this->db || empty($providerMessageId)) {
            return null;
        }

        $stmt = $this->db->prepare("SELECT * FROM whatsapp_notifications WHERE provider_message_id = :p LIMIT 1");
        $stmt->execute(['p' => $providerMessageId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Update status pengiriman notifikasi
     */
    public function updateStatus(int $id, string $status, ?string $providerMsgId = null, ?string $errorMessage = null, ?array $rawResponse = null): bool
    {
        if (!$this->db) {
            return false;
        }

        $fields = ["status = :status", "attempts = attempts + 1"];
        $params = [
            'id' => $id,
            'status' => $status,
        ];

        if ($providerMsgId !== null) {
            $fields[] = "provider_message_id = :provider_msg_id";
            $params['provider_msg_id'] = $providerMsgId;
        }

        if ($errorMessage !== null) {
            $fields[] = "error_message = :err";
            $params['err'] = $errorMessage;
        }

        if ($rawResponse !== null) {
            $fields[] = "raw_response = :raw";
            $params['raw'] = json_encode($rawResponse);
        }

        if (in_array($status, ['sent', 'delivered', 'read'])) {
            $fields[] = "sent_at = COALESCE(sent_at, NOW())";
        }

        $sql = "UPDATE whatsapp_notifications SET " . implode(", ", $fields) . " WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Periksa apakah pengingat baru-baru ini telah dikirim ke mahasiswa untuk tagihan tertentu
     * Termasuk status 'simulated' agar scheduler tidak membuat duplikat saat mode simulasi aktif.
     */
    public function hasRecentReminder(int $studentId, string $category, int $billId, int $cooldownDays = 1): bool
    {
        if (!$this->db) {
            return false;
        }

        $sql = "SELECT COUNT(*) FROM whatsapp_notifications 
                WHERE student_id = :sid 
                  AND category = :cat 
                  AND bill_id = :bid 
                  AND status IN ('sent', 'delivered', 'read', 'processing', 'simulated')
                  AND created_at >= DATE_SUB(NOW(), INTERVAL :days DAY)";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'sid'  => $studentId,
            'cat'  => $category,
            'bid'  => $billId,
            'days' => $cooldownDays
        ]);

        return (int)$stmt->fetchColumn() > 0;
    }

    /**
     * Ambil notifikasi beserta relasi data mahasiswa, tagihan, dan transaksi
     */
    public function findWithDetails(int $id): ?array
    {
        if (!$this->db) {
            return null;
        }

        $sql = "SELECT wn.*, 
                       s.nim, s.study_program, s.class_group, s.phone AS student_actual_phone,
                       u.full_name AS sender_user_name, u.username AS sender_username,
                       b.invoice_number, b.title AS bill_title, b.amount AS bill_amount, b.remaining_amount AS bill_remaining, b.status AS bill_status,
                       p.payment_number, p.amount AS payment_amount, p.payment_method
                FROM whatsapp_notifications wn
                LEFT JOIN students s ON wn.student_id = s.id
                LEFT JOIN users u ON wn.user_id = u.id
                LEFT JOIN bills b ON wn.bill_id = b.id
                LEFT JOIN payments p ON wn.payment_id = p.id
                WHERE wn.id = :id
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($row) {
            $row['masked_phone'] = self::maskPhone($row['recipient_phone']);
        }

        return $row ?: null;
    }

    /**
     * Statistik ringkasan notifikasi
     * Menghitung 'simulated' terpisah dari pengiriman nyata (sent/delivered/read).
     */
    public function getStats(array $filters = []): array
    {
        if (!$this->db) {
            return ['total' => 0, 'sent' => 0, 'failed' => 0, 'queued' => 0, 'skipped' => 0, 'simulated' => 0];
        }

        $conditions = ["1=1"];
        $params = [];

        if (!empty($filters['category'])) {
            $conditions[] = "category = :cat";
            $params['cat'] = $filters['category'];
        }

        if (!empty($filters['student_id'])) {
            $conditions[] = "student_id = :sid";
            $params['sid'] = (int)$filters['student_id'];
        }

        if (!empty($filters['user_id'])) {
            $conditions[] = "user_id = :uid";
            $params['uid'] = (int)$filters['user_id'];
        }

        if (!empty($filters['allowed_categories']) && is_array($filters['allowed_categories'])) {
            $placeholders = [];
            foreach ($filters['allowed_categories'] as $idx => $allowedCat) {
                $pName = "stats_cat_" . $idx;
                $placeholders[] = ":" . $pName;
                $params[$pName] = $allowedCat;
            }
            if (!empty($placeholders)) {
                $conditions[] = "category IN (" . implode(", ", $placeholders) . ")";
            }
        }

        $where = implode(" AND ", $conditions);

        $sql = "SELECT 
                    COUNT(*) AS total,
                    SUM(CASE WHEN status IN ('sent', 'delivered', 'read') THEN 1 ELSE 0 END) AS sent,
                    SUM(CASE WHEN status = 'failed' THEN 1 ELSE 0 END) AS failed,
                    SUM(CASE WHEN status IN ('queued', 'processing') THEN 1 ELSE 0 END) AS queued,
                    SUM(CASE WHEN status = 'skipped' THEN 1 ELSE 0 END) AS skipped,
                    SUM(CASE WHEN status = 'simulated' THEN 1 ELSE 0 END) AS simulated
                FROM whatsapp_notifications
                WHERE {$where}";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

        return [
            'total'     => (int)($row['total'] ?? 0),
            'sent'      => (int)($row['sent'] ?? 0),
            'failed'    => (int)($row['failed'] ?? 0),
            'queued'    => (int)($row['queued'] ?? 0),
            'skipped'   => (int)($row['skipped'] ?? 0),
            'simulated' => (int)($row['simulated'] ?? 0),
        ];
    }

    /**
     * Paginate data riwayat notifikasi dengan filter
     */
    public function paginate(int $page = 1, int $perPage = 15, array $filters = []): array
    {
        if (!$this->db) {
            return ['data' => [], 'total' => 0, 'totalPages' => 1, 'page' => $page, 'perPage' => $perPage];
        }

        $conditions = ["1=1"];
        $params = [];

        if (!empty($filters['search'])) {
            $conditions[] = "(wn.recipient_name LIKE :q OR wn.recipient_phone LIKE :q2 OR wn.title LIKE :q3 OR s.nim LIKE :q4 OR b.invoice_number LIKE :q5 OR p.payment_number LIKE :q6)";
            $params['q'] = "%" . $filters['search'] . "%";
            $params['q2'] = "%" . $filters['search'] . "%";
            $params['q3'] = "%" . $filters['search'] . "%";
            $params['q4'] = "%" . $filters['search'] . "%";
            $params['q5'] = "%" . $filters['search'] . "%";
            $params['q6'] = "%" . $filters['search'] . "%";
        }

        if (!empty($filters['status'])) {
            $conditions[] = "wn.status = :status";
            $params['status'] = $filters['status'];
        }

        if (!empty($filters['category'])) {
            $conditions[] = "wn.category = :category";
            $params['category'] = $filters['category'];
        }

        if (!empty($filters['date_from'])) {
            $conditions[] = "DATE(wn.created_at) >= :date_from";
            $params['date_from'] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $conditions[] = "DATE(wn.created_at) <= :date_to";
            $params['date_to'] = $filters['date_to'];
        }

        if (!empty($filters['student_id'])) {
            $conditions[] = "wn.student_id = :sid";
            $params['sid'] = (int)$filters['student_id'];
        }

        if (!empty($filters['allowed_categories']) && is_array($filters['allowed_categories'])) {
            $placeholders = [];
            foreach ($filters['allowed_categories'] as $idx => $allowedCat) {
                $pName = "allowed_cat_" . $idx;
                $placeholders[] = ":" . $pName;
                $params[$pName] = $allowedCat;
            }
            if (!empty($placeholders)) {
                $conditions[] = "wn.category IN (" . implode(", ", $placeholders) . ")";
            }
        }

        $whereClause = implode(" AND ", $conditions);

        // Count total
        $countSql = "SELECT COUNT(*) 
                     FROM whatsapp_notifications wn 
                     LEFT JOIN students s ON wn.student_id = s.id 
                     LEFT JOIN bills b ON wn.bill_id = b.id
                     LEFT JOIN payments p ON wn.payment_id = p.id
                     WHERE {$whereClause}";

        $countStmt = $this->db->prepare($countSql);
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        $totalPages = max(1, (int)ceil($total / $perPage));
        $page = max(1, min($page, $totalPages));
        $offset = ($page - 1) * $perPage;

        // Fetch records
        $sql = "SELECT wn.*, 
                       s.nim, s.study_program,
                       u.full_name AS sender_user_name,
                       b.invoice_number,
                       p.payment_number
                FROM whatsapp_notifications wn
                LEFT JOIN students s ON wn.student_id = s.id
                LEFT JOIN users u ON wn.user_id = u.id
                LEFT JOIN bills b ON wn.bill_id = b.id
                LEFT JOIN payments p ON wn.payment_id = p.id
                WHERE {$whereClause}
                ORDER BY wn.created_at DESC, wn.id DESC
                LIMIT {$perPage} OFFSET {$offset}";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Tambahkan masked phone untuk tiap row
        foreach ($data as &$row) {
            $row['masked_phone'] = self::maskPhone($row['recipient_phone']);
        }

        return [
            'data' => $data,
            'total' => $total,
            'totalPages' => $totalPages,
            'page' => $page,
            'perPage' => $perPage
        ];
    }

    /**
     * Ambil notifikasi berstatus antrean (queued) untuk diproses scheduler
     */
    public function getQueued(int $limit = 20): array
    {
        if (!$this->db) {
            return [];
        }

        $stmt = $this->db->prepare("SELECT * FROM whatsapp_notifications WHERE status = 'queued' ORDER BY id ASC LIMIT :limit");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Hapus log notifikasi yang sudah terlalu lama (maintenance)
     */
    public function pruneOldNotifications(int $daysOld = 90): int
    {
        if (!$this->db) {
            return 0;
        }

        $stmt = $this->db->prepare("DELETE FROM whatsapp_notifications WHERE created_at < DATE_SUB(NOW(), INTERVAL :days DAY)");
        $stmt->execute(['days' => $daysOld]);

        return $stmt->rowCount();
    }
}


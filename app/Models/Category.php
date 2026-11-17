<?php

namespace App\Models;

use App\Core\Model;
use PDO;

class Category extends Model
{
    protected string $table = 'categories';

    public function paginate(int $page = 1, int $perPage = 10, array $filters = []): array
    {
        if (!$this->db) {
            return ['data' => [], 'total' => 0, 'totalPages' => 1, 'page' => $page, 'perPage' => $perPage];
        }

        $conditions = ["1=1"];
        $params = [];

        if (!empty($filters['search'])) {
            $conditions[] = "(c.code LIKE :q OR c.name LIKE :q2 OR c.description LIKE :q3)";
            $params['q'] = "%" . $filters['search'] . "%";
            $params['q2'] = "%" . $filters['search'] . "%";
            $params['q3'] = "%" . $filters['search'] . "%";
        }

        if (!empty($filters['type'])) {
            $conditions[] = "c.type = :type";
            $params['type'] = $filters['type'];
        }

        if (isset($filters['is_active']) && $filters['is_active'] !== '') {
            $conditions[] = "c.is_active = :is_active";
            $params['is_active'] = (int)$filters['is_active'];
        }

        $whereClause = implode(" AND ", $conditions);

        // Count total
        $countStmt = $this->db->prepare("SELECT COUNT(*) FROM categories c WHERE {$whereClause}");
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        $totalPages = max(1, (int)ceil($total / $perPage));
        $page = max(1, min($page, $totalPages));
        $offset = ($page - 1) * $perPage;

        // Fetch records
        $sql = "SELECT c.*,
                       (SELECT COUNT(*) FROM cash_transactions WHERE category_id = c.id) AS tx_count,
                       (SELECT COALESCE(SUM(amount), 0) FROM cash_transactions WHERE category_id = c.id) AS total_amount
                FROM categories c
                WHERE {$whereClause}
                ORDER BY c.type ASC, c.id ASC
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

    public function allByType(?string $type = null): array
    {
        if (!$this->db) return [];
        if ($type) {
            $stmt = $this->db->prepare("SELECT * FROM categories WHERE type = :type AND is_active = 1 ORDER BY name ASC");
            $stmt->execute(['type' => $type]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        $stmt = $this->db->query("SELECT * FROM categories WHERE is_active = 1 ORDER BY type ASC, name ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Dapatkan ringkasan per pos anggaran kategori (total nominal & jumlah transaksi)
     */
    public function getCategoriesSummary(): array
    {
        if (!$this->db) {
            return [];
        }

        $sql = "SELECT c.id, c.name, c.code, c.type,
                       COUNT(ct.id) AS total_tx,
                       COALESCE(SUM(ct.amount), 0) AS total_amount
                FROM categories c
                LEFT JOIN cash_transactions ct ON c.id = ct.category_id AND ct.status != 'voided'
                WHERE c.is_active = 1
                GROUP BY c.id, c.name, c.code, c.type
                ORDER BY total_amount DESC, c.name ASC";

        $stmt = $this->db->query($sql);
        return $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    }

    public function isCodeTaken(string $code, ?int $excludeId = null): bool
    {
        if (!$this->db) return false;
        $sql = "SELECT COUNT(*) FROM categories WHERE code = :code" . ($excludeId ? " AND id != :excludeId" : "");
        $stmt = $this->db->prepare($sql);
        $params = ['code' => trim($code)];
        if ($excludeId) $params['excludeId'] = $excludeId;
        $stmt->execute($params);
        return (int)$stmt->fetchColumn() > 0;
    }

    public function create(array $data): ?int
    {
        if (!$this->db) return null;

        $sql = "INSERT INTO categories (code, name, type, description, is_active)
                VALUES (:code, :name, :type, :description, :is_active)";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'code' => strtoupper(trim($data['code'])),
            'name' => trim($data['name']),
            'type' => $data['type'],
            'description' => trim($data['description'] ?? ''),
            'is_active' => isset($data['is_active']) ? (int)$data['is_active'] : 1
        ]);

        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        if (!$this->db) return false;

        $sql = "UPDATE categories SET
                    code = :code,
                    name = :name,
                    type = :type,
                    description = :description,
                    is_active = :is_active
                WHERE id = :id";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'id' => $id,
            'code' => strtoupper(trim($data['code'])),
            'name' => trim($data['name']),
            'type' => $data['type'],
            'description' => trim($data['description'] ?? ''),
            'is_active' => isset($data['is_active']) ? (int)$data['is_active'] : 1
        ]);
    }

    public function hasTransactions(int $id): bool
    {
        if (!$this->db) return false;
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM cash_transactions WHERE category_id = :id");
        $stmt->execute(['id' => $id]);
        return (int)$stmt->fetchColumn() > 0;
    }

    public function delete(int $id): bool
    {
        if (!$this->db) return false;
        $stmt = $this->db->prepare("DELETE FROM categories WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }
}

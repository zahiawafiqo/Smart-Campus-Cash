<?php

namespace App\Models;

use App\Core\Model;
use PDO;

class User extends Model
{
    protected string $table = 'users';

    public function paginate(int $page = 1, int $perPage = 10, array $filters = []): array
    {
        if (!$this->db) {
            return ['data' => [], 'total' => 0, 'totalPages' => 1, 'page' => $page, 'perPage' => $perPage];
        }

        $conditions = ["1=1"];
        $params = [];

        if (!empty($filters['search'])) {
            $conditions[] = "(u.username LIKE :q OR u.full_name LIKE :q2 OR u.email LIKE :q3)";
            $params['q'] = "%" . $filters['search'] . "%";
            $params['q2'] = "%" . $filters['search'] . "%";
            $params['q3'] = "%" . $filters['search'] . "%";
        }

        if (!empty($filters['role_id'])) {
            $conditions[] = "u.role_id = :role_id";
            $params['role_id'] = (int)$filters['role_id'];
        }

        if (!empty($filters['status'])) {
            $conditions[] = "u.status = :status";
            $params['status'] = $filters['status'];
        }

        $whereClause = implode(" AND ", $conditions);

        // Count total
        $countStmt = $this->db->prepare("SELECT COUNT(*) FROM users u WHERE {$whereClause}");
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        $totalPages = max(1, (int)ceil($total / $perPage));
        $page = max(1, min($page, $totalPages));
        $offset = ($page - 1) * $perPage;

        // Fetch data
        $sql = "SELECT u.id, u.role_id, u.username, u.email, u.full_name, u.phone, u.status, u.last_login_at, u.created_at,
                       r.name AS role_name, r.display_name AS role_display
                FROM users u
                JOIN roles r ON u.role_id = r.id
                WHERE {$whereClause}
                ORDER BY u.id ASC
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

    public function findWithRole(int $id): ?array
    {
        if (!$this->db) return null;
        $sql = "SELECT u.*, r.name AS role_name, r.display_name AS role_display
                FROM users u
                JOIN roles r ON u.role_id = r.id
                WHERE u.id = :id
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function isUsernameTaken(string $username, ?int $excludeId = null): bool
    {
        if (!$this->db) return false;
        $sql = "SELECT COUNT(*) FROM users WHERE username = :username" . ($excludeId ? " AND id != :excludeId" : "");
        $stmt = $this->db->prepare($sql);
        $params = ['username' => $username];
        if ($excludeId) $params['excludeId'] = $excludeId;
        $stmt->execute($params);
        return (int)$stmt->fetchColumn() > 0;
    }

    public function isEmailTaken(string $email, ?int $excludeId = null): bool
    {
        if (!$this->db) return false;
        $sql = "SELECT COUNT(*) FROM users WHERE email = :email" . ($excludeId ? " AND id != :excludeId" : "");
        $stmt = $this->db->prepare($sql);
        $params = ['email' => $email];
        if ($excludeId) $params['excludeId'] = $excludeId;
        $stmt->execute($params);
        return (int)$stmt->fetchColumn() > 0;
    }

    public function create(array $data): ?int
    {
        if (!$this->db) return null;

        $sql = "INSERT INTO users (role_id, username, email, password, full_name, phone, status)
                VALUES (:role_id, :username, :email, :password, :full_name, :phone, :status)";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'role_id' => (int)$data['role_id'],
            'username' => trim($data['username']),
            'email' => trim($data['email']),
            'password' => password_hash($data['password'], PASSWORD_BCRYPT),
            'full_name' => trim($data['full_name']),
            'phone' => trim($data['phone'] ?? ''),
            'status' => $data['status'] ?? 'active'
        ]);

        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        if (!$this->db) return false;

        $sql = "UPDATE users SET 
                    role_id = :role_id,
                    username = :username,
                    email = :email,
                    full_name = :full_name,
                    phone = :phone,
                    status = :status
                WHERE id = :id";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'id' => $id,
            'role_id' => (int)$data['role_id'],
            'username' => trim($data['username']),
            'email' => trim($data['email']),
            'full_name' => trim($data['full_name']),
            'phone' => trim($data['phone'] ?? ''),
            'status' => $data['status'] ?? 'active'
        ]);
    }

    public function updateStatus(int $id, string $status): bool
    {
        if (!$this->db) return false;
        $stmt = $this->db->prepare("UPDATE users SET status = :status WHERE id = :id");
        return $stmt->execute(['id' => $id, 'status' => $status]);
    }

    public function resetPassword(int $id, string $newPassword): bool
    {
        if (!$this->db) return false;
        $hashed = password_hash($newPassword, PASSWORD_BCRYPT);
        $stmt = $this->db->prepare("UPDATE users SET password = :password, remember_token = NULL WHERE id = :id");
        return $stmt->execute(['id' => $id, 'password' => $hashed]);
    }

    public function delete(int $id): bool
    {
        if (!$this->db) return false;
        $stmt = $this->db->prepare("DELETE FROM users WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    /**
     * Dapatkan statistik total pengguna terdaftar dan rincian per role secara aktual dari database
     *
     * @return array{total: int, admin: int, mahasiswa: int, kasir: int, keuangan: int, details: array}|null
     */
    public static function getRoleStatistics(): ?array
    {
        $pdo = \App\Core\Database::getConnection();
        if (!$pdo) {
            return null;
        }

        try {
            // Hitung total pengguna terdaftar (setiap akun dihitung 1 kali secara unik)
            $totalStmt = $pdo->query("SELECT COUNT(DISTINCT id) FROM users");
            if ($totalStmt === false) {
                return null;
            }
            $total = (int)$totalStmt->fetchColumn();

            // Hitung pengguna per role dengan query agregasi GROUP BY
            // Gunakan LEFT JOIN agar semua role terdata dengan benar
            $sql = "SELECT r.name, r.display_name, COUNT(DISTINCT u.id) AS user_count
                    FROM roles r
                    LEFT JOIN users u ON u.role_id = r.id
                    GROUP BY r.id, r.name, r.display_name
                    ORDER BY r.id ASC";
            $stmt = $pdo->query($sql);
            if ($stmt === false) {
                return null;
            }
            $roleRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $byRole = [
                'admin' => 0,
                'mahasiswa' => 0,
                'kasir' => 0,
                'keuangan' => 0
            ];
            $roleDetails = [];

            foreach ($roleRows as $row) {
                $roleKey = strtolower(trim($row['name']));
                $count = (int)$row['user_count'];
                $byRole[$roleKey] = $count;
                $roleDetails[$roleKey] = [
                    'name' => $row['name'],
                    'display_name' => $row['display_name'],
                    'count' => $count
                ];
            }

            return [
                'total' => $total,
                'admin' => $byRole['admin'] ?? 0,
                'mahasiswa' => $byRole['mahasiswa'] ?? 0,
                'kasir' => $byRole['kasir'] ?? 0,
                'keuangan' => $byRole['keuangan'] ?? 0,
                'details' => $roleDetails
            ];
        } catch (\PDOException $e) {
            error_log("Error fetching user statistics: " . $e->getMessage());
            return null;
        }
    }
}

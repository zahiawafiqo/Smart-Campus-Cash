<?php

namespace App\Models;

use App\Core\Model;
use PDO;

class Student extends Model
{
    protected string $table = 'students';

    public function paginate(int $page = 1, int $perPage = 10, array $filters = []): array
    {
        if (!$this->db) {
            return ['data' => [], 'total' => 0, 'totalPages' => 1, 'page' => $page, 'perPage' => $perPage];
        }

        $conditions = ["s.deleted_at IS NULL"];
        $params = [];

        if (!empty($filters['search'])) {
            $conditions[] = "(s.nim LIKE :q OR s.full_name LIKE :q2 OR s.email LIKE :q3)";
            $params['q'] = "%" . $filters['search'] . "%";
            $params['q2'] = "%" . $filters['search'] . "%";
            $params['q3'] = "%" . $filters['search'] . "%";
        }

        if (!empty($filters['study_program'])) {
            $conditions[] = "s.study_program = :prodi";
            $params['prodi'] = $filters['study_program'];
        }

        if (!empty($filters['academic_year'])) {
            $conditions[] = "s.academic_year = :year";
            $params['year'] = $filters['academic_year'];
        }

        if (!empty($filters['status'])) {
            $conditions[] = "s.status = :status";
            $params['status'] = $filters['status'];
        }

        if (!empty($filters['faculty'])) {
            $conditions[] = "s.faculty = :faculty";
            $params['faculty'] = $filters['faculty'];
        }

        $whereClause = implode(" AND ", $conditions);

        // Count total
        $countStmt = $this->db->prepare("SELECT COUNT(*) FROM students s WHERE {$whereClause}");
        $countStmt->execute($params);
        $total = (int)$countStmt->fetchColumn();

        $totalPages = max(1, (int)ceil($total / $perPage));
        $page = max(1, min($page, $totalPages));
        $offset = ($page - 1) * $perPage;

        // Fetch records
        $sql = "SELECT s.*, u.username, u.email as user_email,
                       (SELECT COUNT(*) FROM bills WHERE student_id = s.id AND status != 'PAID') AS unpaid_bills_count,
                       (SELECT COALESCE(SUM(remaining_amount), 0) FROM bills WHERE student_id = s.id AND status != 'PAID') AS total_tunggakan
                FROM students s
                LEFT JOIN users u ON s.user_id = u.id
                WHERE {$whereClause}
                ORDER BY s.id DESC
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

    public function findWithUser(int $id): ?array
    {
        if (!$this->db) return null;
        $sql = "SELECT s.*, u.username, u.email as user_email, u.status as user_status, u.last_login_at
                FROM students s
                LEFT JOIN users u ON s.user_id = u.id
                WHERE s.id = :id AND s.deleted_at IS NULL
                LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function findByNim(string $nim): ?array
    {
        if (!$this->db) return null;
        $stmt = $this->db->prepare("SELECT * FROM students WHERE nim = :nim AND deleted_at IS NULL LIMIT 1");
        $stmt->execute(['nim' => trim($nim)]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function findByUserId(int $userId): ?array
    {
        if (!$this->db) return null;
        $stmt = $this->db->prepare("SELECT * FROM students WHERE user_id = :uid AND deleted_at IS NULL LIMIT 1");
        $stmt->execute(['uid' => $userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function isNimTaken(string $nim, ?int $excludeId = null): bool
    {
        if (!$this->db) return false;
        $sql = "SELECT COUNT(*) FROM students WHERE nim = :nim AND deleted_at IS NULL" . ($excludeId ? " AND id != :excludeId" : "");
        $stmt = $this->db->prepare($sql);
        $params = ['nim' => trim($nim)];
        if ($excludeId) $params['excludeId'] = $excludeId;
        $stmt->execute($params);
        return (int)$stmt->fetchColumn() > 0;
    }

    public function create(array $data): ?int
    {
        if (!$this->db) return null;

        $sql = "INSERT INTO students (user_id, nim, full_name, faculty, study_program, academic_year, current_semester, class_group, email, phone, status)
                VALUES (:user_id, :nim, :full_name, :faculty, :study_program, :academic_year, :current_semester, :class_group, :email, :phone, :status)";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'user_id' => !empty($data['user_id']) ? (int)$data['user_id'] : null,
            'nim' => trim($data['nim']),
            'full_name' => trim($data['full_name']),
            'faculty' => trim($data['faculty'] ?? 'Teknik & Informatika'),
            'study_program' => trim($data['study_program']),
            'academic_year' => trim($data['academic_year']),
            'current_semester' => (int)($data['current_semester'] ?? 1),
            'class_group' => trim($data['class_group'] ?? 'A'),
            'email' => trim($data['email'] ?? ''),
            'phone' => trim($data['phone'] ?? ''),
            'status' => $data['status'] ?? 'active'
        ]);

        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        if (!$this->db) return false;

        $sql = "UPDATE students SET
                    nim = :nim,
                    full_name = :full_name,
                    faculty = :faculty,
                    study_program = :study_program,
                    academic_year = :academic_year,
                    current_semester = :current_semester,
                    class_group = :class_group,
                    email = :email,
                    phone = :phone,
                    status = :status
                WHERE id = :id AND deleted_at IS NULL";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'id' => $id,
            'nim' => trim($data['nim']),
            'full_name' => trim($data['full_name']),
            'faculty' => trim($data['faculty'] ?? 'Teknik & Informatika'),
            'study_program' => trim($data['study_program']),
            'academic_year' => trim($data['academic_year']),
            'current_semester' => (int)($data['current_semester'] ?? 1),
            'class_group' => trim($data['class_group'] ?? 'A'),
            'email' => trim($data['email'] ?? ''),
            'phone' => trim($data['phone'] ?? ''),
            'status' => $data['status'] ?? 'active'
        ]);
    }

    public function softDelete(int $id): bool
    {
        if (!$this->db) return false;
        $stmt = $this->db->prepare("UPDATE students SET deleted_at = NOW() WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    public function getDistinctStudyPrograms(): array
    {
        if (!$this->db) return [];
        $stmt = $this->db->query("SELECT DISTINCT study_program FROM students WHERE deleted_at IS NULL ORDER BY study_program ASC");
        return $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
    }

    public function getDistinctAcademicYears(): array
    {
        if (!$this->db) return [];
        $stmt = $this->db->query("SELECT DISTINCT academic_year FROM students WHERE deleted_at IS NULL ORDER BY academic_year DESC");
        return $stmt->fetchAll(PDO::FETCH_COLUMN) ?: [];
    }
}

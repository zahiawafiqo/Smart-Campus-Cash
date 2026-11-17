<?php

namespace App\Core;

use PDO;

abstract class Model
{
    protected ?PDO $db;
    protected string $table = '';

    public function __construct()
    {
        $this->db = Database::getConnection();
    }

    /**
     * Dapatkan koneksi PDO
     */
    public function getDb(): ?PDO
    {
        return $this->db;
    }

    /**
     * Ambil semua record
     */
    public function all(): array
    {
        if (!$this->db || empty($this->table)) {
            return [];
        }

        $stmt = $this->db->query("SELECT * FROM {$this->table}");
        return $stmt->fetchAll();
    }

    /**
     * Temukan record berdasarkan ID
     */
    public function find(int $id): ?array
    {
        if (!$this->db || empty($this->table)) {
            return null;
        }

        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch();

        return $result ?: null;
    }
}

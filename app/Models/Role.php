<?php

namespace App\Models;

use App\Core\Model;
use PDO;

class Role extends Model
{
    protected string $table = 'roles';

    public function all(): array
    {
        if (!$this->db) return [];
        $stmt = $this->db->query("SELECT * FROM roles ORDER BY id ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function findByName(string $name): ?array
    {
        if (!$this->db) return null;
        $stmt = $this->db->prepare("SELECT * FROM roles WHERE LOWER(name) = LOWER(:name) LIMIT 1");
        $stmt->execute(['name' => $name]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }
}

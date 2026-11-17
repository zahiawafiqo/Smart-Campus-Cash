<?php

namespace App\Models;

use App\Core\Model;
use PDO;

class WhatsAppSetting extends Model
{
    protected string $table = 'whatsapp_settings';

    /**
     * Dapatkan nilai pengaturan berdasarkan key
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $instance = new self();
        if (!$instance->db) {
            return $default;
        }

        $stmt = $instance->db->prepare("SELECT setting_value FROM whatsapp_settings WHERE setting_key = :k LIMIT 1");
        $stmt->execute(['k' => $key]);
        $val = $stmt->fetchColumn();

        return ($val !== false && $val !== null) ? $val : $default;
    }

    /**
     * Simpan atau perbarui nilai pengaturan
     */
    public static function set(string $key, mixed $value, ?string $description = null): bool
    {
        $instance = new self();
        if (!$instance->db) {
            return false;
        }

        $valStr = (string)$value;

        $stmt = $instance->db->prepare("SELECT id FROM whatsapp_settings WHERE setting_key = :k LIMIT 1");
        $stmt->execute(['k' => $key]);
        $existingId = $stmt->fetchColumn();

        if ($existingId) {
            $sql = "UPDATE whatsapp_settings SET setting_value = :v, updated_at = NOW() WHERE setting_key = :k";
            $updateStmt = $instance->db->prepare($sql);
            return $updateStmt->execute(['v' => $valStr, 'k' => $key]);
        } else {
            $sql = "INSERT INTO whatsapp_settings (setting_key, setting_value, description, created_at, updated_at) 
                    VALUES (:k, :v, :d, NOW(), NOW())";
            $insertStmt = $instance->db->prepare($sql);
            return $insertStmt->execute(['k' => $key, 'v' => $valStr, 'd' => $description]);
        }
    }

    /**
     * Dapatkan seluruh setting sebagai key-value associative array
     */
    public static function allKeyValues(): array
    {
        $instance = new self();
        if (!$instance->db) {
            return [];
        }

        $stmt = $instance->db->query("SELECT setting_key, setting_value, description FROM whatsapp_settings ORDER BY id ASC");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $result = [];
        foreach ($rows as $row) {
            $result[$row['setting_key']] = [
                'value' => $row['setting_value'],
                'description' => $row['description']
            ];
        }

        return $result;
    }
}

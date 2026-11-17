<?php

namespace App\Models;

use App\Core\Model;
use PDO;

class WhatsAppTemplate extends Model
{
    protected string $table = 'whatsapp_templates';

    /**
     * Dapatkan semua template aktif
     */
    public function allActive(): array
    {
        if (!$this->db) {
            return [];
        }

        $stmt = $this->db->query("SELECT * FROM whatsapp_templates WHERE is_active = 1 ORDER BY category ASC, name ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Cari template berdasarkan kode (misal: BILL_CREATED, DUE_REMINDER)
     */
    public function findByCode(string $code): ?array
    {
        if (!$this->db) {
            return null;
        }

        $stmt = $this->db->prepare("SELECT * FROM whatsapp_templates WHERE code = :code LIMIT 1");
        $stmt->execute(['code' => trim($code)]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Cari template berdasarkan kategori
     */
    public function findByCategory(string $category): array
    {
        if (!$this->db) {
            return [];
        }

        $stmt = $this->db->prepare("SELECT * FROM whatsapp_templates WHERE category = :cat AND is_active = 1 ORDER BY id ASC");
        $stmt->execute(['cat' => trim($category)]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Update konten template
     */
    public function updateTemplate(int $id, array $data): bool
    {
        if (!$this->db) {
            return false;
        }

        $sql = "UPDATE whatsapp_templates SET 
                    name = :name,
                    subject = :subject,
                    body = :body,
                    description = :desc,
                    is_active = :active
                WHERE id = :id";

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'id' => $id,
            'name' => trim($data['name'] ?? ''),
            'subject' => trim($data['subject'] ?? ''),
            'body' => trim($data['body'] ?? ''),
            'desc' => trim($data['description'] ?? ''),
            'active' => isset($data['is_active']) ? (int)$data['is_active'] : 1
        ]);
    }

    /**
     * Render template teks dengan mengganti placeholder variabel dengan data nyata.
     * Mendukung format [placeholder] dan {placeholder}.
     */
    public static function render(string $body, array $variables): string
    {
        $rendered = $body;

        // Siapkan alias otomatis untuk fleksibilitas template
        if (isset($variables['jenis_tagihan']) && !isset($variables['nama_tagihan'])) {
            $variables['nama_tagihan'] = $variables['jenis_tagihan'];
        }
        if (isset($variables['nama_tagihan']) && !isset($variables['jenis_tagihan'])) {
            $variables['jenis_tagihan'] = $variables['nama_tagihan'];
        }
        if (isset($variables['nominal_pembayaran']) && !isset($variables['nominal'])) {
            $variables['nominal'] = $variables['nominal_pembayaran'];
        }

        foreach ($variables as $key => $val) {
            $cleanKey = trim($key, '[]{}');
            $valStr = (string)$val;
            // Ganti format [key] dan {key}
            $rendered = str_replace(
                ['[' . $cleanKey . ']', '{' . $cleanKey . '}'],
                $valStr,
                $rendered
            );
        }

        return $rendered;
    }
}

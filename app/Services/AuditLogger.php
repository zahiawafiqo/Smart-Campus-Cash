<?php

namespace App\Services;

use App\Core\Database;
use PDO;
use Exception;

class AuditLogger
{
    /**
     * Catat aktivitas ke tabel audit_logs
     */
    public static function log(string $action, ?string $entityType = null, ?int $entityId = null, ?array $oldValues = null, ?array $newValues = null): void
    {
        try {
            $pdo = Database::getConnection();
            if (!$pdo) {
                return;
            }

            $userId = AuthService::id();
            $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
            $ua = substr($_SERVER['HTTP_USER_AGENT'] ?? 'CLI/System', 0, 255);

            $stmt = $pdo->prepare("INSERT INTO audit_logs (user_id, action, entity_type, entity_id, old_values, new_values, ip_address, user_agent, created_at)
                                   VALUES (:uid, :act, :ent, :eid, :old, :new, :ip, :ua, NOW())");

            $stmt->execute([
                'uid' => $userId,
                'act' => $action,
                'ent' => $entityType,
                'eid' => $entityId,
                'old' => $oldValues ? json_encode($oldValues, JSON_UNESCAPED_UNICODE) : null,
                'new' => $newValues ? json_encode($newValues, JSON_UNESCAPED_UNICODE) : null,
                'ip' => substr($ip, 0, 45),
                'ua' => $ua
            ]);
        } catch (Exception $e) {
            error_log("AuditLogger failed: " . $e->getMessage());
        }
    }
}

<?php

namespace App\Core;

use PDO;
use PDOException;

class Database
{
    private static ?PDO $instance = null;
    private static ?string $lastError = null;

    /**
     * Dapatkan koneksi instance PDO Singleton
     */
    public static function getConnection(): ?PDO
    {
        if (self::$instance === null) {
            $config = require dirname(__DIR__, 2) . '/config/database.php';
            $default = $config['default'] ?? 'mysql';
            $dbConfig = $config['connections'][$default] ?? [];

            $dsn = sprintf(
                "mysql:host=%s;port=%s;dbname=%s;charset=%s",
                $dbConfig['host'] ?? '127.0.0.1',
                $dbConfig['port'] ?? 3306,
                $dbConfig['database'] ?? '',
                $dbConfig['charset'] ?? 'utf8mb4'
            );

            try {
                self::$instance = new PDO(
                    $dsn,
                    $dbConfig['username'] ?? 'root',
                    $dbConfig['password'] ?? '',
                    $dbConfig['options'] ?? [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false,
                    ]
                );
                self::$lastError = null;
            } catch (PDOException $e) {
                self::$lastError = $e->getMessage();
                // Jangan crash aplikasi saat setup awal jika database belum dibuat di MySQL
                error_log("Database Connection Error: " . $e->getMessage());
                return null;
            }
        }

        return self::$instance;
    }

    /**
     * Cek status koneksi database
     */
    public static function checkConnection(): array
    {
        $pdo = self::getConnection();
        if ($pdo !== null) {
            return [
                'status' => true,
                'message' => 'Terhubung ke MySQL Database secara sukses.'
            ];
        }

        return [
            'status' => false,
            'message' => self::$lastError ?? 'Tidak dapat terhubung ke database. Pastikan MySQL di Laragon aktif dan nama database sesuai.'
        ];
    }
}

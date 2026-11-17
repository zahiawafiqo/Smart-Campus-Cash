<?php

namespace App\Services;

use App\Core\Database;
use PDO;

class AuthService
{
    /**
     * Inisialisasi session dengan parameter keamanan dan auto-login Remember Me
     */
    public static function initSession(): void
    {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            // Konfigurasi cookie sesi yang aman
            ini_set('session.cookie_httponly', '1');
            ini_set('session.use_only_cookies', '1');
            ini_set('session.cookie_samesite', 'Lax');

            session_start();
        }

        // Cek Remember Me jika session user belum ada tapi cookie remember_me tersedia
        if (empty($_SESSION['user_id']) && !empty($_COOKIE['remember_me'])) {
            self::attemptRememberLogin($_COOKIE['remember_me']);
        }
    }

    /**
     * Cek apakah user sedang login
     */
    public static function check(): bool
    {
        self::initSession();
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }

    /**
     * Dapatkan data user saat ini
     */
    public static function user(): ?array
    {
        self::initSession();
        return $_SESSION['user'] ?? null;
    }

    /**
     * Dapatkan ID user saat ini
     */
    public static function id(): ?int
    {
        self::initSession();
        return $_SESSION['user_id'] ?? null;
    }

    /**
     * Dapatkan nama role user saat ini ('admin', 'mahasiswa', 'kasir', 'keuangan')
     */
    public static function role(): ?string
    {
        self::initSession();
        $role = $_SESSION['role']['name'] ?? null;
        return $role ? strtolower(trim($role)) : null;
    }


    /**
     * Dapatkan data profil mahasiswa jika role adalah mahasiswa
     */
    public static function student(): ?array
    {
        self::initSession();
        return $_SESSION['student'] ?? null;
    }

    /**
     * Periksa apakah user memiliki role tertentu
     */
    public static function isRole(string|array $roles): bool
    {
        $currentRole = self::role();
        if (!$currentRole) {
            return false;
        }

        if (is_string($roles)) {
            return strtolower($currentRole) === strtolower($roles);
        }

        return in_array(strtolower($currentRole), array_map('strtolower', $roles));
    }

    /**
     * Proses otentikasi login
     */
    public static function attempt(string $identifier, string $password, bool $remember = false): array
    {
        self::initSession();
        $pdo = Database::getConnection();

        if (!$pdo) {
            return ['success' => false, 'message' => 'Koneksi database terputus. Silakan coba lagi.'];
        }

        // Cari berdasarkan username, email, atau NIM
        $sql = "SELECT u.*, r.name AS role_name, r.display_name AS role_display,
                       s.id AS student_id, s.nim, s.study_program, s.academic_year, s.current_semester, s.class_group
                FROM users u
                JOIN roles r ON u.role_id = r.id
                LEFT JOIN students s ON s.user_id = u.id AND s.deleted_at IS NULL
                WHERE u.username = :ident1 OR u.email = :ident2 OR s.nim = :ident3
                LIMIT 1";

        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            'ident1' => $identifier,
            'ident2' => $identifier,
            'ident3' => $identifier,
        ]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            return ['success' => false, 'message' => 'Identitas pengguna (Username/Email/NIM) tidak ditemukan.'];
        }

        // Cek status akun
        if ($user['status'] !== 'active') {
            return ['success' => false, 'message' => 'Akun Anda sedang dinonaktifkan atau ditangguhkan. Hubungi Administrator.'];
        }

        // Verifikasi password bcrypt
        if (!password_verify($password, $user['password'])) {
            return ['success' => false, 'message' => 'Kata sandi yang Anda masukkan salah.'];
        }

        // Cegah Session Fixation
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }

        // Simpan data ke session
        self::populateSession($user);

        // Handle Remember Me Cookie
        if ($remember) {
            $rawToken = bin2hex(random_bytes(32));
            $hashedToken = hash('sha256', $rawToken);

            $updateTokenStmt = $pdo->prepare("UPDATE users SET remember_token = :token WHERE id = :id");
            $updateTokenStmt->execute(['token' => $hashedToken, 'id' => $user['id']]);

            $cookieValue = $user['id'] . ':' . $rawToken;
            setcookie(
                'remember_me',
                $cookieValue,
                [
                    'expires' => time() + (86400 * 30), // 30 hari
                    'path' => '/',
                    'httponly' => true,
                    'samesite' => 'Lax'
                ]
            );
        }

        // Perbarui last_login_at
        $updateStmt = $pdo->prepare("UPDATE users SET last_login_at = NOW() WHERE id = :id");
        $updateStmt->execute(['id' => $user['id']]);

        // Catat ke Audit Log
        AuditLogger::log('LOGIN', 'auth', (int)$user['id'], null, [
            'username' => $user['username'],
            'role' => $user['role_name']
        ]);

        return [
            'success' => true,
            'role' => $user['role_name'],
            'user' => $_SESSION['user'],
            'message' => 'Login berhasil! Selamat datang, ' . $user['full_name'] . '.'
        ];
    }

    /**
     * Memulihkan sesi otomatis melalui cookie remember_me yang terenkripsi
     */
    protected static function attemptRememberLogin(string $cookieValue): void
    {
        $parts = explode(':', $cookieValue, 2);
        if (count($parts) !== 2) {
            return;
        }

        [$userId, $rawToken] = $parts;
        $pdo = Database::getConnection();
        if (!$pdo) return;

        $stmt = $pdo->prepare("SELECT u.*, r.name AS role_name, r.display_name AS role_display,
                                      s.id AS student_id, s.nim, s.study_program, s.academic_year, s.current_semester, s.class_group
                               FROM users u
                               JOIN roles r ON u.role_id = r.id
                               LEFT JOIN students s ON s.user_id = u.id AND s.deleted_at IS NULL
                               WHERE u.id = :id AND u.status = 'active'
                               LIMIT 1");
        $stmt->execute(['id' => (int)$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && !empty($user['remember_token'])) {
            $hashedToken = hash('sha256', $rawToken);
            if (hash_equals($user['remember_token'], $hashedToken)) {
                session_regenerate_id(true);
                self::populateSession($user);
            }
        }
    }

    /**
     * Set data session user
     */
    protected static function populateSession(array $user): void
    {
        $_SESSION['user_id'] = (int)$user['id'];
        $_SESSION['user'] = [
            'id' => (int)$user['id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'full_name' => $user['full_name'],
            'phone' => $user['phone'],
            'status' => $user['status']
        ];
        $_SESSION['role'] = [
            'id' => (int)$user['role_id'],
            'name' => $user['role_name'],
            'display_name' => $user['role_display']
        ];

        if (!empty($user['student_id'])) {
            $_SESSION['student'] = [
                'id' => (int)$user['student_id'],
                'nim' => $user['nim'],
                'full_name' => $user['full_name'],
                'study_program' => $user['study_program'],
                'academic_year' => $user['academic_year'],
                'current_semester' => (int)$user['current_semester'],
                'class_group' => $user['class_group']
            ];
        } else {
            $_SESSION['student'] = null;
        }
    }

    /**
     * Logout dan bersihkan seluruh sesi dan remember cookie
     */
    public static function logout(): void
    {
        self::initSession();

        if (isset($_SESSION['user_id'])) {
            $userId = (int)$_SESSION['user_id'];
            AuditLogger::log('LOGOUT', 'auth', $userId, null, null);

            // Bersihkan remember_token di database
            $pdo = Database::getConnection();
            if ($pdo) {
                $pdo->prepare("UPDATE users SET remember_token = NULL WHERE id = :id")->execute(['id' => $userId]);
            }
        }

        // Hapus cookie remember_me
        if (isset($_COOKIE['remember_me'])) {
            setcookie('remember_me', '', [
                'expires' => time() - 3600,
                'path' => '/',
                'httponly' => true,
                'samesite' => 'Lax'
            ]);
            unset($_COOKIE['remember_me']);
        }

        $_SESSION = [];

        if (ini_get("session.use_cookies") && !headers_sent()) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }

        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
    }

    /**
     * Dapatkan URL redirect default sesuai role
     */
    public static function getRedirectForRole(?string $role = null): string
    {
        $role = strtolower(trim((string)($role ?? self::role() ?? '')));
        return match ($role) {
            'admin' => url('admin/dashboard'),
            'mahasiswa' => url('mahasiswa/dashboard'),
            'kasir' => url('kasir/dashboard'),
            'keuangan' => url('keuangan/dashboard'),
            default => url('login'),
        };
    }
}


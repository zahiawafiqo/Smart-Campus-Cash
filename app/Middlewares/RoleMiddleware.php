<?php

namespace App\Middlewares;

use App\Core\Middleware;
use App\Core\View;
use App\Services\AuthService;
use App\Services\AuditLogger;

class RoleMiddleware implements Middleware
{
    /**
     * Handle pengecekan izin peran (Role Authorization)
     * 
     * @param array $params Berisi role atau daftar role yang diizinkan, contoh: ['admin'] atau ['kasir', 'keuangan']
     */
    public function handle(array $params = []): bool
    {
        // Pastikan terotentikasi dulu
        if (!AuthService::check()) {
            $_SESSION['intended_url'] = $_SERVER['REQUEST_URI'] ?? '';
            $_SESSION['flash_error'] = 'Silakan login terlebih dahulu untuk mengakses halaman tersebut.';
            header("Location: " . url('login'));
            exit;
        }

        $userRole = AuthService::role();
        $allowedRoles = $params;

        // Jika tidak ada argumen role, izinkan
        if (empty($allowedRoles)) {
            return true;
        }

        // Admin memiliki Full Access ke seluruh sistem
        if (strtolower($userRole) === 'admin') {
            return true;
        }

        // Cek apakah peran user diizinkan
        if (in_array(strtolower($userRole), array_map('strtolower', $allowedRoles))) {
            return true;
        }

        // Catat insiden pelanggaran otorisasi ke Audit Log
        AuditLogger::log('UNAUTHORIZED_ACCESS_ATTEMPT', 'security', AuthService::id(), null, [
            'attempted_url' => $_SERVER['REQUEST_URI'] ?? '',
            'user_role' => $userRole,
            'required_roles' => $allowedRoles
        ]);

        // 403 Forbidden
        http_response_code(403);
        View::render('errors/403', [
            'title' => '403 - Akses Ditolak',
            'userRole' => $userRole,
            'allowedRoles' => $allowedRoles,
            'attemptedUrl' => $_SERVER['REQUEST_URI'] ?? ''
        ], 'layouts/guest');
        exit;
    }
}

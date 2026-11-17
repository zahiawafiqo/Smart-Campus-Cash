<?php

namespace App\Middlewares;

use App\Core\Middleware;
use App\Services\AuthService;

class AuthMiddleware implements Middleware
{
    public function handle(array $params = []): bool
    {
        if (!AuthService::check()) {
            // Simpan URL yang dituju untuk redirect balik setelah login
            $_SESSION['intended_url'] = $_SERVER['REQUEST_URI'] ?? '';
            $_SESSION['flash_error'] = 'Silakan masuk terlebih dahulu untuk mengakses halaman tersebut.';
            
            header("Location: " . url('login'));
            exit;
        }

        return true;
    }
}

<?php

namespace App\Middlewares;

use App\Core\Middleware;
use App\Services\AuthService;

class CsrfMiddleware implements Middleware
{
    public function handle(array $params = []): bool
    {
        AuthService::initSession();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $submittedToken = $_POST['_csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
            $sessionToken = $_SESSION['_csrf_token'] ?? '';

            if (empty($sessionToken) || !hash_equals($sessionToken, $submittedToken)) {
                http_response_code(419);
                $_SESSION['flash_error'] = 'Sesi keamanan form Anda telah kedaluwarsa. Silakan refresh halaman dan coba kembali.';
                header("Location: " . ($_SERVER['HTTP_REFERER'] ?? url('login')));
                exit;
            }
        }

        return true;
    }
}

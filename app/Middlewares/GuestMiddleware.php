<?php

namespace App\Middlewares;

use App\Core\Middleware;
use App\Services\AuthService;

class GuestMiddleware implements Middleware
{
    public function handle(array $params = []): bool
    {
        if (AuthService::check()) {
            // Sudah login, arahkan ke dashboard sesuai rolenya
            header("Location: " . AuthService::getRedirectForRole());
            exit;
        }

        return true;
    }
}

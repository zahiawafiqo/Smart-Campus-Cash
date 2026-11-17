<?php

namespace App\Middlewares;

use App\Core\Middleware;

class RateLimitMiddleware implements Middleware
{
    /**
     * Pembatas laju request (Rate Limiting) per sesi/IP
     * @param array $params [maxRequests, windowSeconds] default: [15, 60]
     */
    public function handle(array $params = []): bool
    {
        $maxRequests = (int)($params[0] ?? 15);
        $window = (int)($params[1] ?? 60);

        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            session_start();
        }

        $now = time();
        $key = 'rate_limit_' . md5($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1');

        if (!isset($_SESSION[$key]) || !is_array($_SESSION[$key])) {
            $_SESSION[$key] = [];
        }

        // Filter out timestamps older than the window
        $_SESSION[$key] = array_filter($_SESSION[$key], fn($ts) => ($now - $ts) < $window);

        if (count($_SESSION[$key]) >= $maxRequests) {
            http_response_code(429);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => "Terlalu banyak permintaan. Silakan tunggu beberapa saat (Batas: {$maxRequests} permintaan per {$window} detik)."
            ]);
            exit;
        }

        $_SESSION[$key][] = $now;
        return true;
    }
}

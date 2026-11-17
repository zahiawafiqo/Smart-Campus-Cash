<?php

namespace App\Core;

interface Middleware
{
    /**
     * Handle incoming request
     * 
     * @param array $params Parameter rute atau argumen middleware
     * @return bool True jika diizinkan lanjut, False jika dihentikan/redirect
     */
    public function handle(array $params = []): bool;
}

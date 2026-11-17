<?php

namespace App\Controllers;

use App\Core\Controller;

class DashboardController extends Controller
{
    /**
     * Tampilkan template dashboard atau alihkan ke role dashboard pengguna
     */
    public function index(): void
    {
        // Jika user sudah terotentikasi, redirect ke rute dashboard spesifik role
        if (\App\Services\AuthService::check()) {
            $this->redirect(\App\Services\AuthService::getRedirectForRole());
            return;
        }

        // Jika belum login, arahkan ke login
        $this->redirect(url('login'));
    }
}

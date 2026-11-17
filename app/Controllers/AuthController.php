<?php

namespace App\Controllers;

use App\Core\Controller;
use App\Services\AuthService;

class AuthController extends Controller
{
    /**
     * Tampilkan formulir login
     */
    public function login(): void
    {
        // Jika sudah login, redirect langsung ke dashboard role
        if (AuthService::check()) {
            $this->redirect(AuthService::getRedirectForRole());
            return;
        }

        $roleHint = $this->input('role', 'admin');
        $error = flash('flash_error');
        $success = flash('flash_success');

        $this->render('auth/login', [
            'title' => 'Masuk - Smart Campus Cash',
            'roleHint' => $roleHint,
            'error' => $error,
            'success' => $success
        ], 'layouts/guest');
    }

    /**
     * Proses submit form login
     */
    public function authenticate(): void
    {
        $identifier = trim((string)$this->input('identity', ''));
        $password = (string)$this->input('password', '');
        $remember = (bool)$this->input('remember', false);

        // 1. Validasi input
        if (empty($identifier)) {
            $_SESSION['flash_error'] = 'Kolom Username, Email, atau NIM wajib diisi.';
            $this->redirect(url('login'));
            return;
        }

        if (empty($password)) {
            $_SESSION['flash_error'] = 'Kolom kata sandi wajib diisi.';
            $this->redirect(url('login'));
            return;
        }

        // 2. Eksekusi attempt autentikasi
        $result = AuthService::attempt($identifier, $password, $remember);

        if (!$result['success']) {
            $_SESSION['flash_error'] = $result['message'];
            $this->redirect(url('login'));
            return;
        }

        // 3. Sukses: Cek intended URL
        $intendedUrl = $_SESSION['intended_url'] ?? '';
        unset($_SESSION['intended_url']);

        $userRole = strtolower(trim($result['role'] ?? ''));

        if (!empty($intendedUrl) && !str_contains($intendedUrl, 'login') && !str_contains($intendedUrl, 'logout')) {
            $parsedPath = parse_url($intendedUrl, PHP_URL_PATH) ?? '';
            
            // Jika intended URL adalah dashboard umum atau dashboard role lain yang tidak diizinkan, arahkan ke role dashboard
            if (str_ends_with($parsedPath, '/dashboard') || (str_contains($intendedUrl, 'admin/') && $userRole !== 'admin')) {
                $this->redirect(AuthService::getRedirectForRole($userRole));
                return;
            }

            $this->redirect($intendedUrl);
            return;
        }

        // 4. Redirect default ke dashboard sesuai role
        $this->redirect(AuthService::getRedirectForRole($userRole));
    }


    /**
     * Proses logout
     */
    public function logout(): void
    {
        AuthService::logout();
        $_SESSION['flash_success'] = 'Anda telah berhasil keluar dari sistem.';
        $this->redirect(url('login'));
    }
}

<?php

use App\Core\App;
use App\Core\View;

if (!function_exists('env')) {
    /**
     * Dapatkan nilai environment variable
     */
    function env(string $key, mixed $default = null): mixed
    {
        return \App\Core\Env::get($key, $default);
    }
}

if (!function_exists('url')) {
    /**
     * Dapatkan URL penuh untuk path tertentu
     */
    function url(string $path = ''): string
    {
        $base = App::getBaseUrl();
        $path = ltrim($path, '/');
        return empty($path) ? $base : $base . '/' . $path;
    }
}

if (!function_exists('asset')) {
    /**
     * Dapatkan URL aset static (css, js, img)
     */
    function asset(string $path = ''): string
    {
        $base = App::getBaseUrl();
        $path = ltrim($path, '/');
        return $base . '/assets/' . $path;
    }
}

if (!function_exists('e')) {
    /**
     * Escape string untuk mencegah XSS
     */
    function e(?string $value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8', false);
    }
}

if (!function_exists('render_component')) {
    /**
     * Shortcut untuk me-render komponen UI reusable
     */
    function render_component(string $name, array $props = []): void
    {
        View::component($name, $props);
    }
}

if (!function_exists('csrf_token')) {
    /**
     * Hasilkan atau dapatkan CSRF token sesi
     */
    function csrf_token(): string
    {
        \App\Services\AuthService::initSession();
        if (empty($_SESSION['_csrf_token'])) {
            $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_csrf_token'];
    }
}

if (!function_exists('csrf_field')) {
    /**
     * Render hidden input element untuk CSRF token
     */
    function csrf_field(): string
    {
        return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
    }
}

if (!function_exists('auth_check')) {
    function auth_check(): bool
    {
        return \App\Services\AuthService::check();
    }
}

if (!function_exists('auth_user')) {
    function auth_user(): ?array
    {
        return \App\Services\AuthService::user();
    }
}

if (!function_exists('auth_role')) {
    function auth_role(): ?string
    {
        return \App\Services\AuthService::role();
    }
}

if (!function_exists('flash')) {
    /**
     * Ambil dan hapus flash message dari sesi
     */
    function flash(string $key): ?string
    {
        \App\Services\AuthService::initSession();
        if (isset($_SESSION[$key])) {
            $msg = $_SESSION[$key];
            unset($_SESSION[$key]);
            return $msg;
        }
        return null;
    }
}

if (!function_exists('has_flash')) {
    function has_flash(string $key): bool
    {
        \App\Services\AuthService::initSession();
        return isset($_SESSION[$key]);
    }
}


<?php

namespace App\Core;

abstract class Controller
{
    /**
     * Render view template
     */
    protected function render(string $view, array $data = [], ?string $layout = 'layouts/app'): void
    {
        View::render($view, $data, $layout);
    }

    /**
     * Kembalikan respons JSON
     */
    protected function json(mixed $data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Redirect ke URL tertentu
     */
    protected function redirect(string $url): void
    {
        header("Location: {$url}");
        exit;
    }

    /**
     * Ambil input dari request (GET/POST/JSON body)
     */
    protected function input(?string $key = null, mixed $default = null): mixed
    {
        $input = array_merge($_GET, $_POST);
        
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        if (str_contains($contentType, 'application/json')) {
            $json = json_decode(file_get_contents('php://input'), true);
            if (is_array($json)) {
                $input = array_merge($input, $json);
            }
        }

        if ($key === null) {
            return $input;
        }

        return $input[$key] ?? $default;
    }
}

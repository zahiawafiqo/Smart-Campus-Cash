<?php

namespace App\Core;

class Env
{
    /**
     * Memuat file .env ke environment PHP
     */
    public static function load(string $path): void
    {
        if (!file_exists($path)) {
            return;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);

            // Lewati komentar
            if (empty($line) || str_starts_with($line, '#')) {
                continue;
            }

            // Pisahkan key dan value
            if (str_contains($line, '=')) {
                list($name, $value) = explode('=', $line, 2);
                $name = trim($name);
                $value = trim($value);

                // Bersihkan kutip ganda atau tunggal
                if ((str_starts_with($value, '"') && str_ends_with($value, '"')) ||
                    (str_starts_with($value, "'") && str_ends_with($value, "'"))) {
                    $value = substr($value, 1, -1);
                }

                if (!array_key_exists($name, $_SERVER) && !array_key_exists($name, $_ENV)) {
                    putenv(sprintf('%s=%s', $name, $value));
                    $_ENV[$name] = $value;
                    $_SERVER[$name] = $value;
                }
            }
        }
    }

    /**
     * Mengambil nilai environment dengan fallback
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $value = getenv($key);

        if ($value === false) {
            $value = $_ENV[$key] ?? $_SERVER[$key] ?? $default;
        }

        if ($value === 'true' || $value === '(true)') {
            return true;
        }
        if ($value === 'false' || $value === '(false)') {
            return false;
        }
        if ($value === 'null' || $value === '(null)') {
            return null;
        }
        if ($value === 'empty' || $value === '(empty)') {
            return '';
        }

        return $value;
    }
}


<?php

namespace App\Core;

class App
{
    protected static array $routes = [];
    protected static string $baseUri = '';

    /**
     * Daftarkan route GET dengan opsi middleware
     */
    public static function get(string $uri, mixed $action, array $middlewares = []): void
    {
        self::addRoute('GET', $uri, $action, $middlewares);
    }

    /**
     * Daftarkan route POST dengan opsi middleware
     */
    public static function post(string $uri, mixed $action, array $middlewares = []): void
    {
        self::addRoute('POST', $uri, $action, $middlewares);
    }

    /**
     * Menambahkan route internal
     */
    protected static function addRoute(string $method, string $uri, mixed $action, array $middlewares = []): void
    {
        $uri = '/' . trim($uri, '/');
        self::$routes[$method][$uri] = [
            'action' => $action,
            'middlewares' => $middlewares
        ];
    }

    /**
     * Jalankan aplikasi dan tangani request masuk
     */
    public static function run(): void
    {
        // Inisialisasi sesi aplikasi
        \App\Services\AuthService::initSession();

        // Deteksi Base URI otomatis untuk Laragon subdirectory / built-in server
        self::detectBaseUri();

        $requestMethod = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $requestUri = self::getCurrentUri();

        $routesForMethod = self::$routes[$requestMethod] ?? [];

        // Pencocokan eksak
        if (isset($routesForMethod[$requestUri])) {
            $routeData = $routesForMethod[$requestUri];
            if (self::runMiddlewares($routeData['middlewares'])) {
                self::executeAction($routeData['action']);
            }
            return;
        }

        // Pencocokan parameter dinamis (contoh: /detail/{id})
        foreach ($routesForMethod as $routePattern => $routeData) {
            $regex = preg_replace('#\{([a-zA-Z0-9_]+)\}#', '([^/]+)', $routePattern);
            $regex = '#^' . $regex . '$#';

            if (preg_match($regex, $requestUri, $matches)) {
                array_shift($matches); // Hilangkan elemen pertama (full match)
                if (self::runMiddlewares($routeData['middlewares'])) {
                    self::executeAction($routeData['action'], $matches);
                }
                return;
            }
        }

        // 404 Not Found
        http_response_code(404);
        View::render('errors/404', [
            'title' => '404 - Halaman Tidak Ditemukan',
            'requestedUri' => $requestUri
        ], 'layouts/guest');
    }

    /**
     * Eksekusi rangkaian middleware untuk rute yang cocok
     */
    protected static function runMiddlewares(array $middlewares): bool
    {
        foreach ($middlewares as $mw) {
            $class = $mw;
            $params = [];

            if (is_array($mw)) {
                $class = $mw[0] ?? '';
                $params = $mw[1] ?? [];
                if (!is_array($params)) {
                    $params = [$params];
                }
            }

            if (is_string($class) && class_exists($class)) {
                $instance = new $class();
                if ($instance instanceof Middleware) {
                    if (!$instance->handle($params)) {
                        return false;
                    }
                }
            }
        }
        return true;
    }

    /**
     * Deteksi base directory secara akurat
     */
    protected static function detectBaseUri(): void
    {
        $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
        $dir = str_replace('\\', '/', dirname($scriptName));
        
        if ($dir === '/' || $dir === '.' || $dir === '') {
            self::$baseUri = '';
        } else {
            $trimmed = trim($dir, '/');
            self::$baseUri = empty($trimmed) ? '' : '/' . $trimmed;
        }
    }

    /**
     * Dapatkan path URI saat ini tanpa base subdirectory dan query string
     */
    public static function getCurrentUri(): string
    {
        $uri = $_SERVER['REQUEST_URI'] ?? '/';

        // Buang query string (?foo=bar)
        if (($pos = strpos($uri, '?')) !== false) {
            $uri = substr($uri, 0, $pos);
        }

        $uri = rawurldecode($uri);

        // Jika ada base URI (misal /sistem keuangan/public), hilangkan dari requestUri
        if (!empty(self::$baseUri) && str_starts_with($uri, self::$baseUri)) {
            $uri = substr($uri, strlen(self::$baseUri));
        } elseif (!empty(self::$baseUri) && str_ends_with(self::$baseUri, '/public')) {
            $parentBase = substr(self::$baseUri, 0, -7);
            if (!empty($parentBase) && str_starts_with($uri, $parentBase)) {
                $uri = substr($uri, strlen($parentBase));
            }
        }

        $uri = '/' . trim($uri, '/');
        return $uri === '' ? '/' : $uri;
    }

    /**
     * Dapatkan Base URL aplikasi
     */
    public static function getBaseUrl(): string
    {
        if (isset($_SERVER['HTTP_HOST'])) {
            $scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
            $host = $_SERVER['HTTP_HOST'];
            return rtrim($scheme . '://' . $host . self::$baseUri, '/');
        }

        $envUrl = env('APP_URL', 'http://localhost/sistem%20keuangan/public');
        return rtrim($envUrl, '/');
    }

    /**
     * Eksekusi controller action atau callback
     */
    protected static function executeAction(mixed $action, array $params = []): void
    {
        if (is_callable($action)) {
            call_user_func_array($action, $params);
            return;
        }

        if (is_array($action) && count($action) === 2) {
            [$controllerClass, $method] = $action;

            if (class_exists($controllerClass)) {
                $controllerInstance = new $controllerClass();
                if (method_exists($controllerInstance, $method)) {
                    call_user_func_array([$controllerInstance, $method], $params);
                    return;
                }
                http_response_code(500);
                echo "Method [{$method}] does not exist in controller [{$controllerClass}].";
                return;
            }
            http_response_code(500);
            echo "Controller class [{$controllerClass}] not found.";
            return;
        }

        http_response_code(500);
        echo "Invalid route action defined.";
    }
}

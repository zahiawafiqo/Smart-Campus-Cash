<?php

namespace App\Core;

class View
{
    protected static array $sharedData = [];

    /**
     * Berbagi data ke semua view
     */
    public static function share(string $key, mixed $value): void
    {
        self::$sharedData[$key] = $value;
    }

    /**
     * Render view template dengan layout tertentu
     * 
     * @param string $viewPath e.g. 'home/index' or 'dashboard/index'
     * @param array $data Data yang dilewatkan ke view
     * @param string|null $layout e.g. 'layouts/app' atau 'layouts/guest' (null jika tanpa layout)
     */
    public static function render(string $viewPath, array $data = [], ?string $layout = 'layouts/app'): void
    {
        $viewFile = dirname(__DIR__) . '/Views/' . str_replace('.', '/', $viewPath) . '.php';

        if (!file_exists($viewFile)) {
            http_response_code(500);
            echo "View file not found: [{$viewFile}]";
            return;
        }

        // Gabungkan shared data dengan parameter data
        $mergedData = array_merge(self::$sharedData, $data);
        extract($mergedData, EXTR_SKIP);

        // Jika menggunakan layout
        if ($layout !== null) {
            // Tangkap konten view ke dalam buffer
            ob_start();
            require $viewFile;
            $content = ob_get_clean();

            $layoutFile = dirname(__DIR__) . '/Views/' . str_replace('.', '/', $layout) . '.php';
            if (file_exists($layoutFile)) {
                require $layoutFile;
            } else {
                echo $content;
            }
        } else {
            // Render langsung tanpa layout
            require $viewFile;
        }
    }

    /**
     * Render komponen UI reusable
     * 
     * @param string $component e.g. 'button', 'card', 'modal'
     * @param array $props
     */
    public static function component(string $component, array $props = []): void
    {
        $compFile = dirname(__DIR__) . '/Views/components/' . $component . '.php';
        if (file_exists($compFile)) {
            extract($props, EXTR_SKIP);
            require $compFile;
        } else {
            echo "<!-- Component [{$component}] not found -->";
        }
    }
}

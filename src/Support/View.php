<?php

declare(strict_types=1);

namespace App\Support;

use RuntimeException;

/**
 * Lightweight view template renderer.
 */
class View
{
    private static string $baseDir = __DIR__ . '/../views/';

    /**
     * Render a view file, optionally wrapped inside a layout.
     */
    public static function render(string $template, array $data = [], ?string $layout = 'layouts/main'): string
    {
        $templateFile = self::$baseDir . ltrim($template, '/') . '.php';

        if (!file_exists($templateFile)) {
            throw new RuntimeException("View template tidak ditemukan: {$templateFile}");
        }

        // Render template content
        $content = self::renderFile($templateFile, $data);

        // If layout specified, wrap template content inside layout
        if ($layout !== null) {
            $layoutFile = self::$baseDir . ltrim($layout, '/') . '.php';
            if (!file_exists($layoutFile)) {
                throw new RuntimeException("Layout view tidak ditemukan: {$layoutFile}");
            }
            $layoutData = array_merge($data, ['content' => $content]);
            return self::renderFile($layoutFile, $layoutData);
        }

        return $content;
    }

    /**
     * Render raw PHP view file with extracted parameters.
     */
    private static function renderFile(string $file, array $data): string
    {
        extract($data, EXTR_SKIP);
        ob_start();
        include $file;
        return (string)ob_get_clean();
    }
}

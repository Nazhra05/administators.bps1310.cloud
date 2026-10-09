<?php
declare(strict_types=1);

namespace App\Support;

/**
 * Lightweight file logger.
 */
class Logger
{
    private static ?string $logFile = null;

    public static function getLogFile(): string
    {
        if (self::$logFile === null) {
            $root = dirname(__DIR__, 2);
            $dir = $root . '/storage/logs';
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            self::$logFile = $dir . '/app.log';
        }
        return self::$logFile;
    }

    public static function setLogFile(string $path): void
    {
        self::$logFile = $path;
    }

    public static function log(string $level, string $message, array $context = []): void
    {
        $file = self::getLogFile();
        $timestamp = date('Y-m-d H:i:s');
        $contextStr = !empty($context) ? ' ' . json_encode($context, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : '';
        $line = sprintf("[%s] [%s] %s%s\n", $timestamp, strtoupper($level), $message, $contextStr);

        file_put_contents($file, $line, FILE_APPEND | LOCK_EX);
    }

    public static function error(string $message, array $context = []): void
    {
        self::log('error', $message, $context);
    }

    public static function info(string $message, array $context = []): void
    {
        self::log('info', $message, $context);
    }

    public static function warning(string $message, array $context = []): void
    {
        self::log('warning', $message, $context);
    }
}

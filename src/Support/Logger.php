<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Lightweight, security-hardened file logger with sensitive data redaction.
 */
class Logger
{
    private static ?string $logFile = null;

    private const SENSITIVE_KEYS = [
        'password',
        'current_password',
        'new_password',
        'confirm_password',
        'token',
        'verification_token',
        'csrf_token',
        'api_key',
        'key',
        'secret',
        'auth',
        'authorization',
        'cookie',
        'session',
        'x_session',
        'x-session',
        'smtp_password',
        'db_pass',
        'dev_admin_password',
    ];

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

    /**
     * Recursively redact sensitive keys from context data.
     */
    public static function sanitize(mixed $data): mixed
    {
        if (is_array($data)) {
            $cleaned = [];
            foreach ($data as $key => $val) {
                $lowerKey = strtolower((string)$key);
                $isSensitive = false;
                foreach (self::SENSITIVE_KEYS as $sensitive) {
                    if (str_contains($lowerKey, $sensitive)) {
                        $isSensitive = true;
                        break;
                    }
                }

                if ($isSensitive) {
                    $cleaned[$key] = '[REDACTED]';
                } else {
                    $cleaned[$key] = self::sanitize($val);
                }
            }
            return $cleaned;
        }

        if (is_string($data)) {
            // Mask query string password/token/key parameters if present
            $data = preg_replace('/((?:password|token|api_key|secret)=)[^&\s]+/i', '$1[REDACTED]', $data);
            // Mask bearer tokens
            $data = preg_replace('/(Bearer\s+)[a-zA-Z0-9_\-\.]+/i', '$1[REDACTED]', (string)$data);
            return $data;
        }

        return $data;
    }

    public static function log(string $level, string $message, array $context = []): void
    {
        $file = self::getLogFile();
        $timestamp = date('Y-m-d H:i:s');
        $reqId = function_exists('request_id') ? request_id() : '-';

        $sanitizedMessage = (string)self::sanitize($message);
        $sanitizedContext = (array)self::sanitize($context);

        $contextStr = !empty($sanitizedContext)
            ? ' ' . json_encode($sanitizedContext, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
            : '';

        $line = sprintf("[%s] [%s] [req:%s] %s%s\n", $timestamp, strtoupper($level), $reqId, $sanitizedMessage, $contextStr);

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

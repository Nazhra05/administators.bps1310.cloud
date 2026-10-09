<?php

declare(strict_types=1);

namespace App\Support;

class Json
{
    /**
     * Return successful v1 JSON envelope: { "data": ..., "error": null }
     */
    public static function ok(mixed $data = null, int $status = 200): string
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        return (string) json_encode([
            'data'  => $data,
            'error' => null,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Return error v1 JSON envelope: { "data": null, "error": { "code": ..., "message": ... } }
     */
    public static function error(string $code, string $message, int $status = 400, mixed $details = null): string
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        $error = [
            'code'    => $code,
            'message' => $message,
        ];
        if ($details !== null) {
            $error['details'] = $details;
        }

        return (string) json_encode([
            'data'  => null,
            'error' => $error,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Output raw JSON response (used for legacy API formats).
     */
    public static function raw(mixed $payload, int $status = 200): string
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        return (string) json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}

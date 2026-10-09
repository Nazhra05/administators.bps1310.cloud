<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Lightweight validator and sanitization helper.
 * Complies with Task 6.8 in todo.md.
 */
class Validator
{
    /**
     * Cast and validate integer ID or parameter.
     */
    public static function int(mixed $value, int $default = 0, ?int $min = null, ?int $max = null): int
    {
        if ($value === null || $value === '') {
            return $default;
        }

        if (is_numeric($value)) {
            $intVal = (int)$value;
            if ($min !== null && $intVal < $min) {
                return $default;
            }
            if ($max !== null && $intVal > $max) {
                return $default;
            }
            return $intVal;
        }

        return $default;
    }

    /**
     * Validate and trim string with max length constraint.
     */
    public static function string(mixed $value, int $maxLength = 255, string $default = ''): string
    {
        if ($value === null) {
            return $default;
        }

        $str = trim((string)$value);
        if ($maxLength > 0 && mb_strlen($str, 'UTF-8') > $maxLength) {
            return mb_substr($str, 0, $maxLength, 'UTF-8');
        }

        return $str;
    }

    /**
     * Validate URL with scheme restriction (only http and https allowed).
     * Rejects javascript:, data:, file:, and malformed URIs.
     */
    public static function url(mixed $value, array $allowedSchemes = ['http', 'https']): ?string
    {
        if ($value === null || !is_string($value)) {
            return null;
        }

        $trimmed = trim($value);
        if ($trimmed === '') {
            return null;
        }

        if (filter_var($trimmed, FILTER_VALIDATE_URL) === false) {
            return null;
        }

        $scheme = parse_url($trimmed, PHP_URL_SCHEME);
        if ($scheme === null || $scheme === false) {
            return null;
        }

        if (!in_array(strtolower($scheme), $allowedSchemes, true)) {
            return null;
        }

        return $trimmed;
    }

    /**
     * Validate value against an allowed set of enum choices.
     */
    public static function enum(mixed $value, array $allowedValues, mixed $default = null): mixed
    {
        return in_array($value, $allowedValues, true) ? $value : $default;
    }

    /**
     * Validate email format.
     */
    public static function email(mixed $value): ?string
    {
        if ($value === null || !is_string($value)) {
            return null;
        }

        $trimmed = trim($value);
        if (filter_var($trimmed, FILTER_VALIDATE_EMAIL) === false) {
            return null;
        }

        return $trimmed;
    }
}

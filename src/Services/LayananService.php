<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Service for Layanan (Services) operations, alias normalization, and filtering.
 */
class LayananService
{
    /**
     * Normalize raw search aliases input into clean, deduplicated, lowercase comma-separated string.
     */
    public static function normalizeAliases(?string $raw): string
    {
        if ($raw === null || trim($raw) === '') {
            return '';
        }

        $parts = explode(',', $raw);
        $clean = [];

        foreach ($parts as $part) {
            $item = mb_strtolower(trim($part), 'UTF-8');
            if ($item !== '' && !in_array($item, $clean, true)) {
                $clean[] = $item;
            }
        }

        return implode(', ', $clean);
    }

    /**
     * Parse comma-separated aliases into an array of normalized strings.
     *
     * @return array<int, string>
     */
    public static function parseAliasesToArray(?string $stored): array
    {
        if ($stored === null || trim($stored) === '') {
            return [];
        }

        $parts = explode(',', $stored);
        $result = [];

        foreach ($parts as $part) {
            $item = mb_strtolower(trim($part), 'UTF-8');
            if ($item !== '' && !in_array($item, $result, true)) {
                $result[] = $item;
            }
        }

        return $result;
    }
}

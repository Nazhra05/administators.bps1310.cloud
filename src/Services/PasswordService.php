<?php

declare(strict_types=1);

namespace App\Services;

use PDO;

/**
 * Service for password hashing, verification, upgrade-on-login, and policy enforcement.
 * Complies with Lampiran F in todo.md.
 */
class PasswordService
{
    /**
     * Hash a plain password using Bcrypt with cost 10.
     */
    public static function hash(string $plainPassword): string
    {
        return password_hash($plainPassword, PASSWORD_BCRYPT, ['cost' => 10]);
    }

    /**
     * Validate password according to policy:
     * - Minimum length (default 10, or PASSWORD_MIN_LENGTH env)
     * - Maximum length 255 characters
     * - Cannot be identical to username
     *
     * @return string|null Error message or null if valid.
     */
    public static function validatePolicy(string $password, ?string $username = null): ?string
    {
        $minLen = (int)env('PASSWORD_MIN_LENGTH', 10);
        if (strlen($password) < $minLen) {
            return "Password minimal harus {$minLen} karakter.";
        }

        if (strlen($password) > 255) {
            return 'Password maksimal 255 karakter.';
        }

        if (!empty($username) && strcasecmp(trim($password), trim($username)) === 0) {
            return 'Password tidak boleh sama dengan username.';
        }

        return null;
    }

    /**
     * Verify plain password against stored hash, with upgrade-on-login for legacy hashes.
     *
     * @param string $input Plain password submitted by user
     * @param string $storedHash Hash stored in database
     * @param int|null $userId User ID for auto-upgrade
     * @param PDO|null $pdo Database connection for saving updated hash
     */
    public static function verify(string $input, string $storedHash, ?int $userId = null, ?PDO $pdo = null): bool
    {
        $storedHash = trim($storedHash);
        if ($storedHash === '') {
            return false;
        }

        // 1. Standard modern hash (Bcrypt / Argon2)
        if (password_verify($input, $storedHash)) {
            if ($userId !== null && $pdo !== null && password_needs_rehash($storedHash, PASSWORD_BCRYPT, ['cost' => 10])) {
                self::rehashAndSave($userId, $input, $pdo);
            }
            return true;
        }

        // 2. Legacy SHA-256 fallback (Audit 1.3)
        // TODO: hapus setelah migrasi seluruh akun lama (dicatat di docs/CLEANUP.md)
        if (hash_equals(strtolower($storedHash), strtolower(hash('sha256', $input)))) {
            if ($userId !== null && $pdo !== null) {
                self::rehashAndSave($userId, $input, $pdo);
            }
            return true;
        }

        // 3. Legacy MD5 fallback (Audit 1.3)
        // TODO: hapus setelah migrasi seluruh akun lama (dicatat di docs/CLEANUP.md)
        if (hash_equals(strtolower($storedHash), strtolower(md5($input)))) {
            if ($userId !== null && $pdo !== null) {
                self::rehashAndSave($userId, $input, $pdo);
            }
            return true;
        }

        return false;
    }

    /**
     * Perform dummy verification to prevent timing attacks when user does not exist.
     */
    public static function dummyVerify(): void
    {
        password_verify('dummy_timing_defense', '$2y$10$e8O1fMvY57Zg2tZzU59aDe37jE54Qk10vX89m03Yh8rR3u19c4Vme');
    }

    /**
     * Silently rehash with Bcrypt and update database record.
     */
    private static function rehashAndSave(int $userId, string $plainPassword, PDO $pdo): void
    {
        try {
            $newHash = self::hash($plainPassword);
            $stmt = $pdo->prepare("UPDATE admin SET password = ? WHERE id_admin = ?");
            $stmt->execute([$newHash, $userId]);
        } catch (\Throwable $e) {
            \App\Support\Logger::error("Gagal melakukan upgrade hash password untuk user {$userId}: " . $e->getMessage());
        }
    }
}

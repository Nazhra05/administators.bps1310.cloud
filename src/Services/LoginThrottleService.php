<?php

declare(strict_types=1);

namespace App\Services;

use PDO;

/**
 * Service for anti brute-force protection, account lockout, and IP rate limiting.
 * Complies with Task 6.4 in todo.md.
 */
class LoginThrottleService
{
    public const MAX_ACCOUNT_ATTEMPTS = 5;
    public const LOCKOUT_DURATION_MINUTES = 10;
    public const MAX_IP_ATTEMPTS = 20;
    public const IP_WINDOW_MINUTES = 15;

    /**
     * Check if client IP has exceeded failed login threshold.
     */
    public static function isIpThrottled(PDO $pdo, string $ip): bool
    {
        $maxAttempts = (int)env('LOGIN_MAX_ATTEMPTS_IP', self::MAX_IP_ATTEMPTS);
        $windowMinutes = (int)env('LOGIN_IP_WINDOW_MIN', self::IP_WINDOW_MINUTES);

        try {
            $stmt = $pdo->prepare("
                SELECT COUNT(*) 
                FROM login_attempts 
                WHERE ip_address = ? 
                  AND success = 0 
                  AND attempted_at >= DATE_SUB(NOW(), INTERVAL ? MINUTE)
            ");
            $stmt->execute([$ip, $windowMinutes]);
            $count = (int)$stmt->fetchColumn();
            return $count >= $maxAttempts;
        } catch (\Throwable $e) {
            \App\Support\Logger::error("RateLimiter IP check failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if account is currently locked out.
     */
    public static function isAccountLocked(array $admin): bool
    {
        if (empty($admin['locked_until'])) {
            return false;
        }

        $lockedUntil = strtotime((string)$admin['locked_until']);
        return $lockedUntil > time();
    }

    /**
     * Record a login attempt in login_attempts table.
     */
    public static function recordAttempt(PDO $pdo, string $ip, string $identity, bool $success): void
    {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO login_attempts (ip_address, identity, success, attempted_at)
                VALUES (?, ?, ?, NOW())
            ");
            $stmt->execute([$ip, substr($identity, 0, 150), $success ? 1 : 0]);
        } catch (\Throwable $e) {
            \App\Support\Logger::error("Failed to record login attempt: " . $e->getMessage());
        }
    }

    /**
     * Handle failed login attempt for account and IP.
     * Increments failed_attempts, locks account if >= 5 failures, records IP attempt.
     */
    public static function handleFailedLogin(PDO $pdo, ?array $admin, string $ip, string $identity): void
    {
        self::recordAttempt($pdo, $ip, $identity, false);

        if ($admin !== null && isset($admin['id_admin'])) {
            $adminId = (int)$admin['id_admin'];
            $isPastLock = !empty($admin['locked_until']) && strtotime((string)$admin['locked_until']) <= time();
            $currentFailed = $isPastLock ? 0 : (int)($admin['failed_attempts'] ?? 0);
            $newFailed = $currentFailed + 1;

            if ($newFailed >= self::MAX_ACCOUNT_ATTEMPTS) {
                $stmt = $pdo->prepare("
                    UPDATE admin 
                    SET failed_attempts = ?, 
                        locked_until = DATE_ADD(NOW(), INTERVAL ? MINUTE) 
                    WHERE id_admin = ?
                ");
                $stmt->execute([$newFailed, self::LOCKOUT_DURATION_MINUTES, $adminId]);
            } else {
                $stmt = $pdo->prepare("
                    UPDATE admin 
                    SET failed_attempts = ? 
                    WHERE id_admin = ?
                ");
                $stmt->execute([$newFailed, $adminId]);
            }
        } else {
            // User does not exist - dummy verify to prevent timing enumeration
            PasswordService::dummyVerify();
        }
    }

    /**
     * Handle successful login attempt.
     * Resets failed_attempts and locked_until, updates last_login_at, records success.
     */
    public static function handleSuccessfulLogin(PDO $pdo, int $adminId, string $ip, string $identity): void
    {
        self::recordAttempt($pdo, $ip, $identity, true);

        try {
            $stmt = $pdo->prepare("
                UPDATE admin 
                SET failed_attempts = 0, 
                    locked_until = NULL, 
                    last_login_at = NOW() 
                WHERE id_admin = ?
            ");
            $stmt->execute([$adminId]);
        } catch (\Throwable $e) {
            \App\Support\Logger::error("Failed to reset failed login attempts: " . $e->getMessage());
        }
    }
}

<?php

declare(strict_types=1);

namespace App\Support;

use PDO;

/**
 * Service to record critical security and administrative actions in audit_log.
 * Complies with Task 6.6 in todo.md.
 */
class AuditLogger
{
    /**
     * Record an audit log entry.
     *
     * @param string $action Action name (e.g. 'create', 'update', 'delete', 'approve', 'reject')
     * @param string $entity Target entity (e.g. 'layanan', 'kategori', 'admin', 'user')
     * @param int|null $entityId Target entity ID
     * @param array<string, mixed>|null $meta Additional context or changes
     * @param int|null $userId User ID performing the action (defaults to authenticated session user)
     * @param PDO|null $pdo Database connection
     */
    public static function log(
        string $action,
        string $entity,
        ?int $entityId = null,
        ?array $meta = null,
        ?int $userId = null,
        ?PDO $pdo = null
    ): void {
        try {
            if ($userId === null) {
                Session::start();
                $sessionUser = Session::get('admin_id');
                if ($sessionUser !== null) {
                    $userId = (int)$sessionUser;
                }
            }

            if ($pdo === null) {
                $pdo = Database::pdo();
            }

            $stmt = $pdo->prepare("
                INSERT INTO audit_log (user_id, action, entity, entity_id, meta, created_at)
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            $metaJson = $meta !== null ? json_encode($meta, JSON_UNESCAPED_UNICODE) : null;
            $stmt->execute([$userId, $action, $entity, $entityId, $metaJson]);
        } catch (\Throwable $e) {
            Logger::error("Audit log recording failed: " . $e->getMessage());
        }
    }
}

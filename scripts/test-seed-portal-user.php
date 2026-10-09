<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';

/** @var \PDO $conn */
$stmt = $conn->prepare("
    UPDATE admin
    SET
        status = 'pending',
        email_verified = 0,
        verification_token = 'token_test_portal_aktif',
        verification_expires = DATE_ADD(NOW(), INTERVAL 1 DAY),
        username = NULL,
        password = NULL
    WHERE id_admin = 4
");

$stmt->execute();
echo "PORTAL_USER_RESET_OK\n";

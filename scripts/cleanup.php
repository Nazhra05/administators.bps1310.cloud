<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/src/bootstrap.php';

use App\Support\Database;

$db = Database::pdo();

echo "[" . date('Y-m-d H:i:s') . "] Memulai proses pembersihan data berkala (cleanup)...\n";

$deleted = [
    'sessions'            => 0,
    'login_attempts'      => 0,
    'verification_tokens' => 0,
    'link_clicks'         => 0,
];

try {
    // 1. Hapus sesi kedaluwarsa
    $stmtSess = $db->prepare("DELETE FROM sessions WHERE expires_at < ?");
    $stmtSess->execute([date('Y-m-d H:i:s')]);
    $deleted['sessions'] = $stmtSess->rowCount();

    // 2. Hapus percobaan login gagal lebih dari 1 hari
    $oneDayAgo = date('Y-m-d H:i:s', time() - 86400);
    $stmtAttempts = $db->prepare("DELETE FROM login_attempts WHERE attempted_at < ?");
    $stmtAttempts->execute([$oneDayAgo]);
    $deleted['login_attempts'] = $stmtAttempts->rowCount();

    // 3. Reset token verifikasi email yang telah kedaluwarsa pada akun belum terverifikasi
    $now = date('Y-m-d H:i:s');
    $stmtVerif = $db->prepare("
        UPDATE admin
        SET verification_token = NULL, verification_expires = NULL
        WHERE verification_expires < ? AND email_verified = 0
    ");
    $stmtVerif->execute([$now]);
    $deleted['verification_tokens'] = $stmtVerif->rowCount();

    // 4. Retensi klik layanan: hapus data link_clicks lebih dari 12 bulan (365 hari per Task 7.10)
    $oneYearAgo = date('Y-m-d H:i:s', time() - (365 * 86400));
    $stmtClicks = $db->prepare("DELETE FROM link_clicks WHERE clicked_at < ?");
    $stmtClicks->execute([$oneYearAgo]);
    $deleted['link_clicks'] = $stmtClicks->rowCount();

    echo "Pembersihan selesai dengan sukses:\n";
    foreach ($deleted as $table => $count) {
        echo "- {$table}: {$count} baris dibersihkan / direset\n";
    }
} catch (\Throwable $e) {
    echo "ERROR saat pembersihan: " . $e->getMessage() . "\n";
    exit(1);
}

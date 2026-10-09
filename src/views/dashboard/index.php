<?php
/** @var array<string, int> $metrics */
/** @var array<int, array<string, mixed>> $categories */
/** @var array<int, array<string, mixed>> $services */
/** @var array<int, array<string, mixed>> $recentActivities */
/** @var string $filterKategori */
/** @var array<string, mixed>|null $user */

$metrics = $metrics ?? [
    'total_layanan'  => (int)($totalLayanan ?? 0),
    'total_publik'   => 0,
    'total_vpn'      => 0,
    'total_pending'  => 0,
    'total_kategori' => (int)($totalKategori ?? 0),
];
$recentActivities = $recentActivities ?? [];
$userRole = (string)($user['role'] ?? '');

$formatActivity = function(array $act): string {
    $meta = [];
    if (!empty($act['meta'])) {
        $decoded = json_decode((string)$act['meta'], true);
        if (is_array($decoded)) {
            $meta = $decoded;
        }
    }
    $action = (string)($act['action'] ?? '');
    $entity = (string)($act['entity'] ?? '');

    return match ($action) {
        'create' => match ($entity) {
            'layanan' => 'Menambahkan layanan: ' . ($meta['nama_layanan'] ?? ('#' . ($act['entity_id'] ?? ''))),
            'kategori' => 'Menambahkan kategori: ' . ($meta['nama_kategori'] ?? ('#' . ($act['entity_id'] ?? ''))),
            'admin' => 'Mendaftarkan pengguna: ' . ($meta['username'] ?? ('#' . ($act['entity_id'] ?? ''))),
            default => "Membuat {$entity} #" . ($act['entity_id'] ?? ''),
        },
        'update' => match ($entity) {
            'layanan' => 'Memperbarui layanan: ' . ($meta['nama_layanan'] ?? ('#' . ($act['entity_id'] ?? ''))),
            'kategori' => 'Memperbarui kategori: ' . ($meta['nama_kategori'] ?? ('#' . ($act['entity_id'] ?? ''))),
            'settings' => 'Memperbarui pengaturan sistem',
            default => "Memperbarui {$entity} #" . ($act['entity_id'] ?? ''),
        },
        'delete' => match ($entity) {
            'layanan' => 'Menghapus/mengarsip layanan #' . ($act['entity_id'] ?? ''),
            'kategori' => 'Menghapus kategori #' . ($act['entity_id'] ?? ''),
            'admin' => 'Menghapus akun #' . ($act['entity_id'] ?? ''),
            default => "Menghapus {$entity} #" . ($act['entity_id'] ?? ''),
        },
        'approve' => 'Menyetujui layanan: ' . ($meta['nama_layanan'] ?? ('#' . ($act['entity_id'] ?? ''))),
        'reject' => 'Menolak layanan: ' . ($meta['nama_layanan'] ?? ('#' . ($act['entity_id'] ?? ''))) . (!empty($meta['reason']) ? " (Alasan: {$meta['reason']})" : ''),
        'toggle_active' => 'Mengubah status aktif akun #' . ($act['entity_id'] ?? ''),
        'reset_password' => 'Mereset password akun #' . ($act['entity_id'] ?? ''),
        default => ucfirst($action) . " {$entity} #" . ($act['entity_id'] ?? ''),
    };
};

$timeAgo = function(string $datetime): string {
    $ts = strtotime($datetime);
    if (!$ts) return $datetime;
    $diff = time() - $ts;
    if ($diff < 60) return 'Baru saja';
    if ($diff < 3600) return (int)($diff / 60) . ' mnt lalu';
    if ($diff < 86400) return (int)($diff / 3600) . ' jam lalu';
    if ($diff < 604800) return (int)($diff / 86400) . ' hari lalu';
    return date('d M Y, H:i', $ts);
};
?>

<!-- 1. Ringkasan Metrik Operasional (Task 7.14) -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(190px, 1fr)); gap: 16px; margin-bottom: 24px;">
    <!-- Total Layanan -->
    <div class="card" style="margin-bottom: 0; padding: 20px; display: flex; align-items: center; gap: 14px; border-left: 4px solid #2563eb;">
        <div style="width: 46px; height: 46px; border-radius: 10px; background: #eff6ff; color: #2563eb; display: flex; align-items: center; justify-content: center; font-size: 22px;">
            🛠️
        </div>
        <div>
            <span style="font-size: 0.8rem; color: #64748b; font-weight: 600; text-transform: uppercase;">Total Layanan</span>
            <h2 style="font-size: 1.6rem; color: #0f172a; margin-top: 2px; font-weight: 700; line-height: 1.2;"><?= (int)$metrics['total_layanan'] ?></h2>
        </div>
    </div>

    <!-- Layanan Publik -->
    <div class="card" style="margin-bottom: 0; padding: 20px; display: flex; align-items: center; gap: 14px; border-left: 4px solid #059669;">
        <div style="width: 46px; height: 46px; border-radius: 10px; background: #ecfdf5; color: #059669; display: flex; align-items: center; justify-content: center; font-size: 22px;">
            🌐
        </div>
        <div>
            <span style="font-size: 0.8rem; color: #64748b; font-weight: 600; text-transform: uppercase;">Layanan Publik</span>
            <h2 style="font-size: 1.6rem; color: #0f172a; margin-top: 2px; font-weight: 700; line-height: 1.2;"><?= (int)$metrics['total_publik'] ?></h2>
        </div>
    </div>

    <!-- Perlu VPN -->
    <div class="card" style="margin-bottom: 0; padding: 20px; display: flex; align-items: center; gap: 14px; border-left: 4px solid #ea580c;">
        <div style="width: 46px; height: 46px; border-radius: 10px; background: #fff7ed; color: #ea580c; display: flex; align-items: center; justify-content: center; font-size: 22px;">
            🔒
        </div>
        <div>
            <span style="font-size: 0.8rem; color: #64748b; font-weight: 600; text-transform: uppercase;">Memerlukan VPN</span>
            <h2 style="font-size: 1.6rem; color: #0f172a; margin-top: 2px; font-weight: 700; line-height: 1.2;"><?= (int)$metrics['total_vpn'] ?></h2>
        </div>
    </div>

    <!-- Total Kategori -->
    <div class="card" style="margin-bottom: 0; padding: 20px; display: flex; align-items: center; gap: 14px; border-left: 4px solid #7c3aed;">
        <div style="width: 46px; height: 46px; border-radius: 10px; background: #f5f3ff; color: #7c3aed; display: flex; align-items: center; justify-content: center; font-size: 22px;">
            📁
        </div>
        <div>
            <span style="font-size: 0.8rem; color: #64748b; font-weight: 600; text-transform: uppercase;">Total Kategori</span>
            <h2 style="font-size: 1.6rem; color: #0f172a; margin-top: 2px; font-weight: 700; line-height: 1.2;"><?= (int)$metrics['total_kategori'] ?></h2>
        </div>
    </div>

    <!-- Menunggu Persetujuan (Pending) -->
    <div class="card" style="margin-bottom: 0; padding: 20px; display: flex; align-items: center; gap: 14px; border-left: 4px solid <?= $metrics['total_pending'] > 0 ? '#dc2626' : '#94a3b8' ?>;">
        <div style="width: 46px; height: 46px; border-radius: 10px; background: <?= $metrics['total_pending'] > 0 ? '#fef2f2' : '#f8fafc' ?>; color: <?= $metrics['total_pending'] > 0 ? '#dc2626' : '#64748b' ?>; display: flex; align-items: center; justify-content: center; font-size: 22px;">
            ⏳
        </div>
        <div>
            <span style="font-size: 0.8rem; color: #64748b; font-weight: 600; text-transform: uppercase;">Pending Approval</span>
            <div style="display: flex; align-items: baseline; gap: 8px;">
                <h2 style="font-size: 1.6rem; color: <?= $metrics['total_pending'] > 0 ? '#dc2626' : '#0f172a' ?>; margin-top: 2px; font-weight: 700; line-height: 1.2;">
                    <?= (int)$metrics['total_pending'] ?>
                </h2>
                <?php if ($metrics['total_pending'] > 0): ?>
                    <a href="<?= e(url('/layanan.php?status=pending')) ?>" style="font-size: 0.75rem; color: #dc2626; text-decoration: underline; font-weight: 600;">
                        Tinjau →
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- 2. Pintasan Aksi Cepat (Quick Actions) -->
<div class="card" style="margin-bottom: 24px; padding: 16px 20px;">
    <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px;">
        <div style="font-size: 0.9rem; font-weight: 700; color: #1e293b; display: flex; align-items: center; gap: 6px;">
            <span>⚡</span> Pintasan Aksi:
        </div>
        <div style="display: flex; flex-wrap: wrap; gap: 10px; align-items: center;">
            <a href="<?= e(url('/tambah_layanan.php')) ?>" class="btn btn-primary" style="padding: 7px 14px; font-size: 0.85rem;">
                <span>➕</span> Tambah Layanan
            </a>
            <a href="<?= e(url('/tambah_kategori.php')) ?>" class="btn btn-secondary" style="padding: 7px 14px; font-size: 0.85rem;">
                <span>📁</span> Tambah Kategori
            </a>
            <?php if ($metrics['total_pending'] > 0): ?>
                <a href="<?= e(url('/layanan.php?status=pending')) ?>"
                   style="background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; padding: 7px 14px; border-radius: 6px; font-size: 0.85rem; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 6px;">
                    <span>⏳</span> Tinjau Pengajuan (<?= (int)$metrics['total_pending'] ?>)
                </a>
            <?php endif; ?>
            <?php if ($userRole === 'ADMIN IT'): ?>
                <a href="<?= e(url('/kelola_admin.php')) ?>" class="btn btn-secondary" style="padding: 7px 14px; font-size: 0.85rem;">
                    <span>👥</span> Kelola Pengguna
                </a>
                <a href="<?= e(url('/pengaturan.php')) ?>" class="btn btn-secondary" style="padding: 7px 14px; font-size: 0.85rem;">
                    <span>⚙️</span> Pengaturan Sistem
                </a>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- 3. Grid Dua Kolom: Tabel Layanan & Aktivitas Terbaru -->
<div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px; align-items: start;">
    <!-- Kolom Kiri: Tabel Layanan -->
    <div class="card" style="margin-bottom: 0;">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; margin-bottom: 20px;">
            <div>
                <h2 style="font-size: 1.15rem; color: #0f172a; font-weight: 700; margin: 0;">Katalog Layanan Aktif</h2>
                <p style="color: #64748b; font-size: 0.825rem; margin: 2px 0 0 0;">Daftar tautan layanan yang terdaftar dalam sistem</p>
            </div>

            <form method="GET" action="<?= e(url('/')) ?>" style="display: flex; align-items: center; gap: 8px;">
                <select name="kategori" id="kategori" onchange="this.form.submit()"
                        style="padding: 6px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.85rem; background: white; cursor: pointer;">
                    <option value="">Semua Kategori</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= e($cat['id_kategori']) ?>" <?= ((string)$filterKategori === (string)$cat['id_kategori']) ? 'selected' : '' ?>>
                            <?= e($cat['nama_kategori']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if ($filterKategori !== ''): ?>
                    <a href="<?= e(url('/')) ?>" style="color: #64748b; font-size: 0.8rem; text-decoration: underline;">Reset</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="table-container" style="overflow-x: auto;">
            <table class="layanan-table" style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.9rem;">
                <thead>
                    <tr style="border-bottom: 2px solid #e2e8f0; color: #475569; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.03em;">
                        <th style="padding: 10px 12px; width: 40px;">No</th>
                        <th style="padding: 10px 12px; width: 45px;">Logo</th>
                        <th style="padding: 10px 12px;">Nama Layanan</th>
                        <th style="padding: 10px 12px;">Kategori</th>
                        <th style="padding: 10px 12px; width: 80px; text-align: center;">Link</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($services)): ?>
                        <tr>
                            <td colspan="5" style="text-align: center; padding: 36px 16px; color: #64748b;">
                                <div style="font-size: 1.75rem; margin-bottom: 6px;">📭</div>
                                <div style="font-weight: 600; color: #1e293b; font-size: 0.95rem;">Tidak Ada Layanan Ditemukan</div>
                                <div style="font-size: 0.825rem; margin-top: 4px;">Belum ada data layanan untuk kriteria ini.</div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php $no = 1; foreach (array_slice($services, 0, 15) as $srv): ?>
                            <tr style="border-bottom: 1px solid #f1f5f9;">
                                <td style="padding: 10px 12px; color: #64748b; font-weight: 500;">
                                    <?= $no++ ?>
                                </td>
                                <td style="padding: 10px 12px;">
                                    <?php if (!empty($srv['logo'])): ?>
                                        <img src="<?= e(asset('uploads/' . $srv['logo'])) ?>" alt="<?= e($srv['nama_layanan']) ?>"
                                             onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                                             style="width: 32px; height: 32px; object-fit: contain; border-radius: 6px; background: #f8fafc; padding: 2px; border: 1px solid #e2e8f0;">
                                        <div style="display: none; width: 32px; height: 32px; border-radius: 6px; background: <?= service_color($srv['nama_layanan']) ?>; color: white; align-items: center; justify-content: center; font-size: 11px; font-weight: 700;">
                                            <?= e(service_initials($srv['nama_layanan'])) ?>
                                        </div>
                                    <?php else: ?>
                                        <div style="width: 32px; height: 32px; border-radius: 6px; background: <?= service_color($srv['nama_layanan']) ?>; color: white; display: flex; align-items: center; justify-content: center; font-size: 11px; font-weight: 700;">
                                            <?= e(service_initials($srv['nama_layanan'])) ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 10px 12px; font-weight: 600; color: #1e293b;">
                                    <div><?= e($srv['nama_layanan']) ?></div>
                                    <div style="display: flex; gap: 6px; margin-top: 3px;">
                                        <span style="font-size: 0.7rem; font-weight: 600; padding: 1px 6px; border-radius: 4px; <?= ($srv['akses'] ?? '') === 'Public' ? 'background: #dcfce7; color: #166534;' : 'background: #f1f5f9; color: #475569;' ?>">
                                            <?= e($srv['akses'] ?? 'Public') ?>
                                        </span>
                                        <?php if (($srv['vpn'] ?? '') === 'Yes'): ?>
                                            <span style="font-size: 0.7rem; font-weight: 600; padding: 1px 6px; border-radius: 4px; background: #fff7ed; color: #c2410c;">
                                                VPN
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td style="padding: 10px 12px;">
                                    <span style="display: inline-block; padding: 3px 8px; border-radius: 9999px; background: #e0f2fe; color: #0369a1; font-size: 0.75rem; font-weight: 600;">
                                        <?= e($srv['nama_kategori'] ?? 'Tanpa Kategori') ?>
                                    </span>
                                </td>
                                <td style="padding: 10px 12px; text-align: center;">
                                    <a href="<?= e($srv['url']) ?>" target="_blank" rel="noopener noreferrer"
                                       style="color: #2563eb; text-decoration: none; display: inline-flex; align-items: center; gap: 3px; font-size: 0.8rem; font-weight: 600;">
                                        <span>Buka</span>
                                        <span style="font-size: 10px;">↗</span>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if (count($services) > 15): ?>
            <div style="padding: 12px; text-align: center; border-top: 1px solid #f1f5f9;">
                <a href="<?= e(url('/layanan.php')) ?>" style="color: #2563eb; font-size: 0.85rem; font-weight: 600; text-decoration: none;">
                    Lihat Semua Data Layanan (<?= count($services) ?> Layanan) →
                </a>
            </div>
        <?php endif; ?>
    </div>

    <!-- Kolom Kanan: Aktivitas Terbaru (Audit Log Feed) -->
    <div class="card" style="margin-bottom: 0;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
            <h2 style="font-size: 1.15rem; color: #0f172a; font-weight: 700; margin: 0; display: flex; align-items: center; gap: 6px;">
                <span>📋</span> Aktivitas Terbaru
            </h2>
            <span style="font-size: 0.75rem; color: #64748b; font-weight: 500;">Audit Log</span>
        </div>

        <?php if (empty($recentActivities)): ?>
            <div style="padding: 32px 16px; text-align: center; color: #64748b; font-size: 0.85rem;">
                <div style="font-size: 1.5rem; margin-bottom: 6px;">📝</div>
                <div>Belum ada catatan aktivitas tercatat.</div>
            </div>
        <?php else: ?>
            <div style="display: flex; flex-direction: column; gap: 14px;">
                <?php foreach ($recentActivities as $act): ?>
                    <?php
                        $actorName = !empty($act['username']) ? (string)$act['username'] : 'Sistem';
                        $actorRole = (string)($act['role'] ?? '');
                        $actionText = $formatActivity($act);
                        $timeString = $timeAgo((string)$act['created_at']);
                    ?>
                    <div style="display: flex; gap: 12px; align-items: flex-start; padding-bottom: 12px; border-bottom: 1px solid #f8fafc;">
                        <div style="width: 32px; height: 32px; border-radius: 50%; background: #f1f5f9; display: flex; align-items: center; justify-content: center; font-size: 14px; flex-shrink: 0; color: #475569;">
                            👤
                        </div>
                        <div style="flex: 1; min-width: 0;">
                            <div style="display: flex; justify-content: space-between; align-items: baseline; gap: 6px;">
                                <div style="font-size: 0.825rem; font-weight: 700; color: #0f172a; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                    <?= e($actorName) ?>
                                    <?php if (!empty($actorRole)): ?>
                                        <span style="font-size: 0.65rem; color: #64748b; font-weight: 500; background: #f1f5f9; padding: 1px 5px; border-radius: 4px; margin-left: 2px;">
                                            <?= e($actorRole === 'ADMIN IT' ? 'IT' : 'Leader') ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <span style="font-size: 0.7rem; color: #94a3b8; white-space: nowrap;">
                                    <?= e($timeString) ?>
                                </span>
                            </div>
                            <div style="font-size: 0.8rem; color: #334155; margin-top: 3px; line-height: 1.4; word-break: break-word;">
                                <?= e($actionText) ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>


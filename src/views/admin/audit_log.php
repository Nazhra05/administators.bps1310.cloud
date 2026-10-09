<?php
/** @var array<int, array<string, mixed>> $logs */
?>

<div class="header" style="margin-bottom: 24px;">
    <h1 style="font-size: 1.5rem; color: #0f172a; font-weight: 700; margin: 0;">Riwayat Audit Sistem</h1>
    <p style="color: #64748b; font-size: 0.9rem; margin: 4px 0 0 0;">Catatan jejak aktivitas administrator, persetujuan layanan, dan perubahan data penting</p>
</div>

<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
        <h2 style="font-size: 1.1rem; color: #1e293b; font-weight: 700; margin: 0;">Log Aktivitas Terbaru</h2>
        <span style="font-size: 0.85rem; color: #64748b;">Menampilkan hingga 150 aktivitas terakhir</span>
    </div>

    <div class="table-container" style="overflow-x: auto;">
        <table class="audit-table" style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.9rem;">
            <thead>
                <tr style="border-bottom: 2px solid #e2e8f0; color: #475569; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.03em;">
                    <th style="padding: 12px 14px; width: 40px;">No</th>
                    <th style="padding: 12px 14px; width: 150px;">Waktu</th>
                    <th style="padding: 12px 14px;">Pengguna</th>
                    <th style="padding: 12px 14px; text-align: center;">Aksi</th>
                    <th style="padding: 12px 14px;">Entitas</th>
                    <th style="padding: 12px 14px; width: 60px; text-align: center;">ID</th>
                    <th style="padding: 12px 14px;">Detail Metadata</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($logs)): ?>
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 32px; color: #64748b;">
                            Belum ada catatan aktivitas dalam log audit.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php $no = 1; foreach ($logs as $log): ?>
                        <?php
                            $action = (string)($log['action'] ?? '');
                            $actionBadge = match ($action) {
                                'approve'        => ['bg' => '#dcfce7', 'color' => '#15803d', 'label' => 'Setujui'],
                                'reject'         => ['bg' => '#fee2e2', 'color' => '#b91c1c', 'label' => 'Tolak'],
                                'create'         => ['bg' => '#e0f2fe', 'color' => '#0369a1', 'label' => 'Tambah'],
                                'update'         => ['bg' => '#fef9c3', 'color' => '#854d0e', 'label' => 'Ubah'],
                                'delete'         => ['bg' => '#f1f5f9', 'color' => '#64748b', 'label' => 'Hapus'],
                                'reset_password' => ['bg' => '#ede9fe', 'color' => '#6d28d9', 'label' => 'Reset Pwd'],
                                'login'          => ['bg' => '#ccfbf1', 'color' => '#0f766e', 'label' => 'Login'],
                                default          => ['bg' => '#f1f5f9', 'color' => '#475569', 'label' => ucfirst($action)],
                            };

                            $metaRaw = $log['meta'] ?? null;
                            $metaStr = '-';
                            if (!empty($metaRaw)) {
                                if (is_string($metaRaw)) {
                                    $decoded = json_decode($metaRaw, true);
                                    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                                        $metaStr = json_encode($decoded, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                                    } else {
                                        $metaStr = $metaRaw;
                                    }
                                } elseif (is_array($metaRaw)) {
                                    $metaStr = json_encode($metaRaw, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                                }
                            }
                        ?>
                        <tr style="border-bottom: 1px solid #f1f5f9;">
                            <td style="padding: 10px 14px; color: #64748b; font-size: 0.85rem;">
                                <?= $no++ ?>
                            </td>
                            <td style="padding: 10px 14px; color: #475569; font-size: 0.85rem; white-space: nowrap;">
                                <?= e($log['created_at'] ?? '-') ?>
                            </td>
                            <td style="padding: 10px 14px;">
                                <div style="font-weight: 600; color: #1e293b;">
                                    <?= e($log['username'] ?? 'Sistem') ?>
                                </div>
                                <?php if (!empty($log['role'])): ?>
                                    <span style="font-size: 0.75rem; color: #64748b;"><?= e($log['role']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 10px 14px; text-align: center;">
                                <span style="display: inline-block; padding: 2px 8px; border-radius: 6px; font-size: 0.75rem; font-weight: 600; background: <?= $actionBadge['bg'] ?>; color: <?= $actionBadge['color'] ?>;">
                                    <?= e($actionBadge['label']) ?>
                                </span>
                            </td>
                            <td style="padding: 10px 14px; font-weight: 500; color: #334155;">
                                <?= e(ucfirst((string)($log['entity'] ?? '-'))) ?>
                            </td>
                            <td style="padding: 10px 14px; text-align: center; color: #64748b; font-size: 0.85rem;">
                                <?= e((string)($log['entity_id'] ?? '-')) ?>
                            </td>
                            <td style="padding: 10px 14px; font-family: monospace; font-size: 0.8rem; color: #334155; max-width: 320px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="<?= e($metaStr) ?>">
                                <?= e($metaStr) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

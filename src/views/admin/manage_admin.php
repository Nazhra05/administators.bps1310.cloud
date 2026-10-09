<?php
/** @var array<int, array<string, mixed>> $admins */
/** @var array<string, mixed> $user */
?>

<div class="header" style="margin-bottom: 24px;">
    <h1 style="font-size: 1.5rem; color: #0f172a; font-weight: 700; margin: 0;">Kelola Admin</h1>
    <p style="color: #64748b; font-size: 0.9rem; margin: 4px 0 0 0;">Otorisasi dan penugasan peran administrator sistem BPS Solok Selatan</p>
</div>

<div class="card">
    <div class="table-container" style="overflow-x: auto;">
        <table class="admin-table" style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.925rem;">
            <thead>
                <tr style="border-bottom: 2px solid #e2e8f0; color: #475569; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.03em;">
                    <th style="padding: 12px 14px; width: 50px;">No</th>
                    <th style="padding: 12px 14px;">Username</th>
                    <th style="padding: 12px 14px;">Nama Lengkap</th>
                    <th style="padding: 12px 14px;">Email</th>
                    <th style="padding: 12px 14px; text-align: center;">Role</th>
                    <th style="padding: 12px 14px; text-align: center;">Status</th>
                    <th style="padding: 12px 14px; text-align: center;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($admins)): ?>
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 32px; color: #64748b;">
                            Tidak ada data admin.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php $no = 1; foreach ($admins as $adm): ?>
                        <?php
                            $idAdmin = (int)$adm['id_admin'];
                            $isSelf = ((int)($user['id'] ?? 0) === $idAdmin);
                            $role = (string)($adm['role'] ?? '');
                            $status = (string)($adm['status'] ?? '');
                        ?>
                        <tr style="border-bottom: 1px solid #f1f5f9;">
                            <td style="padding: 12px 14px; color: #64748b; font-weight: 500;">
                                <?= $no++ ?>
                            </td>
                            <td style="padding: 12px 14px; font-weight: 600; color: #1e293b;">
                                <?= e($adm['username'] ?? '-') ?>
                                <?php if ($isSelf): ?>
                                    <span style="font-size: 0.75rem; background: #e0e7ff; color: #4338ca; padding: 2px 6px; border-radius: 4px; margin-left: 4px;">Anda</span>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 12px 14px; color: #334155;">
                                <?= e(trim(($adm['first_name'] ?? '') . ' ' . ($adm['last_name'] ?? '')) ?: '-') ?>
                            </td>
                            <td style="padding: 12px 14px; color: #64748b;">
                                <?= e($adm['email'] ?? '-') ?>
                            </td>
                            <td style="padding: 12px 14px; text-align: center;">
                                <span style="display: inline-block; padding: 3px 8px; border-radius: 6px; font-size: 0.75rem; font-weight: 600; background: <?= ($role === 'ADMIN IT') ? '#fef3c7; color: #b45309;' : '#e0f2fe; color: #0369a1;' ?>">
                                    <?= e($role ?: 'Belum Ada') ?>
                                </span>
                            </td>
                            <td style="padding: 12px 14px; text-align: center;">
                                <?php
                                    $statusBg = match ($status) {
                                        'approved' => '#dcfce7; color: #15803d;',
                                        'pending'  => '#fef9c3; color: #854d0e;',
                                        'rejected' => '#fee2e2; color: #b91c1c;',
                                        default    => '#f1f5f9; color: #64748b;',
                                    };
                                ?>
                                <span style="display: inline-block; padding: 3px 8px; border-radius: 6px; font-size: 0.75rem; font-weight: 600; background: <?= $statusBg ?>">
                                    <?= e(ucfirst($status)) ?>
                                </span>
                            </td>
                            <td style="padding: 12px 14px; text-align: center;">
                                <?php if ($isSelf): ?>
                                    <span style="color: #94a3b8; font-size: 0.8rem; font-style: italic;">Akun Aktif</span>
                                <?php else: ?>
                                    <div style="display: inline-flex; align-items: center; gap: 6px; flex-wrap: wrap; justify-content: center;">
                                        <?php if ($status === 'pending'): ?>
                                            <form method="POST" action="<?= e(url('/kelola_admin.php')) ?>" style="display: inline; margin: 0;">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="id_admin" value="<?= $idAdmin ?>">
                                                <input type="hidden" name="action" value="approve">
                                                <button type="submit" style="background: #16a34a; color: white; border: none; padding: 4px 8px; border-radius: 4px; font-size: 0.75rem; cursor: pointer;">
                                                    Setujui
                                                </button>
                                            </form>
                                            <form method="POST" action="<?= e(url('/kelola_admin.php')) ?>" style="display: inline; margin: 0;">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="id_admin" value="<?= $idAdmin ?>">
                                                <input type="hidden" name="action" value="reject">
                                                <button type="submit" style="background: #dc2626; color: white; border: none; padding: 4px 8px; border-radius: 4px; font-size: 0.75rem; cursor: pointer;">
                                                    Tolak
                                                </button>
                                            </form>
                                        <?php elseif ($status === 'approved'): ?>
                                            <?php if ($role === 'ADMIN DASHBOARD'): ?>
                                                <form method="POST" action="<?= e(url('/kelola_admin.php')) ?>" style="display: inline; margin: 0;">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="id_admin" value="<?= $idAdmin ?>">
                                                    <input type="hidden" name="action" value="make_main">
                                                    <button type="submit" style="background: #0284c7; color: white; border: none; padding: 4px 8px; border-radius: 4px; font-size: 0.75rem; cursor: pointer;">
                                                        Jadikan IT
                                                    </button>
                                                </form>
                                            <?php elseif ($role === 'ADMIN IT'): ?>
                                                <form method="POST" action="<?= e(url('/kelola_admin.php')) ?>" style="display: inline; margin: 0;">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="id_admin" value="<?= $idAdmin ?>">
                                                    <input type="hidden" name="action" value="make_regular">
                                                    <button type="submit" style="background: #64748b; color: white; border: none; padding: 4px 8px; border-radius: 4px; font-size: 0.75rem; cursor: pointer;">
                                                        Jadikan Dashboard
                                                    </button>
                                                </form>
                                            <?php endif; ?>

                                            <form method="POST" action="<?= e(url('/kelola_admin.php')) ?>"
                                                  onsubmit="return confirm('Hapus admin ini?');" style="display: inline; margin: 0;">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="id_admin" value="<?= $idAdmin ?>">
                                                <input type="hidden" name="action" value="delete">
                                                <button type="submit" style="background: #fee2e2; color: #dc2626; border: none; padding: 4px 8px; border-radius: 4px; font-size: 0.75rem; cursor: pointer;">
                                                    Hapus
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
/** @var array<int, array<string, mixed>> $users */
/** @var string $error */
/** @var string $email_value */
?>

<div class="header" style="margin-bottom: 24px;">
    <h1 style="font-size: 1.5rem; color: #0f172a; font-weight: 700; margin: 0;">Kelola Pengguna Portal</h1>
    <p style="color: #64748b; font-size: 0.9rem; margin: 4px 0 0 0;">Undang dan kelola akun pengguna aplikasi portal internal BPS Solok Selatan</p>
</div>

<div class="card" style="margin-bottom: 24px; max-width: 600px;">
    <h2 style="font-size: 1.1rem; color: #1e293b; font-weight: 700; margin-bottom: 14px;">Undang Pengguna Baru</h2>

    <?php if (!empty($error)): ?>
        <div style="background: #fef2f2; border-left: 4px solid #ef4444; color: #991b1b; padding: 12px 16px; border-radius: 6px; margin-bottom: 16px; font-size: 0.9rem;">
            <?= e($error) ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="<?= e(url('/kelola_user.php')) ?>">
        <?= csrf_field() ?>

        <div style="margin-bottom: 16px;">
            <label for="email" style="display: block; font-weight: 600; color: #334155; margin-bottom: 6px; font-size: 0.9rem;">
                Alamat Email Pengguna <span style="color: #ef4444;">*</span>
            </label>
            <input type="email" id="email" name="email" value="<?= e($email_value) ?>" required
                   placeholder="nama@bps.go.id atau email instansi"
                   style="width: 100%; box-sizing: border-box; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 0.95rem;">
        </div>

        <button type="submit"
                style="background: #2563eb; color: white; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 600; font-size: 0.95rem; cursor: pointer;">
            Kirim Undangan Pendaftaran
        </button>
    </form>
</div>

<div class="card">
    <h2 style="font-size: 1.1rem; color: #1e293b; font-weight: 700; margin-bottom: 16px;">Daftar Pengguna Portal</h2>

    <div class="table-container" style="overflow-x: auto;">
        <table class="user-table" style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.925rem;">
            <thead>
                <tr style="border-bottom: 2px solid #e2e8f0; color: #475569; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.03em;">
                    <th style="padding: 12px 14px; width: 50px;">No</th>
                    <th style="padding: 12px 14px;">Username</th>
                    <th style="padding: 12px 14px;">Email</th>
                    <th style="padding: 12px 14px; text-align: center;">Status Verifikasi</th>
                    <th style="padding: 12px 14px; text-align: center;">Status Akun</th>
                    <th style="padding: 12px 14px;">Tanggal Dibuat</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($users)): ?>
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 32px; color: #64748b;">
                            Belum ada akun pengguna portal yang diundang.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php $no = 1; foreach ($users as $u): ?>
                        <tr style="border-bottom: 1px solid #f1f5f9;">
                            <td style="padding: 12px 14px; color: #64748b; font-weight: 500;">
                                <?= $no++ ?>
                            </td>
                            <td style="padding: 12px 14px; font-weight: 600; color: #1e293b;">
                                <?= e($u['username'] ?? 'Belum Aktivasi') ?>
                            </td>
                            <td style="padding: 12px 14px; color: #64748b;">
                                <?= e($u['email']) ?>
                            </td>
                            <td style="padding: 12px 14px; text-align: center;">
                                <?php if (!empty($u['email_verified'])): ?>
                                    <span style="display: inline-block; padding: 3px 8px; border-radius: 6px; font-size: 0.75rem; font-weight: 600; background: #dcfce7; color: #15803d;">
                                        Terverifikasi
                                    </span>
                                <?php else: ?>
                                    <span style="display: inline-block; padding: 3px 8px; border-radius: 6px; font-size: 0.75rem; font-weight: 600; background: #fef9c3; color: #854d0e;">
                                        Menunggu
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 12px 14px; text-align: center;">
                                <span style="display: inline-block; padding: 3px 8px; border-radius: 6px; font-size: 0.75rem; font-weight: 600; background: <?= ($u['status'] === 'approved') ? '#dcfce7; color: #15803d;' : '#fef9c3; color: #854d0e;' ?>">
                                    <?= e(ucfirst($u['status'])) ?>
                                </span>
                            </td>
                            <td style="padding: 12px 14px; color: #64748b; font-size: 0.85rem;">
                                <?= e($u['created_at'] ?? '-') ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
/** @var array<string, mixed> $admin */
/** @var string $error_msg */
/** @var string $success_msg */
?>

<div class="header" style="margin-bottom: 24px;">
    <h1 style="font-size: 1.5rem; color: #0f172a; font-weight: 700; margin: 0;">Pengaturan</h1>
    <p style="color: #64748b; font-size: 0.9rem; margin: 4px 0 0 0;">Informasi sistem dan konfigurasi keamanan akun administrator</p>
</div>

<?php if (!empty($error_msg)): ?>
    <div style="background: #fef2f2; border-left: 4px solid #ef4444; color: #991b1b; padding: 12px 16px; border-radius: 6px; margin-bottom: 20px; font-size: 0.9rem;">
        <?= e($error_msg) ?>
    </div>
<?php endif; ?>

<?php if (!empty($success_msg)): ?>
    <div style="background: #f0fdf4; border-left: 4px solid #22c55e; color: #166534; padding: 12px 16px; border-radius: 6px; margin-bottom: 20px; font-size: 0.9rem;">
        <?= e($success_msg) ?>
    </div>
<?php endif; ?>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 24px;">
    <!-- System Information Card (Must be first card for tests) -->
    <div class="card">
        <h2 style="font-size: 1.15rem; color: #1e293b; font-weight: 700; margin-bottom: 16px;">Informasi Sistem</h2>

        <div style="display: flex; flex-direction: column; gap: 14px; font-size: 0.925rem;">
            <div>
                <span style="color: #64748b; display: block; font-size: 0.8rem; text-transform: uppercase;">Nama Sistem</span>
                <span style="font-weight: 600; color: #1e293b;">Dashboard Admin BPS Kabupaten Solok Selatan</span>
            </div>
            <div>
                <span style="color: #64748b; display: block; font-size: 0.8rem; text-transform: uppercase;">Database</span>
                <span style="font-weight: 600; color: #1e293b;"><?= e(env('DB_NAME', 'bps_solsel_dev')) ?> (MySQL / MariaDB)</span>
            </div>
            <div>
                <span style="color: #64748b; display: block; font-size: 0.8rem; text-transform: uppercase;">Versi PHP</span>
                <span style="font-weight: 600; color: #1e293b;"><?= PHP_VERSION ?></span>
            </div>
            <div>
                <span style="color: #64748b; display: block; font-size: 0.8rem; text-transform: uppercase;">Instansi</span>
                <span style="font-weight: 600; color: #1e293b;">Badan Pusat Statistik Kabupaten Solok Selatan</span>
            </div>
            <div>
                <span style="color: #64748b; display: block; font-size: 0.8rem; text-transform: uppercase;">Pengguna Login</span>
                <span style="font-weight: 600; color: #1e293b;"><?= e($admin['username'] ?? '-') ?> (<?= e($admin['role'] ?? '-') ?>)</span>
            </div>
        </div>
    </div>

    <!-- Password Change Card -->
    <div class="card">
        <h2 style="font-size: 1.15rem; color: #1e293b; font-weight: 700; margin-bottom: 16px;">Ubah Kata Sandi</h2>

        <form method="POST" action="<?= e(url('/pengaturan.php')) ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="change_password">

            <div style="margin-bottom: 16px;">
                <label for="current_password" style="display: block; font-weight: 600; color: #334155; margin-bottom: 6px; font-size: 0.9rem;">
                    Kata Sandi Saat Ini <span style="color: #ef4444;">*</span>
                </label>
                <input type="password" id="current_password" name="current_password" required
                       style="width: 100%; box-sizing: border-box; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 0.95rem;">
            </div>

            <div style="margin-bottom: 16px;">
                <label for="new_password" style="display: block; font-weight: 600; color: #334155; margin-bottom: 6px; font-size: 0.9rem;">
                    Kata Sandi Baru <span style="color: #ef4444;">*</span>
                </label>
                <input type="password" id="new_password" name="new_password" minlength="8" required
                       placeholder="Minimal 8 karakter"
                       style="width: 100%; box-sizing: border-box; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 0.95rem;">
            </div>

            <div style="margin-bottom: 20px;">
                <label for="confirm_password" style="display: block; font-weight: 600; color: #334155; margin-bottom: 6px; font-size: 0.9rem;">
                    Konfirmasi Kata Sandi Baru <span style="color: #ef4444;">*</span>
                </label>
                <input type="password" id="confirm_password" name="confirm_password" minlength="8" required
                       placeholder="Ulangi kata sandi baru"
                       style="width: 100%; box-sizing: border-box; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 0.95rem;">
            </div>

            <button type="submit"
                    style="background: #2563eb; color: white; border: none; padding: 10px 22px; border-radius: 8px; font-weight: 600; font-size: 0.95rem; cursor: pointer;">
                Perbarui Kata Sandi
            </button>
        </form>
    </div>

    <?php if (($user['role'] ?? ($admin['role'] ?? '')) === 'ADMIN IT'): ?>
        <!-- Public Portal & Helpdesk Settings Card (Task 7.6) -->
        <div class="card" style="grid-column: 1 / -1; border-top: 4px solid #2563eb;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; flex-wrap: wrap; gap: 8px;">
                <div>
                    <h2 style="font-size: 1.15rem; color: #1e293b; font-weight: 700; margin: 0;">Pengaturan Kontak Bantuan & Portal Publik</h2>
                    <p style="color: #64748b; font-size: 0.85rem; margin: 4px 0 0 0;">Konfigurasi identitas portal, email/WhatsApp bantuan, dan jam layanan untuk pengguna portal App B</p>
                </div>
                <span style="background: #e0f2fe; color: #0369a1; padding: 3px 10px; border-radius: 9999px; font-size: 0.75rem; font-weight: 600;">
                    Khusus ADMIN IT
                </span>
            </div>

            <form method="POST" action="<?= e(url('/pengaturan.php')) ?>">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="update_settings">

                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 18px; margin-bottom: 20px;">
                    <div>
                        <label for="app_name" style="display: block; font-weight: 600; color: #334155; margin-bottom: 6px; font-size: 0.875rem;">
                            Nama Portal / Sistem
                        </label>
                        <input type="text" id="app_name" name="app_name"
                               value="<?= e($settings['app_name'] ?? 'Portal Internal BPS Kabupaten Solok Selatan') ?>"
                               style="width: 100%; box-sizing: border-box; padding: 9px 13px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 0.9rem;">
                    </div>

                    <div>
                        <label for="contact_email" style="display: block; font-weight: 600; color: #334155; margin-bottom: 6px; font-size: 0.875rem;">
                            Email Kontak Helpdesk
                        </label>
                        <input type="email" id="contact_email" name="contact_email"
                               value="<?= e($settings['contact_email'] ?? 'bps1310@bps.go.id') ?>"
                               style="width: 100%; box-sizing: border-box; padding: 9px 13px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 0.9rem;">
                    </div>

                    <div>
                        <label for="contact_whatsapp" style="display: block; font-weight: 600; color: #334155; margin-bottom: 6px; font-size: 0.875rem;">
                            WhatsApp Bantuan
                        </label>
                        <input type="text" id="contact_whatsapp" name="contact_whatsapp"
                               value="<?= e($settings['contact_whatsapp'] ?? '08116661310') ?>"
                               placeholder="Contoh: 08116661310"
                               style="width: 100%; box-sizing: border-box; padding: 9px 13px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 0.9rem;">
                    </div>

                    <div>
                        <label for="helpdesk_unit" style="display: block; font-weight: 600; color: #334155; margin-bottom: 6px; font-size: 0.875rem;">
                            Unit Kerja Penanggung Jawab
                        </label>
                        <input type="text" id="helpdesk_unit" name="helpdesk_unit"
                               value="<?= e($settings['helpdesk_unit'] ?? 'Tim IPDS BPS Kabupaten Solok Selatan') ?>"
                               style="width: 100%; box-sizing: border-box; padding: 9px 13px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 0.9rem;">
                    </div>

                    <div style="grid-column: 1 / -1;">
                        <label for="service_hours" style="display: block; font-weight: 600; color: #334155; margin-bottom: 6px; font-size: 0.875rem;">
                            Jam Operasional / Pelayanan
                        </label>
                        <input type="text" id="service_hours" name="service_hours"
                               value="<?= e($settings['service_hours'] ?? 'Senin - Kamis: 08.00 - 15.30 WIB, Jumat: 08.00 - 16.00 WIB') ?>"
                               style="width: 100%; box-sizing: border-box; padding: 9px 13px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 0.9rem;">
                    </div>
                </div>

                <button type="submit"
                        style="background: #16a34a; color: white; border: none; padding: 10px 22px; border-radius: 8px; font-weight: 600; font-size: 0.95rem; cursor: pointer;">
                    Simpan Pengaturan
                </button>
            </form>
        </div>
    <?php endif; ?>
</div>

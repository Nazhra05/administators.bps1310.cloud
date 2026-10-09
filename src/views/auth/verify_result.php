<div style="text-align: center; padding: 12px 0;">
    <div style="font-size: 3rem; margin-bottom: 12px;">
        <?= !empty($success) ? '✓' : '⚠️' ?>
    </div>

    <h1 style="font-size: 1.35rem; color: #0f172a; margin-bottom: 8px;">
        <?= e($heading ?? $title ?? 'Status Verifikasi') ?>
    </h1>

    <div class="message" style="color: #64748b; font-size: 0.95rem; line-height: 1.5; margin-bottom: 24px;">
        <?= e($message ?? '') ?>
    </div>

    <?php if (!empty($showSetup) && !empty($setupUrl)): ?>
        <div class="setup-text" style="color: #555; font-size: 0.95rem; line-height: 1.6; margin-bottom: 20px;">
            Silakan buat username dan password untuk akun Anda.
        </div>
        <a href="<?= e($setupUrl) ?>" class="btn btn-primary" style="text-decoration: none; display: inline-block;">
            Buat Username & Password
        </a>
    <?php else: ?>
        <a href="<?= e(url('/login')) ?>" class="btn btn-primary" style="text-decoration: none; display: inline-block;">
            Ke Halaman Login
        </a>
    <?php endif; ?>
</div>

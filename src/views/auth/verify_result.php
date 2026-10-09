<div style="text-align: center; padding: 12px 0;">
    <div style="font-size: 3rem; margin-bottom: 12px;">
        <?= !empty($success) ? '🎉' : '⚠️' ?>
    </div>

    <h2 style="font-size: 1.35rem; color: #0f172a; margin-bottom: 8px;">
        <?= e($title ?? 'Status Verifikasi') ?>
    </h2>

    <p style="color: #64748b; font-size: 0.95rem; line-height: 1.5; margin-bottom: 24px;">
        <?= e($message ?? '') ?>
    </p>

    <a href="<?= e(url('/login')) ?>" class="btn btn-primary" style="text-decoration: none;">
        Ke Halaman Login
    </a>
</div>

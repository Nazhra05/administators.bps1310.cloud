<div style="text-align: center; margin-bottom: 24px;">
    <h2 style="font-size: 1.35rem; color: #005a9c; margin-bottom: 4px;">Aktivasi Akun Portal</h2>
    <p style="color: #64748b; font-size: 0.9rem;">Email: <strong><?= e($email ?? '') ?></strong></p>
</div>

<?php if (!empty($error)): ?>
    <div class="alert alert-error" style="margin-bottom: 20px;">
        <?= e($error) ?>
    </div>
<?php endif; ?>

<form method="POST" style="display: flex; flex-direction: column; gap: 16px;">
    <?= csrf_field() ?>
    <input type="hidden" name="token" value="<?= e($token ?? '') ?>">

    <div class="form-group" style="display: flex; flex-direction: column; gap: 6px;">
        <label for="username" style="font-weight: 600; font-size: 0.9rem; color: #334155;">Tentukan Username</label>
        <input type="text" id="username" name="username" placeholder="Masukkan username pilihan Anda" required
               style="padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 0.95rem;">
    </div>

    <div class="form-group" style="display: flex; flex-direction: column; gap: 6px;">
        <label for="password" style="font-weight: 600; font-size: 0.9rem; color: #334155;">Tentukan Password</label>
        <input type="password" id="password" name="password" placeholder="Minimal 6 karakter" required
               style="padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 0.95rem;">
    </div>

    <div class="form-group" style="display: flex; flex-direction: column; gap: 6px;">
        <label for="password_confirmation" style="font-weight: 600; font-size: 0.9rem; color: #334155;">Konfirmasi Password</label>
        <input type="password" id="password_confirmation" name="password_confirmation" placeholder="Ulangi password" required
               style="padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 0.95rem;">
    </div>

    <button type="submit" class="btn btn-primary" style="padding: 12px; margin-top: 8px; justify-content: center; font-size: 1rem;">
        Simpan & Aktifkan Akun
    </button>
</form>

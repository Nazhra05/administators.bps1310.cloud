<div class="logo" style="text-align: center; margin-bottom: 24px;">
    <img src="<?= e(asset('assets/logo_bps.jpg')) ?>" alt="Logo BPS" style="width: 56px; height: 56px; object-fit: contain; margin-bottom: 8px;">
    <h1 style="font-size: 1.5rem; color: #005a9c; margin-bottom: 4px;">Dashboard Admin</h1>
    <p style="color: #64748b; font-size: 0.9rem;">BPS Kabupaten Solok Selatan</p>
</div>

<?php if (!empty($error)): ?>
    <div class="error alert alert-error" style="margin-bottom: 20px;">
        <?= e($error) ?>
    </div>
<?php endif; ?>

<form method="POST" style="display: flex; flex-direction: column; gap: 16px;">
    <?= csrf_field() ?>

    <div class="form-group" style="display: flex; flex-direction: column; gap: 6px;">
        <label for="username" style="font-weight: 600; font-size: 0.9rem; color: #334155;">Username atau Email</label>
        <input type="text" id="username" name="username" placeholder="Masukkan username atau email" required
               style="padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 0.95rem;">
    </div>

    <div class="form-group" style="display: flex; flex-direction: column; gap: 6px;">
        <label for="password" style="font-weight: 600; font-size: 0.9rem; color: #334155;">Password</label>
        <input type="password" id="password" name="password" placeholder="Masukkan password" required
               style="padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 0.95rem;">
    </div>

    <button type="submit" class="btn-login" style="margin-top: 8px; padding: 12px; background: #0066b3; color: white; border: none; border-radius: 8px; font-weight: 600; font-size: 1rem; cursor: pointer; transition: background 0.15s ease;">
        Login
    </button>
</form>

<div class="register-link" style="text-align: center; margin-top: 24px; font-size: 0.9rem; color: #64748b;">
    Belum memiliki akun? <a href="register.php" style="color: #0066b3; font-weight: 600; text-decoration: none;">Daftar sekarang</a>
</div>

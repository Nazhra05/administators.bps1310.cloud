<div class="heading" style="text-align: center; margin-bottom: 24px;">
    <h1 style="font-size: 1.5rem; color: #005a9c; margin-bottom: 4px;">Create an Account</h1>
    <p style="color: #64748b; font-size: 0.9rem;">Daftar akun baru portal BPS Kabupaten Solok Selatan</p>
</div>

<?php if (!empty($error)): ?>
    <div class="error alert alert-error" style="margin-bottom: 20px;">
        <?= e($error) ?>
    </div>
<?php endif; ?>

<form action="<?= e(url('/register')) ?>" method="POST" style="display: flex; flex-direction: column; gap: 14px;">
    <?= csrf_field() ?>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
        <div class="form-group" style="display: flex; flex-direction: column; gap: 4px;">
            <label for="first_name" style="font-weight: 600; font-size: 0.875rem; color: #334155;">Nama Depan</label>
            <input type="text" id="first_name" name="first_name" required
                   style="padding: 9px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 0.9rem;">
        </div>
        <div class="form-group" style="display: flex; flex-direction: column; gap: 4px;">
            <label for="last_name" style="font-weight: 600; font-size: 0.875rem; color: #334155;">Nama Belakang</label>
            <input type="text" id="last_name" name="last_name" required
                   style="padding: 9px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 0.9rem;">
        </div>
    </div>

    <div class="form-group" style="display: flex; flex-direction: column; gap: 4px;">
        <label for="username" style="font-weight: 600; font-size: 0.875rem; color: #334155;">Username</label>
        <input type="text" id="username" name="username" required
               style="padding: 9px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 0.9rem;">
    </div>

    <div class="form-group" style="display: flex; flex-direction: column; gap: 4px;">
        <label for="email" style="font-weight: 600; font-size: 0.875rem; color: #334155;">Email</label>
        <input type="email" id="email" name="email" required
               style="padding: 9px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 0.9rem;">
    </div>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
        <div class="form-group" style="display: flex; flex-direction: column; gap: 4px;">
            <label for="password" style="font-weight: 600; font-size: 0.875rem; color: #334155;">Password</label>
            <input type="password" id="password" name="password" required
                   style="padding: 9px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 0.9rem;">
        </div>
        <div class="form-group" style="display: flex; flex-direction: column; gap: 4px;">
            <label for="confirm_password" style="font-weight: 600; font-size: 0.875rem; color: #334155;">Konfirmasi Password</label>
            <input type="password" id="confirm_password" name="confirm_password" required
                   style="padding: 9px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 0.9rem;">
        </div>
    </div>

    <div style="display: flex; align-items: center; gap: 8px; margin: 4px 0;">
        <input type="checkbox" id="agree_to_terms" name="agree_to_terms" value="1" required>
        <label for="agree_to_terms" style="font-size: 0.85rem; color: #64748b;">
            Saya menyetujui Syarat & Ketentuan yang berlaku.
        </label>
    </div>

    <button type="submit" class="btn-create" style="margin-top: 6px; padding: 11px; background: #0066b3; color: white; border: none; border-radius: 8px; font-weight: 600; font-size: 0.95rem; cursor: pointer;">
        Create Account
    </button>
</form>

<div style="text-align: center; margin-top: 20px; font-size: 0.875rem; color: #64748b;">
    Sudah memiliki akun? <a href="<?= e(url('/login')) ?>" style="color: #0066b3; font-weight: 600; text-decoration: none;">Login di sini</a>
</div>

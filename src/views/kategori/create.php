<?php
/** @var string $error */
/** @var string $nama_kategori */
?>

<div class="header" style="margin-bottom: 24px;">
    <h1 style="font-size: 1.5rem; color: #0f172a; font-weight: 700; margin: 0;">Tambah Kategori</h1>
    <p style="color: #64748b; font-size: 0.9rem; margin: 4px 0 0 0;">Tambahkan kelompok kategori baru untuk mengelompokkan layanan</p>
</div>

<div class="card" style="max-width: 600px;">
    <?php if (!empty($error)): ?>
        <div style="background: #fef2f2; border-left: 4px solid #ef4444; color: #991b1b; padding: 12px 16px; border-radius: 6px; margin-bottom: 20px; font-size: 0.9rem;">
            <?= e($error) ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="<?= e(url('/tambah_kategori.php')) ?>">
        <?= csrf_field() ?>

        <div style="margin-bottom: 20px;">
            <label for="nama_kategori" style="display: block; font-weight: 600; color: #334155; margin-bottom: 8px; font-size: 0.9rem;">
                Nama Kategori <span style="color: #ef4444;">*</span>
            </label>
            <input type="text" id="nama_kategori" name="nama_kategori" value="<?= e($nama_kategori) ?>" required autofocus
                   placeholder="Contoh: Sosial, Distribusi, Neraca..."
                   style="width: 100%; box-sizing: border-box; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 0.95rem;">
        </div>

        <div style="display: flex; gap: 12px; align-items: center; margin-top: 24px;">
            <button type="submit" class="btn-simpan"
                    style="background: #2563eb; color: white; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 600; font-size: 0.95rem; cursor: pointer;">
                Simpan Kategori
            </button>
            <a href="<?= e(url('/kategori.php')) ?>"
               style="color: #64748b; text-decoration: none; font-size: 0.95rem; font-weight: 500; padding: 10px 16px;">
                Batal
            </a>
        </div>
    </form>
</div>

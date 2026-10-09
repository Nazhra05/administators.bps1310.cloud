<?php
/** @var array<string, mixed> $layanan */
/** @var array<int, array<string, mixed>> $categories */
/** @var string $error */
?>

<div class="header" style="margin-bottom: 24px;">
    <h1 style="font-size: 1.5rem; color: #0f172a; font-weight: 700; margin: 0;">Edit Layanan</h1>
    <p style="color: #64748b; font-size: 0.9rem; margin: 4px 0 0 0;">Perbarui data atau tautan layanan</p>
</div>

<div class="card" style="max-width: 700px;">
    <?php if (!empty($error)): ?>
        <div style="background: #fef2f2; border-left: 4px solid #ef4444; color: #991b1b; padding: 12px 16px; border-radius: 6px; margin-bottom: 20px; font-size: 0.9rem;">
            <?= e($error) ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="<?= e(url('/edit_layanan.php?id=' . (int)$layanan['id_layanan'])) ?>" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= (int)$layanan['id_layanan'] ?>">

        <div style="margin-bottom: 18px;">
            <label for="nama_layanan" style="display: block; font-weight: 600; color: #334155; margin-bottom: 6px; font-size: 0.9rem;">
                Nama Layanan <span style="color: #ef4444;">*</span>
            </label>
            <input type="text" id="nama_layanan" name="nama_layanan" value="<?= e($layanan['nama_layanan'] ?? '') ?>" required autofocus
                   style="width: 100%; box-sizing: border-box; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 0.95rem;">
        </div>

        <div style="margin-bottom: 18px;">
            <label for="id_kategori" style="display: block; font-weight: 600; color: #334155; margin-bottom: 6px; font-size: 0.9rem;">
                Kategori <span style="color: #ef4444;">*</span>
            </label>
            <select id="id_kategori" name="id_kategori" required
                    style="width: 100%; box-sizing: border-box; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 0.95rem; background: white;">
                <option value="">-- Pilih Kategori --</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= e($cat['id_kategori']) ?>" <?= ((string)$layanan['id_kategori'] === (string)$cat['id_kategori']) ? 'selected' : '' ?>>
                        <?= e($cat['nama_kategori']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <?php if (($user['role'] ?? '') === 'ADMIN IT'): ?>
            <div style="margin-bottom: 18px;">
                <label for="unit_id" style="display: block; font-weight: 600; color: #334155; margin-bottom: 6px; font-size: 0.9rem;">
                    Unit Kerja Penanggung Jawab (Opsional)
                </label>
                <select id="unit_id" name="unit_id"
                        style="width: 100%; box-sizing: border-box; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 0.95rem; background: white;">
                    <option value="">-- Lintas Unit / Umum --</option>
                    <?php foreach ($units ?? [] as $u): ?>
                        <option value="<?= (int)$u['id'] ?>" <?= ((string)($layanan['unit_id'] ?? '') === (string)$u['id']) ? 'selected' : '' ?>>
                            <?= e($u['nama']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        <?php endif; ?>

        <div style="margin-bottom: 18px;">
            <label for="url" style="display: block; font-weight: 600; color: #334155; margin-bottom: 6px; font-size: 0.9rem;">
                URL / Tautan Layanan <span style="color: #ef4444;">*</span>
            </label>
            <input type="url" id="url" name="url" value="<?= e($layanan['url'] ?? '') ?>" required
                   style="width: 100%; box-sizing: border-box; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 0.95rem;">
        </div>

        <div style="margin-bottom: 18px;">
            <label for="deskripsi_layanan" style="display: block; font-weight: 600; color: #334155; margin-bottom: 6px; font-size: 0.9rem;">
                Deskripsi Layanan <span style="color: #ef4444;">*</span>
            </label>
            <textarea id="deskripsi_layanan" name="deskripsi_layanan" rows="3" required
                      style="width: 100%; box-sizing: border-box; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 0.95rem; font-family: inherit;"><?= e($layanan['deskripsi_layanan'] ?? '') ?></textarea>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 18px;">
            <div>
                <label for="akses" style="display: block; font-weight: 600; color: #334155; margin-bottom: 6px; font-size: 0.9rem;">
                    Akses <span style="color: #ef4444;">*</span>
                </label>
                <select id="akses" name="akses" required
                        style="width: 100%; box-sizing: border-box; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 0.95rem; background: white;">
                    <option value="Public" <?= ($layanan['akses'] === 'Public') ? 'selected' : '' ?>>Public</option>
                    <option value="Internal" <?= ($layanan['akses'] === 'Internal') ? 'selected' : '' ?>>Internal</option>
                </select>
            </div>

            <div>
                <label for="vpn" style="display: block; font-weight: 600; color: #334155; margin-bottom: 6px; font-size: 0.9rem;">
                    Memerlukan VPN <span style="color: #ef4444;">*</span>
                </label>
                <select id="vpn" name="vpn" required
                        style="width: 100%; box-sizing: border-box; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 0.95rem; background: white;">
                    <option value="No" <?= ($layanan['vpn'] === 'No') ? 'selected' : '' ?>>Tidak (No)</option>
                    <option value="Yes" <?= ($layanan['vpn'] === 'Yes') ? 'selected' : '' ?>>Ya (Yes)</option>
                </select>
            </div>
        </div>

        <div style="margin-bottom: 18px;">
            <label for="search_aliases" style="display: block; font-weight: 600; color: #334155; margin-bottom: 6px; font-size: 0.9rem;">
                Kata Kunci Pencarian / Alias (Opsional)
            </label>
            <input type="text" id="search_aliases" name="search_aliases" value="<?= e($layanan['search_aliases'] ?? '') ?>"
                   placeholder="Contoh: portal, statistik daerah, sirusa (pisahkan dengan koma)"
                   style="width: 100%; box-sizing: border-box; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 0.95rem;">
            <small style="color: #64748b; font-size: 0.8rem; display: block; margin-top: 4px;">Kata kunci membantu pencarian instan di portal App B. Pisahkan tiap kata dengan tanda koma (,).</small>
        </div>

        <div style="margin-bottom: 24px;">
            <label for="logo" style="display: block; font-weight: 600; color: #334155; margin-bottom: 6px; font-size: 0.9rem;">
                Ganti Logo Layanan (Opsional)
            </label>
            <?php if (!empty($layanan['logo'])): ?>
                <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 10px;">
                    <img src="<?= e(asset('uploads/' . $layanan['logo'])) ?>" alt="Logo Saat Ini"
                         style="width: 48px; height: 48px; object-fit: contain; border-radius: 8px; border: 1px solid #cbd5e1; padding: 4px; background: #f8fafc;">
                    <span style="font-size: 0.85rem; color: #64748b;">Logo saat ini: <?= e($layanan['logo']) ?></span>
                </div>
            <?php endif; ?>
            <input type="file" id="logo" name="logo" accept="image/jpeg,image/png,image/webp"
                   style="width: 100%; box-sizing: border-box; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 0.9rem;">
            <small style="color: #64748b; font-size: 0.8rem; display: block; margin-top: 4px;">Biarkan kosong jika tidak ingin mengubah logo</small>
        </div>

        <div style="display: flex; gap: 12px; align-items: center;">
            <button type="submit" class="btn-save"
                    style="background: #2563eb; color: white; border: none; padding: 10px 22px; border-radius: 8px; font-weight: 600; font-size: 0.95rem; cursor: pointer;">
                Simpan Perubahan
            </button>
            <a href="<?= e(url('/layanan.php')) ?>"
               style="color: #64748b; text-decoration: none; font-size: 0.95rem; font-weight: 500; padding: 10px 16px;">
                Batal
            </a>
        </div>
    </form>
</div>

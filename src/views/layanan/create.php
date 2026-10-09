<?php
/** @var array<int, array<string, mixed>> $categories */
/** @var array<int, array<string, mixed>> $units */
/** @var array<string, mixed> $user */
/** @var string $error */
/** @var array<string, string> $errors */
/** @var array<string, mixed> $form */
$errors = $errors ?? [];
$form = $form ?? [];
?>

<div class="header" style="margin-bottom: 24px;">
    <h1 style="font-size: 1.5rem; color: #0f172a; font-weight: 700; margin: 0;">Tambah Layanan</h1>
    <p style="color: #64748b; font-size: 0.9rem; margin: 4px 0 0 0;">Tambahkan tautan layanan atau portal baru ke dalam sistem</p>
</div>

<div class="card" style="max-width: 700px;">
    <?php if (!empty($error)): ?>
        <div style="background: #fef2f2; border-left: 4px solid #ef4444; color: #991b1b; padding: 12px 16px; border-radius: 6px; margin-bottom: 20px; font-size: 0.9rem;">
            <?= e($error) ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="<?= e(url('/tambah_layanan.php')) ?>" enctype="multipart/form-data" novalidate>
        <?= csrf_field() ?>

        <div style="margin-bottom: 18px;">
            <label for="nama_layanan" style="display: block; font-weight: 600; color: #334155; margin-bottom: 6px; font-size: 0.9rem;">
                Nama Layanan <span style="color: #ef4444;">*</span>
            </label>
            <input type="text" id="nama_layanan" name="nama_layanan" value="<?= e($form['nama_layanan'] ?? '') ?>" required autofocus
                   placeholder="Contoh: Web Portal BPS, Simpeg, Sirusa..."
                   class="<?= !empty($errors['nama_layanan']) ? 'input-error' : '' ?>"
                   style="width: 100%; box-sizing: border-box; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 0.95rem;">
            <?php if (!empty($errors['nama_layanan'])): ?>
                <span class="field-error"><?= e($errors['nama_layanan']) ?></span>
            <?php endif; ?>
        </div>

        <div style="margin-bottom: 18px;">
            <label for="id_kategori" style="display: block; font-weight: 600; color: #334155; margin-bottom: 6px; font-size: 0.9rem;">
                Kategori <span style="color: #ef4444;">*</span>
            </label>
            <select id="id_kategori" name="id_kategori" required
                    class="<?= !empty($errors['id_kategori']) ? 'input-error' : '' ?>"
                    style="width: 100%; box-sizing: border-box; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 0.95rem; background: white;">
                <option value="">-- Pilih Kategori --</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= e($cat['id_kategori']) ?>" <?= ((string)($form['id_kategori'] ?? '') === (string)$cat['id_kategori']) ? 'selected' : '' ?>>
                        <?= e($cat['nama_kategori']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if (!empty($errors['id_kategori'])): ?>
                <span class="field-error"><?= e($errors['id_kategori']) ?></span>
            <?php endif; ?>
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
                        <option value="<?= (int)$u['id'] ?>" <?= ((string)($form['unit_id'] ?? '') === (string)$u['id']) ? 'selected' : '' ?>>
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
            <input type="url" id="url" name="url" value="<?= e($form['url'] ?? '') ?>" required
                   placeholder="https://..."
                   class="<?= !empty($errors['url']) ? 'input-error' : '' ?>"
                   style="width: 100%; box-sizing: border-box; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 0.95rem;">
            <?php if (!empty($errors['url'])): ?>
                <span class="field-error"><?= e($errors['url']) ?></span>
            <?php endif; ?>
        </div>

        <div style="margin-bottom: 18px;">
            <label for="deskripsi_layanan" style="display: block; font-weight: 600; color: #334155; margin-bottom: 6px; font-size: 0.9rem;">
                Deskripsi Layanan <span style="color: #ef4444;">*</span>
            </label>
            <textarea id="deskripsi_layanan" name="deskripsi_layanan" rows="3" required
                      placeholder="Jelaskan secara singkat kegunaan layanan ini..."
                      class="<?= !empty($errors['deskripsi_layanan']) ? 'input-error' : '' ?>"
                      style="width: 100%; box-sizing: border-box; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 0.95rem; font-family: inherit;"><?= e($form['deskripsi_layanan'] ?? '') ?></textarea>
            <?php if (!empty($errors['deskripsi_layanan'])): ?>
                <span class="field-error"><?= e($errors['deskripsi_layanan']) ?></span>
            <?php endif; ?>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 18px;">
            <div>
                <label for="akses" style="display: block; font-weight: 600; color: #334155; margin-bottom: 6px; font-size: 0.9rem;">
                    Akses <span style="color: #ef4444;">*</span>
                </label>
                <select id="akses" name="akses" required
                        class="<?= !empty($errors['akses']) ? 'input-error' : '' ?>"
                        style="width: 100%; box-sizing: border-box; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 0.95rem; background: white;">
                    <option value="Public" <?= (($form['akses'] ?? 'Public') === 'Public') ? 'selected' : '' ?>>Public</option>
                    <option value="Internal" <?= (($form['akses'] ?? '') === 'Internal') ? 'selected' : '' ?>>Internal</option>
                </select>
                <?php if (!empty($errors['akses'])): ?>
                    <span class="field-error"><?= e($errors['akses']) ?></span>
                <?php endif; ?>
            </div>

            <div>
                <label for="vpn" style="display: block; font-weight: 600; color: #334155; margin-bottom: 6px; font-size: 0.9rem;">
                    Memerlukan VPN <span style="color: #ef4444;">*</span>
                </label>
                <select id="vpn" name="vpn" required
                        class="<?= !empty($errors['vpn']) ? 'input-error' : '' ?>"
                        style="width: 100%; box-sizing: border-box; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 0.95rem; background: white;">
                    <option value="No" <?= (($form['vpn'] ?? 'No') === 'No') ? 'selected' : '' ?>>Tidak (No)</option>
                    <option value="Yes" <?= (($form['vpn'] ?? '') === 'Yes') ? 'selected' : '' ?>>Ya (Yes)</option>
                </select>
                <?php if (!empty($errors['vpn'])): ?>
                    <span class="field-error"><?= e($errors['vpn']) ?></span>
                <?php endif; ?>
            </div>
        </div>

        <div style="margin-bottom: 18px;">
            <label for="search_aliases" style="display: block; font-weight: 600; color: #334155; margin-bottom: 6px; font-size: 0.9rem;">
                Kata Kunci Pencarian / Alias (Opsional)
            </label>
            <input type="text" id="search_aliases" name="search_aliases" value="<?= e($form['search_aliases'] ?? '') ?>"
                   placeholder="Contoh: portal, statistik daerah, sirusa (pisahkan dengan koma)"
                   style="width: 100%; box-sizing: border-box; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 0.95rem;">
            <small style="color: #64748b; font-size: 0.8rem; display: block; margin-top: 4px;">Kata kunci membantu pencarian instan di portal App B. Pisahkan tiap kata dengan tanda koma (,).</small>
        </div>

        <div style="margin-bottom: 24px;">
            <label for="logo" style="display: block; font-weight: 600; color: #334155; margin-bottom: 6px; font-size: 0.9rem;">
                Logo Layanan (Opsional)
            </label>
            <input type="file" id="logo" name="logo" accept="image/jpeg,image/png,image/webp" data-preview="logoPreview"
                   class="<?= !empty($errors['logo']) ? 'input-error' : '' ?>"
                   style="width: 100%; box-sizing: border-box; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 0.9rem;">
            <small style="color: #64748b; font-size: 0.8rem; display: block; margin-top: 4px;">Format didukung: JPG, PNG, WebP (maks. 2MB)</small>
            
            <div id="logoPreview_error" class="field-error" style="display: none;"></div>
            <?php if (!empty($errors['logo'])): ?>
                <span class="field-error"><?= e($errors['logo']) ?></span>
            <?php endif; ?>

            <!-- Live Preview Box -->
            <div id="logoPreview" class="logo-preview-box" style="display: none;">
                <img src="" alt="Pratinjau Logo" style="width: 44px; height: 44px; object-fit: contain; border-radius: 6px; border: 1px solid #e2e8f0; background: white; padding: 2px;">
                <div>
                    <span style="font-size: 0.85rem; font-weight: 600; color: #334155; display: block;">Pratinjau Logo Baru:</span>
                    <span class="preview-filename" style="font-size: 0.8rem; color: #64748b;"></span>
                </div>
            </div>
        </div>

        <div style="display: flex; gap: 12px; align-items: center;">
            <button type="submit" class="btn-simpan" data-loading-text="Menyimpan Layanan..."
                    style="background: #2563eb; color: white; border: none; padding: 10px 22px; border-radius: 8px; font-weight: 600; font-size: 0.95rem; cursor: pointer;">
                Simpan Layanan
            </button>
            <a href="<?= e(url('/layanan.php')) ?>"
               style="color: #64748b; text-decoration: none; font-size: 0.95rem; font-weight: 500; padding: 10px 16px;">
                Batal
            </a>
        </div>
    </form>
</div>


<?php
/** @var array<int, array<string, mixed>> $services */
/** @var array<int, array<string, mixed>> $categories */
/** @var int|null $selectedKategori */
?>

<div class="header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 16px;">
    <div>
        <h1 style="font-size: 1.5rem; color: #0f172a; font-weight: 700; margin: 0;">Manage Link - Kelola Layanan</h1>
        <p style="color: #64748b; font-size: 0.9rem; margin: 4px 0 0 0;">Daftar seluruh tautan layanan dan portal internal BPS Kabupaten Solok Selatan</p>
    </div>
    <div>
        <a href="<?= e(url('/tambah_layanan.php')) ?>" class="btn-add"
           style="background: #2563eb; color: white; padding: 10px 18px; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 0.9rem; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 1px 3px rgba(37,99,235,0.2);">
            <span>+</span>
            <span>Tambah Layanan</span>
        </a>
    </div>
</div>

<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; margin-bottom: 20px;">
        <h2 style="font-size: 1.15rem; color: #1e293b; font-weight: 700; margin: 0;">Semua Layanan</h2>

        <form method="GET" action="<?= e(url('/layanan.php')) ?>" style="display: flex; align-items: center; gap: 10px;">
            <label for="id_kategori" style="font-size: 0.9rem; font-weight: 600; color: #475569;">Filter:</label>
            <select name="id_kategori" id="id_kategori" onchange="this.form.submit()"
                    style="padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 0.9rem; background: white; cursor: pointer;">
                <option value="">Semua Kategori</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= e($cat['id_kategori']) ?>" <?= ($selectedKategori === (int)$cat['id_kategori']) ? 'selected' : '' ?>>
                        <?= e($cat['nama_kategori']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>

    <div class="table-container" style="overflow-x: auto;">
        <table class="layanan-table" style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.925rem;">
            <thead>
                <tr style="border-bottom: 2px solid #e2e8f0; color: #475569; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.03em;">
                    <th style="padding: 12px 14px; width: 50px;">No</th>
                    <th style="padding: 12px 14px; width: 60px;">Logo</th>
                    <th style="padding: 12px 14px;">Nama Layanan</th>
                    <th style="padding: 12px 14px;">Kategori</th>
                    <th style="padding: 12px 14px; width: 90px; text-align: center;">Akses</th>
                    <th style="padding: 12px 14px; width: 80px; text-align: center;">VPN</th>
                    <th style="padding: 12px 14px;">Link</th>
                    <th style="padding: 12px 14px; width: 140px; text-align: center;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($services)): ?>
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 32px; color: #64748b;">
                            Tidak ada layanan yang ditemukan.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php $no = 1; foreach ($services as $srv): ?>
                        <?php $srvId = (int)$srv['id_layanan']; ?>
                        <tr style="border-bottom: 1px solid #f1f5f9;">
                            <td style="padding: 12px 14px; color: #64748b; font-weight: 500;">
                                <?= $no++ ?>
                            </td>
                            <td style="padding: 12px 14px;">
                                <?php if (!empty($srv['logo'])): ?>
                                    <img src="<?= e(asset('uploads/' . $srv['logo'])) ?>" alt="Logo"
                                         style="width: 32px; height: 32px; object-fit: contain; border-radius: 6px; background: #f8fafc; padding: 2px; border: 1px solid #e2e8f0;">
                                <?php else: ?>
                                    <div style="width: 32px; height: 32px; border-radius: 6px; background: #e2e8f0; display: flex; align-items: center; justify-content: center; font-size: 14px;">
                                        🔗
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 12px 14px; font-weight: 600; color: #1e293b;">
                                <div><?= e($srv['nama_layanan']) ?></div>
                                <?php if (!empty($srv['deskripsi_layanan'])): ?>
                                    <div style="font-size: 0.8rem; color: #64748b; font-weight: 400; margin-top: 2px;">
                                        <?= e($srv['deskripsi_layanan']) ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 12px 14px;">
                                <span style="display: inline-block; padding: 3px 10px; border-radius: 9999px; background: #e0f2fe; color: #0369a1; font-size: 0.8rem; font-weight: 600;">
                                    <?= e($srv['nama_kategori'] ?? 'Tanpa Kategori') ?>
                                </span>
                            </td>
                            <td style="padding: 12px 14px; text-align: center;">
                                <span style="display: inline-block; padding: 3px 8px; border-radius: 6px; font-size: 0.75rem; font-weight: 600; background: <?= ($srv['akses'] === 'Public') ? '#dcfce7; color: #15803d;' : '#fef3c7; color: #b45309;' ?>">
                                    <?= e($srv['akses']) ?>
                                </span>
                            </td>
                            <td style="padding: 12px 14px; text-align: center;">
                                <span style="display: inline-block; padding: 3px 8px; border-radius: 6px; font-size: 0.75rem; font-weight: 600; background: <?= ($srv['vpn'] === 'Yes') ? '#fee2e2; color: #b91c1c;' : '#f1f5f9; color: #64748b;' ?>">
                                    <?= e($srv['vpn']) ?>
                                </span>
                            </td>
                            <td style="padding: 12px 14px;">
                                <a href="<?= e($srv['url']) ?>" target="_blank" rel="noopener noreferrer"
                                   style="color: #0284c7; text-decoration: none; display: inline-flex; align-items: center; gap: 4px; font-size: 0.85rem;">
                                    <span>Buka Tautan</span>
                                    <span style="font-size: 11px;">↗</span>
                                </a>
                            </td>
                            <td style="padding: 12px 14px; text-align: center;">
                                <div style="display: inline-flex; align-items: center; gap: 8px;">
                                    <a href="<?= e(url('/edit_layanan.php?id=' . $srvId)) ?>"
                                       class="action-btn edit-btn"
                                       style="background: #f1f5f9; color: #2563eb; padding: 6px 12px; border-radius: 6px; text-decoration: none; font-size: 0.85rem; font-weight: 600;">
                                        Edit
                                    </a>

                                    <form method="POST" action="<?= e(url('/hapus_layanan.php')) ?>"
                                          onsubmit="return confirm('Apakah Anda yakin ingin menghapus layanan ini?');"
                                          style="display: inline; margin: 0;">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="id" value="<?= $srvId ?>">
                                        <button type="submit" class="action-btn delete-btn"
                                                style="background: #fef2f2; color: #dc2626; border: none; padding: 6px 12px; border-radius: 6px; cursor: pointer; font-size: 0.85rem; font-weight: 600;">
                                            Hapus
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

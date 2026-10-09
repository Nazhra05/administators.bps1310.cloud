<?php
/** @var array<int, array<string, mixed>> $categories */
/** @var array<int, array<int, array<string, mixed>>> $servicesByCategory */
?>

<div class="header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; flex-wrap: wrap; gap: 16px;">
    <div>
        <h1 style="font-size: 1.5rem; color: #0f172a; font-weight: 700; margin: 0;">Kelola Kategori</h1>
        <p style="color: #64748b; font-size: 0.9rem; margin: 4px 0 0 0;">Daftar klasifikasi bidang layanan BPS Kabupaten Solok Selatan</p>
    </div>
    <div>
        <a href="<?= e(url('/tambah_kategori.php')) ?>" class="btn-tambah"
           style="background: #2563eb; color: white; padding: 10px 18px; border-radius: 8px; text-decoration: none; font-weight: 600; font-size: 0.9rem; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 1px 3px rgba(37,99,235,0.2);">
            <span>+</span>
            <span>Tambah Kategori</span>
        </a>
    </div>
</div>

<div class="card">
    <div class="table-container" style="overflow-x: auto;">
        <table class="kategori-table" style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.925rem;">
            <thead>
                <tr style="border-bottom: 2px solid #e2e8f0; color: #475569; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.03em;">
                    <th style="padding: 12px 14px; width: 60px;">No</th>
                    <th style="padding: 12px 14px;">Nama Kategori</th>
                    <th style="padding: 12px 14px; width: 140px; text-align: center;">Jumlah Layanan</th>
                    <th style="padding: 12px 14px;">Preview Layanan</th>
                    <th style="padding: 12px 14px; width: 140px; text-align: center;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($categories)): ?>
                    <tr>
                        <td colspan="5" style="text-align: center; padding: 32px; color: #64748b;">
                            Belum ada kategori layanan.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php $no = 1; foreach ($categories as $cat): ?>
                        <?php $catId = (int)$cat['id_kategori']; ?>
                        <tr style="border-bottom: 1px solid #f1f5f9;">
                            <td style="padding: 12px 14px; color: #64748b; font-weight: 500;">
                                <?= $no++ ?>
                            </td>
                            <td style="padding: 12px 14px; font-weight: 600; color: #1e293b;">
                                <?= e($cat['nama_kategori']) ?>
                            </td>
                            <td style="padding: 12px 14px; text-align: center;">
                                <span style="display: inline-block; padding: 4px 12px; border-radius: 9999px; background: #e2e8f0; color: #334155; font-size: 0.85rem; font-weight: 600;">
                                    <?= (int)$cat['jumlah_layanan'] ?> Layanan
                                </span>
                            </td>
                            <td style="padding: 12px 14px; color: #64748b; font-size: 0.85rem;">
                                <?php if (!empty($servicesByCategory[$catId])): ?>
                                    <ul style="margin: 0; padding-left: 18px; line-height: 1.5;">
                                        <?php foreach ($servicesByCategory[$catId] as $item): ?>
                                            <li><?= e($item['nama_layanan']) ?></li>
                                        <?php endforeach; ?>
                                        <?php if ((int)$cat['jumlah_layanan'] > 3): ?>
                                            <li style="color: #94a3b8; font-style: italic;">...dan <?= (int)$cat['jumlah_layanan'] - 3 ?> lainnya</li>
                                        <?php endif; ?>
                                    </ul>
                                <?php else: ?>
                                    <span style="color: #94a3b8; font-style: italic;">Belum ada layanan</span>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 12px 14px; text-align: center;">
                                <div style="display: inline-flex; align-items: center; gap: 8px;">
                                    <a href="<?= e(url('/edit_kategori.php?id=' . $catId)) ?>"
                                       class="action-btn edit-btn"
                                       style="background: #f1f5f9; color: #2563eb; padding: 6px 12px; border-radius: 6px; text-decoration: none; font-size: 0.85rem; font-weight: 600;">
                                        Edit
                                    </a>

                                    <form method="POST" action="<?= e(url('/hapus_kategori.php')) ?>"
                                          onsubmit="return confirm('Apakah Anda yakin ingin menghapus kategori ini?');"
                                          style="display: inline; margin: 0;">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="id" value="<?= $catId ?>">
                                        <button type="submit" class="action-btn delete-btn"
                                                style="background: #fef2f2; color: #dc2626; border: none; padding: 6px 12px; border-radius: 6px; cursor: pointer; font-size: 0.85rem; font-weight: 600;"
                                                <?= ((int)$cat['jumlah_layanan'] > 0) ? 'title="Tidak dapat menghapus kategori yang masih memiliki layanan" disabled style="opacity: 0.5; cursor: not-allowed;"' : '' ?>>
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

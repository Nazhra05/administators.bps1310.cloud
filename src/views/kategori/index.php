<?php
/** @var array<int, array<string, mixed>> $categories */
/** @var array<int, array<int, array<string, mixed>>> $servicesByCategory */
/** @var string $search */
/** @var string $sort */
/** @var string $order */
/** @var bool $hasFilters */
/** @var array{page: int, per_page: int, total_items: int, total_pages: int, from: int, to: int} $pagination */

$sortUrl = function(string $column) use ($sort, $order): string {
    $params = $_GET;
    $params['sort'] = $column;
    $params['order'] = ($sort === $column && $order === 'asc') ? 'desc' : 'asc';
    $params['page'] = 1;
    return e(url('/kategori.php?' . http_build_query($params)));
};
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
    <!-- Search and Filter Bar -->
    <div style="border-bottom: 1px solid #f1f5f9; padding-bottom: 16px; margin-bottom: 20px;">
        <form method="GET" action="<?= e(url('/kategori.php')) ?>" style="display: flex; flex-wrap: wrap; gap: 12px; align-items: center;">
            <?php if (!empty($sort)): ?>
                <input type="hidden" name="sort" value="<?= e($sort) ?>">
            <?php endif; ?>
            <?php if (!empty($order)): ?>
                <input type="hidden" name="order" value="<?= e($order) ?>">
            <?php endif; ?>

            <div style="flex: 1; min-width: 240px;">
                <input type="text" name="search" value="<?= e($search) ?>" placeholder="Cari nama kategori..."
                       style="width: 100%; box-sizing: border-box; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 0.875rem;">
            </div>

            <button type="submit"
                    style="background: #2563eb; color: white; border: none; padding: 8px 16px; border-radius: 8px; font-weight: 600; font-size: 0.875rem; cursor: pointer;">
                Cari
            </button>

            <?php if ($hasFilters): ?>
                <a href="<?= e(url('/kategori.php')) ?>"
                   style="color: #64748b; font-size: 0.85rem; text-decoration: underline; padding: 8px 4px;">
                    Reset Pencarian
                </a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Table Kategori -->
    <div class="table-container" style="overflow-x: auto;">
        <table class="kategori-table" style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.925rem;">
            <thead>
                <tr style="border-bottom: 2px solid #e2e8f0; color: #475569; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.03em;">
                    <th style="padding: 12px 14px; width: 60px;">
                        <a href="<?= $sortUrl('id') ?>" style="color: inherit; text-decoration: none;">
                            No <?= ($sort === 'id') ? ($order === 'asc' ? '▲' : '▼') : '' ?>
                        </a>
                    </th>
                    <th style="padding: 12px 14px;">
                        <a href="<?= $sortUrl('nama') ?>" style="color: inherit; text-decoration: none;">
                            Nama Kategori <?= ($sort === 'nama') ? ($order === 'asc' ? '▲' : '▼') : '' ?>
                        </a>
                    </th>
                    <th style="padding: 12px 14px; width: 150px; text-align: center;">
                        <a href="<?= $sortUrl('layanan') ?>" style="color: inherit; text-decoration: none;">
                            Jumlah Layanan <?= ($sort === 'layanan') ? ($order === 'asc' ? '▲' : '▼') : '' ?>
                        </a>
                    </th>
                    <th style="padding: 12px 14px;">Preview Layanan</th>
                    <th style="padding: 12px 14px; width: 140px; text-align: center;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($categories)): ?>
                    <tr>
                        <td colspan="5" style="text-align: center; padding: 48px 16px; color: #64748b;">
                            <?php if ($hasFilters): ?>
                                <div style="font-size: 2rem; margin-bottom: 8px;">🔍</div>
                                <div style="font-weight: 600; color: #1e293b; font-size: 1rem; margin-bottom: 4px;">Kategori Tidak Ditemukan</div>
                                <div style="font-size: 0.875rem; margin-bottom: 16px;">Tidak ada kategori yang cocok dengan pencarian "<strong><?= e($search) ?></strong>".</div>
                                <a href="<?= e(url('/kategori.php')) ?>" style="display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px; background: #f1f5f9; color: #2563eb; border-radius: 6px; text-decoration: none; font-weight: 600; font-size: 0.85rem;">
                                    Reset Pencarian
                                </a>
                            <?php else: ?>
                                <div style="font-size: 2rem; margin-bottom: 8px;">📭</div>
                                <div style="font-weight: 600; color: #1e293b; font-size: 1rem; margin-bottom: 4px;">Belum Ada Kategori</div>
                                <div style="font-size: 0.875rem; margin-bottom: 16px;">Belum ada kategori layanan yang terdaftar di sistem.</div>
                                <a href="<?= e(url('/tambah_kategori.php')) ?>" style="display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px; background: #2563eb; color: white; border-radius: 6px; text-decoration: none; font-weight: 600; font-size: 0.85rem;">
                                    + Tambah Kategori Pertama
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php $no = (int)($pagination['from'] ?? 1); foreach ($categories as $cat): ?>
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
                                          onsubmit="return confirm('Apakah Anda yakin ingin menghapus kategori: &quot;<?= e(addslashes($cat['nama_kategori'])) ?>&quot;?');"
                                          style="display: inline; margin: 0;">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="id" value="<?= $catId ?>">
                                        <button type="submit" class="action-btn delete-btn" data-loading-text="Menghapus..."
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

    <!-- Server-Side Pagination (Task 7.12) -->
    <?php
        $baseUrl = '/kategori.php';
        include __DIR__ . '/../partials/pagination.php';
    ?>
</div>

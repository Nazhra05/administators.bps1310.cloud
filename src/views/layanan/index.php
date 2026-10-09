<?php
/** @var array<int, array<string, mixed>> $services */
/** @var array<int, array<string, mixed>> $categories */
/** @var int|null $selectedKategori */
/** @var string|null $selectedStatus */
/** @var int $pendingCount */
/** @var array<string, mixed>|null $user */

$userRole = (string)($user['role'] ?? '');
$userUnitId = isset($user['unit_id']) ? (int)$user['unit_id'] : null;
$baseKatQuery = $selectedKategori !== null ? ('&id_kategori=' . $selectedKategori) : '';
$baseStatusQuery = $selectedStatus !== null ? ('&status=' . urlencode($selectedStatus)) : '';
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
    <!-- Filter Tabs & Dropdown -->
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; margin-bottom: 20px; border-bottom: 1px solid #f1f5f9; padding-bottom: 16px;">
        <!-- Status Tabs -->
        <div style="display: flex; gap: 8px; flex-wrap: wrap; align-items: center;">
            <a href="<?= e(url('/layanan.php' . ($selectedKategori ? '?id_kategori=' . $selectedKategori : ''))) ?>"
               style="padding: 7px 14px; border-radius: 6px; font-size: 0.85rem; font-weight: 600; text-decoration: none; <?= $selectedStatus === null ? 'background: #2563eb; color: white;' : 'background: #f1f5f9; color: #475569;' ?>">
                Semua
            </a>

            <a href="<?= e(url('/layanan.php?status=pending' . $baseKatQuery)) ?>"
               style="padding: 7px 14px; border-radius: 6px; font-size: 0.85rem; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; <?= $selectedStatus === 'pending' ? 'background: #d97706; color: white;' : 'background: #fef3c7; color: #92400e;' ?>">
                <span>Menunggu Review</span>
                <?php if ($pendingCount > 0): ?>
                    <span style="display: inline-block; padding: 1px 7px; border-radius: 9999px; font-size: 0.75rem; font-weight: 700; background: <?= $selectedStatus === 'pending' ? 'white' : '#b45309' ?>; color: <?= $selectedStatus === 'pending' ? '#d97706' : 'white' ?>;">
                        <?= $pendingCount ?>
                    </span>
                <?php endif; ?>
            </a>

            <a href="<?= e(url('/layanan.php?status=published' . $baseKatQuery)) ?>"
               style="padding: 7px 14px; border-radius: 6px; font-size: 0.85rem; font-weight: 600; text-decoration: none; <?= $selectedStatus === 'published' ? 'background: #16a34a; color: white;' : 'background: #f1f5f9; color: #475569;' ?>">
                Published
            </a>

            <a href="<?= e(url('/layanan.php?status=draft' . $baseKatQuery)) ?>"
               style="padding: 7px 14px; border-radius: 6px; font-size: 0.85rem; font-weight: 600; text-decoration: none; <?= $selectedStatus === 'draft' ? 'background: #64748b; color: white;' : 'background: #f1f5f9; color: #475569;' ?>">
                Draft
            </a>
        </div>

        <!-- Kategori Filter -->
        <form method="GET" action="<?= e(url('/layanan.php')) ?>" style="display: flex; align-items: center; gap: 10px;">
            <?php if ($selectedStatus !== null): ?>
                <input type="hidden" name="status" value="<?= e($selectedStatus) ?>">
            <?php endif; ?>
            <label for="id_kategori" style="font-size: 0.85rem; font-weight: 600; color: #475569;">Kategori:</label>
            <select name="id_kategori" id="id_kategori" onchange="this.form.submit()"
                    style="padding: 7px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 0.85rem; background: white; cursor: pointer;">
                <option value="">Semua Kategori</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= e($cat['id_kategori']) ?>" <?= ($selectedKategori === (int)$cat['id_kategori']) ? 'selected' : '' ?>>
                        <?= e($cat['nama_kategori']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <?php if ($selectedKategori !== null || $selectedStatus !== null): ?>
                <a href="<?= e(url('/layanan.php')) ?>" style="font-size: 0.8rem; color: #64748b; text-decoration: underline; margin-left: 2px;">
                    Reset
                </a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Table Layanan -->
    <div class="table-container" style="overflow-x: auto;">
        <table class="layanan-table" style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.925rem;">
            <thead>
                <tr style="border-bottom: 2px solid #e2e8f0; color: #475569; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.03em;">
                    <th style="padding: 12px 14px; width: 45px;">No</th>
                    <th style="padding: 12px 14px; width: 55px;">Logo</th>
                    <th style="padding: 12px 14px;">Nama Layanan</th>
                    <th style="padding: 12px 14px;">Kategori & Unit</th>
                    <th style="padding: 12px 14px; width: 110px; text-align: center;">Status</th>
                    <th style="padding: 12px 14px; width: 85px; text-align: center;">Akses</th>
                    <th style="padding: 12px 14px; width: 80px; text-align: center;">VPN</th>
                    <th style="padding: 12px 14px; width: 100px;">Link</th>
                    <th style="padding: 12px 14px; min-width: 170px; text-align: center;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($services)): ?>
                    <tr>
                        <td colspan="9" style="text-align: center; padding: 36px 16px; color: #64748b;">
                            <div style="font-size: 1.5rem; margin-bottom: 8px;">📭</div>
                            <div>Tidak ada layanan yang sesuai dengan filter.</div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php $no = 1; foreach ($services as $srv): ?>
                        <?php 
                            $srvId = (int)$srv['id_layanan'];
                            $status = (string)($srv['status'] ?? 'published');
                            $canEdit = ($userRole === 'ADMIN IT') || ($userUnitId !== null && (int)($srv['unit_id'] ?? 0) === $userUnitId);
                        ?>
                        <tr style="border-bottom: 1px solid #f1f5f9; <?= $status === 'pending' ? 'background: #fffbeb;' : '' ?>">
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
                                    <div style="font-size: 0.8rem; color: #64748b; font-weight: 400; margin-top: 2px; max-width: 300px;">
                                        <?= e($srv['deskripsi_layanan']) ?>
                                    </div>
                                <?php endif; ?>

                                <?php if ($status === 'draft' && !empty($srv['reject_reason'])): ?>
                                    <div style="margin-top: 6px; font-size: 0.775rem; color: #b91c1c; background: #fef2f2; border: 1px solid #fecaca; border-radius: 4px; padding: 4px 8px; line-height: 1.4; max-width: 320px;">
                                        <strong>Catatan Penolakan:</strong> <?= e($srv['reject_reason']) ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 12px 14px;">
                                <span style="display: inline-block; padding: 3px 10px; border-radius: 9999px; background: #e0f2fe; color: #0369a1; font-size: 0.8rem; font-weight: 600;">
                                    <?= e($srv['nama_kategori'] ?? 'Tanpa Kategori') ?>
                                </span>
                                <?php if (!empty($srv['unit_name'])): ?>
                                    <div style="font-size: 0.75rem; color: #475569; margin-top: 4px; font-weight: 500;">
                                        🏛️ <?= e($srv['unit_name']) ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 12px 14px; text-align: center;">
                                <?php if ($status === 'published'): ?>
                                    <span style="display: inline-block; padding: 3px 9px; border-radius: 9999px; font-size: 0.75rem; font-weight: 600; background: #dcfce7; color: #166534; border: 1px solid #bbf7d0;">
                                        Published
                                    </span>
                                <?php elseif ($status === 'pending'): ?>
                                    <span style="display: inline-block; padding: 3px 9px; border-radius: 9999px; font-size: 0.75rem; font-weight: 600; background: #fef3c7; color: #92400e; border: 1px solid #fde68a;">
                                        Menunggu Review
                                    </span>
                                <?php elseif ($status === 'draft'): ?>
                                    <span style="display: inline-block; padding: 3px 9px; border-radius: 9999px; font-size: 0.75rem; font-weight: 600; background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1;">
                                        Draft
                                    </span>
                                <?php endif; ?>
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
                                    <span>Tautan</span>
                                    <span style="font-size: 11px;">↗</span>
                                </a>
                            </td>
                            <td style="padding: 12px 14px; text-align: center;">
                                <div style="display: inline-flex; align-items: center; gap: 6px; flex-wrap: wrap; justify-content: center;">
                                    <!-- Aksi Persetujuan Khusus ADMIN IT -->
                                    <?php if ($userRole === 'ADMIN IT' && $status === 'pending'): ?>
                                        <form method="POST" action="<?= e(url('/layanan/status')) ?>" style="display: inline; margin: 0;"
                                              onsubmit="return confirm('Apakah Anda yakin ingin menyetujui dan mempublikasikan layanan ini?');">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="id" value="<?= $srvId ?>">
                                            <input type="hidden" name="action" value="approve">
                                            <button type="submit"
                                                    style="background: #16a34a; color: white; border: none; padding: 5px 10px; border-radius: 6px; cursor: pointer; font-size: 0.8rem; font-weight: 600;"
                                                    title="Setujui dan Publikasikan">
                                                ✓ Setujui
                                            </button>
                                        </form>

                                        <button type="button"
                                                onclick="document.getElementById('modalReject_<?= $srvId ?>').style.display = 'flex';"
                                                style="background: #fee2e2; color: #dc2626; border: 1px solid #fecaca; padding: 5px 10px; border-radius: 6px; cursor: pointer; font-size: 0.8rem; font-weight: 600;"
                                                title="Tolak Layanan dan Berikan Catatan Perbaikan">
                                            ✕ Tolak
                                        </button>

                                        <!-- Modal Dialog Tolak Layanan -->
                                        <div id="modalReject_<?= $srvId ?>" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.4); z-index: 9999; align-items: center; justify-content: center; padding: 16px;">
                                            <div style="background: white; border-radius: 12px; max-width: 440px; width: 100%; margin: 60px auto; padding: 24px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1); text-align: left;">
                                                <h3 style="margin: 0 0 8px 0; font-size: 1.1rem; color: #0f172a; font-weight: 700;">
                                                    Tolak Layanan: <?= e($srv['nama_layanan']) ?>
                                                </h3>
                                                <p style="color: #64748b; font-size: 0.85rem; margin: 0 0 16px 0;">
                                                    Berikan alasan penolakan agar pengaju dapat memperbaiki data layanan. Layanan akan dikembalikan ke status <strong>Draft</strong>.
                                                </p>
                                                <form method="POST" action="<?= e(url('/layanan/status')) ?>">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="id" value="<?= $srvId ?>">
                                                    <input type="hidden" name="action" value="reject">
                                                    <div style="margin-bottom: 16px;">
                                                        <label for="reason_<?= $srvId ?>" style="display: block; font-weight: 600; font-size: 0.85rem; color: #334155; margin-bottom: 6px;">
                                                            Alasan Penolakan <span style="color: #ef4444;">*</span>
                                                        </label>
                                                        <textarea id="reason_<?= $srvId ?>" name="reject_reason" rows="3" required
                                                                  placeholder="Contoh: URL tidak dapat diakses, mohon periksa kembali tautan..."
                                                                  style="width: 100%; box-sizing: border-box; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.875rem; font-family: inherit;"></textarea>
                                                    </div>
                                                    <div style="display: flex; justify-content: flex-end; gap: 8px;">
                                                        <button type="button" onclick="document.getElementById('modalReject_<?= $srvId ?>').style.display = 'none';"
                                                                style="background: #e2e8f0; color: #475569; border: none; padding: 8px 14px; border-radius: 6px; font-size: 0.85rem; font-weight: 600; cursor: pointer;">
                                                            Batal
                                                        </button>
                                                        <button type="submit"
                                                                style="background: #dc2626; color: white; border: none; padding: 8px 16px; border-radius: 6px; font-size: 0.85rem; font-weight: 600; cursor: pointer;">
                                                            Tolak Layanan
                                                        </button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    <?php endif; ?>

                                    <!-- Edit & Hapus -->
                                    <?php if ($canEdit): ?>
                                        <a href="<?= e(url('/edit_layanan.php?id=' . $srvId)) ?>"
                                           class="action-btn edit-btn"
                                           style="background: #f1f5f9; color: #2563eb; padding: 5px 10px; border-radius: 6px; text-decoration: none; font-size: 0.8rem; font-weight: 600;">
                                            Edit
                                        </a>

                                        <form method="POST" action="<?= e(url('/hapus_layanan.php')) ?>"
                                              onsubmit="return confirm('Apakah Anda yakin ingin menghapus layanan ini?');"
                                              style="display: inline; margin: 0;">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="id" value="<?= $srvId ?>">
                                            <button type="submit" class="action-btn delete-btn"
                                                    style="background: #fef2f2; color: #dc2626; border: none; padding: 5px 10px; border-radius: 6px; cursor: pointer; font-size: 0.8rem; font-weight: 600;">
                                                Hapus
                                            </button>
                                        </form>
                                    <?php elseif (!($userRole === 'ADMIN IT' && $status === 'pending')): ?>
                                        <span style="font-size: 0.8rem; color: #94a3b8; font-style: italic;">
                                            Hanya lihat
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 28px;">
    <div class="card" style="margin-bottom: 0; display: flex; align-items: center; gap: 16px;">
        <div style="width: 48px; height: 48px; border-radius: 12px; background: #e0f2fe; color: #0284c7; display: flex; align-items: center; justify-content: center; font-size: 24px;">
            🛠️
        </div>
        <div>
            <span style="font-size: 0.85rem; color: #64748b; font-weight: 600; text-transform: uppercase;">Total Layanan</span>
            <h2 style="font-size: 1.75rem; color: #0f172a; margin-top: 2px; font-weight: 700;"><?= (int)$totalLayanan ?></h2>
        </div>
    </div>

    <div class="card" style="margin-bottom: 0; display: flex; align-items: center; gap: 16px;">
        <div style="width: 48px; height: 48px; border-radius: 12px; background: #fef3c7; color: #d97706; display: flex; align-items: center; justify-content: center; font-size: 24px;">
            📁
        </div>
        <div>
            <span style="font-size: 0.85rem; color: #64748b; font-weight: 600; text-transform: uppercase;">Total Kategori</span>
            <h2 style="font-size: 1.75rem; color: #0f172a; margin-top: 2px; font-weight: 700;"><?= (int)$totalKategori ?></h2>
        </div>
    </div>
</div>

<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px; margin-bottom: 20px;">
        <h2 style="font-size: 1.25rem; color: #0f172a; font-weight: 700;">Daftar Layanan</h2>

        <form method="GET" action="<?= e(url('/')) ?>" style="display: flex; align-items: center; gap: 10px;">
            <label for="kategori" style="font-size: 0.9rem; font-weight: 600; color: #475569;">Filter:</label>
            <select name="kategori" id="kategori" onchange="this.form.submit()"
                    style="padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 0.9rem; background: white; cursor: pointer;">
                <option value="">Semua Kategori</option>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= e($cat['id_kategori']) ?>" <?= ((string)$filterKategori === (string)$cat['id_kategori']) ? 'selected' : '' ?>>
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
                    <th style="padding: 12px 14px;">Link</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($services)): ?>
                    <tr>
                        <td colspan="5" style="text-align: center; padding: 32px; color: #64748b;">
                            Tidak ada layanan yang ditemukan.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php $no = 1; foreach ($services as $srv): ?>
                        <tr style="border-bottom: 1px solid #f1f5f9; transition: background 0.1s ease;">
                            <td style="padding: 10px 14px; color: #64748b; font-weight: 500;">
                                <?= $no++ ?>
                            </td>
                            <td style="padding: 10px 14px;">
                                <?php if (!empty($srv['logo'])): ?>
                                    <img src="<?= e(asset('uploads/' . $srv['logo'])) ?>" alt="Logo"
                                         style="width: 32px; height: 32px; object-fit: contain; border-radius: 6px; background: #f8fafc; padding: 2px; border: 1px solid #e2e8f0;">
                                <?php else: ?>
                                    <div style="width: 32px; height: 32px; border-radius: 6px; background: #e2e8f0; display: flex; align-items: center; justify-content: center; font-size: 14px;">
                                        🔗
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 10px 14px; font-weight: 600; color: #1e293b;">
                                <?= e($srv['nama_layanan']) ?>
                            </td>
                            <td style="padding: 10px 14px;">
                                <span style="display: inline-block; padding: 4px 10px; border-radius: 9999px; background: #e0f2fe; color: #0369a1; font-size: 0.8rem; font-weight: 600;">
                                    <?= e($srv['nama_kategori'] ?? 'Tanpa Kategori') ?>
                                </span>
                            </td>
                            <td style="padding: 10px 14px;">
                                <a href="<?= e($srv['url']) ?>" target="_blank" rel="noopener noreferrer"
                                   style="color: #0284c7; text-decoration: none; display: inline-flex; align-items: center; gap: 4px; font-size: 0.875rem;">
                                    <span>Buka Tautan</span>
                                    <span style="font-size: 11px;">↗</span>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

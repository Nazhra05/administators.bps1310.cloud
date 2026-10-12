<?php
/**
 * Modal Tambah dan Edit Layanan
 * Digunakan bersama pada public/layanan.php dan halaman manajemen layanan lainnya.
 */
?>
<!-- ===================================================== 
     MODAL FORM TAMBAH LAYANAN (MATCH REPOSITORI PORTAL) 
===================================================== --> 
<div class="modal-overlay" id="modalTambahLayanan" onclick="closeModalOnBackdrop(event, 'modalTambahLayanan')">
    <div class="modal-card">
        <div class="modal-header">
            <h3>Tambah Layanan Baru</h3>
            <button type="button" class="modal-close-btn" onclick="closeModal('modalTambahLayanan')">✕</button>
        </div>
        <form method="POST" action="<?= htmlspecialchars(basename($_SERVER['PHP_SELF'] ?? 'layanan.php')); ?>" enctype="multipart/form-data">
            <input type="hidden" name="action" value="tambah_layanan">
            <div class="modal-body">
                <div class="form-group">
                    <label for="tambah_nama_layanan">Nama Layanan *</label>
                    <input type="text" id="tambah_nama_layanan" name="nama_layanan" required placeholder="Contoh: SIPP - Portal Pelaporan Perusahaan">
                </div>

                <div class="form-group">
                    <label for="tambah_deskripsi_layanan">Deskripsi Layanan *</label>
                    <textarea id="tambah_deskripsi_layanan" name="deskripsi_layanan" rows="3" required placeholder="Jelaskan fungsi dan tujuan layanan ini..."></textarea>
                </div>

                <div class="form-group">
                    <label for="tambah_url">URL / Tautan Link *</label>
                    <input type="url" id="tambah_url" name="url" required placeholder="https://contoh.bps.go.id">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="tambah_id_kategori">Kategori *</label>
                        <select id="tambah_id_kategori" name="id_kategori" required>
                            <option value="">Pilih Kategori...</option>
                            <?php foreach ($data_kategori as $kat): ?>
                                <option value="<?= (int) $kat['id_kategori']; ?>">
                                    <?= htmlspecialchars($kat['nama_kategori']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="tambah_akses">Tipe Akses *</label>
                        <select id="tambah_akses" name="akses" required>
                            <option value="Public">Public (Umum)</option>
                            <option value="Internal">Internal (Pegawai BPS)</option>
                            <option value="Private">Private</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="tambah_vpn">Memerlukan VPN? *</label>
                        <select id="tambah_vpn" name="vpn" required>
                            <option value="No">No (Tidak)</option>
                            <option value="Yes">Yes (Ya)</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="tambah_logo">Logo / Gambar (Opsional)</label>
                        <input type="file" id="tambah_logo" name="logo" accept="image/png,image/jpeg,image/webp">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="category-select" onclick="closeModal('modalTambahLayanan')">Batal</button>
                <button type="submit" class="btn-tambah">Simpan Layanan</button>
            </div>
        </form>
    </div>
</div>

<!-- ===================================================== 
     MODAL FORM EDIT LAYANAN (MATCH REPOSITORI PORTAL) 
===================================================== --> 
<div class="modal-overlay" id="modalEditLayanan" onclick="closeModalOnBackdrop(event, 'modalEditLayanan')">
    <div class="modal-card">
        <div class="modal-header">
            <h3>Edit Layanan</h3>
            <button type="button" class="modal-close-btn" onclick="closeModal('modalEditLayanan')">✕</button>
        </div>
        <form method="POST" action="<?= htmlspecialchars(basename($_SERVER['PHP_SELF'] ?? 'layanan.php')); ?>" enctype="multipart/form-data">
            <input type="hidden" name="action" value="edit_layanan">
            <input type="hidden" name="id_layanan" id="edit_layanan_id" value="">
            <div class="modal-body">
                <div class="form-group">
                    <label for="edit_nama_layanan">Nama Layanan *</label>
                    <input type="text" id="edit_nama_layanan" name="nama_layanan" required placeholder="Contoh: SIPP - Portal Pelaporan Perusahaan">
                </div>

                <div class="form-group">
                    <label for="edit_deskripsi_layanan">Deskripsi Layanan *</label>
                    <textarea id="edit_deskripsi_layanan" name="deskripsi_layanan" rows="3" required placeholder="Jelaskan fungsi dan tujuan layanan ini..."></textarea>
                </div>

                <div class="form-group">
                    <label for="edit_url">URL / Tautan Link *</label>
                    <input type="url" id="edit_url" name="url" required placeholder="https://contoh.bps.go.id">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="edit_id_kategori">Kategori *</label>
                        <select id="edit_id_kategori" name="id_kategori" required>
                            <option value="">Pilih Kategori...</option>
                            <?php foreach ($data_kategori as $kat): ?>
                                <option value="<?= (int) $kat['id_kategori']; ?>">
                                    <?= htmlspecialchars($kat['nama_kategori']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="edit_akses">Tipe Akses *</label>
                        <select id="edit_akses" name="akses" required>
                            <option value="Public">Public (Umum)</option>
                            <option value="Internal">Internal (Pegawai BPS)</option>
                            <option value="Private">Private</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="edit_vpn">Memerlukan VPN? *</label>
                        <select id="edit_vpn" name="vpn" required>
                            <option value="No">No (Tidak)</option>
                            <option value="Yes">Yes (Ya)</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="edit_logo">Logo / Gambar Baru (Opsional)</label>
                        <input type="file" id="edit_logo" name="logo" accept="image/png,image/jpeg,image/webp">
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="category-select" onclick="closeModal('modalEditLayanan')">Batal</button>
                <button type="submit" class="btn-tambah">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

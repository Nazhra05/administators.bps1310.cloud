<?php
/** @var array<int, array<string, mixed>> $admins */
/** @var array<int, array<string, mixed>> $units */
/** @var array<string, mixed> $user */
/** @var array<string, mixed> $formInput */
/** @var array<string, string> $formErrors */
$formInput = $formInput ?? [];
$formErrors = $formErrors ?? [];
$showAddForm = !empty($formErrors);
?>

<div class="header" style="margin-bottom: 24px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;">
    <div>
        <h1 style="font-size: 1.5rem; color: #0f172a; font-weight: 700; margin: 0;">Kelola Pengguna & Administrator</h1>
        <p style="color: #64748b; font-size: 0.9rem; margin: 4px 0 0 0;">Otorisasi, unit kerja, penugasan peran, dan manajemen akun sistem BPS Solok Selatan</p>
    </div>
    <button type="button" onclick="document.getElementById('formTambahUser').style.display = document.getElementById('formTambahUser').style.display === 'none' ? 'block' : 'none';"
            style="background: #2563eb; color: white; border: none; padding: 9px 16px; border-radius: 8px; font-weight: 600; font-size: 0.9rem; cursor: pointer; display: inline-flex; align-items: center; gap: 6px;">
        <span>➕</span> Tambah Pengguna Baru
    </button>
</div>

<!-- Form Tambah Pengguna Baru -->
<div id="formTambahUser" class="card" style="display: <?= $showAddForm ? 'block' : 'none' ?>; margin-bottom: 24px; border-left: 4px solid #2563eb;">
    <h2 style="font-size: 1.1rem; color: #1e293b; font-weight: 700; margin: 0 0 16px 0;">Tambah Akun Baru</h2>
    <form method="POST" action="<?= e(url('/admin/tambah')) ?>" novalidate>
        <?= csrf_field() ?>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-bottom: 16px;">
            <div>
                <label for="new_username" style="display: block; font-weight: 600; font-size: 0.85rem; color: #334155; margin-bottom: 6px;">
                    Username <span style="color: #ef4444;">*</span>
                </label>
                <input type="text" id="new_username" name="username" value="<?= e($formInput['username'] ?? '') ?>" required
                       placeholder="contoh: budi_solsel"
                       class="<?= !empty($formErrors['username']) ? 'input-error' : '' ?>"
                       style="width: 100%; box-sizing: border-box; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.9rem;">
                <?php if (!empty($formErrors['username'])): ?>
                    <span class="field-error"><?= e($formErrors['username']) ?></span>
                <?php endif; ?>
            </div>
            <div>
                <label for="new_email" style="display: block; font-weight: 600; font-size: 0.85rem; color: #334155; margin-bottom: 6px;">
                    Email <span style="color: #ef4444;">*</span>
                </label>
                <input type="email" id="new_email" name="email" value="<?= e($formInput['email'] ?? '') ?>" required
                       placeholder="budi@bps.go.id"
                       class="<?= !empty($formErrors['email']) ? 'input-error' : '' ?>"
                       style="width: 100%; box-sizing: border-box; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.9rem;">
                <?php if (!empty($formErrors['email'])): ?>
                    <span class="field-error"><?= e($formErrors['email']) ?></span>
                <?php endif; ?>
            </div>
            <div>
                <label for="new_first_name" style="display: block; font-weight: 600; font-size: 0.85rem; color: #334155; margin-bottom: 6px;">
                    Nama Depan
                </label>
                <input type="text" id="new_first_name" name="first_name" value="<?= e($formInput['first_name'] ?? '') ?>"
                       placeholder="Budi"
                       style="width: 100%; box-sizing: border-box; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.9rem;">
            </div>
            <div>
                <label for="new_last_name" style="display: block; font-weight: 600; font-size: 0.85rem; color: #334155; margin-bottom: 6px;">
                    Nama Belakang
                </label>
                <input type="text" id="new_last_name" name="last_name" value="<?= e($formInput['last_name'] ?? '') ?>"
                       placeholder="Santoso"
                       style="width: 100%; box-sizing: border-box; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.9rem;">
            </div>
            <div>
                <label for="new_role" style="display: block; font-weight: 600; font-size: 0.85rem; color: #334155; margin-bottom: 6px;">
                    Role / Hak Akses <span style="color: #ef4444;">*</span>
                </label>
                <select id="new_role" name="role" required
                        style="width: 100%; box-sizing: border-box; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.9rem; background: white;">
                    <option value="ADMIN DASHBOARD" <?= (($formInput['role'] ?? 'ADMIN DASHBOARD') === 'ADMIN DASHBOARD') ? 'selected' : '' ?>>ADMIN DASHBOARD (Leader/Operator)</option>
                    <option value="ADMIN IT" <?= (($formInput['role'] ?? '') === 'ADMIN IT') ? 'selected' : '' ?>>ADMIN IT (Super Administrator)</option>
                    <option value="USER APP PORTAL" <?= (($formInput['role'] ?? '') === 'USER APP PORTAL') ? 'selected' : '' ?>>USER APP PORTAL (Pegawai Biasa)</option>
                </select>
            </div>
            <div>
                <label for="new_unit_id" style="display: block; font-weight: 600; font-size: 0.85rem; color: #334155; margin-bottom: 6px;">
                    Unit Kerja
                </label>
                <select id="new_unit_id" name="unit_id"
                        style="width: 100%; box-sizing: border-box; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.9rem; background: white;">
                    <option value="">-- Pilih Unit Kerja (Opsional) --</option>
                    <?php foreach ($units as $u): ?>
                        <option value="<?= (int)$u['id'] ?>" <?= ((string)($formInput['unit_id'] ?? '') === (string)$u['id']) ? 'selected' : '' ?>><?= e($u['nama']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label for="new_password" style="display: block; font-weight: 600; font-size: 0.85rem; color: #334155; margin-bottom: 6px;">
                    Password (Opsional)
                </label>
                <input type="text" id="new_password" name="password"
                       placeholder="Kosongkan untuk buat password acak"
                       class="<?= !empty($formErrors['password']) ? 'input-error' : '' ?>"
                       style="width: 100%; box-sizing: border-box; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.9rem;">
                <?php if (!empty($formErrors['password'])): ?>
                    <span class="field-error"><?= e($formErrors['password']) ?></span>
                <?php endif; ?>
            </div>
        </div>
        <div style="display: flex; gap: 8px;">
            <button type="submit" data-loading-text="Menyimpan Akun..."
                    style="background: #16a34a; color: white; border: none; padding: 8px 18px; border-radius: 6px; font-weight: 600; font-size: 0.9rem; cursor: pointer;">
                Simpan Akun
            </button>
            <button type="button" onclick="document.getElementById('formTambahUser').style.display = 'none';"
                    style="background: #e2e8f0; color: #475569; border: none; padding: 8px 16px; border-radius: 6px; font-weight: 600; font-size: 0.9rem; cursor: pointer;">
                Batal
            </button>
        </div>
    </form>
</div>

<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; flex-wrap: wrap; gap: 8px;">
        <h2 style="font-size: 1.1rem; color: #1e293b; font-weight: 700; margin: 0;">Daftar Administrator Sistem</h2>
        <span style="font-size: 0.85rem; color: #64748b;">
            Total: <?= (int)($pagination['total_items'] ?? count($admins)) ?> Akun
        </span>
    </div>

    <!-- Search & Filter Form -->
    <div style="border-bottom: 1px solid #f1f5f9; padding-bottom: 16px; margin-bottom: 20px;">
        <form method="GET" action="<?= e(url('/kelola_admin.php')) ?>" style="display: flex; flex-wrap: wrap; gap: 12px; align-items: center;">
            <?php if (!empty($sort)): ?>
                <input type="hidden" name="sort" value="<?= e($sort) ?>">
            <?php endif; ?>
            <?php if (!empty($order)): ?>
                <input type="hidden" name="order" value="<?= e($order) ?>">
            <?php endif; ?>

            <div style="flex: 1; min-width: 240px;">
                <input type="text" name="search" value="<?= e($search ?? '') ?>" placeholder="Cari nama, username, email admin..."
                       style="width: 100%; box-sizing: border-box; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 0.875rem;">
            </div>

            <!-- Role Filter -->
            <div style="min-width: 170px;">
                <select name="role" onchange="this.form.submit()"
                        style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 0.875rem; background: white; cursor: pointer;">
                    <option value="">Semua Role</option>
                    <option value="ADMIN IT" <?= (($roleFilter ?? '') === 'ADMIN IT') ? 'selected' : '' ?>>ADMIN IT</option>
                    <option value="ADMIN DASHBOARD" <?= (($roleFilter ?? '') === 'ADMIN DASHBOARD') ? 'selected' : '' ?>>ADMIN DASHBOARD</option>
                </select>
            </div>

            <!-- Status Filter -->
            <div style="min-width: 140px;">
                <select name="status" onchange="this.form.submit()"
                        style="width: 100%; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 0.875rem; background: white; cursor: pointer;">
                    <option value="">Semua Status</option>
                    <option value="approved" <?= (($statusFilter ?? '') === 'approved') ? 'selected' : '' ?>>Approved</option>
                    <option value="pending" <?= (($statusFilter ?? '') === 'pending') ? 'selected' : '' ?>>Pending</option>
                </select>
            </div>

            <button type="submit"
                    style="background: #2563eb; color: white; border: none; padding: 8px 16px; border-radius: 8px; font-weight: 600; font-size: 0.875rem; cursor: pointer;">
                Cari
            </button>

            <?php if (!empty($hasFilters)): ?>
                <a href="<?= e(url('/kelola_admin.php')) ?>"
                   style="color: #64748b; font-size: 0.85rem; text-decoration: underline; padding: 8px 4px;">
                    Reset Filter
                </a>
            <?php endif; ?>
        </form>
    </div>

    <?php
        $sortUrlAdmin = function(string $column) use ($sort, $order): string {
            $params = $_GET;
            $params['sort'] = $column;
            $params['order'] = ($sort === $column && $order === 'asc') ? 'desc' : 'asc';
            $params['page'] = 1;
            return e(url('/kelola_admin.php?' . http_build_query($params)));
        };
    ?>

    <div class="table-container" style="overflow-x: auto;">
        <table class="admin-table" style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.925rem;">
            <thead>
                <tr style="border-bottom: 2px solid #e2e8f0; color: #475569; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 0.03em;">
                    <th style="padding: 12px 14px; width: 40px;">
                        <a href="<?= $sortUrlAdmin('id') ?>" style="color: inherit; text-decoration: none;">
                            No <?= ($sort === 'id') ? ($order === 'asc' ? '▲' : '▼') : '' ?>
                        </a>
                    </th>
                    <th style="padding: 12px 14px;">
                        <a href="<?= $sortUrlAdmin('username') ?>" style="color: inherit; text-decoration: none;">
                            Pengguna <?= ($sort === 'username') ? ($order === 'asc' ? '▲' : '▼') : '' ?>
                        </a>
                    </th>
                    <th style="padding: 12px 14px;">Kontak</th>
                    <th style="padding: 12px 14px;">Unit Kerja</th>
                    <th style="padding: 12px 14px; text-align: center;">
                        <a href="<?= $sortUrlAdmin('role') ?>" style="color: inherit; text-decoration: none;">
                            Role <?= ($sort === 'role') ? ($order === 'asc' ? '▲' : '▼') : '' ?>
                        </a>
                    </th>
                    <th style="padding: 12px 14px; text-align: center;">
                        <a href="<?= $sortUrlAdmin('status') ?>" style="color: inherit; text-decoration: none;">
                            Status <?= ($sort === 'status') ? ($order === 'asc' ? '▲' : '▼') : '' ?>
                        </a>
                    </th>
                    <th style="padding: 12px 14px; text-align: center;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($admins)): ?>
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 48px 16px; color: #64748b;">
                            <?php if (!empty($hasFilters)): ?>
                                <div style="font-size: 2rem; margin-bottom: 8px;">🔍</div>
                                <div style="font-weight: 600; color: #1e293b; font-size: 1rem; margin-bottom: 4px;">Pencarian Tidak Ditemukan</div>
                                <div style="font-size: 0.875rem; margin-bottom: 16px;">Tidak ada akun administrator yang sesuai dengan kriteria filter atau kata kunci pencarian.</div>
                                <a href="<?= e(url('/kelola_admin.php')) ?>" style="display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px; background: #f1f5f9; color: #2563eb; border-radius: 6px; text-decoration: none; font-weight: 600; font-size: 0.85rem;">
                                    Reset Semua Filter
                                </a>
                            <?php else: ?>
                                <div style="font-size: 2rem; margin-bottom: 8px;">👤</div>
                                <div style="font-weight: 600; color: #1e293b; font-size: 1rem; margin-bottom: 4px;">Belum Ada Administrator</div>
                                <div style="font-size: 0.875rem;">Belum ada data admin terdaftar.</div>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php $no = (int)($pagination['from'] ?? 1); foreach ($admins as $adm): ?>
                        <?php
                            $idAdmin = (int)$adm['id_admin'];
                            $isSelf = ((int)($user['id'] ?? 0) === $idAdmin);
                            $role = (string)($adm['role'] ?? '');
                            $status = (string)($adm['status'] ?? '');
                            $isActive = (int)($adm['is_active'] ?? 1);
                            $unitId = $adm['unit_id'] ? (int)$adm['unit_id'] : null;
                        ?>
                        <tr style="border-bottom: 1px solid #f1f5f9; <?= $isActive === 0 ? 'background: #f8fafc; opacity: 0.75;' : '' ?>">
                            <td style="padding: 12px 14px; color: #64748b; font-weight: 500;">
                                <?= $no++ ?>
                            </td>
                            <td style="padding: 12px 14px;">
                                <div style="font-weight: 600; color: #1e293b;">
                                    <?= e($adm['username'] ?? '-') ?>
                                    <?php if ($isSelf): ?>
                                        <span style="font-size: 0.75rem; background: #e0e7ff; color: #4338ca; padding: 2px 6px; border-radius: 4px; margin-left: 4px;">Anda</span>
                                    <?php endif; ?>
                                </div>
                                <div style="font-size: 0.8rem; color: #64748b; margin-top: 2px;">
                                    <?= e(trim(($adm['first_name'] ?? '') . ' ' . ($adm['last_name'] ?? '')) ?: '-') ?>
                                </div>
                            </td>
                            <td style="padding: 12px 14px; color: #64748b; font-size: 0.85rem;">
                                <?= e($adm['email'] ?? '-') ?>
                            </td>
                            <td style="padding: 12px 14px; color: #334155; font-size: 0.85rem;">
                                <?= e($adm['unit_name'] ?? 'Lintas Unit / Umum') ?>
                            </td>
                            <td style="padding: 12px 14px; text-align: center;">
                                <span style="display: inline-block; padding: 3px 8px; border-radius: 6px; font-size: 0.75rem; font-weight: 600; background: <?= ($role === 'ADMIN IT') ? '#fef3c7; color: #b45309;' : (($role === 'ADMIN DASHBOARD') ? '#e0f2fe; color: #0369a1;' : '#f1f5f9; color: #475569;') ?>">
                                    <?= e($role ?: 'USER') ?>
                                </span>
                            </td>
                            <td style="padding: 12px 14px; text-align: center;">
                                <div style="display: flex; flex-direction: column; gap: 4px; align-items: center;">
                                    <?php
                                        $statusBg = match ($status) {
                                            'approved' => '#dcfce7; color: #15803d;',
                                            'pending'  => '#fef9c3; color: #854d0e;',
                                            'rejected' => '#fee2e2; color: #b91c1c;',
                                            default    => '#f1f5f9; color: #64748b;',
                                        };
                                    ?>
                                    <span style="display: inline-block; padding: 2px 6px; border-radius: 4px; font-size: 0.7rem; font-weight: 600; background: <?= $statusBg ?>">
                                        <?= e(ucfirst($status)) ?>
                                    </span>
                                    <?php if ($isActive === 1): ?>
                                        <span style="font-size: 0.7rem; color: #16a34a; font-weight: 600;">● Aktif</span>
                                    <?php else: ?>
                                        <span style="font-size: 0.7rem; color: #dc2626; font-weight: 600;">● Nonaktif</span>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td style="padding: 12px 14px; text-align: center;">
                                <div style="display: inline-flex; align-items: center; gap: 4px; flex-wrap: wrap; justify-content: center;">
                                    <?php if ($status === 'pending'): ?>
                                        <form method="POST" action="<?= e(url('/kelola_admin.php')) ?>" style="display: inline; margin: 0;">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="id_admin" value="<?= $idAdmin ?>">
                                            <input type="hidden" name="action" value="approve">
                                            <button type="submit" style="background: #16a34a; color: white; border: none; padding: 4px 8px; border-radius: 4px; font-size: 0.75rem; cursor: pointer;">
                                                Setujui
                                            </button>
                                        </form>
                                        <form method="POST" action="<?= e(url('/kelola_admin.php')) ?>" style="display: inline; margin: 0;">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="id_admin" value="<?= $idAdmin ?>">
                                            <input type="hidden" name="action" value="reject">
                                            <button type="submit" style="background: #dc2626; color: white; border: none; padding: 4px 8px; border-radius: 4px; font-size: 0.75rem; cursor: pointer;">
                                                Tolak
                                            </button>
                                        </form>
                                    <?php elseif ($status === 'approved'): ?>
                                        <!-- Edit Role & Unit Modal Toggle -->
                                        <button type="button" onclick="document.getElementById('modalEdit_<?= $idAdmin ?>').style.display = 'block';"
                                                style="background: #e0f2fe; color: #0284c7; border: 1px solid #bae6fd; padding: 3px 7px; border-radius: 4px; font-size: 0.75rem; cursor: pointer;">
                                            Ubah
                                        </button>

                                        <!-- Toggle Aktif/Nonaktif -->
                                        <?php if ($isActive === 1): ?>
                                            <form method="POST" action="<?= e(url('/kelola_admin.php')) ?>" style="display: inline; margin: 0;"
                                                  onsubmit="return confirm('Nonaktifkan akun <?= e(addslashes($adm['username'])) ?>? Sesi pengguna yang bersangkutan akan langsung diakhiri.');">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="id_admin" value="<?= $idAdmin ?>">
                                                <input type="hidden" name="action" value="toggle_active">
                                                <input type="hidden" name="is_active" value="0">
                                                <button type="submit" data-loading-text="Memproses..."
                                                        style="background: #fef3c7; color: #b45309; border: 1px solid #fde68a; padding: 3px 7px; border-radius: 4px; font-size: 0.75rem; cursor: pointer;">
                                                    Nonaktifkan
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <form method="POST" action="<?= e(url('/kelola_admin.php')) ?>" style="display: inline; margin: 0;"
                                                  onsubmit="return confirm('Aktifkan kembali akun <?= e(addslashes($adm['username'])) ?>?');">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="id_admin" value="<?= $idAdmin ?>">
                                                <input type="hidden" name="action" value="toggle_active">
                                                <input type="hidden" name="is_active" value="1">
                                                <button type="submit" data-loading-text="Memproses..."
                                                        style="background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; padding: 3px 7px; border-radius: 4px; font-size: 0.75rem; cursor: pointer;">
                                                    Aktifkan
                                                </button>
                                            </form>
                                        <?php endif; ?>

                                        <!-- Reset Password -->
                                        <form method="POST" action="<?= e(url('/kelola_admin.php')) ?>" style="display: inline; margin: 0;"
                                              onsubmit="return confirm('Reset password untuk akun: &quot;<?= e(addslashes($adm['username'])) ?>&quot;?\nPassword sementara akan dibuatkan otomatis dan pengguna wajib menggantinya saat login.');">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="id_admin" value="<?= $idAdmin ?>">
                                            <input type="hidden" name="action" value="reset_password">
                                            <button type="submit" data-loading-text="Mereset..."
                                                    style="background: #f1f5f9; color: #334155; border: 1px solid #cbd5e1; padding: 3px 7px; border-radius: 4px; font-size: 0.75rem; cursor: pointer;">
                                                Reset Password
                                            </button>
                                        </form>

                                        <!-- Hapus Akun -->
                                        <?php if (!$isSelf): ?>
                                            <form method="POST" action="<?= e(url('/kelola_admin.php')) ?>"
                                                  onsubmit="return confirm('Hapus akun <?= e(addslashes($adm['username'])) ?> (<?= e(addslashes(($adm['first_name'] ?? '') . ' ' . ($adm['last_name'] ?? ''))) ?>) secara permanen?\nTindakan ini tidak dapat dibatalkan.');" style="display: inline; margin: 0;">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="id_admin" value="<?= $idAdmin ?>">
                                                <input type="hidden" name="action" value="delete">
                                                <button type="submit" data-loading-text="Menghapus..."
                                                        style="background: #fee2e2; color: #dc2626; border: 1px solid #fecaca; padding: 3px 7px; border-radius: 4px; font-size: 0.75rem; cursor: pointer;">
                                                    Hapus
                                                </button>
                                            </form>
                                        <?php endif; ?>

                                        <!-- Modal Dialog Ubah Role & Unit -->
                                        <div id="modalEdit_<?= $idAdmin ?>" style="display: none; position: fixed; inset: 0; background: rgba(15, 23, 42, 0.4); z-index: 9999; align-items: center; justify-content: center; padding: 16px;">
                                            <div style="background: white; border-radius: 12px; max-width: 420px; width: 100%; margin: 60px auto; padding: 24px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1); text-align: left;">
                                                <h3 style="margin: 0 0 16px 0; font-size: 1.1rem; color: #0f172a; font-weight: 700;">
                                                    Ubah Role & Unit: <?= e($adm['username'] ?? '') ?>
                                                </h3>
                                                <form method="POST" action="<?= e(url('/kelola_admin.php')) ?>">
                                                    <?= csrf_field() ?>
                                                    <input type="hidden" name="id_admin" value="<?= $idAdmin ?>">
                                                    <input type="hidden" name="action" value="update_role_unit">

                                                    <div style="margin-bottom: 14px;">
                                                        <label style="display: block; font-weight: 600; font-size: 0.85rem; color: #334155; margin-bottom: 6px;">
                                                            Role Akses
                                                        </label>
                                                        <select name="new_role" required style="width: 100%; box-sizing: border-box; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.9rem; background: white;">
                                                            <option value="ADMIN IT" <?= $role === 'ADMIN IT' ? 'selected' : '' ?>>ADMIN IT (Super Admin)</option>
                                                            <option value="ADMIN DASHBOARD" <?= $role === 'ADMIN DASHBOARD' ? 'selected' : '' ?>>ADMIN DASHBOARD (Leader/Operator)</option>
                                                            <option value="USER APP PORTAL" <?= $role === 'USER APP PORTAL' ? 'selected' : '' ?>>USER APP PORTAL (Pegawai Biasa)</option>
                                                        </select>
                                                    </div>

                                                    <div style="margin-bottom: 20px;">
                                                        <label style="display: block; font-weight: 600; font-size: 0.85rem; color: #334155; margin-bottom: 6px;">
                                                            Unit Kerja
                                                        </label>
                                                        <select name="new_unit_id" style="width: 100%; box-sizing: border-box; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 0.9rem; background: white;">
                                                            <option value="">-- Lintas Unit / Umum --</option>
                                                            <?php foreach ($units as $u): ?>
                                                                <option value="<?= (int)$u['id'] ?>" <?= $unitId === (int)$u['id'] ? 'selected' : '' ?>>
                                                                    <?= e($u['nama']) ?>
                                                                </option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </div>

                                                    <div style="display: flex; justify-content: flex-end; gap: 8px;">
                                                        <button type="button" onclick="document.getElementById('modalEdit_<?= $idAdmin ?>').style.display = 'none';"
                                                                style="background: #e2e8f0; color: #475569; border: none; padding: 8px 14px; border-radius: 6px; font-size: 0.85rem; font-weight: 600; cursor: pointer;">
                                                            Batal
                                                        </button>
                                                        <button type="submit"
                                                                style="background: #2563eb; color: white; border: none; padding: 8px 16px; border-radius: 6px; font-size: 0.85rem; font-weight: 600; cursor: pointer;">
                                                            Simpan Perubahan
                                                        </button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    <?php endif; ?>
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
        $baseUrl = '/kelola_admin.php';
        include __DIR__ . '/../partials/pagination.php';
    ?>
</div>

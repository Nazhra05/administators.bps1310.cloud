<?php
/** @var array<int, array<string, mixed>> $users */
/** @var array<int, array<string, mixed>> $importedCredentials */
/** @var string $error */
/** @var string $email_value */
?>

<div class="header" style="margin-bottom: 24px;">
    <h1 style="font-size: 1.5rem; color: #0f172a; font-weight: 700; margin: 0;">Kelola Pengguna Portal & Pegawai</h1>
    <p style="color: #64748b; font-size: 0.9rem; margin: 4px 0 0 0;">Impor akun pegawai massal dan kelola pengguna aplikasi portal internal BPS Solok Selatan</p>
</div>

<?php if (!empty($importedCredentials)): ?>
    <!-- One-Time Credentials Display Card (Task 7.7) -->
    <div class="card" style="margin-bottom: 24px; border-left: 5px solid #16a34a; background: #f0fdf4;">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 12px; margin-bottom: 14px;">
            <div>
                <h2 style="font-size: 1.15rem; color: #15803d; font-weight: 700; margin: 0;">
                    Hasil Impor Akun Pegawai (Ditampilkan Sekali Saja)
                </h2>
                <p style="color: #166534; font-size: 0.875rem; margin: 4px 0 0 0;">
                    Password sementara di bawah ini hanya ditampilkan <strong>satu kali</strong> demi keamanan. Harap unduh CSV atau bagikan kredensial ini kepada pegawai bersangkutan sekarang.
                </p>
            </div>
            <button id="btn-download-creds" onclick="downloadCredentialsCsv()"
                    style="background: #16a34a; color: white; border: none; padding: 8px 16px; border-radius: 6px; font-weight: 600; font-size: 0.875rem; cursor: pointer;">
                Unduh Kredensial (.CSV)
            </button>
        </div>

        <div class="table-container" style="overflow-x: auto; background: white; border-radius: 8px; border: 1px solid #bbf7d0;">
            <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.9rem;">
                <thead>
                    <tr style="background: #dcfce7; color: #166534; font-size: 0.8rem; text-transform: uppercase;">
                        <th style="padding: 10px 14px;">No</th>
                        <th style="padding: 10px 14px;">Username</th>
                        <th style="padding: 10px 14px;">Nama Pegawai</th>
                        <th style="padding: 10px 14px;">Unit Kerja</th>
                        <th style="padding: 10px 14px;">Email</th>
                        <th style="padding: 10px 14px;">Password Sementara</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $cNo = 1; foreach ($importedCredentials as $c): ?>
                        <tr style="border-bottom: 1px solid #f1f5f9;">
                            <td style="padding: 10px 14px; color: #64748b;"><?= $cNo++ ?></td>
                            <td style="padding: 10px 14px; font-weight: 600; color: #0f172a;"><?= e($c['username']) ?></td>
                            <td style="padding: 10px 14px; color: #334155;"><?= e($c['name']) ?></td>
                            <td style="padding: 10px 14px; color: #64748b;"><?= e($c['unit'] ?? '-') ?></td>
                            <td style="padding: 10px 14px; color: #64748b;"><?= e($c['email']) ?></td>
                            <td style="padding: 10px 14px;">
                                <code style="background: #fef08a; color: #854d0e; padding: 3px 8px; border-radius: 4px; font-weight: 700; font-size: 0.9rem;">
                                    <?= e($c['temp_password']) ?>
                                </code>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
    function downloadCredentialsCsv() {
        const rows = [
            ["Username", "Nama", "Unit", "Email", "Password Sementara"],
            <?php foreach ($importedCredentials as $c): ?>
                [
                    <?= json_encode($c['username']) ?>,
                    <?= json_encode($c['name']) ?>,
                    <?= json_encode($c['unit'] ?? '-') ?>,
                    <?= json_encode($c['email']) ?>,
                    <?= json_encode($c['temp_password']) ?>
                ],
            <?php endforeach; ?>
        ];
        let csvContent = "data:text/csv;charset=utf-8," + rows.map(e => e.map(x => '"' + String(x).replace(/"/g, '""') + '"').join(",")).join("\n");
        const encodedUri = encodeURI(csvContent);
        const link = document.createElement("a");
        link.setAttribute("href", encodedUri);
        link.setAttribute("download", "kredensial_sementara_pegawai_" + new Date().toISOString().slice(0, 10) + ".csv");
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }
    </script>
<?php endif; ?>

<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 20px; margin-bottom: 24px;">
    <!-- Card 1: Import Akun Pegawai (CSV) (Task 7.7) -->
    <div class="card" style="border-top: 4px solid #2563eb;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
            <h2 style="font-size: 1.1rem; color: #1e293b; font-weight: 700; margin: 0;">Impor Akun Pegawai (CSV)</h2>
            <a href="<?= e(url('/kelola_user.php?action=template')) ?>"
               style="color: #2563eb; font-size: 0.825rem; font-weight: 600; text-decoration: none;">
                Unduh Template CSV
            </a>
        </div>
        <p style="color: #64748b; font-size: 0.85rem; margin-top: 0; margin-bottom: 16px;">
            Unggah file CSV dengan kolom <code>username, nama, unit, email</code>. Sistem akan membuatkan password sementara dan mewajibkan ganti password pada saat login pertama.
        </p>

        <form method="POST" action="<?= e(url('/kelola_user.php')) ?>" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="import_csv">

            <div style="margin-bottom: 16px;">
                <label for="csv_file" style="display: block; font-weight: 600; color: #334155; margin-bottom: 6px; font-size: 0.875rem;">
                    Pilih Berkas CSV <span style="color: #ef4444;">*</span>
                </label>
                <input type="file" id="csv_file" name="csv_file" accept=".csv,text/csv" required
                       style="width: 100%; box-sizing: border-box; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 0.9rem;">
            </div>

            <button type="submit"
                    style="background: #2563eb; color: white; border: none; padding: 9px 18px; border-radius: 8px; font-weight: 600; font-size: 0.9rem; cursor: pointer;">
                Impor Akun Pegawai
            </button>
        </form>
    </div>

    <!-- Card 2: Undang Pengguna Baru via Email -->
    <div class="card" style="border-top: 4px solid #64748b;">
        <h2 style="font-size: 1.1rem; color: #1e293b; font-weight: 700; margin-bottom: 12px;">Undang Pengguna Tunggal</h2>
        <p style="color: #64748b; font-size: 0.85rem; margin-top: 0; margin-bottom: 16px;">
            Kirimkan email undangan aktivasi mandiri bagi staf atau pengguna eksternal portal.
        </p>

        <?php if (!empty($error)): ?>
            <div style="background: #fef2f2; border-left: 4px solid #ef4444; color: #991b1b; padding: 10px 14px; border-radius: 6px; margin-bottom: 14px; font-size: 0.85rem;">
                <?= e($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="<?= e(url('/kelola_user.php')) ?>">
            <?= csrf_field() ?>

            <div style="margin-bottom: 16px;">
                <label for="email" style="display: block; font-weight: 600; color: #334155; margin-bottom: 6px; font-size: 0.875rem;">
                    Alamat Email <span style="color: #ef4444;">*</span>
                </label>
                <input type="email" id="email" name="email" value="<?= e($email_value) ?>" required
                       placeholder="nama@bps.go.id"
                       style="width: 100%; box-sizing: border-box; padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 0.9rem;">
            </div>

            <button type="submit"
                    style="background: #475569; color: white; border: none; padding: 9px 18px; border-radius: 8px; font-weight: 600; font-size: 0.9rem; cursor: pointer;">
                Kirim Undangan Email
            </button>
        </form>
    </div>
</div>

<div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; flex-wrap: wrap; gap: 8px;">
        <h2 style="font-size: 1.1rem; color: #1e293b; font-weight: 700; margin: 0;">Daftar Pengguna Portal & Pegawai</h2>
        <span style="font-size: 0.85rem; color: #64748b;">
            Total: <?= count($users) ?> Akun Pegawai
        </span>
    </div>

    <div class="table-container" style="overflow-x: auto;">
        <table class="user-table" style="width: 100%; border-collapse: collapse; text-align: left; font-size: 0.9rem;">
            <thead>
                <tr style="border-bottom: 2px solid #e2e8f0; color: #475569; font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.03em;">
                    <th style="padding: 12px 14px; width: 40px;">No</th>
                    <th style="padding: 12px 14px;">Username</th>
                    <th style="padding: 12px 14px;">Nama Lengkap</th>
                    <th style="padding: 12px 14px;">Unit Kerja</th>
                    <th style="padding: 12px 14px;">Email</th>
                    <th style="padding: 12px 14px; text-align: center;">Ganti Password</th>
                    <th style="padding: 12px 14px; text-align: center;">Status</th>
                    <th style="padding: 12px 14px;">Tanggal Dibuat</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($users)): ?>
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 32px; color: #64748b;">
                            Belum ada akun pengguna portal atau pegawai yang terdaftar.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php $no = 1; foreach ($users as $u): ?>
                        <tr style="border-bottom: 1px solid #f1f5f9;">
                            <td style="padding: 12px 14px; color: #64748b; font-weight: 500;">
                                <?= $no++ ?>
                            </td>
                            <td style="padding: 12px 14px; font-weight: 600; color: #1e293b;">
                                <?= e($u['username'] ?? 'Belum Aktivasi') ?>
                            </td>
                            <td style="padding: 12px 14px; color: #334155;">
                                <?= e(trim(($u['first_name'] ?? '') . ' ' . ($u['last_name'] ?? '')) ?: '-') ?>
                            </td>
                            <td style="padding: 12px 14px; color: #64748b;">
                                <?= e($u['unit_name'] ?? '-') ?>
                            </td>
                            <td style="padding: 12px 14px; color: #64748b;">
                                <?= e($u['email']) ?>
                            </td>
                            <td style="padding: 12px 14px; text-align: center;">
                                <?php if (!empty($u['must_change_password'])): ?>
                                    <span style="display: inline-block; padding: 3px 8px; border-radius: 6px; font-size: 0.75rem; font-weight: 600; background: #fef9c3; color: #854d0e;">
                                        Wajib Ganti
                                    </span>
                                <?php else: ?>
                                    <span style="display: inline-block; padding: 3px 8px; border-radius: 6px; font-size: 0.75rem; font-weight: 600; background: #dcfce7; color: #15803d;">
                                        Sudah Aman
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 12px 14px; text-align: center;">
                                <span style="display: inline-block; padding: 3px 8px; border-radius: 6px; font-size: 0.75rem; font-weight: 600; background: <?= ($u['status'] === 'approved') ? '#dcfce7; color: #15803d;' : '#fef9c3; color: #854d0e;' ?>">
                                    <?= e(ucfirst($u['status'])) ?>
                                </span>
                            </td>
                            <td style="padding: 12px 14px; color: #64748b; font-size: 0.85rem;">
                                <?= e($u['created_at'] ?? '-') ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

import { describe, it, expect, beforeAll, afterAll } from 'vitest';
import { Window } from 'happy-dom';

const BASE_URL = 'http://localhost/Dashboard_Admin/public';

function parseHtml(html) {
  const window = new Window();
  window.document.documentElement.innerHTML = html;
  return window.document;
}

function createSession() {
  let cookie = '';
  return {
    async fetch(url, options = {}) {
      const headers = { ...(options.headers || {}) };
      if (cookie) {
        headers['Cookie'] = cookie;
      }
      const response = await fetch(url, { ...options, headers, redirect: 'manual' });
      const setCookies = typeof response.headers.getSetCookie === 'function'
        ? response.headers.getSetCookie()
        : [response.headers.get('set-cookie')].filter(Boolean);
      if (setCookies.length > 0) {
        cookie = setCookies[setCookies.length - 1].split(';')[0];
      }
      return response;
    },
    getCookie() {
      return cookie;
    }
  };
}

describe('Admin Kelola Layanan — Tambah & Edit Layanan Form Consistency', () => {
  let adminClient;
  let createdLayananId = null;

  beforeAll(async () => {
    adminClient = createSession();

    const adminPass = process.env.ADMIN_PASSWORD || '#1310SolokSelatan';
    const loginRes = await adminClient.fetch(`${BASE_URL}/login.php`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: `username=admin&password=${encodeURIComponent(adminPass)}`
    });
    expect(loginRes.status).toBe(302);
  });

  afterAll(async () => {
    if (createdLayananId) {
      const pageRes = await adminClient.fetch(`${BASE_URL}/layanan.php`);
      const doc = parseHtml(await pageRes.text());
      const csrf = doc.querySelector('input[name="csrf_token"]')?.value || '';
      await adminClient.fetch(`${BASE_URL}/hapus_layanan.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `id=${createdLayananId}&csrf_token=${encodeURIComponent(csrf)}`
      });
    }
  });

  it('1. Halaman layanan.php memiliki modal Tambah & Edit Layanan dengan struktur sama persis dengan repositori_portal.php', async () => {
    const res = await adminClient.fetch(`${BASE_URL}/layanan.php`);
    expect(res.status).toBe(200);
    const html = await res.text();
    const doc = parseHtml(html);

    // Modal Tambah Layanan
    const modalTambah = doc.getElementById('modalTambahLayanan');
    expect(modalTambah).not.toBeNull();
    const formTambah = modalTambah.querySelector('form');
    expect(formTambah).not.toBeNull();
    expect(formTambah.querySelector('#tambah_nama_layanan')).not.toBeNull();
    expect(formTambah.querySelector('#tambah_deskripsi_layanan')).not.toBeNull();
    expect(formTambah.querySelector('#tambah_url')).not.toBeNull();
    expect(formTambah.querySelector('#tambah_id_kategori')).not.toBeNull();
    expect(formTambah.querySelector('#tambah_akses')).not.toBeNull();
    expect(formTambah.querySelector('#tambah_vpn')).not.toBeNull();
    expect(formTambah.querySelector('#tambah_logo')).not.toBeNull();
    expect(formTambah.querySelector('.btn-tambah')?.textContent).toContain('Simpan Layanan');
    expect(formTambah.querySelector('.category-select')?.textContent).toContain('Batal');

    // Modal Edit Layanan
    const modalEdit = doc.getElementById('modalEditLayanan');
    expect(modalEdit).not.toBeNull();
    const formEdit = modalEdit.querySelector('form');
    expect(formEdit).not.toBeNull();
    expect(formEdit.querySelector('#edit_nama_layanan')).not.toBeNull();
    expect(formEdit.querySelector('#edit_deskripsi_layanan')).not.toBeNull();
    expect(formEdit.querySelector('#edit_url')).not.toBeNull();
    expect(formEdit.querySelector('#edit_id_kategori')).not.toBeNull();
    expect(formEdit.querySelector('#edit_akses')).not.toBeNull();
    expect(formEdit.querySelector('#edit_vpn')).not.toBeNull();
    expect(formEdit.querySelector('#edit_logo')).not.toBeNull();
    expect(formEdit.querySelector('.btn-tambah')?.textContent).toContain('Simpan Perubahan');
    expect(formEdit.querySelector('.category-select')?.textContent).toContain('Batal');

    // Action button Edit memuat data-* attributes
    const editBtns = doc.querySelectorAll('.action-btn.edit-btn');
    if (editBtns.length > 0) {
      const btn = editBtns[0];
      expect(btn.getAttribute('data-id')).not.toBeNull();
      expect(btn.getAttribute('data-nama')).not.toBeNull();
      expect(btn.getAttribute('data-kategori')).not.toBeNull();
      expect(btn.getAttribute('onclick')).toContain('openEditLayananModalFromBtn');
    }
  });

  it('2. Halaman tambah_layanan.php memiliki desain form, field, urutan, placeholder, dan tombol sama dengan repositori_portal.php', async () => {
    const res = await adminClient.fetch(`${BASE_URL}/tambah_layanan.php`);
    expect(res.status).toBe(200);
    const html = await res.text();
    const doc = parseHtml(html);

    const form = doc.getElementById('formTambahLayanan');
    expect(form).not.toBeNull();

    // Field urutan & placeholder
    const namaInput = form.querySelector('#nama_layanan');
    expect(namaInput).not.toBeNull();
    expect(namaInput.getAttribute('placeholder')).toBe('Contoh: SIPP - Portal Pelaporan Perusahaan');

    const descInput = form.querySelector('#deskripsi_layanan');
    expect(descInput).not.toBeNull();
    expect(descInput.getAttribute('placeholder')).toBe('Jelaskan fungsi dan tujuan layanan ini...');

    const urlInput = form.querySelector('#url');
    expect(urlInput).not.toBeNull();
    expect(urlInput.getAttribute('placeholder')).toBe('https://contoh.bps.go.id');

    const katSelect = form.querySelector('#id_kategori');
    expect(katSelect).not.toBeNull();
    const katOptions = Array.from(katSelect.querySelectorAll('option')).map(o => o.textContent.trim());
    expect(katOptions[0]).toBe('Pilih Kategori...');

    const aksesSelect = form.querySelector('#akses');
    expect(aksesSelect).not.toBeNull();
    const aksesOptions = Array.from(aksesSelect.querySelectorAll('option')).map(o => o.textContent.trim());
    expect(aksesOptions).toContain('Public (Umum)');
    expect(aksesOptions).toContain('Internal (Pegawai BPS)');
    expect(aksesOptions).toContain('Private');

    const vpnSelect = form.querySelector('#vpn');
    expect(vpnSelect).not.toBeNull();
    const vpnOptions = Array.from(vpnSelect.querySelectorAll('option')).map(o => o.textContent.trim());
    expect(vpnOptions).toContain('No (Tidak)');
    expect(vpnOptions).toContain('Yes (Ya)');

    const logoInput = form.querySelector('#logo');
    expect(logoInput).not.toBeNull();

    // Tombol Batal & Simpan
    const btnCancel = form.querySelector('.category-select');
    expect(btnCancel?.textContent.trim()).toBe('Batal');
    const btnSave = form.querySelector('.btn-tambah');
    expect(btnSave?.textContent.trim()).toBe('Simpan Layanan');

    // Warning alert box & validasi
    expect(doc.getElementById('formAlertBox')).not.toBeNull();
    expect(html).toContain('Harap lengkapi semua kolom yang wajib diisi (kecuali yang bertanda opsional)!');
  });

  it('3. Admin dapat menambah layanan baru via tambah_layanan.php', async () => {
    const postRes = await adminClient.fetch(`${BASE_URL}/tambah_layanan.php`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: new URLSearchParams({
        nama_layanan: 'Layanan Uji Admin Form',
        deskripsi_layanan: 'Deskripsi uji layanan admin form',
        url: 'https://admin-uji.bps.go.id',
        id_kategori: '1',
        akses: 'Internal',
        vpn: 'Yes'
      }).toString()
    });
    // Redirects to layanan.php on success
    expect(postRes.status).toBe(302);
    expect(postRes.headers.get('location')).toBe('layanan.php');

    // Cek di layanan.php dan ambil ID layanan yang baru dibuat
    const listRes = await adminClient.fetch(`${BASE_URL}/layanan.php?id_kategori=1`);
    const listHtml = await listRes.text();
    expect(listHtml).toContain('Layanan Uji Admin Form');

    const match = listHtml.match(/data-id="(\d+)"[^>]*data-nama="Layanan Uji Admin Form"/);
    expect(match).not.toBeNull();
    createdLayananId = parseInt(match[1], 10);
    expect(createdLayananId).toBeGreaterThan(0);
  });

  it('4. Halaman edit_layanan.php memuat data lama dan memiliki struktur form sama persis dengan repositori_portal.php', async () => {
    expect(createdLayananId).not.toBeNull();

    const res = await adminClient.fetch(`${BASE_URL}/edit_layanan.php?id=${createdLayananId}`);
    expect(res.status).toBe(200);
    const html = await res.text();
    const doc = parseHtml(html);

    const form = doc.getElementById('formEditLayanan');
    expect(form).not.toBeNull();

    // Memuat data lama yang sesuai
    const namaInput = form.querySelector('#nama_layanan');
    expect(namaInput.value).toBe('Layanan Uji Admin Form');

    const descInput = form.querySelector('#deskripsi_layanan');
    expect(descInput.value).toBe('Deskripsi uji layanan admin form');

    const urlInput = form.querySelector('#url');
    expect(urlInput.value).toBe('https://admin-uji.bps.go.id');

    const katSelect = form.querySelector('#id_kategori');
    expect(katSelect.value).toBe('1');

    const aksesSelect = form.querySelector('#akses');
    expect(aksesSelect.value).toBe('Internal');

    const vpnSelect = form.querySelector('#vpn');
    expect(vpnSelect.value).toBe('Yes');

    // Tombol Batal & Simpan Perubahan
    const btnCancel = form.querySelector('.category-select');
    expect(btnCancel?.textContent.trim()).toBe('Batal');
    const btnSave = form.querySelector('.btn-tambah');
    expect(btnSave?.textContent.trim()).toBe('Simpan Perubahan');

    // Warning alert box & validasi
    expect(doc.getElementById('formAlertBox')).not.toBeNull();
  });

  it('5. Admin dapat mengedit layanan dan perubahan tersimpan tanpa membuat data duplikat', async () => {
    expect(createdLayananId).not.toBeNull();

    // Hitung kemunculan nama layanan sebelum edit
    const beforeRes = await adminClient.fetch(`${BASE_URL}/layanan.php?id_kategori=1`);
    const beforeHtml = await beforeRes.text();
    const countBefore = (beforeHtml.match(/Layanan Uji Admin Form/g) || []).length;
    expect(countBefore).toBeGreaterThan(0);

    // Kirim edit data
    const editRes = await adminClient.fetch(`${BASE_URL}/edit_layanan.php?id=${createdLayananId}`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: new URLSearchParams({
        nama_layanan: 'Layanan Uji Admin Form (Telah Diedit)',
        deskripsi_layanan: 'Deskripsi berhasil diperbarui oleh admin',
        url: 'https://admin-uji-updated.bps.go.id',
        id_kategori: '2',
        akses: 'Public',
        vpn: 'No'
      }).toString()
    });
    expect(editRes.status).toBe(302);
    expect(editRes.headers.get('location')).toBe('layanan.php');

    // Cek di layanan.php bahwa data lama berubah dan tidak ada duplikasi
    const afterRes = await adminClient.fetch(`${BASE_URL}/layanan.php?id_kategori=2`);
    const afterHtml = await afterRes.text();
    expect(afterHtml).toContain('Layanan Uji Admin Form (Telah Diedit)');
    expect(afterHtml).not.toContain('Layanan Uji Admin Form"');

    // Pastikan ID tetap sama
    const match = afterHtml.match(/data-id="(\d+)"[^>]*data-nama="Layanan Uji Admin Form \(Telah Diedit\)"/);
    expect(match).not.toBeNull();
    expect(parseInt(match[1], 10)).toBe(createdLayananId);
  });
});

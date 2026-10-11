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

function extractCsrfToken(doc) {
  const input = doc.querySelector('input[name="csrf_token"]');
  return input ? input.value : '';
}

describe('Kelola Admin — Aksi Nonaktifkan, Aktifkan Kembali, dan Hapus Admin', () => {
  let adminClient;
  let userClient;
  let testAdminId = null;

  beforeAll(async () => {
    adminClient = createSession();
    userClient = createSession();

    // Login as Admin Utama (admin)
    const adminPass = process.env.ADMIN_PASSWORD || '#1310SolokSelatan';
    const adminLogin = await adminClient.fetch(`${BASE_URL}/login.php`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: `username=admin&password=${encodeURIComponent(adminPass)}`
    });
    expect(adminLogin.status).toBe(302);

    // Login as Regular User (Luce)
    const userLogin = await userClient.fetch(`${BASE_URL}/login.php`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: 'username=Luce&password=user123'
    });
    expect(userLogin.status).toBe(302);
  });

  afterAll(async () => {
    if (testAdminId) {
      // Cleanup test admin if still exists
      const pageRes = await adminClient.fetch(`${BASE_URL}/kelola_admin.php`);
      const doc = parseHtml(await pageRes.text());
      const csrf = extractCsrfToken(doc);
      await adminClient.fetch(`${BASE_URL}/kelola_admin.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
          csrf_token: csrf,
          action: 'delete_admin',
          id_admin: String(testAdminId)
        }).toString()
      });
    }
  });

  it('1. Halaman kelola_admin.php menampilkan tabel daftar admin dengan kolom Aksi dan Status', async () => {
    const res = await adminClient.fetch(`${BASE_URL}/kelola_admin.php`);
    expect(res.status).toBe(200);

    const doc = parseHtml(await res.text());
    const table = doc.querySelector('table');
    expect(table).not.toBeNull();

    const headers = Array.from(table.querySelectorAll('th')).map(th => th.textContent.trim());
    expect(headers).toContain('No');
    expect(headers).toContain('Username');
    expect(headers).toContain('Email');
    expect(headers).toContain('Status');
    expect(headers).toContain('Aksi');

    // Admin utama dilindungi
    const protectedBadge = doc.querySelector('.action-protected-badge');
    expect(protectedBadge).not.toBeNull();
    expect(protectedBadge?.textContent).toContain('Dilindungi');

    // Sesi aktif admin sendiri dilindungi
    const selfBadge = doc.querySelector('.action-self-badge');
    expect(selfBadge).not.toBeNull();
    expect(selfBadge?.textContent).toContain('Sesi Aktif');
  });

  it('2. Admin dapat membuat admin baru untuk pengujian', async () => {
    // Daftarkan admin via form email
    const pageRes = await adminClient.fetch(`${BASE_URL}/kelola_admin.php`);
    const doc = parseHtml(await pageRes.text());
    const csrf = extractCsrfToken(doc);

    const testEmail = `testadmin_${Date.now()}@bps1310.cloud`;
    const regRes = await adminClient.fetch(`${BASE_URL}/kelola_admin.php`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: new URLSearchParams({
        csrf_token: csrf,
        email: testEmail
      }).toString()
    });
    expect(regRes.status).toBe(200);

    // Ambil akun yang baru dibuat dari tabel
    const afterRes = await adminClient.fetch(`${BASE_URL}/kelola_admin.php`);
    const afterDoc = parseHtml(await afterRes.text());
    const table = afterDoc.querySelector('table');
    const rows = Array.from(table.querySelectorAll('tbody tr'));
    
    const targetRow = rows.find(r => r.textContent.includes(testEmail));
    expect(targetRow).not.toBeUndefined();

    // Dapatkan ID admin dari hidden input
    const idInput = targetRow.querySelector('input[name="id_admin"]');
    expect(idInput).not.toBeNull();
    testAdminId = parseInt(idInput.value, 10);
    expect(testAdminId).toBeGreaterThan(0);
  }, 30000);

  it('3. Admin dapat menonaktifkan akun admin lain, status berubah menjadi Nonaktif', async () => {
    expect(testAdminId).not.toBeNull();

    // Dapatkan CSRF token segar
    const pageRes = await adminClient.fetch(`${BASE_URL}/kelola_admin.php`);
    const doc = parseHtml(await pageRes.text());
    const csrf = extractCsrfToken(doc);

    // Kirim aksi deactivate
    const deactRes = await adminClient.fetch(`${BASE_URL}/kelola_admin.php`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: new URLSearchParams({
        csrf_token: csrf,
        action: 'deactivate',
        id_admin: String(testAdminId)
      }).toString()
    });
    expect(deactRes.status).toBe(200);
    const html = await deactRes.text();
    expect(html).toContain('berhasil dinonaktifkan');

    // Cek bahwa status di tabel berubah dan tombol menjadi Aktifkan
    const checkDoc = parseHtml(html);
    const rows = Array.from(checkDoc.querySelectorAll('tbody tr'));
    const targetRow = rows.find(r => r.querySelector(`input[name="id_admin"][value="${testAdminId}"]`));
    expect(targetRow).not.toBeUndefined();

    const statusBadge = targetRow.querySelector('.badge-inactive');
    expect(statusBadge).not.toBeNull();
    expect(statusBadge?.textContent).toContain('Nonaktif');

    const activateBtn = targetRow.querySelector('button.btn-activate');
    expect(activateBtn).not.toBeNull();
    expect(activateBtn?.textContent).toContain('Aktifkan');
  });

  it('4. Admin dapat mengaktifkan kembali akun admin yang dinonaktifkan', async () => {
    expect(testAdminId).not.toBeNull();

    // Dapatkan CSRF token segar
    const pageRes = await adminClient.fetch(`${BASE_URL}/kelola_admin.php`);
    const doc = parseHtml(await pageRes.text());
    const csrf = extractCsrfToken(doc);

    // Kirim aksi activate
    const actRes = await adminClient.fetch(`${BASE_URL}/kelola_admin.php`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: new URLSearchParams({
        csrf_token: csrf,
        action: 'activate',
        id_admin: String(testAdminId)
      }).toString()
    });
    expect(actRes.status).toBe(200);
    const html = await actRes.text();
    expect(html).toContain('berhasil diaktifkan kembali');

    // Cek bahwa status di tabel kembali menjadi Aktif dan tombol menjadi Nonaktifkan
    const checkDoc = parseHtml(html);
    const rows = Array.from(checkDoc.querySelectorAll('tbody tr'));
    const targetRow = rows.find(r => r.querySelector(`input[name="id_admin"][value="${testAdminId}"]`));
    expect(targetRow).not.toBeUndefined();

    const statusBadge = targetRow.querySelector('.badge-approved');
    expect(statusBadge).not.toBeNull();
    expect(statusBadge?.textContent).toContain('Aktif');

    const deactBtn = targetRow.querySelector('button.btn-deactivate');
    expect(deactBtn).not.toBeNull();
    expect(deactBtn?.textContent).toContain('Nonaktifkan');
  });

  it('5. Keamanan: Admin tidak dapat menonaktifkan atau menghapus akun sendiri dan Admin Utama', async () => {
    const pageRes = await adminClient.fetch(`${BASE_URL}/kelola_admin.php`);
    const doc = parseHtml(await pageRes.text());
    const csrf = extractCsrfToken(doc);

    // Coba nonaktifkan akun sendiri (ID 1)
    const selfRes = await adminClient.fetch(`${BASE_URL}/kelola_admin.php`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: new URLSearchParams({
        csrf_token: csrf,
        action: 'deactivate',
        id_admin: '1'
      }).toString()
    });
    const selfHtml = await selfRes.text();
    expect(selfHtml).toContain('Anda tidak dapat menonaktifkan akun Anda sendiri');

    // Coba hapus akun sendiri (ID 1)
    const delSelfRes = await adminClient.fetch(`${BASE_URL}/kelola_admin.php`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: new URLSearchParams({
        csrf_token: csrf,
        action: 'delete_admin',
        id_admin: '1'
      }).toString()
    });
    const delSelfHtml = await delSelfRes.text();
    expect(delSelfHtml).toContain('Anda tidak dapat menghapus akun Anda sendiri');
  }, 30000);

  it('6. Keamanan: Role USER biasa tidak memiliki izin mengakses kelola_admin.php', async () => {
    const res = await userClient.fetch(`${BASE_URL}/kelola_admin.php`);
    // Redirect ke index.php karena role USER tidak berhak
    expect(res.status).toBe(302);
    expect(res.headers.get('location')).toBe('index.php');
  });

  it('7. Admin dapat menghapus akun admin lain dengan aman', async () => {
    expect(testAdminId).not.toBeNull();

    const pageRes = await adminClient.fetch(`${BASE_URL}/kelola_admin.php`);
    const doc = parseHtml(await pageRes.text());
    const csrf = extractCsrfToken(doc);

    const delRes = await adminClient.fetch(`${BASE_URL}/kelola_admin.php`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: new URLSearchParams({
        csrf_token: csrf,
        action: 'delete_admin',
        id_admin: String(testAdminId)
      }).toString()
    });
    expect(delRes.status).toBe(200);
    const html = await delRes.text();
    expect(html).toContain('berhasil dihapus dari sistem');

    // Pastikan akun sudah tidak ada di tabel
    const checkDoc = parseHtml(html);
    const targetInput = checkDoc.querySelector(`input[name="id_admin"][value="${testAdminId}"]`);
    expect(targetInput).toBeNull();

    testAdminId = null;
  });
});

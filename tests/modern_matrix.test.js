import { describe, it, expect, beforeAll, afterAll } from 'vitest';
import { Window } from 'happy-dom';
import { execSync } from 'child_process';

const BASE_URL = 'http://localhost/Dashboard_Admin/public';
const API_KEY = 'Hashtag_1310SolokSelatan_PortalInternal_dan_DashboardAdmin_V1_BPSSolokSelatan_KotoTinggi';

function parseHtml(html) {
  const window = new Window();
  window.document.documentElement.innerHTML = html;
  return window.document;
}

function createSession() {
  const cookieJar = new Map();
  return {
    async fetch(url, options = {}) {
      const headers = { ...(options.headers || {}) };
      if (cookieJar.size > 0) {
        headers['Cookie'] = Array.from(cookieJar.entries()).map(([k, v]) => `${k}=${v}`).join('; ');
      }
      const response = await fetch(url, { ...options, headers, redirect: 'manual' });
      const setCookies = typeof response.headers.getSetCookie === 'function'
        ? response.headers.getSetCookie()
        : [response.headers.get('set-cookie')].filter(Boolean);
      for (const raw of setCookies) {
        if (!raw) continue;
        const first = raw.split(';')[0];
        const eqIdx = first.indexOf('=');
        if (eqIdx !== -1) {
          cookieJar.set(first.substring(0, eqIdx).trim(), first.substring(eqIdx + 1).trim());
        }
      }
      return response;
    },
    getCookie() {
      return Array.from(cookieJar.entries()).map(([k, v]) => `${k}=${v}`).join('; ');
    }
  };
}

async function loginAs(client, username, password) {
  const pageRes = await client.fetch(`${BASE_URL}/login.php`);
  const match = (await pageRes.text()).match(/name="csrf_token"\s+value="([^"]+)"/);
  const csrfToken = match ? match[1] : '';

  return client.fetch(`${BASE_URL}/login.php`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: `username=${encodeURIComponent(username)}&password=${encodeURIComponent(password)}&csrf_token=${encodeURIComponent(csrfToken)}`
  });
}

function extractCsrfToken(doc) {
  const input = doc.querySelector('input[name="csrf_token"]');
  return input ? input.value : '';
}

describe('Matriks Pengujian Modern — App A & App B Integration (Section 10)', () => {
  let adminClient;
  let userClient;

  beforeAll(async () => {
    adminClient = createSession();
    userClient = createSession();

    // Login sebagai Admin Utama
    const adminPass = process.env.ADMIN_PASSWORD || '#1310SolokSelatan';
    const adminLogin = await loginAs(adminClient, 'admin', adminPass);
    expect(adminLogin.status).toBe(302);

    // Login sebagai Regular User (Luce)
    const userLogin = await loginAs(userClient, 'Luce', 'user123');
    expect(userLogin.status).toBe(302);
  });

  /* =====================================================
     1. PENGUJIAN TAKSONOMI DAN RELASI KONSISTEN (APP B)
  ===================================================== */
  describe('1. Taksonomi dan Relasi Kategori-Layanan', () => {
    it('setiap layanan pada /api/layanan.php merujuk ke kategori valid di /api/kategori.php', async () => {
      // Ambil daftar kategori
      const catRes = await fetch(`${BASE_URL}/api/kategori.php`, {
        headers: { 'X-API-KEY': API_KEY }
      });
      expect(catRes.status).toBe(200);
      const catJson = await catRes.json();
      expect(catJson.status).toBe(true);
      const validCategoryIds = new Set(catJson.data.map(c => Number(c.id_kategori)));

      // Ambil katalog layanan
      const layRes = await fetch(`${BASE_URL}/api/layanan.php`, {
        headers: { 'X-API-KEY': API_KEY }
      });
      expect(layRes.status).toBe(200);
      const layJson = await layRes.json();
      expect(layJson.status).toBe(true);

      // Pastikan seluruh id_kategori ada dalam himpunan kategori valid
      for (const item of layJson.data) {
        expect(validCategoryIds.has(Number(item.id_kategori))).toBe(true);
        expect(item.nama_kategori).toBeTruthy();
        expect(['Public', 'Internal']).toContain(item.akses);
      }
    });

    it('filter parameter ?id_kategori=... pada API mengembalikan hanya layanan kategori bersangkutan', async () => {
      const targetCatId = 1;
      const res = await fetch(`${BASE_URL}/api/layanan.php?id_kategori=${targetCatId}`, {
        headers: { 'X-API-KEY': API_KEY }
      });
      expect(res.status).toBe(200);
      const json = await res.json();
      expect(json.status).toBe(true);
      for (const item of json.data) {
        expect(Number(item.id_kategori)).toBe(targetCatId);
      }
    });
  });

  /* =====================================================
     2. SIKLUS HIDUP AKUN USER LENGKAP (END-TO-END)
  ===================================================== */
  describe('2. Siklus Hidup Akun Pengguna (Registration -> Verify -> Approve -> Login)', () => {
    let createdUserId = null;
    const testUsername = `user_e2e_${Date.now()}`;
    const testEmail = `${testUsername}@bps1310.cloud`;
    const testPassword = 'Password123#';

    afterAll(async () => {
      if (createdUserId) {
        // Hapus akun test langsung melalui kueri aman
        execSync('php', {
          input: `<?php require 'config/config.php'; \$conn->query("DELETE FROM admin WHERE id_admin = ${createdUserId}");`
        });
      }
    });

    it('tahap 1: registrasi mandiri via register.php berhasil menyimpan akun status pending & role USER', async () => {
      const guestClient = createSession();
      const regPageRes = await guestClient.fetch(`${BASE_URL}/register.php`);
      const regDoc = parseHtml(await regPageRes.text());
      const csrf = extractCsrfToken(regDoc);

      const regRes = await guestClient.fetch(`${BASE_URL}/register.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
          csrf_token: csrf,
          first_name: 'Test',
          last_name: 'E2E',
          username: testUsername,
          email: testEmail,
          password: testPassword,
          confirm_password: testPassword,
          agree_to_terms: '1'
        }).toString()
      });
      const regText = await regRes.text();
      console.log('REG_TEXT:', regText.substring(0, 300));

      // Ambil ID dan verification_token dari database
      const idOutput = execSync('php', {
        input: `<?php require 'config/config.php'; echo \$conn->query("SELECT id_admin FROM admin WHERE email = '${testEmail}'")->fetchColumn();`
      }).toString().trim();
      console.log('ID_OUTPUT:', idOutput);
      createdUserId = parseInt(idOutput, 10);
      expect(createdUserId).toBeGreaterThan(0);
    }, 90000);

    it('tahap 2: verifikasi token email melalui verify_email.php berhasil mengaktifkan status verifikasi email', async () => {
      const token = execSync('php', {
        input: `<?php require 'config/config.php'; echo \$conn->query("SELECT verification_token FROM admin WHERE id_admin = ${createdUserId}")->fetchColumn();`
      }).toString().trim();
      expect(token).toBeTruthy();

      const verifyRes = await fetch(`${BASE_URL}/verify_email.php?token=${encodeURIComponent(token)}`);
      expect(verifyRes.status).toBe(200);
      const verifyHtml = await verifyRes.text();
      expect(verifyHtml).toContain('berhasil diverifikasi');
    });

    it('tahap 3: login sebelum persetujuan admin ditolak dengan pesan menunggu persetujuan', async () => {
      const loginClient = createSession();
      const loginRes = await loginAs(loginClient, testUsername, testPassword);
      const loginHtml = await loginRes.text();
      expect(loginHtml).toContain('Akun Anda masih menunggu persetujuan administrator');
    });

    it('tahap 4: administrator menyetujui akun melalui kelola_user.php', async () => {
      const pageRes = await adminClient.fetch(`${BASE_URL}/kelola_user.php`);
      const doc = parseHtml(await pageRes.text());
      const csrf = extractCsrfToken(doc);

      const approveRes = await adminClient.fetch(`${BASE_URL}/kelola_user.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
          csrf_token: csrf,
          action: 'approve',
          id_admin: String(createdUserId)
        }).toString()
      });
      expect(approveRes.status).toBe(200);
      const approveHtml = await approveRes.text();
      const approveDoc = parseHtml(approveHtml);
      const alertText = approveDoc.querySelector('.alert')?.textContent?.trim();
      console.log('APPROVE_ALERT:', alertText, 'CREATED_USER_ID:', createdUserId);
      expect(alertText).toContain('Akun berhasil disetujui');
    });

    it('tahap 5: user yang telah disetujui berhasil login dan dialihkan ke index.php', async () => {
      const newClient = createSession();
      const loginRes = await loginAs(newClient, testUsername, testPassword);
      expect(loginRes.status).toBe(302);
      expect(loginRes.headers.get('location')).toBe('index.php');

      // Akses index.php sebagai USER yang baru
      const indexRes = await newClient.fetch(`${BASE_URL}/index.php`);
      expect(indexRes.status).toBe(200);
      const indexDoc = parseHtml(await indexRes.text());
      expect(indexDoc.querySelector('.header-left h1')?.textContent).toContain('Dashboard User');
    });
  });

  /* =====================================================
     3. SIKLUS HIDUP KATALOG LAYANAN (E2E APP A -> APP B)
  ===================================================== */
  describe('3. Siklus Hidup Katalog Layanan (Tambah -> Refleksi App B -> Update -> Hapus Aman)', () => {
    let testLayananId = null;
    const initialName = `Layanan Matrix Test ${Date.now()}`;
    const updatedName = `${initialName} (Updated)`;

    afterAll(async () => {
      if (testLayananId) {
        const pageRes = await adminClient.fetch(`${BASE_URL}/layanan.php`);
        const doc = parseHtml(await pageRes.text());
        const csrf = extractCsrfToken(doc);
        await adminClient.fetch(`${BASE_URL}/hapus_layanan.php`, {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: `id=${testLayananId}&csrf_token=${encodeURIComponent(csrf)}`
        });
      }
    });

    it('tahap 1: penambahan layanan via tambah_layanan.php tersimpan di database', async () => {
      const formRes = await adminClient.fetch(`${BASE_URL}/tambah_layanan.php`);
      const formDoc = parseHtml(await formRes.text());
      const csrf = extractCsrfToken(formDoc);

      const addRes = await adminClient.fetch(`${BASE_URL}/tambah_layanan.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
          csrf_token: csrf,
          nama_layanan: initialName,
          deskripsi_layanan: 'Layanan uji integrasi App A dan App B',
          url: 'https://matrix-test.bps.go.id',
          id_kategori: '1',
          akses: 'Public',
          vpn: 'No'
        }).toString()
      });
      expect(addRes.status).toBe(302);
      expect(addRes.headers.get('location')).toBe('layanan.php');

      // Ambil ID dari database
      const idOutput = execSync('php', {
        input: `<?php require 'config/config.php'; echo \$conn->query("SELECT id_layanan FROM layanan WHERE nama_layanan = '${initialName}'")->fetchColumn();`
      }).toString().trim();
      testLayananId = parseInt(idOutput, 10);
      expect(testLayananId).toBeGreaterThan(0);
    });

    it('tahap 2: layanan baru langsung terrefleksi pada API App B (/api/layanan.php)', async () => {
      const apiRes = await fetch(`${BASE_URL}/api/layanan.php`, {
        headers: { 'X-API-KEY': API_KEY }
      });
      expect(apiRes.status).toBe(200);
      const json = await apiRes.json();
      const found = json.data.find(item => item.id_layanan == testLayananId);
      expect(found).not.toBeUndefined();
      expect(found.nama_layanan).toBe(initialName);
      expect(found.akses).toBe('Public');
    });

    it('tahap 3: pembaruan layanan via edit_layanan.php langsung terrefleksi pada API App B', async () => {
      const formRes = await adminClient.fetch(`${BASE_URL}/edit_layanan.php?id=${testLayananId}`);
      const formDoc = parseHtml(await formRes.text());
      const csrf = extractCsrfToken(formDoc);

      const editRes = await adminClient.fetch(`${BASE_URL}/edit_layanan.php?id=${testLayananId}`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
          csrf_token: csrf,
          nama_layanan: updatedName,
          deskripsi_layanan: 'Deskripsi berhasil diperbarui',
          url: 'https://matrix-test-updated.bps.go.id',
          id_kategori: '2',
          akses: 'Internal',
          vpn: 'Yes'
        }).toString()
      });
      expect(editRes.status).toBe(302);

      // Verifikasi di API App B
      const apiRes = await fetch(`${BASE_URL}/api/layanan.php`, {
        headers: { 'X-API-KEY': API_KEY }
      });
      const json = await apiRes.json();
      const found = json.data.find(item => item.id_layanan == testLayananId);
      expect(found).not.toBeUndefined();
      expect(found.nama_layanan).toBe(updatedName);
      expect(found.akses).toBe('Internal');
      expect(found.vpn).toBe('Yes');
    });

    it('tahap 4: penghapusan aman via POST hapus_layanan.php membersihkan data dari API App B', async () => {
      const pageRes = await adminClient.fetch(`${BASE_URL}/layanan.php`);
      const doc = parseHtml(await pageRes.text());
      const csrf = extractCsrfToken(doc);

      const delRes = await adminClient.fetch(`${BASE_URL}/hapus_layanan.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `id=${testLayananId}&csrf_token=${encodeURIComponent(csrf)}`
      });
      expect(delRes.status).toBe(302);
      expect(delRes.headers.get('location')).toContain('layanan.php');

      // Verifikasi hilang dari API App B
      const apiRes = await fetch(`${BASE_URL}/api/layanan.php`, {
        headers: { 'X-API-KEY': API_KEY }
      });
      const json = await apiRes.json();
      const found = json.data.find(item => item.id_layanan == testLayananId);
      expect(found).toBeUndefined();

      testLayananId = null;
    });
  });

  /* =====================================================
     4. NAVIGASI UI SMOKE TEST TAHAN BANTING (SEMANTIK)
  ===================================================== */
  describe('4. Navigasi UI Semantik Berbasis Peran (Smoke Test)', () => {
    it('Admin melihat seluruh menu administratif (Layanan, Kategori, Kelola User, Kelola Admin, Pengaturan)', async () => {
      const res = await adminClient.fetch(`${BASE_URL}/index.php`);
      expect(res.status).toBe(200);
      const doc = parseHtml(await res.text());

      // Pengecekan semantik berbasis href tautan
      expect(doc.querySelector('a[href*="layanan.php"]')).not.toBeNull();
      expect(doc.querySelector('a[href*="kategori.php"]')).not.toBeNull();
      expect(doc.querySelector('a[href*="kelola_user.php"]')).not.toBeNull();
      expect(doc.querySelector('a[href*="kelola_admin.php"]')).not.toBeNull();
      expect(doc.querySelector('a[href*="pengaturan.php"]')).not.toBeNull();
    });

    it('User melihat menu operasional dan TIDAK melihat menu kelola admin maupun kelola user', async () => {
      const res = await userClient.fetch(`${BASE_URL}/index.php`);
      expect(res.status).toBe(200);
      const doc = parseHtml(await res.text());

      // Menu operasional yang harus ada untuk USER
      expect(doc.querySelector('a[href*="layanan.php"]')).not.toBeNull();
      expect(doc.querySelector('a[href*="kategori.php"]')).not.toBeNull();
      expect(doc.querySelector('a[href*="pengaturan.php"]')).not.toBeNull();

      // Menu administratif HARUS TIDAK ADA untuk USER
      expect(doc.querySelector('a[href*="kelola_user.php"]')).toBeNull();
      expect(doc.querySelector('a[href*="kelola_admin.php"]')).toBeNull();
    });
  });
});

import { describe, it, expect, beforeAll, afterAll } from 'vitest';
import { execSync } from 'node:child_process';

const BASE_URL = 'http://localhost/Dashboard_Admin/public';
const API_KEY = 'Hashtag_1310SolokSelatan_PortalInternal_dan_DashboardAdmin_V1_BPSSolokSelatan_KotoTinggi';

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

describe('App B API Contract & RBAC Architecture Tests', () => {
  describe('1. Endpoint /api/layanan.php Contract', () => {
    it('menolak permintaan tanpa header X-API-KEY dengan HTTP 401', async () => {
      const res = await fetch(`${BASE_URL}/api/layanan.php`);
      expect(res.status).toBe(401);
      const json = await res.json();
      expect(json.status).toBe(false);
      expect(json.message).toContain('API key wajib dikirim');
    });

    it('menolak permintaan dengan header X-API-KEY yang salah dengan HTTP 401', async () => {
      const res = await fetch(`${BASE_URL}/api/layanan.php`, {
        headers: { 'X-API-KEY': 'KEY_PALSU_TIDAK_VALID' }
      });
      expect(res.status).toBe(401);
      const json = await res.json();
      expect(json.status).toBe(false);
    });

    it('mengembalikan array layanan berstatus 200 dengan struktur yang diharapkan App B', async () => {
      const res = await fetch(`${BASE_URL}/api/layanan.php`, {
        headers: { 'X-API-KEY': API_KEY }
      });
      expect(res.status).toBe(200);
      const json = await res.json();
      expect(json.status).toBe(true);
      expect(Array.isArray(json.data)).toBe(true);
      expect(json.data.length).toBeGreaterThan(0);

      // Verifikasi kontrak properti yang dibutuhkan App B (api.ts -> LayananItem)
      const firstItem = json.data[0];
      expect(firstItem).toHaveProperty('id_layanan');
      expect(firstItem).toHaveProperty('id_kategori');
      expect(firstItem).toHaveProperty('nama_layanan');
      expect(firstItem).toHaveProperty('url');
      expect(firstItem).toHaveProperty('akses');
      expect(firstItem).toHaveProperty('vpn');
      expect(firstItem).toHaveProperty('nama_kategori');
    });

    it('tidak pernah membocorkan layanan privat (Private) ke publik', async () => {
      const res = await fetch(`${BASE_URL}/api/layanan.php`, {
        headers: { 'X-API-KEY': API_KEY }
      });
      const json = await res.json();
      const privateItems = json.data.filter(item => item.akses === 'Private');
      expect(privateItems.length).toBe(0);
    });
  });

  describe('2. Endpoint /api/kategori.php Contract', () => {
    it('menolak permintaan tanpa API key', async () => {
      const res = await fetch(`${BASE_URL}/api/kategori.php`);
      expect(res.status).toBe(401);
    });

    it('mengembalikan daftar kategori resmi dengan status 200', async () => {
      const res = await fetch(`${BASE_URL}/api/kategori.php`, {
        headers: { 'X-API-KEY': API_KEY }
      });
      expect(res.status).toBe(200);
      const json = await res.json();
      expect(json.status).toBe(true);
      expect(Array.isArray(json.data)).toBe(true);
      expect(json.data.length).toBeGreaterThanOrEqual(6);

      const firstCat = json.data[0];
      expect(firstCat).toHaveProperty('id_kategori');
      expect(firstCat).toHaveProperty('nama_kategori');
    });
  });

  describe('3. Endpoint /api/auth/loginPortal.php Contract', () => {
    it('menolak login tanpa X-API-KEY dengan HTTP 401', async () => {
      const res = await fetch(`${BASE_URL}/api/auth/loginPortal.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ identity: 'admin', password: 'password' })
      });
      expect(res.status).toBe(401);
    });

    it('menolak login dengan password salah dengan HTTP 401', async () => {
      const res = await fetch(`${BASE_URL}/api/auth/loginPortal.php`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-API-KEY': API_KEY
        },
        body: JSON.stringify({ identity: 'admin', password: 'password_salah_12345' })
      });
      expect(res.status).toBe(401);
      const json = await res.json();
      expect(json.status).toBe(false);
      expect(json.message).toContain('Password salah');
    });

    it('berhasil login untuk akun aktif dan mengembalikan format AdminUserData untuk App B', async () => {
      const res = await fetch(`${BASE_URL}/api/auth/loginPortal.php`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-API-KEY': API_KEY
        },
        body: JSON.stringify({ identity: 'admin', password: '#1310SolokSelatan' })
      });
      expect(res.status).toBe(200);
      const json = await res.json();
      expect(json.status).toBe(true);
      expect(json.data).not.toBeNull();
      expect(json.data).toHaveProperty('id_admin');
      expect(json.data).toHaveProperty('username');
      expect(json.data).toHaveProperty('email');
      expect(json.data).toHaveProperty('role');
    });
  });

  describe('4. RBAC & Akses Keamanan Web Dashboard', () => {
    it('pengunjung anonim (tanpa login) dialihkan ke login.php saat mengakses index.php', async () => {
      const client = createSession();
      const res = await client.fetch(`${BASE_URL}/index.php`);
      expect(res.status).toBe(302);
      expect(res.headers.get('location')).toContain('login.php');
    });

    it('role USER diblokir keras saat mengakses kelola_user.php dan dialihkan ke index.php', async () => {
      const userClient = createSession();
      // Login sebagai user Luce
      const loginRes = await userClient.fetch(`${BASE_URL}/login.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'username=Luce&password=user123'
      });
      expect(loginRes.status).toBe(302);

      const res = await userClient.fetch(`${BASE_URL}/kelola_user.php`);
      expect(res.status).toBe(302);
      expect(res.headers.get('location')).toContain('index.php');
    });

    it('role USER diblokir keras saat mengakses kelola_admin.php dan dialihkan ke index.php', async () => {
      const userClient = createSession();
      const loginRes = await userClient.fetch(`${BASE_URL}/login.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'username=Luce&password=user123'
      });
      expect(loginRes.status).toBe(302);

      const res = await userClient.fetch(`${BASE_URL}/kelola_admin.php`);
      expect(res.status).toBe(302);
      expect(res.headers.get('location')).toContain('index.php');
    });
  });

  describe('5. Pengamanan Mutasi Data & Proteksi CSRF (Section 9)', () => {
    let adminClient;

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

    it('hapus_layanan.php via POST tanpa token CSRF ditolak dengan HTTP 403', async () => {
      const res = await adminClient.fetch(`${BASE_URL}/hapus_layanan.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'id=999999'
      });
      expect(res.status).toBe(403);
    });

    it('hapus_kategori.php via POST tanpa token CSRF ditolak dengan HTTP 403', async () => {
      const res = await adminClient.fetch(`${BASE_URL}/hapus_kategori.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'id=999999'
      });
      expect(res.status).toBe(403);
    });

    it('hapus_layanan.php via GET tanpa CSRF token menampilkan halaman konfirmasi aman dengan token CSRF', async () => {
      const res = await adminClient.fetch(`${BASE_URL}/hapus_layanan.php?id=1`);
      expect(res.status).toBe(200);
      const html = await res.text();
      expect(html).toContain('Konfirmasi Hapus Layanan');
      expect(html).toContain('name="csrf_token"');
      expect(html).toContain('action="hapus_layanan.php"');
    });

    it('hapus_kategori.php via GET tanpa CSRF token menampilkan halaman konfirmasi aman dengan token CSRF', async () => {
      const res = await adminClient.fetch(`${BASE_URL}/hapus_kategori.php?id=1`);
      expect(res.status).toBe(200);
      const html = await res.text();
      expect(html).toContain('Konfirmasi Hapus Kategori');
      expect(html).toContain('name="csrf_token"');
      expect(html).toContain('action="hapus_kategori.php"');
    });

    it('api/user_repo.php menolak akses tanpa otentikasi login dengan status 401', async () => {
      const anonClient = createSession();
      const res = await anonClient.fetch(`${BASE_URL}/api/user_repo.php`);
      expect(res.status).toBe(401);
      const json = await res.json();
      expect(json.success).toBe(false);
    });

    it('api/user_repo.php menerima request server-to-server App B dengan X-API-KEY dan X-User-Id', async () => {
      // User id 1 adalah admin default aktif
      const res = await fetch(`${BASE_URL}/api/user_repo.php?action=get_all`, {
        headers: {
          'X-API-KEY': API_KEY,
          'X-User-Id': '1'
        }
      });
      expect(res.status).toBe(200);
      const json = await res.json();
      expect(json.success).toBe(true);
      expect(json.data).toHaveProperty('counts');
      expect(json.data).toHaveProperty('favorites');
      expect(json.data).toHaveProperty('links');
      expect(json.data).toHaveProperty('data_links');
      expect(json.data).toHaveProperty('mobile_apps');
      expect(json.data).toHaveProperty('drive_links');
      expect(json.data).toHaveProperty('photo_links');
      expect(json.data).toHaveProperty('other_links');
    });

    it('api/user_repo.php menolak request dengan X-API-KEY palsu dengan status 401', async () => {
      const res = await fetch(`${BASE_URL}/api/user_repo.php?action=get_all`, {
        headers: {
          'X-API-KEY': 'WRONG_API_KEY_123',
          'X-User-Id': '1'
        }
      });
      expect(res.status).toBe(401);
      const json = await res.json();
      expect(json.success).toBe(false);
    });
  });

  describe('6. Endpoint /api/auth/registerPortal.php Contract', () => {
    let testRegisteredId = null;
    const testRegUser = `reg_portal_${Date.now()}`;
    const testRegEmail = `${testRegUser}@bps.go.id`;

    afterAll(() => {
      if (testRegisteredId) {
        try {
          execSync('php', {
            input: `<?php require 'config/config.php'; \$conn->query("DELETE FROM admin WHERE id_admin = ${testRegisteredId}");`
          });
        } catch {}
      }
    });

    it('menolak permintaan registrasi tanpa header X-API-KEY dengan HTTP 401', async () => {
      const res = await fetch(`${BASE_URL}/api/auth/registerPortal.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          first_name: 'Test',
          username: testRegUser,
          email: testRegEmail,
          password: 'Password123#'
        })
      });
      expect(res.status).toBe(401);
      const json = await res.json();
      expect(json.status).toBe(false);
    });

    it('menolak payload tidak lengkap dengan HTTP 400', async () => {
      const res = await fetch(`${BASE_URL}/api/auth/registerPortal.php`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-API-KEY': API_KEY
        },
        body: JSON.stringify({
          first_name: '',
          email: 'invalid-email'
        })
      });
      expect(res.status).toBe(400);
      const json = await res.json();
      expect(json.status).toBe(false);
    });

    it('berhasil mendaftarkan user baru via registerPortal.php dengan role USER dan status pending (HTTP 201)', async () => {
      const res = await fetch(`${BASE_URL}/api/auth/registerPortal.php`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-API-KEY': API_KEY
        },
        body: JSON.stringify({
          first_name: 'Budi',
          last_name: 'Santoso',
          username: testRegUser,
          email: testRegEmail,
          password: 'PasswordBPS2026#'
        })
      });
      expect(res.status).toBe(201);
      const json = await res.json();
      expect(json.status).toBe(true);
      expect(json.data.username).toBe(testRegUser);
      expect(json.data.role).toBe('USER');
      expect(json.data.status).toBe('pending');
      testRegisteredId = json.data.id_admin;
      expect(testRegisteredId).toBeGreaterThan(0);
    }, 30000);

    it('menolak pendaftaran username/email yang sudah terdaftar dengan HTTP 409', async () => {
      const res = await fetch(`${BASE_URL}/api/auth/registerPortal.php`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-API-KEY': API_KEY
        },
        body: JSON.stringify({
          first_name: 'Budi Duplikat',
          username: testRegUser,
          email: testRegEmail,
          password: 'PasswordBPS2026#'
        })
      });
      expect(res.status).toBe(409);
      const json = await res.json();
      expect(json.status).toBe(false);
    });
  });
});



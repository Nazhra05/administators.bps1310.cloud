import { describe, it, expect, beforeAll } from 'vitest';
import { Window } from 'happy-dom';
import fs from 'node:fs';
import path from 'node:path';
import { execFileSync } from 'node:child_process';

function loadEnv() {
  const envPath = path.resolve(process.cwd(), '.env');
  const env = {};
  if (fs.existsSync(envPath)) {
    const lines = fs.readFileSync(envPath, 'utf8').split('\n');
    for (const line of lines) {
      const trimmed = line.trim();
      if (!trimmed || trimmed.startsWith('#')) continue;
      const idx = trimmed.indexOf('=');
      if (idx !== -1) {
        const key = trimmed.slice(0, idx).trim();
        const val = trimmed.slice(idx + 1).trim().replace(/^['"]|['"]$/g, '');
        env[key] = val;
      }
    }
  }
  return env;
}

const env = loadEnv();
const rawBaseUrl = process.env.TEST_BASE_URL ||
  (process.env.APP_URL && process.env.APP_URL.startsWith('http') ? process.env.APP_URL : null) ||
  env.APP_URL ||
  'http://admin.test';

const BASE_URL = rawBaseUrl.replace(/\/+$/, '');
const API_KEY = process.env.API_KEY_APP_B || env.API_KEY_APP_B || env.API_KEY || 'BPS_SOLSEL_API_2026_GANTI_DENGAN_KEY_RAHASIA';

function parseHtml(html) {
  const window = new Window();
  window.document.documentElement.innerHTML = html;
  return window.document;
}

let sessionCookie = '';

async function fetchWithSession(url, options = {}) {
  const headers = { ...(options.headers || {}) };
  if (sessionCookie) {
    headers['Cookie'] = sessionCookie;
  }
  const response = await fetch(url, { ...options, headers, redirect: 'manual' });
  const setCookie = response.headers.get('set-cookie');
  if (setCookie) {
    sessionCookie = setCookie.split(';')[0];
  }
  return response;
}

function extractCsrfToken(doc) {
  const input = doc.querySelector('input[name="csrf_token"]');
  return input ? input.value : '';
}

describe('Functional Flow & Click-Path Characterization Tests (Task 2.4)', () => {

  beforeAll(async () => {
    // Perform admin login once to establish session for functional flows
    const loginPage = await fetchWithSession(`${BASE_URL}/login.php`);
    const doc = parseHtml(await loginPage.text());
    const csrfToken = extractCsrfToken(doc);

    const params = new URLSearchParams();
    params.append('csrf_token', csrfToken);
    params.append('username', 'admin');
    params.append('password', 'admin123');

    const res = await fetchWithSession(`${BASE_URL}/login.php`, {
      method: 'POST',
      body: params,
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
    });

    expect(res.status).toBe(302);
    expect(res.headers.get('location')).toBe('index.php');
  });

  describe('1. Flow: Login → Dashboard → Kategori CRUD → API Sync', () => {
    let createdKategoriId = 0;
    const testCategoryName = 'Kategori Alur Uji ' + Date.now();
    const updatedCategoryName = testCategoryName + ' Edit';

    it('navigates to dashboard index.php and confirms admin session', async () => {
      const res = await fetchWithSession(`${BASE_URL}/index.php`);
      expect(res.status).toBe(200);
      const doc = parseHtml(await res.text());
      expect(doc.querySelector('.admin-name')?.textContent).toContain('admin');
    });

    it('creates a new category via tambah_kategori.php and redirects to kategori.php', async () => {
      const formPage = await fetchWithSession(`${BASE_URL}/tambah_kategori.php`);
      expect(formPage.status).toBe(200);
      const doc = parseHtml(await formPage.text());
      const csrfToken = extractCsrfToken(doc);
      expect(csrfToken).not.toBe('');

      const params = new URLSearchParams();
      params.append('csrf_token', csrfToken);
      params.append('nama_kategori', testCategoryName);
      params.append('deskripsi', 'Kategori pengujian alur otomatis');

      const submitRes = await fetchWithSession(`${BASE_URL}/tambah_kategori.php`, {
        method: 'POST',
        body: params,
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
      });

      expect(submitRes.status).toBe(302);
      expect(submitRes.headers.get('location')).toBe('kategori.php');
    });

    it('verifies the newly created category appears in GET /api/kategori.php', async () => {
      const apiRes = await fetch(`${BASE_URL}/api/kategori.php`, {
        headers: { 'X-API-KEY': API_KEY }
      });
      expect(apiRes.status).toBe(200);
      const json = await apiRes.json();
      expect(json.status).toBe(true);

      const found = json.data.find(k => k.nama_kategori === testCategoryName);
      expect(found).not.toBeUndefined();
      createdKategoriId = found.id_kategori;
      expect(createdKategoriId).toBeGreaterThan(0);
    });

    it('updates the category via edit_kategori.php', async () => {
      expect(createdKategoriId).toBeGreaterThan(0);

      const editPage = await fetchWithSession(`${BASE_URL}/edit_kategori.php?id=${createdKategoriId}`);
      expect(editPage.status).toBe(200);
      const doc = parseHtml(await editPage.text());
      const csrfToken = extractCsrfToken(doc);

      const params = new URLSearchParams();
      params.append('csrf_token', csrfToken);
      params.append('nama_kategori', updatedCategoryName);
      params.append('deskripsi', 'Deskripsi telah diperbarui via alur tes');

      const updateRes = await fetchWithSession(`${BASE_URL}/edit_kategori.php?id=${createdKategoriId}`, {
        method: 'POST',
        body: params,
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
      });

      expect(updateRes.status).toBe(302);
      expect(updateRes.headers.get('location')).toBe('kategori.php');
    });

    it('verifies the updated category reflects in GET /api/kategori.php', async () => {
      const apiRes = await fetch(`${BASE_URL}/api/kategori.php`, {
        headers: { 'X-API-KEY': API_KEY }
      });
      const json = await apiRes.json();
      const found = json.data.find(k => k.id_kategori === createdKategoriId);
      expect(found?.nama_kategori).toBe(updatedCategoryName);
    });

    it('BUG-001: rejects deletion of kategori via GET method (405 Method Not Allowed)', async () => {
      const getRes = await fetchWithSession(`${BASE_URL}/hapus_kategori.php?id=${createdKategoriId}`);
      expect(getRes.status).toBe(405);
    });

    it('BUG-001: rejects deletion of kategori via POST without valid CSRF token (403 Forbidden)', async () => {
      const params = new URLSearchParams();
      params.append('id', String(createdKategoriId));
      params.append('csrf_token', 'invalid_token_123');

      const postRes = await fetchWithSession(`${BASE_URL}/hapus_kategori.php`, {
        method: 'POST',
        body: params,
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
      });
      expect(postRes.status).toBe(403);
    });

    it('BUG-001: safely deletes category via POST with valid CSRF token and reflects in API', async () => {
      // Fetch kategori.php to grab valid csrf token
      const pageRes = await fetchWithSession(`${BASE_URL}/kategori.php`);
      expect(pageRes.status).toBe(200);
      const doc = parseHtml(await pageRes.text());
      const csrfToken = extractCsrfToken(doc);
      expect(csrfToken).toBeTruthy();

      const params = new URLSearchParams();
      params.append('id', String(createdKategoriId));
      params.append('csrf_token', csrfToken);

      const deleteRes = await fetchWithSession(`${BASE_URL}/hapus_kategori.php`, {
        method: 'POST',
        body: params,
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
      });
      expect([200, 302]).toContain(deleteRes.status);

      // Verify category no longer exists in API
      const apiRes = await fetch(`${BASE_URL}/api/kategori.php`, {
        headers: { 'X-API-KEY': API_KEY }
      });
      const json = await apiRes.json();
      const found = json.data.find(k => k.id_kategori === createdKategoriId);
      expect(found).toBeUndefined();
    });
  });

  describe('2. Flow: Login → Layanan CRUD (+ Logo) → API Sync', () => {
    let createdLayananId = 0;
    const testServiceName = 'Layanan Uji Integrasi ' + Date.now();
    const updatedServiceName = testServiceName + ' Diperbarui';

    it('creates a new layanan via tambah_layanan.php', async () => {
      const formPage = await fetchWithSession(`${BASE_URL}/tambah_layanan.php`);
      expect(formPage.status).toBe(200);
      const doc = parseHtml(await formPage.text());
      const csrfToken = extractCsrfToken(doc);

      const params = new URLSearchParams();
      params.append('csrf_token', csrfToken);
      params.append('nama_layanan', testServiceName);
      params.append('id_kategori', '1');
      params.append('akses', 'Public');
      params.append('vpn', 'No');
      params.append('url', 'https://layanan-uji.internal.bps1310.cloud');
      params.append('deskripsi_layanan', 'Deskripsi uji layanan otomatis');

      const submitRes = await fetchWithSession(`${BASE_URL}/tambah_layanan.php`, {
        method: 'POST',
        body: params,
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
      });

      expect([200, 302]).toContain(submitRes.status);
    });

    it('verifies the new layanan appears in GET /api/layanan.php', async () => {
      const apiRes = await fetch(`${BASE_URL}/api/layanan.php`, {
        headers: { 'X-API-KEY': API_KEY }
      });
      expect(apiRes.status).toBe(200);
      const json = await apiRes.json();

      const found = json.data.find(l => l.nama_layanan === testServiceName);
      expect(found).not.toBeUndefined();
      createdLayananId = found.id_layanan;
      expect(createdLayananId).toBeGreaterThan(0);
    });

    it('updates the layanan via edit_layanan.php', async () => {
      expect(createdLayananId).toBeGreaterThan(0);

      const editPage = await fetchWithSession(`${BASE_URL}/edit_layanan.php?id=${createdLayananId}`);
      expect(editPage.status).toBe(200);
      const doc = parseHtml(await editPage.text());
      const csrfToken = extractCsrfToken(doc);

      const params = new URLSearchParams();
      params.append('csrf_token', csrfToken);
      params.append('nama_layanan', updatedServiceName);
      params.append('id_kategori', '1');
      params.append('akses', 'Internal');
      params.append('vpn', 'Yes');
      params.append('url', 'https://layanan-uji.internal.bps1310.cloud/v2');
      params.append('deskripsi_layanan', 'Deskripsi telah diupdate');

      const updateRes = await fetchWithSession(`${BASE_URL}/edit_layanan.php?id=${createdLayananId}`, {
        method: 'POST',
        body: params,
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
      });

      expect([200, 302]).toContain(updateRes.status);

      // Verify update in API
      const apiRes = await fetch(`${BASE_URL}/api/layanan.php?id=${createdLayananId}`, {
        headers: { 'X-API-KEY': API_KEY }
      });
      const json = await apiRes.json();
      expect(json.data.nama_layanan).toBe(updatedServiceName);
      expect(json.data.akses).toBe('Internal');
      expect(json.data.vpn).toBe('Yes');
    });

    it('BUG-002: rejects deletion of layanan via GET method (405 Method Not Allowed)', async () => {
      const getRes = await fetchWithSession(`${BASE_URL}/hapus_layanan.php?id=${createdLayananId}`);
      expect(getRes.status).toBe(405);
    });

    it('BUG-002: rejects deletion of layanan via POST without valid CSRF token (403 Forbidden)', async () => {
      const params = new URLSearchParams();
      params.append('id', String(createdLayananId));
      params.append('csrf_token', 'invalid_token_xyz');

      const postRes = await fetchWithSession(`${BASE_URL}/hapus_layanan.php`, {
        method: 'POST',
        body: params,
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
      });
      expect(postRes.status).toBe(403);
    });

    it('BUG-002: safely deletes test layanan via POST with valid CSRF token and reflects in API', async () => {
      const pageRes = await fetchWithSession(`${BASE_URL}/layanan.php`);
      expect(pageRes.status).toBe(200);
      const doc = parseHtml(await pageRes.text());
      const csrfToken = extractCsrfToken(doc);
      expect(csrfToken).toBeTruthy();

      const params = new URLSearchParams();
      params.append('id', String(createdLayananId));
      params.append('csrf_token', csrfToken);

      const deleteRes = await fetchWithSession(`${BASE_URL}/hapus_layanan.php`, {
        method: 'POST',
        body: params,
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
      });
      expect([200, 302]).toContain(deleteRes.status);

      // Verify removal in API
      const apiRes = await fetch(`${BASE_URL}/api/layanan.php?id=${createdLayananId}`, {
        headers: { 'X-API-KEY': API_KEY }
      });
      expect(apiRes.status).toBe(404);
    });
  });

  describe('3. Flow: User & Access Management Characterization', () => {
    it('authenticates admin access to kelola_admin.php', async () => {
      const res = await fetchWithSession(`${BASE_URL}/kelola_admin.php`);
      expect(res.status).toBe(200);
      const doc = parseHtml(await res.text());
      expect(doc.querySelector('h1')?.textContent).toContain('Kelola Admin');
    });

    it('authenticates admin access to kelola_user.php and renders invite form', async () => {
      const res = await fetchWithSession(`${BASE_URL}/kelola_user.php`);
      expect(res.status).toBe(200);
      const doc = parseHtml(await res.text());
      expect(doc.querySelector('input[name="email"]')).not.toBeNull();
    });

    it('BUG-003: Setup user page rejects IDOR access via raw ID without token', async () => {
      const res = await fetch(`${BASE_URL}/setup_user.php?id=4`);
      expect(res.status).toBe(200);
      const text = await res.text();
      expect(text).toContain('Link pendaftaran tidak valid');
      const doc = parseHtml(text);
      expect(doc.querySelector('input[name="password"]')).toBeNull();
    });

    it('BUG-003: Setup user page rejects fabricated or invalid token', async () => {
      const res = await fetch(`${BASE_URL}/setup_user.php?token=invalid_random_token_999`);
      expect(res.status).toBe(200);
      const text = await res.text();
      expect(text).toContain('Link pendaftaran tidak valid atau sudah tidak berlaku');
      const doc = parseHtml(text);
      expect(doc.querySelector('input[name="password"]')).toBeNull();
    });

    it('BUG-004: Completes portal user verification, password setup with Bcrypt, and logs in via API', async () => {
      // 0. Ensure user 4 is in fresh pending state
      execFileSync('php', ['scripts/test-seed-portal-user.php']);

      // 1. Verify email via token
      const verifyRes = await fetch(`${BASE_URL}/verify_user.php?token=token_test_portal_aktif`);
      expect(verifyRes.status).toBe(200);
      const verifyText = await verifyRes.text();
      expect(verifyText).toContain('Email Berhasil Diverifikasi');
      expect(verifyText).toContain('setup_user.php?token=token_test_portal_aktif');

      // 2. Open setup page with valid token
      const setupPage = await fetch(`${BASE_URL}/setup_user.php?token=token_test_portal_aktif`);
      expect(setupPage.status).toBe(200);
      const setupCookie = setupPage.headers.get('set-cookie')?.split(';')[0] || '';
      const doc = parseHtml(await setupPage.text());
      const csrfToken = extractCsrfToken(doc);
      expect(csrfToken).toBeTruthy();

      // 3. Submit credentials
      const params = new URLSearchParams();
      params.append('token', 'token_test_portal_aktif');
      params.append('csrf_token', csrfToken);
      params.append('username', 'user_portal_aktif');
      params.append('password', 'PasswordKuat123!');
      params.append('password_confirmation', 'PasswordKuat123!');

      const headers = { 'Content-Type': 'application/x-www-form-urlencoded' };
      if (setupCookie) {
        headers['Cookie'] = setupCookie;
      }

      const submitRes = await fetch(`${BASE_URL}/setup_user.php`, {
        method: 'POST',
        body: params,
        headers
      });
      expect(submitRes.status).toBe(200);
      const submitText = await submitRes.text();
      expect(submitText).toContain('Pendaftaran Berhasil');

      // 4. Verify login via App B portal auth API
      const loginRes = await fetch(`${BASE_URL}/api/auth/loginPortal.php`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-API-KEY': API_KEY
        },
        body: JSON.stringify({
          identity: 'user_portal_aktif',
          password: 'PasswordKuat123!'
        })
      });
      expect(loginRes.status).toBe(200);
      const loginJson = await loginRes.json();
      expect(loginJson.status).toBe(true);
      expect(loginJson.data.username).toBe('user_portal_aktif');
    });

    it.skip('BUG-006: Provide profile settings and password change form in pengaturan.php', async () => {
      // In Fase 3, pengaturan.php will be upgraded with change password form
    });
  });

});

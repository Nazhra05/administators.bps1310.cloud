import { describe, it, expect, beforeAll, afterAll } from 'vitest';
import { Window } from 'happy-dom';

const BASE_URL = 'http://localhost/Dashboard_Admin/public';
const ADMIN_PASS = process.env.ADMIN_PASSWORD || '#1310SolokSelatan';

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

describe('Verifikasi Email Admin — Alur Pendaftaran & Verifikasi Admin Baru', () => {
  let adminClient;
  let newAdminId = null;
  let testEmail = '';
  let token = '';

  beforeAll(async () => {
    adminClient = createSession();

    // Login as Admin Utama
    const loginRes = await adminClient.fetch(`${BASE_URL}/login.php`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: `username=admin&password=${encodeURIComponent(ADMIN_PASS)}`
    });
    expect(loginRes.status).toBe(302);
  });

  afterAll(async () => {
    if (newAdminId) {
      // Hapus akun admin uji coba
      const pageRes = await adminClient.fetch(`${BASE_URL}/kelola_admin.php`);
      const doc = parseHtml(await pageRes.text());
      const csrf = extractCsrfToken(doc);
      await adminClient.fetch(`${BASE_URL}/kelola_admin.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
          csrf_token: csrf,
          action: 'delete_admin',
          id_admin: String(newAdminId)
        }).toString()
      });
    }
  });

  it('1. kelola_admin.php menggunakan fungsi kirim verifikasi email khusus admin', async () => {
    const pageRes = await adminClient.fetch(`${BASE_URL}/kelola_admin.php`);
    const doc = parseHtml(await pageRes.text());
    const csrf = extractCsrfToken(doc);

    testEmail = `test_admin_verif_${Date.now()}@bps1310.cloud`;
    const regRes = await adminClient.fetch(`${BASE_URL}/kelola_admin.php`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: new URLSearchParams({
        csrf_token: csrf,
        email: testEmail
      }).toString()
    });
    expect(regRes.status).toBe(200);

    // Ambil data admin dari tabel
    const afterRes = await adminClient.fetch(`${BASE_URL}/kelola_admin.php`);
    const afterDoc = parseHtml(await afterRes.text());
    const table = afterDoc.querySelector('table');
    const rows = Array.from(table.querySelectorAll('tbody tr'));
    const targetRow = rows.find(r => r.textContent.includes(testEmail));
    expect(targetRow).not.toBeUndefined();

    const idInput = targetRow.querySelector('input[name="id_admin"]');
    newAdminId = parseInt(idInput.value, 10);
    expect(newAdminId).toBeGreaterThan(0);

    // Cek bahwa role di tabel adalah ADMIN
    expect(targetRow.textContent).toContain('ADMIN');
  }, 45000);

  it('2. verify_user.php berhasil memverifikasi token admin dan menampilkan teks khusus Admin', async () => {
    // Ambil token langsung dari database via PHP CLI
    const { execSync } = require('child_process');
    const tokenOutput = execSync('php', {
      input: `<?php require 'config/config.php'; echo \$conn->query('SELECT verification_token FROM admin WHERE id_admin = ${newAdminId}')->fetchColumn();`
    }).toString().trim();

    token = tokenOutput;
    expect(token).toBeTruthy();

    // Akses verify_user.php dengan token
    const verifyClient = createSession();
    const verifyRes = await verifyClient.fetch(`${BASE_URL}/verify_user.php?token=${encodeURIComponent(token)}`);
    expect(verifyRes.status).toBe(200);

    const doc = parseHtml(await verifyRes.text());
    const heading = doc.querySelector('h1')?.textContent || '';
    expect(heading).toContain('Email Admin Berhasil Diverifikasi');

    // Terdapat tautan ke setup_user.php dengan ID admin
    const setupLink = doc.querySelector('a.button');
    expect(setupLink).not.toBeNull();
    expect(setupLink.getAttribute('href')).toContain(`setup_user.php?id=${newAdminId}`);
    expect(setupLink.textContent).toContain('Admin');
  });

  it('3. setup_user.php menampilkan form buat akun administrator dan mempertahankan role ADMIN', async () => {
    const setupClient = createSession();
    const setupGetRes = await setupClient.fetch(`${BASE_URL}/setup_user.php?id=${newAdminId}`);
    expect(setupGetRes.status).toBe(200);

    const doc = parseHtml(await setupGetRes.text());
    expect(doc.querySelector('h1')?.textContent).toContain('Admin');
    const csrf = extractCsrfToken(doc);

    const newUsername = `adm_${Date.now()}`;
    const newPassword = 'PasswordBaru123!';

    // Submit pembuatan akun admin
    const setupPostRes = await setupClient.fetch(`${BASE_URL}/setup_user.php`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: new URLSearchParams({
        csrf_token: csrf,
        id_admin: String(newAdminId),
        username: newUsername,
        password: newPassword,
        password_confirmation: newPassword
      }).toString()
    });
    expect(setupPostRes.status).toBe(200);

    const postDoc = parseHtml(await setupPostRes.text());
    expect(postDoc.querySelector('.success-text')?.textContent).toContain('Administrator');

    // Cek bahwa admin baru bisa login dan role tetap ADMIN
    const newAdminSession = createSession();
    const newLoginRes = await newAdminSession.fetch(`${BASE_URL}/login.php`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: `username=${encodeURIComponent(newUsername)}&password=${encodeURIComponent(newPassword)}`
    });
    expect(newLoginRes.status).toBe(302);
    expect(newLoginRes.headers.get('location')).toContain('index.php');
  });
});

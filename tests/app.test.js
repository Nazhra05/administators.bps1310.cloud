import { describe, it, expect, beforeAll } from 'vitest';
import { Window } from 'happy-dom';

const BASE_URL = 'http://localhost/administators.bps1310.cloud/public';
const API_KEY = 'BPS_SOLSEL_API_2026_GANTI_DENGAN_KEY_RAHASIA';

function parseHtml(html) {
  const window = new Window();
  window.document.documentElement.innerHTML = html;
  return window.document;
}

// Session cookie storage to simulate a browser session
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

describe('Dashboard BPS Solok Selatan - User-Facing Features', () => {

  // 1. LOGIN PAGE INTERFACE
  it('1. User sees complete Login page interface with title, logos, and form fields', async () => {
    const res = await fetch(`${BASE_URL}/login.php`);
    expect(res.status).toBe(200);

    const html = await res.text();
    const doc = parseHtml(html);

    // Title & Brand
    expect(doc.title).toContain('Login Admin');
    expect(doc.querySelector('.logo img')).not.toBeNull();
    expect(doc.querySelector('.logo h1')?.textContent).toContain('Dashboard Admin');
    expect(doc.querySelector('.logo p')?.textContent).toContain('BPS Kabupaten Solok Selatan');

    // Inputs & Labels
    expect(doc.querySelector('label[for="username"]')?.textContent).toContain('Username');
    expect(doc.querySelector('input#username')).not.toBeNull();
    expect(doc.querySelector('label[for="password"]')?.textContent).toContain('Password');
    expect(doc.querySelector('input#password')).not.toBeNull();

    // Buttons & Links
    expect(doc.querySelector('button.btn-login')?.textContent).toContain('Login');
    const registerLink = doc.querySelector('.register-link a');
    expect(registerLink?.textContent).toContain('Daftar sekarang');
    expect(registerLink?.getAttribute('href')).toBe('register.php');
  });

  // 2. LOGIN VALIDATION - EMPTY CREDENTIALS
  it('2. User sees validation error when submitting empty credentials', async () => {
    // Get fresh page and CSRF token
    const pageRes = await fetchWithSession(`${BASE_URL}/login.php`);
    const doc = parseHtml(await pageRes.text());
    const csrfToken = extractCsrfToken(doc);

    const params = new URLSearchParams();
    params.append('csrf_token', csrfToken);
    params.append('username', '');
    params.append('password', '');

    const res = await fetchWithSession(`${BASE_URL}/login.php`, {
      method: 'POST',
      body: params,
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
    });

    const resultDoc = parseHtml(await res.text());
    const errorAlert = resultDoc.querySelector('.error');
    expect(errorAlert).not.toBeNull();
    expect(errorAlert?.textContent).toContain('Username dan password wajib diisi');
  });

  // 3. LOGIN VALIDATION - INVALID PASSWORD
  it('3. User sees error message when entering an invalid password', async () => {
    const pageRes = await fetchWithSession(`${BASE_URL}/login.php`);
    const doc = parseHtml(await pageRes.text());
    const csrfToken = extractCsrfToken(doc);

    const params = new URLSearchParams();
    params.append('csrf_token', csrfToken);
    params.append('username', 'admin');
    params.append('password', 'wrongPassword123');

    const res = await fetchWithSession(`${BASE_URL}/login.php`, {
      method: 'POST',
      body: params,
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
    });

    const resultDoc = parseHtml(await res.text());
    const errorAlert = resultDoc.querySelector('.error');
    expect(errorAlert).not.toBeNull();
    expect(errorAlert?.textContent).toContain('Username atau password salah');
  });

  // 4. LOGIN SUCCESS - WITH USERNAME
  it('4. User logs in successfully using username and gets redirected to index.php', async () => {
    const pageRes = await fetchWithSession(`${BASE_URL}/login.php`);
    const doc = parseHtml(await pageRes.text());
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

    // Should redirect to index.php (302)
    expect(res.status).toBe(302);
    expect(res.headers.get('location')).toBe('index.php');
  });

  // 5. LOGIN SUCCESS - WITH EMAIL
  it('5. User logs in successfully using registered email address', async () => {
    const pageRes = await fetchWithSession(`${BASE_URL}/login.php`);
    const doc = parseHtml(await pageRes.text());
    const csrfToken = extractCsrfToken(doc);

    const params = new URLSearchParams();
    params.append('csrf_token', csrfToken);
    params.append('username', 'admin@bps1310.cloud');
    params.append('password', 'admin123');

    const res = await fetchWithSession(`${BASE_URL}/login.php`, {
      method: 'POST',
      body: params,
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
    });

    expect(res.status).toBe(302);
    expect(res.headers.get('location')).toBe('index.php');
  });

  // 6. ACCESS CONTROL - UNAUTHENTICATED REDIRECT
  it('6. Unauthenticated visitor is redirected to login.php when accessing protected pages', async () => {
    // Fresh request without session cookie
    const res = await fetch(`${BASE_URL}/index.php`, { redirect: 'manual' });
    expect(res.status).toBe(302);
    expect(res.headers.get('location')).toBe('login.php');
  });

  // 7. DASHBOARD PAGE - OVERVIEW & STATS
  it('7. Authenticated user sees Dashboard overview, greeting, and summary cards', async () => {
    const res = await fetchWithSession(`${BASE_URL}/index.php`);
    expect(res.status).toBe(200);

    const doc = parseHtml(await res.text());
    expect(doc.title).toContain('Dashboard Admin');
    expect(doc.querySelector('.header h1')?.textContent).toContain('Dashboard Admin');

    // Admin greeting status
    const adminName = doc.querySelector('.admin-name');
    expect(adminName?.textContent).toContain('admin');

    // Summary cards (Total Layanan & Total Kategori)
    const cards = doc.querySelectorAll('.card');
    expect(cards.length).toBeGreaterThanOrEqual(2);
    const cardText = Array.from(cards).map(c => c.textContent).join(' ');
    expect(cardText).toContain('Total Layanan');
    expect(cardText).toContain('Total Kategori');
  });

  // 8. DASHBOARD CATEGORY FILTER
  it('8. User sees category filter options on dashboard table', async () => {
    const res = await fetchWithSession(`${BASE_URL}/index.php`);
    const doc = parseHtml(await res.text());

    const select = doc.querySelector('select#kategori');
    expect(select).not.toBeNull();

    const options = Array.from(select.querySelectorAll('option')).map(o => o.textContent.trim());
    expect(options).toContain('Semua Kategori');
    expect(options).toContain('Distribusi');
    expect(options).toContain('Produksi');
  });

  // 9. DASHBOARD DATA TABLE
  it('9. User sees service table with columns and link icons', async () => {
    const res = await fetchWithSession(`${BASE_URL}/index.php`);
    const doc = parseHtml(await res.text());

    const headers = Array.from(doc.querySelectorAll('thead th')).map(th => th.textContent.trim());
    expect(headers).toContain('No');
    expect(headers).toContain('Nama Layanan');
    expect(headers).toContain('Kategori');
    expect(headers).toContain('Link');

    const rows = doc.querySelectorAll('tbody tr');
    expect(rows.length).toBeGreaterThan(0);
  });

  // 10. KELOLA LAYANAN PAGE
  it('10. User sees Kelola Layanan page with "Tambah Layanan" button and action buttons', async () => {
    const res = await fetchWithSession(`${BASE_URL}/layanan.php`);
    expect(res.status).toBe(200);

    const doc = parseHtml(await res.text());
    expect(doc.querySelector('h1')?.textContent).toContain('Manage Link');

    // Add button
    const btnTambah = doc.querySelector('.btn-add');
    expect(btnTambah?.textContent).toContain('Tambah Layanan');

    // Actions exist in table (Edit and Delete)
    const editBtn = doc.querySelector('.action-btn.edit-btn');
    const deleteBtn = doc.querySelector('.action-btn.delete-btn');
    expect(editBtn).not.toBeNull();
    expect(deleteBtn).not.toBeNull();
  });

  // 11. TAMBAH LAYANAN FORM
  it('11. User sees Tambah Layanan form with all required inputs and options', async () => {
    const res = await fetchWithSession(`${BASE_URL}/tambah_layanan.php`);
    expect(res.status).toBe(200);

    const doc = parseHtml(await res.text());
    expect(doc.querySelector('.header h1')?.textContent).toContain('Tambah Layanan');

    // Form inputs
    expect(doc.querySelector('input[name="nama_layanan"]')).not.toBeNull();
    expect(doc.querySelector('input[name="url"]')).not.toBeNull();
    expect(doc.querySelector('select[name="id_kategori"]')).not.toBeNull();
    expect(doc.querySelector('input[name="logo"]')).not.toBeNull();
    expect(doc.querySelector('button[type="submit"]')?.textContent).toContain('Simpan Layanan');
  });

  // 12. KATEGORI PAGE
  it('12. User sees Kategori page listing categories with service counts', async () => {
    const res = await fetchWithSession(`${BASE_URL}/kategori.php`);
    expect(res.status).toBe(200);

    const doc = parseHtml(await res.text());
    expect(doc.querySelector('.header h1')?.textContent).toContain('Kategori');

    const btnTambah = doc.querySelector('.btn-tambah');
    expect(btnTambah?.textContent).toContain('Tambah Kategori');

    // Categories list table
    const tableText = doc.querySelector('.table-container')?.textContent || '';
    expect(tableText).toContain('Distribusi');
    expect(tableText).toContain('Produksi');
    expect(tableText).toContain('Sosial');
  });

  // 13. TAMBAH KATEGORI FORM
  it('13. User sees Tambah Kategori form with input and save button', async () => {
    const res = await fetchWithSession(`${BASE_URL}/tambah_kategori.php`);
    expect(res.status).toBe(200);

    const doc = parseHtml(await res.text());
    expect(doc.querySelector('.header h1')?.textContent).toContain('Tambah Kategori');
    expect(doc.querySelector('input[name="nama_kategori"]')).not.toBeNull();
    expect(doc.querySelector('button.btn-simpan')?.textContent).toContain('Simpan Kategori');
  });

  // 14. EDIT KATEGORI FORM
  it('14. User sees Edit Kategori form populated with existing category data', async () => {
    const res = await fetchWithSession(`${BASE_URL}/edit_kategori.php?id=1`);
    expect(res.status).toBe(200);

    const doc = parseHtml(await res.text());
    expect(doc.querySelector('.header h1')?.textContent).toContain('Edit Kategori');

    const input = doc.querySelector('input[name="nama_kategori"]');
    expect(input?.value).toBe('Distribusi');
    expect(doc.querySelector('button.btn-save')?.textContent).toContain('Simpan Perubahan');
  });

  // 15. KELOLA ADMIN PAGE
  it('15. User sees Kelola Admin page with administrator accounts table', async () => {
    const res = await fetchWithSession(`${BASE_URL}/kelola_admin.php`);
    expect(res.status).toBe(200);

    const doc = parseHtml(await res.text());
    expect(doc.querySelector('h1')?.textContent).toContain('Kelola Admin');
  });

  // 16. PENGATURAN PAGE
  it('16. User sees Pengaturan page with system information', async () => {
    const res = await fetchWithSession(`${BASE_URL}/pengaturan.php`);
    expect(res.status).toBe(200);

    const doc = parseHtml(await res.text());
    expect(doc.querySelector('.header h1')?.textContent).toContain('Pengaturan');

    const cardText = doc.querySelector('.card')?.textContent || '';
    expect(cardText).toContain('Nama Sistem');
    expect(cardText).toContain('Dashboard Admin BPS Kabupaten Solok Selatan');
    expect(cardText).toContain('Database');
  });

  // 17. REGISTER PAGE INTERFACE
  it('17. User sees complete registration form with all required user fields', async () => {
    const res = await fetch(`${BASE_URL}/register.php`);
    expect(res.status).toBe(200);

    const doc = parseHtml(await res.text());
    expect(doc.querySelector('.heading h1')?.textContent).toContain('Create an Account');

    expect(doc.querySelector('input[name="first_name"]')).not.toBeNull();
    expect(doc.querySelector('input[name="last_name"]')).not.toBeNull();
    expect(doc.querySelector('input[name="username"]')).not.toBeNull();
    expect(doc.querySelector('input[name="email"]')).not.toBeNull();
    expect(doc.querySelector('input[name="password"]')).not.toBeNull();
    expect(doc.querySelector('input[name="confirm_password"]')).not.toBeNull();
    expect(doc.querySelector('input[name="agree_to_terms"]')).not.toBeNull();
    expect(doc.querySelector('button.btn-create')?.textContent).toContain('Create Account');
  });

  // 18. REGISTER VALIDATION - PASSWORD MISMATCH
  it('18. User sees validation error when passwords do not match during registration', async () => {
    const pageRes = await fetchWithSession(`${BASE_URL}/register.php`);
    const doc = parseHtml(await pageRes.text());
    const csrfToken = extractCsrfToken(doc);

    const params = new URLSearchParams();
    params.append('csrf_token', csrfToken);
    params.append('first_name', 'Test');
    params.append('last_name', 'User');
    params.append('username', 'testuser999');
    params.append('email', 'test999@example.com');
    params.append('password', 'password123');
    params.append('confirm_password', 'differentPassword456');
    params.append('agree_to_terms', '1');

    const res = await fetchWithSession(`${BASE_URL}/register.php`, {
      method: 'POST',
      body: params,
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
    });

    const resultDoc = parseHtml(await res.text());
    const errorAlert = resultDoc.querySelector('.error');
    expect(errorAlert?.textContent).toContain('Konfirmasi password tidak cocok');
  });

  // 19. REST API SECURITY & PUBLIC ACCESS
  it('19. API protects unauthorized access without API key and returns structured JSON with API key', async () => {
    // 19a: Unauthorized request without API key
    const deniedRes = await fetch(`${BASE_URL}/api/layanan.php`);
    expect(deniedRes.status).toBe(401);
    const deniedData = await deniedRes.json();
    expect(deniedData.status).toBe(false);
    expect(deniedData.message).toContain('API key wajib dikirim');

    // 19b: Authorized request with API key
    const successRes = await fetch(`${BASE_URL}/api/website.php`, {
      headers: { 'X-API-KEY': API_KEY }
    });
    expect(successRes.status).toBe(200);
    const successData = await successRes.json();
    expect(successData.status).toBe(true);
    expect(Array.isArray(successData.data)).toBe(true);
    expect(successData.data.length).toBeGreaterThan(0);
  });

  // 20. LOGOUT FLOW
  it('20. User logs out and is cleanly redirected to login.php', async () => {
    const res = await fetchWithSession(`${BASE_URL}/logout.php`);
    expect(res.status).toBe(302);
    expect(res.headers.get('location')).toBe('login.php');

    // Subsequent dashboard request must be redirected to login
    const protectedRes = await fetchWithSession(`${BASE_URL}/index.php`);
    expect(protectedRes.status).toBe(302);
    expect(protectedRes.headers.get('location')).toBe('login.php');
  });

});

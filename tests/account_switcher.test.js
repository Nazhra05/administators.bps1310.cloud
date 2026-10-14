import { describe, it, expect } from 'vitest';
import { Window } from 'happy-dom';

const BASE_URL = 'http://localhost/Dashboard_Admin/public';

function parseHtml(html) {
  const window = new Window();
  window.document.documentElement.innerHTML = html;
  return window.document;
}

const sessionCookies = new Map();

function clearSession() {
  sessionCookies.clear();
}

async function fetchWithSession(url, options = {}) {
  const headers = { ...(options.headers || {}) };
  if (sessionCookies.size > 0) {
    headers['Cookie'] = Array.from(sessionCookies.entries()).map(([k, v]) => `${k}=${v}`).join('; ');
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
      sessionCookies.set(first.substring(0, eqIdx).trim(), first.substring(eqIdx + 1).trim());
    }
  }
  return response;
}

async function login(username, password) {
  const pageRes = await fetchWithSession(`${BASE_URL}/login.php`);
  const match = (await pageRes.text()).match(/name="csrf_token"\s+value="([^"]+)"/);
  const csrfToken = match ? match[1] : '';

  return fetchWithSession(`${BASE_URL}/login.php`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: `username=${encodeURIComponent(username)}&password=${encodeURIComponent(password)}&csrf_token=${encodeURIComponent(csrfToken)}`
  });
}

describe('Admin-Only Role Switch (Switch ke User ↔ Switch ke Admin) & Privacy Protection', () => {

  it('1. Admin logs in: does NOT see Switch ke User in sidebar or dropdown, and does NOT see other user accounts', async () => {
    // Clear session cookie
    clearSession();

    const adminPass = process.env.ADMIN_PASSWORD || '#1310SolokSelatan';
    const loginRes = await login('admin', adminPass);

    expect(loginRes.status).toBe(302);
    expect(loginRes.headers.get('location')).toBe('index.php');

    // Access index.php
    const indexRes = await fetchWithSession(`${BASE_URL}/index.php`);
    expect(indexRes.status).toBe(200);

    const doc = parseHtml(await indexRes.text());
    const adminName = doc.querySelector('.admin-name');
    expect(adminName?.textContent).toContain('admin');
    expect(adminName?.textContent).toContain('Admin');

    // Admin sees Kelola User and Kelola Admin in sidebar
    expect(doc.querySelector('a[href*="kelola_user.php"]')?.textContent).toContain('Kelola User');
    expect(doc.querySelector('a[href*="kelola_admin.php"]')?.textContent).toContain('Kelola Admin');

    // Admin does NOT have Switch ke User in sidebar or dropdown
    const switchRoleSidebar = doc.querySelector('.sidebar a[href*="switch_role.php?to=user"]');
    expect(switchRoleSidebar).toBeNull();

    const switchRoleDropdown = doc.querySelector('.account-menu a[href*="switch_role.php?to=user"]');
    expect(switchRoleDropdown).toBeNull();

    // Admin does NOT see any other user accounts list
    expect(doc.querySelector('.account-section-title')).toBeNull();
    expect(doc.querySelector('.account-switch-item')).toBeNull();

    // Admin sees new overview cards for Total Layanan and Kategori Layanan
    const overviewCards = doc.querySelectorAll('.cat-overview-card');
    expect(overviewCards.length).toBe(2);
    expect(overviewCards[0].querySelector('.cat-overview-title')?.textContent).toBe('Total Layanan');
    expect(overviewCards[0].querySelector('.cat-count-badge')?.textContent).toMatch(/\d+\s+item/);
    expect(overviewCards[0].querySelector('.cat-icon-svg')).not.toBeNull();
    expect(overviewCards[1].querySelector('.cat-overview-title')?.textContent).toBe('Kategori Layanan');
    expect(overviewCards[1].querySelector('.cat-count-badge')?.textContent).toMatch(/\d+\s+item/);
    expect(overviewCards[1].querySelector('.cat-icon-svg')).not.toBeNull();

    // Admin sees category carousel reference cards (Distribusi, Produksi, Neraca, etc.)
    const catTrack = doc.querySelector('.cat-carousel-track');
    expect(catTrack).not.toBeNull();
    const catCards = doc.querySelectorAll('.cat-ref-card');
    expect(catCards.length).toBeGreaterThanOrEqual(3);

    // Verify first card has code tag ( 001 ), title, focus description, pill button, and watermark
    const firstCat = catCards[0];
    expect(firstCat.querySelector('.cat-code-tag')?.textContent).toMatch(/\(\s*\d{3}\s*\)/);
    expect(firstCat.querySelector('.cat-title')?.textContent).toBeTruthy();
    expect(firstCat.querySelector('.cat-desc')?.textContent).toContain('Fokus pada');
    expect(firstCat.querySelector('.cat-pill-btn')?.textContent).toMatch(/\d+\s+Layanan\s*→/);
    expect(firstCat.querySelector('.cat-watermark')).not.toBeNull();
  });

  it('2. Admin switches to User mode: views Dashboard User, sees Switch ke Admin to return', async () => {
    const switchRes = await fetchWithSession(`${BASE_URL}/switch_role.php?to=user`);
    expect(switchRes.status).toBe(302);
    expect(switchRes.headers.get('location')).toBe('index.php');

    const indexRes = await fetchWithSession(`${BASE_URL}/index.php`);
    expect(indexRes.status).toBe(200);

    const doc = parseHtml(await indexRes.text());
    const headerTitle = doc.querySelector('.header-left h1');
    expect(headerTitle?.textContent).toContain('Dashboard User');

    // In User mode, Admin has Switch ke Admin in sidebar
    const switchRoleSidebar = doc.querySelector('.sidebar a[href*="switch_role.php?to=admin"]');
    expect(switchRoleSidebar).not.toBeNull();
    expect(switchRoleSidebar?.textContent).toContain('Switch ke Admin');

    // In User mode, Admin has Switch ke Admin in dropdown
    const switchRoleDropdown = doc.querySelector('.account-menu a[href*="switch_role.php?to=admin"]');
    expect(switchRoleDropdown).not.toBeNull();
    expect(switchRoleDropdown?.textContent).toContain('Switch ke Admin');
  });

  it('3. Admin switches back to Admin mode: returns to Dashboard Admin', async () => {
    const switchRes = await fetchWithSession(`${BASE_URL}/switch_role.php?to=admin`);
    expect(switchRes.status).toBe(302);
    expect(switchRes.headers.get('location')).toBe('index.php');

    const indexRes = await fetchWithSession(`${BASE_URL}/index.php`);
    const doc = parseHtml(await indexRes.text());
    const headerTitle = doc.querySelector('.header-left h1');
    expect(headerTitle?.textContent).toContain('Dashboard Admin');

    // Admin menus back
    expect(doc.querySelector('a[href*="kelola_admin.php"]')).not.toBeNull();
    expect(doc.querySelector('a[href*="kelola_user.php"]')).not.toBeNull();
    expect(doc.querySelector('.sidebar a[href*="switch_role.php?to=user"]')).toBeNull();
  });

  it('4. Self-registered User logs in: has NO switch buttons in sidebar or dropdown, cannot switch to Admin', async () => {
    // Clear session and login as Luce (User)
    clearSession();

    const loginRes = await login('Luce', 'user123');

    expect(loginRes.status).toBe(302);
    expect(loginRes.headers.get('location')).toBe('index.php');

    const indexRes = await fetchWithSession(`${BASE_URL}/index.php`);
    const doc = parseHtml(await indexRes.text());
    const headerTitle = doc.querySelector('.header-left h1');
    expect(headerTitle?.textContent).toContain('Dashboard User');

    // Normal User MUST NOT have Switch button in sidebar
    const switchRoleSidebar = doc.querySelector('.sidebar a[href*="switch_role.php"]');
    expect(switchRoleSidebar).toBeNull();

    // Normal User MUST NOT have Switch button in dropdown
    const switchRoleDropdown = doc.querySelector('.account-menu a[href*="switch_role.php"]');
    expect(switchRoleDropdown).toBeNull();

    // Normal User does NOT see other accounts
    expect(doc.querySelector('.account-section-title')).toBeNull();
    expect(doc.querySelector('.account-switch-item')).toBeNull();

    // If User tries to access switch_role.php directly, they are redirected and cannot become admin
    const attemptRes = await fetchWithSession(`${BASE_URL}/switch_role.php?to=admin`);
    expect(attemptRes.status).toBe(302);
    expect(attemptRes.headers.get('location')).toBe('index.php');

    // Verify still user
    const checkRes = await fetchWithSession(`${BASE_URL}/index.php`);
    const checkDoc = parseHtml(await checkRes.text());
    expect(checkDoc.querySelector('.header-left h1')?.textContent).toContain('Dashboard User');
    expect(checkDoc.querySelector('a[href*="kelola_admin.php"]')).toBeNull();
  });

  it('5. Kelola User page has no + Daftarkan Admin Baru button and Daftar Pengguna only lists USER accounts', async () => {
    // Login as Admin
    clearSession();
    const adminPass = process.env.ADMIN_PASSWORD || '#1310SolokSelatan';
    await login('admin', adminPass);

    const kelolaRes = await fetchWithSession(`${BASE_URL}/kelola_user.php`);
    expect(kelolaRes.status).toBe(200);

    const doc = parseHtml(await kelolaRes.text());

    // + Daftarkan Admin Baru button is removed from page header
    expect(doc.querySelector('.page-header a')).toBeNull();
    expect(doc.body.textContent).not.toContain('Daftarkan Admin Baru');

    // All role badges in the Daftar Pengguna table must be USER only
    const roleBadges = Array.from(doc.querySelectorAll('.card:last-of-type .badge')).map(b => b.textContent?.trim());
    for (const badge of roleBadges) {
      expect(badge).toBe('USER');
    }
  });

  it('6. Logout clears session cleanly', async () => {
    const logoutRes = await fetchWithSession(`${BASE_URL}/logout.php`);
    expect(logoutRes.status).toBe(302);
    expect(logoutRes.headers.get('location')).toBe('login.php');

    const protectedRes = await fetchWithSession(`${BASE_URL}/index.php`);
    expect(protectedRes.status).toBe(302);
    expect(protectedRes.headers.get('location')).toBe('login.php');
  });

});


import { describe, it, expect, beforeAll } from 'vitest';
import { Window } from 'happy-dom';

const BASE_URL = 'http://localhost/Dashboard_Admin/public';

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
        headers['Cookie'] = Array.from(cookieJar.entries())
          .map(([k, v]) => `${k}=${v}`)
          .join('; ');
      }
      const response = await fetch(url, { ...options, headers, redirect: 'manual' });
      const setCookies = typeof response.headers.getSetCookie === 'function'
        ? response.headers.getSetCookie()
        : (response.headers.get('set-cookie') ? [response.headers.get('set-cookie')] : []);
      for (const sc of setCookies) {
        if (!sc) continue;
        const [pair] = sc.split(';');
        const eqIdx = pair.indexOf('=');
        if (eqIdx !== -1) {
          const key = pair.slice(0, eqIdx).trim();
          const val = pair.slice(eqIdx + 1).trim();
          cookieJar.set(key, val);
        }
      }
      return response;
    },
    getCookie() {
      return Array.from(cookieJar.entries()).map(([k, v]) => `${k}=${v}`).join('; ');
    }
  };
}

async function loginClient(client, username, password) {
  const getLogin = await client.fetch(`${BASE_URL}/login.php`);
  const docLogin = parseHtml(await getLogin.text());
  const csrfToken = docLogin.querySelector('input[name="csrf_token"]')?.value || '';

  return client.fetch(`${BASE_URL}/login.php`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: `username=${encodeURIComponent(username)}&password=${encodeURIComponent(password)}&csrf_token=${encodeURIComponent(csrfToken)}`
  });
}

describe('USER Role Requirements & Isolation', () => {
  let userClient;
  let adminClient;

  beforeAll(async () => {
    userClient = createSession();
    adminClient = createSession();

    // Login as Luce (USER)
    const userLogin = await loginClient(userClient, 'Luce', 'user123');
    expect(userLogin.status).toBe(302);
    expect(userLogin.headers.get('location')).toBe('index.php');

    // Login as Admin
    const adminPass = process.env.ADMIN_PASSWORD || '#1310SolokSelatan';
    const adminLogin = await loginClient(adminClient, 'admin', adminPass);
    expect(adminLogin.status).toBe(302);
  });

  it('1. USER sidebar shares unified layout (Dashboard, Kelola Layanan, Kategori, Pengaturan) and excludes Kelola User & Kelola Admin', async () => {
    const res = await userClient.fetch(`${BASE_URL}/index.php`);
    expect(res.status).toBe(200);

    const doc = parseHtml(await res.text());
    const menuLinks = Array.from(doc.querySelectorAll('.sidebar .menu li a')).map(a => a.textContent.trim());

    expect(menuLinks.some(t => t.includes('Dashboard'))).toBe(true);
    expect(menuLinks.some(t => t.includes('Kelola Layanan'))).toBe(true);
    expect(menuLinks.some(t => t.includes('Kategori'))).toBe(true);
    expect(menuLinks.some(t => t.includes('Pengaturan'))).toBe(true);

    // Kelola User and Kelola Admin MUST NOT be visible to USER
    expect(menuLinks.some(t => t.includes('Kelola User'))).toBe(false);
    expect(menuLinks.some(t => t.includes('Kelola Admin'))).toBe(false);
  });

  it('2. USER can access kategori.php, but is strictly blocked from kelola_admin.php and kelola_user.php', async () => {
    // USER can access kategori.php
    const katRes = await userClient.fetch(`${BASE_URL}/kategori.php`);
    expect(katRes.status).toBe(200);

    // USER is blocked from user and admin management
    const restrictedPages = [
      'kelola_admin.php',
      'kelola_user.php'
    ];

    for (const page of restrictedPages) {
      const res = await userClient.fetch(`${BASE_URL}/${page}`);
      expect(res.status).toBe(302);
      expect(res.headers.get('location')).toBe('index.php');
    }
  });

  it('3. USER Dashboard displays unified layout with role title, overview cards, and service catalog', async () => {
    const res = await userClient.fetch(`${BASE_URL}/index.php`);
    expect(res.status).toBe(200);

    const doc = parseHtml(await res.text());

    // Title should be Dashboard User
    expect(doc.querySelector('.header-left h1')?.textContent).toContain('Dashboard User');

    // Overview cards must exist
    expect(doc.body.textContent).toContain('Total Layanan');
    expect(doc.body.textContent).toContain('Kategori Layanan');

    // Quick links exist to layanan and kategori
    expect(doc.querySelector('a[href*="layanan.php"]')).not.toBeNull();
    expect(doc.querySelector('a[href*="kategori.php"]')).not.toBeNull();
  });

  it('4. Kelola Repositori Portal displays 7 categories, + Tambah Layanan button, and filter', async () => {
    const res = await userClient.fetch(`${BASE_URL}/repositori_portal.php`);
    expect(res.status).toBe(200);

    const doc = parseHtml(await res.text());

    // Header and Action Bar
    expect(doc.body.textContent).toContain('Kelola Repositori Portal');
    expect(doc.body.textContent).toContain('+ Tambah Layanan');
    expect(doc.querySelector('#filterKategoriSelect')).not.toBeNull();

    // 7 dynamic categories
    const categoryCards = doc.querySelectorAll('.kategori-card');
    expect(categoryCards.length).toBe(7);

    // Sidebar has Kelola Repositori Portal (Repositori Private dialihkan ke Portal Pegawai)
    const menuLinks = Array.from(doc.querySelectorAll('.sidebar .menu li a')).map(a => a.textContent.trim());
    expect(menuLinks.some(t => t.includes('Kelola Repositori Portal'))).toBe(true);
    expect(menuLinks.some(t => t.includes('Kelola Repositori Private'))).toBe(false);
  });

  it('5. USER can add service via Kelola Repositori Portal, stored with created_by = user_id', async () => {
    const timestamp = Date.now();
    const serviceName = `Layanan Portal Test ${timestamp}`;
    const serviceDesc = `Deskripsi layanan uji coba portal milik user Luce ${timestamp}`;
    const serviceUrl = `https://test-portal-${timestamp}.bps.go.id`;

    const body = new URLSearchParams({
      action: 'tambah_layanan',
      nama_layanan: serviceName,
      deskripsi_layanan: serviceDesc,
      url: serviceUrl,
      id_kategori: '1',
      akses: 'Private',
      vpn: 'No'
    }).toString();

    const addRes = await userClient.fetch(`${BASE_URL}/repositori_portal.php`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body
    });

    expect(addRes.status).toBe(200);
    const addHtml = await addRes.text();
    expect(addHtml).toContain(serviceName);

    // Check on Dashboard User summary
    const dashRes = await userClient.fetch(`${BASE_URL}/index.php`);
    const dashHtml = await dashRes.text();
    expect(dashHtml).toContain(serviceName);

    // Clean up created test service
    const match = addHtml.match(new RegExp(`data-id="(\\d+)"[^>]*data-nama="${serviceName}"`));
    if (match) {
      await userClient.fetch(`${BASE_URL}/repositori_portal.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
          action: 'hapus_layanan',
          id_layanan: match[1]
        }).toString()
      });
    }
  });

  it('6. USER can toggle favorite and see service in Kelola Repositori Private (Tab Favorite)', async () => {
    // 1. Toggle favorite on service ID 1
    const favRes = await userClient.fetch(`${BASE_URL}/api/user_repo.php`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: 'action=toggle_favorite&id_layanan=1'
    });
    expect(favRes.status).toBe(200);
    const favJson = await favRes.json();
    expect(favJson.success).toBe(true);

    // 2. Visit repositori_private.php tab favorite
    const privRes = await userClient.fetch(`${BASE_URL}/repositori_private.php?tab=favorite`);
    expect(privRes.status).toBe(200);
    const privHtml = await privRes.text();
    expect(privHtml).toContain('Kelola Repositori Private');
    expect(privHtml).toContain('Layanan Favorit');
  });

  it('7. USER can manage personal Website Links and Data Links in Repositori Private', async () => {
    const timestamp = Date.now();
    const siteName = `Web Solsel ${timestamp}`;
    const siteUrl = `https://solsel-${timestamp}.bps.go.id`;

    // Add website link
    const addLinkRes = await userClient.fetch(`${BASE_URL}/api/user_repo.php`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: `action=add_link&nama_website=${encodeURIComponent(siteName)}&url_website=${encodeURIComponent(siteUrl)}&deskripsi=Test`
    });
    expect(addLinkRes.status).toBe(200);
    const addLinkJson = await addLinkRes.json();
    expect(addLinkJson.success).toBe(true);
    const linkId = addLinkJson.data.id_link;

    // Check website tab
    const viewSites = await userClient.fetch(`${BASE_URL}/repositori_private.php?tab=website`);
    const viewSitesHtml = await viewSites.text();
    expect(viewSitesHtml).toContain(siteName);

    // Delete website link
    const delLinkRes = await userClient.fetch(`${BASE_URL}/api/user_repo.php`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: `action=delete_link&id_link=${linkId}`
    });
    expect(delLinkRes.status).toBe(200);
  });

  it('8. Unauthenticated access to private repository and API is strictly rejected', async () => {
    const unauthClient = createSession();

    // 1. Access repositori_private.php directly without login
    const privRes = await unauthClient.fetch(`${BASE_URL}/repositori_private.php`);
    expect(privRes.status).toBe(302);
    expect(privRes.headers.get('location')).toBe('login.php');

    // 2. Access repositori_portal.php directly without login
    const portalRes = await unauthClient.fetch(`${BASE_URL}/repositori_portal.php`);
    expect(portalRes.status).toBe(302);
    expect(portalRes.headers.get('location')).toBe('login.php');

    // 3. API request without session returns 401
    const apiRes = await unauthClient.fetch(`${BASE_URL}/api/user_repo.php`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: 'action=toggle_favorite&id_layanan=1'
    });
    expect(apiRes.status).toBe(401);
  });

  it('9. ADMIN continues to see all services and retains full administrative access', async () => {
    const adminDashRes = await adminClient.fetch(`${BASE_URL}/index.php`);
    expect(adminDashRes.status).toBe(200);

    const doc = parseHtml(await adminDashRes.text());
    expect(doc.querySelector('.header-left h1')?.textContent).toContain('Dashboard Admin');

    // Admin has Kategori, Kelola User, Kelola Admin
    const menuLinks = Array.from(doc.querySelectorAll('.sidebar .menu li a')).map(a => a.textContent.trim());
    expect(menuLinks).toContain('Kategori');
    expect(menuLinks).toContain('Kelola User');
    expect(menuLinks).toContain('Kelola Admin');

    // Admin can access kategori.php
    const katRes = await adminClient.fetch(`${BASE_URL}/kategori.php`);
    expect(katRes.status).toBe(200);
  });

  it('10. Strict User Data Privacy & Cross-User Isolation (User B cannot see/edit/delete User A data)', async () => {
    // 1. User A (Luce) adds a private website link and private data link
    const timestamp = Date.now();
    const aSiteName = `Private Site User A ${timestamp}`;
    const aDocName = `Private Doc User A ${timestamp}`;

    const addLinkRes = await userClient.fetch(`${BASE_URL}/api/user_repo.php`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: `action=add_link&nama_website=${encodeURIComponent(aSiteName)}&url_website=https://bps.go.id&deskripsi=SecretA`
    });
    const addLinkJson = await addLinkRes.json();
    const linkIdA = addLinkJson.data.id_link;

    const addDocRes = await userClient.fetch(`${BASE_URL}/api/user_repo.php`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: `action=add_data_link&nama_dokumen=${encodeURIComponent(aDocName)}&url_dokumen=https://bps.go.id/data&jenis_dokumen=Excel`
    });
    const addDocJson = await addDocRes.json();
    const docIdA = addDocJson.data.id_data_link;

    // 2. User B (andrefanni) logs in
    const userBClient = createSession();
    const userBLogin = await loginClient(userBClient, 'andrefanni', 'user123');
    expect(userBLogin.status).toBe(302);

    // 3. User B views their private repository website tab -> MUST NOT see User A's links
    const bViewRes = await userBClient.fetch(`${BASE_URL}/repositori_private.php?tab=website`);
    const bViewHtml = await bViewRes.text();
    expect(bViewHtml).not.toContain(aSiteName);

    // 4. User B attempts to edit User A's link -> HTTP 403 Forbidden
    const bEditRes = await userBClient.fetch(`${BASE_URL}/api/user_repo.php`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: `action=edit_link&id_link=${linkIdA}&nama_website=Hacked&url_website=https://hacked.com`
    });
    expect(bEditRes.status).toBe(403);

    // 5. User B attempts to delete User A's link -> HTTP 403 Forbidden
    const bDelRes = await userBClient.fetch(`${BASE_URL}/api/user_repo.php`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: `action=delete_link&id_link=${linkIdA}`
    });
    expect(bDelRes.status).toBe(403);

    // 6. User B attempts to edit User A's data link -> HTTP 403 Forbidden
    const bEditDocRes = await userBClient.fetch(`${BASE_URL}/api/user_repo.php`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: `action=edit_data_link&id_data_link=${docIdA}&nama_dokumen=Hacked&url_dokumen=https://hacked.com`
    });
    expect(bEditDocRes.status).toBe(403);

    // 7. User B attempts to delete User A's data link -> HTTP 403 Forbidden
    const bDelDocRes = await userBClient.fetch(`${BASE_URL}/api/user_repo.php`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: `action=delete_data_link&id_data_link=${docIdA}`
    });
    expect(bDelDocRes.status).toBe(403);

    // 8. User A cleans up their link
    await userClient.fetch(`${BASE_URL}/api/user_repo.php`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: `action=delete_link&id_link=${linkIdA}`
    });
    await userClient.fetch(`${BASE_URL}/api/user_repo.php`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: `action=delete_data_link&id_data_link=${docIdA}`
    });
  });

  it('11. USER can manage personal Data Links (Sheets, Docs, Excel, PDF)', async () => {
    const timestamp = Date.now();
    const docName = `Dataset Pertanian ${timestamp}`;
    const docUrl = `https://docs.google.com/spreadsheets/d/test-${timestamp}`;

    // Add Data Link
    const addRes = await userClient.fetch(`${BASE_URL}/api/user_repo.php`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: `action=add_data_link&nama_dokumen=${encodeURIComponent(docName)}&url_dokumen=${encodeURIComponent(docUrl)}&jenis_dokumen=Google+Sheets&deskripsi=Uji+Coba`
    });
    expect(addRes.status).toBe(200);
    const addJson = await addRes.json();
    expect(addJson.success).toBe(true);
    const dataId = addJson.data.id_data_link;

    // View in Private Repository
    const viewRes = await userClient.fetch(`${BASE_URL}/repositori_private.php?tab=data`);
    expect(viewRes.status).toBe(200);
    const viewHtml = await viewRes.text();
    expect(viewHtml).toContain(docName);
    expect(viewHtml).toContain('Google Sheets');

    // Clean up
    const delRes = await userClient.fetch(`${BASE_URL}/api/user_repo.php`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: `action=delete_data_link&id_data_link=${dataId}`
    });
    expect(delRes.status).toBe(200);
  });

  it('11. Admin also has their own Repositori Private and Repositori Portal access in sidebar and can manage personal repository items', async () => {
    // 1. Admin sidebar contains both Repositori Portal and Repositori Private
    const adminDashRes = await adminClient.fetch(`${BASE_URL}/index.php`);
    expect(adminDashRes.status).toBe(200);
    const doc = parseHtml(await adminDashRes.text());
    const menuLinks = Array.from(doc.querySelectorAll('.sidebar .menu li a')).map(a => a.textContent.trim());

    expect(menuLinks.some(t => t.includes('Kelola Repositori Portal'))).toBe(true);
    expect(menuLinks.some(t => t.includes('Kelola Repositori Private'))).toBe(false);

    // 2. Admin can access repositori_private.php and repositori_portal.php
    const privRes = await adminClient.fetch(`${BASE_URL}/repositori_private.php`);
    expect(privRes.status).toBe(200);
    const privDoc = parseHtml(await privRes.text());
    expect(privDoc.querySelector('.header-left h1')?.textContent).toContain('Kelola Repositori Private');

    const portalRes = await adminClient.fetch(`${BASE_URL}/repositori_portal.php`);
    expect(portalRes.status).toBe(200);

    // 3. Admin adds a private link in their personal repository
    const timestamp = Date.now();
    const adminLinkName = `Admin Personal Link ${timestamp}`;
    const addRes = await adminClient.fetch(`${BASE_URL}/api/user_repo.php`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: `action=add_link&nama_website=${encodeURIComponent(adminLinkName)}&url_website=https://admin.bps.go.id&deskripsi=AdminInternal`
    });
    expect(addRes.status).toBe(200);
    const addJson = await addRes.json();
    expect(addJson.success).toBe(true);
    const linkId = addJson.data.id_link;

    // 4. Admin sees it in repositori_private.php?tab=links
    const viewRes = await adminClient.fetch(`${BASE_URL}/repositori_private.php?tab=links`);
    expect(viewRes.status).toBe(200);
    const viewHtml = await viewRes.text();
    expect(viewHtml).toContain(adminLinkName);

    // 5. User Luce CANNOT see Admin's private link
    const userViewRes = await userClient.fetch(`${BASE_URL}/repositori_private.php?tab=links`);
    const userViewHtml = await userViewRes.text();
    expect(userViewHtml).not.toContain(adminLinkName);

    // 6. Clean up
    const cleanRes = await adminClient.fetch(`${BASE_URL}/api/user_repo.php`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: `action=delete_link&id_link=${linkId}`
    });
    expect(cleanRes.status).toBe(200);
  });

  it('13. USER is strictly forbidden from modifying categories via tambah_kategori, edit_kategori, and hapus_kategori (RBAC protection)', async () => {
    // 1. Direct GET to tambah_kategori.php as USER -> redirects to kategori.php
    const tambahRes = await userClient.fetch(`${BASE_URL}/tambah_kategori.php`);
    expect(tambahRes.status).toBe(302);
    expect(tambahRes.headers.get('location')).toBe('kategori.php');

    // 2. Direct GET to edit_kategori.php as USER -> redirects to kategori.php
    const editRes = await userClient.fetch(`${BASE_URL}/edit_kategori.php?id=1`);
    expect(editRes.status).toBe(302);
    expect(editRes.headers.get('location')).toBe('kategori.php');

    // 3. Direct POST to hapus_kategori.php as USER with JSON header -> HTTP 403 Forbidden
    const hapusRes = await userClient.fetch(`${BASE_URL}/hapus_kategori.php`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
        'Accept': 'application/json'
      },
      body: 'id=1'
    });
    expect(hapusRes.status).toBe(403);
    const hapusJson = await hapusRes.json();
    expect(hapusJson.success).toBe(false);
    expect(hapusJson.message).toContain('Hanya Administrator');
  });
});


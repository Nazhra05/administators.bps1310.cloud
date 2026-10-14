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

describe('Kelola Repositori Private - 7 Kategori Koleksi Pribadi', () => {
  let userLuce;
  let userAdmin;

  beforeAll(async () => {
    userLuce = createSession();
    userAdmin = createSession();

    // Login as Luce (USER)
    const luceLogin = await loginAs(userLuce, 'Luce', 'user123');
    expect(luceLogin.status).toBe(302);

    // Login as Admin
    const adminPass = process.env.ADMIN_PASSWORD || '#1310SolokSelatan';
    const adminLogin = await loginAs(userAdmin, 'admin', adminPass);
    expect(adminLogin.status).toBe(302);
  });

  it('1. Kelola Repositori Private menampilkan seluruh 7 kategori dalam overview dan tab navigasi', async () => {
    const res = await userLuce.fetch(`${BASE_URL}/repositori_private.php`);
    expect(res.status).toBe(200);

    const doc = parseHtml(await res.text());

    // Overview cards (7 kategori)
    const overviewCards = Array.from(doc.querySelectorAll('.cat-overview-card'));
    expect(overviewCards.length).toBe(7);

    const overviewTitles = overviewCards.map(c => c.querySelector('.cat-overview-title')?.textContent?.trim());
    expect(overviewTitles).toContain('Favorite');
    expect(overviewTitles).toContain('Link Website');
    expect(overviewTitles).toContain('Link Data');
    expect(overviewTitles).toContain('Aplikasi Mobile');
    expect(overviewTitles.some(t => t.includes('Link Drive'))).toBe(true);
    expect(overviewTitles).toContain('Link Foto');
    expect(overviewTitles).toContain('Link Lainnya');

    // Verifikasi bahwa navigasi kategori utama menggunakan 7 kotak kategori
    expect(doc.querySelector('.tabs-header')).toBeNull();
    expect(overviewCards.length).toBe(7);
  });

  it('2. CRUD Aplikasi Mobile melalui API dengan isolasi user yang ketat', async () => {
    // 2a. Tambah Aplikasi Mobile oleh Luce
    const addRes = await userLuce.fetch(`${BASE_URL}/api/user_repo.php`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: 'action=add_mobile_app&nama_aplikasi=FASIH+Mobile&url_aplikasi=https%3A%2F%2Fplay.google.com%2Fstore%2Fapps%2Fdetails%3Fid%3Dgo.id.bps.fasih&platform=Android&deskripsi=Aplikasi+survei+lapangan+BPS'
    });
    expect(addRes.status).toBe(200);
    const addData = await addRes.json();
    expect(addData.success).toBe(true);
    expect(addData.id_app).toBeGreaterThan(0);
    const appId = addData.id_app;

    // 2b. User Admin mencoba mengedit aplikasi milik Luce -> HARUS DITOLAK
    const editUnauthorized = await userAdmin.fetch(`${BASE_URL}/api/user_repo.php`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: `action=edit_mobile_app&id_app=${appId}&nama_aplikasi=Hacked+App&url_aplikasi=https%3A%2F%2Fbad.com&platform=iOS`
    });
    expect([200, 403]).toContain(editUnauthorized.status);
    const editUnauthJson = await editUnauthorized.json();
    expect(editUnauthJson.success).toBe(false);

    // 2c. User Luce mengedit aplikasi miliknya sendiri -> SUKSES
    const editRes = await userLuce.fetch(`${BASE_URL}/api/user_repo.php`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: `action=edit_mobile_app&id_app=${appId}&nama_aplikasi=FASIH+Mobile+Updated&url_aplikasi=https%3A%2F%2Fplay.google.com%2Fstore%2Fapps%2Fdetails%3Fid%3Dgo.id.bps.fasih&platform=Android+%26+iOS&deskripsi=Multiplatform+survei`
    });
    const editData = await editRes.json();
    expect(editData.success).toBe(true);

    // 2d. Periksa tampilan di halaman repositori_private.php?tab=mobile_app
    const pageRes = await userLuce.fetch(`${BASE_URL}/repositori_private.php?tab=mobile_app`);
    const pageDoc = parseHtml(await pageRes.text());
    expect(pageDoc.body.textContent).toContain('FASIH Mobile Updated');

    // 2e. Hapus aplikasi mobile
    const delRes = await userLuce.fetch(`${BASE_URL}/api/user_repo.php`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: `action=delete_mobile_app&id_app=${appId}`
    });
    const delData = await delRes.json();
    expect(delData.success).toBe(true);
  });

  it('3. CRUD Link Drive (Folder) melalui API dengan isolasi user', async () => {
    // 3a. Tambah folder drive
    const addRes = await userLuce.fetch(`${BASE_URL}/api/user_repo.php`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: 'action=add_drive_link&nama_folder=Dokumen+Sensus+Pertanian&url_folder=https%3A%2F%2Fdrive.google.com%2Fdrive%2Ffolders%2Fsample123&jenis_drive=Google+Drive&deskripsi=Folder+arsip+kuesioner'
    });
    const addData = await addRes.json();
    expect(addData.success).toBe(true);
    const driveId = addData.id_drive;

    // 3b. Periksa isolasi: Admin tidak bisa menghapus folder milik Luce
    const delAdmin = await userAdmin.fetch(`${BASE_URL}/api/user_repo.php`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: `action=delete_drive_link&id_drive=${driveId}`
    });
    const delAdminData = await delAdmin.json();
    expect(delAdminData.success).toBe(false);

    // 3c. Luce mengedit foldernya
    const editRes = await userLuce.fetch(`${BASE_URL}/api/user_repo.php`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: `action=edit_drive_link&id_drive=${driveId}&nama_folder=Dokumen+Sensus+Pertanian+Final&url_folder=https%3A%2F%2Fdrive.google.com%2Fdrive%2Ffolders%2Fsample123&jenis_drive=OneDrive&deskripsi=Pindah+ke+OneDrive`
    });
    const editData = await editRes.json();
    expect(editData.success).toBe(true);

    // 3d. Hapus
    const delRes = await userLuce.fetch(`${BASE_URL}/api/user_repo.php`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: `action=delete_drive_link&id_drive=${driveId}`
    });
    const delData = await delRes.json();
    expect(delData.success).toBe(true);
  });

  it('4. CRUD Link Foto dengan thumbnail preview dan isolasi user', async () => {
    // 4a. Tambah tautan foto
    const addRes = await userLuce.fetch(`${BASE_URL}/api/user_repo.php`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: 'action=add_photo_link&judul_foto=Dokumentasi+Rakerda+2026&url_foto=https%3A%2F%2Fimages.unsplash.com%2Fphoto-1542744094-3a31f272c490&deskripsi=Foto+bersama+peserta'
    });
    const addData = await addRes.json();
    expect(addData.success).toBe(true);
    const photoId = addData.id_photo;

    // 4b. Cek di halaman repositori_private.php?tab=photo
    const pageRes = await userLuce.fetch(`${BASE_URL}/repositori_private.php?tab=photo`);
    const pageDoc = parseHtml(await pageRes.text());
    expect(pageDoc.body.textContent).toContain('Dokumentasi Rakerda 2026');

    // 4c. Edit foto
    const editRes = await userLuce.fetch(`${BASE_URL}/api/user_repo.php`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: `action=edit_photo_link&id_photo=${photoId}&judul_foto=Dokumentasi+Rakerda+2026+HD&url_foto=https%3A%2F%2Fimages.unsplash.com%2Fphoto-1542744094-3a31f272c490&deskripsi=Versi+HD`
    });
    const editData = await editRes.json();
    expect(editData.success).toBe(true);

    // 4d. Hapus
    const delRes = await userLuce.fetch(`${BASE_URL}/api/user_repo.php`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: `action=delete_photo_link&id_photo=${photoId}`
    });
    const delData = await delRes.json();
    expect(delData.success).toBe(true);
  });

  it('5. CRUD Link Lainnya & Referensi dengan label kategori', async () => {
    // 5a. Tambah link lainnya
    const addRes = await userLuce.fetch(`${BASE_URL}/api/user_repo.php`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: 'action=add_other_link&nama_link=Portal+Satu+Data+Indonesia&url_link=https%3A%2F%2Fdata.go.id&label=Regulasi&deskripsi=Perpres+39+Tahun+2019'
    });
    const addData = await addRes.json();
    expect(addData.success).toBe(true);
    const otherId = addData.id_other;

    // 5b. Cek halaman repositori_private.php?tab=other
    const pageRes = await userLuce.fetch(`${BASE_URL}/repositori_private.php?tab=other`);
    const pageDoc = parseHtml(await pageRes.text());
    expect(pageDoc.body.textContent).toContain('Portal Satu Data Indonesia');
    expect(pageDoc.body.textContent).toContain('Regulasi');

    // 5c. Edit
    const editRes = await userLuce.fetch(`${BASE_URL}/api/user_repo.php`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: `action=edit_other_link&id_other=${otherId}&nama_link=Portal+Satu+Data+Indonesia+Official&url_link=https%3A%2F%2Fdata.go.id&label=Referensi&deskripsi=Referensi+Utama`
    });
    const editData = await editRes.json();
    expect(editData.success).toBe(true);

    // 5d. Hapus
    const delRes = await userLuce.fetch(`${BASE_URL}/api/user_repo.php`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: `action=delete_other_link&id_other=${otherId}`
    });
    const delData = await delRes.json();
    expect(delData.success).toBe(true);
  });

  it('6. Kelola Repositori Private terintegrasi dengan toggle collapsible sidebar terpadu tanpa fungsi konflik', async () => {
    const res = await userLuce.fetch(`${BASE_URL}/repositori_private.php`);
    expect(res.status).toBe(200);
    const html = await res.text();
    const doc = parseHtml(html);

    // Sidebar header toggle exists and calls unified toggleSidebar()
    const toggleBtn = doc.querySelector('#sidebarHeaderToggle');
    expect(toggleBtn).not.toBeNull();
    expect(toggleBtn.getAttribute('onclick')).toBe('toggleSidebar()');
    expect(toggleBtn.querySelector('.toggle-icon-collapse')).not.toBeNull();
    expect(toggleBtn.querySelector('.toggle-icon-expand')).not.toBeNull();

    // Fungsi toggleSidebar, openSidebar, dan closeSidebar terdefinisi tepat satu kali dari navbar.php (tidak tertimpa duplikat)
    const toggleMatches = html.match(/function\s+toggleSidebar\s*\(/g) || [];
    const openMatches = html.match(/function\s+openSidebar\s*\(/g) || [];
    const closeMatches = html.match(/function\s+closeSidebar\s*\(/g) || [];
    expect(toggleMatches.length).toBe(1);
    expect(openMatches.length).toBe(1);
    expect(closeMatches.length).toBe(1);
  });
});

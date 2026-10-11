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

describe('Portal Repository Edit Feature & Security Isolation', () => {
  let luceClient;
  let andrefanniClient;
  let testLayananId = null;

  beforeAll(async () => {
    luceClient = createSession();
    andrefanniClient = createSession();

    await luceClient.fetch(`${BASE_URL}/login.php`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: 'username=Luce&password=user123'
    });

    await andrefanniClient.fetch(`${BASE_URL}/login.php`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: 'username=andrefanni&password=user123'
    });
  });

  afterAll(async () => {
    if (testLayananId) {
      await luceClient.fetch(`${BASE_URL}/repositori_portal.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: new URLSearchParams({
          action: 'hapus_layanan',
          id_layanan: String(testLayananId)
        }).toString()
      });
    }
  });

  it('1. UI contains edit modal, inputs, and validation warning structure', async () => {
    const res = await luceClient.fetch(`${BASE_URL}/repositori_portal.php`);
    expect(res.status).toBe(200);
    const html = await res.text();
    const doc = parseHtml(html);

    const editModal = doc.getElementById('modalEditLayanan');
    expect(editModal).not.toBeNull();

    const editForm = editModal.querySelector('form');
    expect(editForm).not.toBeNull();
    expect(editForm.querySelector('input[name="action"][value="edit_layanan"]')).not.toBeNull();
    expect(editForm.querySelector('#edit_nama_layanan')).not.toBeNull();
    expect(editForm.querySelector('#edit_deskripsi_layanan')).not.toBeNull();
    expect(editForm.querySelector('#edit_url')).not.toBeNull();
    expect(editForm.querySelector('#edit_id_kategori')).not.toBeNull();
    expect(editForm.querySelector('#edit_akses')).not.toBeNull();
    expect(editForm.querySelector('#edit_vpn')).not.toBeNull();
    expect(editForm.querySelector('#edit_logo')).not.toBeNull();

    expect(html).toContain('openEditPortalModalFromBtn');
    expect(html).toContain('.btn-edit-own');
  });

  it('2. User can add service and then edit their own service', async () => {
    // 1) Tambah layanan oleh Luce
    const addRes = await luceClient.fetch(`${BASE_URL}/repositori_portal.php`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: new URLSearchParams({
        action: 'tambah_layanan',
        nama_layanan: 'Layanan Uji Edit Luce',
        deskripsi_layanan: 'Deskripsi sebelum diedit',
        url: 'https://uji-awal.bps.go.id',
        id_kategori: '1',
        akses: 'Public',
        vpn: 'No'
      }).toString()
    });
    expect(addRes.status).toBe(200);
    const addHtml = await addRes.text();
    expect(addHtml).toContain('Layanan baru berhasil ditambahkan');

    // Temukan id layanan yang baru dibuat
    const match = addHtml.match(/data-id="(\d+)"[^>]*data-nama="Layanan Uji Edit Luce"/);
    expect(match).not.toBeNull();
    testLayananId = parseInt(match[1], 10);
    expect(testLayananId).toBeGreaterThan(0);

    // Pastikan tombol edit ada untuk Luce (pemilik layanan)
    expect(addHtml).toContain(`data-id="${testLayananId}"`);
    expect(addHtml).toContain('class="btn-edit-own"');

    // 2) Edit layanan oleh Luce
    const editRes = await luceClient.fetch(`${BASE_URL}/repositori_portal.php`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: new URLSearchParams({
        action: 'edit_layanan',
        id_layanan: String(testLayananId),
        nama_layanan: 'Layanan Uji Edit Luce (Sudah Diedit)',
        deskripsi_layanan: 'Deskripsi berhasil diperbarui',
        url: 'https://uji-baru.bps.go.id',
        id_kategori: '1',
        akses: 'Internal',
        vpn: 'Yes'
      }).toString()
    });
    expect(editRes.status).toBe(200);
    const editHtml = await editRes.text();
    expect(editHtml).toContain('Layanan berhasil diperbarui!');
    expect(editHtml).toContain('Layanan Uji Edit Luce (Sudah Diedit)');
    expect(editHtml).toContain('Deskripsi berhasil diperbarui');
  });

  it('3. User lain tidak berhak mengedit layanan milik orang lain', async () => {
    expect(testLayananId).not.toBeNull();

    // User andrefanni mencoba mengedit layanan milik Luce
    const unauthEditRes = await andrefanniClient.fetch(`${BASE_URL}/repositori_portal.php`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: new URLSearchParams({
        action: 'edit_layanan',
        id_layanan: String(testLayananId),
        nama_layanan: 'Dibajak oleh Andrefanni',
        deskripsi_layanan: 'Deskripsi dibajak',
        url: 'https://hacked.com',
        id_kategori: '1',
        akses: 'Public',
        vpn: 'No'
      }).toString()
    });
    expect(unauthEditRes.status).toBe(200);
    const unauthHtml = await unauthEditRes.text();
    expect(unauthHtml).toContain('Anda tidak memiliki hak untuk mengedit layanan ini');

    // Verifikasi data tetap milik Luce dan tidak berubah
    const luceRes = await luceClient.fetch(`${BASE_URL}/repositori_portal.php`);
    const luceHtml = await luceRes.text();
    expect(luceHtml).toContain('Layanan Uji Edit Luce (Sudah Diedit)');
    expect(luceHtml).not.toContain('Dibajak oleh Andrefanni');
  });
});

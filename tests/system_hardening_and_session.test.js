import { describe, it, expect } from 'vitest';

const BASE_URL = 'http://localhost/Dashboard_Admin/public';
const API_KEY = process.env.API_KEY || 'Hashtag_1310SolokSelatan_PortalInternal_dan_DashboardAdmin_V1_BPSSolokSelatan_KotoTinggi';

describe('System Hardening, Session Validation & Catalog Enhancement', () => {

  it('1. Endpoint katalog layanan mengembalikan unified envelope dengan properti status dan success', async () => {
    const res = await fetch(`${BASE_URL}/api/layanan.php`, {
      headers: {
        'X-API-KEY': API_KEY,
        'Accept': 'application/json'
      }
    });

    expect(res.status).toBe(200);
    const body = await res.json();
    expect([true, 'success']).toContain(body.status);
    expect(body.success).toBe(true);
    expect(Array.isArray(body.data)).toBe(true);
  });

  it('2. Endpoint katalog layanan mendukung paginasi data dan menyertakan metadata meta', async () => {
    const res = await fetch(`${BASE_URL}/api/layanan.php?page=1&limit=3`, {
      headers: {
        'X-API-KEY': API_KEY,
        'Accept': 'application/json'
      }
    });

    expect(res.status).toBe(200);
    const body = await res.json();
    expect(body.success).toBe(true);
    expect(body.meta).toBeDefined();
    expect(body.meta.page).toBe(1);
    expect(body.meta.limit).toBe(3);
    expect(typeof body.meta.total).toBe('number');
    expect(typeof body.meta.total_pages).toBe('number');
    expect(body.data.length).toBeLessThanOrEqual(3);
  });

  it('3. Endpoint kategori mengembalikan envelope standar (status dan success)', async () => {
    const res = await fetch(`${BASE_URL}/api/kategori.php`, {
      headers: {
        'X-API-KEY': API_KEY,
        'Accept': 'application/json'
      }
    });

    expect(res.status).toBe(200);
    const body = await res.json();
    expect([true, 'success']).toContain(body.status);
    expect(body.success).toBe(true);
    expect(Array.isArray(body.data)).toBe(true);
  });

  it('4. Endpoint check_session.php memvalidasi akun admin utama berstatus approved', async () => {
    const res = await fetch(`${BASE_URL}/api/auth/check_session.php?id_user=1`, {
      headers: {
        'X-API-KEY': API_KEY,
        'Accept': 'application/json'
      }
    });

    expect(res.status).toBe(200);
    const body = await res.json();
    expect(body.success).toBe(true);
    expect(body.data.valid).toBe(true);
    expect(body.data.revoked).toBe(false);
    expect(body.data.status).toBe('approved');
  });

  it('5. Endpoint check_session.php menolak dan menandai revoked jika id_user tidak ditemukan', async () => {
    const res = await fetch(`${BASE_URL}/api/auth/check_session.php?id_user=999999`, {
      headers: {
        'X-API-KEY': API_KEY,
        'Accept': 'application/json'
      }
    });

    expect(res.status).toBe(404);
    const body = await res.json();
    expect(body.success).toBe(false);
    expect(body.data.valid).toBe(false);
    expect(body.data.revoked).toBe(true);
    expect(body.data.reason).toBe('user_not_found');
  });

  it('6. Halaman resend_verification.php dapat diakses dan merender form input email', async () => {
    const res = await fetch(`${BASE_URL}/resend_verification.php`);
    expect(res.status).toBe(200);
    const html = await res.text();
    expect(html).toContain('Kirim Ulang Verifikasi');
    expect(html).toContain('name="email"');
    expect(html).toContain('name="csrf_token"');
  });

});

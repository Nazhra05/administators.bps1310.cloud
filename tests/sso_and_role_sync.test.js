import { describe, it, expect, beforeAll } from 'vitest';
import crypto from 'crypto';

const BASE_URL = 'http://localhost/Dashboard_Admin/public';
const API_KEY = process.env.API_KEY || 'Hashtag_1310SolokSelatan_PortalInternal_dan_DashboardAdmin_V1_BPSSolokSelatan_KotoTinggi';

function createSignedSsoToken(payload, secret = API_KEY) {
  const payloadB64 = Buffer.from(JSON.stringify(payload)).toString('base64url');
  const signature = crypto.createHmac('sha256', secret).update(payloadB64).digest('base64url');
  return `${payloadB64}.${signature}`;
}

describe('SSO & Cross-App Role Synchronization Tests', () => {
  let testUser = { id_admin: 1, username: 'admin', role: 'ADMIN' };

  beforeAll(async () => {
    try {
      const res = await fetch(`${BASE_URL}/api/auth/loginPortal.php`, {
        method: 'POST',
        headers: {
          'X-API-KEY': API_KEY,
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          identity: 'Luce',
          password: 'user123',
        }),
      });
      const json = await res.json();
      if (json && json.status && json.data) {
        testUser = json.data;
      }
    } catch {}
  });

  it('1. /sso.php tanpa token menolak dan mengalihkan ke login.php', async () => {
    const res = await fetch(`${BASE_URL}/sso.php`, { redirect: 'manual' });
    expect([302, 303, 307]).toContain(res.status);
    expect(res.headers.get('location')).toContain('login.php');
  });

  it('2. /sso.php dengan signature palsu ditolak keras dan dialihkan ke login.php', async () => {
    const now = Math.floor(Date.now() / 1000);
    const forgedToken = createSignedSsoToken({
      userId: testUser.id_admin,
      username: testUser.username,
      role: testUser.role,
      target: 'layanan.php',
      iat: now,
      exp: now + 60,
    }, 'KUNCI_PALSU_YANG_SALAH');

    const res = await fetch(`${BASE_URL}/sso.php?token=${encodeURIComponent(forgedToken)}`, {
      redirect: 'manual',
    });
    expect([302, 303, 307]).toContain(res.status);
    expect(res.headers.get('location')).toContain('login.php');
  });

  it('3. /sso.php dengan token sah menginisialisasi sesi sesuai akun portal dan mengalihkan ke layanan.php', async () => {
    const now = Math.floor(Date.now() / 1000);
    const validToken = createSignedSsoToken({
      userId: testUser.id_admin,
      username: testUser.username,
      role: testUser.role,
      target: 'layanan.php',
      iat: now,
      exp: now + 60,
    });

    const ssoRes = await fetch(`${BASE_URL}/sso.php?token=${encodeURIComponent(validToken)}&target=layanan.php`, {
      redirect: 'manual',
    });

    expect([302, 303, 307]).toContain(ssoRes.status);
    expect(ssoRes.headers.get('location')).toBe('layanan.php');

    // Ambil PHPSESSID dari cookie yang di-set oleh sso.php
    const setCookies = typeof ssoRes.headers.getSetCookie === 'function'
      ? ssoRes.headers.getSetCookie()
      : [ssoRes.headers.get('set-cookie')].filter(Boolean);
    const lastCookie = setCookies.length > 0 ? setCookies[setCookies.length - 1] : '';
    const phpsessid = lastCookie ? lastCookie.split(';')[0] : '';

    // Sekarang gunakan session cookie tersebut untuk mengakses layanan.php
    const layananRes = await fetch(`${BASE_URL}/layanan.php`, {
      headers: {
        cookie: phpsessid,
      },
      redirect: 'manual',
    });

    // Harus berstatus 200 OK (bukan 302 redirect ke login.php)
    expect(layananRes.status).toBe(200);
    const html = await layananRes.text();

    if (testUser.role === 'USER') {
      // Verifikasi bahwa halaman TIDAK menampilkan menu kelola admin/user (karena role-nya USER)
      expect(html).not.toContain('kelola_admin.php');
    }
    expect(html).toContain('Tambah Layanan');
  });

  it('4. Akses langsung layanan.php dengan cookie bps_solsel_session otomatis mensinkronkan sesi user aktif', async () => {
    const now = Math.floor(Date.now() / 1000);
    const portalSessionCookie = createSignedSsoToken({
      sessionId: crypto.randomUUID(),
      userId: testUser.id_admin,
      username: testUser.username,
      role: testUser.role,
      iat: now,
      exp: now + 3600,
    });

    const res = await fetch(`${BASE_URL}/layanan.php`, {
      headers: {
        cookie: `bps_solsel_session=${portalSessionCookie}`,
      },
      redirect: 'manual',
    });

    // Harus berhasil masuk ke layanan.php tanpa terlempar ke login.php
    expect(res.status).toBe(200);
  });
});

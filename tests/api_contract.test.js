import { describe, it, expect } from 'vitest';
import fs from 'node:fs';
import path from 'node:path';

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

describe('Characterization Tests — API Contract & Access Control (Fase 2)', () => {

  describe('1. API Key Access Control & Rejection Tests', () => {
    it('rejects GET /api/layanan.php without X-API-KEY header (401)', async () => {
      const res = await fetch(`${BASE_URL}/api/layanan.php`);
      expect(res.status).toBe(401);
      const json = await res.json();
      expect(json.status).toBe(false);
      expect(json.message).toBe('API key wajib dikirim');
    });

    it('rejects GET /api/layanan.php with invalid X-API-KEY header (401)', async () => {
      const res = await fetch(`${BASE_URL}/api/layanan.php`, {
        headers: { 'X-API-KEY': 'INVALID_API_KEY_12345' }
      });
      expect(res.status).toBe(401);
      const json = await res.json();
      expect(json.status).toBe(false);
      expect(json.message).toBe('API key tidak valid');
    });

    it('rejects GET /api/kategori.php without X-API-KEY header (401)', async () => {
      const res = await fetch(`${BASE_URL}/api/kategori.php`);
      expect(res.status).toBe(401);
      const json = await res.json();
      expect(json.status).toBe(false);
      expect(json.message).toBe('API key wajib dikirim');
    });

    it('rejects GET /api/website.php without X-API-KEY header (401)', async () => {
      const res = await fetch(`${BASE_URL}/api/website.php`);
      expect(res.status).toBe(401);
      const json = await res.json();
      expect(json.status).toBe(false);
      expect(json.message).toBe('API key wajib dikirim');
    });

    it('rejects POST /api/auth/loginPortal.php without X-API-KEY header (401)', async () => {
      const res = await fetch(`${BASE_URL}/api/auth/loginPortal.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ identity: 'admin', password: 'admin123' })
      });
      expect(res.status).toBe(401);
      const json = await res.json();
      expect(json.status).toBe(false);
      expect(json.message).toBe('API key wajib dikirim');
    });
  });

  describe('2. Contract Schema Lock — Layanan & Kategori APIs', () => {
    it('returns exact JSON structure for GET /api/layanan.php', async () => {
      const res = await fetch(`${BASE_URL}/api/layanan.php`, {
        headers: { 'X-API-KEY': API_KEY }
      });
      expect(res.status).toBe(200);
      const json = await res.json();
      expect(json.status).toBe(true);
      expect(json.message).toBe('Data layanan berhasil diambil');
      expect(Array.isArray(json.data)).toBe(true);
      expect(json.data.length).toBeGreaterThan(0);

      const item = json.data[0];
      expect(typeof item.id_layanan).toBe('number');
      expect(typeof item.id_kategori).toBe('number');
      expect(typeof item.akses).toBe('string');
      expect(['Internal', 'Public']).toContain(item.akses);
      expect(typeof item.vpn).toBe('string');
      expect(['Yes', 'No']).toContain(item.vpn);
      expect(typeof item.nama_layanan).toBe('string');
      expect(typeof item.deskripsi_layanan).toBe('string');
      expect(typeof item.url).toBe('string');
      expect(typeof item.logo).toBe('string');
      expect(typeof item.nama_kategori).toBe('string');
    });

    it('returns exact JSON structure for GET /api/layanan.php?id=1', async () => {
      const res = await fetch(`${BASE_URL}/api/layanan.php?id=1`, {
        headers: { 'X-API-KEY': API_KEY }
      });
      expect(res.status).toBe(200);
      const json = await res.json();
      expect(json.status).toBe(true);
      expect(json.data).not.toBeNull();
      expect(json.data.id_layanan).toBe(1);
      expect(typeof json.data.nama_layanan).toBe('string');
      expect(typeof json.data.url).toBe('string');
    });

    it('returns 404 for GET /api/layanan.php?id=999999 (non-existent id)', async () => {
      const res = await fetch(`${BASE_URL}/api/layanan.php?id=999999`, {
        headers: { 'X-API-KEY': API_KEY }
      });
      expect(res.status).toBe(404);
      const json = await res.json();
      expect(json.status).toBe(false);
      expect(json.message).toBe('Data layanan tidak ditemukan');
    });

    it('returns exact JSON structure for GET /api/kategori.php', async () => {
      const res = await fetch(`${BASE_URL}/api/kategori.php`, {
        headers: { 'X-API-KEY': API_KEY }
      });
      expect(res.status).toBe(200);
      const json = await res.json();
      expect(json.status).toBe(true);
      expect(json.message).toBe('Data kategori berhasil diambil');
      expect(Array.isArray(json.data)).toBe(true);
      expect(json.data.length).toBeGreaterThanOrEqual(7);

      const firstCat = json.data[0];
      expect(typeof firstCat.id_kategori).toBe('number');
      expect(typeof firstCat.nama_kategori).toBe('string');
      expect(typeof firstCat.deskripsi).toBe('string');
    });

    it('returns grouped categories and links for GET /api/website.php (Portal Home)', async () => {
      const res = await fetch(`${BASE_URL}/api/website.php`, {
        headers: { 'X-API-KEY': API_KEY }
      });
      expect(res.status).toBe(200);
      const json = await res.json();
      expect(json.status).toBe(true);
      expect(json.message).toBe('Data website berhasil diambil');
      expect(Array.isArray(json.data)).toBe(true);

      const group = json.data[0];
      expect(typeof group.title).toBe('string');
      expect(typeof group.category).toBe('string');
      expect(typeof group.description).toBe('string');
      expect(Array.isArray(group.links)).toBe(true);

      if (group.links.length > 0) {
        const link = group.links[0];
        expect(typeof link.label).toBe('string');
        expect(typeof link.href).toBe('string');
        expect(typeof link.logo).toBe('string');
        expect(typeof link.id_layanan).toBe('number');
        expect(typeof link.akses).toBe('string');
        expect(typeof link.vpn).toBe('string');
      }
    });
  });

  describe('3. Dashboard Unauthenticated Redirection Lock', () => {
    const protectedPages = [
      'index.php',
      'layanan.php',
      'kategori.php',
      'kelola_admin.php',
      'pengaturan.php',
    ];

    for (const page of protectedPages) {
      it(`redirects unauthenticated visitor from /${page} to login.php (302)`, async () => {
        const res = await fetch(`${BASE_URL}/${page}`, { redirect: 'manual' });
        expect(res.status).toBe(302);
        expect(res.headers.get('location')).toBe('login.php');
      });
    }
  });

  describe('4. App B Login Flow Contract (POST /api/auth/loginPortal.php)', () => {
    it('returns 400 when submitting empty identity or password', async () => {
      const res = await fetch(`${BASE_URL}/api/auth/loginPortal.php`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-API-KEY': API_KEY
        },
        body: JSON.stringify({ identity: '', password: '' })
      });
      expect(res.status).toBe(400);
      const json = await res.json();
      expect(json.status).toBe(false);
      expect(json.message).toContain('wajib diisi');
    });

    it('returns 404 when identity does not exist in database', async () => {
      const res = await fetch(`${BASE_URL}/api/auth/loginPortal.php`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-API-KEY': API_KEY
        },
        body: JSON.stringify({ identity: 'user_tidak_dikenal_999', password: 'password123' })
      });
      expect(res.status).toBe(404);
      const json = await res.json();
      expect(json.status).toBe(false);
      expect(json.message).toBe('Akun tidak ditemukan');
    });

    it('returns 401 when password does not match', async () => {
      const res = await fetch(`${BASE_URL}/api/auth/loginPortal.php`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-API-KEY': API_KEY
        },
        body: JSON.stringify({ identity: 'admin', password: 'PasswordSalah999!' })
      });
      expect(res.status).toBe(401);
      const json = await res.json();
      expect(json.status).toBe(false);
      expect(json.message).toBe('Password salah');
    });

    it('successfully logs in via username and returns authenticated user contract', async () => {
      const res = await fetch(`${BASE_URL}/api/auth/loginPortal.php`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-API-KEY': API_KEY
        },
        body: JSON.stringify({ identity: 'admin', password: 'admin123' })
      });
      expect(res.status).toBe(200);
      const json = await res.json();
      expect(json.status).toBe(true);
      expect(json.message).toBe('Login berhasil');
      expect(json.data).not.toBeNull();
      expect(json.data.username).toBe('admin');
      expect(json.data.email).toBe('admin@bps1310.cloud');
      expect(typeof json.data.id_admin).toBe('number');
      expect(typeof json.data.role).toBe('string');
    });

    it('successfully logs in via email and returns authenticated user contract', async () => {
      const res = await fetch(`${BASE_URL}/api/auth/loginPortal.php`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-API-KEY': API_KEY
        },
        body: JSON.stringify({ identity: 'admin@bps1310.cloud', password: 'admin123' })
      });
      expect(res.status).toBe(200);
      const json = await res.json();
      expect(json.status).toBe(true);
      expect(json.message).toBe('Login berhasil');
      expect(json.data.username).toBe('admin');
    });
  });

});

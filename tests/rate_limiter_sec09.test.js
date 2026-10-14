import { describe, it, expect, afterAll } from 'vitest';
import { execFileSync } from 'child_process';

describe('SEC-09 Rate Limiter & Database Protection Tests', () => {
  const testIdent = 'rate_limit_test_user_' + Date.now();
  const testIp = '198.51.100.99';

  const runPhp = (code) => {
    return execFileSync('php', ['-r', code], {
      cwd: process.cwd(),
      encoding: 'utf8'
    });
  };

  afterAll(() => {
    runPhp(`
      require 'config/config.php';
      $conn->exec("DELETE FROM login_attempts WHERE ip_address = '${testIp}' OR identifier = '${testIdent}'");
    `);
  });

  it('1. Awalnya diizinkan (allowed: true, remaining: 5)', () => {
    const raw = runPhp(`
      require 'config/config.php';
      echo json_encode(check_login_rate_limit('${testIdent}', '${testIp}'));
    `);
    const res = JSON.parse(raw);
    expect(res.allowed).toBe(true);
    expect(res.remaining).toBe(5);
    expect(res.failed_count).toBe(0);
  });

  it('2. Mencatat 5 kegagalan berturut-turut memicu lockout (allowed: false)', () => {
    const raw = runPhp(`
      require 'config/config.php';
      for ($i = 0; $i < 5; $i++) {
          record_login_attempt('${testIdent}', false, '${testIp}');
      }
      echo json_encode(check_login_rate_limit('${testIdent}', '${testIp}'));
    `);
    const res = JSON.parse(raw);
    expect(res.allowed).toBe(false);
    expect(res.remaining).toBe(0);
    expect(res.failed_count).toBe(5);
    expect(res.retry_after).toBeGreaterThan(0);
  });

  it('3. Percobaan login sukses me-reset riwayat kegagalan (allowed: true)', () => {
    const raw = runPhp(`
      require 'config/config.php';
      record_login_attempt('${testIdent}', true, '${testIp}');
      echo json_encode(check_login_rate_limit('${testIdent}', '${testIp}'));
    `);
    const res = JSON.parse(raw);
    expect(res.allowed).toBe(true);
    expect(res.remaining).toBe(5);
    expect(res.failed_count).toBe(0);
  });
});

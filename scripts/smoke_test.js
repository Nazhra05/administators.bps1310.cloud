const BASE = 'http://localhost/adminDashboard/public';

const endpoints = [
  'login.php',
  'register.php',
  'index.php',
  'kategori.php',
  'tambah_kategori.php',
  'edit_kategori.php?id=1',
  'hapus_kategori.php',
  'layanan.php',
  'tambah_layanan.php',
  'edit_layanan.php?id=1',
  'hapus_layanan.php',
  'kelola_admin.php',
  'kelola_user.php',
  'pengaturan.php',
  'setup_user.php',
  'verify_email.php',
  'verify_user.php',
  'send_verification_email.php',
  'send_user_verification_email.php',
  'send_admin_notification.php',
  'logout.php',
  'api/kategori.php',
  'api/layanan.php',
  'api/website.php',
  'api/auth/loginPortal.php'
];

async function run() {
  console.log('=== UNAUTHENTICATED SMOKE TEST ===');
  for (const ep of endpoints) {
    try {
      const res = await fetch(`${BASE}/${ep}`, { redirect: 'manual' });
      const loc = res.headers.get('location') || '';
      console.log(`[${res.status}] ${ep} ${loc ? '-> ' + loc : ''}`);
    } catch (e) {
      console.log(`[ERR] ${ep}: ${e.message}`);
    }
  }

  // Now login to get session cookie
  console.log('\n=== LOGGING IN AS ADMIN ===');
  const loginPage = await fetch(`${BASE}/login.php`);
  const setCookie = loginPage.headers.get('set-cookie') || '';
  let cookie = setCookie.split(';')[0];
  const pageHtml = await loginPage.text();
  const tokenMatch = pageHtml.match(/name="csrf_token" value="([^"]+)"/);
  const csrfToken = tokenMatch ? tokenMatch[1] : '';

  const params = new URLSearchParams();
  params.append('csrf_token', csrfToken);
  params.append('username', 'admin');
  params.append('password', 'admin123');

  const loginRes = await fetch(`${BASE}/login.php`, {
    method: 'POST',
    body: params,
    headers: { 'Cookie': cookie, 'Content-Type': 'application/x-www-form-urlencoded' },
    redirect: 'manual'
  });
  if (loginRes.headers.get('set-cookie')) {
    cookie = loginRes.headers.get('set-cookie').split(';')[0];
  }
  console.log('Login status:', loginRes.status, 'Location:', loginRes.headers.get('location'));

  console.log('\n=== AUTHENTICATED SMOKE TEST (Admin IT) ===');
  for (const ep of endpoints) {
    try {
      const res = await fetch(`${BASE}/${ep}`, {
        headers: { 'Cookie': cookie, 'X-API-KEY': 'BPS_SOLSEL_API_2026_GANTI_DENGAN_KEY_RAHASIA' },
        redirect: 'manual'
      });
      const loc = res.headers.get('location') || '';
      const text = await res.text();
      const hasError = text.includes('Fatal error') || text.includes('Parse error') || text.includes('Warning:') || text.includes('Notice:') || text.includes('SQLSTATE');
      const cleanSnippet = text.replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim().substring(0, 90);
      console.log(`[${res.status}] ${ep} ${loc ? '-> ' + loc : ''} ${hasError ? '[PHP ERROR!]' : ''} | ${cleanSnippet}`);
    } catch (e) {
      console.log(`[ERR] ${ep}: ${e.message}`);
    }
  }
}
run();

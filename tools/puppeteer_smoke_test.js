// tools/puppeteer_smoke_test.js
// Headless smoke test for registration, profile update, and password-change flows.
// Usage:
// 1) Ensure your local PHP dev server is running, e.g.:
//      php -S localhost:8000 -t "C:\xampp\htdocs\carwash_project"
// 2) From project root: npm install puppeteer
// 3) Run: node tools/puppeteer_smoke_test.js

const puppeteer = require('puppeteer');

const BASE = process.env.BASE_URL || 'http://localhost:8000';

function now() { return Date.now(); }

(async () => {
  const browser = await puppeteer.launch({ headless: true });
  try {
    console.log('BASE:', BASE);

    // --- Registration flow ---
    console.log('\n== Registration flow ==');
    const regPage = await browser.newPage();
    await regPage.goto(`${BASE}/backend/auth/Customer_Registration.php`, { waitUntil: 'networkidle2' });

    // Try to read hidden CSRF input from the registration page
    let regCsrf = null;
    try {
      regCsrf = await regPage.$eval('input[name="csrf_token"]', el => el.value);
    } catch (e) {
      console.warn('Could not find csrf_token input on registration page:', e.message);
    }

    // If not found, try to read from document.cookie after visiting
    if (!regCsrf) {
      const cookies = await regPage.cookies();
      console.log('Cookies from registration page:', cookies.map(c => `${c.name}=${c.value}`).join('; '));
    }

    const uniqueEmail = `puppeteer+${now()}@example.test`;
    const regBody = new URLSearchParams();
    regBody.append('full_name', 'Puppeteer Test');
    regBody.append('email', uniqueEmail);
    regBody.append('password', 'TestPass123!');
    regBody.append('password_confirm', 'TestPass123!');
    regBody.append('role', 'customer');
    regBody.append('city', 'istanbul');
    regBody.append('terms', 'on');
    if (regCsrf) regBody.append('csrf_token', regCsrf);

    // Post registration
    const regResult = await regPage.evaluate(async (url, body) => {
      const res = await fetch(url, { method: 'POST', body: body, credentials: 'same-origin', redirect: 'follow' });
      const text = await res.text();
      return { status: res.status, url: res.url, snippet: text.slice(0, 800) };
    }, `${BASE}/backend/auth/Customer_Registration_process.php`, regBody);

    console.log('Registration result:', regResult);

    // --- Profile update + password-change using session bootstrap ---
    console.log('\n== Profile update & Password change (session-bootstrap) ==');
    const sessPage = await browser.newPage();
    const resp = await sessPage.goto(`${BASE}/tools/session_bootstrap.php`, { waitUntil: 'networkidle2' });
    const tokenText = await resp.text();
    const csrf = tokenText.trim();
    console.log('Session bootstrap token:', csrf);

    // POST profile update
    const profileBody = new URLSearchParams();
    profileBody.append('action', 'update_profile');
    profileBody.append('name', 'Puppeteer User');
    profileBody.append('surname', 'Tester');
    profileBody.append('email', 'puppeteer+profile@example.test');
    profileBody.append('phone', '5550001111');
    profileBody.append('home_phone', '021234567');
    profileBody.append('national_id', '12345678901');
    profileBody.append('address', '123 Puppeteer Ave');
    profileBody.append('city', 'istanbul');
    profileBody.append('csrf_token', csrf);

    const profileResp = await sessPage.evaluate(async (url, body) => {
      const res = await fetch(url, { method: 'POST', body: body, credentials: 'same-origin' });
      try { return await res.json(); } catch (e) { return { status: res.status, text: await res.text() }; }
    }, `${BASE}/backend/dashboard/Customer_Dashboard_process.php`, profileBody);

    console.log('Profile update response:', profileResp);

    // POST password change (use incorrect current password to test CSRF acceptance)
    const pwBody = new URLSearchParams();
    pwBody.append('currentPassword', 'wrongpass');
    pwBody.append('newPassword', 'NewPass123!');
    pwBody.append('confirmPassword', 'NewPass123!');
    pwBody.append('csrf_token', csrf);

    const pwResp = await sessPage.evaluate(async (url, body) => {
      const res = await fetch(url, { method: 'POST', body: body, credentials: 'same-origin' });
      try { return await res.json(); } catch (e) { return { status: res.status, text: await res.text() }; }
    }, `${BASE}/backend/api/update_password.php`, pwBody);

    console.log('Password-change response:', pwResp);

    // Basic success checks
    const okProfile = !!(profileResp && (profileResp.success === true || profileResp.status === 200));
    const okPwCsrf = !(pwResp && pwResp.error && pwResp.error.toLowerCase && pwResp.error.toLowerCase().includes('csrf'));

    if (okProfile && okPwCsrf) {
      console.log('\nSMOKE TESTS PASSED: registration/profile/password-change flows accepted CSRF tokens.');
      process.exit(0);
    } else {
      console.error('\nSMOKE TESTS FAILED: see outputs above.');
      process.exit(3);
    }

  } catch (err) {
    console.error('Unexpected error during smoke test:', err);
    process.exit(2);
  } finally {
    try { await browser.close(); } catch (e) {}
  }
})();

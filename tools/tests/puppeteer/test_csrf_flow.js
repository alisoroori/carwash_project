/**
 * Puppeteer CSRF flow test (optional)
 * - Loads a page to read the CSRF meta token
 * - Posts to bookings/create.php with token -> expects success (200 + success:true)
 * - Posts without token -> expects 403 (or error flag)
 *
 * Usage: (from repo root)
 *   node tools/tests/puppeteer/test_csrf_flow.js
 *
 * Requires puppeteer installed in repo dev deps: npm install puppeteer --save-dev
 */

const puppeteer = require('puppeteer');
(async function(){
  const base = 'http://localhost/carwash_project';
  const pageUrl = base + '/backend/booking/new_booking.php';
  const apiCreate = base + '/backend/api/bookings/create.php';

  const browser = await puppeteer.launch({ headless: true, args: ['--no-sandbox'] });
  const page = await browser.newPage();

  try {
    await page.goto(pageUrl, { waitUntil: 'networkidle2' });
    // read meta token
    const token = await page.evaluate(() => document.querySelector('meta[name="csrf-token"]')?.content || '');
    console.log('meta csrf-token length:', token.length);

    // 1) POST with token (using fetch inside the page context to reuse cookies/session)
    const withToken = await page.evaluate(async (api, t) => {
      const fd = new FormData();
      fd.append('carwash_id', '1');
      fd.append('service_id', '1');
      fd.append('date', new Date().toISOString().slice(0,10));
      fd.append('time', '10:00');
      // include token
      fd.append('csrf_token', t);
      const res = await fetch(api, { method: 'POST', body: fd, credentials: 'same-origin' });
      const json = await res.json();
      return { status: res.status, body: json };
    }, apiCreate, token);

    console.log('With token response:', withToken);

    // 2) POST without token
    const withoutToken = await page.evaluate(async (api) => {
      const fd = new FormData();
      fd.append('carwash_id', '1');
      fd.append('service_id', '1');
      fd.append('date', new Date().toISOString().slice(0,10));
      fd.append('time', '10:00');
      const res = await fetch(api, { method: 'POST', body: fd, credentials: 'same-origin' });
      let json;
      try { json = await res.json(); } catch(e){ json = null; }
      return { status: res.status, body: json };
    }, apiCreate);

    console.log('Without token response:', withoutToken);

    const pass = (withToken.status === 200 && withToken.body && withToken.body.success) && (withoutToken.status === 403 || (withoutToken.body && !withoutToken.body.success));
    console.log('CSRF smoke test pass:', pass);

    await browser.close();
    process.exit(pass ? 0 : 2);
  } catch (e) {
    console.error('Error in test:', e);
    await browser.close();
    process.exit(2);
  }
})();

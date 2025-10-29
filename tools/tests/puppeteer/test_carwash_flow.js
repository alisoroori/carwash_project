const puppeteer = require('puppeteer');

(async () => {
  const base = 'http://localhost/carwash_project';
  const dashboardUrl = base + '/backend/dashboard/Customer_Dashboard.php';
  const bookingPath = '/backend/booking/new_booking.php';

  // Attempt to read test credentials emitted by tools/create_test_user.php
  const fs = require('fs');
  let testCreds = null;
  try {
    const raw = fs.readFileSync(__dirname + '/test_user.json', 'utf8');
    testCreds = JSON.parse(raw);
    console.log('Loaded test creds for', testCreds.email);
  } catch (e) {
    console.warn('No test_user.json found; ensure you created a test user or provide credentials via environment variables');
  }

  const browser = await puppeteer.launch({ headless: true, args: ['--no-sandbox'] });
  try {
    const page = await browser.newPage();
    page.setDefaultTimeout(15000);
    // If we have credentials, log in first via the web login page so session cookie is set
    if (testCreds && testCreds.email && testCreds.password) {
      const loginUrl = base + '/backend/auth/login.php';
      console.log('Navigating to login:', loginUrl);
      await page.goto(loginUrl, { waitUntil: 'networkidle2' });

      // Fill login form (user_type=customer, email, password)
      await page.select('select[name="user_type"]', 'customer').catch(() => {});
      await page.type('input[name="email"]', testCreds.email);
      await page.type('input[name="password"]', testCreds.password);
      await Promise.all([
        page.click('button[type="submit"]'),
        page.waitForNavigation({ waitUntil: 'networkidle2', timeout: 10000 }).catch(() => {})
      ]);

      // Now navigate to dashboard (in case login redirected elsewhere)
      console.log('Navigating to dashboard:', dashboardUrl);
      await page.goto(dashboardUrl, { waitUntil: 'networkidle2' });
    } else {
      console.log('No test credentials provided; opening dashboard directly:', dashboardUrl);
      await page.goto(dashboardUrl, { waitUntil: 'networkidle2' });
    }

    // Ensure the car wash section is visible so cards are rendered (the dashboard shows 'dashboard' by default)
    await page.evaluate(() => { if (typeof showSection === 'function') { try { showSection('carWashSelection'); } catch (e) {} } });
    // Wait for car wash cards to appear inside #carWashList
    await page.waitForSelector('#carWashList', { timeout: 5000 }).catch(() => {});

    // Find first "Rezervasyon Yap" button by scanning buttons/links on the page
    const btnInfo = await page.evaluate(() => {
      const candidates = Array.from(document.querySelectorAll('#carWashList button, #carWashList a, button, a'));
      const found = candidates.find(el => el.textContent && el.textContent.trim().includes('Rezervasyon Yap'));
      if (!found) return null;
      return { onclick: found.getAttribute('onclick') || null };
    });

    if (!btnInfo) {
      throw new Error('Rezervasyon Yap button not found on dashboard');
    }

    const onclick = btnInfo.onclick;
    console.log('Found button onclick:', onclick);
  const m = onclick ? /selectCarWashForReservation\s*\(\s*(?:event\s*,\s*)?(\d+)\s*,\s*'([^']+)'\s*\)/.exec(onclick) : null;
    const expectedId = m ? m[1] : null;
    const expectedName = m ? m[2] : null;
    console.log('Expected carwash id/name from onclick:', expectedId, expectedName);

    // Click the found button (may navigate current page or open new tab)
    await page.evaluate(() => {
      const candidates = Array.from(document.querySelectorAll('button, a'));
      const found = candidates.find(el => el.textContent && el.textContent.trim().includes('Rezervasyon Yap'));
      if (!found) throw new Error('btn not found to click');
      found.click();
    });

    // Wait for either a new target that contains bookingPath or an embedded form injected into the dashboard
    let bookingPage = null;
    let embedded = false;
    try {
      const target = await browser.waitForTarget(t => t.url().includes(bookingPath), { timeout: 8000 }).catch(() => null);
      if (target) bookingPage = await target.page();
    } catch (e) {
      // ignore
    }

    if (!bookingPage) {
      // Check for embedded form in the current page
      try {
        await page.waitForSelector('#newReservationForm #newBookingForm', { timeout: 8000 });
        embedded = true;
        bookingPage = page;
      } catch (e) {
        // If not embedded, wait for navigation as a fallback
        try { await page.waitForNavigation({ waitUntil: 'networkidle2', timeout: 8000 }); } catch (e) {}
        const pages = await browser.pages();
        bookingPage = pages.find(p => p.url().includes(bookingPath));
      }
    }

    if (!bookingPage) throw new Error('Booking page did not open or embedded form not injected');

    await bookingPage.bringToFront();
    if (!embedded) {
      await bookingPage.waitForSelector('#carwashSelect, #carwash', { visible: true });
    } else {
      await bookingPage.waitForSelector('#newReservationForm #newBookingForm', { visible: true });
    }

    // Read selected value
    const selected = await bookingPage.evaluate(() => {
      const sel = document.getElementById('carwashSelect') || document.getElementById('carwash');
      if (!sel) return { exists: false };
      const opt = sel.options[sel.selectedIndex];
      return { exists: true, value: sel.value, text: opt ? opt.text : null };
    });

    console.log('Booking page #carwash selected:', selected);

    // Verification
    if (!selected.exists) throw new Error('#carwash select not present on booking page');
    if (!expectedId) console.warn('Could not parse expectedId from onclick; will trust selected value');

    if (expectedId && String(selected.value) !== String(expectedId)) {
      throw new Error(`Selected carwash id mismatch: expected ${expectedId} but booking page has ${selected.value}`);
    }

    console.log('Test PASSED: booking page opened and carwash preselected correctly');
    await browser.close();
    process.exit(0);
  } catch (err) {
    console.error('Test FAILED:', err);
    await browser.close();
    process.exit(2);
  }
})();

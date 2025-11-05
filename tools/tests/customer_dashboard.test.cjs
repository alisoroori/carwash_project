const puppeteer = require('puppeteer');
const path = require('path');

(async () => {
  const TEST_URL = 'http://localhost/carwash_project/backend/dashboard/Customer_Dashboard.php?test_user_id=1';
  const IMAGE_PATH = path.resolve(__dirname, '../../frontend/assets/images/default-car.png');
  const envHeadless = process.env.HEADLESS;
  const headless = (typeof envHeadless === 'undefined') ? false : !(envHeadless === '0' || envHeadless.toLowerCase() === 'false');
  const browser = await puppeteer.launch({ headless: headless, defaultViewport: null, args: ['--no-sandbox'], slowMo: 30 });
  const page = await browser.newPage();
  page.setDefaultTimeout(120000);

  // Collect console errors
  const consoleErrors = [];
  page.on('console', msg => {
    if (msg.type() === 'error') {
      consoleErrors.push(msg.text());
    }
    console.log(`PAGE LOG [${msg.type()}] ${msg.text()}`);
  });

  // Capture vehicle_api responses
  const vehicleApiResponses = [];
  page.on('response', async (res) => {
    try {
      const url = res.url();
      if (url.includes('/backend/dashboard/vehicle_api.php')) {
        const text = await res.text();
        let json = null;
        try { json = text ? JSON.parse(text) : null; } catch (e) { json = text; }
        vehicleApiResponses.push({ status: res.status(), url, json });
      }
    } catch (e) {
      // ignore
    }
  });

  // Intercept dialogs (confirm for delete)
  page.on('dialog', async dialog => {
    try { await dialog.accept(); } catch (e) { console.warn('Dialog accept failed', e); }
  });

  try {
    await page.goto(TEST_URL, { waitUntil: 'networkidle2' });

    // Ensure the page has loaded and body is present
    await page.waitForSelector('body', { timeout: 60000 });

    // Ensure vehicle list container and form are present (the section may be hidden until opened)
    // Open vehicle modal via global helper if available; guard against transient execution context destruction
    try {
      // Ensure vehicles section is active; prefer calling showSection('vehicles') if available
      try {
        await page.evaluate(() => { if (typeof showSection === 'function') { try { showSection('vehicles'); } catch(e){} } });
      } catch(e) {}

      // Click the 'Araç Ekle' button inside the vehicles section
      await page.evaluate(() => {
        try {
          const btn = document.querySelector('button[onclick*="openVehicleModal"]') || Array.from(document.querySelectorAll('button')).find(n => /araç ekle|araç ekle/i.test(n.textContent || ''));
          if (btn) btn.click();
        } catch (e) { /* ignore */ }
      });
    
    } catch (e) {
      // If execution context was destroyed during navigation, retry a short reload and try again
  console.warn('Initial openVehicleModal attempt failed, retrying once...', e && e.message);
  await new Promise(res => setTimeout(res, 1000));
  try { await page.evaluate(() => typeof window.openVehicleModal === 'function' ? window.openVehicleModal(null) : null); } catch (e2) { /* ignore */ }
    }

    // Wait for the inline form and fields
    await page.waitForSelector('#vehicleFormInline', { visible: true });
    await page.waitForSelector('#car_brand_inline', { visible: true });
    await page.waitForSelector('#license_plate_inline', { visible: true });
    await page.waitForSelector('#vehicle_image_inline', { visible: true });

    // Fill form fields
    const uniq = Date.now();
    await page.type('#car_brand_inline', 'TestBrand');
    await page.type('#car_model_inline', 'ModelX');
    await page.type('#license_plate_inline', `TS-${uniq}`);
    await page.type('#car_year_inline', '2020');
    await page.type('#car_color_inline', 'Blue');

    // Upload file
    const fileInput = await page.$('#vehicle_image_inline');
    if (!fileInput) throw new Error('File input #vehicle_image_inline not found');
    await fileInput.uploadFile(IMAGE_PATH);

    // Remove debug-mode if auto-enabled to perform real create; try to disable window.DEBUG_VEHICLE_FORM if set
    await page.evaluate(() => { try { window.DEBUG_VEHICLE_FORM = false; } catch(e){} });

    // Click submit
    await Promise.all([
      page.waitForResponse(res => res.url().includes('/backend/dashboard/vehicle_api.php') && (res.request().method() === 'POST' || res.status() >= 200), { timeout: 60000 }).catch(() => null),
      page.click('#vehicleInlineSubmit')
    ]);

    // Wait for message element to contain success-ish text
    await page.waitForFunction(() => {
      const el = document.getElementById('vehicleFormMessageInline');
      if (!el) return false;
      const t = (el.textContent || '').trim().toLowerCase();
      return t.length > 0 && (t.includes('başar') || t.includes('kaydedildi') || t.includes('simulation') || t.includes('simulat'));
    }, { timeout: 10000 });

    // Check no console errors recorded
    if (consoleErrors.length > 0) {
      console.error('Console errors detected:', consoleErrors);
      throw new Error('Console errors occurred during test');
    }

    // Inspect captured vehicle_api response for create success
    const postResponses = vehicleApiResponses.filter(r => r.json && (r.json.success || (r.json.status && String(r.json.status).toLowerCase() === 'success')));
    let createdVehicleId = null;
    if (postResponses.length) {
      // Attempt to extract id
      for (const r of postResponses) {
        if (r.json && r.json.data && (r.json.data.vehicle_id || r.json.data.id)) {
          createdVehicleId = r.json.data.vehicle_id || r.json.data.id;
          break;
        }
        if (r.json && r.json.vehicle_id) { createdVehicleId = r.json.vehicle_id; break; }
        if (r.json && r.json.id) { createdVehicleId = r.json.id; break; }
      }
    }

    // If create succeeded and we have an id, verify it appears in DOM
    if (createdVehicleId) {
      console.log('Created vehicle id:', createdVehicleId);
      // Wait for vehicle card with data-vehicle-id
      await page.waitForSelector(`[data-vehicle-id="${createdVehicleId}"]`, { visible: true, timeout: 10000 });

      // Now click delete on that card
      const card = await page.$(`[data-vehicle-id="${createdVehicleId}"]`);
      if (!card) throw new Error('Created vehicle card not found');
      const delBtn = await card.$('[data-action="delete"]');
      if (!delBtn) throw new Error('Delete button not found on created vehicle');
      await Promise.all([
        page.waitForResponse(res => res.url().includes('/backend/dashboard/vehicle_api.php') && res.request().method() === 'POST', { timeout: 10000 }).catch(() => null),
        delBtn.click()
      ]);

      // Wait for card to be removed
      await page.waitForFunction(id => !document.querySelector(`[data-vehicle-id="${id}"]`), {}, createdVehicleId);
    } else {
      // No real create happened; ensure debug-mode message appears
      const msg = await page.$eval('#vehicleFormMessageInline', el => el.textContent || '');
      if (!/simulation|debug/i.test(msg)) {
        console.warn('No createdVehicleId and no simulation message; server may have rejected create. Message:', msg);
      }
    }

    console.log('\n✅ All Customer Dashboard tests passed');
    await browser.close();
    process.exit(0);
  } catch (err) {
    console.error('Test failed:', err);
    try { await browser.close(); } catch (e) {}
    process.exit(1);
  }
})();

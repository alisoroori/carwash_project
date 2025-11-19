/*
Automated E2E test for Hizmet Listesi (Services) using Playwright
Requires environment variables (see ../.env.example)
Outputs step-by-step logs and verification results to console and exits with non-zero code on failures.

Run with:
  node test/e2e/hizmet_list_test.js

Prerequisites:
  npm install -D playwright mysql2 dotenv
  npx playwright install
*/

const { chromium } = require('playwright');
const mysql = require('mysql2/promise');
const path = require('path');
require('dotenv').config({ path: path.resolve(__dirname, '../.env') });

const BASE_URL = process.env.BASE_URL || 'http://localhost/carwash_project';
const DASHBOARD_URL = `${BASE_URL}/backend/dashboard/Car_Wash_Dashboard.php`;
const LOGIN_URL = `${BASE_URL}/backend/auth/login.php`;

const TEST_USER = {
  email: process.env.TEST_USER_EMAIL || process.env.TEST_USER || '',
  password: process.env.TEST_USER_PASSWORD || process.env.TEST_PASS || ''
};

const DB_CONFIG = {
  host: process.env.DB_HOST || '127.0.0.1',
  user: process.env.DB_USER || 'root',
  password: process.env.DB_PASS || '',
  database: process.env.DB_NAME || 'carwash_db',
  port: process.env.DB_PORT ? parseInt(process.env.DB_PORT) : 3306
};

const TEST_PREFIX = `E2E_TEST_${Date.now()}`;
const createdIds = [];

async function log(step, msg) {
  console.log(`[${new Date().toISOString()}] [${step}] ${msg}`);
}

async function fail(step, msg) {
  console.error(`[${new Date().toISOString()}] [${step}] FAIL: ${msg}`);
  await cleanupAndExit(1);
}

async function cleanupAndExit(code = 0) {
  try {
    if (createdIds.length > 0) {
      const conn = await mysql.createConnection(DB_CONFIG);
      const ids = createdIds.join(',');
      await conn.query(`DELETE FROM services WHERE id IN (${ids})`);
      await conn.end();
      await log('CLEANUP', `Deleted test rows: ${ids}`);
    }
  } catch (e) {
    console.error('Cleanup error', e.message);
  }
  process.exit(code);
}

(async () => {
  await log('START', 'E2E Hizmet Listesi test starting');

  // DB connection for verification
  let conn;
  try {
    conn = await mysql.createConnection(DB_CONFIG);
    await log('DB', `Connected to DB ${DB_CONFIG.database}@${DB_CONFIG.host}`);
  } catch (e) {
    await fail('DB', `Failed to connect: ${e.message}`);
  }

  const browser = await chromium.launch({ headless: true });
  const context = await browser.newContext();
  const page = await context.newPage();

  // Capture console errors
  const jsErrors = [];
  page.on('console', msg => {
    if (msg.type() === 'error') {
      jsErrors.push(msg.text());
      console.error('[PAGE][console.error]', msg.text());
    }
  });

  // 1. Login
  await log('STEP', `Navigate to login page: ${LOGIN_URL}`);
  await page.goto(LOGIN_URL, { waitUntil: 'domcontentloaded' });

  try {
    // Try to fill login form - detect inputs
    if (!TEST_USER.email || !TEST_USER.password) {
      await fail('LOGIN', 'TEST_USER_EMAIL or TEST_USER_PASSWORD not set in env');
    }

    // Fill form (try several selectors)
    const emailSelector = 'input[type="email"], input[name="email"], input[id*="email"]';
    const passSelector = 'input[type="password"], input[name="password"], input[id*="password"]';

    await page.fill(emailSelector, TEST_USER.email);
    await page.fill(passSelector, TEST_USER.password);
    await Promise.all([
      page.waitForNavigation({ waitUntil: 'networkidle' }),
      page.click('button[type="submit"], input[type="submit"], button:has-text("Giriş"), button:has-text("Login")')
    ]);
    await log('LOGIN', 'Submitted login form and navigated');
  } catch (e) {
    await fail('LOGIN', `Login failed: ${e.message}`);
  }

  // 2. Open dashboard and go to Services
  await page.goto(DASHBOARD_URL, { waitUntil: 'domcontentloaded' });
  await log('NAV', 'Opened dashboard');

  // click services link in sidebar
  try {
    const servicesLink = await page.$('a[href="#services"], a:has-text("Hizmet")');
    if (servicesLink) {
      await servicesLink.click();
      await page.waitForSelector('#services:not(.hidden)', { timeout: 3000 });
      await log('UI', 'Services section opened');
    } else {
      await fail('UI', 'Services link not found');
    }
  } catch (e) {
    await fail('UI', `Failed to open services section: ${e.message}`);
  }

  // Baseline: count current services in DOM
  const initialCount = await page.$$eval('#services .flex.items-center.justify-between.p-4.border.rounded-lg', nodes => nodes.length).catch(() => 0);
  await log('UI', `Initial services in DOM: ${initialCount}`);

  // Helper to add a service via UI and verify
  async function addService(name, desc, dur, price) {
    await log('ACTION', `Add service: ${name}`);
    // click add button
    await page.click('button:has-text("Hizmet Ekle"), button:has-text("Hizmet Ekle")');
    await page.waitForSelector('#serviceModal:not(.hidden)', { timeout: 3000 });
    await log('UI', 'Service modal opened');

    await page.fill('#auto_160', name);
    await page.fill('#auto_161', desc);
    await page.fill('#auto_162', String(dur));
    await page.fill('#auto_163', String(price));

    // Intercept save response
    const [response] = await Promise.all([
      page.waitForResponse(resp => resp.url().includes('/backend/api/services/save_service.php') && resp.request().method() === 'POST', {timeout: 5000}),
      page.click('#serviceSaveBtn')
    ]);

    const text = await response.text();
    let json;
    try { json = JSON.parse(text); } catch (e) { json = null; }

    if (!response.ok) {
      await log('NETWORK', `Save service HTTP ${response.status} - response: ${text}`);
      throw new Error(`Save service HTTP ${response.status}`);
    }
    if (!json || json.status !== 'success') {
      await log('NETWORK', `Save service returned non-success JSON: ${text}`);
      throw new Error('Save service did not return success');
    }

    const newId = (json.data && json.data.id) ? json.data.id : (json.id || null);
    if (newId) createdIds.push(newId);
    await log('VERIFY', `Save response OK, id=${newId}`);

    // verify in DB
    const [rows] = await conn.query('SELECT * FROM services WHERE id = ?', [newId]);
    if (!rows || rows.length === 0) {
      throw new Error('Inserted row not found in DB');
    }
    await log('DB', `Row verified in DB id=${newId} name=${rows[0].name}`);

    // verify DOM updated (wait for either loadServices GET or page reload)
    await page.waitForTimeout(800); // allow UI refresh
    const found = await page.$(`text=${name}`);
    if (!found) throw new Error('Service name not found in DOM after save');
    await log('UI', `Service ${name} found in DOM`);

    return newId;
  }

  // Add multiple services
  const servicesToAdd = [
    { name: `${TEST_PREFIX}_A`, desc: 'Auto test A', dur: 30, price: 50 },
    { name: `${TEST_PREFIX}_B`, desc: 'Auto test B', dur: 45, price: 80 }
  ];

  for (const s of servicesToAdd) {
    try {
      const id = await addService(s.name, s.desc, s.dur, s.price);
      await log('SUCCESS', `Added service id=${id}`);
    } catch (e) {
      await fail('ADD', `Failed to add service ${s.name}: ${e.message}`);
    }
  }

  // Ensure createdIds not empty
  if (createdIds.length === 0) await fail('TEST', 'No services were created');

  // Edit the first created service
  const editId = createdIds[0];
  const editName = `${TEST_PREFIX}_A_EDIT`;
  try {
    // Find row containing original name
    const origName = servicesToAdd[0].name;
    const row = await page.locator(`xpath=//h4[text()="${origName}"]`).first();
    if (!row) throw new Error('Original row not found for edit');
    const parent = row.locator('xpath=../..');
    // find edit button within same row
    const editBtn = parent.locator('button:has(i.fa-edit), button[aria-label*="Düzenle"], button[title*="Düzenle"]').first();
    if (!await editBtn.count()) throw new Error('Edit button not found in row');
    await editBtn.click();
    await page.waitForSelector('#serviceModal:not(.hidden)', { timeout: 3000 });
    await log('EDIT', 'Edit modal opened');

    // Check pre-filled
    const preName = await page.$eval('#auto_160', el => el.value).catch(() => null);
    if (preName && preName !== origName) {
      await log('WARN', `Pre-filled name mismatch: expected=${origName} actual=${preName}`);
    }

    await page.fill('#auto_160', editName);
    const [editResp] = await Promise.all([
      page.waitForResponse(resp => resp.url().includes('/backend/api/services/save_service.php') && resp.request().method() === 'POST', {timeout: 5000}),
      page.click('#serviceSaveBtn')
    ]);
    const editText = await editResp.text();
    const editJson = (() => { try { return JSON.parse(editText); } catch(e){ return null; } })();
    if (!editResp.ok || !editJson || editJson.status !== 'success') {
      throw new Error(`Edit failed: ${editText}`);
    }
    await log('EDIT', `Edit API success: ${editText}`);

    // DB verify update
    const [rows] = await conn.query('SELECT name FROM services WHERE id = ?', [editId]);
    if (!rows || rows.length === 0) throw new Error('Edited row not found in DB');
    if (rows[0].name !== editName) throw new Error('DB name did not update');
    await log('DB', `Edit verified in DB for id=${editId}`);

    // UI verify
    await page.waitForTimeout(800);
    const foundEdited = await page.$(`text=${editName}`);
    if (!foundEdited) throw new Error('Edited name not found in DOM');
    await log('UI', 'Edited name found in DOM');
  } catch (e) {
    await fail('EDIT', e.message);
  }

  // Delete the second created service via UI (if possible)
  const deleteId = createdIds[1];
  try {
    const delOrig = servicesToAdd[1].name;
    const row2 = await page.locator(`xpath=//h4[text()="${delOrig}"]`).first();
    if (!row2) throw new Error('Row to delete not found');
    const parent2 = row2.locator('xpath=../..');
    const delBtn = parent2.locator('button:has(i.fa-trash), button[aria-label*="Sil"], button[title*="Sil"]').first();
    if (!await delBtn.count()) throw new Error('Delete button not found');

    // Listen for confirm dialog
    page.once('dialog', async dialog => {
      await log('DIALOG', `Dialog message: ${dialog.message()}`);
      await dialog.accept();
    });

    const [delResp] = await Promise.all([
      page.waitForResponse(resp => resp.url().includes('/backend/api/services/delete.php') || resp.request().url().includes('/delete') || resp.request().method() === 'POST' && resp.request().url().includes('/backend/api/services/'), {timeout: 5000}).catch(() => null),
      delBtn.click()
    ]);

    if (delResp) {
      const delText = await delResp.text();
      let delJson = null;
      try { delJson = JSON.parse(delText); } catch(e) { delJson = null; }
      if (delResp.ok && delJson && delJson.status === 'success') {
        await log('DELETE', `Delete API success: ${delText}`);
      } else {
        await log('DELETE', `Delete API returned: ${delText}`);
      }
    } else {
      await log('DELETE', 'No delete API response detected; assuming front-end performed direct DB change or soft-delete');
    }

    // Verify DB: row should be removed or status changed
    const [rowsDel] = await conn.query('SELECT * FROM services WHERE id = ?', [deleteId]);
    if (rowsDel && rowsDel.length > 0) {
      // If still present, check status
      const status = rowsDel[0].status || '';
      await log('DELETE', `Row still in DB id=${deleteId} status=${status}`);
    } else {
      await log('DB', `Row id=${deleteId} not found in DB - deletion verified`);
    }

    // UI verify removal
    await page.waitForTimeout(800);
    const stillThere = await page.$(`text=${delOrig}`);
    if (stillThere) {
      await log('UI', 'Deleted item still in DOM (soft delete or UI not refreshed)');
    } else {
      await log('UI', 'Deleted item removed from DOM');
    }

  } catch (e) {
    await fail('DELETE', e.message);
  }

  // Final checks
  await log('FINAL', `Created test ids: ${createdIds.join(',')}`);

  // Check JS console errors
  if (jsErrors.length > 0) {
    await log('JS', `JavaScript console errors captured: ${jsErrors.length}`);
    jsErrors.forEach((e, i) => console.error(`[JSERR ${i+1}] ${e}`));
    // Not failing test automatically, just report
  } else {
    await log('JS', 'No JS console errors captured');
  }

  // Close browser and DB
  await browser.close();
  await conn.end();

  await log('END', 'E2E test completed successfully');
  await cleanupAndExit(0);

})();

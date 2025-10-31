/**
 * Vehicle Debug Runner
 * - Fetches /backend/dashboard/vehicle_api.php?action=list
 * - Logs vehicles array length and content
 * - Verifies #vehiclesList exists
 * - Attempts to render each vehicle card and append to #vehiclesList
 * - Verifies images load
 * - Confirms window.CONFIG.CSRF_TOKEN (first 8 chars)
 * - Provides a manual refresh button and a debug panel with logs
 *
 * Usage:
 * - Drop this file into backend/dashboard/vehicle_debug_runner.js
 * - Include on Customer Dashboard pages (after DOM elements) with:
 *     <script src="/carwash_project/backend/dashboard/vehicle_debug_runner.js"></script>
 *
 * Notes:
 * - Does not use top-level await; uses async functions and event handlers.
 * - Uses same-origin fetch and will pass credentials to respect session cookies.
 */

(function () {
  'use strict';

  // Ensure CONFIG and API defaults exist
  window.CONFIG = window.CONFIG || {};
  window.CONFIG.API = window.CONFIG.API || {};
  window.CONFIG.API.VEHICLE_CREATE = window.CONFIG.API.VEHICLE_CREATE || '/carwash_project/backend/dashboard/vehicle_api.php';
  window.CONFIG.API.VEHICLE_LIST = window.CONFIG.API.VEHICLE_LIST || '/carwash_project/backend/dashboard/vehicle_api.php?action=list';

  // Read CSRF from meta tag safely and set on window.CONFIG (only once)
  (function loadCsrfFromMeta() {
    try {
      const meta = document.querySelector('meta[name="csrf-token"]');
      if (meta && meta.content) {
        window.CONFIG = window.CONFIG || {};
        if (!window.CONFIG.CSRF_TOKEN) {
          window.CONFIG.CSRF_TOKEN = meta.content;
          console.info('[VDR] CSRF_TOKEN loaded:', String(meta.content).substring(0, 8) + '...');
        }
      } else {
        if (!window.__VDR_WARNED_CSRF_META) {
          window.__VDR_WARNED_CSRF_META = true;
          console.warn('[VDR] CSRF_TOKEN missing in meta tag');
        }
      }
    } catch (e) {
      console.warn('[VDR] CSRF token detection error', e);
    }
  })();

  // Helper: append CSRF to FormData only once
  function appendCsrfOnce(formData) {
    if (!formData) return;
    window.CONFIG = window.CONFIG || {};
    const token = window.CONFIG.CSRF_TOKEN || '';
    if (token && !formData.has('csrf_token')) {
      formData.append('csrf_token', token);
    }
  }

  // Replace any direct FormData append in the file to use appendCsrfOnce(fd)
  // Example usage in create/update/delete flows:
  // const fd = new FormData(formElem);
  // appendCsrfOnce(fd);
  // fetch(window.CONFIG.API.VEHICLE_CREATE, { method:'POST', body: fd, credentials:'same-origin' });

  // Debug panel creation
  const panel = document.createElement('div');
  panel.id = 'vehicle-debug-runner';
  panel.style.cssText = 'position:fixed;right:12px;bottom:12px;width:420px;max-height:420px;background:rgba(0,0,0,0.85);color:#e6e6e6;font-family:monospace;font-size:12px;padding:10px;border-radius:6px;z-index:99999;overflow:auto;';
  panel.innerHTML = `
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
      <strong style="color:#fff">Vehicle Debug Runner</strong>
      <button id="vdr-toggle" style="background:transparent;border:1px solid #444;color:#fff;padding:2px 6px;border-radius:4px;cursor:pointer">Hide</button>
    </div>
    <div id="vdr-controls" style="margin-bottom:8px;">
      <button id="vdr-refresh" style="margin-right:6px;padding:6px;background:#0b74de;border:none;border-radius:4px;color:white;cursor:pointer">Refresh</button>
      <button id="vdr-fetch" style="padding:6px;background:#16a34a;border:none;border-radius:4px;color:white;cursor:pointer">Fetch Only</button>
    </div>
    <div id="vdr-log" style="white-space:pre-wrap;line-height:1.3;max-height:320px;overflow:auto;"></div>
  `;
  document.body.appendChild(panel);

  const logEl = document.getElementById('vdr-log');
  const toggleBtn = document.getElementById('vdr-toggle');
  const refreshBtn = document.getElementById('vdr-refresh');
  const fetchBtn = document.getElementById('vdr-fetch');

  function timestamp() {
    return new Date().toLocaleTimeString();
  }

  function log(msg, level = 'info') {
    const color = level === 'error' ? '#ff8b8b' : level === 'warn' ? '#ffd580' : '#cfe8ff';
    const prefix = `[${timestamp()}] ${level.toUpperCase()}: `;
    const entry = document.createElement('div');
    entry.style.marginBottom = '6px';
    entry.innerHTML = `<span style="color:${color}">${prefix}</span><span style="color:#ddd">${escapeHtml(String(msg))}</span>`;
    logEl.appendChild(entry);
    logEl.scrollTop = logEl.scrollHeight;
    // Also print to console for developers
    if (level === 'error') console.error(msg);
    else if (level === 'warn') console.warn(msg);
    else console.log(msg);
  }

  function escapeHtml(str) {
    return str.replace(/[&<>"']/g, function (m) {
      return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[m];
    });
  }

  toggleBtn.addEventListener('click', () => {
    const controls = document.getElementById('vdr-controls');
    if (logEl.style.display === 'none') {
      logEl.style.display = 'block';
      controls.style.display = 'block';
      toggleBtn.textContent = 'Hide';
    } else {
      logEl.style.display = 'none';
      controls.style.display = 'none';
      toggleBtn.textContent = 'Show';
    }
  });

  // Main state counters for summary
  let summary = {
    totalFromJSON: 0,
    successfullyRendered: 0,
    missingImages: [],
    errors: []
  };

  // Check CSRF token
  function checkCsrf() {
    if (window.CONFIG && window.CONFIG.CSRF_TOKEN) {
      log(`CSRF_TOKEN loaded: ${String(window.CONFIG.CSRF_TOKEN).substring(0, 8)}...`, 'info');
      return true;
    } else {
      log('CSRF_TOKEN not found on window.CONFIG or meta tag. Form submissions may fail.', 'warn');
      return false;
    }
  }

  // Verify container existence
  function findVehiclesContainer() {
    const container = document.getElementById('vehiclesList') || document.querySelector('.vehicles-list') || null;
    if (container) {
      log('Found vehicles container (#vehiclesList or .vehicles-list).', 'info');
    } else {
      log('Vehicles container not found. Look for element with id="vehiclesList" or class="vehicles-list".', 'error');
    }
    return container;
  }

  // Render a single vehicle card (minimal safe markup)
  function renderVehicleCard(vehicle) {
    const card = document.createElement('div');
    card.className = 'vdr-vehicle-card';
    card.style.cssText = 'background:#fff;color:#111;border-radius:10px;padding:10px;margin-bottom:8px;display:flex;gap:10px;align-items:flex-start;box-shadow:0 2px 6px rgba(0,0,0,0.08);';
    const imgSrc = vehicle.image_path || vehicle.image || '/carwash_project/frontend/images/default-car.png';
    const img = document.createElement('img');
    img.src = imgSrc;
    img.alt = `${vehicle.brand || 'Vehicle'} ${vehicle.model || ''}`.trim();
    img.style.cssText = 'width:84px;height:56px;object-fit:cover;border-radius:6px;background:#f3f4f6;';
    img.setAttribute('data-vdr-src', imgSrc);

    const body = document.createElement('div');
    body.style.flex = '1';

    const title = document.createElement('div');
    title.style.fontWeight = '700';
    title.style.marginBottom = '4px';
    title.textContent = `${vehicle.brand || ''} ${vehicle.model || ''}`.trim();

    const meta = document.createElement('div');
    meta.style.fontSize = '12px';
    meta.style.color = '#555';
    meta.innerHTML = `<div><strong>Plate:</strong> ${escapeHtml(vehicle.license_plate || '—')}</div>
                      <div><strong>Year:</strong> ${escapeHtml(String(vehicle.year || '—'))}</div>
                      <div><strong>Color:</strong> ${escapeHtml(vehicle.color || '—')}</div>`;

    body.appendChild(title);
    body.appendChild(meta);

    card.appendChild(img);
    card.appendChild(body);

    return { card, img };
  }

  // Verify image load
  function verifyImageLoad(imgEl, vehicleIdOrLabel) {
    return new Promise((resolve) => {
      const testImg = new Image();
      testImg.onload = () => {
        log(`Image loaded for vehicle ${vehicleIdOrLabel}: ${imgEl.getAttribute('data-vdr-src')}`, 'info');
        resolve(true);
      };
      testImg.onerror = () => {
        log(`Image MISSING / failed for vehicle ${vehicleIdOrLabel}: ${imgEl.getAttribute('data-vdr-src')}`, 'warn');
        resolve(false);
      };
      // Use the same src; some browsers may start loading already but onload/onerror will still fire
      testImg.src = imgEl.getAttribute('data-vdr-src') || imgEl.src;
      // Fallback timeout: if neither fires within X ms assume failure
      setTimeout(() => resolve(false), 4000);
    });
  }

  // Fetch the vehicles JSON from the API endpoint
  async function fetchVehiclesJSON() {
    summary = { totalFromJSON: 0, successfullyRendered: 0, missingImages: [], errors: [] };
    log(`Fetching vehicles from: ${window.CONFIG.API.VEHICLE_LIST}`, 'info');
    try {
      const res = await fetch(window.CONFIG.API.VEHICLE_LIST, { method: 'GET', credentials: 'same-origin', headers: { 'Accept': 'application/json' } });
      const raw = await res.text(); // read text to log potential HTML
      if (!res.ok) {
        log(`HTTP ${res.status} fetching vehicle list. Response snippet: ${raw.slice(0, 800)}`, 'error');
        summary.errors.push(`HTTP ${res.status}`);
        return null;
      }
      let json = null;
      try {
        json = raw ? JSON.parse(raw) : null;
      } catch (err) {
        log('Failed to parse JSON from vehicle API response. Response snippet follows:', 'error');
        log(raw.slice(0, 2000), 'error');
        summary.errors.push('Invalid JSON response');
        return null;
      }
      if (!json || json.status !== 'success') {
        log(`Vehicle API returned status != success: ${JSON.stringify(json && (json.message || json), null, 2)}`, 'warn');
        summary.errors.push('API returned non-success status');
        return json;
      }
      const vehicles = (json.data && Array.isArray(json.data.vehicles)) ? json.data.vehicles : [];
      log(`Vehicles from JSON: ${vehicles.length}`, 'info');
      // log the content (first N)
      const preview = vehicles.slice(0, 20).map((v, i) => ({ i: i + 1, id: v.id ?? null, brand: v.brand ?? null, model: v.model ?? null, license_plate: v.license_plate ?? null, image_path: v.image_path ?? v.image ?? null }));
      log(`First ${preview.length} vehicles preview:\n` + JSON.stringify(preview, null, 2), 'info');
      summary.totalFromJSON = vehicles.length;
      return vehicles;
    } catch (err) {
      log(`Network or fetch error: ${err.message}`, 'error');
      summary.errors.push(err.message);
      return null;
    }
  }

  // Attempt to render vehicles into DOM
  async function tryRenderVehicles(vehicles) {
    const container = findVehiclesContainer();
    if (!container) {
      summary.errors.push('Missing container');
      return;
    }
    // Optionally clear existing children (comment/uncomment depending on desired behavior)
    // container.innerHTML = '';
    let renderedCount = 0;
    for (const v of vehicles) {
      try {
        const { card, img } = renderVehicleCard(v);
        // attach data-vehicle-id for delete button logging / event wiring
        if (v.id) card.setAttribute('data-vehicle-id', String(v.id));

        // Append card to container
        container.appendChild(card);
        renderedCount++;

        // Verify image load
        const ok = await verifyImageLoad(img, v.id ?? v.license_plate ?? v.brand ?? 'unknown');
        if (!ok) {
          summary.missingImages.push({ id: v.id ?? null, src: img.getAttribute('data-vdr-src') });
        } else {
          // mark as successfully rendered with good image
          summary.successfullyRendered++;
        }
      } catch (err) {
        log(`Error rendering vehicle ${v.id ?? '(no id)'}: ${err.message}`, 'error');
        summary.errors.push(err.message);
      }
    }
    log(`Rendered ${renderedCount} vehicles into DOM (appended).`, 'info');
  }

  // Full run: fetch and render and produce summary
  async function runDebugCycle({ onlyFetch = false } = {}) {
    const hasCsrf = checkCsrf();
    const vehicles = await fetchVehiclesJSON();
    if (!vehicles) {
      log('No vehicles array to render (fetch failed or invalid).', 'warn');
      printSummary();
      return;
    }
    if (onlyFetch) {
      log('Fetch-only mode: not rendering to DOM.', 'info');
      printSummary();
      return;
    }
    // Ensure container exists
    const container = findVehiclesContainer();
    if (!container) {
      log('Attempting to create a temporary container "#vehiclesList" for debug rendering.', 'warn');
      const temp = document.createElement('div');
      temp.id = 'vehiclesList';
      temp.style.padding = '12px';
      temp.style.maxWidth = '620px';
      // insert near body top to be visible
      document.body.insertBefore(temp, document.body.firstChild);
      log('Temporary container created and inserted at top of body.', 'info');
    }
    await tryRenderVehicles(vehicles);
    printSummary();
  }

  function printSummary() {
    log('--- Debug Summary ---', 'info');
    log(`Total vehicles in JSON: ${summary.totalFromJSON}`, 'info');
    log(`Total vehicles successfully rendered (with confirmed images): ${summary.successfullyRendered}`, 'info');
    log(`Missing images (${summary.missingImages.length}):\n` + JSON.stringify(summary.missingImages.slice(0, 20), null, 2), summary.missingImages.length ? 'warn' : 'info');
    if (summary.errors && summary.errors.length) {
      log(`Errors encountered (${summary.errors.length}):\n` + JSON.stringify(summary.errors.slice(0, 20), null, 2), 'error');
    } else {
      log('No errors encountered during this run.', 'info');
    }
  }

  // Attach actions
  refreshBtn.addEventListener('click', async () => {
    log('Manual refresh triggered by user.', 'info');
    await runDebugCycle({ onlyFetch: false });
  });
  fetchBtn.addEventListener('click', async () => {
    log('Manual fetch-only triggered by user.', 'info');
    await runDebugCycle({ onlyFetch: true });
  });

  // Auto-run on load after a short delay to give page a chance to render async lists
  function scheduleAutoRun() {
    const delayMs = 1800;
    log(`Scheduling automatic debug run in ${delayMs}ms...`, 'info');
    setTimeout(() => {
      runDebugCycle({ onlyFetch: false }).catch(err => {
        log(`Unhandled error during debug run: ${err.message}`, 'error');
      });
    }, delayMs);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', scheduleAutoRun);
  } else {
    scheduleAutoRun();
  }

  // Expose a console-friendly shortcut
  window.runVehicleDebug = runDebugCycle;
})();

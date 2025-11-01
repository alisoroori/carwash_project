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

  /* SAFE debug logger + initialization */
  let logEl = null;

  function escapeHtml(str) {
    return String(str).replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[m]);
  }

  function timestamp() {
    return new Date().toISOString();
  }

  function log(msg, level = 'info') {
    const color = level === 'error' ? '#ff8b8b' : level === 'warn' ? '#ffd580' : '#cfe8ff';
    const prefix = `[${timestamp()}] ${level.toUpperCase()}: `;
    const entryText = `${prefix}${String(msg)}`;

    // If UI container exists, append there; otherwise fallback to console
    if (logEl) {
      const entry = document.createElement('div');
      entry.style.marginBottom = '6px';
      entry.innerHTML = `<span style="color:${color}">${escapeHtml(prefix)}</span><span style="color:#ddd">${escapeHtml(String(msg))}</span>`;
      logEl.appendChild(entry);
      logEl.scrollTop = logEl.scrollHeight;
    } else {
      // Fallback to console to avoid ReferenceError before DOM is ready
      if (level === 'error') console.error(entryText);
      else if (level === 'warn') console.warn(entryText);
      else console.log(entryText);
    }
  }

  /* Initialize logEl after DOM ready and then run startup tasks */
  function initDebugLog(containerId = 'log-container') {
    // guard: if already initialized, noop
    if (logEl) return;

    // Attempt to find existing container
    const existing = document.getElementById(containerId);
    if (existing) {
      logEl = existing;
    } else {
      // create one
      logEl = document.createElement('div');
      logEl.id = containerId;
      logEl.style.cssText = `
        background:#0b1220;
        color:#cfe8ff;
        font-family: monospace;
        padding:10px;
        border-radius:6px;
        max-height: 36vh;
        overflow:auto;
        font-size:13px;
        margin:8px;
      `;
      document.body.appendChild(logEl);
    }
    log('✅ Debug log initialized', 'info');
  }

  /* Ensure DOMContentLoaded init then run original startup */
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
      initDebugLog(); 
      // safe-call: if loadCsrfFromMeta exists, call it (it will use safe log fallback)
      if (typeof loadCsrfFromMeta === 'function') {
        try { loadCsrfFromMeta(); } catch (e) { log('loadCsrfFromMeta error: '+e, 'error'); }
      }
    });
  } else {
    // DOM already ready
    initDebugLog();
    if (typeof loadCsrfFromMeta === 'function') {
      try { loadCsrfFromMeta(); } catch (e) { log('loadCsrfFromMeta error: '+e, 'error'); }
    }
  }

  // Timestamp helper
  function timestamp() {
    return new Date().toLocaleTimeString();
  }

  // Basic HTML escaper used by log entries
  function escapeHtml(str) {
    return String(str).replace(/[&<>"']/g, function (m) {
      return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[m];
    });
  }

  // Robust log function: writes to UI log if available, otherwise falls back to console
  function log(msg, level = 'info') {
    const color = level === 'error' ? '#ff8b8b' : level === 'warn' ? '#ffd580' : '#cfe8ff';
    const prefix = `[${timestamp()}] ${level.toUpperCase()}: `;
    // If UI log available, append there
    if (logEl) {
      try {
        const entry = document.createElement('div');
        entry.style.marginBottom = '6px';
        entry.innerHTML = `<span style="color:${color}">${escapeHtml(prefix)}</span><span style="color:#ddd">${escapeHtml(String(msg))}</span>`;
        logEl.appendChild(entry);
        logEl.scrollTop = logEl.scrollHeight;
      } catch (e) {
        // ignore UI failures and fallback to console
        console[level === 'error' ? 'error' : level === 'warn' ? 'warn' : 'log']('[VDR] ' + msg);
      }
    } else {
      // Fallback: console only
      if (level === 'error') console.error('[VDR] ' + msg);
      else if (level === 'warn') console.warn('[VDR] ' + msg);
      else console.log('[VDR] ' + msg);
    }
  }
  // --- end moved-up logging helpers ---

  // Read CSRF from meta tag safely and set on window.CONFIG (only once)
  (function loadCsrfFromMeta() {
    try {
      // Prefer window.CONFIG if already set by server header script
      window.CONFIG = window.CONFIG || {};
      if (window.CONFIG.CSRF_TOKEN && typeof window.CONFIG.CSRF_TOKEN === 'string' && window.CONFIG.CSRF_TOKEN.length > 8) {
        // already set by header.php; log once
        if (!window.__VDR_LOGGED_CSRF) {
          window.__VDR_LOGGED_CSRF = true;
          console.info('[VDR] CSRF_TOKEN loaded from window.CONFIG:', String(window.CONFIG.CSRF_TOKEN).substring(0, 8) + '...');
          if (typeof log === 'function') log(`CSRF_TOKEN loaded: ${String(window.CONFIG.CSRF_TOKEN).substring(0,8)}...`, 'info');
        }
        return;
      }

      // Fallback: read meta tag and ensure non-empty content
      const meta = document.querySelector('meta[name="csrf-token"]');
      const metaToken = meta && meta.content ? String(meta.content).trim() : '';
      const metaToken = meta && meta.content ? String(meta.content).trim() : '';
      if (metaToken) {
        if (!window.CONFIG.CSRF_TOKEN) window.CONFIG.CSRF_TOKEN = metaToken;
        if (!window.__VDR_LOGGED_CSRF) {indow.CONFIG.CSRF_TOKEN = metaToken;
          window.__VDR_LOGGED_CSRF = true;
          console.info('[VDR] CSRF_TOKEN loaded from <meta>:', metaToken.substring(0, 8) + '...');
          if (typeof log === 'function') log(`CSRF_TOKEN loaded from <meta>: ${metaToken.substring(0,8)}...`, 'info');
        } if (typeof log === 'function') log(`CSRF_TOKEN loaded from <meta>: ${metaToken.substring(0,8)}...`, 'info');
        return;
      } return;
      }
      // Visible once-only warning (console + debug panel) if token missing
      if (!window.__VDR_WARNED_CSRF_META) { + debug panel) if token missing
        window.__VDR_WARNED_CSRF_META = true;
        const warnMsg = '[VDR] CSRF_TOKEN missing in meta tag and window.CONFIG.CSRF_TOKEN. Add <meta name="csrf-token"> in your <head> (backend/includes/header.php).';
        console.warn(warnMsg); CSRF_TOKEN missing in meta tag and window.CONFIG.CSRF_TOKEN. Add <meta name="csrf-token"> in your <head> (backend/includes/header.php).';
        if (typeof log === 'function') log(warnMsg, 'warn');
      } if (typeof log === 'function') log(warnMsg, 'warn');
    } catch (e) {
      if (!window.__VDR_WARNED_CSRF_META) {
        window.__VDR_WARNED_CSRF_META = true;
        console.warn('[VDR] CSRF token detection error', e);
        if (typeof log === 'function') log('CSRF token detection error: ' + (e && e.message) , 'error');
      } if (typeof log === 'function') log('CSRF token detection error: ' + (e && e.message) , 'error');
    } }
  })();
  })();
  // Helper: append CSRF to FormData only once
  function appendCsrfOnce(formData) {only once
    if (!formData) return;formData) {
    window.CONFIG = window.CONFIG || {};
    const token = window.CONFIG.CSRF_TOKEN || '';
    if (token && !formData.has('csrf_token')) {';
      formData.append('csrf_token', token);)) {
    } formData.append('csrf_token', token);
  } }
  }
  // Replace any direct FormData append in the file to use appendCsrfOnce(fd)
  // Example usage in create/update/delete flows:le to use appendCsrfOnce(fd)
  // const fd = new FormData(formElem);ete flows:
  // appendCsrfOnce(fd);Data(formElem);
  // fetch(window.CONFIG.API.VEHICLE_CREATE, { method:'POST', body: fd, credentials:'same-origin' });
  // fetch(window.CONFIG.API.VEHICLE_CREATE, { method:'POST', body: fd, credentials:'same-origin' });
  // Debug panel creation
  const panel = document.createElement('div');
  panel.id = 'vehicle-debug-runner';nt('div');
  panel.style.cssText = 'position:fixed;right:12px;bottom:12px;width:420px;max-height:420px;background:rgba(0,0,0,0.85);color:#e6e6e6;font-family:monospace;font-size:12px;padding:10px;border-radius:6px;z-index:99999;overflow:auto;';
  panel.innerHTML = ` = 'position:fixed;right:12px;bottom:12px;width:420px;max-height:420px;background:rgba(0,0,0,0.85);color:#e6e6e6;font-family:monospace;font-size:12px;padding:10px;border-radius:6px;z-index:99999;overflow:auto;';
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:8px;">
      <strong style="color:#fff">Vehicle Debug Runner</strong>gn-items:center;margin-bottom:8px;">
      <button id="vdr-toggle" style="background:transparent;border:1px solid #444;color:#fff;padding:2px 6px;border-radius:4px;cursor:pointer">Hide</button>
    </div>ton id="vdr-toggle" style="background:transparent;border:1px solid #444;color:#fff;padding:2px 6px;border-radius:4px;cursor:pointer">Hide</button>
    <div id="vdr-controls" style="margin-bottom:8px;">
      <button id="vdr-refresh" style="margin-right:6px;padding:6px;background:#0b74de;border:none;border-radius:4px;color:white;cursor:pointer">Refresh</button>
      <button id="vdr-fetch" style="padding:6px;background:#16a34a;border:none;border-radius:4px;color:white;cursor:pointer">Fetch Only</button>Refresh</button>
    </div>ton id="vdr-fetch" style="padding:6px;background:#16a34a;border:none;border-radius:4px;color:white;cursor:pointer">Fetch Only</button>
    <div id="vdr-log" style="white-space:pre-wrap;line-height:1.3;max-height:320px;overflow:auto;"></div>
  `;<div id="vdr-log" style="white-space:pre-wrap;line-height:1.3;max-height:320px;overflow:auto;"></div>
  document.body.appendChild(panel);
  document.body.appendChild(panel);
  const logEl = document.getElementById('vdr-log');
  const toggleBtn = document.getElementById('vdr-toggle');
  const refreshBtn = document.getElementById('vdr-refresh');
  const fetchBtn = document.getElementById('vdr-fetch');h');
  const fetchBtn = document.getElementById('vdr-fetch');
  toggleBtn.addEventListener('click', () => {
    const controls = document.getElementById('vdr-controls');
    if (logEl.style.display === 'none') {yId('vdr-controls');
      logEl.style.display = 'block';e') {
      controls.style.display = 'block';
      toggleBtn.textContent = 'Hide';';
    } else {Btn.textContent = 'Hide';
      logEl.style.display = 'none';
      controls.style.display = 'none';
      toggleBtn.textContent = 'Show';;
    } toggleBtn.textContent = 'Show';
  });
  });
  // Main state counters for summary
  let summary = {ounters for summary
    totalFromJSON: 0,
    successfullyRendered: 0,
    missingImages: [],ed: 0,
    errors: []ges: [],
  };errors: []
  };
  // Check CSRF token
  function checkCsrf() {
    if (window.CONFIG && window.CONFIG.CSRF_TOKEN) {
      log(`CSRF_TOKEN loaded: ${String(window.CONFIG.CSRF_TOKEN).substring(0, 8)}...`, 'info');
      return true;KEN loaded: ${String(window.CONFIG.CSRF_TOKEN).substring(0, 8)}...`, 'info');
    } else { true;
      log('CSRF_TOKEN not found on window.CONFIG or meta tag. Form submissions may fail.', 'warn');
      return false;EN not found on window.CONFIG or meta tag. Form submissions may fail.', 'warn');
    } return false;
  } }
  }
  // Verify container existence
  function findVehiclesContainer() {
    const container = document.getElementById('vehiclesList') || document.querySelector('.vehicles-list') || null;
    if (container) {= document.getElementById('vehiclesList') || document.querySelector('.vehicles-list') || null;
      log('Found vehicles container (#vehiclesList or .vehicles-list).', 'info');
    } else {ound vehicles container (#vehiclesList or .vehicles-list).', 'info');
      log('Vehicles container not found. Look for element with id="vehiclesList" or class="vehicles-list".', 'error');
    } log('Vehicles container not found. Look for element with id="vehiclesList" or class="vehicles-list".', 'error');
    return container;
  } return container;
  }
  // Render a single vehicle card (minimal safe markup)
  function renderVehicleCard(vehicle) {mal safe markup)
    const card = document.createElement('div');
    card.className = 'vdr-vehicle-card';'div');
    card.style.cssText = 'background:#fff;color:#111;border-radius:10px;padding:10px;margin-bottom:8px;display:flex;gap:10px;align-items:flex-start;box-shadow:0 2px 6px rgba(0,0,0,0.08);';
    const imgSrc = vehicle.image_path || vehicle.image || '/carwash_project/frontend/images/default-car.png';y:flex;gap:10px;align-items:flex-start;box-shadow:0 2px 6px rgba(0,0,0,0.08);';
    const img = document.createElement('img');le.image || '/carwash_project/frontend/images/default-car.png';
    img.src = imgSrc;ent.createElement('img');
    img.alt = `${vehicle.brand || 'Vehicle'} ${vehicle.model || ''}`.trim();
    img.style.cssText = 'width:84px;height:56px;object-fit:cover;border-radius:6px;background:#f3f4f6;';
    img.setAttribute('data-vdr-src', imgSrc);px;object-fit:cover;border-radius:6px;background:#f3f4f6;';
    img.setAttribute('data-vdr-src', imgSrc);
    const body = document.createElement('div');
    body.style.flex = '1';createElement('div');
    body.style.flex = '1';
    const title = document.createElement('div');
    title.style.fontWeight = '700';ement('div');
    title.style.marginBottom = '4px';
    title.textContent = `${vehicle.brand || ''} ${vehicle.model || ''}`.trim();
    title.textContent = `${vehicle.brand || ''} ${vehicle.model || ''}`.trim();
    const meta = document.createElement('div');
    meta.style.fontSize = '12px';lement('div');
    meta.style.color = '#555';x';
    meta.innerHTML = `<div><strong>Plate:</strong> ${escapeHtml(vehicle.license_plate || '—')}</div>
                      <div><strong>Year:</strong> ${escapeHtml(String(vehicle.year || '—'))}</div>v>
                      <div><strong>Color:</strong> ${escapeHtml(vehicle.color || '—')}</div>`;div>
                      <div><strong>Color:</strong> ${escapeHtml(vehicle.color || '—')}</div>`;
    body.appendChild(title);
    body.appendChild(meta);;
    body.appendChild(meta);
    card.appendChild(img);
    card.appendChild(body);
    card.appendChild(body);
    return { card, img };
  } return { card, img };
  }
  // Verify image load
  function verifyImageLoad(imgEl, vehicleIdOrLabel) {
    return new Promise((resolve) => {icleIdOrLabel) {
      const testImg = new Image();> {
      testImg.onload = () => {e();
        log(`Image loaded for vehicle ${vehicleIdOrLabel}: ${imgEl.getAttribute('data-vdr-src')}`, 'info');
        resolve(true);ded for vehicle ${vehicleIdOrLabel}: ${imgEl.getAttribute('data-vdr-src')}`, 'info');
      };resolve(true);
      testImg.onerror = () => {
        log(`Image MISSING / failed for vehicle ${vehicleIdOrLabel}: ${imgEl.getAttribute('data-vdr-src')}`, 'warn');
        resolve(false);ING / failed for vehicle ${vehicleIdOrLabel}: ${imgEl.getAttribute('data-vdr-src')}`, 'warn');
      };resolve(false);
      // Use the same src; some browsers may start loading already but onload/onerror will still fire
      testImg.src = imgEl.getAttribute('data-vdr-src') || imgEl.src;ut onload/onerror will still fire
      // Fallback timeout: if neither fires within X ms assume failure
      setTimeout(() => resolve(false), 4000);ithin X ms assume failure
    });etTimeout(() => resolve(false), 4000);
  } });
  }
  // Fetch the vehicles JSON from the API endpoint
  async function fetchVehiclesJSON() {API endpoint
    summary = { totalFromJSON: 0, successfullyRendered: 0, missingImages: [], errors: [] };
    log(`Fetching vehicles from: ${window.CONFIG.API.VEHICLE_LIST}`, 'info'); errors: [] };
    try {Fetching vehicles from: ${window.CONFIG.API.VEHICLE_LIST}`, 'info');
      const res = await fetch(window.CONFIG.API.VEHICLE_LIST, { method: 'GET', credentials: 'same-origin', headers: { 'Accept': 'application/json' } });
      const raw = await res.text(); // read text to log potential HTML: 'GET', credentials: 'same-origin', headers: { 'Accept': 'application/json' } });
      if (!res.ok) {ait res.text(); // read text to log potential HTML
        log(`HTTP ${res.status} fetching vehicle list. Response snippet: ${raw.slice(0, 800)}`, 'error');
        summary.errors.push(`HTTP ${res.status}`);ist. Response snippet: ${raw.slice(0, 800)}`, 'error');
        return null;rs.push(`HTTP ${res.status}`);
      } return null;
      let json = null;
      try {son = null;
        json = raw ? JSON.parse(raw) : null;
      } catch (err) {JSON.parse(raw) : null;
        log('Failed to parse JSON from vehicle API response. Response snippet follows:', 'error');
        log(raw.slice(0, 2000), 'error');hicle API response. Response snippet follows:', 'error');
        summary.errors.push('Invalid JSON response');
        return null;rs.push('Invalid JSON response');
      } return null;
      if (!json || json.status !== 'success') {
        log(`Vehicle API returned status != success: ${JSON.stringify(json && (json.message || json), null, 2)}`, 'warn');
        summary.errors.push('API returned non-success status');ingify(json && (json.message || json), null, 2)}`, 'warn');
        return json;rs.push('API returned non-success status');
      } return json;
      const vehicles = (json.data && Array.isArray(json.data.vehicles)) ? json.data.vehicles : [];
      log(`Vehicles from JSON: ${vehicles.length}`, 'info');.vehicles)) ? json.data.vehicles : [];
      // log the content (first N)ehicles.length}`, 'info');
      const preview = vehicles.slice(0, 20).map((v, i) => ({ i: i + 1, id: v.id ?? null, brand: v.brand ?? null, model: v.model ?? null, license_plate: v.license_plate ?? null, image_path: v.image_path ?? v.image ?? null }));
      log(`First ${preview.length} vehicles preview:\n` + JSON.stringify(preview, null, 2), 'info');and ?? null, model: v.model ?? null, license_plate: v.license_plate ?? null, image_path: v.image_path ?? v.image ?? null }));
      summary.totalFromJSON = vehicles.length;eview:\n` + JSON.stringify(preview, null, 2), 'info');
      return vehicles;mJSON = vehicles.length;
    } catch (err) {es;
      log(`Network or fetch error: ${err.message}`, 'error');
      summary.errors.push(err.message);r.message}`, 'error');
      return null;rs.push(err.message);
    } return null;
  } }
  }
  // Attempt to render vehicles into DOM
  async function tryRenderVehicles(vehicles) {
    const container = findVehiclesContainer();
    if (!container) { findVehiclesContainer();
      summary.errors.push('Missing container');
      return;.errors.push('Missing container');
    } return;
    // Optionally clear existing children (comment/uncomment depending on desired behavior)
    // container.innerHTML = ''; children (comment/uncomment depending on desired behavior)
    let renderedCount = 0; = '';
    for (const v of vehicles) {
      try {nst v of vehicles) {
        const { card, img } = renderVehicleCard(v);
        // attach data-vehicle-id for delete button logging / event wiring
        if (v.id) card.setAttribute('data-vehicle-id', String(v.id));iring
        if (v.id) card.setAttribute('data-vehicle-id', String(v.id));
        // Append card to container
        container.appendChild(card);
        renderedCount++;Child(card);
        renderedCount++;
        // Verify image load
        const ok = await verifyImageLoad(img, v.id ?? v.license_plate ?? v.brand ?? 'unknown');
        if (!ok) { await verifyImageLoad(img, v.id ?? v.license_plate ?? v.brand ?? 'unknown');
          summary.missingImages.push({ id: v.id ?? null, src: img.getAttribute('data-vdr-src') });
        } else {y.missingImages.push({ id: v.id ?? null, src: img.getAttribute('data-vdr-src') });
          // mark as successfully rendered with good image
          summary.successfullyRendered++;d with good image
        } summary.successfullyRendered++;
      } catch (err) {
        log(`Error rendering vehicle ${v.id ?? '(no id)'}: ${err.message}`, 'error');
        summary.errors.push(err.message);id ?? '(no id)'}: ${err.message}`, 'error');
      } summary.errors.push(err.message);
    } }
    log(`Rendered ${renderedCount} vehicles into DOM (appended).`, 'info');
  } log(`Rendered ${renderedCount} vehicles into DOM (appended).`, 'info');
  }
  // Full run: fetch and render and produce summary
  async function runDebugCycle({ onlyFetch = false } = {}) {
    const hasCsrf = checkCsrf(); onlyFetch = false } = {}) {
    const vehicles = await fetchVehiclesJSON();
    if (!vehicles) { await fetchVehiclesJSON();
      log('No vehicles array to render (fetch failed or invalid).', 'warn');
      printSummary();s array to render (fetch failed or invalid).', 'warn');
      return;mmary();
    } return;
    if (onlyFetch) {
      log('Fetch-only mode: not rendering to DOM.', 'info');
      printSummary(); mode: not rendering to DOM.', 'info');
      return;mmary();
    } return;
    // Ensure container exists
    const container = findVehiclesContainer();
    if (!container) { findVehiclesContainer();
      log('Attempting to create a temporary container "#vehiclesList" for debug rendering.', 'warn');
      const temp = document.createElement('div');iner "#vehiclesList" for debug rendering.', 'warn');
      temp.id = 'vehiclesList';ateElement('div');
      temp.style.padding = '12px';
      temp.style.maxWidth = '620px';
      // insert near body top to be visible
      document.body.insertBefore(temp, document.body.firstChild);
      log('Temporary container created and inserted at top of body.', 'info');
    } log('Temporary container created and inserted at top of body.', 'info');
    await tryRenderVehicles(vehicles);
    printSummary();Vehicles(vehicles);
  } printSummary();
  }
  function printSummary() {
    log('--- Debug Summary ---', 'info');
    log(`Total vehicles in JSON: ${summary.totalFromJSON}`, 'info');
    log(`Total vehicles successfully rendered (with confirmed images): ${summary.successfullyRendered}`, 'info');
    log(`Missing images (${summary.missingImages.length}):\n` + JSON.stringify(summary.missingImages.slice(0, 20), null, 2), summary.missingImages.length ? 'warn' : 'info');
    if (summary.errors && summary.errors.length) {ength}):\n` + JSON.stringify(summary.missingImages.slice(0, 20), null, 2), summary.missingImages.length ? 'warn' : 'info');
      log(`Errors encountered (${summary.errors.length}):\n` + JSON.stringify(summary.errors.slice(0, 20), null, 2), 'error');
    } else {rrors encountered (${summary.errors.length}):\n` + JSON.stringify(summary.errors.slice(0, 20), null, 2), 'error');
      log('No errors encountered during this run.', 'info');
    } log('No errors encountered during this run.', 'info');
  } }
  }
  // Attach actions
  refreshBtn.addEventListener('click', async () => {
    log('Manual refresh triggered by user.', 'info');
    await runDebugCycle({ onlyFetch: false });info');
  });wait runDebugCycle({ onlyFetch: false });
  fetchBtn.addEventListener('click', async () => {
    log('Manual fetch-only triggered by user.', 'info');
    await runDebugCycle({ onlyFetch: true });', 'info');
  });wait runDebugCycle({ onlyFetch: true });
  });
  // Auto-run on load after a short delay to give page a chance to render async lists
  function scheduleAutoRun() {short delay to give page a chance to render async lists
    const delayMs = 1800;n() {
    log(`Scheduling automatic debug run in ${delayMs}ms...`, 'info');
    setTimeout(() => {tomatic debug run in ${delayMs}ms...`, 'info');
      runDebugCycle({ onlyFetch: false }).catch(err => {
        log(`Unhandled error during debug run: ${err.message}`, 'error');
      });og(`Unhandled error during debug run: ${err.message}`, 'error');
    }, delayMs);
  } }, delayMs);
  }
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', scheduleAutoRun);
  } else {nt.addEventListener('DOMContentLoaded', scheduleAutoRun);
    scheduleAutoRun();
  } scheduleAutoRun();
  }
  // Expose a console-friendly shortcut
  window.runVehicleDebug = runDebugCycle;
})();dow.runVehicleDebug = runDebugCycle;
})();







































})();  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', create, { once: true });  create();  }    } catch (e) { window.__VDR_PREMOUNTED = false; }      window.__VDR_PREMOUNTED = true;      if (document.body) document.body.appendChild(el);      el.appendChild(body);      body.style.whiteSpace = 'pre-wrap';      body.id = 'vdr-log-body';      const body = document.createElement('div');      el.appendChild(hdr);      hdr.innerHTML = '<strong style="color:#fff;font-size:13px">Vehicle Debug</strong>';      hdr.style.cssText = 'display:flex;justify-content:space-between;align-items:center;margin-bottom:8px';      const hdr = document.createElement('div');      ].join(';');        'overflow:auto'        'z-index:2147483647',        'border-radius:6px',        'padding:10px',        'font-size:13px',        'font-family:monospace',        'color:#cfe8ff',        'background:rgba(11,18,32,0.95)',        'max-height:36vh',        'width:420px',        'bottom:12px',        'right:12px',        'position:fixed',      el.style.cssText = [      el.id = 'log-container';      const el = document.createElement('div');      if (document.getElementById('log-container')) return;    try {  function create() {  if (document.getElementById('log-container')) { window.__VDR_PREMOUNTED = true; return; }  'use strict';(function () {
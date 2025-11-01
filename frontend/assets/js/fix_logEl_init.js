// Fix ReferenceError: Cannot access 'logEl' before initialization

let logEl;

document.addEventListener('DOMContentLoaded', () => {
  logEl = document.getElementById('log-container');
  if (logEl) {
    log('✅ Debug log initialized');
  } else {
    console.warn("⚠️ logEl not found in DOM!");
  }
});

function log(msg, level = 'info') {
  const color = level === 'error' ? '#ff8b8b' : level === 'warn' ? '#ffd580' : '#cfe8ff';
  const prefix = `[${new Date().toLocaleTimeString()}] ${level.toUpperCase()}: `;
  const entry = document.createElement('div');
  entry.innerHTML = `<span style="color:${color}">${prefix}</span><span style="color:#ddd">${String(msg)}</span>`;

  if (logEl) {
    logEl.appendChild(entry);
    logEl.scrollTop = logEl.scrollHeight;
  } else {
    console.warn("logEl not initialized yet:", msg);
  }

  if (level === 'error') console.error(msg);
  else if (level === 'warn') console.warn(msg);
  else console.log(msg);
}

// Quick pre-mount for VDR log container so vehicle_debug_helper.js finds it immediately.
// This script should be loaded before vehicle_debug_helper.js.
(function () {
  'use strict';

  // Do nothing if the container already exists
  if (document.getElementById('log-container')) {
    window.__VDR_PREMOUNTED = true;
    return;
  }

  function createContainer() {
    if (document.getElementById('log-container')) return;
    try {
      const el = document.createElement('div');
      el.id = 'log-container';
      el.style.cssText = [
        'position:fixed',
        'right:12px',
        'bottom:12px',
        'width:420px',
        'max-height:36vh',
        'background:rgba(11,18,32,0.95)',
        'color:#cfe8ff',
        'font-family:monospace',
        'font-size:13px',
        'padding:10px',
        'border-radius:6px',
        'z-index:2147483647',
        'overflow:auto',
        'display:block'
      ].join(';');
      // Minimal header so panel isn't empty
      const hdr = document.createElement('div');
      hdr.style.cssText = 'display:flex;justify-content:space-between;align-items:center;margin-bottom:8px';
      hdr.innerHTML = '<strong style="color:#fff;font-size:13px">Vehicle Debug</strong>';
      el.appendChild(hdr);

      // Log container body (vehicle_debug_helper will use same id)
      const body = document.createElement('div');
      body.id = 'vdr-log-body';
      body.style.whiteSpace = 'pre-wrap';
      el.appendChild(body);

      // Append to body if available; otherwise wait for DOMContentLoaded
      if (document.body) {
        document.body.appendChild(el);
        window.__VDR_PREMOUNTED = true;
      } else {
        document.addEventListener('DOMContentLoaded', function () {
          document.body.appendChild(el);
          window.__VDR_PREMOUNTED = true;
        }, { once: true });
      }
    } catch (e) {
      // Fail silently — vehicle_debug_helper has its own fallback to console
      window.__VDR_PREMOUNTED = false;
    }
  }

  // Try immediate mount, and also on DOM ready
  createContainer();
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', createContainer, { once: true });
  }
})();

// CSRF Helper
// - Reads CSRF token from <meta name="csrf-token"> on page load
// - Logs presence and length of token
// - Automatically appends the token to all fetch POST requests as header 'X-CSRF-Token'
// - Automatically appends the token to XHR POST requests as header 'X-CSRF-Token'
// - Injects a hidden input named 'csrf_token' into forms submitted via POST
// - Fallback behavior: if token/helper missing forms still submit; server will return 403 for invalid/missing tokens
//
// Usage example (fetch POST):
// fetch('/carwash_project/backend/api/bookings/create.php', {
//   method: 'POST',
//   body: new URLSearchParams({ carwash_id: 1, service_id: 2, date: '2025-11-11', time: '10:00' })
// }).then(r => r.json()).then(console.log).catch(console.error);
// The helper will automatically add the X-CSRF-Token header for same-origin POST requests.

(function(){
  'use strict';

  // Read the CSRF token from the meta tag. We read it on-demand so late injections still work.
  function readMetaToken(){
    const m = document.querySelector('meta[name="csrf-token"]');
    return m ? (m.getAttribute('content') || '') : '';
  }

  function getToken(){
    return readMetaToken() || '';
  }

  // Patch fetch to add X-CSRF-Token header only for POST requests to same-origin URLs
  if (window.fetch) {
    const _fetch = window.fetch.bind(window);
    window.fetch = function(input, init){
      try {
        init = init || {};
        // Determine method: preference init.method, then Request.method if input is Request
        let method = (init.method || '').toString().toUpperCase();
        if (!method && typeof input === 'object' && input && input.method) method = (input.method || '').toString().toUpperCase();
        method = method || 'GET';

        // Only add header for POST requests
        if (method === 'POST') {
          const token = getToken();
          if (token) {
            const url = (typeof input === 'string') ? input : (input && input.url) || location.href;
            const reqUrl = new URL(url, location.href);
            if (reqUrl.origin === location.origin) {
              const headers = new Headers(init.headers || {});
              if (!headers.get('X-CSRF-Token')) headers.set('X-CSRF-Token', token);
              init.headers = headers;
            }
          }
        }
      } catch (e) {
        // ignore and continue
      }
      return _fetch(input, init);
    };
  }

  // Patch XMLHttpRequest to set X-CSRF-Token header for POST same-origin requests
  (function(){
    if (!window.XMLHttpRequest) return;
    const _open = XMLHttpRequest.prototype.open;
    const _send = XMLHttpRequest.prototype.send;

    XMLHttpRequest.prototype.open = function(method, url){
      try {
        this.__csrf_helper_method = (method || 'GET').toUpperCase();
        this.__csrf_helper_url = url;
      } catch(e) {}
      return _open.apply(this, arguments);
    };

    XMLHttpRequest.prototype.send = function(body){
      try {
        const method = (this.__csrf_helper_method || 'GET').toUpperCase();
        if (method === 'POST' && this.setRequestHeader && this.__csrf_helper_url) {
          const token = getToken();
          if (token) {
            const reqUrl = new URL(this.__csrf_helper_url, location.href);
            if (reqUrl.origin === location.origin) {
              try { this.setRequestHeader('X-CSRF-Token', token); } catch(e) {}
            }
          }
        }
      } catch(e) {}
      return _send.apply(this, arguments);
    };
  })();

  // Inject hidden csrf_token input into forms submitted via POST (capture phase)
  document.addEventListener('submit', function(e){
    try {
      const form = e.target;
      if (!(form instanceof HTMLFormElement)) return;
      const method = (form.method || '').toLowerCase() || 'get';
      if (method !== 'post') return; // only inject for POST
      if (!form.querySelector('input[name="csrf_token"]')) {
        const token = getToken();
        if (token) {
          const inp = document.createElement('input');
          inp.type = 'hidden';
          inp.name = 'csrf_token';
          inp.value = token;
          form.appendChild(inp);
        }
      }
    } catch (err) {
      // ignore errors to avoid blocking submit
    }
  }, true);

  // Log presence of token for quick verification in console (matches requested format)
  try {
    const t = getToken();
    console.info(`CSRF helper loaded; token present: ${!!t} length: ${t ? t.length : 0}`);
  } catch(e){}

})();

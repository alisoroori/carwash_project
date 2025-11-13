// Lightweight API helper: apiCall(url, options)
// Returns an object { data, text, response } on success.
// Throws an Error on network failure or non-OK responses. Error includes .response, .data, .text when available.
(function(){
  async function apiCall(url, options = {}){
    try {
      const res = await fetch(url, options);

      let data = null;
      let text = null;
      const ct = res.headers && res.headers.get ? (res.headers.get('content-type') || '') : '';

      try {
        if (ct && ct.indexOf('application/json') !== -1) {
          data = await res.json();
        } else {
          text = await res.text();
          try { data = JSON.parse(text); } catch(e) { /* leave data null */ }
        }
      } catch (parseErr) {
        // fallback: attempt to read text if json parsing failed
        try { text = await res.text(); } catch(e) { /* ignore */ }
      }

      if (!res.ok) {
        const message = (data && (data.error || data.message)) || text || `${res.status} ${res.statusText}` || 'Server error';
        const err = new Error(message);
        err.response = res;
        err.data = data;
        err.text = text;
        throw err;
      }

      return { data, text, response: res };
    } catch (err) {
      // Normalize network errors to friendly messages
      if (err instanceof TypeError && /failed to fetch/i.test(err.message)) {
        throw new Error('Network error: failed to reach server');
      }
      throw err;
    }
  }

  // Expose globally
  if (typeof window !== 'undefined') window.apiCall = apiCall;
  if (typeof module !== 'undefined' && module.exports) module.exports = { apiCall };
})();

/*
  dashboard_auto_fix.js — safe diagnostic & optional-fix helper for Customer Dashboard
  - Scans <img> tags and reports broken ones (HEAD request)
  - Suggests safe replacements (relative paths) but won't change them unless run with apply=true
  - Ensures CSRF meta exists and reports status
  - Tests a small set of API endpoints and logs status

  Usage (paste into browser console on Customer Dashboard):
    // load from server
    (async ()=>{ const s=document.createElement('script'); s.src='/carwash_project/frontend/assets/js/dashboard_auto_fix.js'; document.head.appendChild(s); })();

  Then run:
    // just diagnose
    window.DashboardAutoFix && window.DashboardAutoFix.run({apply:false});

    // diagnose + apply fixes (WARNING: will change DOM src attributes and inject meta tag if missing)
    window.DashboardAutoFix && window.DashboardAutoFix.run({apply:true});
*/
(function(){
  if (window.DashboardAutoFix) return; // already loaded

  const DEFAULT_BASE = '/carwash_project';

  function safeFetchHead(url){
    try {
      return fetch(url, { method: 'HEAD', credentials: 'same-origin' });
    } catch (e) {
      return Promise.reject(e);
    }
  }

  async function checkImages({apply=false} = {}){
    const images = Array.from(document.querySelectorAll('img'));
    const results = [];
    for (const img of images){
      const src = img.getAttribute('src') || img.src || '';
      if (!src) { results.push({el:img, src, ok:false, reason:'empty-src'}); continue; }
      let ok = false, status = null, error=null;
      try {
        const res = await safeFetchHead(src);
        status = res && res.status ? res.status : null;
        ok = res && res.ok;
      } catch (e) { error = e && e.message ? e.message : String(e); }

      if (!ok){
        // Suggest candidate fixes
        const fixes = [];
        // If absolute localhost pointing to project, propose relative
        try {
          const urlObj = new URL(src, window.location.href);
          if (urlObj.hostname === 'localhost' || urlObj.hostname === window.location.hostname) {
            // convert to relative web-root path
            const rel = src.replace(/^https?:\/\/[^/]+/i, '');
            fixes.push({type:'relative', value: rel});
            // also try replacing with DEFAULT_BASE prefix
            if (!rel.startsWith(DEFAULT_BASE)) fixes.push({type:'prefixed', value: DEFAULT_BASE + rel});
          }
        } catch(e){}

        results.push({el:img, src, ok:false, status, error, fixes});

        // If apply and we have at least one suggested fix, try the first
        if (apply && !ok && Array.isArray(fixes) && fixes.length>0){
          try{
            const candidate = fixes[0].value;
            img.src = candidate;
            console.log('[AutoFix] Replaced', src, '→', candidate);
            // small delay to allow browser to attempt load, then re-check
            await new Promise(r=>setTimeout(r, 240));
            // re-check
            try{ const r2 = await safeFetchHead(candidate); if (r2 && r2.ok){ console.log('[AutoFix] Candidate OK:', candidate); } }catch(e){}
          }catch(e){ console.warn('[AutoFix] Failed to apply fix for', src, e); }
        }

      } else {
        results.push({el:img, src, ok:true, status});
      }
    }
    return results;
  }

  function ensureCsrfMeta({apply=false} = {}){
    const meta = document.querySelector('meta[name="csrf-token"]');
    const token = (meta && meta.content) ? meta.content : (window.CONFIG && window.CONFIG.CSRF_TOKEN ? window.CONFIG.CSRF_TOKEN : null);
    const missing = !meta || !meta.content;
    if (missing && apply){
      try{
        const m = document.createElement('meta');
        m.name = 'csrf-token';
        m.content = token || (window.localStorage && window.localStorage.getItem('CSRF_TOKEN')) || ('temp-' + Math.random().toString(36).slice(2));
        document.head.appendChild(m);
        console.log('[AutoFix] Injected csrf-token meta (temporary).');
        return {missing:true, injected:true, value:m.content};
      }catch(e){ console.warn('[AutoFix] Failed to inject CSRF meta', e); return {missing:true, injected:false}; }
    }
    return {missing, injected:false, value: token};
  }

  async function testApiEndpoints({apply=false} = {}){
    const tokenMeta = document.querySelector('meta[name="csrf-token"]');
    const token = tokenMeta && tokenMeta.content ? tokenMeta.content : (window.CONFIG && window.CONFIG.CSRF_TOKEN ? window.CONFIG.CSRF_TOKEN : null);

    const endpoints = [
      '/backend/api/vehicle/create.php',
      '/backend/api/vehicle/read.php',
      '/backend/api/vehicle/update.php',
      '/backend/api/vehicle/delete.php'
    ];

    const results = [];
    for (const ep of endpoints){
      const url = DEFAULT_BASE + ep;
      try{
        const res = await fetch(url, { method: 'GET', credentials: 'same-origin', headers: { 'X-CSRF-TOKEN': token || '' } });
        results.push({endpoint:ep, url, ok:res.ok, status: res.status});
      }catch(e){ results.push({endpoint:ep, url, ok:false, error: e && e.message ? e.message : String(e)}); }
    }
    return results;
  }

  window.DashboardAutoFix = {
    run: async function(opts = {apply:false}){
      console.log('[DashboardAutoFix] Running diagnostic (apply=' + !!opts.apply + ')');
      const csrf = ensureCsrfMeta({apply: !!opts.apply});
      console.log('[DashboardAutoFix] CSRF:', csrf);

      const images = await checkImages({apply: !!opts.apply});
      const total = images.length;
      const broken = images.filter(i => !i.ok);
      console.log('[DashboardAutoFix] Images checked:', total, 'broken:', broken.length, broken);

      const apis = await testApiEndpoints({apply: !!opts.apply});
      console.log('[DashboardAutoFix] API test results:', apis);

      // Provide summary object
      const summary = { csrf, images, apis };

      // Attach to window for inspection
      window.DashboardAutoFix.last = summary;

      return summary;
    }
  };

  console.log('[DashboardAutoFix] loaded. Run window.DashboardAutoFix.run({apply:false}) to diagnose.');
})();

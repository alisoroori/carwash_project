/* Simple form validator + client-side CSRF helper
   - Rules supported via data-validate attribute: required,email,min:6
   - Error UI inserted from template at frontend/templates/error-block.html
   - CSRF token: generated client-side and stored in localStorage under 'csrf_token'
     Server must validate token against session or other server-side store for real protection.
*/
(function () {
  'use strict';

  // Utility
  function $(sel, ctx = document) { return ctx.querySelector(sel); }
  function $all(sel, ctx = document) { return Array.from(ctx.querySelectorAll(sel)); }

  // Basic validators
  const validators = {
    required(value) { return value !== null && value !== undefined && String(value).trim() !== ''; },
    email(value) {
      if (!value) return false;
      const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      return re.test(String(value).trim());
    },
    min(value, n) { return String(value).trim().length >= Number(n); }
  };

  // Load error template (cached)
  let errorTemplate = null;
  function loadErrorTemplate() {
    if (errorTemplate) return Promise.resolve(errorTemplate);
    return fetch('/carwash_project/frontend/templates/error-block.html')
      .then(r => r.text())
      .then(html => { errorTemplate = html; return html; })
      .catch(() => {
        // fallback simple markup
        errorTemplate = '<div class="form-error" style="color:#b91c1c;font-size:13px;margin-top:6px;"></div>';
        return errorTemplate;
      });
  }

  // Insert or set error message for a form field wrapper
  function setFieldError(field, message) {
    const wrapper = field.closest('.form-row') || field.parentElement || field;
    let err = wrapper.querySelector('.form-error');
    if (!err) {
      // inject template
      const div = document.createElement('div');
      div.innerHTML = errorTemplate;
      err = div.firstElementChild;
      wrapper.appendChild(err);
    }
    err.textContent = message || '';
    if (message) {
      field.classList.add('invalid');
      err.style.display = 'block';
    } else {
      field.classList.remove('invalid');
      err.style.display = 'none';
    }
  }

  // Validate one field by reading data-validate attribute
  function validateField(field) {
    const ruleStr = field.dataset.validate || '';
    if (!ruleStr) return true;
    const parts = ruleStr.split('|').map(s => s.trim()).filter(Boolean);
    const val = field.value;
    for (const p of parts) {
      if (p === 'required') {
        if (!validators.required(val)) {
          setFieldError(field, 'This field is required.');
          return false;
        }
      } else if (p === 'email') {
        if (!validators.email(val)) {
          setFieldError(field, 'Please enter a valid email address.');
          return false;
        }
      } else if (p.startsWith('min:')) {
        const n = p.split(':')[1];
        if (!validators.min(val, n)) {
          setFieldError(field, `Please enter at least ${n} characters.`);
          return false;
        }
      }
    }
    setFieldError(field, ''); // clear
    return true;
  }

  // Generate or read CSRF token from localStorage
  function getCsrfToken() {
    try {
      let t = localStorage.getItem('csrf_token');
      if (!t) {
        // simple random token; server should validate this against session for production
        t = 'cs_' + Math.random().toString(36).slice(2) + Date.now().toString(36);
        localStorage.setItem('csrf_token', t);
      }
      return t;
    } catch (e) {
      // storage not available
      return 'cs_fallback';
    }
  }

  // Attach validator to a given form
  function enhanceForm(form) {
    if (!form || form._fv_attached) return;
    form._fv_attached = true;

    // ensure error template is loaded
    loadErrorTemplate().then(() => {
      // populate CSRF hidden input or create one
      let csrf = form.querySelector('input[name="csrf_token"]');
      if (!csrf) {
        csrf = document.createElement('input');
        csrf.type = 'hidden';
        csrf.name = 'csrf_token';
        form.appendChild(csrf);
      }
      csrf.value = getCsrfToken();

      // find inputs with data-validate
      const fields = $all('[data-validate]', form);
      fields.forEach(f => {
        f.addEventListener('blur', () => validateField(f));
        f.addEventListener('input', () => validateField(f));
      });

      // catch submit
      form.addEventListener('submit', function (e) {
        let ok = true;
        fields.forEach(f => { if (!validateField(f)) ok = false; });
        if (!ok) {
          e.preventDefault();
          // focus first invalid
          const first = form.querySelector('.invalid');
          if (first && typeof first.focus === 'function') first.focus();
        } else {
          // ensure CSRF token is fresh before submit
          csrf.value = getCsrfToken();
        }
      });
    });
  }

  // Auto-enhance forms that have data-enable-validation="1"
  function autoEnhance() {
    const forms = $all('form[data-enable-validation="1"]');
    forms.forEach(enhanceForm);
  }

  // Public API (exposed on window for manual use)
  window.FV = {
    enhanceForm,
    getCsrfToken,
    validateField
  };

  // init on DOM ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', autoEnhance);
  } else {
    autoEnhance();
  }
})();

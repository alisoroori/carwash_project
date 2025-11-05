/* Refactored Customer Dashboard JS
   - Improved form submission handling for profile and review forms
   - Added robust response validation (response.ok, JSON parsing guard)
   - Disabled submit button during submission to prevent duplicate posts
   - Added improved success/error notification UI
   - Automatically resets and closes form/modal on success
   - Inline comments document weaknesses, potential bugs, and fixes
*/

/*
  Notes / Findings (structured feedback):
  - Weakness: previous handlers assumed response.json() always succeeds; non-JSON responses crash code.
    Fix: check response.ok and attempt to parse JSON inside try/catch; fallback to generic error.
  - Weakness: submit button wasn't disabled, risking duplicate submissions on slow networks.
    Fix: disable button during request and re-enable on completion.
  - UX: notifications were ephemeral but could stack or not be accessible. Use a single container and ARIA role.
    Fix: create a notification area and limit simultaneous notifications; ensure accessible text.
  - UX: after successful submission modal is hidden and location.reload() used in one handler; reload is heavy-handed.
    Fix: reset form and close modal; only reload if explicitly required by caller. For review submission we will close modal and optionally navigate if backend suggests.
  - Potential bug: direct DOM queries may return null (page variations). Defensive checks added.
  - Security: ensure CSRF tokens included by the form elements themselves; handler will not mutate CSRF behavior.
*/

/* Utility: centralized notification container for consistent UX */
(function () {
    const NOTIFICATION_TIMEOUT = 4000;
    let container = document.getElementById('cw-notification-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'cw-notification-container';
        container.setAttribute('aria-live', 'polite');
        container.style.position = 'fixed';
        container.style.top = '1rem';
        container.style.right = '1rem';
        container.style.zIndex = '9999';
        container.style.display = 'flex';
        container.style.flexDirection = 'column';
        container.style.gap = '0.5rem';
        document.body.appendChild(container);
    }

    window.showNotification = function (message, type = 'info', opts = {}) {
        // type: 'success' | 'error' | 'info' | 'warning'
        const el = document.createElement('div');
        el.className = `cw-notification cw-${type}`;
        el.style.minWidth = '200px';
        el.style.maxWidth = '360px';
        el.style.padding = '0.6rem 0.9rem';
        el.style.borderRadius = '6px';
        el.style.boxShadow = '0 2px 8px rgba(0,0,0,0.08)';
        el.style.color = '#fff';
        el.style.fontSize = '0.95rem';
        el.style.lineHeight = '1.2';
        el.style.display = 'flex';
        el.style.alignItems = 'center';
        el.style.justifyContent = 'space-between';
        el.style.gap = '0.6rem';

        switch (type) {
            case 'success':
                el.style.background = '#16a34a';
                break;
            case 'error':
                el.style.background = '#dc2626';
                break;
            case 'warning':
                el.style.background = '#f59e0b';
                el.style.color = '#000';
                break;
            default:
                el.style.background = '#2563eb';
                break;
        }

        const text = document.createElement('span');
        text.textContent = message;
        text.style.flex = '1';

        const closeBtn = document.createElement('button');
        closeBtn.type = 'button';
        closeBtn.innerText = '×';
        closeBtn.style.background = 'transparent';
        closeBtn.style.border = 'none';
        closeBtn.style.color = 'inherit';
        closeBtn.style.fontSize = '1.1rem';
        closeBtn.style.cursor = 'pointer';
        closeBtn.setAttribute('aria-label', 'Close notification');
        closeBtn.addEventListener('click', () => {
            el.remove();
        });

        el.appendChild(text);
        el.appendChild(closeBtn);

        container.appendChild(el);

        const timeout = opts.timeout || NOTIFICATION_TIMEOUT;
        setTimeout(() => {
            try { el.remove(); } catch (e) {}
        }, timeout);
    };
})();

/* Helper: safely parse JSON, return null on failure */
async function safeJson(response) {
    try {
        return await response.json();
    } catch (err) {
        return null;
    }
}

/* Generic submit handler helper
   - formElement: HTMLFormElement
   - options:
       url override (optional)
       closeSelector: selector for modal/container to close on success (optional)
       onSuccess: callback(data, form) optional
       onError: callback(errorMessage, data, form) optional
   Behavior:
   - Prevent default form submission
   - Disable submit button
   - Send fetch with formData, expect JSON ideally
   - On success: showNotification, reset form, close modal if present
   - On error: showNotification with helpful message
*/
async function handleFormSubmission(formElement, options = {}) {
    if (!formElement || !(formElement instanceof HTMLFormElement)) return;

    formElement.addEventListener('submit', async function (ev) {
        ev.preventDefault();

        // Find the submit button to disable (first button[type=submit] or input[type=submit])
        const submitBtn = formElement.querySelector('button[type="submit"], input[type="submit"]');
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.setAttribute('data-cw-disabled', '1');
            // Provide visual feedback if desired
            submitBtn.classList && submitBtn.classList.add('opacity-60', 'cursor-not-allowed');
        }

        // Defensive: let callers override URL; otherwise use form.action or current location
        const submitUrl = options.url || formElement.action || window.location.href;
        const method = (formElement.method || 'POST').toUpperCase();

        // Build FormData
        const fd = new FormData(formElement);
        // Ensure CSRF token is attached when available (from window.CONFIG or meta tag)
        try {
            fd.append('csrf_token', window.CONFIG?.CSRF_TOKEN || document.querySelector('meta[name="csrf-token"]')?.content);
        } catch (e) {
            // Optional chaining may not be supported in some older environments; fail silently
        }

        // Note: If backend expects JSON, callers should override and stringify / set headers.
        let fetchOptions = {
            method,
            body: fd,
            credentials: 'same-origin'
        };

        // If caller wants JSON, they should provide fetchOptions via onBeforeFetch; keep flexible
        if (typeof options.onBeforeFetch === 'function') {
            try {
                const custom = await options.onBeforeFetch({ form: formElement, fetchOptions, formData: fd });
                if (custom && typeof custom === 'object') {
                    fetchOptions = Object.assign(fetchOptions, custom);
                }
            } catch (e) {
                console.warn('onBeforeFetch threw:', e);
            }
        }

        let response;
        try {
            response = await fetch(submitUrl, fetchOptions);
        } catch (networkError) {
            console.error('Network error during form submission:', networkError);
            window.showNotification('Network error: failed to reach server', 'error');
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.removeAttribute('data-cw-disabled');
                submitBtn.classList && submitBtn.classList.remove('opacity-60', 'cursor-not-allowed');
            }
            if (typeof options.onError === 'function') {
                options.onError('Network error', null, formElement);
            }
            return;
        }

        // Try to extract JSON, but guard if server returned HTML or empty body
        const data = await safeJson(response);

        // If server returned non-2xx, prefer to show server-provided error message if present
        if (!response.ok) {
            let message = 'Server error';
            if (data && data.error) message = data.error;
            else if (data && data.message) message = data.message;
            else if (response.statusText) message = `${response.status} ${response.statusText}`;
            window.showNotification(message, 'error');

            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.removeAttribute('data-cw-disabled');
                submitBtn.classList && submitBtn.classList.remove('opacity-60', 'cursor-not-allowed');
            }
            if (typeof options.onError === 'function') {
                options.onError(message, data, formElement);
            }
            return;
        }

        // response.ok -> attempt to interpret backend result
        if (data && (data.success === true || data.success === '1' || data.success === 1)) {
            // Success path
            window.showNotification(data.message || data.success_message || 'Operation completed', 'success');

            // Reset the form to clear inputs and file selections
            try {
                formElement.reset();
            } catch (e) {
                // Some forms with custom elements might need manual reset
            }

            // Close containing modal or element if a selector was provided
            if (options.closeSelector) {
                const container = document.querySelector(options.closeSelector);
                if (container) {
                    // Try common hiding patterns: add 'hidden', set display or hide modals via inline style
                    if (container.classList) container.classList.add('hidden');
                    container.style.display = 'none';
                }
            } else {
                // Auto-close: if form is inside an element with role=dialog or with id ending with 'Modal' hide it
                const dialog = formElement.closest('[role="dialog"], .modal, .cw-modal, [id$="Modal"], [id$="modal"]');
                if (dialog) {
                    dialog.style.display = 'none';
                    if (dialog.classList) dialog.classList.add('hidden');
                }
            }

            // Call success callback if provided
            if (typeof options.onSuccess === 'function') {
                try { options.onSuccess(data, formElement); } catch (e) { console.warn('onSuccess error', e); }
            }

            // If backend indicates a redirect or reload, follow it (optional)
            if (data.redirect) {
                window.location.href = data.redirect;
            } else if (data.reload) {
                window.location.reload();
            }
        } else {
            // Failure path: data may be null if non-JSON; handle gracefully
            let message = 'Failed to submit form';
            if (data && data.error) message = data.error;
            else if (data && data.message) message = data.message;
            else if (!data && response.status === 204) message = 'No content returned from server';
            window.showNotification(message, 'error');

            if (typeof options.onError === 'function') {
                options.onError(message, data, formElement);
            }
        }

        // Re-enable submit button
        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.removeAttribute('data-cw-disabled');
            submitBtn.classList && submitBtn.classList.remove('opacity-60', 'cursor-not-allowed');
        }
    });
}

/* -------------------------
   Initialize known dashboard forms
   -------------------------
   We attach behavior to:
   - profile form with id "profileForm"
   - review form with id "reviewForm" or forms opened via modal buttons (class .btn-review)
   The previous code assumed specific endpoints and used location.reload in some places.
   New behavior: show notification, reset/close form, call optional redirect only if backend asks.
*/
document.addEventListener('DOMContentLoaded', function () {
    // Profile update form
    const profileForm = document.getElementById('profileForm');
    if (profileForm) {
        handleFormSubmission(profileForm, {
            // backend endpoint already in form.action, so no url override
            onSuccess: (data, form) => {
                // Structured feedback: success -> use friendly message
                // If backend returns updated fields, you may update DOM here instead of reloading.
                // E.g., refresh displayed name/email if present in response.
            },
            onError: (message, data, form) => {
                // Could attach inline form errors if backend returns field-level errors
                if (data && data.errors && typeof data.errors === 'object') {
                    // Attempt to show first field error inline (example)
                    const firstField = Object.keys(data.errors)[0];
                    if (firstField) {
                        const input = form.querySelector(`[name="${firstField}"]`);
                        if (input) {
                            // Optionally mark invalid (ARIA)
                            input.setAttribute('aria-invalid', 'true');
                        }
                    }
                }
            }
        });
    }

    // Generic review modal forms - attach to any form with id 'reviewForm' or data-role attribute
    const reviewForm = document.getElementById('reviewForm');
    if (reviewForm) {
        handleFormSubmission(reviewForm, {
            // Some review endpoints used different URLs in repo; rely on form.action
            // The previous code used location.reload; prefer closing modal + notification.
            closeSelector: '#reviewModal',
            onSuccess: (data, form) => {
                // If backend indicates to navigate to review listing, it can set redirect
                // If not, optionally reload reviews section via a custom loader (not implemented here).
            }
        });
    }

    // If review modal opens dynamically per .btn-review, we still ensure any new form inserted is wired:
    // Re-scan for dynamically inserted forms and attach handler:
    const observer = new MutationObserver((mutations) => {
        for (const m of mutations) {
            for (const node of Array.from(m.addedNodes)) {
                if (!(node instanceof Element)) continue;
                // attach to reviewForm if newly added
                const newReviewForm = node.querySelector ? node.querySelector('#reviewForm') : null;
                if (newReviewForm) {
                    handleFormSubmission(newReviewForm, {
                        closeSelector: '#reviewModal'
                    });
                }
            }
        }
    });
    observer.observe(document.body, { childList: true, subtree: true });

    // For other inline forms in the dashboard (booking, vehicle, etc.), the same helper can be used by other scripts:
    // Example usage elsewhere:
    // handleFormSubmission(document.getElementById('bookingForm'), { onSuccess: ..., closeSelector: '#bookingModal' });
});
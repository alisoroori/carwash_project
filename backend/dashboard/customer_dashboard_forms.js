
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

/* Generic submit handler helper */
async function handleFormSubmission(formElement, options = {}) {
    if (!formElement || !(formElement instanceof HTMLFormElement)) return;

    formElement.addEventListener('submit', async function (ev) {
        ev.preventDefault();

        const submitBtn = formElement.querySelector('button[type="submit"], input[type="submit"]');
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.setAttribute('data-cw-disabled', '1');
            submitBtn.classList && submitBtn.classList.add('opacity-60', 'cursor-not-allowed');
        }

        const submitUrl = options.url || formElement.action || window.location.href;
        const method = (formElement.method || 'POST').toUpperCase();

        const fd = new FormData(formElement);

        let fetchOptions = {
            method,
            body: fd,
            credentials: 'same-origin'
        };

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

        // Use apiCall wrapper which normalizes JSON parsing and non-OK responses
        let data;
        try {
            const resObj = await apiCall(submitUrl, fetchOptions);
            data = resObj.data;
        } catch (err) {
            console.error('Network error during form submission:', err);
            window.showNotification(err.message || 'Network error: failed to reach server', 'error');
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.removeAttribute('data-cw-disabled');
                submitBtn.classList && submitBtn.classList.remove('opacity-60', 'cursor-not-allowed');
            }
            if (typeof options.onError === 'function') {
                options.onError(err.message || 'Network error', err.data || null, formElement);
            }
            return;
        }

        if (data && (data.success === true || data.success === '1' || data.success === 1)) {
            window.showNotification(data.message || data.success_message || 'Operation completed', 'success');

            try { formElement.reset(); } catch (e) {}

            if (options.closeSelector) {
                const container = document.querySelector(options.closeSelector);
                if (container) {
                    if (container.classList) container.classList.add('hidden');
                    container.style.display = 'none';
                }
            } else {
                const dialog = formElement.closest('[role="dialog"], .modal, .cw-modal, [id$="Modal"], [id$="modal"]');
                if (dialog) {
                    dialog.style.display = 'none';
                    if (dialog.classList) dialog.classList.add('hidden');
                }
            }

            if (typeof options.onSuccess === 'function') {
                try { options.onSuccess(data, formElement); } catch (e) { console.warn('onSuccess error', e); }
            }

            if (data.redirect) {
                window.location.href = data.redirect;
            } else if (data.reload) {
                window.location.reload();
            }
        } else {
            let message = 'Failed to submit form';
            if (data && data.error) message = data.error;
            else if (data && data.message) message = data.message;
            else if (!data && response.status === 204) message = 'No content returned from server';
            window.showNotification(message, 'error');

            if (typeof options.onError === 'function') {
                options.onError(message, data, formElement);
            }
        }

        if (submitBtn) {
            submitBtn.disabled = false;
            submitBtn.removeAttribute('data-cw-disabled');
            submitBtn.classList && submitBtn.classList.remove('opacity-60', 'cursor-not-allowed');
        }
    });
}

document.addEventListener('DOMContentLoaded', function () {
    const profileForm = document.getElementById('profileForm');
    if (profileForm) {
        handleFormSubmission(profileForm, {
            onSuccess: (data, form) => {
            },
            onError: (message, data, form) => {
                if (data && data.errors && typeof data.errors === 'object') {
                    const firstField = Object.keys(data.errors)[0];
                    if (firstField) {
                        const input = form.querySelector(`[name="${firstField}"]`);
                        if (input) {
                            input.setAttribute('aria-invalid', 'true');
                        }
                    }
                }
            }
        });
    }

    const reviewForm = document.getElementById('reviewForm');
    if (reviewForm) {
        handleFormSubmission(reviewForm, {
            closeSelector: '#reviewModal',
            onSuccess: (data, form) => {
            }
        });
    }

    const observer = new MutationObserver((mutations) => {
        for (const m of mutations) {
            for (const node of Array.from(m.addedNodes)) {
                if (!(node instanceof Element)) continue;
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
});

// Vehicle form wiring: attach robust handler for vehicle add/update
(function attachVehicleFormHandler(){
    const form = document.getElementById('vehicleFormInline');
    if (!form) return;

    handleFormSubmission(form, {
        url: '/carwash_project/backend/dashboard/vehicle_api.php',
        onBeforeFetch: async ({ form, fetchOptions, formData }) => {
            // Ensure action param exists (create or update)
            const actionInput = form.querySelector('#vehicleFormAction');
            const action = actionInput ? (actionInput.value || 'create') : (formData.get('action') || 'create');
            formData.set('action', action);
            // id field
            const vid = form.querySelector('#vehicle_id_input_inline') ? form.querySelector('#vehicle_id_input_inline').value : formData.get('id');
            if (vid) formData.set('id', vid);

            // Attach CSRF token idempotently
            try {
                if (window.VDR && typeof window.VDR.appendCsrfOnce === 'function') {
                    window.VDR.appendCsrfOnce(formData);
                } else {
                    const token = (window.CONFIG && window.CONFIG.CSRF_TOKEN) || document.querySelector('meta[name="csrf-token"]')?.content;
                    if (token && !formData.get('csrf_token')) formData.set('csrf_token', token);
                }
            } catch (e) { console.warn('CSRF append failed', e); }

            // Keep default fetch options (FormData body). Return nothing or custom options.
            return { body: formData, method: 'POST', credentials: 'same-origin' };
        },
        closeSelector: '#vehicleInlineSection',
        onSuccess: (data, formEl) => {
            try { window.showNotification(data.message || 'Araç başarıyla kaydedildi', 'success'); } catch(e){}
            // Refresh vehicles list without full reload
            try { if (typeof loadUserVehicles === 'function') loadUserVehicles(); } catch (e) { console.warn('loadUserVehicles refresh failed', e); }
        },
        onError: (message, data, formEl) => {
            try { window.showNotification(message || 'Araç kaydı başarısız oldu', 'error'); } catch(e){}
            // show field errors inline if present
            if (data && data.errors && typeof data.errors === 'object') {
                Object.keys(data.errors).forEach((field) => {
                    const input = form.querySelector(`[name="${field}"]`);
                    if (input) input.setAttribute('aria-invalid', 'true');
                });
            }
        }
    });

    // Expose a programmatic helper to reset and close inline vehicle form
    window.closeAndResetVehicleForm = function() {
        try { form.reset(); } catch(e){}
        const container = document.getElementById('vehicleInlineSection');
        if (container) { container.style.display = 'none'; if (container.classList) container.classList.add('hidden'); }
    };

    // Override/deleteVehicle to ensure CSRF and consistent error handling
    window.deleteVehicle = async function deleteVehicle(vehicleId){
        if (!vehicleId) return;
        if (!confirm('Bu aracı silmek istediğinizden emin misiniz? Bu işlem geri alınamaz.')) return;

        const fd = new FormData();
        fd.set('action','delete');
        fd.set('id', vehicleId);
        try {
            if (window.VDR && typeof window.VDR.appendCsrfOnce === 'function') window.VDR.appendCsrfOnce(fd);
            else {
                const token = (window.CONFIG && window.CONFIG.CSRF_TOKEN) || document.querySelector('meta[name="csrf-token"]')?.content;
                if (token) fd.set('csrf_token', token);
            }
        } catch(e) { /* ignore */ }

        try {
            const resObj = await apiCall('/carwash_project/backend/dashboard/vehicle_api.php', { method: 'POST', body: fd, credentials: 'same-origin' });
            const data = resObj.data;
            if (data && (data.success === true || data.success === '1' || data.status === 'success')) {
                // remove DOM card if present
                try { const card = document.querySelector(`[data-vehicle-id="${vehicleId}"]`); if (card) card.remove(); } catch(e){}
                window.showNotification(data.message || 'Araç silindi', 'success');
                try { if (typeof loadUserVehicles === 'function') loadUserVehicles(); } catch(e){}
            } else {
                const msg = (data && (data.error || data.message)) || 'Araç silinirken hata oluştu.';
                window.showNotification(msg, 'error');
            }
        } catch (err) {
            console.error('Delete vehicle error:', err);
            window.showNotification(err.message || 'Araç silinirken bir hata oluştu.', 'error');
        }
    };
})();

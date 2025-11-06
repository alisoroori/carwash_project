// reviews_submission.js
document.addEventListener('DOMContentLoaded', function () {
    function safeJson(response) {
        return response.text().then(text => {
            try { return JSON.parse(text); } catch (e) { return null; }
        });
    }

    document.querySelectorAll('.js-review-form').forEach(form => {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const submitBtn = form.querySelector('button[type="submit"], input[type="submit"]');
            if (submitBtn) { submitBtn.disabled = true; submitBtn.classList && submitBtn.classList.add('opacity-60'); }

            const fd = new FormData(form);
            // normalize field names (if your form uses 'review_text' or 'comment')
            if (!fd.get('comment') && form.querySelector('textarea[name="review_text"]')) {
                fd.set('comment', form.querySelector('textarea[name="review_text"]').value);
            }

            try {
                const resp = await fetch('/carwash_project/backend/api/reviews/submit_review.php', {
                    method: 'POST',
                    body: fd,
                    credentials: 'same-origin'
                });

                const data = await safeJson(resp);

                if (!resp.ok) {
                    const msg = data?.message || `Server error: ${resp.status}`;
                    (window.showNotification || window.alert)(msg);
                } else if (data && data.success) {
                    (window.showNotification || window.alert)(data.message || 'Review submitted');
                    try { form.reset(); } catch (err) {}
                    const modal = form.closest('[id$="Modal"], .modal, [role="dialog"]');
                    if (modal) { modal.style.display = 'none'; modal.classList && modal.classList.add('hidden'); }
                    if (data.redirect) window.location.href = data.redirect;
                    else if (data.reload) window.location.reload();
                } else {
                    const message = data?.message || 'Failed to submit review';
                    if (data?.errors) {
                        const first = Object.keys(data.errors)[0];
                        (window.showNotification || window.alert)(`${message}: ${data.errors[first]}`);
                    } else {
                        (window.showNotification || window.alert)(message);
                    }
                }
            } catch (err) {
                console.error('Network error:', err);
                (window.showNotification || window.alert)('Network error: failed to reach server');
            } finally {
                if (submitBtn) { submitBtn.disabled = false; submitBtn.classList && submitBtn.classList.remove('opacity-60'); }
            }
        });
    });
});
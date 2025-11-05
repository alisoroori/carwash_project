/**
 * frontend/js/review_form_handler.js
 * Unified handler for all review submission forms.
 */

window.initReviewForms = function (selector = '.js-review-form', opts = {}) {
    const forms = document.querySelectorAll(selector);
    if (!forms.length) {
        console.warn('[ReviewForms] No forms found for selector:', selector);
        return;
    }

    forms.forEach((form) => {
        form.addEventListener('submit', (e) => {
            e.preventDefault();
            window.handleReviewFormSubmission(form, opts);
        });
    });

    console.info(`[ReviewForms] Initialized ${forms.length} form(s).`);
};

window.handleReviewFormSubmission = async function (form, opts = {}) {
    try {
        const formData = new FormData(form);

        // Extract canonical fields
        const entityType = form.dataset.entityType || formData.get('entity_type');
        const entityId = form.dataset.entityId || formData.get('entity_id');
        const rating = formData.get('rating');
        const reviewText = formData.get('review_text') || formData.get('comment') || '';

        if (!entityType || !entityId || !rating) {
            alert('Please fill out all required fields.');
            return;
        }

        // Append canonical fields explicitly
        formData.set('entity_type', entityType);
        formData.set('entity_id', entityId);
        formData.set('review_text', reviewText);

        // Append CSRF token if available
        let csrfToken =
            window.CONFIG?.CSRF_TOKEN ||
            document.querySelector('meta[name="csrf-token"]')?.content ||
            '';

        if (csrfToken) formData.set('csrf_token', csrfToken);

        const response = await fetch('/carwash_project/backend/api/reviews/submit_review.php', {
            method: 'POST',
            body: formData,
            credentials: 'same-origin',
        });

        let json = null;
        try {
            json = await response.json();
        } catch (err) {
            console.error('[ReviewForms] JSON parse error', err);
            alert('Unexpected response from server.');
            return;
        }

        if (!response.ok || !json.success) {
            console.warn('[ReviewForms] Submission failed:', json);
            alert(json.message || 'Failed to submit review.');
            return;
        }

        console.log('[ReviewForms] Review submitted:', json);

        // ✅ Success feedback
        alert(json.message || 'Review submitted successfully.');

        // ✅ Optional callbacks
        if (typeof opts.onSuccess === 'function') opts.onSuccess(json);
        else if (typeof opts.onRefresh === 'function') opts.onRefresh(json);
        else if (typeof window.loadReviews === 'function') window.loadReviews();

    } catch (error) {
        console.error('[ReviewForms] Unexpected error:', error);
        alert('An unexpected error occurred. Please try again.');
    }
};

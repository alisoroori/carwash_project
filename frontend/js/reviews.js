// ...existing code...
/* Helper: safely parse JSON, return null on failure */
async function safeJson(response) {
    try {
        // Prefer response.json() but guard against invalid JSON or empty bodies
        if (!response) return null;
        // If response has no content (204), return null
        const contentType = response.headers && response.headers.get ? response.headers.get('Content-Type') : '';
        if (response.status === 204) return null;
        if (contentType && contentType.indexOf('application/json') === -1) {
            // still attempt parse, but guard
            try { return await response.json(); } catch (_) { return null; }
        }
        return await response.json();
    } catch (err) {
        return null;
    }
}
// ...existing code...

/* Generic submit handler helper
   - formElement: HTMLFormElement
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
            submitBtn.classList && submitBtn.classList.add('opacity-60', 'cursor-not-allowed');
        }

        const submitUrl = options.url || formElement.action || window.location.href;
        const method = (formElement.method || 'POST').toUpperCase();

        const fd = new FormData(formElement);

        // Ensure CSRF token is attached when available
        try {
            const token = window.CONFIG?.CSRF_TOKEN || document.querySelector('meta[name="csrf-token"]')?.content;
            if (token && !fd.has('csrf_token')) fd.append('csrf_token', token);
        } catch (e) { /* silent */ }

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

        // Use safeJson and check response.ok
        const data = await safeJson(response);

        if (!response.ok) {
            let message = 'Server error';
            if (data && (data.error || data.message)) message = data.error || data.message;
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

        if (data && (data.success === true || data.success === '1' || data.success === 1)) {
            window.showNotification(data.message || data.success_message || 'Operation completed', 'success');

            try { formElement.reset(); } catch (e) { /* ignore */ }

            if (options.closeSelector) {
                const container = document.querySelector(options.closeSelector);
                if (container) {
                    if (container.classList) container.classList.add('hidden');
                    container.style.display = 'none';
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
            if (data && (data.error || data.message)) message = data.error || data.message;
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
// ...existing code...// ...existing code...

// Add safeJson helper (same behavior as dashboard.js)
async function safeJson(response) {
    try {
        if (!response) return null;
        if (response.status === 204) return null;
        const contentType = response.headers && response.headers.get ? response.headers.get('Content-Type') : '';
        if (contentType && contentType.indexOf('application/json') === -1) {
            try { return await response.json(); } catch (_) { return null; }
        }
        return await response.json();
    } catch (err) {
        return null;
    }
}

// ...existing code...

class ReviewComponent {
    /*...*/
    setupReviewForm() {
        const form = this.container.querySelector('.review-form');
        if (!form) return;

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const formData = new FormData(form);
            const rating = formData.get('rating');
            const comment = formData.get('comment');

            try {
                try {
                    const resObj = await apiCall('/carwash_project/backend/api/reviews/submit_review.php', {
                        method: 'POST',
                        body: formData,
                        credentials: 'same-origin',
                        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
                    });
                    const data = resObj.data;
                    if (data && data.success) {
                        await this.loadReviews();
                        form.reset();
                    } else {
                        alert(data?.message || data?.error || 'Değerlendirme gönderilemedi');
                    }
                } catch (err) {
                    console.warn('[ReviewForms] Submission failed:', err);
                    alert(err.message || 'Sistem hatası');
                }
            } catch (error) {
                console.error('Error submitting review:', error);
                alert('Sistem hatası');
            }
        });
    }
    /*...*/
}

// ...existing code...class ReviewComponent {
    constructor(containerId) {
        this.container = document.getElementById(containerId);
        this.carwashId = this.container.dataset.carwashId;
        this.init();
    }

    async init() {
        await this.loadReviews();
        this.setupReviewForm();
    }

    async loadReviews() {
        try {
            try {
                const resObj = await apiCall(`../backend/api/get_reviews.php?carwash_id=${this.carwashId}`);
                const data = resObj.data;
                if (data && data.success) {
                    this.renderReviews(data.reviews);
                    this.renderStats(data.stats);
                }
            } catch (err) {
                console.error('Error loading reviews:', err);
            }
        } catch (error) {
            console.error('Error loading reviews:', error);
        }
    }

    renderReviews(reviews) {
        const reviewsHtml = reviews.map(review => `
            <div class="border-b last:border-0 py-4">
                <div class="flex justify-between items-start mb-2">
                    <div>
                        <div class="font-semibold">${review.user_name}</div>
                        <div class="text-yellow-400">
                            ${'★'.repeat(review.rating)}${'☆'.repeat(5-review.rating)}
                        </div>
                    </div>
                    <div class="flex items-center space-x-2">
                        <div class="text-sm text-gray-500">
                            ${new Date(review.created_at).toLocaleDateString('tr-TR')}
                        </div>
                        <button onclick="showReportModal(${review.id})"
                                class="text-gray-400 hover:text-red-600">
                            <i class="fas fa-flag"></i>
                        </button>
                    </div>
                </div>
                <p class="text-gray-600">${review.comment}</p>
            </div>
        `).join('');

        this.container.querySelector('.reviews-list').innerHTML = reviewsHtml;
    }

    renderStats(stats) {
        this.container.querySelector('.rating-stats').innerHTML = `
            <div class="text-center">
                <div class="text-3xl font-bold">${stats.avg_rating.toFixed(1)}</div>
                <div class="text-yellow-400 text-xl">
                    ${'★'.repeat(Math.round(stats.avg_rating))}
                </div>
                <div class="text-sm text-gray-500">${stats.total_reviews} değerlendirme</div>
            </div>
        `;
    }

    setupReviewForm() {
        const form = this.container.querySelector('.review-form');
        if (!form) return;

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const formData = new FormData(form);
            const rating = formData.get('rating');
            const comment = formData.get('comment');

            try {
                const response = await fetch('../backend/api/submit_review.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        carwash_id: this.carwashId,
                        rating: parseInt(rating),
                        comment
                    })
                });

                const data = await response.json();
                
                if (data.success) {
                    await this.loadReviews();
                    form.reset();
                } else {
                    alert(data.error || 'Değerlendirme gönderilemedi');
                }
            } catch (error) {
                console.error('Error submitting review:', error);
                alert('Sistem hatası');
            }
        });
    }

    showReportModal(reviewId) {
        const modal = document.createElement('div');
        modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center';
        modal.innerHTML = `
            <div class="bg-white rounded-lg p-6 w-full max-w-md">
                <h3 class="text-lg font-bold mb-4">Değerlendirmeyi Bildir</h3>
                <form id="reportForm" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Sebep</label>
                        <select name="reason" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            <option value="spam">Spam</option>
                            <option value="offensive">Rahatsız Edici İçerik</option>
                            <option value="inappropriate">Uygunsuz</option>
                            <option value="other">Diğer</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Açıklama</label>
                        <textarea name="description" rows="3"
                                  class="mt-1 block w-full rounded-md border-gray-300 shadow-sm"></textarea>
                    </div>
                    <div class="flex justify-end space-x-2">
                        <button type="button" onclick="closeReportModal()"
                                class="px-4 py-2 text-gray-600 hover:text-gray-800">
                            İptal
                        </button>
                        <button type="submit"
                                class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700">
                            Bildir
                        </button>
                    </div>
                </form>
            </div>
        `;

        document.body.appendChild(modal);

        const form = modal.querySelector('#reportForm');
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            await this.submitReport(reviewId, form);
        });
    }

    async submitReport(reviewId, form) {
        const formData = new FormData(form);
        
        try {
            const response = await fetch('../backend/api/report_review.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    review_id: reviewId,
                    reason: formData.get('reason'),
                    description: formData.get('description')
                })
            });

            const data = await response.json();
            
            if (data.success) {
                alert('Bildiriminiz için teşekkürler');
                this.closeReportModal();
            } else {
                alert(data.error || 'Bildirim gönderilemedi');
            }
        } catch (error) {
            console.error('Error submitting report:', error);
            alert('Sistem hatası');
        }
    }

    closeReportModal() {
        const modal = document.querySelector('.fixed');
        if (modal) {
            modal.remove();
        }
    }
}

class ReviewManager {
    constructor() {
        this.selectedRating = 0;
        this.form = document.getElementById('reviewForm');
        this.init();
    }

    init() {
        this.setupRatingStars();
        this.setupFormSubmission();
    }

    setupRatingStars() {
        const stars = document.querySelectorAll('.rating-star');
        
        stars.forEach(star => {
            star.addEventListener('click', (e) => {
                const rating = parseInt(e.currentTarget.dataset.rating);
                this.setRating(rating);
            });

            star.addEventListener('mouseover', (e) => {
                const rating = parseInt(e.currentTarget.dataset.rating);
                this.highlightStars(rating);
            });
        });

        document.getElementById('ratingStars').addEventListener('mouseleave', () => {
            this.highlightStars(this.selectedRating);
        });
    }

    setRating(rating) {
        this.selectedRating = rating;
        this.highlightStars(rating);
    }

    highlightStars(rating) {
        const stars = document.querySelectorAll('.rating-star');
        stars.forEach((star, index) => {
            star.classList.toggle('text-yellow-400', index < rating);
            star.classList.toggle('text-gray-300', index >= rating);
        });
    }

    setupFormSubmission() {
        this.form.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            if (!this.selectedRating) {
                alert('Please select a rating');
                return;
            }

            const formData = new FormData(this.form);
            formData.append('rating', this.selectedRating);

            try {
                const response = await fetch('/carwash_project/backend/api/reviews/submit_review.php', {
                    method: 'POST',
                    body: formData
                });

                const data = await response.json();
                
                if (data.success) {
                    window.location.href = '/carwash_project/frontend/dashboard/user_dashboard.php?review_submitted=1';
                } else {
                    alert(data.error || 'Failed to submit review');
                }
            } catch (error) {
                console.error('Error submitting review:', error);
                alert('Failed to submit review. Please try again.');
            }
        });
    }
}

// Initialize component
document.addEventListener('DOMContentLoaded', () => {
    const reviewsContainer = document.getElementById('reviewsComponent');
    if (reviewsContainer) {
        new ReviewComponent('reviewsComponent');
    }

    const reviewManager = new ReviewManager();
});
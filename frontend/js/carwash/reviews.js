class ReviewManager {
    constructor() {
        this.endpoints = {
            reviews: '/carwash_project/backend/api/carwash/reviews/list.php',
            respond: '/carwash_project/backend/api/carwash/reviews/respond.php',
            metrics: '/carwash_project/backend/api/carwash/reviews/metrics.php',
            report: '/carwash_project/backend/api/carwash/reviews/report.php'
        };
        this.filters = {
            rating: 'all',
            status: 'all',
            timeframe: '30days'
        };
        this.init();
    }

    async init() {
        this.setupEventListeners();
        await this.loadReviews();
        this.initializeMetricsChart();
    }

    setupEventListeners() {
        // Rating filter
        document.getElementById('ratingFilter')?.addEventListener('change', (e) => {
            this.filters.rating = e.target.value;
            this.loadReviews();
        });

        // Status filter
        document.getElementById('statusFilter')?.addEventListener('change', (e) => {
            this.filters.status = e.target.value;
            this.loadReviews();
        });

        // Timeframe selector
        document.getElementById('timeframeSelect')?.addEventListener('change', (e) => {
            this.filters.timeframe = e.target.value;
            this.loadReviews();
        });

        // Response form submissions are handled in renderReviews
    }

    async loadReviews() {
        try {
            const params = new URLSearchParams(this.filters);
            const [reviews, metrics] = await Promise.all([
                fetch(`${this.endpoints.reviews}?${params}`).then(r => r.json()),
                fetch(`${this.endpoints.metrics}?${params}`).then(r => r.json())
            ]);

            this.renderReviews(reviews);
            this.updateMetrics(metrics);
        } catch (error) {
            this.showError('Failed to load reviews');
        }
    }

    renderReviews(reviews) {
        const container = document.getElementById('reviewsList');
        if (!container) return;

        container.innerHTML = reviews.map(review => `
            <div class="review-card ${review.status}" data-id="${review.id}">
                <div class="review-header">
                    <div class="rating">
                        ${'★'.repeat(review.rating)}${'☆'.repeat(5 - review.rating)}
                    </div>
                    <span class="review-date">
                        ${new Date(review.created_at).toLocaleDateString()}
                    </span>
                </div>
                
                <div class="review-content">
                    <p class="review-text">${this.sanitizeHTML(review.content)}</p>
                    <div class="customer-info">
                        <span>By: ${this.sanitizeHTML(review.customer_name)}</span>
                        <span>Service: ${this.sanitizeHTML(review.service_name)}</span>
                    </div>
                </div>

                ${review.response ? this.renderResponse(review.response) : ''}
                
                <div class="review-actions">
                    ${!review.response ? `
                        <button class="respond-btn" 
                                onclick="reviewManager.showResponseForm('${review.id}')">
                            Respond
                        </button>
                    ` : ''}
                    <button class="report-btn" 
                            onclick="reviewManager.reportReview('${review.id}')">
                        Report
                    </button>
                </div>
            </div>
        `).join('');
    }

    renderResponse(response) {
        return `
            <div class="review-response">
                <h4>Our Response:</h4>
                <p>${this.sanitizeHTML(response.content)}</p>
                <span class="response-date">
                    Responded on ${new Date(response.created_at).toLocaleDateString()}
                </span>
            </div>
        `;
    }

    showResponseForm(reviewId) {
        const form = document.createElement('div');
        form.className = 'response-form';
        form.innerHTML = `
            <form onsubmit="return reviewManager.submitResponse(event, '${reviewId}')">
                <textarea name="response" 
                          placeholder="Write your response..."
                          required></textarea>
                <div class="form-actions">
                    <button type="submit">Submit Response</button>
                    <button type="button" onclick="this.closest('.response-form').remove()">
                        Cancel
                    </button>
                </div>
            </form>
        `;

        const reviewCard = document.querySelector(`[data-id="${reviewId}"]`);
        reviewCard?.appendChild(form);
    }

    async submitResponse(event, reviewId) {
        event.preventDefault();
        const form = event.target;
        const response = form.response.value;

        try {
            const result = await fetch(this.endpoints.respond, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ review_id: reviewId, response })
            }).then(r => r.json());

            if (result.success) {
                this.showSuccess('Response submitted successfully');
                await this.loadReviews();
            } else {
                this.showError(result.message);
            }
        } catch (error) {
            this.showError('Failed to submit response');
        }

        return false;
    }

    async reportReview(reviewId) {
        if (!confirm('Are you sure you want to report this review?')) return;

        try {
            const result = await fetch(this.endpoints.report, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ review_id: reviewId })
            }).then(r => r.json());

            if (result.success) {
                this.showSuccess('Review reported successfully');
                await this.loadReviews();
            } else {
                this.showError(result.message);
            }
        } catch (error) {
            this.showError('Failed to report review');
        }
    }

    sanitizeHTML(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    showSuccess(message) {
        const alert = document.createElement('div');
        alert.className = 'success-alert';
        alert.textContent = message;
        document.body.appendChild(alert);
        setTimeout(() => alert.remove(), 3000);
    }

    showError(message) {
        const alert = document.createElement('div');
        alert.className = 'error-alert';
        alert.textContent = message;
        document.body.appendChild(alert);
        setTimeout(() => alert.remove(), 5000);
    }
}

// Initialize review manager
document.addEventListener('DOMContentLoaded', () => new ReviewManager());
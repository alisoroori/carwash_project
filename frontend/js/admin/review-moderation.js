class ReviewModeration {
    constructor() {
        this.endpoints = {
            reviews: '/carwash_project/backend/api/admin/reviews/pending.php',
            moderate: '/carwash_project/backend/api/admin/reviews/moderate.php',
            stats: '/carwash_project/backend/api/admin/reviews/moderation-stats.php'
        };
        this.currentPage = 1;
        this.itemsPerPage = 10;
        this.filters = {
            status: 'pending',
            flag: 'all'
        };
        this.init();
    }

    async init() {
        this.setupEventListeners();
        await this.loadPendingReviews();
        this.initModerationQueue();
        this.startAutoRefresh();
    }

    setupEventListeners() {
        // Status filter
        document.getElementById('statusFilter')?.addEventListener('change', (e) => {
            this.filters.status = e.target.value;
            this.loadPendingReviews();
        });

        // Flag filter
        document.getElementById('flagFilter')?.addEventListener('change', (e) => {
            this.filters.flag = e.target.value;
            this.loadPendingReviews();
        });

        // Bulk action handlers
        document.getElementById('bulkApprove')?.addEventListener('click', () => 
            this.handleBulkAction('approve')
        );
        document.getElementById('bulkReject')?.addEventListener('click', () => 
            this.handleBulkAction('reject')
        );
    }

    async loadPendingReviews() {
        try {
            const params = new URLSearchParams({
                ...this.filters,
                page: this.currentPage,
                limit: this.itemsPerPage
            });

            const response = await fetch(`${this.endpoints.reviews}?${params}`);
            const data = await response.json();

            this.renderReviews(data.reviews);
            this.updatePagination(data.totalPages);
            this.updateModerationStats(data.stats);
        } catch (error) {
            this.showError('Failed to load pending reviews');
        }
    }

    renderReviews(reviews) {
        const container = document.getElementById('reviewsContainer');
        if (!container) return;

        container.innerHTML = reviews.map(review => `
            <div class="review-card ${review.flag}" data-id="${review.id}">
                <div class="review-header">
                    <span class="rating">${'★'.repeat(review.rating)}${'☆'.repeat(5-review.rating)}</span>
                    <span class="date">${new Date(review.created_at).toLocaleDateString()}</span>
                </div>
                <div class="review-content">
                    <p class="review-text">${this.sanitizeHTML(review.content)}</p>
                    <div class="review-meta">
                        <span>Customer: ${this.sanitizeHTML(review.customer_name)}</span>
                        <span>CarWash: ${this.sanitizeHTML(review.carwash_name)}</span>
                    </div>
                </div>
                <div class="review-flags">
                    ${review.flags.map(flag => `
                        <span class="flag-badge ${flag.type}">${flag.type}</span>
                    `).join('')}
                </div>
                <div class="review-actions">
                    <button class="approve-btn" onclick="reviewModeration.moderateReview('${review.id}', 'approve')">
                        Approve
                    </button>
                    <button class="reject-btn" onclick="reviewModeration.moderateReview('${review.id}', 'reject')">
                        Reject
                    </button>
                    <button class="flag-btn" onclick="reviewModeration.showFlagDialog('${review.id}')">
                        Flag
                    </button>
                </div>
            </div>
        `).join('');
    }

    async moderateReview(reviewId, action) {
        try {
            const response = await fetch(this.endpoints.moderate, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    review_id: reviewId,
                    action: action
                })
            });

            const result = await response.json();
            if (result.success) {
                this.showSuccess(`Review ${action}ed successfully`);
                this.loadPendingReviews();
            } else {
                this.showError(result.message);
            }
        } catch (error) {
            this.showError('Failed to moderate review');
        }
    }

    async handleBulkAction(action) {
        const selectedReviews = Array.from(
            document.querySelectorAll('.review-checkbox:checked')
        ).map(cb => cb.value);

        if (!selectedReviews.length) {
            this.showError('No reviews selected');
            return;
        }

        try {
            const response = await fetch(this.endpoints.moderate, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    review_ids: selectedReviews,
                    action: action
                })
            });

            const result = await response.json();
            if (result.success) {
                this.showSuccess(`Bulk ${action} successful`);
                this.loadPendingReviews();
            } else {
                this.showError(result.message);
            }
        } catch (error) {
            this.showError('Failed to perform bulk action');
        }
    }

    sanitizeHTML(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    startAutoRefresh() {
        setInterval(() => this.loadPendingReviews(), 300000); // Refresh every 5 minutes
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

// Initialize review moderation
const reviewModeration = new ReviewModeration();
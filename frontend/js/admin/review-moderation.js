class ReviewModerator {
    constructor() {
        this.currentStatus = 'pending';
        this.init();
    }

    async init() {
        this.setupEventListeners();
        await this.loadReviews();
    }

    setupEventListeners() {
        document.getElementById('statusFilter').addEventListener('change', (e) => {
            this.currentStatus = e.target.value;
            this.loadReviews();
        });
    }

    async loadReviews() {
        try {
            const response = await fetch(`/carwash_project/backend/api/admin/get_reviews.php?status=${this.currentStatus}`);
            const data = await response.json();
            
            if (data.success) {
                this.renderReviews(data.reviews);
            }
        } catch (error) {
            console.error('Error loading reviews:', error);
        }
    }

    renderReviews(reviews) {
        const container = document.getElementById('reviewsList');
        container.innerHTML = reviews.map(review => `
            <div class="bg-white p-6 rounded-lg shadow border-l-4 ${this.getStatusBorderColor(review.status)}">
                <div class="flex justify-between items-start">
                    <div>
                        <div class="font-semibold">${review.customer_name}</div>
                        <div class="text-sm text-gray-500">${review.carwash_name}</div>
                        <div class="mt-2">
                            ${this.renderStars(review.rating)}
                        </div>
                        <p class="mt-2">${review.comment}</p>
                    </div>
                    <div class="space-y-2">
                        ${this.renderActionButtons(review)}
                    </div>
                </div>
            </div>
        `).join('');
    }

    async handleAction(reviewId, action) {
        try {
            const response = await fetch('/carwash_project/backend/api/admin/moderate_review.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ reviewId, action })
            });

            const data = await response.json();
            if (data.success) {
                await this.loadReviews();
            }
        } catch (error) {
            console.error('Error moderating review:', error);
        }
    }
}

// Initialize moderator
const reviewModerator = new ReviewModerator();
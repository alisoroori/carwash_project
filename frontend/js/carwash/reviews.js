class CarwashReviews {
    constructor() {
        this.loadStats();
        this.loadReviews();
        this.currentReviewId = null;
        
        // Setup reply form
        document.getElementById('replyForm').addEventListener('submit', (e) => {
            e.preventDefault();
            this.submitReply();
        });
    }

    async loadStats() {
        try {
            const response = await fetch('../../api/carwash/get_review_stats.php');
            const data = await response.json();
            
            if (data.success) {
                document.getElementById('avgRating').textContent = 
                    data.stats.average_rating.toFixed(1) + ' ★';
                document.getElementById('totalReviews').textContent = 
                    data.stats.total_reviews;
                document.getElementById('recentReviews').textContent = 
                    data.stats.recent_reviews;
                document.getElementById('responseRate').textContent = 
                    data.stats.response_rate + '%';
            }
        } catch (error) {
            console.error('Error loading stats:', error);
        }
    }

    async loadReviews() {
        try {
            const response = await fetch('../../api/carwash/get_reviews.php');
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
            <div class="p-6">
                <div class="flex justify-between items-start mb-2">
                    <div>
                        <div class="font-semibold">${review.user_name}</div>
                        <div class="text-yellow-400">
                            ${'★'.repeat(review.rating)}${'☆'.repeat(5-review.rating)}
                        </div>
                        <div class="text-sm text-gray-500">
                            ${new Date(review.created_at).toLocaleDateString('tr-TR')}
                        </div>
                    </div>
                    ${!review.reply ? `
                        <button onclick="carwashReviews.showReplyModal(${review.id})"
                                class="text-blue-600 hover:text-blue-800">
                            <i class="fas fa-reply"></i> Yanıtla
                        </button>
                    ` : ''}
                </div>
                <p class="text-gray-600 mb-4">${review.comment}</p>
                ${review.reply ? `
                    <div class="ml-8 p-4 bg-gray-50 rounded">
                        <div class="text-sm font-semibold mb-1">Yanıtınız:</div>
                        <p class="text-gray-600">${review.reply}</p>
                        <div class="text-sm text-gray-500 mt-1">
                            ${new Date(review.reply_date).toLocaleDateString('tr-TR')}
                        </div>
                    </div>
                ` : ''}
            </div>
        `).join('');
    }

    showReplyModal(reviewId) {
        this.currentReviewId = reviewId;
        document.getElementById('replyModal').classList.remove('hidden');
    }

    closeReplyModal() {
        this.currentReviewId = null;
        document.getElementById('replyModal').classList.add('hidden');
        document.getElementById('replyForm').reset();
    }

    async submitReply() {
        if (!this.currentReviewId) return;

        const form = document.getElementById('replyForm');
        const reply = form.reply.value.trim();

        if (!reply) {
            alert('Lütfen bir yanıt yazın');
            return;
        }

        try {
            const response = await fetch('../../api/carwash/reply_to_review.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    reviewId: this.currentReviewId,
                    reply: reply
                })
            });

            const data = await response.json();
            
            if (data.success) {
                this.closeReplyModal();
                this.loadReviews();
            } else {
                alert(data.error || 'Yanıt gönderilemedi');
            }
        } catch (error) {
            console.error('Error submitting reply:', error);
            alert('Sistem hatası');
        }
    }
}

// Initialize
const carwashReviews = new CarwashReviews();
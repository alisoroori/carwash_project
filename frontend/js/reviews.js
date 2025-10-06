class ReviewComponent {
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
            const response = await fetch(`../backend/api/get_reviews.php?carwash_id=${this.carwashId}`);
            const data = await response.json();
            
            if (data.success) {
                this.renderReviews(data.reviews);
                this.renderStats(data.stats);
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

// Initialize component
document.addEventListener('DOMContentLoaded', () => {
    const reviewsContainer = document.getElementById('reviewsComponent');
    if (reviewsContainer) {
        new ReviewComponent('reviewsComponent');
    }
});
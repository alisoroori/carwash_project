class UserDashboard {
    constructor() {
        this.userId = document.body.dataset.userId;
        this.init();
    }

    async init() {
        await Promise.all([
            this.loadUserInfo(),
            this.loadRecentAppointments(),
            this.loadReviews()
        ]);
    }

    async loadUserInfo() {
        try {
            const response = await fetch('../../api/user/get_profile.php');
            const data = await response.json();
            
            if (data.success) {
                document.getElementById('userName').textContent = data.user.name;
                document.getElementById('userEmail').textContent = data.user.email;
            }
        } catch (error) {
            console.error('Error loading user info:', error);
        }
    }

    async loadRecentAppointments() {
        try {
            const response = await fetch('../../api/user/get_recent_appointments.php');
            const data = await response.json();
            
            if (data.success) {
                this.renderAppointments(data.appointments);
            }
        } catch (error) {
            console.error('Error loading appointments:', error);
        }
    }

    renderAppointments(appointments) {
        const container = document.getElementById('recentAppointments');
        container.innerHTML = appointments.length ? appointments.map(apt => `
            <div class="border-b last:border-0 py-4">
                <div class="flex justify-between items-start">
                    <div>
                        <div class="font-semibold">${apt.carwash_name}</div>
                        <div class="text-sm text-gray-500">${apt.service_name}</div>
                        <div class="text-sm text-gray-500">
                            ${this.formatDateTime(apt.appointment_date)}
                        </div>
                    </div>
                    <span class="px-2 py-1 rounded-full text-xs ${this.getStatusClass(apt.status)}">
                        ${apt.status}
                    </span>
                </div>
            </div>
        `).join('') : `
            <div class="text-center text-gray-500 py-4">
                Henüz randevunuz bulunmuyor
            </div>
        `;
    }

    async loadReviews() {
        try {
            const response = await fetch('../../api/user/get_reviews.php');
            const data = await response.json();
            
            if (data.success) {
                this.renderReviews(data.reviews);
            }
        } catch (error) {
            console.error('Error loading reviews:', error);
        }
    }

    renderReviews(reviews) {
        const container = document.getElementById('userReviews');
        container.innerHTML = reviews.length ? reviews.map(review => `
            <div class="border-b last:border-0 py-4">
                <div class="flex justify-between items-start mb-2">
                    <div>
                        <div class="font-semibold">${review.carwash_name}</div>
                        <div class="text-yellow-400">
                            ${'★'.repeat(review.rating)}${'☆'.repeat(5-review.rating)}
                        </div>
                    </div>
                    <div class="text-sm text-gray-500">
                        ${this.formatDate(review.created_at)}
                    </div>
                </div>
                <p class="text-gray-600">${review.comment}</p>
                ${review.reply ? `
                    <div class="mt-2 pl-4 border-l-2 border-gray-200">
                        <div class="text-sm text-gray-500">Yanıt:</div>
                        <p class="text-gray-600">${review.reply}</p>
                    </div>
                ` : ''}
            </div>
        `).join('') : `
            <div class="text-center text-gray-500 py-4">
                Henüz değerlendirmeniz bulunmuyor
            </div>
        `;
    }

    formatDateTime(dateString) {
        return new Date(dateString).toLocaleDateString('tr-TR', {
            day: 'numeric',
            month: 'long',
            hour: '2-digit',
            minute: '2-digit'
        });
    }

    formatDate(dateString) {
        return new Date(dateString).toLocaleDateString('tr-TR', {
            day: 'numeric',
            month: 'short',
            year: 'numeric'
        });
    }

    getStatusClass(status) {
        const classes = {
            'pending': 'bg-yellow-100 text-yellow-800',
            'completed': 'bg-green-100 text-green-800',
            'cancelled': 'bg-red-100 text-red-800'
        };
        return classes[status] || 'bg-gray-100 text-gray-800';
    }
}

// Initialize dashboard
const dashboard = new UserDashboard();
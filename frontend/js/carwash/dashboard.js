class CarwashDashboard {
    constructor() {
        this.carwashId = document.body.dataset.carwashId;
        this.init();
    }

    async init() {
        await Promise.all([
            this.loadTodayStats(),
            this.loadUpcomingAppointments(),
            this.initCharts()
        ]);
    }

    async loadTodayStats() {
        try {
            const response = await fetch(`../../api/carwash/get_today_stats.php`);
            const data = await response.json();
            
            if (data.success) {
                this.updateStats(data.stats);
            }
        } catch (error) {
            console.error('Error loading stats:', error);
        }
    }

    updateStats(stats) {
        document.getElementById('todayAppointments').textContent = stats.appointments;
        document.getElementById('todayRevenue').textContent = this.formatCurrency(stats.revenue);
        document.getElementById('averageRating').textContent = `${stats.rating} ★`;
        document.getElementById('monthlyRevenue').textContent = this.formatCurrency(stats.monthly);
    }

    async loadUpcomingAppointments() {
        try {
            const response = await fetch(`../../api/carwash/get_upcoming_appointments.php`);
            const data = await response.json();
            
            if (data.success) {
                this.renderAppointments(data.appointments);
            }
        } catch (error) {
            console.error('Error loading appointments:', error);
        }
    }

    renderAppointments(appointments) {
        const tbody = document.getElementById('appointmentsTable').querySelector('tbody');
        tbody.innerHTML = appointments.map(apt => `
            <tr>
                <td class="py-3">${this.formatTime(apt.time)}</td>
                <td class="py-3">${apt.customer_name}</td>
                <td class="py-3">${apt.service_name}</td>
                <td class="py-3">
                    <span class="px-2 py-1 rounded-full text-xs ${this.getStatusClass(apt.status)}">
                        ${apt.status}
                    </span>
                </td>
                <td class="py-3">
                    <button onclick="dashboard.updateStatus(${apt.id})"
                            class="text-blue-600 hover:text-blue-800">
                        <i class="fas fa-edit"></i>
                    </button>
                </td>
            </tr>
        `).join('');
    }

    async initCharts() {
        await Promise.all([
            this.initWeeklyRevenueChart(),
            this.initPopularServicesChart()
        ]);
    }

    async initWeeklyRevenueChart() {
        const ctx = document.getElementById('weeklyRevenueChart').getContext('2d');
        const response = await fetch(`../../api/carwash/get_weekly_revenue.php`);
        const data = await response.json();

        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: data.labels,
                datasets: [{
                    label: 'Haftalık Gelir',
                    data: data.values,
                    backgroundColor: '#2563eb'
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    }

    async initPopularServicesChart() {
        const ctx = document.getElementById('popularServicesChart').getContext('2d');
        const response = await fetch(`../../api/carwash/get_popular_services.php`);
        const data = await response.json();

        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: data.labels,
                datasets: [{
                    data: data.values,
                    backgroundColor: [
                        '#2563eb',
                        '#7c3aed',
                        '#db2777',
                        '#dc2626',
                        '#d97706'
                    ]
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    }

    formatCurrency(amount) {
        return new Intl.NumberFormat('tr-TR', {
            style: 'currency',
            currency: 'TRY'
        }).format(amount);
    }

    formatTime(timeString) {
        return new Date(timeString).toLocaleTimeString('tr-TR', {
            hour: '2-digit',
            minute: '2-digit'
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
const dashboard = new CarwashDashboard();
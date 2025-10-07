class AdminDashboard {
    constructor() {
        this.loadStats();
        this.initCharts();
        this.loadActivities();
    }

    async loadStats() {
        try {
            const response = await fetch('../../api/admin/get_dashboard_stats.php');
            const data = await response.json();
            
            if (data.success) {
                this.updateStats(data.stats);
            }
        } catch (error) {
            console.error('Error loading stats:', error);
        }
    }

    updateStats(stats) {
        document.getElementById('totalRevenue').textContent = 
            this.formatCurrency(stats.total_revenue);
        document.getElementById('activeBusinesses').textContent = 
            stats.active_businesses;
        document.getElementById('totalBookings').textContent = 
            stats.total_bookings;
        document.getElementById('newUsers').textContent = 
            stats.new_users;
    }

    initCharts() {
        this.initRevenueChart();
        this.initServicesChart();
    }

    async initRevenueChart() {
        const ctx = document.getElementById('revenueChart').getContext('2d');
        const response = await fetch('../../api/admin/get_revenue_data.php');
        const data = await response.json();

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: data.labels,
                datasets: [{
                    label: 'Aylık Gelir',
                    data: data.values,
                    borderColor: '#2563eb',
                    tension: 0.1
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

    async initServicesChart() {
        const ctx = document.getElementById('servicesChart').getContext('2d');
        const response = await fetch('../../api/admin/get_services_data.php');
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

    async loadActivities() {
        try {
            const response = await fetch('../../api/admin/get_recent_activities.php');
            const data = await response.json();
            
            if (data.success) {
                this.renderActivities(data.activities);
            }
        } catch (error) {
            console.error('Error loading activities:', error);
        }
    }

    renderActivities(activities) {
        const tbody = document.getElementById('activityTable').querySelector('tbody');
        tbody.innerHTML = activities.map(activity => `
            <tr>
                <td class="py-2">${this.formatDate(activity.created_at)}</td>
                <td class="py-2">${activity.action}</td>
                <td class="py-2">${activity.user_name}</td>
                <td class="py-2">
                    <span class="px-2 py-1 rounded-full text-xs ${this.getStatusClass(activity.status)}">
                        ${activity.status}
                    </span>
                </td>
            </tr>
        `).join('');
    }

    formatCurrency(amount) {
        return new Intl.NumberFormat('tr-TR', {
            style: 'currency',
            currency: 'TRY'
        }).format(amount);
    }

    formatDate(dateString) {
        return new Date(dateString).toLocaleDateString('tr-TR', {
            day: 'numeric',
            month: 'short',
            hour: '2-digit',
            minute: '2-digit'
        });
    }

    getStatusClass(status) {
        const classes = {
            'success': 'bg-green-100 text-green-800',
            'pending': 'bg-yellow-100 text-yellow-800',
            'failed': 'bg-red-100 text-red-800'
        };
        return classes[status] || 'bg-gray-100 text-gray-800';
    }
}

// Initialize dashboard
const dashboard = new AdminDashboard();
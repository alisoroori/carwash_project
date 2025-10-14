class AdminDashboard {
    constructor() {
        this.endpoints = {
            stats: '/carwash_project/backend/api/admin/stats.php',
            bookings: '/carwash_project/backend/api/admin/bookings.php',
            revenue: '/carwash_project/backend/api/admin/revenue.php'
        };
        this.charts = new Map();
        this.refreshInterval = 300000; // 5 minutes
        this.init();
    }

    async init() {
        this.initializeCharts();
        await this.loadDashboardData();
        this.setupEventListeners();
        this.startAutoRefresh();
    }

    initializeCharts() {
        // Revenue Chart
        const revenueCtx = document.getElementById('revenue-chart');
        if (revenueCtx) {
            new Chart(revenueCtx, {
                type: 'line',
                data: {
                    labels: this.getLastNDays(30),
                    datasets: [{
                        label: 'Daily Revenue',
                        data: [], // Will be populated via API
                        borderColor: '#4CAF50'
                    }]
                }
            });
        }

        // Bookings Chart
        const bookingsCtx = document.getElementById('bookings-chart');
        if (bookingsCtx) {
            new Chart(bookingsCtx, {
                type: 'bar',
                data: {
                    labels: this.getLastNDays(7),
                    datasets: [{
                        label: 'Daily Bookings',
                        data: [], // Will be populated via API
                        backgroundColor: '#2196F3'
                    }]
                }
            });
        }
    }

    async loadDashboardData() {
        try {
            const [stats, bookings, revenue] = await Promise.all([
                fetch(this.endpoints.stats).then(r => r.json()),
                fetch(this.endpoints.bookings).then(r => r.json()),
                fetch(this.endpoints.revenue).then(r => r.json())
            ]);

            this.updateStats(stats);
            this.updateCharts(bookings, revenue);
        } catch (error) {
            console.error('Failed to load dashboard data:', error);
            this.showError('Failed to load dashboard data');
        }
    }

    updateStats(stats) {
        const elements = {
            totalBookings: document.getElementById('totalBookings'),
            totalRevenue: document.getElementById('totalRevenue'),
            activeUsers: document.getElementById('activeUsers'),
            completionRate: document.getElementById('completionRate')
        };

        for (const [key, element] of Object.entries(elements)) {
            if (element && stats[key]) {
                element.textContent = this.formatStatValue(key, stats[key]);
            }
        }
    }

    updateCharts(bookings, revenue) {
        const revenueChart = this.charts.get('revenue');
        if (revenueChart) {
            revenueChart.data.labels = revenue.dates;
            revenueChart.data.datasets[0].data = revenue.amounts;
            revenueChart.update();
        }

        const bookingsChart = this.charts.get('bookings');
        if (bookingsChart) {
            bookingsChart.data.labels = bookings.dates;
            bookingsChart.data.datasets[0].data = bookings.counts;
            bookingsChart.update();
        }
    }

    setupEventListeners() {
        const dateRangeSelect = document.getElementById('dateRange');
        if (dateRangeSelect) {
            dateRangeSelect.addEventListener('change', () => this.loadDashboardData());
        }

        document.querySelectorAll('.refresh-btn').forEach(btn => {
            btn.addEventListener('click', () => this.loadDashboardData());
        });
    }

    startAutoRefresh() {
        setInterval(() => this.loadDashboardData(), this.refreshInterval);
    }

    formatStatValue(key, value) {
        switch (key) {
            case 'totalRevenue':
                return new Intl.NumberFormat('en-US', {
                    style: 'currency',
                    currency: 'USD'
                }).format(value);
            case 'completionRate':
                return `${value}%`;
            default:
                return value.toLocaleString();
        }
    }

    showError(message) {
        const alert = document.createElement('div');
        alert.className = 'alert alert-error';
        alert.textContent = message;
        document.body.appendChild(alert);
        setTimeout(() => alert.remove(), 5000);
    }

    showSuccess(message) {
        const alert = document.createElement('div');
        alert.className = 'alert alert-success';
        alert.textContent = message;
        document.body.appendChild(alert);
        setTimeout(() => alert.remove(), 3000);
    }

    getLastNDays(n) {
        const dates = [];
        for (let i = n - 1; i >= 0; i--) {
            const date = new Date();
            date.setDate(date.getDate() - i);
            dates.push(date.toLocaleDateString());
        }
        return dates;
    }
}

// Initialize dashboard
document.addEventListener('DOMContentLoaded', () => new AdminDashboard());
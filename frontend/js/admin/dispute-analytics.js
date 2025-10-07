class DisputeAnalytics {
    constructor() {
        this.endpoints = {
            stats: '/carwash_project/backend/api/admin/disputes/stats.php',
            trends: '/carwash_project/backend/api/admin/disputes/trends.php',
            categories: '/carwash_project/backend/api/admin/disputes/categories.php'
        };
        this.charts = new Map();
        this.filters = {
            dateRange: '30days',
            status: 'all',
            category: 'all'
        };
        this.init();
    }

    async init() {
        this.initializeCharts();
        this.setupEventListeners();
        await this.loadAnalytics();
        this.startPeriodicRefresh();
    }

    initializeCharts() {
        // Dispute Trends Chart
        const trendsCtx = document.getElementById('disputeTrendsChart');
        if (trendsCtx) {
            this.charts.set('trends', new Chart(trendsCtx, {
                type: 'line',
                data: {
                    labels: [],
                    datasets: [{
                        label: 'New Disputes',
                        borderColor: '#EF4444',
                        data: []
                    }, {
                        label: 'Resolved Disputes',
                        borderColor: '#10B981',
                        data: []
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: { stepSize: 1 }
                        }
                    }
                }
            }));
        }

        // Category Distribution Chart
        const categoryCtx = document.getElementById('disputeCategoryChart');
        if (categoryCtx) {
            this.charts.set('categories', new Chart(categoryCtx, {
                type: 'doughnut',
                data: {
                    labels: [],
                    datasets: [{
                        data: [],
                        backgroundColor: [
                            '#3B82F6',
                            '#EF4444',
                            '#F59E0B',
                            '#10B981',
                            '#6366F1'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            position: 'right'
                        }
                    }
                }
            }));
        }
    }

    setupEventListeners() {
        // Date range filter
        const dateFilter = document.getElementById('dateRangeFilter');
        if (dateFilter) {
            dateFilter.addEventListener('change', (e) => {
                this.filters.dateRange = e.target.value;
                this.loadAnalytics();
            });
        }

        // Status filter
        const statusFilter = document.getElementById('statusFilter');
        if (statusFilter) {
            statusFilter.addEventListener('change', (e) => {
                this.filters.status = e.target.value;
                this.loadAnalytics();
            });
        }

        // Export button
        const exportBtn = document.getElementById('exportData');
        if (exportBtn) {
            exportBtn.addEventListener('click', () => this.exportAnalytics());
        }
    }

    async loadAnalytics() {
        try {
            const [stats, trends, categories] = await Promise.all([
                this.fetchData(this.endpoints.stats),
                this.fetchData(this.endpoints.trends),
                this.fetchData(this.endpoints.categories)
            ]);

            this.updateStatistics(stats);
            this.updateTrendsChart(trends);
            this.updateCategoryChart(categories);
        } catch (error) {
            this.showError('Failed to load dispute analytics');
            console.error('Analytics load error:', error);
        }
    }

    async fetchData(endpoint) {
        const queryParams = new URLSearchParams(this.filters);
        const response = await fetch(`${endpoint}?${queryParams}`);
        if (!response.ok) throw new Error('API request failed');
        return await response.json();
    }

    updateStatistics(stats) {
        const elements = {
            totalDisputes: document.getElementById('totalDisputes'),
            averageResolutionTime: document.getElementById('avgResolutionTime'),
            resolutionRate: document.getElementById('resolutionRate'),
            escalationRate: document.getElementById('escalationRate')
        };

        for (const [key, element] of Object.entries(elements)) {
            if (element && stats[key]) {
                element.textContent = this.formatStatValue(key, stats[key]);
            }
        }
    }

    updateTrendsChart(trends) {
        const trendsChart = this.charts.get('trends');
        if (trendsChart) {
            trendsChart.data.labels = trends.dates;
            trendsChart.data.datasets[0].data = trends.new;
            trendsChart.data.datasets[1].data = trends.resolved;
            trendsChart.update();
        }
    }

    updateCategoryChart(categories) {
        const categoryChart = this.charts.get('categories');
        if (categoryChart) {
            categoryChart.data.labels = categories.labels;
            categoryChart.data.datasets[0].data = categories.values;
            categoryChart.update();
        }
    }

    formatStatValue(key, value) {
        switch (key) {
            case 'resolutionRate':
            case 'escalationRate':
                return `${value.toFixed(1)}%`;
            case 'averageResolutionTime':
                return `${value.toFixed(1)} hours`;
            default:
                return value.toLocaleString();
        }
    }

    startPeriodicRefresh() {
        setInterval(() => this.loadAnalytics(), 300000); // Refresh every 5 minutes
    }

    async exportAnalytics() {
        try {
            const data = await this.fetchData(`${this.endpoints.stats}?export=true`);
            const csv = this.convertToCSV(data);
            this.downloadCSV(csv, `dispute-analytics-${new Date().toISOString()}.csv`);
        } catch (error) {
            this.showError('Failed to export analytics');
        }
    }

    showError(message) {
        const alert = document.createElement('div');
        alert.className = 'error-alert';
        alert.textContent = message;
        document.body.appendChild(alert);
        setTimeout(() => alert.remove(), 5000);
    }
}

// Initialize dispute analytics
document.addEventListener('DOMContentLoaded', () => new DisputeAnalytics());
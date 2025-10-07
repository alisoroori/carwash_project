class PaymentAnalytics {
    constructor() {
        this.endpoints = {
            stats: '/carwash_project/backend/api/admin/payments/stats.php',
            trends: '/carwash_project/backend/api/admin/payments/trends.php',
            methods: '/carwash_project/backend/api/admin/payments/methods.php'
        };
        this.charts = new Map();
        this.dateRange = '30days';
        this.init();
    }

    async init() {
        this.initializeCharts();
        this.setupEventListeners();
        await this.loadAnalytics();
        this.startPeriodicRefresh();
    }

    initializeCharts() {
        // Transaction Volume Chart
        const volumeCtx = document.getElementById('transactionVolumeChart');
        if (volumeCtx) {
            this.charts.set('volume', new Chart(volumeCtx, {
                type: 'line',
                data: {
                    labels: [],
                    datasets: [{
                        label: 'Transaction Volume',
                        borderColor: '#3B82F6',
                        data: []
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: value => `₺${value.toLocaleString()}`
                            }
                        }
                    }
                }
            }));
        }

        // Payment Methods Distribution
        const methodsCtx = document.getElementById('paymentMethodsChart');
        if (methodsCtx) {
            this.charts.set('methods', new Chart(methodsCtx, {
                type: 'doughnut',
                data: {
                    labels: [],
                    datasets: [{
                        data: [],
                        backgroundColor: [
                            '#10B981', // Credit Card
                            '#3B82F6', // Bank Transfer
                            '#F59E0B', // Digital Wallet
                            '#EF4444'  // Other
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
        // Date range selector
        const dateSelect = document.getElementById('dateRangeSelect');
        dateSelect?.addEventListener('change', (e) => {
            this.dateRange = e.target.value;
            this.loadAnalytics();
        });

        // Export data button
        const exportBtn = document.getElementById('exportData');
        exportBtn?.addEventListener('click', () => this.exportAnalytics());

        // Refresh button
        const refreshBtn = document.getElementById('refreshAnalytics');
        refreshBtn?.addEventListener('click', () => this.loadAnalytics());
    }

    async loadAnalytics() {
        try {
            const [stats, trends, methods] = await Promise.all([
                this.fetchData(`${this.endpoints.stats}?range=${this.dateRange}`),
                this.fetchData(`${this.endpoints.trends}?range=${this.dateRange}`),
                this.fetchData(`${this.endpoints.methods}?range=${this.dateRange}`)
            ]);

            this.updateStats(stats);
            this.updateCharts(trends, methods);
        } catch (error) {
            this.showError('Failed to load payment analytics');
            console.error('Analytics loading error:', error);
        }
    }

    async fetchData(endpoint) {
        const response = await fetch(endpoint);
        if (!response.ok) throw new Error('API request failed');
        return await response.json();
    }

    updateStats(stats) {
        const elements = {
            totalRevenue: document.getElementById('totalRevenue'),
            averageTransaction: document.getElementById('averageTransaction'),
            successRate: document.getElementById('successRate'),
            refundRate: document.getElementById('refundRate')
        };

        for (const [key, element] of Object.entries(elements)) {
            if (element && stats[key]) {
                element.textContent = this.formatStatValue(key, stats[key]);
            }
        }
    }

    updateCharts(trends, methods) {
        // Update volume chart
        const volumeChart = this.charts.get('volume');
        if (volumeChart) {
            volumeChart.data.labels = trends.dates;
            volumeChart.data.datasets[0].data = trends.volumes;
            volumeChart.update();
        }

        // Update methods chart
        const methodsChart = this.charts.get('methods');
        if (methodsChart) {
            methodsChart.data.labels = methods.labels;
            methodsChart.data.datasets[0].data = methods.values;
            methodsChart.update();
        }
    }

    formatStatValue(key, value) {
        switch (key) {
            case 'totalRevenue':
            case 'averageTransaction':
                return `₺${value.toLocaleString(undefined, {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                })}`;
            case 'successRate':
            case 'refundRate':
                return `${value.toFixed(1)}%`;
            default:
                return value.toLocaleString();
        }
    }

    async exportAnalytics() {
        try {
            const data = await this.fetchData(`${this.endpoints.stats}?export=true&range=${this.dateRange}`);
            const csv = this.convertToCSV(data);
            this.downloadCSV(csv, `payment-analytics-${new Date().toISOString()}.csv`);
        } catch (error) {
            this.showError('Failed to export analytics data');
        }
    }

    convertToCSV(data) {
        const headers = Object.keys(data[0]);
        const rows = data.map(row => headers.map(header => row[header]));
        return [headers, ...rows].map(row => row.join(',')).join('\n');
    }

    downloadCSV(csv, filename) {
        const blob = new Blob([csv], { type: 'text/csv' });
        const url = window.URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = filename;
        link.click();
        window.URL.revokeObjectURL(url);
    }

    startPeriodicRefresh() {
        setInterval(() => this.loadAnalytics(), 300000); // Refresh every 5 minutes
    }

    showError(message) {
        const alert = document.createElement('div');
        alert.className = 'error-alert';
        alert.textContent = message;
        document.body.appendChild(alert);
        setTimeout(() => alert.remove(), 5000);
    }
}

// Initialize payment analytics
document.addEventListener('DOMContentLoaded', () => new PaymentAnalytics());
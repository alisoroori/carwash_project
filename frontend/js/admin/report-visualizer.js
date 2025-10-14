class ReportVisualizer {
    constructor() {
        this.endpoints = {
            reports: '/carwash_project/backend/api/admin/reports/data.php',
            metrics: '/carwash_project/backend/api/admin/reports/metrics.php',
            insights: '/carwash_project/backend/api/admin/reports/insights.php'
        };
        this.charts = new Map();
        this.activeReport = null;
        this.reportConfig = {
            timeframe: 'monthly',
            metrics: ['revenue', 'bookings', 'ratings'],
            view: 'chart'
        };
        this.init();
    }

    async init() {
        this.setupChartContainers();
        this.bindEventListeners();
        await this.loadInitialData();
    }

    setupChartContainers() {
        // Revenue Performance Chart
        const revenueCtx = document.getElementById('revenuePerformanceChart');
        if (revenueCtx) {
            this.charts.set('revenue', new Chart(revenueCtx, {
                type: 'line',
                data: {
                    labels: [],
                    datasets: [{
                        label: 'Actual Revenue',
                        borderColor: '#10B981',
                        data: []
                    }, {
                        label: 'Projected Revenue',
                        borderColor: '#6366F1',
                        borderDash: [5, 5],
                        data: []
                    }]
                },
                options: {
                    responsive: true,
                    interaction: {
                        intersect: false,
                        mode: 'index'
                    },
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

        // Service Performance Chart
        const serviceCtx = document.getElementById('servicePerformanceChart');
        if (serviceCtx) {
            this.charts.set('services', new Chart(serviceCtx, {
                type: 'bar',
                data: {
                    labels: [],
                    datasets: [{
                        label: 'Service Bookings',
                        backgroundColor: '#3B82F6',
                        data: []
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1
                            }
                        }
                    }
                }
            }));
        }
    }

    bindEventListeners() {
        // Report type selector
        document.getElementById('reportType')?.addEventListener('change', (e) => {
            this.loadReport(e.target.value);
        });

        // Timeframe selector
        document.getElementById('timeframeSelect')?.addEventListener('change', (e) => {
            this.reportConfig.timeframe = e.target.value;
            this.refreshCurrentReport();
        });

        // View toggle
        document.querySelectorAll('.view-toggle')?.forEach(button => {
            button.addEventListener('click', (e) => {
                this.reportConfig.view = e.target.dataset.view;
                this.toggleView(e.target.dataset.view);
            });
        });

        // Export button
        document.getElementById('exportReport')?.addEventListener('click', () => {
            this.exportCurrentReport();
        });
    }

    async loadInitialData() {
        try {
            const defaultReport = document.getElementById('reportType')?.value || 'monthly';
            await this.loadReport(defaultReport);
        } catch (error) {
            this.showError('Failed to load initial report data');
            console.error('Report loading error:', error);
        }
    }

    async loadReport(reportType) {
        try {
            const params = new URLSearchParams({
                type: reportType,
                timeframe: this.reportConfig.timeframe
            });

            const [reportData, metrics] = await Promise.all([
                fetch(`${this.endpoints.reports}?${params}`).then(r => r.json()),
                fetch(`${this.endpoints.metrics}?${params}`).then(r => r.json())
            ]);

            this.activeReport = reportType;
            this.updateCharts(reportData);
            this.updateMetrics(metrics);
            this.generateInsights(reportData);
        } catch (error) {
            this.showError('Failed to load report');
            console.error('Report loading error:', error);
        }
    }

    updateCharts(data) {
        // Update revenue chart
        const revenueChart = this.charts.get('revenue');
        if (revenueChart && data.revenue) {
            revenueChart.data.labels = data.revenue.labels;
            revenueChart.data.datasets[0].data = data.revenue.actual;
            revenueChart.data.datasets[1].data = data.revenue.projected;
            revenueChart.update();
        }

        // Update services chart
        const servicesChart = this.charts.get('services');
        if (servicesChart && data.services) {
            servicesChart.data.labels = data.services.labels;
            servicesChart.data.datasets[0].data = data.services.counts;
            servicesChart.update();
        }
    }

    async exportCurrentReport() {
        try {
            const params = new URLSearchParams({
                type: this.activeReport,
                timeframe: this.reportConfig.timeframe,
                format: 'csv'
            });

            const response = await fetch(`${this.endpoints.reports}?${params}`);
            const blob = await response.blob();
            
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `report-${this.activeReport}-${new Date().toISOString()}.csv`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            window.URL.revokeObjectURL(url);
        } catch (error) {
            this.showError('Failed to export report');
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

// Initialize report visualizer
document.addEventListener('DOMContentLoaded', () => new ReportVisualizer());
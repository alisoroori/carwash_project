class ReviewReports {
    constructor() {
        this.endpoints = {
            reports: '/carwash_project/backend/api/admin/reviews/reports.php',
            trends: '/carwash_project/backend/api/admin/reviews/report-trends.php',
            categories: '/carwash_project/backend/api/admin/reviews/report-categories.php'
        };
        this.charts = new Map();
        this.filters = {
            timeRange: '30days',
            category: 'all',
            status: 'all'
        };
        this.init();
    }

    async init() {
        this.initializeCharts();
        this.setupEventListeners();
        await this.loadReportData();
        this.startAutoRefresh();
    }

    initializeCharts() {
        // Reports by Category Chart
        const categoryCtx = document.getElementById('reportCategoriesChart');
        if (categoryCtx) {
            this.charts.set('categories', new Chart(categoryCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Spam', 'Inappropriate', 'Fake', 'Other'],
                    datasets: [{
                        data: [],
                        backgroundColor: [
                            '#EF4444', // Red
                            '#F59E0B', // Orange
                            '#3B82F6', // Blue
                            '#6B7280'  // Gray
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

        // Reports Timeline Chart
        const timelineCtx = document.getElementById('reportTimelineChart');
        if (timelineCtx) {
            this.charts.set('timeline', new Chart(timelineCtx, {
                type: 'line',
                data: {
                    labels: [],
                    datasets: [{
                        label: 'Reports Received',
                        borderColor: '#EF4444',
                        data: []
                    }, {
                        label: 'Reports Resolved',
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
    }

    setupEventListeners() {
        // Time range filter
        document.getElementById('timeRangeFilter')?.addEventListener('change', (e) => {
            this.filters.timeRange = e.target.value;
            this.loadReportData();
        });

        // Category filter
        document.getElementById('categoryFilter')?.addEventListener('change', (e) => {
            this.filters.category = e.target.value;
            this.loadReportData();
        });

        // Status filter
        document.getElementById('statusFilter')?.addEventListener('change', (e) => {
            this.filters.status = e.target.value;
            this.loadReportData();
        });

        // Export reports
        document.getElementById('exportReports')?.addEventListener('click', () => 
            this.exportReportData()
        );

        // Bulk action handlers
        document.getElementById('bulkResolve')?.addEventListener('click', () => 
            this.handleBulkAction('resolve')
        );
    }

    async loadReportData() {
        try {
            const queryParams = new URLSearchParams(this.filters);
            const [reports, trends, categories] = await Promise.all([
                this.fetchData(`${this.endpoints.reports}?${queryParams}`),
                this.fetchData(`${this.endpoints.trends}?${queryParams}`),
                this.fetchData(`${this.endpoints.categories}?${queryParams}`)
            ]);

            this.updateReportsList(reports);
            this.updateCharts(trends, categories);
            this.updateStats(reports.stats);
        } catch (error) {
            this.showError('Failed to load report data');
            console.error('Report loading error:', error);
        }
    }

    updateReportsList(reports) {
        const container = document.getElementById('reportsContainer');
        if (!container) return;

        container.innerHTML = reports.items.map(report => `
            <div class="report-card ${report.status}" data-id="${report.id}">
                <div class="report-header">
                    <span class="report-category ${report.category}">${report.category}</span>
                    <span class="report-date">${new Date(report.created_at).toLocaleString()}</span>
                </div>
                <div class="report-content">
                    <p class="report-reason">${this.sanitizeHTML(report.reason)}</p>
                    <div class="reported-review">
                        <strong>Reported Review:</strong>
                        <p>${this.sanitizeHTML(report.review_content)}</p>
                    </div>
                </div>
                <div class="report-actions">
                    <button class="resolve-btn" onclick="reviewReports.resolveReport('${report.id}')">
                        Resolve
                    </button>
                    <button class="delete-review-btn" onclick="reviewReports.deleteReview('${report.review_id}')">
                        Delete Review
                    </button>
                </div>
            </div>
        `).join('');
    }

    async resolveReport(reportId) {
        try {
            const response = await fetch(`${this.endpoints.reports}/resolve`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ report_id: reportId })
            });

            const result = await response.json();
            if (result.success) {
                this.showSuccess('Report resolved successfully');
                this.loadReportData();
            } else {
                this.showError(result.message);
            }
        } catch (error) {
            this.showError('Failed to resolve report');
        }
    }

    sanitizeHTML(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    startAutoRefresh() {
        setInterval(() => this.loadReportData(), 300000); // Refresh every 5 minutes
    }

    showSuccess(message) {
        const alert = document.createElement('div');
        alert.className = 'success-alert';
        alert.textContent = message;
        document.body.appendChild(alert);
        setTimeout(() => alert.remove(), 3000);
    }

    showError(message) {
        const alert = document.createElement('div');
        alert.className = 'error-alert';
        alert.textContent = message;
        document.body.appendChild(alert);
        setTimeout(() => alert.remove(), 5000);
    }
}

// Initialize review reports
const reviewReports = new ReviewReports();
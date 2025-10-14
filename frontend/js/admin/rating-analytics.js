class RatingAnalytics {
    constructor() {
        this.endpoints = {
            ratings: '/carwash_project/backend/api/admin/ratings/list.php',
            trends: '/carwash_project/backend/api/admin/ratings/trends.php',
            distribution: '/carwash_project/backend/api/admin/ratings/distribution.php'
        };
        this.charts = new Map();
        this.filters = {
            timeRange: '30days',
            carwashId: 'all',
            serviceType: 'all'
        };
        this.init();
    }

    async init() {
        this.initializeCharts();
        this.setupEventListeners();
        await this.loadRatingData();
        this.startAutoRefresh();
    }

    initializeCharts() {
        // Rating Trends Chart
        const trendsCtx = document.getElementById('ratingTrendsChart');
        if (trendsCtx) {
            this.charts.set('trends', new Chart(trendsCtx, {
                type: 'line',
                data: {
                    labels: [],
                    datasets: [{
                        label: 'Average Rating',
                        borderColor: '#6366F1',
                        data: []
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true,
                            max: 5,
                            ticks: {
                                stepSize: 0.5
                            }
                        }
                    }
                }
            }));
        }

        // Rating Distribution Chart
        const distributionCtx = document.getElementById('ratingDistributionChart');
        if (distributionCtx) {
            this.charts.set('distribution', new Chart(distributionCtx, {
                type: 'bar',
                data: {
                    labels: ['1★', '2★', '3★', '4★', '5★'],
                    datasets: [{
                        label: 'Rating Distribution',
                        backgroundColor: [
                            '#EF4444', // 1 star
                            '#F59E0B', // 2 stars
                            '#FCD34D', // 3 stars
                            '#10B981', // 4 stars
                            '#3B82F6'  // 5 stars
                        ],
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

    setupEventListeners() {
        // Time range filter
        document.getElementById('timeRangeFilter')?.addEventListener('change', (e) => {
            this.filters.timeRange = e.target.value;
            this.loadRatingData();
        });

        // CarWash filter
        document.getElementById('carwashFilter')?.addEventListener('change', (e) => {
            this.filters.carwashId = e.target.value;
            this.loadRatingData();
        });

        // Service type filter
        document.getElementById('serviceTypeFilter')?.addEventListener('change', (e) => {
            this.filters.serviceType = e.target.value;
            this.loadRatingData();
        });

        // Export button
        document.getElementById('exportRatings')?.addEventListener('click', () => 
            this.exportRatingAnalytics()
        );
    }

    async loadRatingData() {
        try {
            const queryParams = new URLSearchParams(this.filters);
            const [ratings, trends, distribution] = await Promise.all([
                this.fetchData(`${this.endpoints.ratings}?${queryParams}`),
                this.fetchData(`${this.endpoints.trends}?${queryParams}`),
                this.fetchData(`${this.endpoints.distribution}?${queryParams}`)
            ]);

            this.updateRatingStats(ratings);
            this.updateCharts(trends, distribution);
            this.updateInsights(ratings.insights);
        } catch (error) {
            this.showError('Failed to load rating analytics');
            console.error('Rating analytics error:', error);
        }
    }

    updateRatingStats(data) {
        const elements = {
            averageRating: document.getElementById('averageRating'),
            totalRatings: document.getElementById('totalRatings'),
            responseRate: document.getElementById('responseRate'),
            satisfactionScore: document.getElementById('satisfactionScore')
        };

        for (const [key, element] of Object.entries(elements)) {
            if (element && data[key]) {
                element.textContent = this.formatStatValue(key, data[key]);
            }
        }
    }

    formatStatValue(key, value) {
        switch (key) {
            case 'averageRating':
                return value.toFixed(1);
            case 'responseRate':
            case 'satisfactionScore':
                return `${value.toFixed(1)}%`;
            default:
                return value.toLocaleString();
        }
    }

    async exportRatingAnalytics() {
        try {
            const data = await this.fetchData(`${this.endpoints.ratings}?export=true`);
            const csv = this.convertToCSV(data);
            this.downloadCSV(csv, `rating-analytics-${new Date().toISOString()}.csv`);
        } catch (error) {
            this.showError('Failed to export rating data');
        }
    }

    startAutoRefresh() {
        setInterval(() => this.loadRatingData(), 300000); // Refresh every 5 minutes
    }

    showError(message) {
        const alert = document.createElement('div');
        alert.className = 'error-alert';
        alert.textContent = message;
        document.body.appendChild(alert);
        setTimeout(() => alert.remove(), 5000);
    }
}

// Initialize rating analytics
document.addEventListener('DOMContentLoaded', () => new RatingAnalytics());
class PricingAnalytics {
    constructor() {
        this.endpoints = {
            pricing: '/carwash_project/backend/api/admin/pricing/analysis.php',
            comparison: '/carwash_project/backend/api/admin/pricing/comparison.php',
            revenue: '/carwash_project/backend/api/admin/pricing/revenue-impact.php'
        };
        this.charts = new Map();
        this.filters = {
            timeRange: '30days',
            serviceType: 'all',
            location: 'all'
        };
        this.init();
    }

    async init() {
        this.initializeCharts();
        this.setupEventListeners();
        await this.loadPricingData();
        this.startAutoRefresh();
    }

    initializeCharts() {
        // Price Impact Chart
        const impactCtx = document.getElementById('priceImpactChart');
        if (impactCtx) {
            this.charts.set('impact', new Chart(impactCtx, {
                type: 'line',
                data: {
                    labels: [],
                    datasets: [{
                        label: 'Revenue',
                        borderColor: '#10B981',
                        data: []
                    }, {
                        label: 'Bookings',
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

        // Service Price Comparison
        const comparisonCtx = document.getElementById('priceComparisonChart');
        if (comparisonCtx) {
            this.charts.set('comparison', new Chart(comparisonCtx, {
                type: 'bar',
                data: {
                    labels: [],
                    datasets: [{
                        label: 'Average Price',
                        backgroundColor: '#6366F1',
                        data: []
                    }]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: value => `₺${value}`
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
            this.loadPricingData();
        });

        // Service type filter
        document.getElementById('serviceTypeFilter')?.addEventListener('change', (e) => {
            this.filters.serviceType = e.target.value;
            this.loadPricingData();
        });

        // Location filter
        document.getElementById('locationFilter')?.addEventListener('change', (e) => {
            this.filters.location = e.target.value;
            this.loadPricingData();
        });

        // Export button
        document.getElementById('exportPricingData')?.addEventListener('click', () => 
            this.exportPricingAnalytics()
        );
    }

    async loadPricingData() {
        try {
            const queryParams = new URLSearchParams(this.filters);
            const [pricing, comparison, revenue] = await Promise.all([
                this.fetchData(`${this.endpoints.pricing}?${queryParams}`),
                this.fetchData(`${this.endpoints.comparison}?${queryParams}`),
                this.fetchData(`${this.endpoints.revenue}?${queryParams}`)
            ]);

            this.updatePricingStats(pricing);
            this.updateCharts(comparison, revenue);
            this.updateRecommendations(pricing.recommendations);
        } catch (error) {
            this.showError('Failed to load pricing analytics');
            console.error('Pricing analytics error:', error);
        }
    }

    updatePricingStats(data) {
        const elements = {
            averagePrice: document.getElementById('averagePrice'),
            priceRange: document.getElementById('priceRange'),
            optimalPrice: document.getElementById('optimalPrice'),
            priceElasticity: document.getElementById('priceElasticity')
        };

        for (const [key, element] of Object.entries(elements)) {
            if (element && data[key]) {
                element.textContent = this.formatStatValue(key, data[key]);
            }
        }
    }

    updateRecommendations(recommendations) {
        const container = document.getElementById('priceRecommendations');
        if (!container || !recommendations) return;

        container.innerHTML = recommendations.map(rec => `
            <div class="recommendation-card ${rec.priority}">
                <h4>${rec.title}</h4>
                <p>${rec.description}</p>
                <div class="recommendation-stats">
                    <span>Potential Impact: ${rec.impact}%</span>
                    <span>Confidence: ${rec.confidence}%</span>
                </div>
            </div>
        `).join('');
    }

    formatStatValue(key, value) {
        switch (key) {
            case 'averagePrice':
            case 'optimalPrice':
                return `₺${value.toFixed(2)}`;
            case 'priceElasticity':
                return value.toFixed(2);
            case 'priceRange':
                return `₺${value.min.toFixed(2)} - ₺${value.max.toFixed(2)}`;
            default:
                return value;
        }
    }

    async exportPricingAnalytics() {
        try {
            const data = await this.fetchData(`${this.endpoints.pricing}?export=true`);
            const csv = this.convertToCSV(data);
            this.downloadCSV(csv, `pricing-analytics-${new Date().toISOString()}.csv`);
        } catch (error) {
            this.showError('Failed to export pricing data');
        }
    }

    startAutoRefresh() {
        setInterval(() => this.loadPricingData(), 300000); // Refresh every 5 minutes
    }

    showError(message) {
        const alert = document.createElement('div');
        alert.className = 'error-alert';
        alert.textContent = message;
        document.body.appendChild(alert);
        setTimeout(() => alert.remove(), 5000);
    }
}

// Initialize pricing analytics
document.addEventListener('DOMContentLoaded', () => new PricingAnalytics());
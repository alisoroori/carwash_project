class ReviewMetrics {
    constructor() {
        this.endpoints = {
            metrics: '/carwash_project/backend/api/admin/reviews/metrics.php',
            sentiment: '/carwash_project/backend/api/admin/reviews/sentiment.php',
            keywords: '/carwash_project/backend/api/admin/reviews/keywords.php'
        };
        this.charts = new Map();
        this.filters = {
            timeRange: '30days',
            carwashId: 'all',
            rating: 'all'
        };
        this.init();
    }

    async init() {
        this.initializeCharts();
        this.setupEventListeners();
        await this.loadMetricsData();
        this.startAutoRefresh();
    }

    initializeCharts() {
        // Sentiment Analysis Chart
        const sentimentCtx = document.getElementById('sentimentChart');
        if (sentimentCtx) {
            this.charts.set('sentiment', new Chart(sentimentCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Positive', 'Neutral', 'Negative'],
                    datasets: [{
                        data: [],
                        backgroundColor: ['#10B981', '#F59E0B', '#EF4444']
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

        // Keywords Cloud Container
        const keywordsContainer = document.getElementById('keywordsCloud');
        if (keywordsContainer) {
            this.initializeWordCloud(keywordsContainer);
        }
    }

    initializeWordCloud(container) {
        // Using D3.js for word cloud visualization
        this.wordCloud = d3.select(container)
            .append('svg')
            .attr('width', container.offsetWidth)
            .attr('height', 400);
    }

    setupEventListeners() {
        // Time range filter
        document.getElementById('timeRangeFilter')?.addEventListener('change', (e) => {
            this.filters.timeRange = e.target.value;
            this.loadMetricsData();
        });

        // CarWash filter
        document.getElementById('carwashFilter')?.addEventListener('change', (e) => {
            this.filters.carwashId = e.target.value;
            this.loadMetricsData();
        });

        // Rating filter
        document.getElementById('ratingFilter')?.addEventListener('change', (e) => {
            this.filters.rating = e.target.value;
            this.loadMetricsData();
        });

        // Export metrics
        document.getElementById('exportMetrics')?.addEventListener('click', () => 
            this.exportMetricsData()
        );
    }

    async loadMetricsData() {
        try {
            const queryParams = new URLSearchParams(this.filters);
            const [metrics, sentiment, keywords] = await Promise.all([
                this.fetchData(`${this.endpoints.metrics}?${queryParams}`),
                this.fetchData(`${this.endpoints.sentiment}?${queryParams}`),
                this.fetchData(`${this.endpoints.keywords}?${queryParams}`)
            ]);

            this.updateMetricsDisplay(metrics);
            this.updateSentimentChart(sentiment);
            this.updateKeywordsCloud(keywords);
            this.updateInsights(metrics.insights);
        } catch (error) {
            this.showError('Failed to load review metrics');
            console.error('Review metrics error:', error);
        }
    }

    updateMetricsDisplay(metrics) {
        const elements = {
            totalReviews: document.getElementById('totalReviews'),
            averageRating: document.getElementById('averageRating'),
            responseRate: document.getElementById('responseRate'),
            sentimentScore: document.getElementById('sentimentScore')
        };

        for (const [key, element] of Object.entries(elements)) {
            if (element && metrics[key]) {
                element.textContent = this.formatMetricValue(key, metrics[key]);
            }
        }
    }

    updateKeywordsCloud(keywords) {
        if (!this.wordCloud) return;

        const layout = d3.layout.cloud()
            .size([400, 400])
            .words(keywords.map(d => ({
                text: d.word,
                size: 10 + (d.count * 3)
            })))
            .padding(5)
            .rotate(() => 0)
            .font('Arial')
            .fontSize(d => d.size)
            .on('end', words => this.drawWordCloud(words));

        layout.start();
    }

    drawWordCloud(words) {
        this.wordCloud.selectAll('*').remove();
        
        const cloud = this.wordCloud.append('g')
            .attr('transform', 'translate(200,200)')
            .selectAll('text')
            .data(words)
            .enter()
            .append('text')
            .style('font-size', d => `${d.size}px`)
            .style('font-family', 'Arial')
            .attr('text-anchor', 'middle')
            .attr('transform', d => `translate(${d.x},${d.y})rotate(${d.rotate})`)
            .text(d => d.text);
    }

    formatMetricValue(key, value) {
        switch (key) {
            case 'averageRating':
                return value.toFixed(1);
            case 'responseRate':
            case 'sentimentScore':
                return `${value.toFixed(1)}%`;
            default:
                return value.toLocaleString();
        }
    }

    async exportMetricsData() {
        try {
            const data = await this.fetchData(`${this.endpoints.metrics}?export=true`);
            const csv = this.convertToCSV(data);
            this.downloadCSV(csv, `review-metrics-${new Date().toISOString()}.csv`);
        } catch (error) {
            this.showError('Failed to export metrics data');
        }
    }

    startAutoRefresh() {
        setInterval(() => this.loadMetricsData(), 300000); // Refresh every 5 minutes
    }

    showError(message) {
        const alert = document.createElement('div');
        alert.className = 'error-alert';
        alert.textContent = message;
        document.body.appendChild(alert);
        setTimeout(() => alert.remove(), 5000);
    }
}

// Initialize review metrics
document.addEventListener('DOMContentLoaded', () => new ReviewMetrics());
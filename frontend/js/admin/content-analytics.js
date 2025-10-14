class ContentAnalyticsVisualizer {
    constructor() {
        this.charts = {};
        this.init();
    }

    async init() {
        await this.loadAnalytics();
        this.setupRefreshTimer();
    }

    async loadAnalytics() {
        const data = await this.fetchAnalyticsData();
        this.renderCharts(data);
        this.renderMetrics(data);
    }

    renderCharts(data) {
        // View trends chart
        this.charts.viewTrends = new Chart(
            document.getElementById('viewTrendsChart').getContext('2d'),
            {
                type: 'line',
                data: {
                    labels: data.dates,
                    datasets: [{
                        label: 'Page Views',
                        data: data.views,
                        borderColor: '#2563eb',
                        tension: 0.1
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        title: {
                            display: true,
                            text: 'Content View Trends'
                        }
                    }
                }
            }
        );

        // Content performance chart
        this.charts.performance = new Chart(
            document.getElementById('contentPerformanceChart').getContext('2d'),
            {
                type: 'bar',
                data: {
                    labels: data.content.map(c => c.title),
                    datasets: [{
                        label: 'Views',
                        data: data.content.map(c => c.views),
                        backgroundColor: '#3b82f6'
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        title: {
                            display: true,
                            text: 'Content Performance'
                        }
                    }
                }
            }
        );
    }

    renderMetrics(data) {
        document.getElementById('analyticsMetrics').innerHTML = `
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold mb-2">Total Views</h3>
                    <p class="text-3xl font-bold">${data.totalViews}</p>
                    <p class="text-sm text-green-600 mt-2">
                        <i class="fas fa-arrow-up"></i> ${data.viewsGrowth}% vs last period
                    </p>
                </div>
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold mb-2">Avg. Time on Page</h3>
                    <p class="text-3xl font-bold">${data.avgTimeOnPage}s</p>
                </div>
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold mb-2">Interaction Rate</h3>
                    <p class="text-3xl font-bold">${data.interactionRate}%</p>
                </div>
            </div>
        `;
    }
}
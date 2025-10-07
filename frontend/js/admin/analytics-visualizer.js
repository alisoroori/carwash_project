class AnalyticsVisualizer {
    constructor() {
        this.charts = {};
        this.init();
    }

    async init() {
        await this.loadData();
        this.initializeCharts();
        this.setupRefreshTimer();
    }

    async loadData() {
        const response = await fetch('/carwash_project/backend/api/admin/get_analytics_data.php');
        return await response.json();
    }

    initializeCharts() {
        // Revenue Trends Chart
        this.charts.revenue = new Chart(
            document.getElementById('revenueChart').getContext('2d'),
            {
                type: 'line',
                data: {
                    labels: this.data.dates,
                    datasets: [{
                        label: 'Daily Revenue',
                        data: this.data.revenue,
                        borderColor: '#2563eb',
                        tension: 0.1
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: { position: 'top' },
                        title: { display: true, text: 'Revenue Trends' }
                    }
                }
            }
        );

        // Service Distribution Chart
        this.charts.services = new Chart(
            document.getElementById('serviceDistribution').getContext('2d'),
            {
                type: 'doughnut',
                data: {
                    labels: this.data.serviceNames,
                    datasets: [{
                        data: this.data.serviceCounts,
                        backgroundColor: [
                            '#3b82f6',
                            '#10b981',
                            '#f59e0b',
                            '#ef4444'
                        ]
                    }]
                }
            }
        );

        // Hourly Bookings Heatmap
        this.renderHeatmap(this.data.hourlyBookings);
    }

    renderHeatmap(data) {
        const container = document.getElementById('bookingHeatmap');
        const hours = Array.from({length: 24}, (_, i) => `${i}:00`);
        const days = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];

        container.innerHTML = `
            <div class="grid grid-cols-25 gap-1">
                ${this.generateHeatmapCells(data, hours, days)}
            </div>
        `;
    }
}
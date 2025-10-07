class AnalyticsDashboard {
    constructor() {
        this.charts = {};
        this.ws = null;
        this.init();
    }

    async init() {
        this.initCharts();
        this.connectWebSocket();
        await this.loadInitialData();
    }

    connectWebSocket() {
        this.ws = new WebSocket('ws://localhost:8081');
        
        this.ws.onopen = () => {
            this.ws.send(JSON.stringify({
                type: 'subscribe_analytics',
                role: 'admin'
            }));
        };

        this.ws.onmessage = (event) => {
            const data = JSON.parse(event.data);
            this.updateDashboard(data);
        };

        this.ws.onclose = () => {
            setTimeout(() => this.connectWebSocket(), 5000);
        };
    }

    updateDashboard(data) {
        // Update real-time stats
        document.getElementById('activeUsers').textContent = data.activeUsers;
        document.getElementById('todayBookings').textContent = data.todayBookings;
        document.getElementById('todayRevenue').textContent = 
            new Intl.NumberFormat('tr-TR', { style: 'currency', currency: 'TRY' })
                .format(data.todayRevenue);
        document.getElementById('activeCarwashes').textContent = data.activeCarwashes;

        // Update charts
        this.updateCharts(data);
        this.addActivityItem(data.latestActivity);
    }

    initCharts() {
        // Revenue Chart
        const revenueCtx = document.getElementById('revenueChart').getContext('2d');
        this.charts.revenue = new Chart(revenueCtx, {
            type: 'line',
            data: {
                labels: [],
                datasets: [{
                    label: 'Revenue',
                    data: [],
                    borderColor: '#2563eb',
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Bookings Chart
        const bookingsCtx = document.getElementById('bookingsChart').getContext('2d');
        this.charts.bookings = new Chart(bookingsCtx, {
            type: 'bar',
            data: {
                labels: [],
                datasets: [{
                    label: 'Bookings',
                    data: [],
                    backgroundColor: '#3b82f6'
                }]
            },
            options: {
                responsive: true
            }
        });
    }

    addActivityItem(activity) {
        const feed = document.getElementById('activityFeed');
        const item = document.createElement('div');
        item.className = 'flex items-center space-x-3 p-3 bg-gray-50 rounded';
        item.innerHTML = `
            <div class="flex-shrink-0">
                <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center">
                    <i class="fas ${this.getActivityIcon(activity.type)}"></i>
                </div>
            </div>
            <div class="flex-1">
                <p class="text-sm">${activity.message}</p>
                <p class="text-xs text-gray-500">${this.formatTime(activity.timestamp)}</p>
            </div>
        `;
        feed.insertBefore(item, feed.firstChild);
        if (feed.children.length > 10) {
            feed.lastChild.remove();
        }
    }

    getActivityIcon(type) {
        const icons = {
            'booking': 'fa-calendar-check',
            'payment': 'fa-credit-card',
            'review': 'fa-star',
            'user': 'fa-user'
        };
        return icons[type] || 'fa-info-circle';
    }

    formatTime(timestamp) {
        return new Date(timestamp).toLocaleTimeString('tr-TR');
    }
}

// Initialize dashboard
const dashboard = new AnalyticsDashboard();
class NotificationMonitor {
    constructor() {
        this.endpoints = {
            notifications: '/carwash_project/backend/api/admin/notifications/list.php',
            stats: '/carwash_project/backend/api/admin/notifications/stats.php',
            delivery: '/carwash_project/backend/api/admin/notifications/delivery.php'
        };
        this.charts = new Map();
        this.filters = {
            type: 'all',
            status: 'all',
            timeRange: '24h'
        };
        this.refreshInterval = 60000; // 1 minute
        this.init();
    }

    async init() {
        this.initializeCharts();
        this.setupEventListeners();
        await this.loadNotificationData();
        this.startRealTimeMonitoring();
    }

    initializeCharts() {
        // Delivery Success Rate Chart
        const deliveryCtx = document.getElementById('deliveryRateChart');
        if (deliveryCtx) {
            this.charts.set('delivery', new Chart(deliveryCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Delivered', 'Failed', 'Pending'],
                    datasets: [{
                        data: [0, 0, 0],
                        backgroundColor: ['#10B981', '#EF4444', '#F59E0B']
                    }]
                }
            }));
        }

        // Notification Volume Chart
        const volumeCtx = document.getElementById('notificationVolumeChart');
        if (volumeCtx) {
            this.charts.set('volume', new Chart(volumeCtx, {
                type: 'line',
                data: {
                    labels: [],
                    datasets: [{
                        label: 'Notifications Sent',
                        borderColor: '#3B82F6',
                        data: []
                    }]
                },
                options: {
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
        // Filter form
        const filterForm = document.getElementById('notificationFilterForm');
        filterForm?.addEventListener('submit', (e) => {
            e.preventDefault();
            this.updateFilters(new FormData(e.target));
            this.loadNotificationData();
        });

        // Refresh button
        const refreshBtn = document.getElementById('refreshNotifications');
        refreshBtn?.addEventListener('click', () => this.loadNotificationData());

        // Type filter
        document.querySelectorAll('.notification-type-filter').forEach(filter => {
            filter.addEventListener('change', () => this.loadNotificationData());
        });
    }

    async loadNotificationData() {
        try {
            const queryParams = new URLSearchParams(this.filters);
            const [notifications, stats] = await Promise.all([
                fetch(`${this.endpoints.notifications}?${queryParams}`).then(r => r.json()),
                fetch(`${this.endpoints.stats}?${queryParams}`).then(r => r.json())
            ]);

            this.updateNotificationList(notifications);
            this.updateStatistics(stats);
            this.updateCharts(stats);
        } catch (error) {
            this.showError('Failed to load notification data');
            console.error('Notification loading error:', error);
        }
    }

    updateNotificationList(notifications) {
        const notificationList = document.getElementById('notificationList');
        if (!notificationList) return;

        notificationList.innerHTML = notifications.map(notification => `
            <div class="notification-item ${notification.status}" data-id="${notification.id}">
                <div class="notification-header">
                    <span class="notification-type">${notification.type}</span>
                    <span class="notification-time">
                        ${new Date(notification.created_at).toLocaleString()}
                    </span>
                </div>
                <div class="notification-content">
                    <p class="notification-message">${notification.message}</p>
                    <div class="notification-meta">
                        <span>Recipients: ${notification.recipient_count}</span>
                        <span>Delivery Rate: ${notification.delivery_rate}%</span>
                    </div>
                </div>
                <div class="notification-actions">
                    <button class="resend-btn" onclick="notificationMonitor.resendNotification('${notification.id}')">
                        Resend
                    </button>
                    <button class="details-btn" onclick="notificationMonitor.showNotificationDetails('${notification.id}')">
                        Details
                    </button>
                </div>
            </div>
        `).join('');
    }

    async resendNotification(notificationId) {
        try {
            const response = await fetch(`${this.endpoints.delivery}`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    notification_id: notificationId,
                    action: 'resend'
                })
            });

            const result = await response.json();
            if (result.success) {
                this.showSuccess('Notification queued for resend');
                this.loadNotificationData();
            } else {
                this.showError(result.message);
            }
        } catch (error) {
            this.showError('Failed to resend notification');
        }
    }

    startRealTimeMonitoring() {
        // WebSocket connection for real-time monitoring
        const ws = new WebSocket('ws://localhost:8080/notification-monitor');
        
        ws.onmessage = (event) => {
            const data = JSON.parse(event.data);
            if (data.type === 'notification_update') {
                this.handleNotificationUpdate(data);
            }
        };

        // Fallback to polling if WebSocket fails
        setInterval(() => this.loadNotificationData(), this.refreshInterval);
    }

    showNotificationDetails(notificationId) {
        const modal = document.getElementById('notificationDetailsModal');
        if (!modal) return;

        modal.style.display = 'block';
        this.loadNotificationDetails(notificationId);
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

// Initialize notification monitor
const notificationMonitor = new NotificationMonitor();
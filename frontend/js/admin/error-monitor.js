class ErrorMonitor {
    constructor() {
        this.endpoints = {
            errors: '/carwash_project/backend/api/admin/errors/list.php',
            resolve: '/carwash_project/backend/api/admin/errors/resolve.php',
            stats: '/carwash_project/backend/api/admin/errors/stats.php'
        };
        this.charts = new Map();
        this.filters = {
            severity: 'all',
            timeRange: '24h',
            status: 'active'
        };
        this.refreshInterval = 30000; // 30 seconds
        this.init();
    }

    async init() {
        this.initializeCharts();
        this.setupEventListeners();
        await this.loadErrorData();
        this.startRealTimeMonitoring();
    }

    initializeCharts() {
        // Error Trend Chart
        const trendCtx = document.getElementById('errorTrendChart');
        if (trendCtx) {
            this.charts.set('trend', new Chart(trendCtx, {
                type: 'line',
                data: {
                    labels: [],
                    datasets: [{
                        label: 'Errors Over Time',
                        borderColor: '#EF4444',
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

        // Error Types Distribution
        const typesCtx = document.getElementById('errorTypesChart');
        if (typesCtx) {
            this.charts.set('types', new Chart(typesCtx, {
                type: 'doughnut',
                data: {
                    labels: [],
                    datasets: [{
                        data: [],
                        backgroundColor: [
                            '#EF4444', // Critical
                            '#F59E0B', // High
                            '#3B82F6', // Medium
                            '#10B981'  // Low
                        ]
                    }]
                }
            }));
        }
    }

    setupEventListeners() {
        // Filter form
        const filterForm = document.getElementById('errorFilterForm');
        filterForm?.addEventListener('submit', (e) => {
            e.preventDefault();
            this.updateFilters(new FormData(e.target));
            this.loadErrorData();
        });

        // Refresh button
        const refreshBtn = document.getElementById('refreshErrors');
        refreshBtn?.addEventListener('click', () => this.loadErrorData());

        // Error resolution buttons
        document.addEventListener('click', (e) => {
            if (e.target.matches('.resolve-error-btn')) {
                const errorId = e.target.dataset.errorId;
                this.resolveError(errorId);
            }
        });
    }

    async loadErrorData() {
        try {
            const queryParams = new URLSearchParams(this.filters);
            const [errors, stats] = await Promise.all([
                fetch(`${this.endpoints.errors}?${queryParams}`).then(r => r.json()),
                fetch(`${this.endpoints.stats}?${queryParams}`).then(r => r.json())
            ]);

            this.updateErrorList(errors);
            this.updateStatistics(stats);
            this.updateCharts(stats);
        } catch (error) {
            this.showError('Failed to load error data');
            console.error('Error loading data:', error);
        }
    }

    updateErrorList(errors) {
        const errorList = document.getElementById('errorList');
        if (!errorList) return;

        errorList.innerHTML = errors.map(error => `
            <div class="error-item severity-${error.severity}" data-id="${error.id}">
                <div class="error-header">
                    <span class="error-type">${error.type}</span>
                    <span class="error-time">${new Date(error.timestamp).toLocaleString()}</span>
                </div>
                <div class="error-content">
                    <p class="error-message">${error.message}</p>
                    <pre class="error-stack">${error.stack_trace}</pre>
                </div>
                <div class="error-actions">
                    <button class="resolve-error-btn" data-error-id="${error.id}">
                        Resolve
                    </button>
                    <button class="details-btn" onclick="errorMonitor.showErrorDetails('${error.id}')">
                        Details
                    </button>
                </div>
            </div>
        `).join('');
    }

    async resolveError(errorId) {
        try {
            const response = await fetch(this.endpoints.resolve, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ error_id: errorId })
            });

            const result = await response.json();
            if (result.success) {
                this.showSuccess('Error marked as resolved');
                this.loadErrorData();
            } else {
                this.showError(result.message);
            }
        } catch (error) {
            this.showError('Failed to resolve error');
            console.error('Error resolution failed:', error);
        }
    }

    startRealTimeMonitoring() {
        // WebSocket connection for real-time error monitoring
        const ws = new WebSocket('ws://localhost:8080/error-monitor');
        
        ws.onmessage = (event) => {
            const error = JSON.parse(event.data);
            this.handleNewError(error);
        };

        ws.onerror = (error) => {
            console.error('WebSocket connection failed:', error);
        };

        // Fallback to polling if WebSocket fails
        setInterval(() => this.loadErrorData(), this.refreshInterval);
    }

    handleNewError(error) {
        this.showNotification(`New ${error.severity} error detected`);
        this.loadErrorData();
    }

    showErrorDetails(errorId) {
        // Implementation for error details modal
        const modal = document.getElementById('errorDetailsModal');
        if (!modal) return;

        const error = this.errors.find(e => e.id === errorId);
        if (!error) return;

        modal.querySelector('.error-details-content').innerHTML = `
            <h3>Error Details</h3>
            <p><strong>Type:</strong> ${error.type}</p>
            <p><strong>Message:</strong> ${error.message}</p>
            <p><strong>Time:</strong> ${new Date(error.timestamp).toLocaleString()}</p>
            <p><strong>Severity:</strong> ${error.severity}</p>
            <pre>${error.stack_trace}</pre>
        `;

        modal.style.display = 'block';
    }

    showNotification(message) {
        const notification = document.createElement('div');
        notification.className = 'notification';
        notification.textContent = message;
        document.body.appendChild(notification);
        setTimeout(() => notification.remove(), 5000);
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

// Initialize error monitor
const errorMonitor = new ErrorMonitor();
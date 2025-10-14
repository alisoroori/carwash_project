class ConflictVisualizer {
    constructor() {
        this.endpoints = {
            conflicts: '/carwash_project/backend/api/carwash/conflicts/list.php',
            resolve: '/carwash_project/backend/api/carwash/conflicts/resolve.php',
            stats: '/carwash_project/backend/api/carwash/conflicts/stats.php'
        };
        this.charts = new Map();
        this.activeConflicts = new Set();
        this.init();
    }

    async init() {
        await this.loadChartLibrary();
        this.initializeCharts();
        this.setupEventListeners();
        await this.loadConflictData();
        this.startAutoRefresh();
    }

    async loadChartLibrary() {
        return new Promise((resolve, reject) => {
            const script = document.createElement('script');
            script.src = 'https://cdn.jsdelivr.net/npm/chart.js';
            script.onload = resolve;
            script.onerror = reject;
            document.head.appendChild(script);
        });
    }

    initializeCharts() {
        // Conflict Timeline Chart
        const timelineCtx = document.getElementById('conflictTimelineChart');
        if (timelineCtx) {
            this.charts.set('timeline', new Chart(timelineCtx, {
                type: 'bar',
                data: {
                    labels: [],
                    datasets: [{
                        label: 'Conflicts by Hour',
                        backgroundColor: '#EF4444',
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

        // Conflict Types Chart
        const typesCtx = document.getElementById('conflictTypesChart');
        if (typesCtx) {
            this.charts.set('types', new Chart(typesCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Overlap', 'Resource', 'Staff', 'Equipment'],
                    datasets: [{
                        data: [],
                        backgroundColor: [
                            '#EF4444',
                            '#F59E0B',
                            '#3B82F6',
                            '#10B981'
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: { position: 'right' }
                    }
                }
            }));
        }
    }

    setupEventListeners() {
        // Refresh button
        document.getElementById('refreshConflicts')?.addEventListener('click', () => {
            this.loadConflictData();
        });

        // Auto-resolve button
        document.getElementById('autoResolve')?.addEventListener('click', () => {
            this.handleAutoResolve();
        });

        // Filter conflicts
        document.getElementById('conflictFilter')?.addEventListener('change', (e) => {
            this.filterConflicts(e.target.value);
        });

        // Conflict resolution buttons
        document.addEventListener('click', (e) => {
            if (e.target.matches('.resolve-conflict')) {
                const conflictId = e.target.dataset.id;
                this.resolveConflict(conflictId);
            }
        });
    }

    async loadConflictData() {
        try {
            const [conflicts, stats] = await Promise.all([
                fetch(this.endpoints.conflicts).then(r => r.json()),
                fetch(this.endpoints.stats).then(r => r.json())
            ]);

            this.updateConflictList(conflicts);
            this.updateCharts(stats);
            this.updateStats(stats);
        } catch (error) {
            this.showError('Failed to load conflict data');
        }
    }

    updateConflictList(conflicts) {
        const container = document.getElementById('conflictsList');
        if (!container) return;

        container.innerHTML = conflicts.map(conflict => `
            <div class="conflict-card ${conflict.severity}" data-id="${conflict.id}">
                <div class="conflict-header">
                    <span class="conflict-type">${conflict.type}</span>
                    <span class="conflict-time">
                        ${new Date(conflict.timestamp).toLocaleString()}
                    </span>
                </div>
                <div class="conflict-details">
                    <p>${this.sanitizeHTML(conflict.description)}</p>
                    <div class="affected-bookings">
                        ${this.renderAffectedBookings(conflict.bookings)}
                    </div>
                </div>
                <div class="conflict-actions">
                    <button class="resolve-conflict" data-id="${conflict.id}">
                        Resolve
                    </button>
                    <button class="view-details" 
                            onclick="conflictVisualizer.showConflictDetails('${conflict.id}')">
                        Details
                    </button>
                </div>
            </div>
        `).join('');
    }

    renderAffectedBookings(bookings) {
        return bookings.map(booking => `
            <div class="booking-item">
                <span>Booking #${booking.id}</span>
                <span>${booking.service_name}</span>
                <span>${new Date(booking.time).toLocaleTimeString()}</span>
            </div>
        `).join('');
    }

    async resolveConflict(conflictId) {
        try {
            const response = await fetch(this.endpoints.resolve, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ conflict_id: conflictId })
            });

            const result = await response.json();
            if (result.success) {
                this.showSuccess('Conflict resolved');
                this.loadConflictData();
            } else {
                this.showError(result.message);
            }
        } catch (error) {
            this.showError('Failed to resolve conflict');
        }
    }

    startAutoRefresh() {
        setInterval(() => this.loadConflictData(), 300000); // Refresh every 5 minutes
    }

    sanitizeHTML(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
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

// Initialize conflict visualizer
document.addEventListener('DOMContentLoaded', () => new ConflictVisualizer());
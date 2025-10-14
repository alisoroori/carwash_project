class TransactionDashboard {
    constructor() {
        this.endpoints = {
            transactions: '/carwash_project/backend/api/admin/transactions/list.php',
            summary: '/carwash_project/backend/api/admin/transactions/summary.php',
            details: '/carwash_project/backend/api/admin/transactions/details.php'
        };
        this.charts = new Map();
        this.filters = {
            dateRange: '7days',
            status: 'all',
            paymentMethod: 'all'
        };
        this.init();
    }

    async init() {
        this.initializeCharts();
        this.setupEventListeners();
        await this.loadDashboardData();
        this.startAutoRefresh();
    }

    initializeCharts() {
        // Daily Transactions Chart
        const dailyCtx = document.getElementById('dailyTransactionsChart');
        if (dailyCtx) {
            this.charts.set('daily', new Chart(dailyCtx, {
                type: 'line',
                data: {
                    labels: [],
                    datasets: [{
                        label: 'Transaction Volume',
                        borderColor: '#3B82F6',
                        data: []
                    }, {
                        label: 'Average Value',
                        borderColor: '#10B981',
                        borderDash: [5, 5],
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

        // Payment Methods Chart
        const methodsCtx = document.getElementById('paymentMethodsChart');
        if (methodsCtx) {
            this.charts.set('methods', new Chart(methodsCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Credit Card', 'Bank Transfer', 'Digital Wallet', 'Cash'],
                    datasets: [{
                        data: [],
                        backgroundColor: ['#3B82F6', '#10B981', '#F59E0B', '#6B7280']
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
        // Date range filter
        document.getElementById('dateRangeFilter')?.addEventListener('change', (e) => {
            this.filters.dateRange = e.target.value;
            this.loadDashboardData();
        });

        // Status filter
        document.getElementById('statusFilter')?.addEventListener('change', (e) => {
            this.filters.status = e.target.value;
            this.loadDashboardData();
        });

        // Payment method filter
        document.getElementById('paymentMethodFilter')?.addEventListener('change', (e) => {
            this.filters.paymentMethod = e.target.value;
            this.loadDashboardData();
        });

        // Export transactions
        document.getElementById('exportTransactions')?.addEventListener('click', () => 
            this.exportTransactionData()
        );

        // Transaction row clicks
        document.addEventListener('click', (e) => {
            if (e.target.closest('.transaction-row')) {
                const transactionId = e.target.closest('.transaction-row').dataset.id;
                this.showTransactionDetails(transactionId);
            }
        });
    }

    async loadDashboardData() {
        try {
            const queryParams = new URLSearchParams(this.filters);
            const [transactions, summary] = await Promise.all([
                this.fetchData(`${this.endpoints.transactions}?${queryParams}`),
                this.fetchData(`${this.endpoints.summary}?${queryParams}`)
            ]);

            this.updateTransactionTable(transactions);
            this.updateCharts(summary);
            this.updateSummaryStats(summary);
        } catch (error) {
            this.showError('Failed to load dashboard data');
        }
    }

    updateTransactionTable(transactions) {
        const tableBody = document.getElementById('transactionsTable')?.querySelector('tbody');
        if (!tableBody) return;

        tableBody.innerHTML = transactions.map(tx => `
            <tr class="transaction-row" data-id="${tx.id}">
                <td>${tx.id}</td>
                <td>${new Date(tx.date).toLocaleDateString()}</td>
                <td>₺${tx.amount.toLocaleString()}</td>
                <td><span class="status-badge ${tx.status}">${tx.status}</span></td>
                <td>${tx.payment_method}</td>
                <td>${tx.customer_name}</td>
            </tr>
        `).join('');
    }

    async showTransactionDetails(transactionId) {
        try {
            const response = await fetch(`${this.endpoints.details}?id=${transactionId}`);
            const details = await response.json();

            const modal = document.createElement('div');
            modal.className = 'modal';
            modal.innerHTML = `
                <div class="modal-content">
                    <div class="modal-header">
                        <h3>Transaction Details #${transactionId}</h3>
                        <button class="close-btn">&times;</button>
                    </div>
                    <div class="modal-body">
                        <div class="detail-row">
                            <span>Date:</span>
                            <span>${new Date(details.date).toLocaleString()}</span>
                        </div>
                        <div class="detail-row">
                            <span>Amount:</span>
                            <span>₺${details.amount.toLocaleString()}</span>
                        </div>
                        <div class="detail-row">
                            <span>Status:</span>
                            <span class="status-badge ${details.status}">${details.status}</span>
                        </div>
                        <!-- Additional transaction details -->
                    </div>
                </div>
            `;

            document.body.appendChild(modal);
            modal.querySelector('.close-btn').onclick = () => modal.remove();
        } catch (error) {
            this.showError('Failed to load transaction details');
        }
    }

    async exportTransactionData() {
        try {
            const queryParams = new URLSearchParams({
                ...this.filters,
                export: 'true'
            });
            
            const response = await fetch(`${this.endpoints.transactions}?${queryParams}`);
            const blob = await response.blob();
            
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `transactions-${new Date().toISOString()}.csv`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            window.URL.revokeObjectURL(url);
        } catch (error) {
            this.showError('Failed to export transactions');
        }
    }

    startAutoRefresh() {
        setInterval(() => this.loadDashboardData(), 300000); // Refresh every 5 minutes
    }

    showError(message) {
        const alert = document.createElement('div');
        alert.className = 'error-alert';
        alert.textContent = message;
        document.body.appendChild(alert);
        setTimeout(() => alert.remove(), 5000);
    }
}

// Initialize transaction dashboard
document.addEventListener('DOMContentLoaded', () => new TransactionDashboard());
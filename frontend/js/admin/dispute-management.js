class DisputeManager {
    constructor() {
        this.endpoints = {
            list: '/carwash_project/backend/api/admin/disputes/list.php',
            update: '/carwash_project/backend/api/admin/disputes/update.php',
            resolve: '/carwash_project/backend/api/admin/disputes/resolve.php',
            assign: '/carwash_project/backend/api/admin/disputes/assign.php'
        };
        this.disputeList = document.getElementById('disputeList');
        this.filterForm = document.getElementById('disputeFilterForm');
        this.currentPage = 1;
        this.itemsPerPage = 10;
        
        this.init();
    }

    async init() {
        this.setupEventListeners();
        await this.loadDisputes();
        this.initializeSocketConnection();
    }

    setupEventListeners() {
        // Filter form submission
        this.filterForm?.addEventListener('submit', (e) => {
            e.preventDefault();
            this.currentPage = 1;
            this.loadDisputes();
        });

        // Pagination events
        document.querySelectorAll('.page-link').forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                this.currentPage = parseInt(e.target.dataset.page);
                this.loadDisputes();
            });
        });

        // Bulk actions
        const bulkActionBtn = document.getElementById('bulkActionBtn');
        bulkActionBtn?.addEventListener('click', () => this.handleBulkAction());
    }

    async loadDisputes() {
        try {
            const filters = new FormData(this.filterForm);
            const queryParams = new URLSearchParams({
                page: this.currentPage,
                limit: this.itemsPerPage,
                ...Object.fromEntries(filters)
            });

            const response = await fetch(`${this.endpoints.list}?${queryParams}`);
            const data = await response.json();

            this.renderDisputes(data.disputes);
            this.updatePagination(data.totalPages);
            this.updateStats(data.stats);
        } catch (error) {
            this.showError('Failed to load disputes');
            console.error('Dispute loading error:', error);
        }
    }

    renderDisputes(disputes) {
        if (!this.disputeList) return;

        this.disputeList.innerHTML = disputes.map(dispute => `
            <div class="dispute-card ${dispute.priority}" data-id="${dispute.id}">
                <div class="dispute-header">
                    <h3>Case #${dispute.id}</h3>
                    <span class="status-badge ${dispute.status}">${dispute.status}</span>
                </div>
                <div class="dispute-content">
                    <p class="customer-info">
                        Customer: ${dispute.customer_name}
                        <br>
                        Booking: #${dispute.booking_id}
                    </p>
                    <p class="dispute-description">${dispute.description}</p>
                    <div class="dispute-meta">
                        <span>Created: ${new Date(dispute.created_at).toLocaleString()}</span>
                        <span>Priority: ${dispute.priority}</span>
                    </div>
                </div>
                <div class="dispute-actions">
                    <button class="resolve-btn" onclick="disputeManager.resolveDispute(${dispute.id})">
                        Resolve
                    </button>
                    <button class="assign-btn" onclick="disputeManager.showAssignModal(${dispute.id})">
                        Assign
                    </button>
                </div>
            </div>
        `).join('');
    }

    async resolveDispute(disputeId) {
        const proceed = (window.showConfirm) ? await window.showConfirm('Are you sure you want to resolve this dispute?') : confirm('Are you sure you want to resolve this dispute?');
        if (!proceed) return;

        try {
            const response = await fetch(this.endpoints.resolve, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ dispute_id: disputeId })
            });

            const result = await response.json();
            if (result.success) {
                this.showSuccess('Dispute resolved successfully');
                this.loadDisputes();
            } else {
                this.showError(result.message);
            }
        } catch (error) {
            this.showError('Failed to resolve dispute');
            console.error('Dispute resolution error:', error);
        }
    }

    showAssignModal(disputeId) {
        const modal = document.getElementById('assignModal');
        if (!modal) return;

        modal.querySelector('#disputeId').value = disputeId;
        modal.style.display = 'block';

        modal.querySelector('.close').onclick = () => {
            modal.style.display = 'none';
        };

        modal.querySelector('form').onsubmit = (e) => {
            e.preventDefault();
            this.assignDispute(disputeId, e.target.elements.assignee.value);
            modal.style.display = 'none';
        };
    }

    async assignDispute(disputeId, assigneeId) {
        try {
            const response = await fetch(this.endpoints.assign, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    dispute_id: disputeId,
                    assignee_id: assigneeId
                })
            });

            const result = await response.json();
            if (result.success) {
                this.showSuccess('Dispute assigned successfully');
                this.loadDisputes();
            } else {
                this.showError(result.message);
            }
        } catch (error) {
            this.showError('Failed to assign dispute');
            console.error('Dispute assignment error:', error);
        }
    }

    initializeSocketConnection() {
        const socket = new WebSocket('ws://localhost:8080/disputes');
        
        socket.onmessage = (event) => {
            const data = JSON.parse(event.data);
            if (data.type === 'new_dispute') {
                this.showNotification('New dispute received');
                this.loadDisputes();
            }
        };

        socket.onerror = (error) => {
            console.error('WebSocket error:', error);
        };
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

// Initialize dispute manager
const disputeManager = new DisputeManager();
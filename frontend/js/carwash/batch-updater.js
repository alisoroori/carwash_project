class BatchUpdater {
    constructor() {
        this.endpoints = {
            batch: '/carwash_project/backend/api/carwash/batch/update.php',
            services: '/carwash_project/backend/api/carwash/services/list.php',
            schedules: '/carwash_project/backend/api/carwash/schedules/list.php'
        };
        this.selectedItems = new Set();
        this.batchQueue = [];
        this.init();
    }

    async init() {
        this.setupEventListeners();
        await this.loadInitialData();
        this.initializeBatchProcessor();
    }

    setupEventListeners() {
        // Batch selection controls
        document.getElementById('selectAll')?.addEventListener('change', e => {
            this.handleSelectAll(e.target.checked);
        });

        // Batch action buttons
        document.getElementById('batchUpdatePrice')?.addEventListener('click', () => {
            this.showBatchDialog('price');
        });

        document.getElementById('batchUpdateSchedule')?.addEventListener('click', () => {
            this.showBatchDialog('schedule');
        });

        // Batch process button
        document.getElementById('processBatch')?.addEventListener('click', () => {
            this.processBatchQueue();
        });

        // Individual item selection
        document.querySelectorAll('.batch-item-checkbox').forEach(checkbox => {
            checkbox.addEventListener('change', e => {
                this.handleItemSelection(e.target);
            });
        });
    }

    async loadInitialData() {
        try {
            const [services, schedules] = await Promise.all([
                fetch(this.endpoints.services).then(r => r.json()),
                fetch(this.endpoints.schedules).then(r => r.json())
            ]);

            this.renderBatchItems(services, schedules);
        } catch (error) {
            this.showError('Failed to load batch data');
        }
    }

    renderBatchItems(services, schedules) {
        const container = document.getElementById('batchItemsList');
        if (!container) return;

        container.innerHTML = `
            <div class="batch-header">
                <input type="checkbox" id="selectAll">
                <label for="selectAll">Select All</label>
                <div class="batch-actions">
                    <button id="batchUpdatePrice">Update Prices</button>
                    <button id="batchUpdateSchedule">Update Schedules</button>
                </div>
            </div>
            <div class="batch-items">
                ${services.map(service => this.renderServiceItem(service)).join('')}
                ${schedules.map(schedule => this.renderScheduleItem(schedule)).join('')}
            </div>
            <div class="batch-footer">
                <button id="processBatch" disabled>Process Updates</button>
            </div>
        `;
    }

    renderServiceItem(service) {
        return `
            <div class="batch-item service" data-id="${service.id}" data-type="service">
                <input type="checkbox" 
                       class="batch-item-checkbox" 
                       id="service-${service.id}"
                       data-id="${service.id}"
                       data-type="service">
                <label for="service-${service.id}">
                    ${this.sanitizeHTML(service.name)}
                    <span class="price">₺${service.price}</span>
                </label>
            </div>
        `;
    }

    renderScheduleItem(schedule) {
        return `
            <div class="batch-item schedule" data-id="${schedule.id}" data-type="schedule">
                <input type="checkbox" 
                       class="batch-item-checkbox" 
                       id="schedule-${schedule.id}"
                       data-id="${schedule.id}"
                       data-type="schedule">
                <label for="schedule-${schedule.id}">
                    ${schedule.day} (${schedule.start_time} - ${schedule.end_time})
                </label>
            </div>
        `;
    }

    async processBatchQueue() {
        if (!this.batchQueue.length) {
            this.showError('No updates in queue');
            return;
        }

        try {
            const response = await fetch(this.endpoints.batch, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ updates: this.batchQueue })
            });

            const result = await response.json();
            if (result.success) {
                this.showSuccess('Batch update completed');
                this.batchQueue = [];
                this.updateBatchUI();
                await this.loadInitialData();
            } else {
                this.showError(result.message);
            }
        } catch (error) {
            this.showError('Failed to process batch update');
        }
    }

    showBatchDialog(type) {
        const dialog = document.createElement('div');
        dialog.className = 'batch-dialog';
        
        const content = type === 'price' ? this.getPriceDialogContent() : 
                                         this.getScheduleDialogContent();
        
        dialog.innerHTML = `
            <div class="dialog-content">
                <h3>Batch ${type.charAt(0).toUpperCase() + type.slice(1)} Update</h3>
                ${content}
            </div>
        `;

        document.body.appendChild(dialog);

        dialog.querySelector('form').onsubmit = e => {
            e.preventDefault();
            this.handleBatchUpdate(type, new FormData(e.target));
            dialog.remove();
        };

        dialog.querySelector('.cancel').onclick = () => dialog.remove();
    }

    getPriceDialogContent() {
        return `
            <form id="batchPriceForm">
                <div class="form-group">
                    <label>Price Change:</label>
                    <select name="priceAction">
                        <option value="increase">Increase by</option>
                        <option value="decrease">Decrease by</option>
                        <option value="set">Set to</option>
                    </select>
                    <input type="number" name="priceAmount" min="0" step="1" required>
                </div>
                <div class="form-actions">
                    <button type="submit">Apply</button>
                    <button type="button" class="cancel">Cancel</button>
                </div>
            </form>
        `;
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

// Initialize batch updater
document.addEventListener('DOMContentLoaded', () => new BatchUpdater());
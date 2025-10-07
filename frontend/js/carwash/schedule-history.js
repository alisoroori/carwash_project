class ScheduleHistory {
    constructor(calendar) {
        this.calendar = calendar;
        this.history = [];
        this.currentIndex = -1;
        this.maxHistory = 20;
        
        this.endpoints = {
            history: '/carwash_project/backend/api/carwash/schedules/history.php',
            details: '/carwash_project/backend/api/carwash/schedules/details.php',
            restore: '/carwash_project/backend/api/carwash/schedules/restore.php'
        };
        this.filters = {
            startDate: null,
            endDate: null,
            status: 'all'
        };

        this.setupKeyboardShortcuts();
        this.init();
    }

    async init() {
        this.setupDatePickers();
        this.setupEventListeners();
        await this.loadScheduleHistory();
    }

    setupDatePickers() {
        // Initialize date range pickers
        const dateConfig = {
            enableTime: false,
            dateFormat: 'Y-m-d',
            onChange: (selectedDates, dateStr, instance) => {
                const id = instance.element.id;
                this.filters[id === 'startDate' ? 'startDate' : 'endDate'] = dateStr;
                this.loadScheduleHistory();
            }
        };

        flatpickr('#startDate', { ...dateConfig });
        flatpickr('#endDate', { ...dateConfig });
    }

    setupEventListeners() {
        // Status filter
        document.getElementById('statusFilter')?.addEventListener('change', (e) => {
            this.filters.status = e.target.value;
            this.loadScheduleHistory();
        });

        // Export button
        document.getElementById('exportHistory')?.addEventListener('click', () => {
            this.exportScheduleHistory();
        });

        // Restore button events are added dynamically in renderHistory
    }

    async loadScheduleHistory() {
        try {
            const params = new URLSearchParams(this.filters);
            const response = await fetch(`${this.endpoints.history}?${params}`);
            const history = await response.json();
            
            this.renderHistory(history);
            this.updateStatistics(history.stats);
        } catch (error) {
            this.showError('Failed to load schedule history');
        }
    }

    renderHistory(history) {
        const container = document.getElementById('historyList');
        if (!container) return;

        container.innerHTML = history.items.map(item => `
            <div class="history-item ${item.status}" data-id="${item.id}">
                <div class="history-header">
                    <span class="history-date">
                        ${new Date(item.date).toLocaleDateString()}
                    </span>
                    <span class="history-status ${item.status}">
                        ${this.capitalizeFirst(item.status)}
                    </span>
                </div>
                <div class="history-details">
                    <div class="stats-row">
                        <span>Total Slots: ${item.total_slots}</span>
                        <span>Booked: ${item.booked_slots}</span>
                        <span>Available: ${item.available_slots}</span>
                    </div>
                    <div class="modification-info">
                        <span>Last Modified: ${new Date(item.modified_at).toLocaleString()}</span>
                        <span>By: ${this.sanitizeHTML(item.modified_by)}</span>
                    </div>
                </div>
                <div class="history-actions">
                    <button class="view-btn" onclick="scheduleHistory.viewDetails('${item.id}')">
                        <i class="fas fa-eye"></i> View
                    </button>
                    ${item.can_restore ? `
                        <button class="restore-btn" onclick="scheduleHistory.restoreSchedule('${item.id}')">
                            <i class="fas fa-history"></i> Restore
                        </button>
                    ` : ''}
                </div>
            </div>
        `).join('');
    }

    async viewDetails(scheduleId) {
        try {
            const response = await fetch(`${this.endpoints.details}?id=${scheduleId}`);
            const details = await response.json();
            
            this.showDetailsDialog(details);
        } catch (error) {
            this.showError('Failed to load schedule details');
        }
    }

    showDetailsDialog(details) {
        const dialog = document.createElement('div');
        dialog.className = 'details-dialog';
        dialog.innerHTML = `
            <div class="dialog-content">
                <div class="dialog-header">
                    <h3>Schedule Details - ${new Date(details.date).toLocaleDateString()}</h3>
                    <button class="close-btn">&times;</button>
                </div>
                <div class="schedule-timeline">
                    ${this.renderTimeline(details.slots)}
                </div>
                <div class="modification-history">
                    <h4>Modification History</h4>
                    ${this.renderModificationHistory(details.modifications)}
                </div>
            </div>
        `;

        document.body.appendChild(dialog);
        dialog.querySelector('.close-btn').onclick = () => dialog.remove();
    }

    renderTimeline(slots) {
        return `
            <div class="timeline">
                ${slots.map(slot => `
                    <div class="timeline-slot ${slot.status}">
                        <span class="time">${slot.start_time} - ${slot.end_time}</span>
                        <span class="capacity">
                            ${slot.booked}/${slot.capacity} slots used
                        </span>
                    </div>
                `).join('')}
            </div>
        `;
    }

    async restoreSchedule(scheduleId) {
        if (!confirm('Are you sure you want to restore this schedule?')) return;

        try {
            const response = await fetch(this.endpoints.restore, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ schedule_id: scheduleId })
            });

            const result = await response.json();
            if (result.success) {
                this.showSuccess('Schedule restored successfully');
                await this.loadScheduleHistory();
            } else {
                this.showError(result.message);
            }
        } catch (error) {
            this.showError('Failed to restore schedule');
        }
    }

    capitalizeFirst(str) {
        return str.charAt(0).toUpperCase() + str.slice(1);
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

    addToHistory(schedules) {
        // Remove any future states if we're not at the end
        if (this.currentIndex < this.history.length - 1) {
            this.history = this.history.slice(0, this.currentIndex + 1);
        }

        // Add new state
        this.history.push(JSON.stringify(schedules));
        if (this.history.length > this.maxHistory) {
            this.history.shift();
        }
        this.currentIndex = this.history.length - 1;
    }

    undo() {
        if (this.currentIndex > 0) {
            this.currentIndex--;
            const schedules = JSON.parse(this.history[this.currentIndex]);
            this.calendar.updateSchedules(schedules, false);
            return true;
        }
        return false;
    }

    redo() {
        if (this.currentIndex < this.history.length - 1) {
            this.currentIndex++;
            const schedules = JSON.parse(this.history[this.currentIndex]);
            this.calendar.updateSchedules(schedules, false);
            return true;
        }
        return false;
    }

    setupKeyboardShortcuts() {
        document.addEventListener('keydown', (e) => {
            if (e.ctrlKey) {
                if (e.key === 'z') {
                    e.preventDefault();
                    this.undo();
                } else if (e.key === 'y') {
                    e.preventDefault();
                    this.redo();
                }
            }
        });
    }
}

// Initialize schedule history
document.addEventListener('DOMContentLoaded', () => new ScheduleHistory());
class ScheduleClipboard {
    constructor() {
        this.endpoints = {
            schedules: '/carwash_project/backend/api/carwash/schedules/list.php',
            copy: '/carwash_project/backend/api/carwash/schedules/copy.php',
            paste: '/carwash_project/backend/api/carwash/schedules/paste.php'
        };
        this.copiedSchedule = null;
        this.selectedDates = new Set();
        this.init();
    }

    async init() {
        this.setupEventListeners();
        this.initializeCalendarPicker();
        await this.loadSchedules();
    }

    initializeCalendarPicker() {
        const picker = document.getElementById('datePicker');
        if (!picker) return;

        // Initialize Flatpickr date picker
        this.datePicker = flatpickr(picker, {
            mode: 'multiple',
            dateFormat: 'Y-m-d',
            onChange: (selectedDates) => {
                this.selectedDates = new Set(selectedDates.map(date => 
                    date.toISOString().split('T')[0]
                ));
                this.updateUI();
            }
        });
    }

    setupEventListeners() {
        // Copy button
        document.getElementById('copySchedule')?.addEventListener('click', () => {
            this.copySelectedSchedule();
        });

        // Paste button
        document.getElementById('pasteSchedule')?.addEventListener('click', () => {
            this.pasteSchedule();
        });

        // Clear selection button
        document.getElementById('clearSelection')?.addEventListener('click', () => {
            this.clearSelection();
        });

        // Template selection
        document.getElementById('templateSelect')?.addEventListener('change', (e) => {
            this.loadTemplateSchedule(e.target.value);
        });
    }

    async loadSchedules() {
        try {
            const response = await fetch(this.endpoints.schedules);
            const schedules = await response.json();
            this.renderScheduleList(schedules);
        } catch (error) {
            this.showError('Failed to load schedules');
        }
    }

    renderScheduleList(schedules) {
        const container = document.getElementById('schedulesList');
        if (!container) return;

        container.innerHTML = schedules.map(schedule => `
            <div class="schedule-item" data-id="${schedule.id}">
                <div class="schedule-header">
                    <span class="schedule-date">
                        ${new Date(schedule.date).toLocaleDateString()}
                    </span>
                    <div class="schedule-actions">
                        <button class="copy-btn" onclick="scheduleClipboard.copySchedule('${schedule.id}')">
                            <i class="fas fa-copy"></i> Copy
                        </button>
                        <button class="preview-btn" 
                                onclick="scheduleClipboard.previewSchedule('${schedule.id}')">
                            <i class="fas fa-eye"></i> Preview
                        </button>
                    </div>
                </div>
                <div class="schedule-slots">
                    ${this.renderTimeSlots(schedule.slots)}
                </div>
            </div>
        `).join('');
    }

    renderTimeSlots(slots) {
        return slots.map(slot => `
            <div class="time-slot ${slot.status}">
                <span class="time">${slot.start_time} - ${slot.end_time}</span>
                <span class="capacity">Capacity: ${slot.capacity}</span>
            </div>
        `).join('');
    }

    async copySchedule(scheduleId) {
        try {
            const response = await fetch(`${this.endpoints.copy}?id=${scheduleId}`);
            const schedule = await response.json();
            
            this.copiedSchedule = schedule;
            this.showSuccess('Schedule copied to clipboard');
            this.updateUI();
        } catch (error) {
            this.showError('Failed to copy schedule');
        }
    }

    async pasteSchedule() {
        if (!this.copiedSchedule || this.selectedDates.size === 0) {
            this.showError('Please copy a schedule and select target dates first');
            return;
        }

        try {
            const response = await fetch(this.endpoints.paste, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    schedule: this.copiedSchedule,
                    dates: Array.from(this.selectedDates)
                })
            });

            const result = await response.json();
            if (result.success) {
                this.showSuccess('Schedule pasted successfully');
                await this.loadSchedules();
                this.clearSelection();
            } else {
                this.showError(result.message);
            }
        } catch (error) {
            this.showError('Failed to paste schedule');
        }
    }

    clearSelection() {
        this.selectedDates.clear();
        this.datePicker?.clear();
        this.updateUI();
    }

    updateUI() {
        // Update paste button state
        const pasteBtn = document.getElementById('pasteSchedule');
        if (pasteBtn) {
            pasteBtn.disabled = !this.copiedSchedule || this.selectedDates.size === 0;
        }

        // Update selection counter
        const counter = document.getElementById('selectedDatesCount');
        if (counter) {
            counter.textContent = this.selectedDates.size;
        }
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

// Initialize schedule clipboard
document.addEventListener('DOMContentLoaded', () => new ScheduleClipboard());
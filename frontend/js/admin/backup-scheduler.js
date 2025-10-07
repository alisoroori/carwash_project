class BackupScheduler {
    constructor() {
        this.endpoint = '/carwash_project/backend/api/admin/backup.php';
        this.scheduleForm = document.getElementById('backupScheduleForm');
        this.backupsList = document.getElementById('backupsList');
        this.statusIndicator = document.getElementById('backupStatus');
        this.init();
    }

    init() {
        this.loadExistingSchedules();
        this.bindEvents();
        this.initDatePicker();
    }

    bindEvents() {
        this.scheduleForm?.addEventListener('submit', this.handleScheduleSubmit.bind(this));
        document.querySelectorAll('.delete-backup').forEach(btn => {
            btn.addEventListener('click', this.handleBackupDelete.bind(this));
        });
    }

    async loadExistingSchedules() {
        try {
            const response = await fetch(`${this.endpoint}?action=list`);
            const schedules = await response.json();
            this.updateSchedulesList(schedules);
        } catch (error) {
            this.showError('Failed to load backup schedules');
        }
    }

    async handleScheduleSubmit(event) {
        event.preventDefault();
        const formData = new FormData(event.target);

        try {
            const response = await fetch(this.endpoint, {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            if (result.success) {
                this.showSuccess('Backup scheduled successfully');
                this.loadExistingSchedules();
            } else {
                this.showError(result.message);
            }
        } catch (error) {
            this.showError('Failed to schedule backup');
        }
    }

    updateSchedulesList(schedules) {
        if (!this.backupsList) return;

        this.backupsList.innerHTML = schedules.map(schedule => `
            <div class="backup-item" data-id="${schedule.id}">
                <div class="backup-info">
                    <h4>Scheduled: ${new Date(schedule.scheduled_time).toLocaleString()}</h4>
                    <p>Type: ${schedule.backup_type}</p>
                    <p>Status: <span class="status-${schedule.status}">${schedule.status}</span></p>
                </div>
                <div class="backup-actions">
                    <button class="delete-backup" data-id="${schedule.id}">Delete</button>
                    <button class="run-backup" data-id="${schedule.id}">Run Now</button>
                </div>
            </div>
        `).join('');
    }

    async handleBackupDelete(event) {
        const backupId = event.target.dataset.id;
        if (!confirm('Are you sure you want to delete this backup schedule?')) return;

        try {
            const response = await fetch(`${this.endpoint}?action=delete&id=${backupId}`, {
                method: 'DELETE'
            });
            
            const result = await response.json();
            if (result.success) {
                this.showSuccess('Backup schedule deleted');
                this.loadExistingSchedules();
            } else {
                this.showError(result.message);
            }
        } catch (error) {
            this.showError('Failed to delete backup schedule');
        }
    }

    initDatePicker() {
        const dateInputs = document.querySelectorAll('.date-picker');
        dateInputs.forEach(input => {
            flatpickr(input, {
                enableTime: true,
                dateFormat: "Y-m-d H:i",
                minDate: "today"
            });
        });
    }

    showSuccess(message) {
        if (this.statusIndicator) {
            this.statusIndicator.textContent = message;
            this.statusIndicator.className = 'success';
            setTimeout(() => {
                this.statusIndicator.textContent = '';
            }, 3000);
        }
    }

    showError(message) {
        if (this.statusIndicator) {
            this.statusIndicator.textContent = message;
            this.statusIndicator.className = 'error';
            setTimeout(() => {
                this.statusIndicator.textContent = '';
            }, 3000);
        }
    }
}

// Initialize backup scheduler
document.addEventListener('DOMContentLoaded', () => new BackupScheduler());
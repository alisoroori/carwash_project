class ScheduleTemplates {
    constructor() {
        this.endpoints = {
            templates: '/carwash_project/backend/api/carwash/templates/list.php',
            save: '/carwash_project/backend/api/carwash/templates/save.php',
            apply: '/carwash_project/backend/api/carwash/templates/apply.php',
            preview: '/carwash_project/backend/api/carwash/templates/preview.php'
        };
        this.currentTemplate = null;
        this.timeSlots = [];
        this.init();
    }

    async init() {
        this.setupEventListeners();
        await this.loadTemplates();
        this.initializeTimeGrid();
    }

    setupEventListeners() {
        // Template selection
        document.getElementById('templateSelect')?.addEventListener('change', (e) => {
            this.loadTemplate(e.target.value);
        });

        // Save template
        document.getElementById('saveTemplate')?.addEventListener('submit', (e) => {
            e.preventDefault();
            this.saveTemplate(new FormData(e.target));
        });

        // Apply template
        document.getElementById('applyTemplate')?.addEventListener('click', () => {
            this.showApplyDialog();
        });

        // Time slot selection
        document.getElementById('timeGrid')?.addEventListener('click', (e) => {
            if (e.target.matches('.time-slot')) {
                this.toggleTimeSlot(e.target);
            }
        });
    }

    async loadTemplates() {
        try {
            const response = await fetch(this.endpoints.templates);
            const templates = await response.json();
            this.renderTemplateList(templates);
        } catch (error) {
            this.showError('Failed to load templates');
        }
    }

    renderTemplateList(templates) {
        const select = document.getElementById('templateSelect');
        if (!select) return;

        select.innerHTML = `
            <option value="">Select a template</option>
            ${templates.map(template => `
                <option value="${template.id}">${this.sanitizeHTML(template.name)}</option>
            `).join('')}
        `;
    }

    initializeTimeGrid() {
        const grid = document.getElementById('timeGrid');
        if (!grid) return;

        const hours = Array.from({ length: 12 }, (_, i) => i + 8); // 8 AM to 8 PM
        const minutes = ['00', '30'];

        grid.innerHTML = hours.map(hour => minutes.map(minute => {
            const time = `${hour.toString().padStart(2, '0')}:${minute}`;
            return `
                <div class="time-slot" data-time="${time}">
                    <span class="time-label">${time}</span>
                    <div class="slot-capacity">
                        <input type="number" min="0" max="10" value="1">
                    </div>
                </div>
            `;
        }).join('')).join('');
    }

    async loadTemplate(templateId) {
        if (!templateId) return;

        try {
            const response = await fetch(`${this.endpoints.templates}?id=${templateId}`);
            const template = await response.json();
            
            this.currentTemplate = template;
            this.displayTemplate(template);
        } catch (error) {
            this.showError('Failed to load template');
        }
    }

    displayTemplate(template) {
        // Reset all slots
        document.querySelectorAll('.time-slot').forEach(slot => {
            slot.classList.remove('active');
            slot.querySelector('input').value = '1';
        });

        // Apply template slots
        template.slots.forEach(slot => {
            const slotElement = document.querySelector(`[data-time="${slot.time}"]`);
            if (slotElement) {
                slotElement.classList.add('active');
                slotElement.querySelector('input').value = slot.capacity;
            }
        });

        // Update template info
        document.getElementById('templateName').value = template.name;
        document.getElementById('templateDescription').value = template.description;
    }

    async saveTemplate(formData) {
        const templateData = {
            name: formData.get('templateName'),
            description: formData.get('templateDescription'),
            slots: this.getSelectedSlots()
        };

        try {
            const response = await fetch(this.endpoints.save, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(templateData)
            });

            const result = await response.json();
            if (result.success) {
                this.showSuccess('Template saved successfully');
                await this.loadTemplates();
            } else {
                this.showError(result.message);
            }
        } catch (error) {
            this.showError('Failed to save template');
        }
    }

    getSelectedSlots() {
        return Array.from(document.querySelectorAll('.time-slot.active')).map(slot => ({
            time: slot.dataset.time,
            capacity: parseInt(slot.querySelector('input').value)
        }));
    }

    showApplyDialog() {
        if (!this.currentTemplate) {
            this.showError('Please select a template first');
            return;
        }

        const dialog = document.createElement('div');
        dialog.className = 'template-dialog';
        dialog.innerHTML = `
            <div class="dialog-content">
                <h3>Apply Template: ${this.sanitizeHTML(this.currentTemplate.name)}</h3>
                <form id="applyTemplateForm">
                    <div class="form-group">
                        <label>Select Dates:</label>
                        <input type="text" id="datePicker" class="flatpickr" required>
                    </div>
                    <div class="form-group">
                        <label>Override Existing:</label>
                        <input type="checkbox" name="override">
                    </div>
                    <div class="dialog-actions">
                        <button type="submit">Apply</button>
                        <button type="button" class="cancel">Cancel</button>
                    </div>
                </form>
            </div>
        `;

        document.body.appendChild(dialog);
        this.initializeDatePicker();
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

// Initialize schedule templates
document.addEventListener('DOMContentLoaded', () => new ScheduleTemplates());
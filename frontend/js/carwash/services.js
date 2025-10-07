class ServiceManager {
    constructor() {
        this.endpoints = {
            services: '/carwash_project/backend/api/carwash/services/list.php',
            update: '/carwash_project/backend/api/carwash/services/update.php',
            availability: '/carwash_project/backend/api/carwash/services/availability.php',
            stats: '/carwash_project/backend/api/carwash/services/stats.php'
        };
        this.activeServices = new Map();
        this.init();
    }

    async init() {
        this.setupEventListeners();
        await this.loadServices();
        this.initializeServiceStats();
        this.startAutoRefresh();
    }

    setupEventListeners() {
        // Add service button
        document.getElementById('addService')?.addEventListener('click', () => {
            this.showServiceForm();
        });

        // Bulk actions
        document.getElementById('bulkActions')?.addEventListener('change', (e) => {
            this.handleBulkAction(e.target.value);
        });

        // Service status toggle
        document.addEventListener('change', (e) => {
            if (e.target.matches('.service-status-toggle')) {
                this.toggleServiceStatus(e.target.dataset.serviceId);
            }
        });

        // Search and filter
        document.getElementById('serviceSearch')?.addEventListener('input', (e) => {
            this.filterServices(e.target.value);
        });
    }

    async loadServices() {
        try {
            const response = await fetch(this.endpoints.services);
            const services = await response.json();
            
            this.activeServices.clear();
            services.forEach(service => this.activeServices.set(service.id, service));
            
            this.renderServices();
        } catch (error) {
            this.showError('Failed to load services');
        }
    }

    renderServices() {
        const container = document.getElementById('servicesList');
        if (!container) return;

        container.innerHTML = Array.from(this.activeServices.values()).map(service => `
            <div class="service-card ${service.status}" data-id="${service.id}">
                <div class="service-header">
                    <h3>${this.sanitizeHTML(service.name)}</h3>
                    <label class="switch">
                        <input type="checkbox" 
                               class="service-status-toggle"
                               data-service-id="${service.id}"
                               ${service.status === 'active' ? 'checked' : ''}>
                        <span class="slider"></span>
                    </label>
                </div>
                
                <div class="service-details">
                    <div class="price-duration">
                        <span class="price">₺${service.price.toFixed(2)}</span>
                        <span class="duration">${service.duration} mins</span>
                    </div>
                    <p class="description">${this.sanitizeHTML(service.description)}</p>
                </div>

                <div class="service-stats">
                    <div class="stat-item">
                        <span class="stat-label">Bookings</span>
                        <span class="stat-value">${service.booking_count}</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Rating</span>
                        <span class="stat-value">${service.average_rating.toFixed(1)}★</span>
                    </div>
                </div>

                <div class="service-actions">
                    <button class="edit-btn" 
                            onclick="serviceManager.editService('${service.id}')">
                        Edit
                    </button>
                    <button class="availability-btn" 
                            onclick="serviceManager.manageAvailability('${service.id}')">
                        Availability
                    </button>
                </div>
            </div>
        `).join('');
    }

    showServiceForm(serviceId = null) {
        const service = serviceId ? this.activeServices.get(serviceId) : null;
        const dialog = document.createElement('div');
        dialog.className = 'service-dialog';
        dialog.innerHTML = `
            <div class="dialog-content">
                <h3>${service ? 'Edit' : 'Add'} Service</h3>
                <form id="serviceForm">
                    <input type="hidden" name="id" value="${service?.id || ''}">
                    
                    <div class="form-group">
                        <label>Service Name</label>
                        <input type="text" 
                               name="name" 
                               value="${service?.name || ''}"
                               required>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Price (₺)</label>
                            <input type="number" 
                                   name="price" 
                                   min="0" 
                                   step="0.01"
                                   value="${service?.price || '0'}"
                                   required>
                        </div>
                        <div class="form-group">
                            <label>Duration (mins)</label>
                            <input type="number" 
                                   name="duration" 
                                   min="15" 
                                   step="15"
                                   value="${service?.duration || '30'}"
                                   required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" required>${service?.description || ''}</textarea>
                    </div>

                    <div class="dialog-actions">
                        <button type="submit">${service ? 'Update' : 'Add'}</button>
                        <button type="button" class="cancel">Cancel</button>
                    </div>
                </form>
            </div>
        `;

        document.body.appendChild(dialog);

        dialog.querySelector('form').onsubmit = (e) => {
            e.preventDefault();
            this.saveService(new FormData(e.target));
            dialog.remove();
        };

        dialog.querySelector('.cancel').onclick = () => dialog.remove();
    }

    async saveService(formData) {
        try {
            const response = await fetch(this.endpoints.update, {
                method: 'POST',
                body: formData
            });

            const result = await response.json();
            if (result.success) {
                this.showSuccess('Service saved successfully');
                await this.loadServices();
            } else {
                this.showError(result.message);
            }
        } catch (error) {
            this.showError('Failed to save service');
        }
    }

    startAutoRefresh() {
        setInterval(() => this.loadServices(), 300000); // Refresh every 5 minutes
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

// Initialize service manager
document.addEventListener('DOMContentLoaded', () => new ServiceManager());
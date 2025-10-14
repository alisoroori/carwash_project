class ServiceManagement {
    constructor() {
        this.endpoints = {
            services: '/carwash_project/backend/api/carwash/services/manage.php',
            status: '/carwash_project/backend/api/carwash/services/status.php',
            categories: '/carwash_project/backend/api/carwash/services/categories.php',
            availability: '/carwash_project/backend/api/carwash/services/availability.php'
        };
        this.services = new Map();
        this.categories = new Map();
        this.init();
    }

    async init() {
        await Promise.all([
            this.loadServices(),
            this.loadCategories()
        ]);
        this.setupEventListeners();
        this.initializeDragAndDrop();
        this.startAutoRefresh();
    }

    setupEventListeners() {
        // Category management
        document.getElementById('addCategory')?.addEventListener('click', () => {
            this.showCategoryDialog();
        });

        // Service management
        document.getElementById('addService')?.addEventListener('click', () => {
            this.showServiceDialog();
        });

        // Bulk actions
        document.getElementById('bulkActions')?.addEventListener('change', (e) => {
            this.handleBulkAction(e.target.value);
        });

        // Search functionality
        document.getElementById('serviceSearch')?.addEventListener('input', (e) => {
            this.filterServices(e.target.value);
        });
    }

    async loadServices() {
        try {
            const response = await fetch(this.endpoints.services);
            const services = await response.json();
            
            this.services.clear();
            services.forEach(service => this.services.set(service.id, service));
            
            this.renderServices();
            this.updateServiceStats();
        } catch (error) {
            this.showError('Failed to load services');
        }
    }

    renderServices() {
        const container = document.getElementById('serviceGrid');
        if (!container) return;

        container.innerHTML = Array.from(this.services.values()).map(service => `
            <div class="service-card ${service.status}" 
                 data-id="${service.id}" 
                 draggable="true">
                <div class="service-header">
                    <h3>${this.sanitizeHTML(service.name)}</h3>
                    <div class="service-controls">
                        <label class="switch">
                            <input type="checkbox" 
                                   class="service-toggle" 
                                   ${service.active ? 'checked' : ''}
                                   data-id="${service.id}">
                            <span class="slider"></span>
                        </label>
                        <button class="edit-btn" 
                                onclick="serviceManagement.editService('${service.id}')">
                            <i class="fas fa-edit"></i>
                        </button>
                    </div>
                </div>

                <div class="service-details">
                    <div class="price-info">
                        <span class="price">₺${service.price.toFixed(2)}</span>
                        <span class="duration">${service.duration} mins</span>
                    </div>
                    <p class="description">${this.sanitizeHTML(service.description)}</p>
                    <div class="service-tags">
                        ${this.renderServiceTags(service.tags)}
                    </div>
                </div>

                <div class="service-metrics">
                    <div class="metric">
                        <span>Bookings</span>
                        <strong>${service.booking_count}</strong>
                    </div>
                    <div class="metric">
                        <span>Revenue</span>
                        <strong>₺${service.revenue.toLocaleString()}</strong>
                    </div>
                    <div class="metric">
                        <span>Rating</span>
                        <strong>${service.rating.toFixed(1)}★</strong>
                    </div>
                </div>

                <div class="service-actions">
                    <button class="availability-btn" 
                            onclick="serviceManagement.manageAvailability('${service.id}')">
                        Manage Availability
                    </button>
                    <button class="pricing-btn"
                            onclick="serviceManagement.managePricing('${service.id}')">
                        Pricing Options
                    </button>
                </div>
            </div>
        `).join('');

        this.initializeDragListeners();
    }

    showServiceDialog(serviceId = null) {
        const service = serviceId ? this.services.get(serviceId) : null;
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
                            <select name="duration" required>
                                ${[15, 30, 45, 60, 90, 120].map(d => `
                                    <option value="${d}" 
                                            ${service?.duration === d ? 'selected' : ''}>
                                        ${d} minutes
                                    </option>
                                `).join('')}
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Category</label>
                        <select name="category_id" required>
                            ${Array.from(this.categories.values()).map(cat => `
                                <option value="${cat.id}" 
                                        ${service?.category_id === cat.id ? 'selected' : ''}>
                                    ${this.sanitizeHTML(cat.name)}
                                </option>
                            `).join('')}
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" required>${service?.description || ''}</textarea>
                    </div>

                    <div class="dialog-actions">
                        <button type="submit">${service ? 'Update' : 'Add'} Service</button>
                        <button type="button" class="cancel">Cancel</button>
                    </div>
                </form>
            </div>
        `;

        document.body.appendChild(dialog);
        this.initializeDialogEvents(dialog);
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

// Initialize service management
document.addEventListener('DOMContentLoaded', () => new ServiceManagement());
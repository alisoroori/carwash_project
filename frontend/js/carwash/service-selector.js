class ServiceSelector {
    constructor() {
        this.endpoints = {
            services: '/carwash_project/backend/api/carwash/services/list.php',
            update: '/carwash_project/backend/api/carwash/services/update.php',
            pricing: '/carwash_project/backend/api/carwash/services/pricing.php'
        };
        this.selectedServices = new Set();
        this.init();
    }

    async init() {
        this.setupEventListeners();
        await this.loadServices();
        this.initializePriceCalculator();
    }

    setupEventListeners() {
        // Service selection
        document.getElementById('serviceList')?.addEventListener('change', (e) => {
            if (e.target.matches('input[type="checkbox"]')) {
                this.handleServiceSelection(e.target);
            }
        });

        // Duration updates
        document.querySelectorAll('.service-duration')?.forEach(input => {
            input.addEventListener('change', (e) => {
                this.updateServiceDuration(e.target);
            });
        });

        // Price updates
        document.querySelectorAll('.service-price')?.forEach(input => {
            input.addEventListener('change', (e) => {
                this.updateServicePrice(e.target);
            });
        });

        // Service form submission
        document.getElementById('serviceForm')?.addEventListener('submit', (e) => {
            e.preventDefault();
            this.saveServices(new FormData(e.target));
        });
    }

    async loadServices() {
        try {
            const response = await fetch(this.endpoints.services);
            const services = await response.json();
            this.renderServices(services);
        } catch (error) {
            this.showError('Failed to load services');
        }
    }

    renderServices(services) {
        const container = document.getElementById('serviceList');
        if (!container) return;

        container.innerHTML = services.map(service => `
            <div class="service-card ${service.active ? 'active' : ''}" data-id="${service.id}">
                <div class="service-header">
                    <input type="checkbox" 
                           id="service-${service.id}" 
                           ${service.active ? 'checked' : ''}>
                    <label for="service-${service.id}">${this.sanitizeHTML(service.name)}</label>
                </div>
                <div class="service-details">
                    <div class="form-group">
                        <label>Duration (minutes):</label>
                        <input type="number" 
                               class="service-duration" 
                               value="${service.duration}"
                               min="15" 
                               step="15"
                               data-id="${service.id}">
                    </div>
                    <div class="form-group">
                        <label>Price (₺):</label>
                        <input type="number" 
                               class="service-price" 
                               value="${service.price}"
                               min="0" 
                               step="10"
                               data-id="${service.id}">
                    </div>
                </div>
                <div class="service-description">
                    <p>${this.sanitizeHTML(service.description)}</p>
                </div>
            </div>
        `).join('');
    }

    async updateServicePrice(input) {
        const serviceId = input.dataset.id;
        const newPrice = parseFloat(input.value);

        try {
            const response = await fetch(this.endpoints.pricing, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    service_id: serviceId,
                    price: newPrice
                })
            });

            const result = await response.json();
            if (result.success) {
                this.showSuccess('Price updated successfully');
            } else {
                this.showError(result.message);
                input.value = input.defaultValue;
            }
        } catch (error) {
            this.showError('Failed to update price');
            input.value = input.defaultValue;
        }
    }

    async saveServices(formData) {
        try {
            const response = await fetch(this.endpoints.update, {
                method: 'POST',
                body: formData
            });

            const result = await response.json();
            if (result.success) {
                this.showSuccess('Services updated successfully');
                await this.loadServices();
            } else {
                this.showError(result.message);
            }
        } catch (error) {
            this.showError('Failed to save services');
        }
    }

    initializePriceCalculator() {
        const inputs = document.querySelectorAll('.service-price, .service-duration');
        inputs.forEach(input => {
            input.addEventListener('change', () => this.calculateTotalPrice());
        });
    }

    calculateTotalPrice() {
        const selectedServices = Array.from(document.querySelectorAll('input[type="checkbox"]:checked'))
            .map(checkbox => checkbox.closest('.service-card'))
            .filter(Boolean);

        const total = selectedServices.reduce((sum, card) => {
            const price = parseFloat(card.querySelector('.service-price').value) || 0;
            return sum + price;
        }, 0);

        document.getElementById('totalPrice').textContent = `₺${total.toFixed(2)}`;
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

// Initialize service selector
document.addEventListener('DOMContentLoaded', () => new ServiceSelector());
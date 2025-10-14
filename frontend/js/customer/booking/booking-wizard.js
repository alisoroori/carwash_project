class BookingWizard {
    constructor() {
        this.endpoints = {
            availability: '/carwash_project/backend/api/customer/booking/check-availability.php',
            services: '/carwash_project/backend/api/customer/booking/services.php',
            createBooking: '/carwash_project/backend/api/customer/booking/create.php'
        };
        this.currentStep = 1;
        this.totalSteps = 4;
        this.bookingData = {
            services: [],
            date: null,
            time: null,
            carwashId: null
        };
        this.init();
    }

    async init() {
        this.setupEventListeners();
        await this.loadInitialData();
        this.updateProgressBar();
    }

    setupEventListeners() {
        // Navigation buttons
        document.getElementById('nextStep')?.addEventListener('click', () => {
            this.nextStep();
        });

        document.getElementById('prevStep')?.addEventListener('click', () => {
            this.prevStep();
        });

        // Service selection
        document.getElementById('serviceList')?.addEventListener('change', (e) => {
            if (e.target.matches('input[type="checkbox"]')) {
                this.handleServiceSelection(e.target);
            }
        });

        // Date selection
        document.getElementById('bookingDate')?.addEventListener('change', (e) => {
            this.handleDateSelection(e.target.value);
        });

        // Time slot selection
        document.getElementById('timeSlots')?.addEventListener('click', (e) => {
            if (e.target.matches('.time-slot')) {
                this.handleTimeSelection(e.target);
            }
        });

        // Form submission
        document.getElementById('bookingForm')?.addEventListener('submit', (e) => {
            e.preventDefault();
            this.submitBooking();
        });
    }

    async loadInitialData() {
        try {
            const servicesResponse = await fetch(this.endpoints.services);
            const services = await servicesResponse.json();
            this.renderServices(services);
        } catch (error) {
            this.showError('Failed to load services');
        }
    }

    renderServices(services) {
        const container = document.getElementById('serviceList');
        if (!container) return;

        container.innerHTML = services.map(service => `
            <div class="service-item">
                <input type="checkbox" 
                       id="service-${service.id}" 
                       value="${service.id}"
                       data-price="${service.price}"
                       data-duration="${service.duration}">
                <label for="service-${service.id}">
                    <span class="service-name">${this.sanitizeHTML(service.name)}</span>
                    <span class="service-price">₺${service.price.toFixed(2)}</span>
                    <span class="service-duration">${service.duration} mins</span>
                </label>
                <p class="service-description">
                    ${this.sanitizeHTML(service.description)}
                </p>
            </div>
        `).join('');
    }

    async handleDateSelection(date) {
        this.bookingData.date = date;
        await this.loadTimeSlots(date);
    }

    async loadTimeSlots(date) {
        try {
            const params = new URLSearchParams({
                date,
                services: this.bookingData.services.join(',')
            });

            const response = await fetch(`${this.endpoints.availability}?${params}`);
            const slots = await response.json();
            this.renderTimeSlots(slots);
        } catch (error) {
            this.showError('Failed to load time slots');
        }
    }

    renderTimeSlots(slots) {
        const container = document.getElementById('timeSlots');
        if (!container) return;

        container.innerHTML = slots.map(slot => `
            <div class="time-slot ${slot.available ? 'available' : 'unavailable'}"
                 data-time="${slot.time}"
                 ${!slot.available ? 'disabled' : ''}>
                ${slot.time}
                ${slot.available ? `
                    <span class="slot-capacity">
                        ${slot.remaining} slots available
                    </span>
                ` : ''}
            </div>
        `).join('');
    }

    async submitBooking() {
        try {
            const response = await fetch(this.endpoints.createBooking, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(this.bookingData)
            });

            const result = await response.json();
            if (result.success) {
                this.showSuccess('Booking created successfully');
                window.location.href = `/carwash_project/frontend/customer/booking-confirmation.html?id=${result.bookingId}`;
            } else {
                this.showError(result.message);
            }
        } catch (error) {
            this.showError('Failed to create booking');
        }
    }

    updateProgressBar() {
        const progress = (this.currentStep / this.totalSteps) * 100;
        const progressBar = document.querySelector('.progress-bar');
        if (progressBar) {
            progressBar.style.width = `${progress}%`;
        }
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

// Initialize booking wizard
document.addEventListener('DOMContentLoaded', () => new BookingWizard());
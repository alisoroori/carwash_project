class BookingManager {
    constructor() {
        this.servicesList = document.getElementById('services-list');
        this.timeslotsList = document.getElementById('timeslots-list');
        this.bookingForm = document.getElementById('booking-form');
        
        this.init();
    }

    async init() {
        await this.loadServices();
        this.setupEventListeners();
    }

    async loadServices() {
        try {
            const resObj = await apiCall(`${API_CONFIG.BASE_URL}${API_CONFIG.ENDPOINTS.booking.services}`);
            const data = resObj.data;
            if (data && data.success) {
                this.renderServices(data.services);
            } else {
                throw new Error(data?.error || 'Failed to load services');
            }
        } catch (error) {
            console.error('Error loading services:', error);
            this.showError(error.message || 'Failed to load services. Please try again.');
        }
    }

    async loadTimeslots(serviceId, date) {
        try {
            const url = new URL(`${API_CONFIG.BASE_URL}${API_CONFIG.ENDPOINTS.booking.timeslots}`);
            url.searchParams.append('service_id', serviceId);
            url.searchParams.append('date', date);

            const resObj = await apiCall(url);
            const data = resObj.data;
            if (data && data.success) {
                this.renderTimeslots(data.timeslots);
            } else {
                throw new Error(data?.error || 'Failed to fetch timeslots');
            }
        } catch (error) {
            console.error('Error loading timeslots:', error);
            this.showError('Failed to load available times. Please try again.');
        }
    }

    async createBooking(bookingData) {
        try {
            const resObj = await apiCall(`${API_CONFIG.BASE_URL}${API_CONFIG.ENDPOINTS.booking.create}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(bookingData)
            });

            const data = resObj.data;
            if (data && data.success) {
                return data.booking_id;
            } else {
                throw new Error(data?.error || 'Failed to create booking');
            }
        } catch (error) {
            console.error('Error creating booking:', error);
            this.showError('Failed to create booking. Please try again.');
            return null;
        }
    }

    setupEventListeners() {
        // Service selection
        this.servicesList?.addEventListener('change', (e) => {
            if (e.target.matches('input[type="radio"]')) {
                const dateInput = document.getElementById('booking-date');
                if (dateInput.value) {
                    this.loadTimeslots(e.target.value, dateInput.value);
                }
            }
        });

        // Date selection
        document.getElementById('booking-date')?.addEventListener('change', (e) => {
            const selectedService = document.querySelector('input[name="service"]:checked');
            if (selectedService) {
                this.loadTimeslots(selectedService.value, e.target.value);
            }
        });

        // Form submission
        this.bookingForm?.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(e.target);
            const bookingData = {
                service_id: formData.get('service'),
                date: formData.get('date'),
                time: formData.get('time')
            };

            const bookingId = await this.createBooking(bookingData);
            if (bookingId) {
                window.location.href = `/carwash_project/frontend/booking-confirmation.html?id=${bookingId}`;
            }
        });
    }

    // UI Helper methods
    renderServices(services) {
        if (!this.servicesList) return;
        
        this.servicesList.innerHTML = services.map(service => `
            <div class="service-item">
                <input type="radio" name="service" id="service-${service.id}" value="${service.id}">
                <label for="service-${service.id}">
                    <h3>${service.name}</h3>
                    <p>${service.description}</p>
                    <span class="price">$${service.price}</span>
                    <span class="duration">${service.duration} mins</span>
                </label>
            </div>
        `).join('');
    }

    renderTimeslots(timeslots) {
        if (!this.timeslotsList) return;
        
        this.timeslotsList.innerHTML = timeslots.map(slot => `
            <div class="timeslot">
                <input type="radio" name="time" id="time-${slot.start}" value="${slot.start}">
                <label for="time-${slot.start}">
                    ${slot.start} - ${slot.end}
                </label>
            </div>
        `).join('');
    }

    showError(message) {
        const errorDiv = document.createElement('div');
        errorDiv.className = 'error-message';
        errorDiv.textContent = message;
        document.body.appendChild(errorDiv);
        setTimeout(() => errorDiv.remove(), 5000);
    }
}

// Initialize booking manager when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    new BookingManager();
});
// Booking functionality
class BookingManager {
    constructor(carwashId, userId) {
        this.carwashId = carwashId;
        this.userId = userId;
        this.selectedServices = new Set();
        this.selectedTime = null;
        this.selectedDate = null;
        document.getElementById('bookingModal').classList.remove('hidden');
        this.init();
    }

    async init() {
        const dateInput = document.querySelector('input[name="booking_date"]');
        dateInput.min = new Date().toISOString().split('T')[0];
        await this.loadServices();
        this.setupEventListeners();
        this.initializeWebSocket();
    }

    async loadServices() {
        try {
            const response = await fetch(`/carwash_project/backend/api/services/get_services.php?carwash_id=${this.carwashId}`);
            const data = await response.json();
            if (data.success) {
                this.renderServices(data.services);
            }
        } catch (error) {
            console.error('Error loading services:', error);
        }
    }
renderServices(services) {
    const container = document.getElementById('servicesList');
    container.innerHTML = services.map(service => `
        <div class="flex items-center justify-between p-4 border rounded hover:bg-gray-50">
            <div>
                <div class="font-semibold">${service.name}</div>
                <div class="text-sm text-gray-600">${service.description}</div>
                <div class="text-sm text-gray-600">Duration: ${service.duration} min</div>
            </div>
            <div class="flex items-center space-x-4">
                <div class="text-lg font-semibold">₺${service.price}</div>
                <button data-service-id="${service.id}" class="service-select px-4 py-2 rounded border hover:bg-blue-50">
                    Select
                </button>
            </div>
        </div>
    `).join('');
    // Add click handlers
    document.querySelectorAll('.service-select').forEach(button => {
        button.addEventListener('click', () => this.toggleService(button));
    });
}
    setupEventListeners() {
        document.getElementById('appointmentDate').addEventListener('change', (e) => {const formData = new FormData(this);
            this.selectedDate = e.target.value;
            this.loadTimeSlots();
    });
    document.getElementById('confirmBooking').addEventListener('click', () => {
        this.confirmBooking();
    });
}

async loadTimeSlots() {
    if (!this.selectedDate) return;

    try {
        const response = await fetch('/carwash_project/backend/api/booking/get_timeslots.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                carwashId: this.carwashId,
                date: this.selectedDate,
                services: Array.from(this.selectedServices)
            })
        });
        const data = await response.json();
        if (data.success) {
            this.renderTimeSlots(data.slots);
        }
    } catch (error) {
        console.error('Error loading time slots:', error);
    }
}
    initializeWebSocket() {
        this.ws = new WebSocket('ws://localhost:8082');
        // Add WebSocket event handlers here if needed
    }


    renderTimeSlots(slots) {
        const container = document.getElementById('timeSlotsList');
        container.innerHTML = slots.map(_slot => `
            <div class="flex items-center justify-between p-4 border rounded hover:bg-gray-50">
                <div>
                    <div class="font-semibold">Time: ${_slot.time}</div>
                    <div class="text-sm text-gray-600">Duration: ${_slot.duration} min</div>
                </div>
                <div class="flex items-center space-x-4">
                    <button data-slot-id="${_slot.id}" class="slot-select px-4 py-2 rounded border hover:bg-blue-50">
                        Select
                    </button>
                </div>
            </div>
        `).join('');
        // Add click handlers
        document.querySelectorAll('.slot-select').forEach(button => {
            button.addEventListener('click', () => this.selectTimeSlot(button));
        });
    }

    toggleService(button) {
        const serviceId = button.getAttribute('data-service-id');
        if (this.selectedServices.has(serviceId)) {
            this.selectedServices.delete(serviceId);
            button.classList.remove('bg-blue-200');
            button.textContent = 'Select';
        } else {
            this.selectedServices.add(serviceId);
            button.classList.add('bg-blue-200');
            button.textContent = 'Selected';
        }
        this.loadTimeSlots();
    }
}

var bookingManager = new BookingManager(carwashId, userId); // Initialize booking manager
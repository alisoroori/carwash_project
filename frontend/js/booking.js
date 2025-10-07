// Booking functionality
class BookingManager {
    constructor(carwashId, userId) {let selectedService = null;
        this.carwashId = carwashId;
        this.userId = userId;
        this.selectedServices = new Set();
        this.selectedTime = null;Type;
        this.selectedDate = null;
        document.getElementById('bookingModal').classList.remove('hidden');
        this.init();
    }
ooking_date"]');
    async init() {dateInput.min = new Date().toISOString().split('T')[0];
        await this.loadServices();
        this.setupEventListeners();
        this.initializeWebSocket();t.querySelector('select[name="booking_time"]');
    }   timeSelect.innerHTML = '';
}
    async loadServices() {
        try {
            const response = await fetch(`/carwash_project/backend/api/services/get_services.php?carwash_id=${this.carwashId}`);   document.getElementById('bookingModal').classList.add('hidden');
            const data = await response.json();}
            
            if (data.success) {
                this.renderServices(data.services);me="booking_date"]').addEventListener('change', async function(e) {
            }
        } catch (error) {ime"]');
            console.error('Error loading services:', error);    timeSelect.innerHTML = '<option value="">Yükleniyor...</option>';
        }
    }
ait fetch('../backend/api/get_available_times.php', {
    renderServices(services) {OST',
        const container = document.getElementById('servicesList');
        container.innerHTML = services.map(service => `  'Content-Type': 'application/x-www-form-urlencoded',
            <div class="flex items-center justify-between p-4 border rounded hover:bg-gray-50">
                <div> body: `date=${date}&service_type=${selectedService.type}`
                    <div class="font-semibold">${service.name}</div>});
                    <div class="text-sm text-gray-600">${service.description}</div>
                    <div class="text-sm text-gray-600">Duration: ${service.duration} min</div>const data = await response.json();
                </div>
                <div class="flex items-center space-x-4">
                    <div class="text-lg font-semibold">₺${service.price}</div>
                    <button data-service-id="${service.id}"n value="${time}">${time}</option>`
                            class="service-select px-4 py-2 rounded border hover:bg-blue-50">in('');
                        Select
                    </button>   timeSelect.innerHTML = '<option value="">Uygun saat bulunamadı</option>';
                </div>
            </div>
        `).join('');
   timeSelect.innerHTML = '<option value="">Hata oluştu</option>';
        // Add click handlers }
        document.querySelectorAll('.service-select').forEach(button => {});
            button.addEventListener('click', () => this.toggleService(button));
        });
    }('bookingForm').addEventListener('submit', async function(e) {
e.preventDefault();
    setupEventListeners() {
        document.getElementById('appointmentDate').addEventListener('change', (e) => {const formData = new FormData(this);
            this.selectedDate = e.target.value;
            this.loadTimeSlots();
        });ait fetch('../backend/api/create_booking.php', {
,
        document.getElementById('confirmBooking').addEventListener('click', () => { body: formData
            this.confirmBooking();});
        });
    }const data = await response.json();

    async loadTimeSlots() {s) {
        if (!this.selectedDate) return;

        try {
            const response = await fetch('/carwash_project/backend/api/booking/get_timeslots.php', {şturuldu. Onay emaili gönderildi.',
                method: 'POST', confirmButtonText: 'Tamam'
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({eBookingModal();
                    carwashId: this.carwashId,
                    date: this.selectedDate,
                    services: Array.from(this.selectedServices)
                })
            });vasyon oluşturulurken bir hata oluştu.',
 confirmButtonText: 'Tamam'
            const data = await response.json();   });
            if (data.success) {
                this.renderTimeSlots(data.slots);
            }or('Error creating booking:', error);
        } catch (error) {
            console.error('Error loading time slots:', error);
        }
    }ulurken bir hata oluştu.',
 confirmButtonText: 'Tamam'
    initializeWebSocket() {   });
        this.ws = new WebSocket('ws://localhost:8082'); }











































const bookingManager = new BookingManager(carwashId, userId);// Initialize booking manager}    }        }            alert('Booking failed. Please try again.');            console.error('Error creating booking:', error);        } catch (error) {            }                alert(data.error || 'Booking failed');            } else {                window.location.href = `/carwash_project/frontend/booking/confirmation.php?id=${data.bookingId}`;            if (data.success) {            const data = await response.json();            });                })                    services: Array.from(this.selectedServices)                    time: this.selectedTime,                    date: this.selectedDate,                    userId: this.userId,                    carwashId: this.carwashId,                body: JSON.stringify({                headers: { 'Content-Type': 'application/json' },                method: 'POST',            const response = await fetch('/carwash_project/backend/api/booking/create_booking.php', {        try {        }            return;            alert('Please select date, time and services');        if (!this.selectedDate || !this.selectedTime || this.selectedServices.size === 0) {    async confirmBooking() {    }        };            }                this.loadTimeSlots();            if (data.type === 'slot_update' && data.carwashId === this.carwashId) {            const data = JSON.parse(event.data);        this.ws.onmessage = (event) => {        });
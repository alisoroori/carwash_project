class AvailabilityCalendar {
    constructor() {
        this.endpoints = {
            slots: '/carwash_project/backend/api/carwash/availability/slots.php',
            update: '/carwash_project/backend/api/carwash/availability/update.php',
            bookings: '/carwash_project/backend/api/carwash/bookings/list.php'
        };
        this.calendar = null;
        this.currentView = 'timeGridWeek';
        this.init();
    }

    async init() {
        await this.loadFullCalendar();
        this.initializeCalendar();
        this.setupEventListeners();
    }

    async loadFullCalendar() {
        // Load FullCalendar library
        await Promise.all([
            this.loadScript('https://cdn.jsdelivr.net/npm/@fullcalendar/core/main.min.js'),
            this.loadScript('https://cdn.jsdelivr.net/npm/@fullcalendar/timegrid/main.min.js'),
            this.loadScript('https://cdn.jsdelivr.net/npm/@fullcalendar/interaction/main.min.js')
        ]);
    }

    initializeCalendar() {
        const calendarEl = document.getElementById('availabilityCalendar');
        if (!calendarEl) return;

        this.calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: this.currentView,
            slotMinTime: '08:00:00',
            slotMaxTime: '20:00:00',
            slotDuration: '00:30:00',
            selectable: true,
            editable: true,
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'timeGridWeek,timeGridDay'
            },
            select: info => this.handleTimeSlotSelect(info),
            eventClick: info => this.handleEventClick(info),
            eventDrop: info => this.handleEventDrop(info),
            eventResize: info => this.handleEventResize(info),
            events: this.loadEvents.bind(this)
        });

        this.calendar.render();
    }

    setupEventListeners() {
        // Bulk availability update
        document.getElementById('bulkUpdate')?.addEventListener('submit', e => {
            e.preventDefault();
            this.handleBulkUpdate(new FormData(e.target));
        });

        // Service duration change
        document.getElementById('serviceDuration')?.addEventListener('change', e => {
            this.updateSlotDuration(e.target.value);
        });
    }

    async loadEvents(info, successCallback, failureCallback) {
        try {
            const params = new URLSearchParams({
                start: info.startStr,
                end: info.endStr
            });

            const [slots, bookings] = await Promise.all([
                fetch(`${this.endpoints.slots}?${params}`).then(r => r.json()),
                fetch(`${this.endpoints.bookings}?${params}`).then(r => r.json())
            ]);

            const events = [
                ...this.formatAvailabilitySlots(slots),
                ...this.formatBookings(bookings)
            ];

            successCallback(events);
        } catch (error) {
            failureCallback(error);
            this.showError('Failed to load calendar events');
        }
    }

    formatAvailabilitySlots(slots) {
        return slots.map(slot => ({
            id: `slot-${slot.id}`,
            title: 'Available',
            start: slot.start_time,
            end: slot.end_time,
            backgroundColor: '#10B981',
            extendedProps: {
                type: 'availability',
                maxBookings: slot.max_bookings
            }
        }));
    }

    formatBookings(bookings) {
        return bookings.map(booking => ({
            id: `booking-${booking.id}`,
            title: `Booking: ${booking.service_name}`,
            start: booking.start_time,
            end: booking.end_time,
            backgroundColor: '#3B82F6',
            editable: false,
            extendedProps: {
                type: 'booking',
                customerId: booking.customer_id
            }
        }));
    }

    async handleTimeSlotSelect(info) {
        const response = await this.showSlotDialog({
            start: info.startStr,
            end: info.endStr
        });

        if (response) {
            try {
                const result = await this.saveAvailability(response);
                if (result.success) {
                    this.calendar.addEvent({
                        title: 'Available',
                        start: info.startStr,
                        end: info.endStr,
                        backgroundColor: '#10B981'
                    });
                    this.showSuccess('Availability updated');
                }
            } catch (error) {
                this.showError('Failed to update availability');
            }
        }
    }

    async saveAvailability(data) {
        const response = await fetch(this.endpoints.update, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        });
        return await response.json();
    }

    showSlotDialog(defaultData) {
        return new Promise(resolve => {
            const dialog = document.createElement('div');
            dialog.className = 'slot-dialog';
            dialog.innerHTML = `
                <div class="dialog-content">
                    <h3>Set Availability</h3>
                    <form id="slotForm">
                        <div class="form-group">
                            <label>Max Bookings:</label>
                            <input type="number" name="maxBookings" min="1" value="1">
                        </div>
                        <div class="form-actions">
                            <button type="submit">Save</button>
                            <button type="button" class="cancel">Cancel</button>
                        </div>
                    </form>
                </div>
            `;

            document.body.appendChild(dialog);

            dialog.querySelector('form').onsubmit = e => {
                e.preventDefault();
                const formData = new FormData(e.target);
                resolve({
                    ...defaultData,
                    maxBookings: formData.get('maxBookings')
                });
                dialog.remove();
            };

            dialog.querySelector('.cancel').onclick = () => {
                resolve(null);
                dialog.remove();
            };
        });
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

// Initialize availability calendar
document.addEventListener('DOMContentLoaded', () => new AvailabilityCalendar());
class EnhancedCalendar {
    constructor() {
        this.endpoints = {
            bookings: '/carwash_project/backend/api/carwash/bookings/list.php',
            availability: '/carwash_project/backend/api/carwash/availability/check.php',
            services: '/carwash_project/backend/api/carwash/services/list.php',
            weather: '/carwash_project/backend/api/carwash/weather/forecast.php'
        };
        this.calendar = null;
        this.weatherData = null;
        this.selectedDate = null;
        this.isDragging = false;
        this.dragStart = null;
        this.dragEnd = null;
        this.init();
    }

    async init() {
        await this.loadDependencies();
        this.initializeCalendar();
        this.setupEventListeners();
        await Promise.all([
            this.loadBookings(),
            this.loadWeatherData()
        ]);
    }

    async loadDependencies() {
        // Load FullCalendar and weather icons
        await Promise.all([
            this.loadScript('https://cdn.jsdelivr.net/npm/@fullcalendar/core/main.min.js'),
            this.loadScript('https://cdn.jsdelivr.net/npm/@fullcalendar/daygrid/main.min.js'),
            this.loadScript('https://cdn.jsdelivr.net/npm/@fullcalendar/timegrid/main.min.js'),
            this.loadScript('https://cdn.jsdelivr.net/npm/@fullcalendar/interaction/main.min.js'),
            this.loadScript('https://cdn.jsdelivr.net/npm/weather-icons/weather-icons.min.js')
        ]);
    }

    initializeCalendar() {
        const calendarEl = document.getElementById('enhancedCalendar');
        if (!calendarEl) return;

        this.calendar = new FullCalendar.Calendar(calendarEl, {
            initialView: 'timeGridWeek',
            slotMinTime: '08:00:00',
            slotMaxTime: '20:00:00',
            slotDuration: '00:30:00',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,timeGridDay'
            },
            views: {
                timeGridWeek: {
                    type: 'timeGrid',
                    duration: { days: 7 },
                    buttonText: 'Week'
                }
            },
            eventContent: this.renderEventContent.bind(this),
            dateClick: this.handleDateClick.bind(this),
            eventClick: this.handleEventClick.bind(this),
            eventDidMount: this.handleEventMount.bind(this)
        });

        this.calendar.render();
    }

    setupEventListeners() {
        // View toggles
        document.querySelectorAll('.calendar-view-toggle').forEach(button => {
            button.addEventListener('click', (e) => {
                this.calendar.changeView(e.target.dataset.view);
            });
        });

        // Service filter
        document.getElementById('serviceFilter')?.addEventListener('change', (e) => {
            this.filterBookingsByService(e.target.value);
        });

        // Weather overlay toggle
        document.getElementById('weatherOverlay')?.addEventListener('change', (e) => {
            this.toggleWeatherOverlay(e.target.checked);
        });

        const cells = document.querySelectorAll('[data-hour]');
        
        cells.forEach(cell => {
            cell.addEventListener('mousedown', (e) => this.handleDragStart(e));
            cell.addEventListener('mousemove', (e) => this.handleDragMove(e));
            cell.addEventListener('mouseup', (e) => this.handleDragEnd(e));
        });
    }

    handleDragStart(e) {
        this.isDragging = true;
        this.dragStart = {
            day: parseInt(e.target.dataset.day),
            hour: parseInt(e.target.dataset.hour)
        };
        e.target.classList.add('bg-blue-200');
    }

    handleDragMove(e) {
        if (!this.isDragging) return;
        
        const currentCell = {
            day: parseInt(e.target.dataset.day),
            hour: parseInt(e.target.dataset.hour)
        };

        this.highlightRange(this.dragStart, currentCell);
    }

    handleDragEnd(e) {
        if (!this.isDragging) return;
        
        this.isDragging = false;
        this.dragEnd = {
            day: parseInt(e.target.dataset.day),
            hour: parseInt(e.target.dataset.hour)
        };

        this.createSchedule(this.dragStart, this.dragEnd);
    }

    createSchedule(start, end) {
        const schedule = {
            day: start.day,
            start: `${start.hour}:00`,
            end: `${end.hour + 1}:00`,
            max: 1
        };

        this.dispatchEvent('scheduleCreated', schedule);
    }

    async loadBookings() {
        try {
            const response = await fetch(this.endpoints.bookings);
            const bookings = await response.json();
            
            this.calendar.removeAllEvents();
            bookings.forEach(booking => {
                this.calendar.addEvent({
                    id: booking.id,
                    title: `${booking.service_name} - ${booking.customer_name}`,
                    start: booking.start_time,
                    end: booking.end_time,
                    extendedProps: {
                        status: booking.status,
                        serviceType: booking.service_type,
                        customer: booking.customer_name,
                        phone: booking.customer_phone
                    }
                });
            });
        } catch (error) {
            this.showError('Failed to load bookings');
        }
    }

    async loadWeatherData() {
        try {
            const response = await fetch(this.endpoints.weather);
            this.weatherData = await response.json();
            this.updateWeatherOverlay();
        } catch (error) {
            this.showError('Failed to load weather data');
        }
    }

    renderEventContent(eventInfo) {
        const event = eventInfo.event;
        return {
            html: `
                <div class="calendar-event ${event.extendedProps.status}">
                    <div class="event-title">${event.title}</div>
                    <div class="event-time">
                        ${event.start.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}
                    </div>
                    ${this.getServiceIcon(event.extendedProps.serviceType)}
                </div>
            `
        };
    }

    getServiceIcon(serviceType) {
        const icons = {
            'basic-wash': '<i class="fas fa-car-wash"></i>',
            'premium-wash': '<i class="fas fa-star"></i>',
            'interior-clean': '<i class="fas fa-vacuum-robot"></i>'
        };
        return icons[serviceType] || '';
    }

    async handleDateClick(info) {
        const availabilityResponse = await this.checkAvailability(info.date);
        if (!availabilityResponse.available) {
            this.showError('Selected time slot is not available');
            return;
        }

        this.showBookingDialog(info.date);
    }

    showBookingDialog(date) {
        const dialog = document.createElement('div');
        dialog.className = 'booking-dialog';
        dialog.innerHTML = `
            <div class="dialog-content">
                <h3>New Booking</h3>
                <form id="bookingForm">
                    <div class="form-group">
                        <label>Service:</label>
                        <select name="service" required>
                            ${this.getServicesOptions()}
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Time:</label>
                        <input type="time" name="time" 
                               min="08:00" max="20:00" 
                               value="${date.toTimeString().slice(0, 5)}" required>
                    </div>
                    <div class="form-actions">
                        <button type="submit">Book</button>
                        <button type="button" class="cancel">Cancel</button>
                    </div>
                </form>
            </div>
        `;

        document.body.appendChild(dialog);
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

// Initialize enhanced calendar
document.addEventListener('DOMContentLoaded', () => new EnhancedCalendar());
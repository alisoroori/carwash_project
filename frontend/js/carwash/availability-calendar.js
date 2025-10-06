class AvailabilityCalendar {
    constructor(containerId) {
        this.container = document.getElementById(containerId);
        this.currentDate = new Date();
        this.schedules = [];
        this.init();
    }

    init() {
        this.render();
        this.attachEventListeners();
    }

    render() {
        const weekdays = ['Pazar', 'Pazartesi', 'Salı', 'Çarşamba', 'Perşembe', 'Cuma', 'Cumartesi'];
        const hours = Array.from({length: 24}, (_, i) => i);

        this.container.innerHTML = `
            <div class="grid grid-cols-8 gap-1">
                <div class="h-12"></div>
                ${weekdays.map(day => `
                    <div class="h-12 bg-gray-100 flex items-center justify-center font-semibold">
                        ${day}
                    </div>
                `).join('')}
                
                ${hours.map(hour => `
                    <div class="h-12 flex items-center justify-end pr-2 text-sm text-gray-500">
                        ${hour}:00
                    </div>
                    ${weekdays.map((_, dayIndex) => `
                        <div class="h-12 border border-gray-200 hover:bg-blue-50 cursor-pointer"
                             data-hour="${hour}" data-day="${dayIndex}"
                             id="cell-${dayIndex}-${hour}">
                        </div>
                    `).join('')}
                `).join('')}
            </div>
        `;
    }

    updateSchedule(schedules) {
        // Clear existing highlights
        document.querySelectorAll('.schedule-highlight').forEach(el => {
            el.classList.remove('schedule-highlight', 'bg-blue-200');
        });

        // Add new highlights
        schedules.forEach(schedule => {
            const startHour = parseInt(schedule.start_time.split(':')[0]);
            const endHour = parseInt(schedule.end_time.split(':')[0]);
            
            for (let hour = startHour; hour < endHour; hour++) {
                const cell = document.getElementById(`cell-${schedule.day_of_week}-${hour}`);
                if (cell) {
                    cell.classList.add('schedule-highlight', 'bg-blue-200');
                    cell.setAttribute('data-max-bookings', schedule.max_bookings);
                }
            }
        });
    }
}
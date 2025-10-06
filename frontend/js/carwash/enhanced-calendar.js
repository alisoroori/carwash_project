class EnhancedCalendar extends AvailabilityCalendar {
    constructor(containerId) {
        super(containerId);
        this.isDragging = false;
        this.dragStart = null;
        this.dragEnd = null;
    }

    init() {
        super.init();
        this.initializeDragHandlers();
    }

    initializeDragHandlers() {
        const cells = this.container.querySelectorAll('[data-hour]');
        
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
}
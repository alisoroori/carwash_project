class ScheduleHistoryManager {
    constructor(calendar) {
        this.calendar = calendar;
        this.history = [];
        this.currentIndex = -1;
        this.maxHistory = 20;
        
        this.setupKeyboardShortcuts();
    }

    addToHistory(schedules) {
        // Remove any future states if we're not at the end
        if (this.currentIndex < this.history.length - 1) {
            this.history = this.history.slice(0, this.currentIndex + 1);
        }

        // Add new state
        this.history.push(JSON.stringify(schedules));
        if (this.history.length > this.maxHistory) {
            this.history.shift();
        }
        this.currentIndex = this.history.length - 1;
    }

    undo() {
        if (this.currentIndex > 0) {
            this.currentIndex--;
            const schedules = JSON.parse(this.history[this.currentIndex]);
            this.calendar.updateSchedules(schedules, false);
            return true;
        }
        return false;
    }

    redo() {
        if (this.currentIndex < this.history.length - 1) {
            this.currentIndex++;
            const schedules = JSON.parse(this.history[this.currentIndex]);
            this.calendar.updateSchedules(schedules, false);
            return true;
        }
        return false;
    }

    setupKeyboardShortcuts() {
        document.addEventListener('keydown', (e) => {
            if (e.ctrlKey) {
                if (e.key === 'z') {
                    e.preventDefault();
                    this.undo();
                } else if (e.key === 'y') {
                    e.preventDefault();
                    this.redo();
                }
            }
        });
    }
}
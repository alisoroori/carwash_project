class ScheduleClipboard {
    constructor(calendar) {
        this.calendar = calendar;
        this.clipboard = null;
        this.init();
    }

    init() {
        this.addContextMenu();
        this.setupKeyboardShortcuts();
    }

    addContextMenu() {
        const menu = document.createElement('div');
        menu.className = 'hidden fixed bg-white shadow-lg rounded-lg z-50';
        menu.innerHTML = `
            <div class="p-2 space-y-1">
                <button class="w-full text-left px-3 py-1 hover:bg-gray-100 rounded" data-action="copy">
                    Kopyala (Ctrl+C)
                </button>
                <button class="w-full text-left px-3 py-1 hover:bg-gray-100 rounded" data-action="paste">
                    Yapıştır (Ctrl+V)
                </button>
            </div>
        `;

        document.body.appendChild(menu);
        this.setupContextMenuHandlers(menu);
    }

    setupContextMenuHandlers(menu) {
        this.calendar.container.addEventListener('contextmenu', (e) => {
            e.preventDefault();
            const cell = e.target.closest('[data-hour]');
            if (cell) {
                menu.style.left = `${e.pageX}px`;
                menu.style.top = `${e.pageY}px`;
                menu.classList.remove('hidden');
                menu.dataset.day = cell.dataset.day;
            }
        });

        menu.addEventListener('click', (e) => {
            const action = e.target.dataset.action;
            const day = parseInt(menu.dataset.day);
            
            if (action === 'copy') {
                this.copySchedules(day);
            } else if (action === 'paste') {
                this.pasteSchedules(day);
            }
            
            menu.classList.add('hidden');
        });
    }

    setupKeyboardShortcuts() {
        document.addEventListener('keydown', (e) => {
            if (e.ctrlKey) {
                if (e.key === 'c') {
                    this.copySchedules(this.getSelectedDay());
                } else if (e.key === 'v') {
                    this.pasteSchedules(this.getSelectedDay());
                }
            }
        });
    }

    async copySchedules(day) {
        const schedules = await this.calendar.getSchedulesForDay(day);
        this.clipboard = schedules;
        this.showToast('Programlar kopyalandı');
    }

    async pasteSchedules(targetDay) {
        if (!this.clipboard) return;
        
        const newSchedules = this.clipboard.map(schedule => ({
            ...schedule,
            day: targetDay
        }));

        await this.calendar.updateSchedules(newSchedules);
        this.showToast('Programlar yapıştırıldı');
    }
}
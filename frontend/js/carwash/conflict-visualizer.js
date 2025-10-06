class ConflictVisualizer {
    constructor(calendar) {
        this.calendar = calendar;
        this.conflicts = new Map();
    }

    showConflicts(conflicts) {
        this.clearConflicts();
        this.conflicts = new Map(Object.entries(conflicts));
        this.visualizeConflicts();
    }

    clearConflicts() {
        document.querySelectorAll('.conflict-highlight').forEach(el => {
            el.classList.remove('conflict-highlight', 'bg-red-200');
            el.removeAttribute('data-conflict');
        });
    }

    visualizeConflicts() {
        this.conflicts.forEach((serviceConflicts, serviceId) => {
            serviceConflicts.forEach(conflict => {
                const startHour = parseInt(conflict.start.split(':')[0]);
                const endHour = parseInt(conflict.end.split(':')[0]);
                
                for (let hour = startHour; hour < endHour; hour++) {
                    const cell = document.getElementById(`cell-${conflict.day}-${hour}`);
                    if (cell) {
                        cell.classList.add('conflict-highlight', 'bg-red-200');
                        
                        // Add conflict details for tooltip
                        const existing = cell.getAttribute('data-conflict') || '';
                        cell.setAttribute('data-conflict', 
                            `${existing}${existing ? '\n' : ''}Çakışma: ${conflict.service}`);
                    }
                }
            });
        });

        this.addConflictTooltips();
    }

    addConflictTooltips() {
        document.querySelectorAll('[data-conflict]').forEach(el => {
            el.addEventListener('mouseover', (e) => {
                const tooltip = document.createElement('div');
                tooltip.className = 'absolute bg-white p-2 rounded shadow-lg text-sm z-50';
                tooltip.textContent = e.target.getAttribute('data-conflict');
                
                const rect = e.target.getBoundingClientRect();
                tooltip.style.left = `${rect.left}px`;
                tooltip.style.top = `${rect.bottom + 5}px`;
                
                document.body.appendChild(tooltip);
                
                el.addEventListener('mouseout', () => tooltip.remove());
            });
        });
    }
}
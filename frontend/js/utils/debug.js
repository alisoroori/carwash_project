class DebugMode {
    static init() {
        if (localStorage.getItem('debug_mode') === 'true') {
            this.enable();
        }

        // Add debug toggle shortcut (Ctrl + Shift + D)
        document.addEventListener('keydown', (e) => {
            if (e.ctrlKey && e.shiftKey && e.key === 'D') {
                this.toggle();
            }
        });
    }

    static enable() {
        localStorage.setItem('debug_mode', 'true');
        document.body.classList.add('debug-mode');
        this.addDebugOverlay();
    }

    static disable() {
        localStorage.setItem('debug_mode', 'false');
        document.body.classList.remove('debug-mode');
        const overlay = document.getElementById('debug-overlay');
        if (overlay) overlay.remove();
    }

    static toggle() {
        if (localStorage.getItem('debug_mode') === 'true') {
            this.disable();
        } else {
            this.enable();
        }
    }

    static addDebugOverlay() {
        const overlay = document.createElement('div');
        overlay.id = 'debug-overlay';
        overlay.innerHTML = `
            <div class="debug-info">
                <h3>Debug Information</h3>
                <p>Screen Width: <span id="screen-width"></span></p>
                <p>Breakpoint: <span id="current-breakpoint"></span></p>
                <p>Loading Time: <span id="page-load"></span></p>
            </div>
        `;
        document.body.appendChild(overlay);
        this.updateDebugInfo();
    }

    static updateDebugInfo() {
        if (!document.getElementById('debug-overlay')) return;

        const updateInfo = () => {
            document.getElementById('screen-width').textContent = 
                `${window.innerWidth}px`;
            document.getElementById('current-breakpoint').textContent = 
                this.getCurrentBreakpoint();
        };

        window.addEventListener('resize', updateInfo);
        updateInfo();
    }

    static getCurrentBreakpoint() {
        const width = window.innerWidth;
        if (width < 768) return 'mobile';
        if (width < 1024) return 'tablet';
        return 'desktop';
    }
}
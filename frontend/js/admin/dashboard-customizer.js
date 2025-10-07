class DashboardCustomizer {
    constructor() {
        this.widgets = new Map();
        this.layout = this.loadLayout();
        this.init();
    }

    init() {
        this.setupDragAndDrop();
        this.registerWidgets();
        this.renderDashboard();
        this.setupEventListeners();
    }

    registerWidgets() {
        this.addWidget('revenue', {
            title: 'Revenue Overview',
            size: 'large',
            refresh: 300,
            render: this.renderRevenueWidget.bind(this)
        });

        this.addWidget('bookings', {
            title: 'Recent Bookings',
            size: 'medium',
            refresh: 60,
            render: this.renderBookingsWidget.bind(this)
        });

        // Add more widgets as needed
    }

    loadLayout() {
        const saved = localStorage.getItem('dashboardLayout');
        return saved ? JSON.parse(saved) : this.getDefaultLayout();
    }

    saveLayout() {
        localStorage.setItem('dashboardLayout', JSON.stringify(this.layout));
    }

    renderDashboard() {
        const container = document.getElementById('customDashboard');
        container.innerHTML = '';

        this.layout.forEach((widgetId) => {
            const widget = this.widgets.get(widgetId);
            if (widget) {
                const element = this.createWidgetElement(widgetId, widget);
                container.appendChild(element);
            }
        });
    }

    createWidgetElement(id, widget) {
        const div = document.createElement('div');
        div.className = `widget widget-${widget.size}`;
        div.dataset.widgetId = id;
        div.innerHTML = `
            <div class="widget-header">
                <h3>${widget.title}</h3>
                <div class="widget-actions">
                    <button class="refresh-btn">
                        <i class="fas fa-sync"></i>
                    </button>
                    <button class="settings-btn">
                        <i class="fas fa-cog"></i>
                    </button>
                </div>
            </div>
            <div class="widget-content"></div>
        `;

        widget.render(div.querySelector('.widget-content'));
        return div;
    }
}

// Initialize
const dashboardCustomizer = new DashboardCustomizer();
class RealTimeUpdater {
    constructor() {
        this.socket = null;
        this.retryAttempts = 0;
        this.maxRetries = 5;
        this.charts = new Map();
        this.init();
    }

    init() {
        this.connectWebSocket();
        this.setupChartListeners();
    }

    connectWebSocket() {
        this.socket = new WebSocket('ws://localhost:8080');
        
        this.socket.onopen = () => {
            console.log('WebSocket connected');
            this.retryAttempts = 0;
            this.subscribeToUpdates();
        };

        this.socket.onmessage = (event) => {
            const data = JSON.parse(event.data);
            this.handleUpdate(data);
        };

        this.socket.onclose = () => {
            if (this.retryAttempts < this.maxRetries) {
                setTimeout(() => {
                    this.retryAttempts++;
                    this.connectWebSocket();
                }, 5000);
            }
        };
    }

    handleUpdate(data) {
        if (data.type === 'chart_update' && this.charts.has(data.chartId)) {
            const chart = this.charts.get(data.chartId);
            chart.data = data.chartData;
            chart.update();
        }
    }
}
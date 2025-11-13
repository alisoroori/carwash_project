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
        // Feature-detect WebSocket
        if (typeof window === 'undefined' || typeof window.WebSocket === 'undefined') {
            console.warn('WebSocket not supported in this environment. Real-time updates disabled.');
            return;
        }

        const connect = () => {
            try {
                this.socket = new WebSocket('ws://localhost:8080');
            } catch (e) {
                console.error('WebSocket construction failed:', e);
                scheduleReconnect();
                return;
            }

            this.socket.onopen = () => {
                console.log('WebSocket connected');
                this.retryAttempts = 0;
                if (typeof this.subscribeToUpdates === 'function') this.subscribeToUpdates();
            };

            this.socket.onmessage = (event) => {
                try {
                    const data = JSON.parse(event.data);
                    this.handleUpdate(data);
                } catch (err) {
                    console.error('Failed to parse websocket message', err, event.data);
                }
            };

            this.socket.onerror = (err) => {
                console.error('WebSocket error', err);
                // let onclose handle reconnect
            };

            this.socket.onclose = (ev) => {
                console.warn('WebSocket closed', ev);
                scheduleReconnect();
            };
        };

        const scheduleReconnect = () => {
            if (this.retryAttempts >= this.maxRetries) {
                console.error('Max WebSocket reconnect attempts reached. Giving up.');
                return;
            }
            const base = 1000; // 1s
            const backoff = Math.min(30000, base * Math.pow(2, this.retryAttempts));
            const jitter = Math.floor(Math.random() * 1000);
            const delay = backoff + jitter;
            console.log(`Reconnecting WebSocket in ${delay}ms (attempt ${this.retryAttempts + 1})`);
            setTimeout(() => {
                this.retryAttempts++;
                connect();
            }, delay);
        };

        // start connection
        connect();
    }

    handleUpdate(data) {
        if (data.type === 'chart_update' && this.charts.has(data.chartId)) {
            const chart = this.charts.get(data.chartId);
            chart.data = data.chartData;
            chart.update();
        }
    }
}
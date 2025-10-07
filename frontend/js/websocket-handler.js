class WebSocketHandler {
    constructor() {
        this.socket = null;
        this.reconnectAttempts = 0;
        this.maxReconnectAttempts = 5;
        this.listeners = new Map();
        this.initialize();
    }

    initialize() {
        this.connect();
        this.setupHeartbeat();
    }

    connect() {
        this.socket = new WebSocket('ws://localhost:8080');

        this.socket.onopen = () => {
            console.log('Connected to WebSocket server');
            this.reconnectAttempts = 0;
            this.authenticate();
        };

        this.socket.onmessage = (event) => {
            const data = JSON.parse(event.data);
            this.handleMessage(data);
        };

        this.socket.onclose = () => {
            this.handleDisconnect();
        };
    }

    handleMessage(data) {
        if (this.listeners.has(data.type)) {
            this.listeners.get(data.type).forEach(callback => callback(data));
        }
    }
}
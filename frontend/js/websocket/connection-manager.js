class WebSocketManager {
    constructor() {
        this.socket = null;
        this.state = {
            connected: false,
            lastMessageId: null,
            pendingMessages: new Map(),
            reconnectAttempts: 0
        };
        this.MAX_RECONNECT_ATTEMPTS = 5;
        this.RECONNECT_INTERVAL = 3000;
        this.init();
    }

    init() {
        this.connect();
        this.setupHeartbeat();
    }

    connect() {
        try {
            this.socket = new WebSocket('ws://localhost:8080/carwash');
            this.setupEventHandlers();
        } catch (error) {
            this.handleConnectionError(error);
        }
    }

    setupEventHandlers() {
        this.socket.onopen = () => {
            this.state.connected = true;
            this.state.reconnectAttempts = 0;
            this.resendPendingMessages();
        };

        this.socket.onclose = () => {
            this.state.connected = false;
            this.attemptReconnection();
        };
    }

    resendPendingMessages() {
        this.state.pendingMessages.forEach((message, id) => {
            this.send(message, id);
        });
    }
}
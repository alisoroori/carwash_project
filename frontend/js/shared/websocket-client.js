class DashboardSocket {
    constructor(carwashId, role) {
        this.carwashId = carwashId;
        this.role = role;
        this.callbacks = {};
        this.connect();
    }

    connect() {
        this.ws = new WebSocket('ws://localhost:8080');
        
        this.ws.onopen = () => {
            this.subscribe();
        };

        this.ws.onmessage = (event) => {
            const data = JSON.parse(event.data);
            if (this.callbacks[data.type]) {
                this.callbacks[data.type](data.payload);
            }
        };

        this.ws.onclose = () => {
            setTimeout(() => this.connect(), 5000);
        };
    }

    subscribe() {
        this.ws.send(JSON.stringify({
            type: 'subscribe',
            carwashId: this.carwashId,
            role: this.role
        }));
    }

    on(event, callback) {
        this.callbacks[event] = callback;
    }

    emit(type, payload) {
        if (this.ws.readyState === WebSocket.OPEN) {
            this.ws.send(JSON.stringify({
                type,
                carwashId: this.carwashId,
                payload
            }));
        }
    }
}
class WebSocketEventHandler {
    constructor() {
        this.eventHandlers = new Map();
        this.initializeHandlers();
    }

    initializeHandlers() {
        this.registerHandler('location_update', this.handleLocationUpdate);
        this.registerHandler('booking_status', this.handleBookingStatus);
        this.registerHandler('zone_update', this.handleZoneUpdate);
    }

    registerHandler(eventType, handler) {
        this.eventHandlers.set(eventType, handler);
    }

    async handleEvent(message) {
        try {
            const data = JSON.parse(message.data);
            const handler = this.eventHandlers.get(data.type);
            
            if (handler) {
                await handler(data);
            } else {
                console.warn(`No handler for event type: ${data.type}`);
            }
        } catch (error) {
            console.error('Error handling WebSocket message:', error);
        }
    }

    handleLocationUpdate(data) {
        // Update provider location on map
        if (window.providerMap) {
            window.providerMap.updateMarkerPosition(
                data.provider_id,
                data.latitude,
                data.longitude
            );
        }
    }
}
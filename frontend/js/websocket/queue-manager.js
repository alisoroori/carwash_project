class WebSocketQueueManager {
    constructor() {
        this.messageQueue = new Map();
        this.retryConfig = {
            maxAttempts: 3,
            backoffMultiplier: 1.5,
            initialDelay: 1000
        };
    }

    async enqueueMessage(message) {
        const messageId = crypto.randomUUID();
        this.messageQueue.set(messageId, {
            payload: message,
            attempts: 0,
            status: 'pending',
            timestamp: Date.now()
        });

        return this.processMessage(messageId);
    }

    async processMessage(messageId) {
        const message = this.messageQueue.get(messageId);
        
        while (message.attempts < this.retryConfig.maxAttempts) {
            try {
                await this.sendMessage(message.payload);
                this.messageQueue.delete(messageId);
                return true;
            } catch (error) {
                message.attempts++;
                await this.handleRetry(message);
            }
        }

        this.handleFailedMessage(messageId);
        return false;
    }
}
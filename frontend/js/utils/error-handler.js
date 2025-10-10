class ErrorHandler {
    static show(message, type = 'error') {
        const container = document.getElementById('error-container') || 
            ErrorHandler.createContainer();

        const alert = document.createElement('div');
        alert.className = `alert alert-${type}`;
        alert.innerHTML = `
            <span class="message">${message}</span>
            <button class="close">&times;</button>
        `;

        container.appendChild(alert);

        // Auto-dismiss after 5 seconds
        setTimeout(() => alert.remove(), 5000);

        // Allow manual dismissal
        alert.querySelector('.close').addEventListener('click', () => 
            alert.remove());
    }

    static createContainer() {
        const container = document.createElement('div');
        container.id = 'error-container';
        document.body.appendChild(container);
        return container;
    }

    static handleApiError(error) {
        if (error.response) {
            // Server responded with error
            ErrorHandler.show(error.response.data.message || 
                'Server error occurred');
        } else if (error.request) {
            // No response received
            ErrorHandler.show('Network error - please check your connection');
        } else {
            // Request setup error
            ErrorHandler.show('An unexpected error occurred');
        }
    }
}
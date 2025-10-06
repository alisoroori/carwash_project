class ServiceSelector {
    constructor(containerId) {
        this.container = document.getElementById(containerId);
        this.selectedServices = new Set();
        this.init();
    }

    async init() {
        await this.loadServices();
        this.attachEventListeners();
    }

    async loadServices() {
        try {
            const response = await fetch('../../api/carwash/get_services.php');
            const data = await response.json();
            
            if (data.success) {
                this.renderServices(data.services);
            }
        } catch (error) {
            console.error('Error loading services:', error);
        }
    }

    renderServices(services) {
        this.container.innerHTML = services.map(service => `
            <div class="service-item" data-id="${service.id}">
                <label class="flex items-center space-x-2 p-2 border rounded-lg cursor-pointer hover:bg-gray-50">
                    <input type="checkbox" class="form-checkbox h-5 w-5 text-blue-600" value="${service.id}">
                    <span>${service.name}</span>
                </label>
            </div>
        `).join('');
    }

    getSelectedServices() {
        return Array.from(this.selectedServices);
    }
}
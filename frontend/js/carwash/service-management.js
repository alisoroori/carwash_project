class ServiceManager {
    constructor() {
        this.carwashId = document.querySelector('meta[name="carwash-id"]').content;
        this.init();
    }

    async init() {
        this.setupEventListeners();
        await this.loadServices();
    }

    setupEventListeners() {
        document.getElementById('addServiceBtn').addEventListener('click', () => {
            this.showModal();
        });

        document.getElementById('serviceForm').addEventListener('submit', (e) => {
            e.preventDefault();
            this.saveService();
        });
    }

    async loadServices() {
        try {
            const response = await fetch(`/carwash_project/backend/api/services/get_services.php?carwash_id=${this.carwashId}`);
            const data = await response.json();
            
            if (data.success) {
                this.renderServices(data.services);
            }
        } catch (error) {
            console.error('Error loading services:', error);
        }
    }

    renderServices(services) {
        // Group services by category
        const categories = {
            exterior: document.getElementById('exteriorServices'),
            interior: document.getElementById('interiorServices'),
            full: document.getElementById('fullServices'),
            special: document.getElementById('specialServices')
        };

        // Clear existing services
        Object.values(categories).forEach(container => container.innerHTML = '');

        // Render services by category
        services.forEach(service => {
            const container = categories[service.category];
            if (container) {
                container.innerHTML += this.createServiceCard(service);
            }
        });
    }

    createServiceCard(service) {
        return `
            <div class="bg-gray-50 p-4 rounded-lg">
                <div class="flex justify-between items-start">
                    <div>
                        <h4 class="font-semibold">${service.name}</h4>
                        <p class="text-sm text-gray-600">${service.description}</p>
                        <div class="mt-2 text-sm">
                            <span class="text-blue-600 font-semibold">₺${service.price}</span>
                            <span class="text-gray-500">· ${service.duration} min</span>
                        </div>
                    </div>
                    <div class="space-x-2">
                        <button onclick="serviceManager.editService(${service.id})"
                                class="text-blue-600 hover:text-blue-800">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button onclick="serviceManager.toggleService(${service.id})"
                                class="text-${service.status === 'active' ? 'red' : 'green'}-600">
                            <i class="fas fa-${service.status === 'active' ? 'times' : 'check'}"></i>
                        </button>
                    </div>
                </div>
            </div>
        `;
    }

    async saveService() {
        const form = document.getElementById('serviceForm');
        const formData = new FormData(form);
        formData.append('carwash_id', this.carwashId);

        try {
            const response = await fetch('/carwash_project/backend/api/services/save_service.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();
            if (data.success) {
                this.closeModal();
                await this.loadServices();
            }
        } catch (error) {
            console.error('Error saving service:', error);
        }
    }
}

// Initialize service manager
const serviceManager = new ServiceManager();
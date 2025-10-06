class ServiceManager {
    constructor() {
        this.loadServices();
        this.setupEventListeners();
    }

    setupEventListeners() {
        const form = document.getElementById('serviceForm');
        form.addEventListener('submit', (e) => {
            e.preventDefault();
            this.saveService(new FormData(form));
        });
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
        const grid = document.getElementById('servicesGrid');
        grid.innerHTML = services.map(service => `
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex justify-between items-start mb-4">
                    <h3 class="text-lg font-semibold">${service.name}</h3>
                    <div class="flex space-x-2">
                        <button onclick="serviceManager.editService(${service.id})"
                                class="text-blue-600 hover:text-blue-800">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button onclick="serviceManager.deleteService(${service.id})"
                                class="text-red-600 hover:text-red-800">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
                <p class="text-gray-600 mb-4">${service.description || 'Açıklama yok'}</p>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-500">
                        <i class="fas fa-clock"></i> ${service.duration} dakika
                    </span>
                    <span class="font-semibold">${service.price} TL</span>
                </div>
            </div>
        `).join('');
    }

    async saveService(formData) {
        try {
            const serviceId = formData.get('serviceId');
            const url = serviceId ? 
                '../../api/carwash/update_service.php' : 
                '../../api/carwash/add_service.php';

            const response = await fetch(url, {
                method: 'POST',
                body: formData
            });

            const data = await response.json();
            
            if (data.success) {
                this.loadServices();
                this.closeServiceModal();
            } else {
                alert(data.error || 'İşlem başarısız');
            }
        } catch (error) {
            console.error('Error saving service:', error);
            alert('Sistem hatası');
        }
    }

    async deleteService(serviceId) {
        if (!confirm('Bu hizmet paketini silmek istediğinizden emin misiniz?')) {
            return;
        }

        try {
            const response = await fetch('../../api/carwash/delete_service.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ serviceId })
            });

            const data = await response.json();
            
            if (data.success) {
                this.loadServices();
            } else {
                alert(data.error || 'Silme işlemi başarısız');
            }
        } catch (error) {
            console.error('Error deleting service:', error);
            alert('Sistem hatası');
        }
    }

    editService(serviceId) {
        const service = document.querySelector(`[data-service-id="${serviceId}"]`);
        document.getElementById('serviceId').value = serviceId;
        document.getElementById('serviceName').value = service.dataset.name;
        document.getElementById('serviceDescription').value = service.dataset.description;
        document.getElementById('servicePrice').value = service.dataset.price;
        document.getElementById('serviceDuration').value = service.dataset.duration;
        
        document.getElementById('modalTitle').textContent = 'Hizmet Paketini Düzenle';
        this.showServiceModal();
    }

    showServiceModal() {
        document.getElementById('serviceModal').classList.remove('hidden');
    }

    closeServiceModal() {
        document.getElementById('serviceModal').classList.add('hidden');
        document.getElementById('serviceForm').reset();
        document.getElementById('serviceId').value = '';
        document.getElementById('modalTitle').textContent = 'Yeni Hizmet Paketi';
    }
}

// Initialize
const serviceManager = new ServiceManager();
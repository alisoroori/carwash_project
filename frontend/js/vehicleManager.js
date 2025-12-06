// Shared vehicleManager factory for Alpine components
// Reads CSRF token from meta tag or global CONFIG if available.
function createVehicleManagerFactory() {
    const metaToken = document.querySelector('meta[name="csrf-token"]')?.content;
    const csrfToken = metaToken || (window.CONFIG && window.CONFIG.CSRF_TOKEN) || '';

    return {
        vehicles: [],
        showVehicleForm: false,
        editingVehicle: null,
        loading: false,
        message: '',
        messageType: '',
        imagePreview: '',
        csrfToken: csrfToken,
        formData: {
            brand: '',
            model: '',
            license_plate: '',
            year: '',
            color: '',
            vehicle_type: '',
            notes: ''
        },

        init() {
            // Called by Alpine when component is initialized
            if (typeof console !== 'undefined') console.log('vehicleManager factory loaded');
            this.loadVehicles();
        },

        async loadVehicles() {
            try {
                const resObj = await apiCall('/carwash_project/backend/api/get_vehicles.php', {
                    method: 'GET',
                    credentials: 'same-origin',
                    headers: { 'Accept': 'application/json' }
                });
                const data = resObj.data;
                this.vehicles = data?.vehicles || data?.data?.vehicles || [];
                const statEl = document.getElementById('vehicleStatCount');
                if (statEl) statEl.textContent = this.vehicles.length;
            } catch (err) {
                console.error('Load vehicles error:', err);
                this.vehicles = [];
                this.showMessage(err.message || 'Araçlar yüklenemedi', 'error');
            }
        },

        openVehicleForm(vehicle = null) {
            this.editingVehicle = vehicle;
            if (vehicle) {
                this.formData = {
                    brand: vehicle.brand || '',
                    model: vehicle.model || '',
                    license_plate: vehicle.license_plate || '',
                    year: vehicle.year || '',
                    color: vehicle.color || '',
                    vehicle_type: vehicle.vehicle_type || '',
                    notes: vehicle.notes || ''
                };
                this.imagePreview = vehicle.image_path || '';
            } else {
                this.resetForm();
            }

            this.showVehicleForm = true;
            document.body.classList.add('menu-open');
        },

        closeVehicleForm() {
            this.showVehicleForm = false;
            this.resetForm();
            document.body.classList.remove('menu-open');
        },

        resetForm() {
            this.editingVehicle = null;
            this.formData = { brand: '', model: '', license_plate: '', year: '', color: '', vehicle_type: '', notes: '' };
            this.imagePreview = '';
            this.message = '';
            this.messageType = '';
        },

        async saveVehicle() {
            this.loading = true;
            this.message = '';
            try {
                const form = document.querySelector('section[x-data="vehicleManager()"] form') || document.getElementById('vehicleForm') || document.querySelector('form');
                if (!form) throw new Error('Form not found');

                const fd = new FormData(form);
                // Ensure csrf present
                if (!fd.has('csrf_token') && this.csrfToken) fd.append('csrf_token', this.csrfToken);

                const isEdit = !!this.editingVehicle;
                const apiUrl = isEdit ? '/carwash_project/backend/api/update_vehicle.php' : '/carwash_project/backend/api/add_vehicle.php';
                if (isEdit) fd.append('vehicle_id', this.editingVehicle.id);

                const resObj = await apiCall(apiUrl, {
                    method: 'POST',
                    credentials: 'same-origin',
                    body: fd
                });

                const data = resObj.data;
                if (data?.success || data?.status === 'success') {
                    this.showMessage(isEdit ? 'Araç güncellendi' : 'Araç eklendi', 'success');
                    
                    // Optimized: Update locally instead of full reload
                    const returnedVehicle = data?.vehicle || data?.data?.vehicle;
                    if (returnedVehicle) {
                        if (isEdit) {
                            // Update existing vehicle in array
                            const idx = this.vehicles.findIndex(v => v.id === this.editingVehicle.id);
                            if (idx !== -1) {
                                this.vehicles[idx] = returnedVehicle;
                            } else {
                                this.vehicles.push(returnedVehicle);
                            }
                        } else {
                            // Add new vehicle to array
                            this.vehicles.push(returnedVehicle);
                        }
                        // Update stat count
                        requestAnimationFrame(() => {
                            const statEl = document.getElementById('vehicleStatCount');
                            if (statEl) statEl.textContent = this.vehicles.length;
                        });
                    } else {
                        // Fallback: reload if no vehicle returned
                        await this.loadVehicles();
                    }
                    
                    setTimeout(() => this.closeVehicleForm(), 1500);
                } else {
                    throw new Error(data?.message || 'İşlem başarısız');
                }
            } catch (err) {
                console.error('Save vehicle error:', err);
                this.showMessage(err.message || 'Kaydetme işlemi başarısız', 'error');
            } finally {
                this.loading = false;
            }
        },

        editVehicle(vehicle) { this.openVehicleForm(vehicle); },

        async deleteVehicle(id) {
            const proceed = (window.showConfirm) ? await window.showConfirm('Bu aracı silmek istediğinizden emin misiniz?') : confirm('Bu aracı silmek istediğinizden emin misiniz?');
            if (!proceed) return;
            this.loading = true;
            
            // Store the index for optimistic rollback if needed
            const vehicleIndex = this.vehicles.findIndex(v => v.id === id);
            const deletedVehicle = vehicleIndex !== -1 ? {...this.vehicles[vehicleIndex]} : null;
            
            // Optimistically remove from UI immediately (better UX)
            if (vehicleIndex !== -1) {
                this.vehicles.splice(vehicleIndex, 1);
                // Update stat count immediately
                requestAnimationFrame(() => {
                    const statEl = document.getElementById('vehicleStatCount');
                    if (statEl) statEl.textContent = this.vehicles.length;
                });
            }
            
            try {
                const fd = new FormData();
                fd.append('vehicle_id', id);
                if (this.csrfToken) fd.append('csrf_token', this.csrfToken);

                const resObj = await apiCall('/carwash_project/backend/api/delete_vehicle.php', {
                    method: 'POST',
                    credentials: 'same-origin',
                    body: fd
                });
                const data = resObj.data;
                if (data?.success || data?.status === 'success') {
                    this.showMessage('Araç başarıyla silindi', 'success');
                    // No need to reload all vehicles - already removed optimistically
                } else {
                    throw new Error(data?.message || 'Silme işlemi başarısız');
                }
            } catch (err) {
                console.error('Delete vehicle error:', err);
                // Rollback: restore the deleted vehicle on error
                if (deletedVehicle && vehicleIndex !== -1) {
                    this.vehicles.splice(vehicleIndex, 0, deletedVehicle);
                    requestAnimationFrame(() => {
                        const statEl = document.getElementById('vehicleStatCount');
                        if (statEl) statEl.textContent = this.vehicles.length;
                    });
                }
                this.showMessage(err.message || 'Silme işlemi başarısız', 'error');
            } finally {
                this.loading = false;
            }
        },

        previewImage(event) {
            const file = event.target.files ? event.target.files[0] : null;
            if (file && file.type && file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = (e) => { this.imagePreview = e.target.result; };
                reader.onerror = () => { this.showMessage('Resim yüklenirken hata oluştu', 'error'); };
                reader.readAsDataURL(file);
            } else if (file) {
                this.showMessage('Lütfen geçerli bir resim dosyası seçin', 'error');
            }
        },

        showMessage(msg, type = 'success') {
            this.message = msg;
            this.messageType = type;
            setTimeout(() => { this.message = ''; this.messageType = ''; }, 5000);
        }
    };
}

// Register with Alpine when it's available and also expose a window factory for backward compatibility
// If Alpine is already present, register immediately. Otherwise listen for alpine:init.
if (typeof Alpine !== 'undefined' && Alpine.data) {
    try {
        Alpine.data('vehicleManager', function() {
            return createVehicleManagerFactory();
        });
    } catch (e) {
        console.error('vehicleManager: immediate Alpine.data registration failed', e);
    }
} else {
    document.addEventListener('alpine:init', function() {
        if (typeof Alpine !== 'undefined' && Alpine.data) {
            Alpine.data('vehicleManager', function() {
                return createVehicleManagerFactory();
            });
        }
    });
}

// Backward-compatible factory for x-data="vehicleManager()" usage
window.vehicleManager = function() {
    return createVehicleManagerFactory();
};


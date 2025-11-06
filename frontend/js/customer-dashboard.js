/**
 * Customer Dashboard JavaScript
 * مدیریت تعاملات داشبورد مشتری
 */

(function() {
    'use strict';

    // ================================================
    // MOBILE SIDEBAR FUNCTIONS
    // ================================================

    window.toggleMobileSidebar = function() {
        const sidebar = document.getElementById('mobileSidebar');
        const overlay = document.getElementById('mobileOverlay');
        const menuBtn = document.getElementById('mobileMenuBtn');
        const menuIcon = document.getElementById('menuIcon');

        if (sidebar.classList.contains('active')) {
            closeMobileSidebar();
        } else {
            sidebar.classList.add('active');
            overlay.classList.add('active');
            menuBtn.classList.add('active');
            menuIcon.className = 'fas fa-times';
            document.body.style.overflow = 'hidden';
        }
    };

    window.closeMobileSidebar = function() {
        const sidebar = document.getElementById('mobileSidebar');
        const overlay = document.getElementById('mobileOverlay');
        const menuBtn = document.getElementById('mobileMenuBtn');
        const menuIcon = document.getElementById('menuIcon');

        sidebar.classList.remove('active');
        overlay.classList.remove('active');
        menuBtn.classList.remove('active');
        menuIcon.className = 'fas fa-bars';
        document.body.style.overflow = '';
    };

    // ================================================
    // SECTION NAVIGATION
    // ================================================

    window.showSection = function(sectionId) {
        console.log('Showing section:', sectionId);

        // Hide all sections
        document.querySelectorAll('.section-content').forEach(section => {
            section.classList.remove('active');
        });

        // Remove active class from all nav links
        document.querySelectorAll('.nav-link').forEach(link => {
            link.classList.remove('active');
        });

        // Show selected section
        const targetSection = document.getElementById(sectionId);
        if (targetSection) {
            targetSection.classList.add('active');
            targetSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }

        // Add active class to clicked link
        const activeLink = document.querySelector(`.nav-link[href="#${sectionId}"]`);
        if (activeLink) {
            activeLink.classList.add('active');
        }

        // Section-specific actions
        if (sectionId === 'vehicles') {
            console.log('Vehicles section activated, loading vehicles...');
            if (typeof loadUserVehicles === 'function') {
                setTimeout(loadUserVehicles, 50);
            }
        }

        // Close mobile sidebar after selection
        if (window.innerWidth < 1024) {
            closeMobileSidebar();
        }
    };

    // ================================================
    // VEHICLE MANAGEMENT
    // ================================================

    window.loadUserVehicles = async function() {
        const container = document.getElementById('vehiclesList');
        const countEl = document.getElementById('vehicleCount');

        if (!container) {
            console.warn('Vehicle list container not found');
            return;
        }

        // Show loading state
        container.innerHTML = '<div style="grid-column: 1/-1; text-align:center; padding:3rem;"><i class="fas fa-spinner fa-spin" style="font-size:2rem;color:#667eea;"></i><p style="margin-top:1rem;color:#6b7280;">Araçlar yükleniyor...</p></div>';

        try {
            const response = await fetch('/carwash_project/backend/dashboard/vehicle_api.php?action=list', {
                method: 'GET',
                credentials: 'same-origin',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }

            const contentType = response.headers.get('content-type');
            let json = null;

            if (contentType && contentType.includes('application/json')) {
                json = await response.json();
            } else {
                const text = await response.text();
                console.warn('Non-JSON response received:', text.substring(0, 200));
                try {
                    json = JSON.parse(text);
                } catch (e) {
                    throw new Error('Sunucudan geçersiz yanıt alındı');
                }
            }

            // Normalize response
            let vehicles = [];
            if (Array.isArray(json)) {
                vehicles = json;
            } else if (json && json.success !== false) {
                if (Array.isArray(json.vehicles)) {
                    vehicles = json.vehicles;
                } else if (json.data && Array.isArray(json.data.vehicles)) {
                    vehicles = json.data.vehicles;
                } else if (json.data && Array.isArray(json.data)) {
                    vehicles = json.data;
                }
            }

            console.log('Loaded vehicles:', vehicles.length, vehicles);

            // Update count
            if (countEl) {
                countEl.textContent = vehicles.length === 0 ? 'Kayıtlı araç yok' : `${vehicles.length} kayıtlı araç`;
            }

            // Render vehicles
            if (vehicles.length === 0) {
                container.innerHTML = `
                    <div style="grid-column: 1/-1; text-align:center; padding:3rem;">
                        <i class="fas fa-car" style="font-size:4rem;color:#d1d5db;margin-bottom:1rem;"></i>
                        <p style="color:#6b7280;font-size:1.125rem;margin-bottom:1rem;">Henüz kayıtlı araç yok</p>
                        <button onclick="openVehicleModal()" class="btn-primary">
                            <i class="fas fa-plus"></i>
                            <span>İlk Aracınızı Ekleyin</span>
                        </button>
                    </div>
                `;
            } else {
                container.innerHTML = vehicles.map(vehicle => {
                    const imgSrc = resolveVehicleImageUrl(vehicle.image_path);
                    const brand = escapeHtml(vehicle.brand || '');
                    const model = escapeHtml(vehicle.model || '');
                    const plate = escapeHtml(vehicle.license_plate || '');
                    const year = escapeHtml(vehicle.year || '');
                    const color = escapeHtml(vehicle.color || '');

                    return `
                        <div class="card" data-vehicle-id="${vehicle.id || ''}">
                            <div style="display:flex;gap:1rem;margin-bottom:1rem;">
                                <div style="width:80px;height:80px;flex-shrink:0;border-radius:12px;overflow:hidden;background:#f3f4f6;">
                                    <img src="${imgSrc}" alt="${brand} ${model}" 
                                         style="width:100%;height:100%;object-fit:cover;" 
                                         onerror="this.src='/carwash_project/frontend/assets/images/default-car.png';">
                                </div>
                                <div style="flex:1;min-width:0;">
                                    <h4 style="font-weight:700;font-size:1.125rem;color:#1f2937;margin-bottom:0.5rem;">${brand} ${model}</h4>
                                    <p style="font-size:0.875rem;color:#6b7280;">
                                        ${plate ? `<span style="margin-right:0.75rem;"><i class="fas fa-id-card" style="margin-right:0.25rem;"></i>${plate}</span>` : ''}
                                        ${year ? `<span style="margin-right:0.75rem;"><i class="fas fa-calendar" style="margin-right:0.25rem;"></i>${year}</span>` : ''}
                                        ${color ? `<span><i class="fas fa-palette" style="margin-right:0.25rem;"></i>${color}</span>` : ''}
                                    </p>
                                </div>
                            </div>
                            <div style="display:flex;gap:0.5rem;padding-top:1rem;border-top:1px solid #e5e7eb;">
                                <button class="btn-secondary" style="flex:1;" onclick='editVehicle(${JSON.stringify(vehicle).replace(/'/g, "\\'")})'">
                                    <i class="fas fa-edit"></i>
                                    <span>Düzenle</span>
                                </button>
                                <button class="btn-secondary" style="flex:1;color:#ef4444;border-color:#ef4444;" onclick="deleteVehicle(${vehicle.id || 0})">
                                    <i class="fas fa-trash"></i>
                                    <span>Sil</span>
                                </button>
                            </div>
                        </div>
                    `;
                }).join('');
            }

        } catch (error) {
            console.error('Error loading vehicles:', error);
            container.innerHTML = `
                <div style="grid-column: 1/-1; text-align:center; padding:3rem;">
                    <i class="fas fa-exclamation-triangle" style="font-size:4rem;color:#ef4444;margin-bottom:1rem;"></i>
                    <p style="color:#ef4444;font-size:1.125rem;margin-bottom:1rem;">Araçlar yüklenirken hata oluştu</p>
                    <p style="color:#6b7280;font-size:0.875rem;margin-bottom:1.5rem;">${error.message}</p>
                    <button onclick="loadUserVehicles()" class="btn-primary">
                        <i class="fas fa-redo"></i>
                        <span>Tekrar Dene</span>
                    </button>
                </div>
            `;
        }
    };

    // Resolve vehicle image URL
    function resolveVehicleImageUrl(imagePath) {
        if (!imagePath) {
            return '/carwash_project/frontend/assets/images/default-car.png';
        }
        if (imagePath.startsWith('http') || imagePath.startsWith('/')) {
            return imagePath;
        }
        return `/carwash_project/${imagePath}`;
    }

    // Escape HTML
    function escapeHtml(text) {
        if (text == null) return '';
        const div = document.createElement('div');
        div.textContent = String(text);
        return div.innerHTML;
    }

    // Open vehicle modal
    window.openVehicleModal = function(vehicle = null) {
        const formPanel = document.getElementById('vehicleInlineSection');
        const formTitle = document.getElementById('vehicleInlineTitle');
        const formAction = document.getElementById('vehicleFormAction');
        const form = document.getElementById('vehicleFormInline');
        const previewImg = document.getElementById('vehicleImagePreview');

        if (vehicle && typeof vehicle === 'object') {
            formTitle.textContent = 'Araç Düzenle';
            formAction.value = 'update';
            document.getElementById('vehicle_id_input_inline').value = vehicle.id || '';
            document.getElementById('car_brand_inline').value = vehicle.brand || '';
            document.getElementById('car_model_inline').value = vehicle.model || '';
            document.getElementById('license_plate_inline').value = vehicle.license_plate || '';
            document.getElementById('car_year_inline').value = vehicle.year || '';
            document.getElementById('car_color_inline').value = vehicle.color || '';

            if (vehicle.image_path) {
                previewImg.src = resolveVehicleImageUrl(vehicle.image_path);
            }
        } else {
            formTitle.textContent = 'Yeni Araç Ekle';
            formAction.value = 'create';
            form.reset();
            previewImg.src = '/carwash_project/frontend/assets/images/default-car.png';
        }

        if (formPanel) {
            formPanel.style.display = 'block';
            formPanel.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    };

    window.editVehicle = window.openVehicleModal;

    // Close vehicle form
    window.closeVehicleForm = function() {
        const formPanel = document.getElementById('vehicleInlineSection');
        const form = document.getElementById('vehicleFormInline');
        const previewImg = document.getElementById('vehicleImagePreview');
        const msgEl = document.getElementById('vehicleFormMessageInline');

        if (formPanel) formPanel.style.display = 'none';
        if (form) form.reset();
        if (previewImg) previewImg.src = '/carwash_project/frontend/assets/images/default-car.png';
        if (msgEl) {
            msgEl.textContent = '';
            msgEl.className = '';
        }
    };

    // Delete vehicle
    window.deleteVehicle = async function(vehicleId) {
        if (!vehicleId) {
            console.error('No vehicle ID provided');
            return;
        }

        if (!confirm('Bu aracı silmek istediğinizden emin misiniz? Bu işlem geri alınamaz.')) {
            return;
        }

        try {
            const card = document.querySelector(`[data-vehicle-id="${vehicleId}"]`);
            const btn = card ? card.querySelector('[onclick*="deleteVehicle"]') : null;
            if (btn) btn.disabled = true;

            const csrfToken = document.getElementById('csrf_token_vehicle')?.value || 
                             document.querySelector('meta[name="csrf-token"]')?.content || '';

            const formData = new FormData();
            formData.append('action', 'delete');
            formData.append('id', vehicleId);
            if (csrfToken) formData.append('csrf_token', csrfToken);

            const response = await fetch('/carwash_project/backend/dashboard/vehicle_api.php', {
                method: 'POST',
                credentials: 'same-origin',
                body: formData
            });

            const contentType = response.headers.get('content-type');
            let result;

            if (contentType && contentType.includes('application/json')) {
                result = await response.json();
            } else {
                const text = await response.text();
                try {
                    result = JSON.parse(text);
                } catch (e) {
                    throw new Error('Geçersiz sunucu yanıtı');
                }
            }

            const isSuccess = result.success === true || 
                             result.status === 'success' || 
                             (response.ok && !result.error);

            if (isSuccess) {
                if (card) {
                    card.style.opacity = '0';
                    setTimeout(() => card.remove(), 300);
                }
                setTimeout(loadUserVehicles, 500);
            } else {
                const errorMsg = result.message || result.error || 'Silme işlemi başarısız';
                alert(errorMsg);
                if (btn) btn.disabled = false;
            }
        } catch (error) {
            console.error('Delete vehicle error:', error);
            alert('Bir hata oluştu: ' + error.message);
        }
    };

    // ================================================
    // VEHICLE FORM SUBMISSION
    // ================================================

    (function initializeVehicleForm() {
        const vehicleForm = document.getElementById('vehicleFormInline');
        const imageInput = document.getElementById('vehicle_image_inline');
        const previewImg = document.getElementById('vehicleImagePreview');
        const msgEl = document.getElementById('vehicleFormMessageInline');
        const submitBtn = document.getElementById('vehicleInlineSubmit');

        if (!vehicleForm) return;

        // Image preview
        if (imageInput && previewImg) {
            imageInput.addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (file && file.type.startsWith('image/')) {
                    const reader = new FileReader();
                    reader.onload = function(ev) {
                        previewImg.src = ev.target.result;
                    };
                    reader.readAsDataURL(file);
                }
            });
        }

        // Form submission
        vehicleForm.addEventListener('submit', async function(e) {
            e.preventDefault();

            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i><span>Kaydediliyor...</span>';
            }

            if (msgEl) {
                msgEl.textContent = '';
                msgEl.className = '';
            }

            try {
                const action = document.getElementById('vehicleFormAction')?.value || 'create';
                const formData = new FormData(vehicleForm);

                formData.set('action', action);

                const csrfToken = document.getElementById('csrf_token_vehicle')?.value || '';
                if (csrfToken) formData.set('csrf_token', csrfToken);

                const vehicleId = document.getElementById('vehicle_id_input_inline')?.value;
                if (action === 'update' && vehicleId) {
                    formData.set('id', vehicleId);
                }

                const response = await fetch('/carwash_project/backend/dashboard/vehicle_api.php', {
                    method: 'POST',
                    credentials: 'same-origin',
                    body: formData
                });

                const contentType = response.headers.get('content-type');
                let result;

                if (contentType && contentType.includes('application/json')) {
                    result = await response.json();
                } else {
                    const text = await response.text();
                    try {
                        result = JSON.parse(text);
                    } catch (e) {
                        throw new Error('Sunucudan geçersiz yanıt alındı');
                    }
                }

                const isSuccess = result.success === true || 
                                 result.status === 'success' || 
                                 (response.ok && !result.error);

                if (isSuccess) {
                    if (msgEl) {
                        msgEl.textContent = action === 'update' ? 'Araç başarıyla güncellendi' : 'Araç başarıyla eklendi';
                        msgEl.style.color = '#10b981';
                        msgEl.style.fontWeight = '600';
                    }

                    vehicleForm.reset();
                    if (previewImg) previewImg.src = '/carwash_project/frontend/assets/images/default-car.png';

                    setTimeout(() => {
                        closeVehicleForm();
                        loadUserVehicles();
                    }, 1000);
                } else {
                    const errorMsg = result.message || result.error || 'İşlem başarısız oldu';
                    if (msgEl) {
                        msgEl.textContent = errorMsg;
                        msgEl.style.color = '#ef4444';
                        msgEl.style.fontWeight = '600';
                    }
                }
            } catch (error) {
                console.error('Vehicle form error:', error);
                if (msgEl) {
                    msgEl.textContent = 'Bir hata oluştu: ' + error.message;
                    msgEl.style.color = '#ef4444';
                    msgEl.style.fontWeight = '600';
                }
            } finally {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = '<i class="fas fa-save"></i><span>Kaydet</span>';
                }
            }
        });
    })();

    // ================================================
    // WINDOW RESIZE HANDLER
    // ================================================

    window.addEventListener('resize', function() {
        if (window.innerWidth >= 1024) {
            closeMobileSidebar();
        }
    });

    // ================================================
    // INITIALIZATION
    // ================================================

    document.addEventListener('DOMContentLoaded', function() {
        console.log('Customer Dashboard initialized');

        // Load vehicles if vehicles section is visible
        const vehiclesSection = document.getElementById('vehicles');
        if (vehiclesSection && !vehiclesSection.classList.contains('hidden')) {
            loadUserVehicles();
        }
    });

})();
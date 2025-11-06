(function initializeVehicleOperations() {
  'use strict';

  // HTML escaping helper
  function escapeHtml(text) {
    if (text == null) return '';
    const div = document.createElement('div');
    div.textContent = String(text);
    return div.innerHTML;
  }

  // Resolve vehicle image URL
  function resolveVehicleImageUrl(path) {
    if (!path) {
      return '/carwash_project/frontend/assets/images/default-car.png';
    }
    
    // If already absolute URL
    if (path.startsWith('http://') || path.startsWith('https://')) {
      return path;
    }
    
    // If starts with /, use as-is
    if (path.startsWith('/')) {
      return path;
    }
    
    // Otherwise, prepend project root
    return '/carwash_project/' + path;
  }

  // Render vehicles list
  function renderVehiclesList(vehicles) {
    const container = document.getElementById('vehiclesList');
    if (!container) return;

    if (!Array.isArray(vehicles) || vehicles.length === 0) {
      container.innerHTML = `
        <div class="col-span-full text-center py-12">
          <i class="fas fa-car text-6xl text-gray-300 mb-4"></i>
          <p class="text-gray-500 text-lg mb-4">Henüz araç eklenmedi</p>
          <button onclick="openVehicleModal()" class="gradient-bg text-white px-6 py-3 rounded-lg hover:shadow-lg transition-all">
            <i class="fas fa-plus mr-2"></i>İlk Aracınızı Ekleyin
          </button>
        </div>
      `;
      return;
    }

    container.innerHTML = vehicles.map(v => {
      const brand = escapeHtml(v.brand || v.car_brand || '');
      const model = escapeHtml(v.model || v.car_model || '');
      const plate = escapeHtml(v.license_plate || '');
      const year = escapeHtml(v.year || v.car_year || '');
      const color = escapeHtml(v.color || v.car_color || '');
      const imgSrc = resolveVehicleImageUrl(v.image_path);

      return `
        <div class="bg-white rounded-2xl p-6 card-hover shadow-lg transition-all" data-vehicle-id="${v.id || ''}">
          <div class="flex items-start gap-4 mb-4">
            <div class="w-20 h-20 flex-shrink-0 rounded-lg overflow-hidden bg-gray-100">
              <img 
                src="${imgSrc}" 
                alt="${escapeHtml(brand + ' ' + model)}"
                class="w-full h-full object-cover"
                onerror="this.onerror=null; this.src='/carwash_project/frontend/assets/images/default-car.png';"
                loading="lazy"
              />
            </div>
            <div class="flex-1 min-w-0">
              <h4 class="font-bold text-lg text-gray-800 truncate">${brand} ${model}</h4>
              <p class="text-sm text-gray-600 mt-1">
                ${plate ? `<span class="inline-block mr-3"><i class="fas fa-id-card mr-1 text-gray-400"></i>${plate}</span>` : ''}
                ${year ? `<span class="inline-block mr-3"><i class="fas fa-calendar mr-1 text-gray-400"></i>${year}</span>` : ''}
                ${color ? `<span class="inline-block"><i class="fas fa-palette mr-1 text-gray-400"></i>${color}</span>` : ''}
              </p>
            </div>
          </div>
          <div class="flex gap-2 pt-3 border-t border-gray-100">
            <button 
              class="flex-1 text-blue-600 hover:bg-blue-50 py-2 rounded-lg transition-colors font-medium" 
              data-action="edit"
              data-vehicle-data='${JSON.stringify(v).replace(/'/g, '&apos;')}'
              aria-label="Düzenle">
              <i class="fas fa-edit mr-1"></i>Düzenle
            </button>
            <button 
              class="flex-1 text-red-600 hover:bg-red-50 py-2 rounded-lg transition-colors font-medium" 
              data-action="delete"
              data-vehicle-id="${v.id || 0}"
              aria-label="Sil">
              <i class="fas fa-trash mr-1"></i>Sil
            </button>
          </div>
        </div>
      `;
    }).join('');

    // Attach event listeners to buttons
    container.querySelectorAll('[data-action]').forEach(btn => {
      btn.addEventListener('click', function(e) {
        e.preventDefault();
        const action = this.getAttribute('data-action');
        
        if (action === 'edit') {
          const vehicleData = this.getAttribute('data-vehicle-data');
          try {
            const vehicle = JSON.parse(vehicleData);
            if (typeof openVehicleModal === 'function') {
              openVehicleModal(vehicle);
            }
          } catch (err) {
            console.error('Failed to parse vehicle data:', err);
          }
        } else if (action === 'delete') {
          const vehicleId = this.getAttribute('data-vehicle-id');
          if (vehicleId && typeof deleteVehicle === 'function') {
            deleteVehicle(parseInt(vehicleId));
          }
        }
      });
    });
  }

  // Load vehicles from API
  async function loadVehicles() {
    const container = document.getElementById('vehiclesList');
    if (!container) return;

    try {
      // Show loading state
      container.innerHTML = `
        <div class="col-span-full text-center py-12">
          <i class="fas fa-spinner fa-spin text-4xl text-blue-500 mb-4"></i>
          <p class="text-gray-600">Araçlar yükleniyor...</p>
        </div>
      `;

      const response = await fetch('/carwash_project/backend/dashboard/vehicle_api.php?action=list', {
        method: 'GET',
        credentials: 'same-origin',
        headers: {
          'Accept': 'application/json'
        }
      });

      if (!response.ok) {
        throw new Error(`HTTP ${response.status}`);
      }

      const contentType = response.headers.get('content-type');
      let result;

      if (contentType && contentType.includes('application/json')) {
        result = await response.json();
      } else {
        const text = await response.text();
        try {
          result = JSON.parse(text);
        } catch (e) {
          console.error('Failed to parse response:', text.substring(0, 200));
          throw new Error('Geçersiz sunucu yanıtı');
        }
      }

      // Handle different response structures
      let vehicles = [];
      
      if (Array.isArray(result)) {
        vehicles = result;
      } else if (result.data && Array.isArray(result.data)) {
        vehicles = result.data;
      } else if (result.vehicles && Array.isArray(result.vehicles)) {
        vehicles = result.vehicles;
      } else if (result.data && result.data.vehicles && Array.isArray(result.data.vehicles)) {
        vehicles = result.data.vehicles;
      }

      renderVehiclesList(vehicles);
      
      // Update vehicle selector in booking form if exists
      if (typeof updateVehicleSelector === 'function') {
        updateVehicleSelector(vehicles);
      }

    } catch (error) {
      console.error('Load vehicles error:', error);
      container.innerHTML = `
        <div class="col-span-full text-center py-12">
          <i class="fas fa-exclamation-triangle text-6xl text-red-300 mb-4"></i>
          <p class="text-red-600 text-lg mb-4">Araçlar yüklenirken bir hata oluştu</p>
          <button onclick="loadVehicles()" class="gradient-bg text-white px-6 py-3 rounded-lg hover:shadow-lg transition-all">
            <i class="fas fa-redo mr-2"></i>Tekrar Dene
          </button>
        </div>
      `;
    }
  }

  // Delete vehicle
  async function deleteVehicle(vehicleId) {
    if (!vehicleId) {
      console.error('No vehicle ID provided');
      return;
    }

    if (!confirm('Bu aracı silmek istediğinizden emin misiniz? Bu işlem geri alınamaz.')) {
      return;
    }

    try {
      // Find and disable delete button
      const card = document.querySelector(`[data-vehicle-id="${vehicleId}"]`);
      const btn = card ? card.querySelector('[data-action="delete"]') : null;
      if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i>Siliniyor...';
      }

      // Get CSRF token
      const csrfToken = document.getElementById('csrf_token_vehicle')?.value || 
                       document.querySelector('meta[name="csrf-token"]')?.content || 
                       '';

      const formData = new FormData();
      formData.append('action', 'delete');
      formData.append('id', vehicleId);
      if (csrfToken) {
        formData.append('csrf_token', csrfToken);
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
          throw new Error('Geçersiz sunucu yanıtı');
        }
      }

      const isSuccess = result.success === true || 
                       result.status === 'success' || 
                       (response.ok && !result.error);

      if (isSuccess) {
        // Show success notification
        const notification = document.createElement('div');
        notification.style.cssText = `
          position: fixed;
          bottom: 20px;
          right: 20px;
          padding: 12px 24px;
          border-radius: 8px;
          color: #fff;
          font-size: 14px;
          z-index: 9999;
          background-color: #10b981;
          box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        `;
        notification.textContent = 'Araç başarıyla silindi';
        document.body.appendChild(notification);
        setTimeout(() => notification.remove(), 3000);

        // Fade out and remove card
        if (card) {
          card.style.transition = 'opacity 0.3s';
          card.style.opacity = '0';
          setTimeout(() => card.remove(), 300);
        }

        // Reload vehicles list after a short delay
        setTimeout(loadVehicles, 500);
      } else {
        const errorMsg = result.message || result.error || 'Silme işlemi başarısız';
        alert(errorMsg);
        if (btn) {
          btn.disabled = false;
          btn.innerHTML = '<i class="fas fa-trash mr-1"></i>Sil';
        }
      }
    } catch (error) {
      console.error('Delete vehicle error:', error);
      alert('Bir hata oluştu: ' + error.message);
      
      const btn = document.querySelector(`[data-vehicle-id="${vehicleId}"] [data-action="delete"]`);
      if (btn) {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-trash mr-1"></i>Sil';
      }
    }
  }

  // Expose functions globally
  window.loadVehicles = loadVehicles;
  window.loadUserVehicles = loadVehicles; // Alias for compatibility
  window.deleteVehicle = deleteVehicle;
  window.renderVehiclesList = renderVehiclesList;

  // Auto-load vehicles when vehicles section becomes visible
  document.addEventListener('DOMContentLoaded', function() {
    const vehiclesSection = document.getElementById('vehicles');
    if (vehiclesSection && !vehiclesSection.classList.contains('hidden')) {
      loadVehicles();
    }
  });

  console.log('Vehicle operations initialized');
})();
(function initializeVehicleForm() {
  'use strict';
  
  const vehicleForm = document.getElementById('vehicleFormInline');
  const formPanel = document.getElementById('vehicleInlineSection');
  const formAction = document.getElementById('vehicleFormAction');
  const msgEl = document.getElementById('vehicleFormMessageInline');
  const imageInput = document.getElementById('vehicle_image_inline');
  const previewImg = document.getElementById('vehicleImagePreview');
  const submitBtn = document.getElementById('vehicleInlineSubmit');
  const titleEl = document.getElementById('vehicleInlineTitle');

  if (!vehicleForm) {
    console.warn('Vehicle form not found');
    return;
  }

  // Image preview handler
  if (imageInput && previewImg) {
    imageInput.addEventListener('change', function(e) {
      const file = e.target.files[0];
      if (file && file.type.startsWith('image/')) {
        // Validate file size (2MB max)
        if (file.size > 2 * 1024 * 1024) {
          showMessage('Resim boyutu 2MB\'den küçük olmalıdır', 'error');
          imageInput.value = '';
          return;
        }
        
        const reader = new FileReader();
        reader.onload = function(ev) {
          previewImg.src = ev.target.result;
        };
        reader.onerror = function() {
          showMessage('Resim önizlemesi yüklenemedi', 'error');
        };
        reader.readAsDataURL(file);
      }
    });
  }

  // Show message helper
  function showMessage(text, type = 'info') {
    if (!msgEl) {
      // Fallback to floating notification
      const box = document.createElement('div');
      box.textContent = text;
      box.style.cssText = `
        position: fixed;
        bottom: 20px;
        right: 20px;
        padding: 12px 24px;
        border-radius: 8px;
        color: #fff;
        font-size: 14px;
        z-index: 9999;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        background-color: ${type === 'success' ? '#10b981' : type === 'error' ? '#ef4444' : '#3b82f6'};
      `;
      document.body.appendChild(box);
      setTimeout(() => box.remove(), 4000);
      return;
    }
    
    msgEl.textContent = text;
    msgEl.className = type === 'error' 
      ? 'text-red-600 font-semibold mt-3' 
      : 'text-green-600 font-semibold mt-3';
    
    setTimeout(() => {
      msgEl.textContent = '';
      msgEl.className = '';
    }, 5000);
  }

  // Get CSRF token
  function getCsrfToken() {
    // Try multiple sources
    const tokenInput = document.getElementById('csrf_token_vehicle');
    if (tokenInput && tokenInput.value) return tokenInput.value;
    
    const metaTag = document.querySelector('meta[name="csrf-token"]');
    if (metaTag && metaTag.content) return metaTag.content;
    
    if (window.CONFIG && window.CONFIG.CSRF_TOKEN) return window.CONFIG.CSRF_TOKEN;
    
    return '';
  }

  // Form submission handler
  vehicleForm.addEventListener('submit', async function(e) {
    e.preventDefault();

    // Clear previous messages
    if (msgEl) {
      msgEl.textContent = '';
      msgEl.className = '';
    }

    // Disable submit button
    if (submitBtn) {
      submitBtn.disabled = true;
      submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Kaydediliyor...';
    }

    try {
      const action = formAction ? formAction.value : 'create';
      const formData = new FormData(vehicleForm);

      // Ensure action is set correctly
      formData.set('action', action);

      // Add CSRF token
      const csrfToken = getCsrfToken();
      if (csrfToken) {
        formData.set('csrf_token', csrfToken);
      } else {
        throw new Error('CSRF token eksik');
      }

      // For update action, ensure vehicle ID is present
      const vehicleId = document.getElementById('vehicle_id_input_inline')?.value;
      if (action === 'update') {
        if (!vehicleId) {
          throw new Error('Araç ID bulunamadı');
        }
        formData.set('id', vehicleId);
      }

      // Validate required fields
      const brand = formData.get('car_brand');
      const model = formData.get('car_model');
      const plate = formData.get('license_plate');

      if (!brand || !model || !plate) {
        throw new Error('Lütfen tüm zorunlu alanları doldurun');
      }

      // Send request
      const response = await fetch('/carwash_project/backend/dashboard/vehicle_api.php', {
        method: 'POST',
        credentials: 'same-origin',
        body: formData
      });

      // Parse response
      const contentType = response.headers.get('content-type');
      let result;

      if (contentType && contentType.includes('application/json')) {
        result = await response.json();
      } else {
        const text = await response.text();
        console.warn('Non-JSON response from server:', text.substring(0, 200));
        try {
          result = JSON.parse(text);
        } catch (e) {
          throw new Error('Sunucudan geçersiz yanıt alındı');
        }
      }

      // Check for success
      const isSuccess = result.success === true || 
                       result.status === 'success' || 
                       (response.ok && !result.error);

      if (isSuccess) {
        const successMsg = action === 'update' 
          ? 'Araç başarıyla güncellendi' 
          : 'Araç başarıyla eklendi';
        
        showMessage(successMsg, 'success');
        
        // Reset form
        vehicleForm.reset();
        if (previewImg) {
          previewImg.src = '/carwash_project/frontend/assets/images/default-car.png';
        }
        
        // Close form panel after short delay
        setTimeout(() => {
          closeVehicleForm();
          
          // Reload vehicles list
          if (typeof loadVehicles === 'function') {
            loadVehicles();
          } else if (typeof loadUserVehicles === 'function') {
            loadUserVehicles();
          } else if (typeof refreshVehiclesList === 'function') {
            refreshVehiclesList();
          }
        }, 1000);
      } else {
        // Handle error response
        const errorMsg = result.message || result.error || 'İşlem başarısız oldu';
        showMessage(errorMsg, 'error');

        // Show field-specific errors if present
        if (result.errors && typeof result.errors === 'object') {
          Object.keys(result.errors).forEach(field => {
            const input = vehicleForm.querySelector(`[name="${field}"]`);
            if (input) {
              input.classList.add('border-red-500');
              setTimeout(() => input.classList.remove('border-red-500'), 3000);
            }
          });
        }
      }
    } catch (error) {
      console.error('Vehicle form submission error:', error);
      showMessage('Bir hata oluştu: ' + error.message, 'error');
    } finally {
      // Re-enable submit button
      if (submitBtn) {
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="fas fa-save mr-2"></i>Kaydet';
      }
    }
  });

  // Global function to open vehicle form for create/edit
  window.openVehicleModal = function(vehicle = null) {
    if (!formPanel || !vehicleForm) return;

    if (vehicle && typeof vehicle === 'object') {
      // Edit mode
      if (titleEl) titleEl.textContent = 'Araç Düzenle';
      if (formAction) formAction.value = 'update';
      
      const vehicleIdInput = document.getElementById('vehicle_id_input_inline');
      if (vehicleIdInput) vehicleIdInput.value = vehicle.id || '';
      
      document.getElementById('car_brand_inline').value = vehicle.brand || '';
      document.getElementById('car_model_inline').value = vehicle.model || '';
      document.getElementById('license_plate_inline').value = vehicle.license_plate || '';
      document.getElementById('car_year_inline').value = vehicle.year || '';
      document.getElementById('car_color_inline').value = vehicle.color || '';
      
      // Set preview image if available
      if (previewImg && vehicle.image_path) {
        let imgSrc = vehicle.image_path;
        // Ensure proper path format
        if (imgSrc && !imgSrc.startsWith('http') && !imgSrc.startsWith('/')) {
          imgSrc = '/' + imgSrc;
        }
        previewImg.src = imgSrc || '/carwash_project/frontend/assets/images/default-car.png';
      }
    } else {
      // Create mode
      if (titleEl) titleEl.textContent = 'Yeni Araç Ekle';
      if (formAction) formAction.value = 'create';
      
      const vehicleIdInput = document.getElementById('vehicle_id_input_inline');
      if (vehicleIdInput) vehicleIdInput.value = '';
      
      vehicleForm.reset();
      if (previewImg) {
        previewImg.src = '/carwash_project/frontend/assets/images/default-car.png';
      }
    }

    // Clear any previous messages
    if (msgEl) {
      msgEl.textContent = '';
      msgEl.className = '';
    }

    // Show form panel
    formPanel.style.display = 'block';
    formPanel.scrollIntoView({ behavior: 'smooth', block: 'start' });
  };

  // Global function to close vehicle form
  window.closeVehicleForm = function() {
    if (!formPanel || !vehicleForm) return;
    
    vehicleForm.reset();
    if (previewImg) {
      previewImg.src = '/carwash_project/frontend/assets/images/default-car.png';
    }
    
    if (msgEl) {
      msgEl.textContent = '';
      msgEl.className = '';
    }
    
    formPanel.style.display = 'none';
  };

  // Alias for compatibility
  window.closeVehicleModal = window.closeVehicleForm;

  console.log('Vehicle form handler initialized');
})();
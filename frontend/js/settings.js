/**
 * CarWash Settings Page JavaScript
 * Following project conventions: Responsive/SEO-Friendly/Performance/Accessibility/Typography/Consistent UI
 * Enhanced with modern JavaScript practices and performance optimizations
 */

'use strict';

// ===========================
// APPLICATION CONFIGURATION
// ===========================

const CONFIG = {
  API_BASE_URL: '/backend/api',
  DEBOUNCE_DELAY: 300,
  TOAST_DURATION: 5000,
  AUTOSAVE_DELAY: 2000,
  PASSWORD_MIN_LENGTH: 8,
  MOBILE_BREAKPOINT: 1024,
  USER_ID: 123 // Example user ID, replace with actual logic
};

// ===========================
// MAIN APPLICATION CLASS
// ===========================

class CarWashSettingsApp {
  constructor() {
    this.currentSection = 'profile';
    this.isLoading = false;
    this.debounceTimers = new Map();
    this.toastContainer = null;
    this.mobileMenuOpen = false;
    
    // Performance optimization: Store frequently accessed elements
    this.elements = {};
    
    // Initialize application
    this.init();
  }

  /**
   * Initialize the application
   */
  init() {
    this.cacheElements();
    this.setupEventListeners();
    this.setupAccessibility();
    this.handleInitialNavigation();
    this.setupPerformanceOptimizations();
    
    console.log('CarWash Settings App initialized successfully');
  }

  /**
   * Cache frequently accessed DOM elements for performance
   */
  cacheElements() {
    this.elements = {
      // Navigation elements
      mobileMenuBtn: document.getElementById('mobile-menu-btn'),
      closeMenuBtn: document.getElementById('close-menu-btn'),
      settingsNav: document.getElementById('settings-nav'),
      navOverlay: document.getElementById('nav-overlay'),
      navLinks: document.querySelectorAll('.nav-link'),
      
      // Section elements
      sections: document.querySelectorAll('.settings-section'),
      
      // Form elements
      profileForm: document.getElementById('profileForm'),
      passwordForm: document.getElementById('passwordForm'),
      notificationForm: document.getElementById('notificationPrefsForm'),
      
      // Input elements
      newPasswordInput: document.getElementById('newPassword'),
      
      // Toast container
      toastContainer: document.getElementById('toast-container'),
      
      // Screen reader announcements
      srAnnouncements: document.getElementById('sr-announcements'),

      // Vehicle elements
      vehicleList: document.getElementById('vehicle-list')
    };
  }

  /**
   * Setup all event listeners
   */
  setupEventListeners() {
    // Mobile navigation
    this.elements.mobileMenuBtn?.addEventListener('click', () => this.openMobileMenu());
    this.elements.closeMenuBtn?.addEventListener('click', () => this.closeMobileMenu());
    this.elements.navOverlay?.addEventListener('click', () => this.closeMobileMenu());

    // Section navigation
    this.elements.navLinks.forEach(link => {
      link.addEventListener('click', (e) => this.handleNavigation(e));
    });

    // Form submissions
    this.elements.profileForm?.addEventListener('submit', (e) => this.handleProfileSubmit(e));
    this.elements.passwordForm?.addEventListener('submit', (e) => this.handlePasswordSubmit(e));
    this.elements.notificationForm?.addEventListener('submit', (e) => this.handleNotificationSubmit(e));

    // Password strength checking
    this.elements.newPasswordInput?.addEventListener('input', 
      this.debounce((e) => this.checkPasswordStrength(e.target.value), CONFIG.DEBOUNCE_DELAY)
    );

    // Global keyboard shortcuts
    document.addEventListener('keydown', (e) => this.handleGlobalKeyboard(e));

    // Browser navigation
    window.addEventListener('popstate', () => this.handleBrowserNavigation());

    // Responsive handlers
    window.addEventListener('resize', 
      this.debounce(() => this.handleResize(), 250), 
      { passive: true }
    );

    // Form input validation
    this.setupInputValidation();

    // Two-factor authentication toggle
    const twoFactorToggle = document.getElementById('twoFactorToggle');
    twoFactorToggle?.addEventListener('change', (e) => this.handleTwoFactorToggle(e));
  }

  /**
   * Setup input validation for all forms
   */
  setupInputValidation() {
    const inputs = document.querySelectorAll('input, textarea, select');
    
    inputs.forEach(input => {
      // Real-time validation on input
      input.addEventListener('input', 
        this.debounce(() => this.validateField(input), CONFIG.DEBOUNCE_DELAY)
      );
      
      // Validation on blur
      input.addEventListener('blur', () => this.validateField(input));
      
      // Clear errors on focus
      input.addEventListener('focus', () => this.clearFieldError(input));
    });
  }

  /**
   * Setup accessibility features
   */
  setupAccessibility() {
    // Add ARIA attributes to navigation
    this.elements.navLinks.forEach((link, index) => {
      link.setAttribute('role', 'tab');
      link.setAttribute('tabindex', index === 0 ? '0' : '-1');
    });

    // Setup keyboard navigation for nav links
    this.elements.settingsNav?.addEventListener('keydown', (e) => {
      this.handleKeyboardNavigation(e);
    });

    // Focus trap for mobile menu
    this.setupFocusTrap();
  }

  /**
   * Setup focus trap for mobile navigation
   */
  setupFocusTrap() {
    this.elements.settingsNav?.addEventListener('keydown', (e) => {
      if (window.innerWidth >= CONFIG.MOBILE_BREAKPOINT || !this.mobileMenuOpen) return;
      
      if (e.key === 'Tab') {
        const focusableElements = this.elements.settingsNav.querySelectorAll(
          'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
        );
        
        const firstElement = focusableElements[0];
        const lastElement = focusableElements[focusableElements.length - 1];
        
        if (e.shiftKey) {
          if (document.activeElement === firstElement) {
            lastElement.focus();
            e.preventDefault();
          }
        } else {
          if (document.activeElement === lastElement) {
            firstElement.focus();
            e.preventDefault();
          }
        }
      }
    });
  }

  /**
   * Handle keyboard navigation for nav links
   */
  handleKeyboardNavigation(event) {
    if (!event.target.matches('.nav-link')) return;
    
    const navLinks = Array.from(this.elements.navLinks);
    const currentIndex = navLinks.indexOf(event.target);
    let newIndex;

    switch (event.key) {
      case 'ArrowRight':
      case 'ArrowDown':
        event.preventDefault();
        newIndex = (currentIndex + 1) % navLinks.length;
        break;
      case 'ArrowLeft':
      case 'ArrowUp':
        event.preventDefault();
        newIndex = (currentIndex - 1 + navLinks.length) % navLinks.length;
        break;
      case 'Home':
        event.preventDefault();
        newIndex = 0;
        break;
      case 'End':
        event.preventDefault();
        newIndex = navLinks.length - 1;
        break;
      case 'Enter':
      case ' ':
        event.preventDefault();
        event.target.click();
        return;
      default:
        return;
    }

    // Update tabindex and focus
    navLinks.forEach((link, index) => {
      link.setAttribute('tabindex', index === newIndex ? '0' : '-1');
    });
    
    navLinks[newIndex].focus();
  }

  /**
   * Setup performance optimizations
   */
  setupPerformanceOptimizations() {
    // Lazy load sections using Intersection Observer
    if ('IntersectionObserver' in window) {
      const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
          if (entry.isIntersecting) {
            this.lazyLoadSection(entry.target);
          }
        });
      }, { threshold: 0.1 });

      this.elements.sections.forEach(section => {
        observer.observe(section);
      });
    }

    // Setup autosave for forms
    this.setupAutosave('profileForm');
    
    // Preload critical resources
    this.preloadCriticalResources();
  }

  /**
   * Handle initial navigation based on URL hash
   */
  handleInitialNavigation() {
    const hash = window.location.hash.substring(1) || 'profile';
    this.showSection(hash);
  }

  /**
   * Handle browser back/forward navigation
   */
  handleBrowserNavigation() {
    const hash = window.location.hash.substring(1) || 'profile';
    this.showSection(hash);
  }

  // ===========================
  // NAVIGATION METHODS
  // ===========================

  /**
   * Handle navigation click events
   */
  handleNavigation(event) {
    event.preventDefault();
    const targetId = event.currentTarget.getAttribute('href').substring(1);
    this.showSection(targetId);
    
    // Update URL without triggering scroll
    history.pushState(null, null, `#${targetId}`);
    
    // Close mobile menu if open
    if (this.mobileMenuOpen) {
      this.closeMobileMenu();
    }
    
    this.announceToScreenReader(`Navigated to ${targetId} section`);
  }

  /**
   * Show specific section
   */
  showSection(sectionId) {
    // Hide all sections
    this.elements.sections.forEach(section => {
      section.classList.add('hidden');
      section.setAttribute('aria-hidden', 'true');
    });

    // Remove active state from all nav links
    this.elements.navLinks.forEach(link => {
      link.classList.remove('active', 'bg-primary-50', 'text-primary-600');
      link.classList.add('text-gray-700');
      link.removeAttribute('aria-current');
    });

    // Show target section
    const targetSection = document.getElementById(sectionId);
    if (targetSection) {
      targetSection.classList.remove('hidden');
      targetSection.setAttribute('aria-hidden', 'false');
      
      // Update active nav link
      const activeLink = document.querySelector(`a[href="#${sectionId}"]`);
      if (activeLink) {
        activeLink.classList.add('active', 'bg-primary-50', 'text-primary-600');
        activeLink.classList.remove('text-gray-700');
        activeLink.setAttribute('aria-current', 'page');
      }
      
      this.currentSection = sectionId;
      
      // Focus management
      this.manageFocus(targetSection);
    }
  }

  /**
   * Manage focus for accessibility
   */
  manageFocus(targetSection) {
    const firstFocusable = targetSection.querySelector(
      'input:not([disabled]), button:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])'
    );
    
    if (firstFocusable) {
      // Use setTimeout to ensure DOM is ready
      setTimeout(() => {
        firstFocusable.focus();
        firstFocusable.scrollIntoView({ behavior: 'smooth', block: 'center' });
      }, 150);
    }
  }

  /**
   * Open mobile menu
   */
  openMobileMenu() {
    this.elements.settingsNav?.classList.remove('-translate-x-full');
    this.elements.navOverlay?.classList.remove('hidden');
    this.elements.mobileMenuBtn?.setAttribute('aria-expanded', 'true');
    document.body.style.overflow = 'hidden';
    this.mobileMenuOpen = true;
    
    // Focus first nav link
    const firstNavLink = this.elements.navLinks[0];
    if (firstNavLink) {
      setTimeout(() => firstNavLink.focus(), 100);
    }
  }

  /**
   * Close mobile menu
   */
  closeMobileMenu() {
    this.elements.settingsNav?.classList.add('-translate-x-full');
    this.elements.navOverlay?.classList.add('hidden');
    this.elements.mobileMenuBtn?.setAttribute('aria-expanded', 'false');
    document.body.style.overflow = '';
    this.mobileMenuOpen = false;
    
    // Return focus to mobile menu button
    this.elements.mobileMenuBtn?.focus();
  }

  // ===========================
  // FORM HANDLING METHODS
  // ===========================

  /**
   * Handle profile form submission
   */
  async handleProfileSubmit(event) {
    event.preventDefault();
    
    if (!this.validateForm(this.elements.profileForm)) {
      this.announceToScreenReader('Form contains errors. Please correct them and try again.');
      return;
    }

    const formData = new FormData(event.target);
    const profileData = Object.fromEntries(formData.entries());

    try {
      this.setLoadingState(true, 'Updating profile...');
      
      // Simulate API call - replace with actual API endpoint
      const response = await this.makeAPIRequest(`${CONFIG.API_BASE_URL}/settings/update_profile.php`, {
        method: 'POST',
        body: JSON.stringify(profileData),
        headers: {
          'Content-Type': 'application/json',
          'X-Requested-With': 'XMLHttpRequest'
        }
      });

      if (response.success) {
        this.showToast('Profile updated successfully!', 'success');
        this.announceToScreenReader('Profile updated successfully');
      } else {
        throw new Error(response.message || 'Update failed');
      }
      
    } catch (error) {
      console.error('Profile update error:', error);
      this.showToast('Failed to update profile. Please try again.', 'error');
      this.announceToScreenReader('Error: Failed to update profile');
    } finally {
      this.setLoadingState(false);
    }
  }

  /**
   * Handle password form submission
   */
  async handlePasswordSubmit(event) {
    event.preventDefault();
    
    if (!this.validateForm(this.elements.passwordForm)) {
      return;
    }

    const formData = new FormData(event.target);
    const passwordData = Object.fromEntries(formData.entries());

    // Additional password validation
    if (passwordData.newPassword !== passwordData.confirmPassword) {
      this.showFieldError(document.getElementById('confirmPassword'), 'Passwords do not match');
      return;
    }

    if (passwordData.currentPassword === passwordData.newPassword) {
      this.showFieldError(document.getElementById('newPassword'), 'New password must be different from current password');
      return;
    }

    try {
      this.setLoadingState(true, 'Changing password...');
      
      const response = await this.makeAPIRequest(`${CONFIG.API_BASE_URL}/settings/update_password.php`, {
        method: 'POST',
        body: JSON.stringify(passwordData),
        headers: {
          'Content-Type': 'application/json'
        }
      });

      if (response.success) {
        event.target.reset();
        this.clearPasswordStrength();
        this.showToast('Password changed successfully!', 'success');
        this.announceToScreenReader('Password changed successfully');
      } else {
        throw new Error(response.message || 'Password change failed');
      }
      
    } catch (error) {
      console.error('Password update error:', error);
      this.showToast('Failed to change password. Please try again.', 'error');
      this.announceToScreenReader('Error: Failed to change password');
    } finally {
      this.setLoadingState(false);
    }
  }

  /**
   * Handle notification preferences submission
   */
  async handleNotificationSubmit(event) {
    event.preventDefault();
    
    const formData = new FormData(event.target);
    const preferences = {
      email_notifications: formData.has('email_notifications'),
      booking_reminders: formData.has('booking_reminders'),
      promotional_emails: formData.has('promotional_emails'),
      sms_notifications: formData.has('sms_notifications')
    };

    try {
      this.setLoadingState(true, 'Saving preferences...');
      
      const response = await this.makeAPIRequest(`${CONFIG.API_BASE_URL}/settings/update_notifications.php`, {
        method: 'POST',
        body: JSON.stringify(preferences),
        headers: {
          'Content-Type': 'application/json'
        }
      });

      if (response.success) {
        this.showToast('Notification preferences saved!', 'success');
        this.announceToScreenReader('Notification preferences saved successfully');
      } else {
        throw new Error(response.message || 'Save failed');
      }
      
    } catch (error) {
      console.error('Notification update error:', error);
      this.showToast('Failed to save preferences. Please try again.', 'error');
      this.announceToScreenReader('Error: Failed to save preferences');
    } finally {
      this.setLoadingState(false);
    }
  }

  /**
   * Load and render the user's vehicles.
   */
  async loadUserVehicles() {
    try {
      const response = await this.makeAPIRequest(`${CONFIG.API_BASE_URL}/vehicle_api.php?action=list&user_id=${CONFIG.USER_ID}`);

      if (response.success && Array.isArray(response.data)) {
        const vehicleList = document.querySelector('#vehicle-list');
        vehicleList.innerHTML = ''; // Clear existing vehicles

        response.data.forEach(vehicle => {
          const imageUrl = vehicle.image_path || '/frontend/images/default-vehicle.png';

          const vehicleItem = document.createElement('div');
          vehicleItem.className = 'vehicle-item flex items-center p-4 border-b';
          vehicleItem.innerHTML = `
            <img src="${imageUrl}" alt="Vehicle Image" class="w-16 h-16 rounded mr-4">
            <div class="flex-1">
              <h4 class="text-lg font-bold">${vehicle.brand} ${vehicle.model}</h4>
              <p class="text-sm text-gray-600">License Plate: ${vehicle.license_plate}</p>
              <p class="text-sm text-gray-600">Year: ${vehicle.year} | Color: ${vehicle.color}</p>
            </div>
            <button class="edit-vehicle-btn px-4 py-2 bg-blue-500 text-white rounded" data-id="${vehicle.id}">Edit</button>
          `;

          // Attach event listener for editing
          vehicleItem.querySelector('.edit-vehicle-btn').addEventListener('click', () => {
            this.openEditVehicleModal(vehicle);
          });

          vehicleList.appendChild(vehicleItem);
        });
      } else {
        throw new Error(response.message || 'Failed to load vehicles');
      }
    } catch (error) {
      console.error('Error loading vehicles:', error);
      this.showToast('Failed to load vehicles. Please try again.', 'error');
    }
  }

  /**
   * Open the Edit Vehicle Modal
   * @param {Object} vehicle - The vehicle data to pre-fill the modal.
   */
  async openEditVehicleModal(vehicle) {
    // Create the modal HTML
    const modal = document.createElement('div');
    modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
    modal.setAttribute('role', 'dialog');
    modal.setAttribute('aria-modal', 'true');
    modal.innerHTML = `
      <div class="bg-white rounded-lg shadow-lg w-96 p-6">
        <h3 class="text-lg font-bold mb-4">Edit Vehicle</h3>
        <form id="edit-vehicle-form">
          <label class="block mb-2">
            Brand:
            <input type="text" name="brand" value="${vehicle.brand}" class="w-full border rounded p-2">
          </label>
          <label class="block mb-2">
            Model:
            <input type="text" name="model" value="${vehicle.model}" class="w-full border rounded p-2">
          </label>
          <label class="block mb-2">
            License Plate:
            <input type="text" name="license_plate" value="${vehicle.license_plate}" class="w-full border rounded p-2">
          </label>
          <div class="flex justify-end mt-4">
            <button type="button" class="modal-cancel px-4 py-2 border rounded mr-2">Cancel</button>
            <button type="submit" class="modal-confirm px-4 py-2 bg-blue-500 text-white rounded">Save</button>
          </div>
        </form>
      </div>
    `;

    // Append modal to the body
    document.body.appendChild(modal);

    // Handle form submission
    const form = modal.querySelector('#edit-vehicle-form');
    form.addEventListener('submit', async (event) => {
      event.preventDefault();

      const formData = new FormData(form);
      formData.append('action', 'update');
      formData.append('csrf_token', CONFIG.CSRF_TOKEN);
      formData.append('id', vehicle.id);

      try {
        const response = await this.makeAPIRequest(`${CONFIG.API_BASE_URL}/vehicle_api.php`, {
          method: 'POST',
          body: formData
        });

        if (response.success) {
          this.showToast('Vehicle updated successfully!', 'success');
          this.refreshVehicleList();
          modal.remove();
        } else {
          throw new Error(response.message || 'Failed to update vehicle');
        }
      } catch (error) {
        console.error('Error updating vehicle:', error);
        this.showToast('Failed to update vehicle. Please try again.', 'error');
      }
    });

    // Handle modal cancel
    modal.querySelector('.modal-cancel').addEventListener('click', () => {
      modal.remove();
    });
  }

  /**
   * Delete a vehicle.
   * @param {number} vehicleId - The ID of the vehicle to delete.
   */
  async deleteVehicle(vehicleId) {
    if (!confirm('Are you sure you want to delete this vehicle?')) {
      return; // User canceled the deletion
    }

    try {
      const formData = new FormData();
      formData.append('action', 'delete');
      formData.append('vehicle_id', vehicleId);
      formData.append('csrf_token', CONFIG.CSRF_TOKEN);

      const response = await fetch(`${CONFIG.API_BASE_URL}/vehicle_api.php`, {
        method: 'POST',
        body: formData
      });

      if (!response.ok) {
        throw new Error(`HTTP Error: ${response.status}`);
      }

      const result = await response.json();

      if (result.success) {
        alert('Vehicle deleted successfully!');
        updateVehicleList(); // Reload the vehicle list
      } else {
        throw new Error(result.message || 'Failed to delete vehicle');
      }
    } catch (error) {
      console.error('Error deleting vehicle:', error);
      alert(`Error: ${error.message}`);
    }
  }

  /**
   * Update the vehicle list in the dashboard.
   */
  async updateVehicleList() {
    try {
      const response = await this.makeAPIRequest(`${CONFIG.API_BASE_URL}/vehicle_api.php?action=list`, {
        method: 'GET',
        headers: {
          'Content-Type': 'application/json'
        }
      });

      if (response.success && Array.isArray(response.vehicles)) {
        const vehicleList = document.querySelector('#vehicle-list');
        vehicleList.innerHTML = ''; // Clear existing vehicles

        response.vehicles.forEach(vehicle => {
          const imageUrl = vehicle.image_path || '/frontend/images/default-car.png';

          const vehicleItem = document.createElement('div');
          vehicleItem.className = 'vehicle-item flex items-center p-4 border-b';
          vehicleItem.innerHTML = `
            <img src="${imageUrl}" alt="Vehicle Image" class="w-16 h-16 rounded mr-4">
            <div class="flex-1">
              <h4 class="text-lg font-bold">${vehicle.brand} ${vehicle.model}</h4>
              <p class="text-sm text-gray-600">License Plate: ${vehicle.license_plate}</p>
            </div>
          `;

          vehicleList.appendChild(vehicleItem);
        });
      } else {
        throw new Error(response.message || 'Failed to fetch vehicles');
      }
    } catch (error) {
      console.error('Error updating vehicle list:', error);
      alert('Failed to update vehicle list. Please try again.');
    }
  }

  /**
   * Validate entire form
   */
  validateForm(form) {
    if (!form) return false;
    
    const inputs = form.querySelectorAll('input[required], textarea[required], select[required]');
    let isValid = true;
    
    inputs.forEach(input => {
      if (!this.validateField(input)) {
        isValid = false;
      }
    });
    
    return isValid;
  }

  /**
   * Validate individual field
   */
  validateField(field) {
    const value = field.value.trim();
    const fieldName = field.name || field.id;
    let isValid = true;
    let errorMessage = '';

    // Clear previous errors
    this.clearFieldError(field);

    // Required field validation
    if (field.required && !value) {
      errorMessage = 'This field is required';
      isValid = false;
    }
    // Email validation
    else if (field.type === 'email' && value && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) {
      errorMessage = 'Please enter a valid email address';
      isValid = false;
    }
    // Phone validation
    else if (field.type === 'tel' && value && !/^[+]?[0-9\s\-\(\)]+$/.test(value)) {
      errorMessage = 'Please enter a valid phone number';
      isValid = false;
    }
    // Password validation
    else if (field.type === 'password' && fieldName === 'newPassword' && value) {
      if (value.length < CONFIG.PASSWORD_MIN_LENGTH) {
        errorMessage = `Password must be at least ${CONFIG.PASSWORD_MIN_LENGTH} characters`;
        isValid = false;
      } else if (!/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]/.test(value)) {
        errorMessage = 'Password must contain uppercase, lowercase, number, and special character';
        isValid = false;
      }
    }
    // Name validation
    else if (fieldName === 'name' && value && value.length < 2) {
      errorMessage = 'Name must be at least 2 characters';
      isValid = false;
    }

    if (!isValid) {
      this.showFieldError(field, errorMessage);
    } else {
      this.markFieldValid(field);
    }

    return isValid;
  }

  /**
   * Show field error
   */
  showFieldError(field, message) {
    const fieldId = field.id || field.name;
    let errorElement = document.getElementById(`${fieldId}-error`);
    
    if (!errorElement) {
      errorElement = document.createElement('p');
      errorElement.id = `${fieldId}-error`;
      errorElement.className = 'field-error mt-2 text-sm text-red-600';
      errorElement.setAttribute('role', 'alert');
      errorElement.setAttribute('aria-live', 'polite');
      field.parentNode.appendChild(errorElement);
    }

    field.classList.add('border-red-500', 'focus:ring-red-500', 'error-state');
    field.classList.remove('border-green-500', 'success-state');
    field.setAttribute('aria-invalid', 'true');
    field.setAttribute('aria-describedby', errorElement.id);
    
    errorElement.textContent = message;
    errorElement.classList.remove('hidden');
    
    // Focus field if not already focused
    if (document.activeElement !== field) {
      field.focus();
    }
    
    this.announceToScreenReader(`Error: ${message}`);
  }

  /**
   * Clear field error
   */
  clearFieldError(field) {
    const fieldId = field.id || field.name;
    const errorElement = document.getElementById(`${fieldId}-error`);
    
    field.classList.remove('border-red-500', 'focus:ring-red-500', 'error-state');
    field.setAttribute('aria-invalid', 'false');
    field.removeAttribute('aria-describedby');
    
    if (errorElement) {
      errorElement.classList.add('hidden');
      errorElement.textContent = '';
    }
  }

  /**
   * Mark field as valid
   */
  markFieldValid(field) {
    field.classList.remove('border-red-500', 'focus:ring-red-500', 'error-state');
    field.classList.add('border-green-500', 'success-state');
    field.setAttribute('aria-invalid', 'false');
  }

  // ===========================
  // PASSWORD STRENGTH METHODS
  // ===========================

  /**
   * Check password strength
   */
  checkPasswordStrength(password) {
    const strengthText = document.getElementById('password-strength-text');
    const strengthFill = document.getElementById('password-strength-fill');
    
    if (!password || !strengthText || !strengthFill) {
      return;
    }

    let strength = 0;
    const checks = {
      length: password.length >= 8,
      longLength: password.length >= 12,
      lowercase: /[a-z]/.test(password),
      uppercase: /[A-Z]/.test(password),
      number: /[0-9]/.test(password),
      special: /[^a-zA-Z0-9]/.test(password)
    };

    // Calculate strength score
    Object.values(checks).forEach(check => {
      if (check) strength++;
    });

    // Update UI based on strength
    let strengthClass, strengthLabel, textColor;
    
    if (strength <= 2) {
      strengthClass = 'strength-weak';
      strengthLabel = 'Weak password';
      textColor = 'text-red-600';
    } else if (strength <= 4) {
      strengthClass = 'strength-medium';
      strengthLabel = 'Medium strength password';
      textColor = 'text-yellow-600';
    } else {
      strengthClass = 'strength-strong';
      strengthLabel = 'Strong password';
      textColor = 'text-green-600';
    }

    strengthFill.className = `password-strength-fill ${strengthClass}`;
    strengthText.textContent = strengthLabel;
    strengthText.className = `mt-2 text-xs font-medium ${textColor}`;
    
    // Update progress bar ARIA attributes
    const progressBar = strengthFill.parentNode;
    if (progressBar) {
      const percentage = (strength / 6) * 100;
      progressBar.setAttribute('aria-valuenow', percentage);
      progressBar.setAttribute('aria-valuetext', strengthLabel);
    }
  }

  /**
   * Clear password strength display
   */
  clearPasswordStrength() {
    const strengthText = document.getElementById('password-strength-text');
    const strengthFill = document.getElementById('password-strength-fill');
    
    if (strengthFill) {
      strengthFill.className = 'password-strength-fill';
      strengthFill.style.width = '0%';
    }
    
    if (strengthText) {
      strengthText.textContent = '';
      strengthText.className = 'mt-2 text-xs font-medium';
    }
  }

  // ===========================
  // UI UTILITY METHODS
  // ===========================

  /**
   * Show toast notification
   */
  showToast(message, type = 'success') {
    const toast = document.createElement('div');
    const toastId = `toast-${Date.now()}`;
    
    const icons = {
      success: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>',
      error: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>',
      warning: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>',
      info: '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>'
    };
    
    const colors = {
      success: 'toast-success',
      error: 'toast-error',
      warning: 'toast-warning',
      info: 'toast-info'
    };
    
    toast.id = toastId;
    toast.className = `toast toast-enter ${colors[type]}`;
    toast.setAttribute('role', 'alert');
    toast.setAttribute('aria-live', 'assertive');
    toast.setAttribute('aria-atomic', 'true');
    
    toast.innerHTML = `
      <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
        ${icons[type]}
      </svg>
      <span class="text-sm font-medium flex-1">${message}</span>
      <button 
        type="button" 
        class="ml-3 text-current opacity-70 hover:opacity-100 focus:outline-none focus:ring-2 focus:ring-current focus:ring-offset-2 rounded"
        onclick="this.parentElement.remove()"
        aria-label="Close notification">
        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" aria-hidden="true">
          <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
        </svg>
      </button>
    `;
    
    this.elements.toastContainer?.appendChild(toast);
    
    // Auto remove after duration
    setTimeout(() => {
      const toastElement = document.getElementById(toastId);
      if (toastElement) {
        toastElement.style.opacity = '0';
        toastElement.style.transform = 'translateX(100%)';
        setTimeout(() => toastElement.remove(), 300);
      }
    }, CONFIG.TOAST_DURATION);
    
    this.announceToScreenReader(message);
  }

  /**
   * Set loading state for forms
   */
  setLoadingState(isLoading, message = 'Loading...') {
    this.isLoading = isLoading;
    
    const submitButtons = document.querySelectorAll('button[type="submit"], .submit-btn');
    
    submitButtons.forEach(btn => {
      btn.disabled = isLoading;
      
      if (isLoading) {
        btn.setAttribute('data-original-text', btn.textContent);
        btn.innerHTML = `
          <svg class="w-5 h-5 mr-2 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
          </svg>
          ${message}
        `;
      } else {
        const originalText = btn.getAttribute('data-original-text');
        if (originalText) {
          btn.textContent = originalText;
          btn.removeAttribute('data-original-text');
        }
      }
    });
    
    this.announceToScreenReader(isLoading ? message : 'Loading complete');
  }

  /**
   * Announce to screen readers
   */
  announceToScreenReader(message) {
    if (this.elements.srAnnouncements) {
      this.elements.srAnnouncements.textContent = message;
      setTimeout(() => {
        this.elements.srAnnouncements.textContent = '';
      }, 1000);
    }
  }

  // ===========================
  // API AND UTILITY METHODS
  // ===========================

  /**
   * Make API requests with error handling
   */
  async makeAPIRequest(url, options = {}) {
    const defaultOptions = {
      method: 'GET',
      headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      },
      credentials: 'same-origin'
    };

    const requestOptions = { ...defaultOptions, ...options };

    try {
      const response = await fetch(url, requestOptions);
      
      if (!response.ok) {
        throw new Error(`HTTP error! status: ${response.status}`);
      }

      const data = await response.json();
      return data;
      
    } catch (error) {
      console.error('API request failed:', error);
      throw error;
    }
  }

  /**
   * Debounce function for performance
   */
  debounce(func, delay) {
    return (...args) => {
      const key = func.toString();
      clearTimeout(this.debounceTimers.get(key));
      this.debounceTimers.set(key, setTimeout(() => func.apply(this, args), delay));
    };
  }

  /**
   * Setup autosave functionality
   */
  setupAutosave(formId) {
    const form = document.getElementById(formId);
    if (!form) return;
    
    let autosaveTimeout;
    
    form.addEventListener('input', () => {
      clearTimeout(autosaveTimeout);
      autosaveTimeout = setTimeout(() => {
        const formData = new FormData(form);
        const data = Object.fromEntries(formData);
        
        try {
          localStorage.setItem(`carwash_autosave_${formId}`, JSON.stringify(data));
          console.log(`Form ${formId} autosaved`);
        } catch (error) {
          console.warn('Autosave failed:', error);
        }
      }, CONFIG.AUTOSAVE_DELAY);
    });
    
    // Restore autosaved data on page load
    try {
      const autosaved = localStorage.getItem(`carwash_autosave_${formId}`);
      if (autosaved) {
        const data = JSON.parse(autosaved);
        Object.entries(data).forEach(([name, value]) => {
          const input = form.querySelector(`[name="${name}"]`);
          if (input && input.type !== 'password') {
            input.value = value;
          }
        });
      }
    } catch (error) {
      console.warn('Failed to restore autosaved data:', error);
    }
  }

  /**
   * Preload critical resources
   */
  preloadCriticalResources() {
    // Implement resource preloading as needed
    console.log('Preloading critical resources...');
  }

  /**
   * Enhanced error logging
   */
  logError(errorType, error) {
    const errorData = {
      type: errorType,
      message: error.message,
      stack: error.stack,
      timestamp: new Date().toISOString(),
      userAgent: navigator.userAgent,
      url: window.location.href
    };

    // Send to logging service (implement based on your backend)
    console.error('CarWash Settings Error:', errorData);
  }

  /**
   * Handle visibility change for performance
   */
  handleVisibilityChange() {
    if (document.hidden) {
      // Page is hidden, pause non-critical operations
      this.pauseNonCriticalOperations();
    } else {
      // Page is visible, resume operations
      this.resumeOperations();
    }
  }

  /**
   * Load user data with caching
   */
  async loadUserData() {
    try {
      // Check cache first
      const cachedData = this.getCachedUserData();
      if (cachedData && this.isCacheValid(cachedData)) {
        this.populateUserData(cachedData.data);
        return;
      }

      const response = await this.makeAPIRequest('/backend/api/settings/get_settings.php');
      
      if (response.success) {
        this.populateUserData(response.data);
        this.cacheUserData(response.data);
      } else {
        throw new Error(response.message || 'Veri yüklenemedi');
      }
    } catch (error) {
      console.error('Error loading settings:', error);
      this.showToast('Ayarlar yüklenirken bir hata oluştu.', 'error');
    }
  }

  /**
   * Populate form fields with user data
   */
  populateUserData(data) {
    // Profile data
    if (data.profile) {
      Object.entries(data.profile).forEach(([key, value]) => {
        const field = document.querySelector(`[name="${key}"]`);
        if (field) {
          field.value = value || '';
        }
      });
    }

    // Notification preferences
    if (data.notifications) {
      Object.entries(data.notifications).forEach(([key, value]) => {
        const field = document.querySelector(`[name="${key}"]`);
        if (field && field.type === 'checkbox') {
          field.checked = Boolean(value);
        }
      });
    }

    // Privacy settings
    if (data.privacy) {
      Object.entries(data.privacy).forEach(([key, value]) => {
        const field = document.querySelector(`[name="${key}"]`);
        if (field) {
          if (field.type === 'checkbox') {
            field.checked = Boolean(value);
          } else {
            field.value = value || '';
          }
        }
      });
    }
  }

  /**
   * Cache user data
   */
  cacheUserData(data) {
    const cacheData = {
      data: data,
      timestamp: Date.now(),
      ttl: 5 * 60 * 1000 // 5 minutes
    };
    
    try {
      localStorage.setItem('carwash_settings_cache', JSON.stringify(cacheData));
    } catch (error) {
      console.warn('Failed to cache user data:', error);
    }
  }

  /**
   * Get cached user data
   */
  getCachedUserData() {
    try {
      const cached = localStorage.getItem('carwash_settings_cache');
      return cached ? JSON.parse(cached) : null;
    } catch (error) {
      console.warn('Failed to get cached data:', error);
      return null;
    }
  }

  /**
   * Check if cache is valid
   */
  isCacheValid(cachedData) {
    return cachedData && 
           cachedData.timestamp && 
           (Date.now() - cachedData.timestamp) < cachedData.ttl;
  }
}

/**
 * Handle AJAX errors gracefully.
 * @param {Error} error - The error object.
 * @param {string} context - Context or description of the operation.
 */
function handleAjaxError(error, context) {
  console.error(`Error during ${context}:`, error);
  alert(`An error occurred while ${context}. Please try again later.`);
}

// Wrap fetch calls with error handling
async function safeFetch(url, options, context) {
  try {
    const response = await fetch(url, options);

    if (!response.ok) {
      throw new Error(`HTTP Error: ${response.status}`);
    }

    const result = await response.json();
    return result;
  } catch (error) {
    handleAjaxError(error, context);
    throw error; // Re-throw to allow further handling if needed
  }
}

// Example usage in updateVehicleList
async function updateVehicleList() {
  try {
    const result = await safeFetch(`${CONFIG.API_BASE_URL}/vehicle_api.php?action=list`, {
      method: 'GET',
      headers: {
        'Content-Type': 'application/json'
      }
    }, 'fetching vehicle list');

    if (result.success && Array.isArray(result.vehicles)) {
      const vehicleList = document.querySelector('#vehicle-list');
      vehicleList.innerHTML = ''; // Clear existing vehicles

      result.vehicles.forEach(vehicle => {
        const imageUrl = vehicle.image_path || '/frontend/images/default-car.png';

        const vehicleItem = document.createElement('div');
        vehicleItem.className = 'vehicle-item flex items-center p-4 border-b';
        vehicleItem.innerHTML = `
          <img src="${imageUrl}" alt="Vehicle Image" class="w-16 h-16 rounded mr-4">
          <div class="flex-1">
            <h4 class="text-lg font-bold">${vehicle.brand} ${vehicle.model}</h4>
            <p class="text-sm text-gray-600">License Plate: ${vehicle.license_plate}</p>
          </div>
        `;

        vehicleList.appendChild(vehicleItem);
      });
    } else {
      throw new Error(result.message || 'Failed to fetch vehicles');
    }
  } catch (error) {
    console.error('Error updating vehicle list:', error);
    alert('Failed to update vehicle list. Please try again.');
  }
}

// Call the function to update the vehicle list on page load
document.addEventListener('DOMContentLoaded', updateVehicleList);
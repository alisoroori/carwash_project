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
  MOBILE_BREAKPOINT: 1024
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
      srAnnouncements: document.getElementById('sr-announcements')
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

  // ===========================
  // VALIDATION METHODS
  // ===========================

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

// Additional placeholder methods for completeness
  handleFormChange(event) { /* Implementation */ }
  handleInputChange(event) { /* Implementation */ }
  handleInputFocus(event) { /* Implementation */ }
  handleResize() { /* Implementation */ }
  handleScroll() { /* Implementation */ }
  handleBeforeUnload(event) { /* Implementation */ }
  handleHashChange() { /* Implementation */ }
  handleDeleteAccount(event) { /* Implementation */ }
  handleExportData(event) { /* Implementation */ }
  handleNotificationSubmit(event) { /* Implementation */ }
  handlePrivacySubmit(event) { /* Implementation */ }
  togglePasswordVisibility(event) { /* Implementation */ }

  /**
   * Handle global keyboard shortcuts for navigation and form actions.
   * @param {KeyboardEvent} event - The keyboard event triggered by user interaction.
   */
  handleGlobalKeyboard(event) {
    // Escape key to close mobile menu
    if (event.key === 'Escape' && this.mobileMenuOpen) {
      this.closeMobileMenu();
      return;
    }

    // Alt + number keys for quick navigation
    if (event.altKey && event.key >= '1' && event.key <= '8') {
      event.preventDefault();
      const sectionIndex = parseInt(event.key) - 1;
      const navLinks = Array.from(this.elements.navLinks);

      if (navLinks[sectionIndex]) {
        const targetSection = navLinks[sectionIndex].getAttribute('href').substring(1);
        this.showSection(targetSection);
        history.pushState(null, null, `#${targetSection}`);
        this.announceToScreenReader(`Navigated to ${targetSection} section using keyboard shortcut`);
      }
    }

    // Ctrl + S to save current form
    if (event.ctrlKey && event.key === 's') {
      event.preventDefault();
      this.saveCurrentForm();
    }
  }
  /**
   * Save the currently visible form
   */
  saveCurrentForm() {
    const activeSection = document.querySelector('.settings-section:not(.hidden)');
    if (!activeSection) return;

    const form = activeSection.querySelector('form');
    if (form) {
      const submitButton = form.querySelector('button[type="submit"]');
      if (submitButton && !submitButton.disabled) {
        submitButton.click();
        this.announceToScreenReader('Form submitted using keyboard shortcut');
      }
    }
  }

  /**
   * Handle two-factor authentication toggle
   */
  async handleTwoFactorToggle(event) {
    const isEnabled = event.target.checked;
    const twoFactorSection = document.getElementById('two-factor-section');
    
    try {
      this.setLoadingState(true, 'Updating security settings...');
      
      const response = await this.makeAPIRequest(`${CONFIG.API_BASE_URL}/settings/toggle_2fa.php`, {
        method: 'POST',
        body: JSON.stringify({ enabled: isEnabled }),
        headers: {
          'Content-Type': 'application/json'
        }
      });

      if (response.success) {
        if (isEnabled) {
          // Show setup instructions
          twoFactorSection?.classList.remove('hidden');
          this.showQRCode(response.qr_code, response.secret);
          this.showToast('Two-factor authentication enabled. Please scan the QR code with your authenticator app.', 'success');
        } else {
          // Hide setup section
          twoFactorSection?.classList.add('hidden');
          this.showToast('Two-factor authentication disabled.', 'info');
        }
        
        this.announceToScreenReader(`Two-factor authentication ${isEnabled ? 'enabled' : 'disabled'}`);
      } else {
        throw new Error(response.message || '2FA toggle failed');
      }
      
    } catch (error) {
      console.error('2FA toggle error:', error);
      event.target.checked = !isEnabled; // Revert toggle
      this.showToast('Failed to update two-factor authentication settings.', 'error');
      this.announceToScreenReader('Error: Failed to update two-factor authentication');
    } finally {
      this.setLoadingState(false);
    }
  }

  /**
  // (Moved inside CarWashSettingsApp class above)

  /**
   * Show QR code for 2FA setup
   */
  showQRCode(qrCodeUrl, secret) {
    const qrContainer = document.getElementById('qr-code-container');
    const secretDisplay = document.getElementById('secret-key');
    
    if (qrContainer) {
      qrContainer.innerHTML = `
        <div class="text-center">
          <img src="${qrCodeUrl}" alt="Two-factor authentication QR code" class="mx-auto mb-4 border rounded-lg">
          <p class="text-sm text-gray-600 mb-2">Scan this QR code with your authenticator app</p>
          <p class="text-xs text-gray-500">Or enter this key manually:</p>
        </div>
      `;
    }
    
    if (secretDisplay) {
      secretDisplay.value = secret;
    }
  }

  /**
   * Handle account deletion
   */
  async handleDeleteAccount(event) {
    event.preventDefault();
    
    const confirmationModal = this.createConfirmationModal(
      'Delete Account',
      'Are you sure you want to delete your account? This action cannot be undone.',
      'Delete Account',
      'danger'
    );
    
    const confirmed = await this.showModal(confirmationModal);
    
    if (confirmed) {
      try {
        this.setLoadingState(true, 'Deleting account...');
        
        const response = await this.makeAPIRequest(`${CONFIG.API_BASE_URL}/settings/delete_account.php`, {
          method: 'DELETE',
          headers: {
            'Content-Type': 'application/json'
          }
        });

        if (response.success) {
          this.showToast('Account deleted successfully. Redirecting...', 'success');
          setTimeout(() => {
            window.location.href = '/backend/auth/logout.php';
          }, 2000);
        } else {
          throw new Error(response.message || 'Account deletion failed');
        }
        
      } catch (error) {
        console.error('Account deletion error:', error);
        this.showToast('Failed to delete account. Please try again.', 'error');
      } finally {
        this.setLoadingState(false);
      }
    }
  }

  /**
   * Handle data export
   */
  async handleExportData(event) {
    event.preventDefault();
    
    try {
      this.setLoadingState(true, 'Preparing data export...');
      
      const response = await this.makeAPIRequest(`${CONFIG.API_BASE_URL}/settings/export_data.php`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        }
      });

      if (response.success) {
        // Create download link
        const blob = new Blob([JSON.stringify(response.data, null, 2)], {
          type: 'application/json'
        });
        
        const url = window.URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = `carwash-data-export-${new Date().toISOString().split('T')[0]}.json`;
        
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        window.URL.revokeObjectURL(url);
        
        this.showToast('Data exported successfully!', 'success');
        this.announceToScreenReader('Data exported and download started');
      } else {
        throw new Error(response.message || 'Data export failed');
      }
      
    } catch (error) {
      console.error('Data export error:', error);
      this.showToast('Failed to export data. Please try again.', 'error');
    } finally {
      this.setLoadingState(false);
    }
  }

  /**
   * Create confirmation modal
   */
  createConfirmationModal(title, message, confirmText, type = 'primary') {
    const modal = document.createElement('div');
    modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
    modal.setAttribute('role', 'dialog');
    modal.setAttribute('aria-modal', 'true');
    modal.setAttribute('aria-labelledby', 'modal-title');
    
    const buttonColors = {
      primary: 'bg-blue-600 hover:bg-blue-700 focus:ring-blue-500',
      danger: 'bg-red-600 hover:bg-red-700 focus:ring-red-500',
      warning: 'bg-yellow-600 hover:bg-yellow-700 focus:ring-yellow-500'
    };
    
    modal.innerHTML = `
      <div class="bg-white rounded-lg p-6 max-w-md mx-4 transform scale-95 opacity-0 transition-all duration-200">
        <div class="flex items-center justify-between mb-4">
          <h3 id="modal-title" class="text-lg font-bold text-gray-900">${title}</h3>
          <button type="button" class="modal-close text-gray-400 hover:text-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-500 rounded">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
          </button>
        </div>
        <p class="text-gray-700 mb-6">${message}</p>
        <div class="flex justify-end space-x-3">
          <button type="button" class="modal-cancel px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-500">
            Cancel
          </button>
          <button type="button" class="modal-confirm px-4 py-2 text-white rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 ${buttonColors[type]}">
            ${confirmText}
          </button>
        </div>
      </div>
    `;
    
    return modal;
  }

  /**
   * Show modal and return promise with user's choice
   */
  showModal(modal) {
    return new Promise((resolve) => {
      document.body.appendChild(modal);
      
      // Animate in
      requestAnimationFrame(() => {
        const content = modal.querySelector('div');
        content.style.transform = 'scale(1)';
        content.style.opacity = '1';
      });
      
      // Focus management
      const firstButton = modal.querySelector('button');
      if (firstButton) {
        firstButton.focus();
      }
      
      // Event handlers
      const handleClose = (confirmed = false) => {
        const content = modal.querySelector('div');
        content.style.transform = 'scale(0.95)';
        content.style.opacity = '0';
        
        setTimeout(() => {
          document.body.removeChild(modal);
          resolve(confirmed);
        }, 200);
      };
      
      modal.querySelector('.modal-confirm').addEventListener('click', () => handleClose(true));
      modal.querySelector('.modal-cancel').addEventListener('click', () => handleClose(false));
      modal.querySelector('.modal-close').addEventListener('click', () => handleClose(false));
      
      // Escape key handling
      const handleKeydown = (event) => {
        if (event.key === 'Escape') {
          handleClose(false);
          document.removeEventListener('keydown', handleKeydown);
        }
      };
      
      document.addEventListener('keydown', handleKeydown);
      
      // Click outside to close
      modal.addEventListener('click', (event) => {
        if (event.target === modal) {
          handleClose(false);
        }
      });
    });
  }

  /**
   * Lazy load section content
   */
  lazyLoadSection(section) {
    const sectionId = section.id.replace('-section', '');
    
    // Load section-specific resources
    switch (sectionId) {
      case 'security':
        this.loadSecurityFeatures();
        break;
      case 'notifications':
        this.loadNotificationHistory();
        break;
      case 'privacy':
        this.loadPrivacySettings();
        break;
      case 'data':
        this.loadDataManagementTools();
        break;
    }
    
    console.log(`Lazy loaded section: ${sectionId}`);
  }

  /**
   * Load security features
   */
  async loadSecurityFeatures() {
    try {
      const response = await this.makeAPIRequest(`${CONFIG.API_BASE_URL}/settings/security_status.php`);
      
      if (response.success) {
        this.updateSecurityStatus(response.data);
      }
    } catch (error) {
      console.error('Failed to load security features:', error);
    }
  }

  /**
   * Update security status display
   */
  updateSecurityStatus(data) {
    const lastLoginElement = document.getElementById('last-login');
    const activeSessionsElement = document.getElementById('active-sessions');
    const loginHistoryElement = document.getElementById('login-history');
    
    if (lastLoginElement && data.lastLogin) {
      lastLoginElement.textContent = new Date(data.lastLogin).toLocaleString();
    }
    
    if (activeSessionsElement && data.activeSessions) {
      activeSessionsElement.textContent = data.activeSessions.toString();
    }
    
    if (loginHistoryElement && data.recentLogins) {
      loginHistoryElement.innerHTML = data.recentLogins.map(login => `
        <div class="flex justify-between items-center py-2 border-b border-gray-200">
          <div>
            <p class="text-sm font-medium">${login.location || 'Unknown Location'}</p>
            <p class="text-xs text-gray-500">${new Date(login.timestamp).toLocaleString()}</p>
          </div>
          <span class="text-xs px-2 py-1 rounded-full ${login.status === 'success' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}">
            ${login.status}
          </span>
        </div>
      `).join('');
    }
  }

  /**
   * Load notification history
   */
  async loadNotificationHistory() {
    try {
      const response = await this.makeAPIRequest(`${CONFIG.API_BASE_URL}/settings/notification_history.php`);
      
      if (response.success) {
        this.updateNotificationHistory(response.data);
      }
    } catch (error) {
      console.error('Failed to load notification history:', error);
    }
  }

  /**
   * Update notification history display
   */
  updateNotificationHistory(notifications) {
    const historyContainer = document.getElementById('notification-history');
    
    if (historyContainer && notifications.length > 0) {
      historyContainer.innerHTML = notifications.map(notification => `
        <div class="flex items-start space-x-3 py-3 border-b border-gray-200">
          <div class="flex-shrink-0">
            <div class="w-8 h-8 rounded-full bg-blue-100 flex items-center justify-center">
              <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5 5v-5zm-6-3h5v-5l-5 5zM12 22a10 10 0 110-20 10 10 0 010 20z"></path>
              </svg>
            </div>
          </div>
          <div class="flex-1 min-w-0">
            <p class="text-sm font-medium text-gray-900">${notification.title}</p>
            <p class="text-sm text-gray-500">${notification.message}</p>
            <p class="text-xs text-gray-400 mt-1">${new Date(notification.timestamp).toLocaleString()}</p>
          </div>
          <div class="flex-shrink-0">
            <span class="text-xs px-2 py-1 rounded-full ${notification.read ? 'bg-gray-100 text-gray-800' : 'bg-blue-100 text-blue-800'}">
              ${notification.read ? 'Read' : 'Unread'}
            </span>
          </div>
        </div>
      `).join('');
    } else if (historyContainer) {
      historyContainer.innerHTML = '<p class="text-gray-500 text-center py-8">No notifications found.</p>';
    }
  }

  /**
   * Load privacy settings
   */
  async loadPrivacySettings() {
    try {
      const response = await this.makeAPIRequest(`${CONFIG.API_BASE_URL}/settings/privacy_status.php`);
      
      if (response.success) {
        this.updatePrivacySettings(response.data);
      }
    } catch (error) {
      console.error('Failed to load privacy settings:', error);
    }
  }

  /**
   * Update privacy settings display
   */
  updatePrivacySettings(data) {
    Object.entries(data).forEach(([key, value]) => {
      const element = document.querySelector(`[name="${key}"]`);
      if (element) {
        if (element.type === 'checkbox') {
          element.checked = Boolean(value);
        } else {
          element.value = value || '';
        }
      }
    });
  }

  /**
   * Load data management tools
   */
  async loadDataManagementTools() {
    try {
      const response = await this.makeAPIRequest(`${CONFIG.API_BASE_URL}/settings/data_summary.php`);
      
      if (response.success) {
        this.updateDataSummary(response.data);
      }
    } catch (error) {
      console.error('Failed to load data summary:', error);
    }
  }

  /**
   * Update data summary display
   */
  updateDataSummary(data) {
    const summaryContainer = document.getElementById('data-summary');
    
    if (summaryContainer) {
      summaryContainer.innerHTML = `
        <div class="grid grid-cols-2 gap-4">
          <div class="bg-gray-50 p-4 rounded-lg">
            <h4 class="text-sm font-medium text-gray-900">Account Created</h4>
            <p class="text-lg font-bold text-blue-600">${new Date(data.accountCreated).toLocaleDateString()}</p>
          </div>
          <div class="bg-gray-50 p-4 rounded-lg">
            <h4 class="text-sm font-medium text-gray-900">Data Size</h4>
            <p class="text-lg font-bold text-blue-600">${this.formatFileSize(data.dataSize)}</p>
          </div>
          <div class="bg-gray-50 p-4 rounded-lg">
            <h4 class="text-sm font-medium text-gray-900">Bookings</h4>
            <p class="text-lg font-bold text-blue-600">${data.totalBookings || 0}</p>
          </div>
          <div class="bg-gray-50 p-4 rounded-lg">
            <h4 class="text-sm font-medium text-gray-900">Last Activity</h4>
            <p class="text-lg font-bold text-blue-600">${new Date(data.lastActivity).toLocaleDateString()}</p>
          </div>
        </div>
      `;
    }
  }

  /**
   * Format file size for display
   */
  formatFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
  }

  /**
   * Pause non-critical operations
   */
  pauseNonCriticalOperations() {
    // Clear all debounce timers
    this.debounceTimers.forEach(timer => clearTimeout(timer));
    this.debounceTimers.clear();
    
    console.log('Non-critical operations paused');
  }

  /**
   * Resume operations
   */
  resumeOperations() {
    // Re-setup autosave
    this.setupAutosave('profileForm');
    
    console.log('Operations resumed');
  }

  /**
   * Handle resize events
   */
  handleResize() {
    // Close mobile menu on resize to desktop
    if (window.innerWidth >= CONFIG.MOBILE_BREAKPOINT && this.mobileMenuOpen) {
      this.closeMobileMenu();
    }
    
    // Update mobile/desktop specific features
    this.updateResponsiveFeatures();
  }

  /**
   * Update responsive features
   */
  updateResponsiveFeatures() {
    const isMobile = window.innerWidth < CONFIG.MOBILE_BREAKPOINT;
    
    // Update navigation behavior
    this.elements.navLinks.forEach(link => {
      if (isMobile) {
        link.setAttribute('data-mobile', 'true');
      } else {
        link.removeAttribute('data-mobile');
      }
    });
  }

  /**
   * Initialize complete validation system
   */
  initializeValidation() {
    // Setup real-time validation
    this.setupInputValidation();
    
    // Setup form validation indicators
    this.setupValidationIndicators();
  }

  /**
   * Setup validation indicators
   */
  setupValidationIndicators() {
    const forms = document.querySelectorAll('form');
    
    forms.forEach(form => {
      const indicator = document.createElement('div');
      indicator.className = 'form-validation-indicator hidden';
      indicator.innerHTML = `
        <div class="flex items-center space-x-2 text-sm">
          <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
          </svg>
          <span class="text-green-600 font-medium">All fields are valid</span>
        </div>
      `;
      
      form.appendChild(indicator);
    });
  }
}

// Initialize the app when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
  // Check if user prefers reduced motion
  const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  
  if (prefersReducedMotion) {
    document.documentElement.style.setProperty('--transition-duration', '0s');
  }
  
  // Initialize settings app
  window.carWashSettings = new CarWashSettingsApp();
});

// Handle service worker registration for PWA features
if ('serviceWorker' in navigator) {
  window.addEventListener('load', () => {
    navigator.serviceWorker.register('/sw.js')
      .then(registration => {
        console.log('SW registered: ', registration);
      })
      .catch(registrationError => {
        console.log('SW registration failed: ', registrationError);
      });
  });
}

// Export for module usage if needed
if (typeof module !== 'undefined' && module.exports) {
  module.exports = CarWashSettingsApp;
}
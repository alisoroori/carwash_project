/**
 * CarWash Main JavaScript File
 * Common functionality shared across multiple pages
 */

// Common utility functions
const CarWash = {
  // Initialize common functionality
  init: function() {
    this.initPasswordToggle();
    this.initInputAnimations();
    this.initButtonEffects();
    this.initSmoothScrolling();
    this.initMobileMenu();
    this.initBackToTop();
  },

  // Password toggle functionality
  initPasswordToggle: function() {
    const togglePassword = (inputId, toggleId) => {
      const passwordInput = document.getElementById(inputId);
      const toggleIcon = document.getElementById(toggleId);
      
      if (!passwordInput || !toggleIcon) return;

      if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        toggleIcon.classList.remove('fa-eye');
        toggleIcon.classList.add('fa-eye-slash');
      } else {
        passwordInput.type = 'password';
        toggleIcon.classList.remove('fa-eye-slash');
        toggleIcon.classList.add('fa-eye');
      }
    };

    // Make togglePassword available globally
    window.togglePassword = togglePassword;
  },

  // Enhanced input field animations
  initInputAnimations: function() {
    document.querySelectorAll('.input-field, input[type="text"], input[type="email"], input[type="password"], input[type="tel"], textarea').forEach(input => {
      input.addEventListener('focus', function() {
        this.style.borderColor = '#667eea';
        this.style.boxShadow = '0 0 0 3px rgba(102, 126, 234, 0.1)';
        this.style.transform = 'translateY(-1px)';
        this.style.transition = 'all 0.3s ease';
      });

      input.addEventListener('blur', function() {
        this.style.borderColor = '#e5e7eb';
        this.style.boxShadow = 'none';
        this.style.transform = 'translateY(0)';
      });
    });
  },

  // Button hover effects
  initButtonEffects: function() {
    document.querySelectorAll('.btn-primary, .btn-customer, .btn-carwash, button, .bg-blue-600, .bg-gradient-to-r').forEach(button => {
      button.addEventListener('mouseenter', function() {
        this.style.transform = 'translateY(-2px)';
        this.style.transition = 'all 0.3s ease';
      });

      button.addEventListener('mouseleave', function() {
        this.style.transform = 'translateY(0)';
      });
    });
  },

  // Smooth scrolling for navigation links
  initSmoothScrolling: function() {
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
      anchor.addEventListener('click', function (e) {
        e.preventDefault();
        const target = document.querySelector(this.getAttribute('href'));
        if (target) {
          // Close mobile menu if open
          const mobileMenu = document.getElementById('mobileMenu');
          if (mobileMenu && !mobileMenu.classList.contains('hidden')) {
            mobileMenu.classList.add('hidden');
          }
          
          target.scrollIntoView({
            behavior: 'smooth',
            block: 'start'
          });
        }
      });
    });
  },

  // Mobile menu functionality
  initMobileMenu: function() {
    // Toggle mobile menu function
    window.toggleMobileMenu = function() {
      const menu = document.getElementById('mobileMenu');
      if (menu) {
        menu.classList.toggle('hidden');
      }
    };

    // Close mobile menu when clicking outside
    document.addEventListener('click', function(event) {
      const mobileMenu = document.getElementById('mobileMenu');
      const menuButton = event.target.closest('button[onclick="toggleMobileMenu()"]');
      
      if (mobileMenu && !menuButton && !mobileMenu.contains(event.target)) {
        mobileMenu.classList.add('hidden');
      }
    });
  },

  // Back to top functionality
  initBackToTop: function() {
    // Scroll to top function
    window.scrollToTop = function() {
      window.scrollTo({
        top: 0,
        behavior: 'smooth'
      });
    };

    // Show/hide back to top button based on scroll position
    window.addEventListener('scroll', function() {
      const backToTopButton = document.querySelector('.back-to-top');
      if (backToTopButton) {
        if (window.pageYOffset > 300) {
          backToTopButton.classList.add('show');
        } else {
          backToTopButton.classList.remove('show');
        }
      }
    });
  },

  // Form validation utilities
  validateEmail: function(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
  },

  validatePhone: function(phone) {
    const phoneRegex = /^[0-9]{10,11}$/;
    return phoneRegex.test(phone.replace(/\s/g, ''));
  },

  // Show notification
  showNotification: function(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg transition-all duration-300 ${
      type === 'success' ? 'bg-green-500' : 
      type === 'error' ? 'bg-red-500' : 
      type === 'warning' ? 'bg-yellow-500' : 'bg-blue-500'
    } text-white`;
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
      notification.style.opacity = '0';
      setTimeout(() => {
        if (notification.parentNode) {
          notification.parentNode.removeChild(notification);
        }
      }, 300);
    }, 3000);
  },

  // Loading state utilities
  showLoading: function(element) {
    if (element) {
      element.disabled = true;
      element.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Yükleniyor...';
    }
  },

  hideLoading: function(element, originalText) {
    if (element) {
      element.disabled = false;
      element.innerHTML = originalText;
    }
  }
};

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
  CarWash.init();
  
  // Add animation delays to cards if they exist
  const cards = document.querySelectorAll('.card-hover');
  cards.forEach((card, index) => {
    card.style.animationDelay = `${index * 0.2}s`;
  });

  // Initialize back to top button visibility
  const backToTopButton = document.querySelector('.back-to-top');
  if (backToTopButton && window.pageYOffset > 300) {
    backToTopButton.classList.add('show');
  }
});

// Export for use in other scripts
if (typeof module !== 'undefined' && module.exports) {
  module.exports = CarWash;
}
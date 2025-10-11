/**
 * CarWash Services JavaScript
 * Handles interactions for the services page including mobile menu, service selection,
 * modal management, accessibility features, and performance optimization.
 * 
 * @version 1.0.0
 * @author CarWash Team
 */

'use strict';

// ========================================
// Configuration & Constants
// ========================================

const CONFIG = {
    // API Endpoints
    API_BASE_URL: '/backend',
    BOOKING_ENDPOINT: '/booking',
    
    // Animation Durations
    ANIMATION_DURATION: 300,
    TOAST_DURATION: 5000,
    MODAL_ANIMATION_DURATION: 200,
    
    // Breakpoints (matching Tailwind CSS)
    BREAKPOINTS: {
        sm: 640,
        md: 768,
        lg: 1024,
        xl: 1280
    },
    
    // Service Data
    SERVICES: {
        'basic-wash': {
            name: 'Basic Wash',
            price: 15,
            duration: '15-20 minutes',
            description: 'Essential exterior cleaning with soap, rinse, and dry.',
            features: [
                'Exterior wash & rinse',
                'Hand dry with clean towels',
                'Tire & wheel cleaning'
            ]
        },
        'premium-wash': {
            name: 'Premium Wash',
            price: 35,
            duration: '30-40 minutes',
            description: 'Complete interior and exterior cleaning with premium products.',
            features: [
                'Everything in Basic Wash',
                'Interior vacuuming',
                'Dashboard & console cleaning',
                'Window cleaning (inside & out)'
            ]
        },
        'deluxe-detail': {
            name: 'Deluxe Detail',
            price: 75,
            duration: '60-90 minutes',
            description: 'Complete auto detailing with waxing and paint protection.',
            features: [
                'Everything in Premium Wash',
                'Premium wax application',
                'Leather conditioning',
                'Engine bay cleaning'
            ]
        },
        'express-wash': {
            name: 'Express Wash',
            price: 10,
            duration: '5-10 minutes',
            description: 'Quick exterior rinse and dry for busy schedules.',
            features: [
                'Automated wash system',
                'Quick rinse & soap',
                'Air dry finish'
            ]
        },
        'interior-detail': {
            name: 'Interior Detail',
            price: 45,
            duration: '45-60 minutes',
            description: 'Deep interior cleaning focusing on all surfaces.',
            features: [
                'Deep vacuum cleaning',
                'Upholstery cleaning',
                'Carpet shampooing',
                'Air freshener treatment'
            ]
        },
        'wax-polish': {
            name: 'Wax & Polish',
            price: 55,
            duration: '40-50 minutes',
            description: 'Professional waxing and polishing for paint protection.',
            features: [
                'Paint decontamination',
                'Premium wax application',
                'Paint polishing',
                'UV protection coating'
            ]
        }
    }
};

// ========================================
// Utility Functions
// ========================================

const Utils = {
    /**
     * Debounce function to limit function calls
     */
    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    },

    /**
     * Throttle function to limit function calls
     */
    throttle(func, limit) {
        let inThrottle;
        return function(...args) {
            if (!inThrottle) {
                func.apply(this, args);
                inThrottle = true;
                setTimeout(() => inThrottle = false, limit);
            }
        };
    },

    /**
     * Get current viewport width
     */
    getViewportWidth() {
        return Math.max(document.documentElement.clientWidth || 0, window.innerWidth || 0);
    },

    /**
     * Check if element is in viewport
     */
    isInViewport(element) {
        const rect = element.getBoundingClientRect();
        return (
            rect.top >= 0 &&
            rect.left >= 0 &&
            rect.bottom <= (window.innerHeight || document.documentElement.clientHeight) &&
            rect.right <= (window.innerWidth || document.documentElement.clientWidth)
        );
    },

    /**
     * Smooth scroll to element
     */
    smoothScrollTo(target, offset = 0) {
        const element = typeof target === 'string' ? document.querySelector(target) : target;
        if (!element) return;

        const targetPosition = element.offsetTop - offset;
        const startPosition = window.pageYOffset;
        const distance = targetPosition - startPosition;
        const duration = Math.min(Math.abs(distance) / 2, 1000);
        let start = null;

        function animation(currentTime) {
            if (start === null) start = currentTime;
            const timeElapsed = currentTime - start;
            const run = easeInOutQuad(timeElapsed, startPosition, distance, duration);
            window.scrollTo(0, run);
            if (timeElapsed < duration) requestAnimationFrame(animation);
        }

        function easeInOutQuad(t, b, c, d) {
            t /= d / 2;
            if (t < 1) return c / 2 * t * t + b;
            t--;
            return -c / 2 * (t * (t - 2) - 1) + b;
        }

        requestAnimationFrame(animation);
    },

    /**
     * Generate unique ID
     */
    generateId() {
        return 'id-' + Math.random().toString(36).substr(2, 9);
    },

    /**
     * Sanitize HTML string
     */
    sanitizeHTML(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    },

    /**
     * Format currency
     */
    formatCurrency(amount) {
        return new Intl.NumberFormat('en-US', {
            style: 'currency',
            currency: 'USD'
        }).format(amount);
    }
};

// ========================================
// Accessibility Manager
// ========================================

class AccessibilityManager {
    constructor() {
        this.init();
    }

    init() {
        this.setupFocusManagement();
        this.setupKeyboardNavigation();
        this.setupScreenReaderAnnouncements();
        this.setupReducedMotion();
    }

    setupFocusManagement() {
        // Focus visible polyfill
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Tab') {
                document.body.classList.add('using-keyboard');
            }
        });

        document.addEventListener('mousedown', () => {
            document.body.classList.remove('using-keyboard');
        });

        // Skip link functionality
        const skipLink = document.querySelector('a[href="#main-content"]');
        if (skipLink) {
            skipLink.addEventListener('click', (e) => {
                e.preventDefault();
                const mainContent = document.querySelector('#main-content');
                if (mainContent) {
                    mainContent.focus();
                    mainContent.scrollIntoView({ behavior: 'smooth' });
                }
            });
        }
    }

    setupKeyboardNavigation() {
        // Service card navigation
        const serviceCards = document.querySelectorAll('.service-card button');
        serviceCards.forEach((button, index) => {
            button.addEventListener('keydown', (e) => {
                switch(e.key) {
                    case 'ArrowRight':
                    case 'ArrowDown':
                        e.preventDefault();
                        const nextIndex = (index + 1) % serviceCards.length;
                        serviceCards[nextIndex].focus();
                        break;
                    case 'ArrowLeft':
                    case 'ArrowUp':
                        e.preventDefault();
                        const prevIndex = (index - 1 + serviceCards.length) % serviceCards.length;
                        serviceCards[prevIndex].focus();
                        break;
                    case 'Home':
                        e.preventDefault();
                        serviceCards[0].focus();
                        break;
                    case 'End':
                        e.preventDefault();
                        serviceCards[serviceCards.length - 1].focus();
                        break;
                }
            });
        });

        // Modal navigation
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                this.closeModal();
            }
        });
    }

    setupScreenReaderAnnouncements() {
        this.announcementElement = document.getElementById('sr-announcements');
    }

    announce(message, priority = 'polite') {
        if (!this.announcementElement) return;
        
        this.announcementElement.setAttribute('aria-live', priority);
        this.announcementElement.textContent = message;
        
        // Clear after announcement
        setTimeout(() => {
            this.announcementElement.textContent = '';
        }, 1000);
    }

    setupReducedMotion() {
        const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
        
        if (prefersReducedMotion.matches) {
            document.documentElement.style.setProperty('--animation-duration', '0.01ms');
            document.documentElement.style.setProperty('--transition-duration', '0.01ms');
        }

        prefersReducedMotion.addEventListener('change', (e) => {
            if (e.matches) {
                document.documentElement.style.setProperty('--animation-duration', '0.01ms');
                document.documentElement.style.setProperty('--transition-duration', '0.01ms');
            } else {
                document.documentElement.style.removeProperty('--animation-duration');
                document.documentElement.style.removeProperty('--transition-duration');
            }
        });
    }

    closeModal() {
        const modal = document.getElementById('service-modal');
        if (modal && !modal.classList.contains('hidden')) {
            ServiceModal.close();
        }
    }
}

// ========================================
// Mobile Menu Manager
// ========================================

class MobileMenuManager {
    constructor() {
        this.menuButton = document.getElementById('mobile-menu-btn');
        this.menu = document.getElementById('mobile-menu');
        this.isOpen = false;
        this.init();
    }

    init() {
        if (!this.menuButton || !this.menu) return;

        this.menuButton.addEventListener('click', () => this.toggle());
        
        // Close menu when clicking outside
        document.addEventListener('click', (e) => {
            if (this.isOpen && !this.menuButton.contains(e.target) && !this.menu.contains(e.target)) {
                this.close();
            }
        });

        // Close menu on escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && this.isOpen) {
                this.close();
            }
        });

        // Close menu on resize to desktop
        window.addEventListener('resize', Utils.debounce(() => {
            if (Utils.getViewportWidth() >= CONFIG.BREAKPOINTS.md && this.isOpen) {
                this.close();
            }
        }, 250));
    }

    toggle() {
        this.isOpen ? this.close() : this.open();
    }

    open() {
        this.menu.classList.remove('hidden');
        this.menuButton.setAttribute('aria-expanded', 'true');
        this.isOpen = true;
        
        // Focus first menu item
        const firstMenuItem = this.menu.querySelector('a');
        if (firstMenuItem) {
            setTimeout(() => firstMenuItem.focus(), 100);
        }

        // Prevent body scroll
        document.body.style.overflow = 'hidden';
    }

    close() {
        this.menu.classList.add('hidden');
        this.menuButton.setAttribute('aria-expanded', 'false');
        this.isOpen = false;
        
        // Restore body scroll
        document.body.style.overflow = '';
    }
}

// ========================================
// Service Modal Manager
// ========================================

class ServiceModalManager {
    constructor() {
        this.modal = document.getElementById('service-modal');
        this.modalContent = document.getElementById('modal-content');
        this.modalTitle = document.getElementById('modal-title');
        this.closeButton = document.getElementById('close-modal');
        this.cancelButton = document.getElementById('modal-cancel');
        this.bookButton = document.getElementById('modal-book');
        this.currentService = null;
        this.init();
    }

    init() {
        if (!this.modal) return;

        // Close button events
        if (this.closeButton) {
            this.closeButton.addEventListener('click', () => this.close());
        }

        if (this.cancelButton) {
            this.cancelButton.addEventListener('click', () => this.close());
        }

        // Close on backdrop click
        this.modal.addEventListener('click', (e) => {
            if (e.target === this.modal) {
                this.close();
            }
        });

        // Service selection buttons
        document.addEventListener('click', (e) => {
            const serviceButton = e.target.closest('[data-service]');
            if (serviceButton) {
                e.preventDefault();
                const serviceId = serviceButton.getAttribute('data-service');
                this.open(serviceId);
            }
        });
    }

    open(serviceId) {
        const service = CONFIG.SERVICES[serviceId];
        if (!service) return;

        this.currentService = serviceId;
        this.renderModalContent(service);
        this.show();
    }

    renderModalContent(service) {
        if (!this.modalContent || !this.modalTitle) return;

        this.modalTitle.textContent = `Book ${service.name}`;

        const features = service.features.map(feature => 
            `<li class="flex items-center text-sm text-gray-600">
                <i class="fas fa-check text-green-500 mr-2 w-4" aria-hidden="true"></i>
                ${Utils.sanitizeHTML(feature)}
            </li>`
        ).join('');

        this.modalContent.innerHTML = `
            <div class="text-center mb-4">
                <div class="text-3xl font-bold text-blue-600 mb-2">
                    ${Utils.formatCurrency(service.price)}
                </div>
                <div class="text-sm text-gray-500 mb-4">
                    Duration: ${Utils.sanitizeHTML(service.duration)}
                </div>
                <p class="text-gray-600 mb-4">
                    ${Utils.sanitizeHTML(service.description)}
                </p>
            </div>
            
            <div class="bg-gray-50 rounded-lg p-4 mb-4">
                <h4 class="font-semibold text-gray-900 mb-3">What's Included:</h4>
                <ul class="space-y-2" role="list">
                    ${features}
                </ul>
            </div>
            
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-3">
                <div class="flex items-center">
                    <i class="fas fa-info-circle text-blue-600 mr-2"></i>
                    <p class="text-sm text-blue-800">
                        Click "Book Now" to proceed to our booking system where you can select your preferred time and location.
                    </p>
                </div>
            </div>
        `;

        // Update book button
        if (this.bookButton) {
            this.bookButton.href = `${CONFIG.BOOKING_ENDPOINT}?service=${this.currentService}`;
        }
    }

    show() {
        if (!this.modal) return;

        this.modal.classList.remove('hidden');
        
        // Animate modal
        const modalDialog = this.modal.querySelector('div > div');
        if (modalDialog) {
            modalDialog.classList.remove('scale-95', 'opacity-0');
            modalDialog.classList.add('scale-100', 'opacity-100');
        }

        // Focus management
        setTimeout(() => {
            if (this.closeButton) {
                this.closeButton.focus();
            }
        }, CONFIG.MODAL_ANIMATION_DURATION);

        // Prevent body scroll
        document.body.style.overflow = 'hidden';

        // Accessibility announcement
        if (window.accessibilityManager) {
            window.accessibilityManager.announce('Service selection dialog opened');
        }
    }

    close() {
        if (!this.modal) return;

        const modalDialog = this.modal.querySelector('div > div');
        
        if (modalDialog) {
            modalDialog.classList.remove('scale-100', 'opacity-100');
            modalDialog.classList.add('scale-95', 'opacity-0');
        }

        setTimeout(() => {
            this.modal.classList.add('hidden');
            document.body.style.overflow = '';
        }, CONFIG.MODAL_ANIMATION_DURATION);

        // Return focus to trigger button
        const triggerButton = document.querySelector(`[data-service="${this.currentService}"]`);
        if (triggerButton) {
            triggerButton.focus();
        }

        this.currentService = null;

        // Accessibility announcement
        if (window.accessibilityManager) {
            window.accessibilityManager.announce('Service selection dialog closed');
        }
    }
}

// ========================================
// Toast Notification Manager
// ========================================

class ToastManager {
    constructor() {
        this.container = document.getElementById('toast-container');
        this.toasts = new Map();
        this.init();
    }

    init() {
        if (!this.container) {
            this.createContainer();
        }
    }

    createContainer() {
        this.container = document.createElement('div');
        this.container.id = 'toast-container';
        this.container.className = 'fixed bottom-4 right-4 z-50 space-y-3';
        this.container.setAttribute('role', 'region');
        this.container.setAttribute('aria-label', 'Notifications');
        this.container.setAttribute('aria-live', 'polite');
        document.body.appendChild(this.container);
    }

    show(message, type = 'info', duration = CONFIG.TOAST_DURATION) {
        const toastId = Utils.generateId();
        const toast = this.createToast(toastId, message, type);
        
        this.container.appendChild(toast);
        this.toasts.set(toastId, toast);

        // Animate in
        requestAnimationFrame(() => {
            toast.classList.add('show');
        });

        // Auto dismiss
        if (duration > 0) {
            setTimeout(() => {
                this.dismiss(toastId);
            }, duration);
        }

        return toastId;
    }

    createToast(id, message, type) {
        const toast = document.createElement('div');
        toast.id = id;
        toast.className = `toast ${type} bg-white border-l-4 rounded-lg shadow-lg p-4 transform translate-x-full opacity-0 transition-all duration-300`;
        
        const iconMap = {
            success: 'fas fa-check-circle text-green-500',
            warning: 'fas fa-exclamation-triangle text-yellow-500',
            error: 'fas fa-times-circle text-red-500',
            info: 'fas fa-info-circle text-blue-500'
        };

        const borderColorMap = {
            success: 'border-green-500',
            warning: 'border-yellow-500',
            error: 'border-red-500',
            info: 'border-blue-500'
        };

        toast.classList.add(borderColorMap[type] || borderColorMap.info);

        toast.innerHTML = `
            <div class="flex items-start">
                <i class="${iconMap[type] || iconMap.info} mr-3 mt-0.5 flex-shrink-0" aria-hidden="true"></i>
                <div class="flex-1">
                    <p class="text-sm font-medium text-gray-900">${Utils.sanitizeHTML(message)}</p>
                </div>
                <button 
                    type="button" 
                    class="ml-4 text-gray-400 hover:text-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-500 rounded"
                    aria-label="Dismiss notification">
                    <i class="fas fa-times text-sm"></i>
                </button>
            </div>
        `;

        // Close button functionality
        const closeButton = toast.querySelector('button');
        closeButton.addEventListener('click', () => {
            this.dismiss(id);
        });

        return toast;
    }

    dismiss(toastId) {
        const toast = this.toasts.get(toastId);
        if (!toast) return;

        toast.classList.remove('show');
        toast.classList.add('translate-x-full', 'opacity-0');

        setTimeout(() => {
            if (toast.parentNode) {
                toast.parentNode.removeChild(toast);
            }
            this.toasts.delete(toastId);
        }, CONFIG.ANIMATION_DURATION);
    }

    dismissAll() {
        this.toasts.forEach((toast, id) => {
            this.dismiss(id);
        });
    }
}

// ========================================
// Scroll Effects Manager
// ========================================

class ScrollEffectsManager {
    constructor() {
        this.header = document.querySelector('header');
        this.scrollToTopButton = null;
        this.init();
    }

    init() {
        this.setupHeaderEffects();
        this.setupScrollToTop();
        this.setupSmoothScrolling();
        this.setupInViewportAnimations();
    }

    setupHeaderEffects() {
        if (!this.header) return;

        const handleScroll = Utils.throttle(() => {
            const scrolled = window.pageYOffset > 10;
            
            if (scrolled) {
                this.header.classList.add('shadow-lg');
                this.header.style.backgroundColor = 'rgba(255, 255, 255, 0.95)';
            } else {
                this.header.classList.remove('shadow-lg');
                this.header.style.backgroundColor = '';
            }
        }, 10);

        window.addEventListener('scroll', handleScroll);
    }

    setupScrollToTop() {
        // Create scroll to top button
        this.scrollToTopButton = document.createElement('button');
        this.scrollToTopButton.innerHTML = '<i class="fas fa-chevron-up"></i>';
        this.scrollToTopButton.className = 'fixed bottom-20 right-4 w-12 h-12 bg-blue-600 text-white rounded-full shadow-lg hover:bg-blue-700 focus:outline-none focus:ring-4 focus:ring-blue-300 transition-all transform scale-0 z-40';
        this.scrollToTopButton.setAttribute('aria-label', 'Scroll to top');
        this.scrollToTopButton.style.display = 'none';

        document.body.appendChild(this.scrollToTopButton);

        // Show/hide based on scroll position
        const handleScroll = Utils.throttle(() => {
            const scrolled = window.pageYOffset > 500;
            
            if (scrolled) {
                this.scrollToTopButton.style.display = 'block';
                requestAnimationFrame(() => {
                    this.scrollToTopButton.classList.remove('scale-0');
                    this.scrollToTopButton.classList.add('scale-100');
                });
            } else {
                this.scrollToTopButton.classList.remove('scale-100');
                this.scrollToTopButton.classList.add('scale-0');
                setTimeout(() => {
                    this.scrollToTopButton.style.display = 'none';
                }, CONFIG.ANIMATION_DURATION);
            }
        }, 100);

        window.addEventListener('scroll', handleScroll);

        // Click handler
        this.scrollToTopButton.addEventListener('click', () => {
            Utils.smoothScrollTo(document.body);
        });
    }

    setupSmoothScrolling() {
        // Smooth scroll for anchor links
        document.addEventListener('click', (e) => {
            const link = e.target.closest('a[href^="#"]');
            if (!link) return;

            const href = link.getAttribute('href');
            if (href === '#') return;

            const target = document.querySelector(href);
            if (!target) return;

            e.preventDefault();
            Utils.smoothScrollTo(target, 80); // Account for fixed header
        });
    }

    setupInViewportAnimations() {
        const animatedElements = document.querySelectorAll('.service-card, .addon-card');
        
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.animationDelay = `${Math.random() * 0.3}s`;
                    entry.target.classList.add('animate-fade-in-up');
                    observer.unobserve(entry.target);
                }
            });
        }, {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        });

        animatedElements.forEach(el => observer.observe(el));
    }
}

// ========================================
// Performance Monitor
// ========================================

class PerformanceMonitor {
    constructor() {
        this.metrics = {};
        this.init();
    }

    init() {
        this.measurePageLoad();
        this.setupImageLazyLoading();
        this.setupPreloading();
    }

    measurePageLoad() {
        window.addEventListener('load', () => {
            if ('performance' in window) {
                const perfData = window.performance.timing;
                const pageLoadTime = perfData.loadEventEnd - perfData.navigationStart;
                
                this.metrics.pageLoadTime = pageLoadTime;
                
                console.log(`Page load time: ${pageLoadTime}ms`);
                
                // Send metrics to analytics if needed
                this.sendMetrics();
            }
        });
    }

    setupImageLazyLoading() {
        const images = document.querySelectorAll('img[loading="lazy"]');
        
        if ('IntersectionObserver' in window) {
            const imageObserver = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        
                        // Add loading animation
                        img.parentElement.classList.add('loading');
                        
                        img.addEventListener('load', () => {
                            img.parentElement.classList.remove('loading');
                        });

                        imageObserver.unobserve(img);
                    }
                });
            });

            images.forEach(img => imageObserver.observe(img));
        }
    }

    setupPreloading() {
        // Preload critical images
        const criticalImages = [
            'images/basic-wash.jpg',
            'images/premium-wash.jpg'
        ];

        criticalImages.forEach(src => {
            const link = document.createElement('link');
            link.rel = 'preload';
            link.as = 'image';
            link.href = src;
            document.head.appendChild(link);
        });
    }

    sendMetrics() {
        // Send performance metrics to analytics
        // This would integrate with your analytics service
        if (this.metrics.pageLoadTime) {
            console.log('Performance metrics:', this.metrics);
        }
    }
}

// ========================================
// Main Application
// ========================================

class ServicesApp {
    constructor() {
        this.components = {};
        this.init();
    }

    init() {
        // Wait for DOM to be ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.initializeComponents());
        } else {
            this.initializeComponents();
        }
    }

    initializeComponents() {
        try {
            // Initialize core components
            this.components.accessibility = new AccessibilityManager();
            this.components.mobileMenu = new MobileMenuManager();
            this.components.serviceModal = new ServiceModalManager();
            this.components.toast = new ToastManager();
            this.components.scrollEffects = new ScrollEffectsManager();
            this.components.performance = new PerformanceMonitor();

            // Make components globally accessible
            window.accessibilityManager = this.components.accessibility;
            window.ServiceModal = this.components.serviceModal;
            window.Toast = this.components.toast;

            // Setup error handling
            this.setupErrorHandling();

            // Setup form validation if needed
            this.setupFormValidation();

            console.log('Services app initialized successfully');

        } catch (error) {
            console.error('Error initializing services app:', error);
            this.handleInitError(error);
        }
    }

    setupErrorHandling() {
        window.addEventListener('error', (event) => {
            console.error('JavaScript error:', event.error);
            
            // Show user-friendly error message
            if (this.components.toast) {
                this.components.toast.show(
                    'Something went wrong. Please refresh the page and try again.',
                    'error'
                );
            }
        });

        window.addEventListener('unhandledrejection', (event) => {
            console.error('Unhandled promise rejection:', event.reason);
        });
    }

    setupFormValidation() {
        // Add any form validation logic here if needed
        const forms = document.querySelectorAll('form');
        
        forms.forEach(form => {
            form.addEventListener('submit', (e) => {
                if (!this.validateForm(form)) {
                    e.preventDefault();
                }
            });
        });
    }

    validateForm(form) {
        const requiredFields = form.querySelectorAll('[required]');
        let isValid = true;

        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                isValid = false;
                field.classList.add('error');
                
                // Show error message
                const errorElement = document.getElementById(field.id + '-error');
                if (errorElement) {
                    errorElement.textContent = 'This field is required';
                    errorElement.classList.remove('hidden');
                }
            } else {
                field.classList.remove('error');
                
                // Hide error message
                const errorElement = document.getElementById(field.id + '-error');
                if (errorElement) {
                    errorElement.classList.add('hidden');
                }
            }
        });

        return isValid;
    }

    handleInitError(error) {
        // Fallback initialization for critical features
        console.warn('Falling back to basic functionality');
        
        // Basic mobile menu fallback
        const mobileMenuBtn = document.getElementById('mobile-menu-btn');
        const mobileMenu = document.getElementById('mobile-menu');
        
        if (mobileMenuBtn && mobileMenu) {
            mobileMenuBtn.addEventListener('click', () => {
                mobileMenu.classList.toggle('hidden');
            });
        }
    }
}

// ========================================
// CSS Animations (injected via JavaScript)
// ========================================

function injectAnimationStyles() {
    const styles = `
        @keyframes fade-in-up {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .animate-fade-in-up {
            animation: fade-in-up 0.6s ease-out forwards;
        }
        
        .loading::after {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.4), transparent);
            animation: loading 1.5s infinite;
        }
        
        @keyframes loading {
            to {
                left: 100%;
            }
        }
        
        .using-keyboard *:focus {
            outline: 2px solid #3b82f6 !important;
            outline-offset: 2px !important;
        }
        
        .toast.show {
            transform: translateX(0) !important;
            opacity: 1 !important;
        }
    `;

    const styleSheet = document.createElement('style');
    styleSheet.textContent = styles;
    document.head.appendChild(styleSheet);
}

// ========================================
// Initialize Application
// ========================================

// Inject animation styles
injectAnimationStyles();

// Initialize the main application
const app = new ServicesApp();

// Export for use in other scripts if needed
window.ServicesApp = ServicesApp;
window.CONFIG = CONFIG;
window.Utils = Utils;

// Service Worker registration (if available)
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js')
            .then(registration => {
                console.log('ServiceWorker registered:', registration);
            })
            .catch(error => {
                console.log('ServiceWorker registration failed:', error);
            });
    });
}
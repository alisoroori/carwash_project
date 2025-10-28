<?php
declare(strict_types=1);

namespace App\Classes;

class to individual cards
    const cards = document.querySelectorAll('.card:not(.universal-card)');
    cards.forEach(card => {
        card.classList.add('universal-card');
    });
}

// ========================================
// 8. IMAGE OPTIMIZATION
// ========================================

function optimizeImages() {
    const images = document.querySelectorAll('img');
    
    images.forEach(img => {
        // Ensure images don't exceed container width
        img.style.maxWidth = '100%';
        img.style.height = 'auto';
        
        // Add loading optimization
        if (!img.hasAttribute('loading')) {
            img.setAttribute('loading', 'lazy');
        }
    });
}

// ========================================
// 9. DASHBOARD SPECIFIC FIXES
// ========================================

function fixDashboardLayout() {
    const dashboard = document.querySelector('.dashboard-container, .dashboard');
    if (dashboard) {
        dashboard.classList.add('universal-dashboard');
        
        const sidebar = dashboard.querySelector('.sidebar, .dashboard-sidebar, aside');
        if (sidebar) {
            sidebar.classList.add('universal-sidebar');
        }
        
        const mainContent = dashboard.querySelector('.main-content, .dashboard-content, main');
        if (mainContent) {
            mainContent.classList.add('universal-main-content');
        }
    }
}

// ========================================
// 10. SMOOTH SCROLLING SETUP
// ========================================

function setupSmoothScrolling() {
    // Enable smooth scrolling for anchor links
    const anchorLinks = document.querySelectorAll('a[href^="#"]');
    
    anchorLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            const targetId = this.getAttribute('href');
            const targetElement = document.querySelector(targetId);
            
            if (targetElement) {
                e.preventDefault();
                targetElement.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
}

// ========================================
// 11. ACCESSIBILITY IMPROVEMENTS
// ========================================

function improveAccessibility() {
    // Add keyboard navigation for mobile menu
    const toggleBtn = document.querySelector('.universal-mobile-toggle');
    if (toggleBtn) {
        toggleBtn.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                this.click();
            }
        });
    }
    
    // Ensure focusable elements are properly accessible
    const focusableElements = document.querySelectorAll('button, a, input, select, textarea');
    focusableElements.forEach(element => {
        if (!element.hasAttribute('tabindex') && element.style.display !== 'none') {
            element.setAttribute('tabindex', '0');
        }
    });
}

// ========================================
// 12. PERFORMANCE OPTIMIZATIONS
// ========================================

function optimizePerformance() {
    // Debounce scroll events
    let scrollTimer;
    window.addEventListener('scroll', function() {
        clearTimeout(scrollTimer);
        scrollTimer = setTimeout(function() {
            // Add any scroll-based optimizations here
        }, 100);
    });
    
    // Optimize animations for reduced motion preference
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        const animatedElements = document.querySelectorAll('[class*="animate"]');
        animatedElements.forEach(element => {
            element.style.animation = 'none';
            element.style.transition = 'none';
        });
    }
}

// ========================================
// 13. ERROR HANDLING AND LOGGING
// ========================================

function setupErrorHandling() {
    // Log any JavaScript errors for debugging
    window.addEventListener('error', function(e) {
        console.group('CarWash Universal Script Error');
        console.error('Error:', e.error);
        console.error('File:', e.filename);
        console.error('Line:', e.lineno);
        console.groupEnd();
    });
    
    // Handle uncaught promise rejections
    window.addEventListener('unhandledrejection', function(e) {
        console.group('CarWash Universal Script Promise Rejection');
        console.error('Rejection:', e.reason);
        console.groupEnd();
    });
}

// ========================================
// 14. MAIN INITIALIZATION FUNCTION
// ========================================

function initializeUniversalFixes() {
    try {
        console.log('Initializing CarWash Universal Fixes...');
        
        // Core fixes
        initializeUniversalMobileMenu();
        handleUniversalResponsiveChanges();
        fixScrollbarIssues();
        cleanupDuplicateFooters();
        
        // Layout fixes
        adjustTableContainers();
        adjustFormElements();
        adjustCardLayouts();
        fixDashboardLayout();
        
        // Optimization fixes
        optimizeImages();
        setupSmoothScrolling();
        improveAccessibility();
        optimizePerformance();
        
        console.log('CarWash Universal Fixes initialized successfully!');
        
    } catch (error) {
        console.error('Error initializing CarWash Universal Fixes:', error);
    }
}

// ========================================
// 15. UTILITY FUNCTIONS
// ========================================

// Function to manually trigger fixes if needed
window.triggerUniversalFixes = function() {
    initializeUniversalFixes();
};

// Function to add universal mobile navigation to any page
window.addUniversalMobileNav = function(navItems = []) {
    const body = document.body;
    
    // Add mobile toggle button
    if (!document.querySelector('.universal-mobile-toggle')) {
        const toggleBtn = document.createElement('button');
        toggleBtn.className = 'universal-mobile-toggle';
        toggleBtn.innerHTML = '<i class="fas fa-bars"></i>';
        body.appendChild(toggleBtn);
    }
    
    // Add mobile navigation
    if (!document.querySelector('.universal-mobile-nav')) {
        const nav = document.createElement('nav');
        nav.className = 'universal-mobile-nav';
        
        const navContent = document.createElement('div');
        navContent.className = 'nav-content';
        
        // Add navigation items
        navItems.forEach(item => {
            const link = document.createElement('a');
            link.href = item.href || '#';
            link.className = 'nav-item';
            link.innerHTML = `${item.icon ? `<i class="${item.icon}"></i>` : ''}${item.text}`;
            navContent.appendChild(link);
        });
        
        nav.appendChild(navContent);
        body.appendChild(nav);
    }
    
    // Add overlay
    if (!document.querySelector('.universal-mobile-overlay')) {
        const overlay = document.createElement('div');
        overlay.className = 'universal-mobile-overlay';
        body.appendChild(overlay);
    }
    
    // Initialize the mobile menu
    initializeUniversalMobileMenu();
};

// ========================================
// 16. AUTOMATIC INITIALIZATION
// ========================================

// Initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', function() {
        initializeUniversalFixes();
        setupErrorHandling();
    });
} else {
    // DOM is already ready
    initializeUniversalFixes();
    setupErrorHandling();
}

// Initialize on page load (for any dynamically loaded content)
window.addEventListener('load', function() {
    // Run fixes again after all resources are loaded
    setTimeout(function() {
        fixScrollbarIssues();
        cleanupDuplicateFooters();
    }, 100);
});

console.log('CarWash Universal Scripts loaded successfully!');
</script>

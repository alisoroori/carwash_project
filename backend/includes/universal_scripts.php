<?php
/**
 * Universal JavaScript for CarWash Project
 * Fixes: Mobile navigation, scrollbar issues, footer cleanup, responsiveness
 * Apply to ALL pages in the project
 */
?>
<script>
/* UNIVERSAL JAVASCRIPT FIXES - CARWASH PROJECT */

// ========================================
// 1. MOBILE NAVIGATION FUNCTIONALITY
// ========================================

function initializeUniversalMobileMenu() {
    const toggleBtn = document.querySelector('.universal-mobile-toggle');
    const mobileNav = document.querySelector('.universal-mobile-nav');
    const overlay = document.querySelector('.universal-mobile-overlay');
    
    if (toggleBtn && mobileNav && overlay) {
        // Toggle mobile menu
        toggleBtn.addEventListener('click', function() {
            const isActive = mobileNav.classList.contains('active');
            
            if (isActive) {
                // Close menu
                mobileNav.classList.remove('active');
                overlay.classList.remove('active');
                toggleBtn.classList.remove('active');
                toggleBtn.innerHTML = '<i class="fas fa-bars"></i>';
                document.body.style.overflow = '';
            } else {
                // Open menu
                mobileNav.classList.add('active');
                overlay.classList.add('active');
                toggleBtn.classList.add('active');
                toggleBtn.innerHTML = '<i class="fas fa-times"></i>';
                document.body.style.overflow = 'hidden';
            }
        });
        
        // Close menu when clicking overlay
        overlay.addEventListener('click', function() {
            mobileNav.classList.remove('active');
            overlay.classList.remove('active');
            toggleBtn.classList.remove('active');
            toggleBtn.innerHTML = '<i class="fas fa-bars"></i>';
            document.body.style.overflow = '';
        });
        
        // Close menu when clicking nav links
        const navLinks = mobileNav.querySelectorAll('.nav-item');
        navLinks.forEach(link => {
            link.addEventListener('click', function() {
                mobileNav.classList.remove('active');
                overlay.classList.remove('active');
                toggleBtn.classList.remove('active');
                toggleBtn.innerHTML = '<i class="fas fa-bars"></i>';
                document.body.style.overflow = '';
            });
        });
    }
}

// ========================================
// 2. RESPONSIVE BEHAVIOR MANAGEMENT
// ========================================

function handleUniversalResponsiveChanges() {
    let resizeTimer;
    
    window.addEventListener('resize', function() {
        // Debounce resize events
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function() {
            // Close mobile menu on desktop
            if (window.innerWidth >= 1024) {
                const mobileNav = document.querySelector('.universal-mobile-nav');
                const overlay = document.querySelector('.universal-mobile-overlay');
                const toggleBtn = document.querySelector('.universal-mobile-toggle');
                
                if (mobileNav && overlay && toggleBtn) {
                    mobileNav.classList.remove('active');
                    overlay.classList.remove('active');
                    toggleBtn.classList.remove('active');
                    toggleBtn.innerHTML = '<i class="fas fa-bars"></i>';
                    document.body.style.overflow = '';
                }
            }
            
            // Fix any overflow issues that may have appeared
            fixScrollbarIssues();
            
            // Recalculate table containers
            adjustTableContainers();
            
        }, 250);
    });
}

// ========================================
// 3. SCROLLBAR ISSUES FIXES
// ========================================

function fixScrollbarIssues() {
    // Ensure body and html don't have horizontal scroll
    document.documentElement.style.overflowX = 'hidden';
    document.body.style.overflowX = 'hidden';
    
    // Fix any elements that might cause horizontal scroll
    const elements = document.querySelectorAll('*');
    elements.forEach(el => {
        const style = window.getComputedStyle(el);
        
        // Check for elements extending beyond viewport
        if (el.scrollWidth > el.clientWidth && style.overflowX !== 'hidden') {
            // Only apply if element doesn't need horizontal scroll
            const rect = el.getBoundingClientRect();
            if (rect.width <= window.innerWidth) {
                el.style.overflowX = 'hidden';
            }
        }
        
        // Fix elements with explicit overflow: scroll that don't need it
        if (style.overflowX === 'scroll' && el.scrollWidth <= el.clientWidth) {
            el.style.overflowX = 'hidden';
        }
    });
    
    // Special handling for common problematic elements
    const containers = document.querySelectorAll('.container, .main-content, .page-wrapper, .dashboard-container');
    containers.forEach(container => {
        container.style.overflowX = 'hidden';
        container.style.maxWidth = '100%';
    });
}

// ========================================
// 4. DUPLICATE FOOTER CLEANUP
// ========================================

function cleanupDuplicateFooters() {
    // Find all footer elements
    const footers = document.querySelectorAll('footer, .footer, .old-footer, .legacy-footer');
    
    if (footers.length > 1) {
        // Create array to track footers to remove
        const footersToRemove = [];
        
        // Identify which footers to keep/remove
        footers.forEach((footer, index) => {
            // Remove footers with specific old/legacy classes
            if (footer.classList.contains('old-footer') || 
                footer.classList.contains('legacy-footer') ||
                footer.id === 'old-footer' ||
                footer.id === 'legacy-footer') {
                footersToRemove.push(footer);
            }
            // Remove all but the last footer (keep newest)
            else if (index < footers.length - 1) {
                footersToRemove.push(footer);
            }
        });
        
        // Remove identified duplicate footers
        footersToRemove.forEach(footer => {
            console.log('Removing duplicate footer:', footer);
            footer.remove();
        });
    }
    
    // Ensure remaining footer doesn't cause overflow
    const remainingFooter = document.querySelector('footer');
    if (remainingFooter) {
        remainingFooter.style.maxWidth = '100%';
        remainingFooter.style.overflowX = 'hidden';
    }
}

// ========================================
// 5. TABLE RESPONSIVENESS
// ========================================

function adjustTableContainers() {
    const tables = document.querySelectorAll('table');
    
    tables.forEach(table => {
        // Wrap table in responsive container if not already wrapped
        if (!table.parentElement.classList.contains('universal-table-container') &&
            !table.parentElement.classList.contains('table-container')) {
            
            const wrapper = document.createElement('div');
            wrapper.className = 'universal-table-container';
            table.parentNode.insertBefore(wrapper, table);
            wrapper.appendChild(table);
        }
        
        // Ensure table doesn't exceed container width
        table.style.maxWidth = '100%';
    });
}

// ========================================
// 6. FORM RESPONSIVENESS
// ========================================

function adjustFormElements() {
    const forms = document.querySelectorAll('form');
    
    forms.forEach(form => {
        // Add universal classes to form inputs
        const inputs = form.querySelectorAll('input, select, textarea');
        inputs.forEach(input => {
            if (!input.classList.contains('universal-form-input')) {
                input.classList.add('universal-form-input');
            }
        });
        
        // Wrap form grids appropriately
        const formRows = form.querySelectorAll('.row, .form-row');
        formRows.forEach(row => {
            if (!row.classList.contains('universal-form-grid')) {
                row.classList.add('universal-form-grid');
            }
        });
    });
}

// ========================================
// 7. CARD LAYOUT FIXES
// ========================================

function adjustCardLayouts() {
    // Find card containers and apply universal classes
    const cardContainers = document.querySelectorAll('.card-grid, .cards, .grid');
    cardContainers.forEach(container => {
        if (!container.classList.contains('universal-card-grid')) {
            container.classList.add('universal-card-grid');
        }
    });
    
    // Apply universal card class to individual cards
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

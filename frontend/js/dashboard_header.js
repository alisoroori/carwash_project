/**
 * Dashboard Header JavaScript
 * Handles mobile menu, dropdown, scroll effects, and keyboard navigation
 */

(function() {
    'use strict';
    
    // ===================================
    // MOBILE MENU FUNCTIONALITY
    // ===================================
    
    const mobileMenuBtn = document.getElementById('mobileMenuBtn');
    const mobileMenuPanel = document.getElementById('mobileMenuPanel');
    const mobileMenuOverlay = document.getElementById('mobileMenuOverlay');
    const body = document.body;
    
    /**
     * Toggle mobile menu open/close
     */
    window.toggleMobileMenu = function() {
        const isOpen = !mobileMenuPanel.classList.contains('-translate-x-full');
        
        if (isOpen) {
            closeMobileMenu();
        } else {
            openMobileMenu();
        }
    };
    
    function openMobileMenu() {
        mobileMenuPanel.classList.remove('-translate-x-full');
        mobileMenuOverlay.classList.remove('hidden');
        body.classList.add('menu-open');
        body.style.overflow = 'hidden';
        // Make background inert for accessibility (if helper available)
        try { if (typeof window.setInertState === 'function') window.setInertState(true); } catch (e) {}
        
        // Focus trap
        mobileMenuPanel.querySelector('a, button').focus();
    }
    
    function closeMobileMenu() {
        mobileMenuPanel.classList.add('-translate-x-full');
        mobileMenuOverlay.classList.add('hidden');
        body.classList.remove('menu-open');
        body.style.overflow = '';
        try { if (typeof window.setInertState === 'function') window.setInertState(false); } catch (e) {}
        
        // Return focus to menu button
        mobileMenuBtn?.focus();
    }
    
    // Close menu on overlay click
    mobileMenuOverlay?.addEventListener('click', closeMobileMenu);
    
    // Close menu on escape key
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && !mobileMenuPanel.classList.contains('-translate-x-full')) {
            closeMobileMenu();
        }
    });
    
    // Close menu on window resize to desktop
    let resizeTimer;
    window.addEventListener('resize', () => {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(() => {
            if (window.innerWidth >= 1024) {
                closeMobileMenu();
            }
        }, 250);
    });
    
    // Close menu when clicking internal links
    mobileMenuPanel?.querySelectorAll('a').forEach(link => {
        link.addEventListener('click', () => {
            setTimeout(closeMobileMenu, 150);
        });
    });
    
    
    // ===================================
    // HEADER SCROLL EFFECT
    // ===================================
    
    const header = document.querySelector('header');
    let lastScroll = 0;
    
    window.addEventListener('scroll', () => {
        const currentScroll = window.pageYOffset;
        
        if (currentScroll > 50) {
            header?.classList.add('scrolled');
        } else {
            header?.classList.remove('scrolled');
        }
        
        lastScroll = currentScroll;
    }, { passive: true });
    
    
    // ===================================
    // DROPDOWN MENU (Alternative to Alpine.js)
    // ===================================
    
    // If not using Alpine.js, use this vanilla JS implementation
    const userMenuButton = document.querySelector('[aria-haspopup="true"]');
    const dropdownMenu = userMenuButton?.nextElementSibling;
    
    if (userMenuButton && dropdownMenu && !window.Alpine) {
        userMenuButton.addEventListener('click', (e) => {
            e.stopPropagation();
            const isOpen = dropdownMenu.style.display === 'block';
            
            if (isOpen) {
                dropdownMenu.style.display = 'none';
                userMenuButton.setAttribute('aria-expanded', 'false');
            } else {
                dropdownMenu.style.display = 'block';
                userMenuButton.setAttribute('aria-expanded', 'true');
            }
        });
        
        // Close dropdown when clicking outside
        document.addEventListener('click', (e) => {
            if (!userMenuButton.contains(e.target) && !dropdownMenu.contains(e.target)) {
                dropdownMenu.style.display = 'none';
                userMenuButton.setAttribute('aria-expanded', 'false');
            }
        });
        
        // Close dropdown on escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && dropdownMenu.style.display === 'block') {
                dropdownMenu.style.display = 'none';
                userMenuButton.setAttribute('aria-expanded', 'false');
                userMenuButton.focus();
            }
        });
    }
    
    
    // ===================================
    // ACTIVE NAV LINK DETECTION
    // ===================================
    
    const navLinks = document.querySelectorAll('.nav-link');
    const currentPath = window.location.hash || window.location.pathname;
    
    navLinks.forEach(link => {
        const href = link.getAttribute('href');
        if (href === currentPath || (href.includes('#') && currentPath.includes(href.split('#')[1]))) {
            link.classList.add('active');
        }
    });
    
    // Update active link on hash change
    window.addEventListener('hashchange', () => {
        const hash = window.location.hash;
        navLinks.forEach(link => {
            link.classList.remove('active');
            if (link.getAttribute('href').includes(hash)) {
                link.classList.add('active');
            }
        });
    });
    
    
    // ===================================
    // KEYBOARD NAVIGATION
    // ===================================
    
    // Tab trap in mobile menu
    const focusableElements = mobileMenuPanel?.querySelectorAll(
        'button, a, input, select, textarea, [tabindex]:not([tabindex="-1"])'
    );
    
    if (focusableElements) {
        const firstFocusable = focusableElements[0];
        const lastFocusable = focusableElements[focusableElements.length - 1];
        
        mobileMenuPanel?.addEventListener('keydown', (e) => {
            if (e.key === 'Tab') {
                if (e.shiftKey && document.activeElement === firstFocusable) {
                    e.preventDefault();
                    lastFocusable.focus();
                } else if (!e.shiftKey && document.activeElement === lastFocusable) {
                    e.preventDefault();
                    firstFocusable.focus();
                }
            }
        });
    }
    
    
    // ===================================
    // ACCESSIBILITY ANNOUNCEMENTS
    // ===================================
    
    const announcer = document.createElement('div');
    announcer.setAttribute('role', 'status');
    announcer.setAttribute('aria-live', 'polite');
    announcer.classList.add('sr-only');
    document.body.appendChild(announcer);
    
    function announce(message) {
        announcer.textContent = message;
        setTimeout(() => {
            announcer.textContent = '';
        }, 1000);
    }
    
    // Announce menu state changes
    const originalToggle = window.toggleMobileMenu;
    window.toggleMobileMenu = function() {
        originalToggle();
        const isOpen = !mobileMenuPanel.classList.contains('-translate-x-full');
        announce(isOpen ? 'Menu opened' : 'Menu closed');
    };
    
    
    // ===================================
    // INITIALIZATION LOG
    // ===================================
    
    console.log('✅ Dashboard Header JavaScript loaded successfully');
    console.log('📱 Mobile menu:', mobileMenuPanel ? 'Found' : 'Not found');
    console.log('👤 User menu:', userMenuButton ? 'Found' : 'Not found');
    console.log('🔗 Navigation links:', navLinks.length);
    
})();
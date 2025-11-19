// Alpine components registry for dashboard
document.addEventListener('alpine:init', function() {
    if (typeof Alpine === 'undefined') return;

    Alpine.data('customerDashboard', function() {
        return {
            mobileMenuOpen: false,
            currentSection: 'dashboard',
            // UI helpers used by forms in the dashboard
            loading: false,
            message: '',
            messageType: '',

            showMessage(msg, type = 'success') {
                this.message = msg;
                this.messageType = type;
                setTimeout(() => { this.message = ''; this.messageType = ''; }, 5000);
            },

            init() {
                // Called when Alpine initializes this component on the page
                if (typeof console !== 'undefined') console.log('customerDashboard factory loaded');

                // Manage mobile sidebar DOM state from within this Alpine component
                try {
                    const sidebar = document.getElementById('customer-sidebar');
                    const overlay = document.getElementById('mobileOverlay') || document.querySelector('.mobile-menu-backdrop-dashboard');

                    const applyOpenState = (isOpen) => {
                        if (!sidebar) return;
                        if (isOpen) {
                            sidebar.classList.add('mobile-open');
                            if (overlay) overlay.classList.add('active');
                            document.body.classList.add('menu-open');
                            // prevent body scroll on small screens
                            if (window.innerWidth < 768) {
                                const scrollY = window.scrollY || 0;
                                document.body.style.position = 'fixed';
                                document.body.style.top = `-${scrollY}px`;
                                document.body.style.width = '100%';
                                document.body.style.overflow = 'hidden';
                            }
                        } else {
                            sidebar.classList.remove('mobile-open');
                            if (overlay) overlay.classList.remove('active');
                            document.body.classList.remove('menu-open');
                            // restore body scroll
                            const top = document.body.style.top;
                            document.body.style.position = '';
                            document.body.style.top = '';
                            document.body.style.width = '';
                            document.body.style.overflow = '';
                            if (top) {
                                window.scrollTo(0, parseInt(top || '0') * -1);
                            }
                        }
                    };

                    // Watch for Alpine data changes
                    if (this && typeof this.$watch === 'function') {
                        this.$watch('mobileMenuOpen', (v) => {
                            try { applyOpenState(!!v); } catch (e) { /* ignore */ }
                        });
                    }

                    // Run initial state
                    applyOpenState(this.mobileMenuOpen);
                } catch (e) {
                    // Defensive: don't break dashboard if DOM isn't present
                }
            },

            toggleMobile() {
                this.mobileMenuOpen = !this.mobileMenuOpen;
            },

            showSection(sectionId) {
                this.currentSection = sectionId;
                // Try to call existing loader if present (backward compatibility)
                try {
                    if (sectionId === 'vehicles') {
                        if (typeof window.loadUserVehicles === 'function') {
                            window.loadUserVehicles();
                        } else if (typeof window.loadVehicles === 'function') {
                            window.loadVehicles();
                        }
                    }
                } catch (e) {
                    // ignore
                }
                // Close mobile menu on small screens
                if (window.innerWidth < 1024) {
                    this.mobileMenuOpen = false;
                }
            }
        };
    });

    // You can register additional components here, e.g., vehicleOperations if needed later
});

// Backward-compatible factory for x-data="customerDashboard()" usage
window.customerDashboard = function() {
    return {
        mobileMenuOpen: false,
        currentSection: 'dashboard',
        loading: false,
        message: '',
        messageType: '',

        showMessage(msg, type = 'success') {
            this.message = msg;
            this.messageType = type;
            setTimeout(() => { this.message = ''; this.messageType = ''; }, 5000);
        },

        init() {
            if (typeof console !== 'undefined') console.log('customerDashboard (fallback) loaded');
        },

        toggleMobile() {
            this.mobileMenuOpen = !this.mobileMenuOpen;
        },

        showSection(sectionId) {
            this.currentSection = sectionId;
            try {
                if (sectionId === 'vehicles') {
                    if (typeof window.loadUserVehicles === 'function') {
                        window.loadUserVehicles();
                    } else if (typeof window.loadVehicles === 'function') {
                        window.loadVehicles();
                    }
                }
            } catch (e) {
                // ignore
            }
            if (window.innerWidth < 1024) this.mobileMenuOpen = false;
        }
    };
};

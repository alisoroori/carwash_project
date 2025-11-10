// Alpine components registry for dashboard
document.addEventListener('alpine:init', function() {
    if (typeof Alpine === 'undefined') return;

    Alpine.data('customerDashboard', function() {
        return {
            mobileMenuOpen: false,
            currentSection: 'dashboard',

            init() {
                // Called when Alpine initializes this component on the page
                if (typeof console !== 'undefined') console.log('customerDashboard factory loaded');
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

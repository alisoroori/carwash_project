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

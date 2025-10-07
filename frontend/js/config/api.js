const API_CONFIG = {
    BASE_URL: '/carwash_project/backend/api',
    ENDPOINTS: {
        // Auth endpoints
        auth: {
            login: '/auth/login.php',
            register: '/auth/register.php',
            logout: '/auth/logout.php'
        },
        // Booking endpoints
        booking: {
            services: '/services/get_services.php',
            timeslots: '/booking/get_timeslots.php',
            create: '/booking/create.php',
            list: '/booking/list.php'
        },
        // Payment endpoints
        payment: {
            process: '/payment/process.php',
            verify: '/payment/verify.php'
        }
    },
    
    // Helper method to get full API URL
    getUrl(endpoint) {
        return this.BASE_URL + this.ENDPOINTS[endpoint];
    }
};
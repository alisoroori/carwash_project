class TestSuite {
    constructor() {
        this.tests = new Map();
        this.setupTests();
        this.setupEventListeners();
    }

    setupEventListeners() {
        document.getElementById('runAllTests').addEventListener('click', () => this.runAllTests());
        document.getElementById('clearResults').addEventListener('click', () => this.clearResults());
    }

    setupTests() {
        // Authentication Tests
        this.addTest('register', this.testRegistration);
        this.addTest('login', this.testLogin);
        
        // Booking Tests
        this.addTest('services', this.testServices);
        this.addTest('timeslots', this.testTimeSlots);
        this.addTest('booking', this.testBooking);
        
        // Payment Tests
        this.addTest('payment', this.testPayment);
        this.addTest('receipt', this.testReceipt);
        
        // Dashboard Tests
        this.addTest('adminDashboard', this.testAdminDashboard);
        this.addTest('carwashDashboard', this.testCarwashDashboard);
        this.addTest('customerDashboard', this.testCustomerDashboard);
        
        // Other Tests
        this.addTest('notifications', this.testNotifications);
        this.addTest('cms', this.testCMS);
    }

    addTest(name, testFunction) {
        this.tests.set(name, testFunction.bind(this));
    }

    async runAllTests() {
        this.clearResults();
        this.log('Starting all tests...', 'info');
        
        for (const [name, testFunction] of this.tests) {
            await this.runTest(name, testFunction);
        }
        
        this.log('All tests completed', 'info');
    }

    async runTest(name, testFunction) {
        const element = document.querySelector(`[data-test="${name}"] .test-status`);
        element.className = 'test-status running';
        element.textContent = 'Running';
        
        this.log(`Running test: ${name}`, 'info');
        
        try {
            await testFunction();
            element.className = 'test-status success';
            element.textContent = 'Success';
            this.log(`✓ Test passed: ${name}`, 'success');
        } catch (error) {
            element.className = 'test-status error';
            element.textContent = 'Failed';
            this.log(`✗ Test failed: ${name} - ${error.message}`, 'error');
        }
    }

    clearResults() {
        const statuses = document.querySelectorAll('.test-status');
        statuses.forEach(status => {
            status.className = 'test-status pending';
            status.textContent = 'Pending';
        });
        
        document.getElementById('testOutput').innerHTML = '';
    }

    log(message, type = 'info') {
        const output = document.getElementById('testOutput');
        const timestamp = new Date().toLocaleTimeString();
        const entry = document.createElement('div');
        entry.className = `log-entry ${type}`;
        entry.innerHTML = `<span class="log-timestamp">[${timestamp}]</span> ${message}`;
        output.appendChild(entry);
        output.scrollTop = output.scrollHeight;
    }

    // Test Implementation Methods
    async testRegistration() {
        const response = await this.makeRequest('/backend/api/auth/register.php', 'POST', {
            name: 'Test User',
            email: `test${Date.now()}@example.com`,
            password: 'Test123!',
            role: 'customer'
        });
        
        if (!response.success) {
            throw new Error('Registration failed');
        }
    }

    async testLogin() {
        const response = await this.makeRequest('/backend/api/auth/login.php', 'POST', {
            email: 'test@example.com',
            password: 'Test123!'
        });
        
        if (!response.success || !response.token) {
            throw new Error('Login failed');
        }
    }

    async testServices() {
        const response = await this.makeRequest('/backend/api/services/list.php');
        if (!response.success || !Array.isArray(response.services)) {
            throw new Error('Failed to fetch services');
        }
    }

    async testTimeSlots() {
        const tomorrow = new Date();
        tomorrow.setDate(tomorrow.getDate() + 1);
        
        const response = await this.makeRequest(
            `/backend/api/booking/timeslots.php?date=${tomorrow.toISOString().split('T')[0]}&service_id=1`
        );
        
        if (!response.success || !Array.isArray(response.slots)) {
            throw new Error('Failed to fetch time slots');
        }
    }

    async testBooking() {
        const response = await this.makeRequest('/backend/api/booking/create.php', 'POST', {
            service_id: 1,
            date: new Date().toISOString().split('T')[0],
            time_slot: '10:00'
        });
        
        if (!response.success) {
            throw new Error('Booking creation failed');
        }
    }

    async makeRequest(endpoint, method = 'GET', data = null) {
        try {
            const options = {
                method,
                headers: {
                    'Content-Type': 'application/json'
                }
            };

            if (data) {
                options.body = JSON.stringify(data);
            }

            const response = await fetch(`/carwash_project${endpoint}`, options);
            return await response.json();
        } catch (error) {
            throw new Error(`Request failed: ${error.message}`);
        }
    }
}

// Initialize test suite
document.addEventListener('DOMContentLoaded', () => {
    window.testSuite = new TestSuite();
});
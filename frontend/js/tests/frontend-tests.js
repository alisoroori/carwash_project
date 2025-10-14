class FrontendTester {
    constructor() {
        this.tests = [];
        this.results = {
            passed: 0,
            failed: 0,
            total: 0
        };
    }

    async testResponsiveness() {
        const breakpoints = [
            {width: 320, name: 'mobile'},
            {width: 768, name: 'tablet'},
            {width: 1024, name: 'desktop'}
        ];

        for (const bp of breakpoints) {
            window.innerWidth = bp.width;
            window.dispatchEvent(new Event('resize'));
            
            // Test navigation menu
            const nav = document.querySelector('.main-nav');
            const computed = window.getComputedStyle(nav);
            
            if (bp.width < 768 && computed.display !== 'none') {
                throw new Error(`Navigation should be hidden on ${bp.name}`);
            }
        }
    }

    async testForms() {
        const forms = document.querySelectorAll('form');
        for (const form of forms) {
            // Test required fields
            const required = form.querySelectorAll('[required]');
            for (const field of required) {
                field.value = '';
                field.dispatchEvent(new Event('change'));
                
                const error = field.parentElement.querySelector('.error-message');
                if (!error || !error.textContent) {
                    throw new Error(`Missing error message for required field: ${field.name}`);
                }
            }
        }
    }
}
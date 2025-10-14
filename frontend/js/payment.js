/**
 * CarWash Payment Processing JavaScript
 * Handles secure payment form validation, processing, and user experience
 * Following CarWash project conventions and PHP backend integration
 * 
 * @version 1.0.0
 * @author CarWash Team
 */

'use strict';

// ========================================
// Configuration & Constants
// ========================================

const PAYMENT_CONFIG = {
    // API Endpoints - Following CarWash project structure
    API_BASE: '/carwash_project/backend',
    ENDPOINTS: {
        GET_BOOKING: '/auth/get_booking.php',
        PROCESS_PAYMENT: '/auth/process_payment.php',
        VALIDATE_CARD: '/auth/validate_card.php',
        GENERATE_RECEIPT: '/auth/generate_receipt.php',
        EMAIL_RECEIPT: '/auth/email_receipt.php'
    },
    
    // Validation rules
    VALIDATION: {
        CARD_NUMBER_MIN: 13,
        CARD_NUMBER_MAX: 19,
        CVV_MIN: 3,
        CVV_MAX: 4,
        EXPIRY_MONTHS: 12,
        NAME_MIN_LENGTH: 2
    },
    
    // Card type patterns
    CARD_PATTERNS: {
        visa: /^4[0-9]{12}(?:[0-9]{3})?$/,
        mastercard: /^5[1-5][0-9]{14}$/,
        amex: /^3[47][0-9]{13}$/,
        discover: /^6(?:011|5[0-9]{2})[0-9]{12}$/
    },
    
    // Performance settings
    DEBOUNCE_DELAY: 300,
    REQUEST_TIMEOUT: 30000,
    VALIDATION_DELAY: 500,
    
    // Format settings
    CURRENCY_FORMAT: {
        locale: 'tr-TR',
        options: {
            style: 'currency',
            currency: 'TRY',
            minimumFractionDigits: 2
        }
    },
    
    // Error messages
    ERROR_MESSAGES: {
        CARD_NUMBER_INVALID: 'Geçersiz kart numarası',
        CARD_NUMBER_REQUIRED: 'Kart numarası zorunludur',
        EXPIRY_INVALID: 'Geçersiz son kullanma tarihi',
        EXPIRY_EXPIRED: 'Kartınızın süresi dolmuş',
        CVV_INVALID: 'Geçersiz CVV kodu',
        NAME_REQUIRED: 'Kart sahibi adı zorunludur',
        TERMS_REQUIRED: 'Kullanım şartlarını kabul etmelisiniz',
        NETWORK_ERROR: 'Bağlantı hatası. Lütfen tekrar deneyin.',
        PAYMENT_FAILED: 'Ödeme işlemi başarısız oldu',
        SESSION_EXPIRED: 'Oturumunuz sona erdi. Lütfen yeniden giriş yapın'
    }
};

// ========================================
// Utility Functions
// ========================================

const PaymentUtils = {
    /**
     * Debounce function to limit validation calls
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
     * Format currency for Turkish locale
     */
    formatCurrency(amount) {
        try {
            return new Intl.NumberFormat(
                PAYMENT_CONFIG.CURRENCY_FORMAT.locale,
                PAYMENT_CONFIG.CURRENCY_FORMAT.options
            ).format(amount);
        } catch (error) {
            console.error('Currency formatting error:', error);
            return `${amount} TL`;
        }
    },

    /**
     * Sanitize input to prevent XSS
     */
    sanitizeInput(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    },

    /**
     * Generate CSRF token for forms
     */
    async generateCSRFToken() {
        try {
            const response = await fetch(`${PAYMENT_CONFIG.API_BASE}/auth/get_csrf_token.php`, {
                method: 'GET',
                credentials: 'include'
            });
            const data = await response.json();
            return data.token || '';
        } catch (error) {
            console.error('CSRF token generation error:', error);
            return '';
        }
    },

    /**
     * Show loading state on element
     */
    showLoading(element, text = 'Yükleniyor...') {
        if (element) {
            element.classList.add('loading');
            element.setAttribute('aria-busy', 'true');
            if (element.tagName === 'BUTTON') {
                element.disabled = true;
            }
        }
    },

    /**
     * Hide loading state
     */
    hideLoading(element) {
        if (element) {
            element.classList.remove('loading');
            element.removeAttribute('aria-busy');
            if (element.tagName === 'BUTTON') {
                element.disabled = false;
            }
        }
    },

    /**
     * Format card number with spaces
     */
    formatCardNumber(value) {
        const cleaned = value.replace(/\s+/g, '').replace(/[^0-9]/gi, '');
        const matches = cleaned.match(/\d{4,16}/g);
        const match = matches && matches[0] || '';
        const parts = [];

        for (let i = 0, len = match.length; i < len; i += 4) {
            parts.push(match.substring(i, i + 4));
        }

        if (parts.length) {
            return parts.join(' ');
        } else {
            return cleaned;
        }
    },

    /**
     * Format expiry date as MM/YY
     */
    formatExpiryDate(value) {
        const cleaned = value.replace(/\D/g, '');
        if (cleaned.length >= 2) {
            return cleaned.substring(0, 2) + (cleaned.length > 2 ? '/' + cleaned.substring(2, 4) : '');
        }
        return cleaned;
    },

    /**
     * Detect card type from number
     */
    detectCardType(cardNumber) {
        const cleanNumber = cardNumber.replace(/\s/g, '');
        
        for (const [type, pattern] of Object.entries(PAYMENT_CONFIG.CARD_PATTERNS)) {
            if (pattern.test(cleanNumber)) {
                return type;
            }
        }
        
        // Basic detection for incomplete numbers
        if (cleanNumber.startsWith('4')) return 'visa';
        if (cleanNumber.startsWith('5')) return 'mastercard';
        if (cleanNumber.startsWith('3')) return 'amex';
        
        return 'unknown';
    },

    /**
     * Luhn algorithm for card validation
     */
    validateCardNumber(cardNumber) {
        const cleanNumber = cardNumber.replace(/\s/g, '');
        
        if (!/^\d+$/.test(cleanNumber)) return false;
        if (cleanNumber.length < PAYMENT_CONFIG.VALIDATION.CARD_NUMBER_MIN || 
            cleanNumber.length > PAYMENT_CONFIG.VALIDATION.CARD_NUMBER_MAX) return false;

        let sum = 0;
        let isEven = false;
        
        for (let i = cleanNumber.length - 1; i >= 0; i--) {
            let digit = parseInt(cleanNumber.charAt(i), 10);
            
            if (isEven) {
                digit *= 2;
                if (digit > 9) {
                    digit -= 9;
                }
            }
            
            sum += digit;
            isEven = !isEven;
        }
        
        return sum % 10 === 0;
    },

    /**
     * Validate expiry date
     */
    validateExpiryDate(expiryDate) {
        const match = expiryDate.match(/^(\d{2})\/(\d{2})$/);
        if (!match) return { valid: false, message: PAYMENT_CONFIG.ERROR_MESSAGES.EXPIRY_INVALID };
        
        const month = parseInt(match[1], 10);
        const year = parseInt(match[2], 10) + 2000;
        
        if (month < 1 || month > 12) {
            return { valid: false, message: PAYMENT_CONFIG.ERROR_MESSAGES.EXPIRY_INVALID };
        }
        
        const currentDate = new Date();
        const currentYear = currentDate.getFullYear();
        const currentMonth = currentDate.getMonth() + 1;
        
        if (year < currentYear || (year === currentYear && month < currentMonth)) {
            return { valid: false, message: PAYMENT_CONFIG.ERROR_MESSAGES.EXPIRY_EXPIRED };
        }
        
        return { valid: true };
    }
};

// ========================================
// Payment Manager Class
// ========================================

class PaymentManager {
    constructor() {
        this.bookingData = null;
        this.isProcessing = false;
        this.validationStates = {};
        this.cardType = 'unknown';
        
        this.init();
    }

    // ========================================
    // Initialization
    // ========================================

    async init() {
        try {
            await this.setupCSRFToken();
            await this.loadBookingData();
            this.setupEventListeners();
            this.setupFormValidation();
            
            console.log('Payment Manager initialized successfully');
        } catch (error) {
            console.error('Error initializing Payment Manager:', error);
            this.handleInitializationError(error);
        }
    }

    async setupCSRFToken() {
        const token = await PaymentUtils.generateCSRFToken();
        const csrfInput = document.getElementById('csrf_token');
        if (csrfInput) {
            csrfInput.value = token;
        }
    }

    setupEventListeners() {
        // Form submission
        const paymentForm = document.getElementById('paymentForm');
        if (paymentForm) {
            paymentForm.addEventListener('submit', (e) => this.handleFormSubmit(e));
        }

        // Card number input
        const cardNumberInput = document.getElementById('cardNumber');
        if (cardNumberInput) {
            cardNumberInput.addEventListener('input', this.handleCardNumberInput.bind(this));
            cardNumberInput.addEventListener('paste', this.handleCardNumberPaste.bind(this));
        }

        // Expiry date input
        const expiryDateInput = document.getElementById('expiryDate');
        if (expiryDateInput) {
            expiryDateInput.addEventListener('input', this.handleExpiryDateInput.bind(this));
        }

        // CVV input
        const cvvInput = document.getElementById('cvv');
        if (cvvInput) {
            cvvInput.addEventListener('input', this.handleCVVInput.bind(this));
        }

        // Cardholder name input
        const cardholderNameInput = document.getElementById('cardholderName');
        if (cardholderNameInput) {
            cardholderNameInput.addEventListener('input', this.handleCardholderNameInput.bind(this));
        }

        // Terms checkbox
        const termsCheckbox = document.getElementById('agreeTerms');
        if (termsCheckbox) {
            termsCheckbox.addEventListener('change', this.validateTermsAgreement.bind(this));
        }

        // Modal close events
        this.setupModalEvents();
    }

    setupModalEvents() {
        const modal = document.getElementById('receiptModal');
        if (!modal) return;

        // Close modal on backdrop click
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                this.closeReceiptModal();
            }
        });

        // Close modal on escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && !modal.classList.contains('hidden')) {
                this.closeReceiptModal();
            }
        });
    }

    setupFormValidation() {
        // Setup debounced validation functions
        this.debouncedValidateCardNumber = PaymentUtils.debounce(
            this.validateCardNumber.bind(this), 
            PAYMENT_CONFIG.VALIDATION_DELAY
        );
        
        this.debouncedValidateExpiryDate = PaymentUtils.debounce(
            this.validateExpiryDate.bind(this), 
            PAYMENT_CONFIG.VALIDATION_DELAY
        );
        
        this.debouncedValidateCVV = PaymentUtils.debounce(
            this.validateCVV.bind(this), 
            PAYMENT_CONFIG.VALIDATION_DELAY
        );
    }

    // ========================================
    // Data Loading
    // ========================================

    async loadBookingData() {
        try {
            // Get booking ID from URL parameters or form
            const urlParams = new URLSearchParams(window.location.search);
            const bookingId = urlParams.get('booking_id') || 
                             document.getElementById('bookingId')?.value;

            if (!bookingId) {
                throw new Error('Rezervasyon bilgisi bulunamadı');
            }

            const response = await this.fetchWithTimeout(
                `${PAYMENT_CONFIG.API_BASE}${PAYMENT_CONFIG.ENDPOINTS.GET_BOOKING}?id=${bookingId}`,
                {
                    method: 'GET',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    credentials: 'include'
                }
            );

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }

            const data = await response.json();

            if (data.success) {
                this.bookingData = data.booking;
                this.renderBookingSummary();
                this.updateTotalAmount();
                
                // Set booking ID in form
                const bookingIdInput = document.getElementById('bookingId');
                if (bookingIdInput) {
                    bookingIdInput.value = bookingId;
                }
            } else {
                throw new Error(data.error || 'Rezervasyon bilgileri yüklenemedi');
            }

        } catch (error) {
            console.error('Error loading booking data:', error);
            this.handleBookingLoadError(error);
        }
    }

    async fetchWithTimeout(url, options = {}) {
        const controller = new AbortController();
        const timeoutId = setTimeout(() => controller.abort(), PAYMENT_CONFIG.REQUEST_TIMEOUT);

        try {
            const response = await fetch(url, {
                ...options,
                signal: controller.signal
            });
            clearTimeout(timeoutId);
            return response;
        } catch (error) {
            clearTimeout(timeoutId);
            if (error.name === 'AbortError') {
                throw new Error('İstek zaman aşımına uğradı');
            }
            throw error;
        }
    }

    // ========================================
    // UI Rendering
    // ========================================

    renderBookingSummary() {
        const loadingElement = document.getElementById('bookingSummaryLoading');
        const detailsElement = document.getElementById('bookingDetails');
        
        if (!this.bookingData || !detailsElement) return;

        // Hide loading, show details
        if (loadingElement) loadingElement.classList.add('hidden');
        detailsElement.classList.remove('hidden');

        // Generate booking summary HTML
        detailsElement.innerHTML = `
            <div class="space-y-4">
                <!-- Car Wash Info -->
                <div class="bg-gray-50 rounded-lg p-4">
                    <h4 class="font-semibold text-gray-900 mb-2">Araç Yıkama</h4>
                    <p class="text-gray-700">${PaymentUtils.sanitizeInput(this.bookingData.carwash_name)}</p>
                    <p class="text-sm text-gray-500">${PaymentUtils.sanitizeInput(this.bookingData.carwash_address || '')}</p>
                </div>

                <!-- Service Info -->
                <div class="bg-gray-50 rounded-lg p-4">
                    <h4 class="font-semibold text-gray-900 mb-2">Hizmet</h4>
                    <p class="text-gray-700">${PaymentUtils.sanitizeInput(this.bookingData.service_name)}</p>
                    <p class="text-sm text-gray-500">${PaymentUtils.sanitizeInput(this.bookingData.service_description || '')}</p>
                </div>

                <!-- Appointment Info -->
                <div class="bg-gray-50 rounded-lg p-4">
                    <h4 class="font-semibold text-gray-900 mb-2">Randevu</h4>
                    <div class="space-y-1">
                        <p class="text-gray-700">
                            <i class="fas fa-calendar text-carwash-primary mr-2" aria-hidden="true"></i>
                            ${this.formatDate(this.bookingData.appointment_date)}
                        </p>
                        <p class="text-gray-700">
                            <i class="fas fa-clock text-carwash-primary mr-2" aria-hidden="true"></i>
                            ${this.bookingData.appointment_time}
                        </p>
                    </div>
                </div>

                <!-- Vehicle Info -->
                ${this.bookingData.vehicle_info ? `
                    <div class="bg-gray-50 rounded-lg p-4">
                        <h4 class="font-semibold text-gray-900 mb-2">Araç Bilgileri</h4>
                        <p class="text-gray-700">${PaymentUtils.sanitizeInput(this.bookingData.vehicle_info)}</p>
                    </div>
                ` : ''}

                <!-- Price Breakdown -->
                <div class="border-t pt-4">
                    <div class="space-y-2">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Hizmet Ücreti:</span>
                            <span class="font-medium">${PaymentUtils.formatCurrency(this.bookingData.service_price)}</span>
                        </div>
                        ${this.bookingData.discount_amount > 0 ? `
                            <div class="flex justify-between text-green-600">
                                <span>İndirim:</span>
                                <span>-${PaymentUtils.formatCurrency(this.bookingData.discount_amount)}</span>
                            </div>
                        ` : ''}
                        <div class="flex justify-between">
                            <span class="text-gray-600">KDV (%18):</span>
                            <span class="font-medium">${PaymentUtils.formatCurrency(this.bookingData.tax_amount)}</span>
                        </div>
                        <div class="flex justify-between font-bold text-lg border-t pt-2">
                            <span>Toplam:</span>
                            <span class="text-carwash-primary">${PaymentUtils.formatCurrency(this.bookingData.total_amount)}</span>
                        </div>
                    </div>
                </div>
            </div>
        `;

        this.announceToScreenReader('Rezervasyon özeti yüklendi');
    }

    updateTotalAmount() {
        const totalAmountElement = document.getElementById('totalAmount');
        if (totalAmountElement && this.bookingData) {
            totalAmountElement.textContent = PaymentUtils.formatCurrency(this.bookingData.total_amount);
        }
    }

    formatDate(dateString) {
        try {
            const date = new Date(dateString);
            return date.toLocaleDateString('tr-TR', {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
        } catch (error) {
            return dateString;
        }
    }

    // ========================================
    // Input Handlers
    // ========================================

    handleCardNumberInput(e) {
        const input = e.target;
        const formatted = PaymentUtils.formatCardNumber(input.value);
        
        // Update input value
        input.value = formatted;
        
        // Detect and update card type
        this.updateCardType(formatted);
        
        // Validate card number
        this.debouncedValidateCardNumber();
    }

    handleCardNumberPaste(e) {
        e.preventDefault();
        const pastedText = (e.clipboardData || window.clipboardData).getData('text');
        const formatted = PaymentUtils.formatCardNumber(pastedText);
        e.target.value = formatted;
        this.updateCardType(formatted);
        this.debouncedValidateCardNumber();
    }

    handleExpiryDateInput(e) {
        const input = e.target;
        const formatted = PaymentUtils.formatExpiryDate(input.value);
        input.value = formatted;
        this.debouncedValidateExpiryDate();
    }

    handleCVVInput(e) {
        const input = e.target;
        const cleaned = input.value.replace(/\D/g, '');
        const maxLength = this.cardType === 'amex' ? 4 : 3;
        input.value = cleaned.substring(0, maxLength);
        this.debouncedValidateCVV();
    }

    handleCardholderNameInput(e) {
        const input = e.target;
        // Allow only letters, spaces, and common name characters
        input.value = input.value.replace(/[^a-zA-ZğüşıöçĞÜŞİÖÇ\s]/g, '');
        this.validateCardholderName();
    }

    updateCardType(cardNumber) {
        const newCardType = PaymentUtils.detectCardType(cardNumber);
        this.cardType = newCardType;
        
        const cardTypeIcon = document.getElementById('cardTypeIcon');
        if (cardTypeIcon) {
            const iconElement = cardTypeIcon.querySelector('i');
            if (iconElement) {
                iconElement.className = this.getCardTypeIcon(newCardType);
            }
        }
    }

    getCardTypeIcon(cardType) {
        const icons = {
            visa: 'fab fa-cc-visa text-xl text-blue-600',
            mastercard: 'fab fa-cc-mastercard text-xl text-red-500',
            amex: 'fab fa-cc-amex text-xl text-blue-500',
            discover: 'fab fa-cc-discover text-xl text-orange-500',
            unknown: 'fas fa-credit-card text-xl text-gray-400'
        };
        return icons[cardType] || icons.unknown;
    }

    // ========================================
    // Form Validation
    // ========================================

    validateCardNumber() {
        const input = document.getElementById('cardNumber');
        const errorElement = document.getElementById('cardNumber-error');
        
        if (!input) return false;
        
        const cardNumber = input.value.replace(/\s/g, '');
        
        if (!cardNumber) {
            this.showFieldError(input, errorElement, PAYMENT_CONFIG.ERROR_MESSAGES.CARD_NUMBER_REQUIRED);
            return false;
        }
        
        if (!PaymentUtils.validateCardNumber(cardNumber)) {
            this.showFieldError(input, errorElement, PAYMENT_CONFIG.ERROR_MESSAGES.CARD_NUMBER_INVALID);
            return false;
        }
        
        this.hideFieldError(input, errorElement);
        this.validationStates.cardNumber = true;
        return true;
    }

    validateExpiryDate() {
        const input = document.getElementById('expiryDate');
        const errorElement = document.getElementById('expiryDate-error');
        
        if (!input) return false;
        
        const expiryDate = input.value;
        const validation = PaymentUtils.validateExpiryDate(expiryDate);
        
        if (!validation.valid) {
            this.showFieldError(input, errorElement, validation.message);
            return false;
        }
        
        this.hideFieldError(input, errorElement);
        this.validationStates.expiryDate = true;
        return true;
    }

    validateCVV() {
        const input = document.getElementById('cvv');
        const errorElement = document.getElementById('cvv-error');
        
        if (!input) return false;
        
        const cvv = input.value;
        const expectedLength = this.cardType === 'amex' ? 4 : 3;
        
        if (!cvv || cvv.length !== expectedLength) {
            this.showFieldError(input, errorElement, PAYMENT_CONFIG.ERROR_MESSAGES.CVV_INVALID);
            return false;
        }
        
        this.hideFieldError(input, errorElement);
        this.validationStates.cvv = true;
        return true;
    }

    validateCardholderName() {
        const input = document.getElementById('cardholderName');
        const errorElement = document.getElementById('cardholderName-error');
        
        if (!input) return false;
        
        const name = input.value.trim();
        
        if (!name || name.length < PAYMENT_CONFIG.VALIDATION.NAME_MIN_LENGTH) {
            this.showFieldError(input, errorElement, PAYMENT_CONFIG.ERROR_MESSAGES.NAME_REQUIRED);
            return false;
        }
        
        this.hideFieldError(input, errorElement);
        this.validationStates.cardholderName = true;
        return true;
    }

    validateTermsAgreement() {
        const checkbox = document.getElementById('agreeTerms');
        
        if (!checkbox) return false;
        
        const isChecked = checkbox.checked;
        this.validationStates.terms = isChecked;
        
        if (!isChecked) {
            this.showNotification(PAYMENT_CONFIG.ERROR_MESSAGES.TERMS_REQUIRED, 'error');
        }
        
        return isChecked;
    }

    validateForm() {
        const validations = [
            this.validateCardNumber(),
            this.validateExpiryDate(),
            this.validateCVV(),
            this.validateCardholderName(),
            this.validateTermsAgreement()
        ];
        
        return validations.every(validation => validation === true);
    }

    showFieldError(input, errorElement, message) {
        if (input) {
            input.classList.add('border-red-500', 'bg-red-50');
            input.classList.remove('border-gray-300');
        }
        
        if (errorElement) {
            errorElement.textContent = message;
            errorElement.classList.remove('hidden');
        }
        
        this.announceToScreenReader(`Hata: ${message}`);
    }

    hideFieldError(input, errorElement) {
        if (input) {
            input.classList.remove('border-red-500', 'bg-red-50');
            input.classList.add('border-gray-300');
        }
        
        if (errorElement) {
            errorElement.classList.add('hidden');
        }
    }

    // ========================================
    // Payment Processing
    // ========================================

    async handleFormSubmit(e) {
        e.preventDefault();
        
        if (this.isProcessing) return;
        
        if (!this.validateForm()) {
            this.showNotification('Lütfen form hatalarını düzeltin', 'error');
            return;
        }
        
        await this.processPayment();
    }

    async processPayment() {
        try {
            this.isProcessing = true;
            this.showPaymentLoading();
            
            const formData = this.getFormData();
            
            const response = await this.fetchWithTimeout(
                `${PAYMENT_CONFIG.API_BASE}${PAYMENT_CONFIG.ENDPOINTS.PROCESS_PAYMENT}`,
                {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify(formData),
                    credentials: 'include'
                }
            );

            const data = await response.json();

            if (data.success) {
                await this.handlePaymentSuccess(data);
            } else {
                throw new Error(data.error || PAYMENT_CONFIG.ERROR_MESSAGES.PAYMENT_FAILED);
            }

        } catch (error) {
            console.error('Payment processing error:', error);
            this.handlePaymentError(error);
        } finally {
            this.isProcessing = false;
            this.hidePaymentLoading();
        }
    }

    getFormData() {
        return {
            booking_id: document.getElementById('bookingId')?.value,
            csrf_token: document.getElementById('csrf_token')?.value,
            card_number: document.getElementById('cardNumber')?.value.replace(/\s/g, ''),
            expiry_date: document.getElementById('expiryDate')?.value,
            cvv: document.getElementById('cvv')?.value,
            cardholder_name: document.getElementById('cardholderName')?.value.trim(),
            amount: this.bookingData?.total_amount,
            currency: 'TRY'
        };
    }

    async handlePaymentSuccess(data) {
        this.showNotification('Ödeme işlemi başarıyla tamamlandı!', 'success');
        
        // Generate and show receipt
        await this.generateReceipt(data.payment_id);
        
        // Redirect to success page after showing receipt
        setTimeout(() => {
            window.location.href = `/carwash_project/frontend/payment_success.html?payment_id=${data.payment_id}`;
        }, 3000);
    }

    handlePaymentError(error) {
        let errorMessage = PAYMENT_CONFIG.ERROR_MESSAGES.PAYMENT_FAILED;
        
        if (error.message.includes('session') || error.message.includes('Session')) {
            errorMessage = PAYMENT_CONFIG.ERROR_MESSAGES.SESSION_EXPIRED;
        } else if (error.message.includes('network') || error.message.includes('fetch')) {
            errorMessage = PAYMENT_CONFIG.ERROR_MESSAGES.NETWORK_ERROR;
        } else if (error.message) {
            errorMessage = error.message;
        }
        
        this.showNotification(errorMessage, 'error');
    }

    showPaymentLoading() {
        const button = document.getElementById('payButton');
        const buttonText = document.getElementById('payButtonText');
        const buttonLoading = document.getElementById('payButtonLoading');
        
        if (button) {
            button.disabled = true;
            PaymentUtils.showLoading(button);
        }
        
        if (buttonText) buttonText.classList.add('hidden');
        if (buttonLoading) buttonLoading.classList.remove('hidden');
        
        this.announceToScreenReader('Ödeme işlemi gerçekleştiriliyor');
    }

    hidePaymentLoading() {
        const button = document.getElementById('payButton');
        const buttonText = document.getElementById('payButtonText');
        const buttonLoading = document.getElementById('payButtonLoading');
        
        if (button) {
            button.disabled = false;
            PaymentUtils.hideLoading(button);
        }
        
        if (buttonText) buttonText.classList.remove('hidden');
        if (buttonLoading) buttonLoading.classList.add('hidden');
    }

    // ========================================
    // Receipt Management
    // ========================================

    async generateReceipt(paymentId) {
        try {
            const response = await this.fetchWithTimeout(
                `${PAYMENT_CONFIG.API_BASE}${PAYMENT_CONFIG.ENDPOINTS.GENERATE_RECEIPT}?payment_id=${paymentId}`,
                {
                    method: 'GET',
                    credentials: 'include'
                }
            );

            const data = await response.json();

            if (data.success) {
                this.renderReceipt(data.receipt);
                this.openReceiptModal();
            }

        } catch (error) {
            console.error('Receipt generation error:', error);
        }
    }

    renderReceipt(receiptData) {
        const receiptContent = document.getElementById('receiptContent');
        const receiptDateTime = document.getElementById('receiptDateTime');
        
        if (!receiptContent || !receiptData) return;

        // Update receipt date/time
        if (receiptDateTime) {
            receiptDateTime.textContent = this.formatDate(receiptData.payment_date);
        }

        // Generate receipt content
        receiptContent.innerHTML = `
            <div class="space-y-6">
                <!-- Payment Info -->
                <div class="bg-gray-50 rounded-lg p-4">
                    <h5 class="font-semibold text-gray-900 mb-3">Ödeme Bilgileri</h5>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-600">İşlem No:</span>
                            <span class="font-medium">${receiptData.transaction_id}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Ödeme Yöntemi:</span>
                            <span class="font-medium">**** **** **** ${receiptData.card_last_four}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Durum:</span>
                            <span class="font-medium text-green-600">Başarılı</span>
                        </div>
                    </div>
                </div>

                <!-- Service Details -->
                <div class="bg-gray-50 rounded-lg p-4">
                    <h5 class="font-semibold text-gray-900 mb-3">Hizmet Detayları</h5>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Araç Yıkama:</span>
                            <span class="font-medium">${receiptData.carwash_name}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Hizmet:</span>
                            <span class="font-medium">${receiptData.service_name}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Randevu:</span>
                            <span class="font-medium">${this.formatDate(receiptData.appointment_date)} ${receiptData.appointment_time}</span>
                        </div>
                    </div>
                </div>

                <!-- Amount Breakdown -->
                <div class="bg-gray-50 rounded-lg p-4">
                    <h5 class="font-semibold text-gray-900 mb-3">Tutar Detayları</h5>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Hizmet Ücreti:</span>
                            <span class="font-medium">${PaymentUtils.formatCurrency(receiptData.service_amount)}</span>
                        </div>
                        ${receiptData.discount_amount > 0 ? `
                            <div class="flex justify-between text-green-600">
                                <span>İndirim:</span>
                                <span>-${PaymentUtils.formatCurrency(receiptData.discount_amount)}</span>
                            </div>
                        ` : ''}
                        <div class="flex justify-between">
                            <span class="text-gray-600">KDV:</span>
                            <span class="font-medium">${PaymentUtils.formatCurrency(receiptData.tax_amount)}</span>
                        </div>
                        <div class="flex justify-between font-bold text-lg border-t pt-2">
                            <span>Toplam:</span>
                            <span class="text-carwash-primary">${PaymentUtils.formatCurrency(receiptData.total_amount)}</span>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    openReceiptModal() {
        const modal = document.getElementById('receiptModal');
        if (modal) {
            modal.classList.remove('hidden');
            
            // Focus management
            const firstFocusable = modal.querySelector('button');
            if (firstFocusable) {
                setTimeout(() => firstFocusable.focus(), 100);
            }
            
            // Prevent body scroll
            document.body.style.overflow = 'hidden';
            
            this.announceToScreenReader('Ödeme makbuzu görüntüleniyor');
        }
    }

    closeReceiptModal() {
        const modal = document.getElementById('receiptModal');
        if (modal) {
            modal.classList.add('hidden');
            
            // Restore body scroll
            document.body.style.overflow = '';
        }
    }

    // ========================================
    // Error Handling
    // ========================================

    handleInitializationError(error) {
        const mainContent = document.getElementById('main-content');
        if (mainContent) {
            mainContent.innerHTML = `
                <div class="text-center py-12">
                    <div class="text-red-500 text-6xl mb-6">
                        <i class="fas fa-exclamation-triangle" aria-hidden="true"></i>
                    </div>
                    <h2 class="text-2xl font-bold text-gray-900 mb-4">Sayfa Yüklenemedi</h2>
                    <p class="text-gray-600 mb-6">${error.message || 'Bir hata oluştu'}</p>
                    <button onclick="window.location.reload()" 
                            class="bg-carwash-primary text-white px-6 py-3 rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-carwash-primary focus:ring-offset-2 transition-colors duration-200">
                        <i class="fas fa-redo mr-2" aria-hidden="true"></i>
                        Sayfayı Yenile
                    </button>
                </div>
            `;
        }
    }

    handleBookingLoadError(error) {
        const summarySection = document.querySelector('.booking-summary');
        if (summarySection) {
            summarySection.innerHTML = `
                <div class="text-center py-8">
                    <div class="text-yellow-500 text-4xl mb-4">
                        <i class="fas fa-exclamation-triangle" aria-hidden="true"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Rezervasyon Yüklenemedi</h3>
                    <p class="text-gray-600 mb-4">${error.message}</p>
                    <button onclick="paymentManager.loadBookingData()" 
                            class="bg-carwash-primary text-white px-4 py-2 rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-carwash-primary focus:ring-offset-2 transition-colors duration-200">
                        <i class="fas fa-redo mr-2" aria-hidden="true"></i>
                        Tekrar Dene
                    </button>
                </div>
            `;
        }
    }

    // ========================================
    // Notifications & Accessibility
    // ========================================

    showNotification(message, type = 'info') {
        // Remove existing notifications
        const existingNotifications = document.querySelectorAll('.payment-notification');
        existingNotifications.forEach(notification => notification.remove());

        const notification = document.createElement('div');
        notification.className = `payment-notification fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg max-w-sm transition-all duration-300 transform translate-x-full`;
        
        const colors = {
            success: 'bg-green-500 text-white',
            error: 'bg-red-500 text-white',
            warning: 'bg-yellow-500 text-white',
            info: 'bg-blue-500 text-white'
        };
        
        notification.className += ` ${colors[type] || colors.info}`;
        
        const icons = {
            success: 'fas fa-check-circle',
            error: 'fas fa-exclamation-circle',
            warning: 'fas fa-exclamation-triangle',
            info: 'fas fa-info-circle'
        };
        
        notification.innerHTML = `
            <div class="flex items-center">
                <i class="${icons[type] || icons.info} mr-3" aria-hidden="true"></i>
                <span>${PaymentUtils.sanitizeInput(message)}</span>
                <button class="ml-3 text-white hover:text-gray-200 focus:outline-none" onclick="this.parentElement.parentElement.remove()">
                    <i class="fas fa-times" aria-hidden="true"></i>
                </button>
            </div>
        `;
        
        document.body.appendChild(notification);
        
        // Animate in
        setTimeout(() => {
            notification.classList.remove('translate-x-full');
        }, 100);
        
        // Auto remove
        setTimeout(() => {
            if (notification.parentElement) {
                notification.classList.add('translate-x-full');
                setTimeout(() => notification.remove(), 300);
            }
        }, 5000);

        // Announce to screen readers
        this.announceToScreenReader(message);
    }

    announceToScreenReader(message) {
        const announcement = document.getElementById('sr-announcements');
        if (announcement) {
            announcement.textContent = message;
            
            // Clear after a delay
            setTimeout(() => {
                announcement.textContent = '';
            }, 1000);
        }
    }
}

// ========================================
// Global Functions for HTML Integration
// ========================================

// Receipt modal functions
window.closeReceiptModal = function() {
    if (window.paymentManager) {
        window.paymentManager.closeReceiptModal();
    }
};

window.downloadReceipt = function() {
    window.print();
};

window.emailReceipt = async function() {
    if (window.paymentManager) {
        try {
            const response = await fetch(`${PAYMENT_CONFIG.API_BASE}${PAYMENT_CONFIG.ENDPOINTS.EMAIL_RECEIPT}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    payment_id: window.paymentManager.currentPaymentId
                }),
                credentials: 'include'
            });
            
            const data = await response.json();
            
            if (data.success) {
                window.paymentManager.showNotification('Makbuz e-posta adresinize gönderildi', 'success');
            } else {
                throw new Error(data.error || 'E-posta gönderilirken hata oluştu');
            }
        } catch (error) {
            window.paymentManager.showNotification('E-posta gönderilirken hata oluştu', 'error');
        }
    }
};

// CVV tooltip toggle
window.toggleCVVTooltip = function() {
    const tooltip = document.getElementById('cvv-tooltip');
    if (tooltip) {
        tooltip.classList.toggle('hidden');
    }
};

// ========================================
// Application Initialization
// ========================================

document.addEventListener('DOMContentLoaded', function() {
    try {
        // Initialize the payment manager
        window.paymentManager = new PaymentManager();
        console.log('CarWash Payment system initialized successfully');
        
    } catch (error) {
        console.error('Failed to initialize CarWash Payment system:', error);
        
        // Show user-friendly error message
        const mainContent = document.getElementById('main-content');
        if (mainContent) {
            mainContent.innerHTML = `
                <div class="text-center py-12">
                    <i class="fas fa-exclamation-triangle text-red-500 text-6xl mb-6" aria-hidden="true"></i>
                    <h2 class="text-2xl font-bold text-gray-900 mb-4">Ödeme Sistemi Başlatılamadı</h2>
                    <p class="text-gray-600 mb-6">Ödeme servisi şu anda kullanılamıyor. Lütfen sayfayı yenileyin.</p>
                    <button class="bg-carwash-primary text-white px-6 py-3 rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-carwash-primary focus:ring-offset-2 transition-colors duration-200"
                            onclick="window.location.reload()">
                        <i class="fas fa-redo mr-2" aria-hidden="true"></i>
                        Sayfayı Yenile
                    </button>
                </div>
            `;
        }
    }
});

// Export classes for external use
window.PaymentManager = PaymentManager;
window.PaymentUtils = PaymentUtils;
window.PAYMENT_CONFIG = PAYMENT_CONFIG;

// Handle page visibility changes
document.addEventListener('visibilitychange', function() {
    if (!document.hidden && window.paymentManager) {
        // Refresh CSRF token when page becomes visible again
        setTimeout(() => {
            window.paymentManager.setupCSRFToken();
        }, 1000);
    }
});

// Handle beforeunload to warn about unsaved changes
window.addEventListener('beforeunload', function(e) {
    if (window.paymentManager && window.paymentManager.isProcessing) {
        e.preventDefault();
        e.returnValue = 'Ödeme işlemi devam ediyor. Sayfayı kapatmak istediğinizden emin misiniz?';
        return e.returnValue;
    }
});
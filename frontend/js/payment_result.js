/**
 * CarWash Payment Result JavaScript
 * Handles payment result display, animations, and user interactions
 * Following CarWash project conventions
 * 
 * @version 1.0.0
 * @author CarWash Team
 */

'use strict';

// ========================================
// Configuration & Constants
// ========================================

const PAYMENT_RESULT_CONFIG = {
    // API Endpoints - Following project structure
    API_BASE: '/carwash_project/backend',
    ENDPOINTS: {
        PAYMENT_VERIFY: '/api/payment_verify.php',
        BOOKING_DETAILS: '/api/booking_details.php',
        SEND_RECEIPT: '/api/send_receipt.php',
        DOWNLOAD_RECEIPT: '/api/download_receipt.php'
    },
    
    // Animation Settings
    ANIMATION_DELAY: 200,
    COUNTDOWN_DURATION: 10000, // 10 seconds for auto redirect
    
    // Notification Settings
    NOTIFICATION_DURATION: 5000,
    
    // Success confetti settings
    CONFETTI_DURATION: 3000,
    
    // Receipt settings
    RECEIPT_FORMAT: 'pdf',
    
    // Turkish language support
    LANGUAGE: 'tr',
    
    // Status types
    STATUS_TYPES: {
        SUCCESS: 'success',
        ERROR: 'error',
        PENDING: 'pending',
        CANCELLED: 'cancelled'
    },
    
    // Error messages
    ERRORS: {
        PAYMENT_VERIFICATION_FAILED: 'Ödeme doğrulaması başarısız oldu.',
        NETWORK_ERROR: 'Ağ bağlantısı hatası oluştu.',
        RECEIPT_SEND_FAILED: 'Fiş e-posta ile gönderilemedi.',
        RECEIPT_DOWNLOAD_FAILED: 'Fiş indirilemedi.',
        SESSION_EXPIRED: 'Oturum süresi doldu. Lütfen tekrar giriş yapın.'
    }
};

// ========================================
// Utility Functions
// ========================================

const PaymentResultUtils = {
    /**
     * Get URL parameters
     */
    getUrlParams() {
        const params = new URLSearchParams(window.location.search);
        return {
            payment_id: params.get('payment_id'),
            status: params.get('status'),
            booking_id: params.get('booking_id'),
            amount: params.get('amount'),
            currency: params.get('currency') || 'TRY'
        };
    },

    /**
     * Format currency for Turkish locale
     */
    formatCurrency(amount, currency = 'TRY') {
        return new Intl.NumberFormat('tr-TR', {
            style: 'currency',
            currency: currency
        }).format(amount);
    },

    /**
     * Format date for Turkish locale
     */
    formatDate(dateString) {
        const date = new Date(dateString);
        return new Intl.DateTimeFormat('tr-TR', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        }).format(date);
    },

    /**
     * Sanitize HTML to prevent XSS
     */
    sanitizeHTML(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    },

    /**
     * Show loading state
     */
    showLoading(element) {
        element.classList.add('loading');
        element.setAttribute('aria-busy', 'true');
    },

    /**
     * Hide loading state
     */
    hideLoading(element) {
        element.classList.remove('loading');
        element.removeAttribute('aria-busy');
    },

    /**
     * Copy text to clipboard
     */
    async copyToClipboard(text) {
        try {
            await navigator.clipboard.writeText(text);
            return true;
        } catch (err) {
            // Fallback for older browsers
            const textArea = document.createElement('textarea');
            textArea.value = text;
            document.body.appendChild(textArea);
            textArea.select();
            const success = document.execCommand('copy');
            document.body.removeChild(textArea);
            return success;
        }
    },

    /**
     * Announce to screen readers
     */
    announceToScreenReader(message) {
        const announcement = document.createElement('div');
        announcement.setAttribute('aria-live', 'polite');
        announcement.setAttribute('aria-atomic', 'true');
        announcement.className = 'sr-only';
        announcement.textContent = message;
        document.body.appendChild(announcement);
        
        setTimeout(() => {
            if (announcement.parentElement) {
                document.body.removeChild(announcement);
            }
        }, 1000);
    }
};

// ========================================
// Payment Result Manager Class
// ========================================

class PaymentResultManager {
    constructor() {
        this.paymentData = null;
        this.bookingData = null;
        this.urlParams = PaymentResultUtils.getUrlParams();
        this.countdownTimer = null;
        this.init();
    }

    // ========================================
    // Initialization
    // ========================================

    async init() {
        try {
            // Show initial loading
            this.showPageLoading();
            
            // Verify payment and get details
            await this.verifyPaymentAndLoadData();
            
            // Setup UI based on payment status
            this.setupUI();
            
            // Setup event listeners
            this.setupEventListeners();
            
            // Start animations
            this.startAnimations();
            
            // Setup auto redirect for successful payments
            if (this.paymentData?.status === PAYMENT_RESULT_CONFIG.STATUS_TYPES.SUCCESS) {
                this.setupAutoRedirect();
            }
            
            console.log('Payment Result page initialized successfully');
            
        } catch (error) {
            console.error('Error initializing payment result:', error);
            this.handleInitError(error);
        } finally {
            this.hidePageLoading();
        }
    }

    async verifyPaymentAndLoadData() {
        if (!this.urlParams.payment_id) {
            throw new Error('Payment ID not found in URL');
        }

        try {
            // Verify payment
            const paymentResponse = await fetch(
                `${PAYMENT_RESULT_CONFIG.API_BASE}${PAYMENT_RESULT_CONFIG.ENDPOINTS.PAYMENT_VERIFY}?payment_id=${this.urlParams.payment_id}`,
                {
                    method: 'GET',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    credentials: 'include'
                }
            );

            if (!paymentResponse.ok) {
                throw new Error(`HTTP ${paymentResponse.status}: ${paymentResponse.statusText}`);
            }

            const paymentData = await paymentResponse.json();
            
            if (!paymentData.success) {
                throw new Error(paymentData.error || 'Payment verification failed');
            }

            this.paymentData = paymentData.payment;

            // Load booking details if booking_id is available
            if (this.paymentData.booking_id) {
                await this.loadBookingDetails(this.paymentData.booking_id);
            }

        } catch (error) {
            console.error('Payment verification error:', error);
            throw new Error(PAYMENT_RESULT_CONFIG.ERRORS.PAYMENT_VERIFICATION_FAILED);
        }
    }

    async loadBookingDetails(bookingId) {
        try {
            const response = await fetch(
                `${PAYMENT_RESULT_CONFIG.API_BASE}${PAYMENT_RESULT_CONFIG.ENDPOINTS.BOOKING_DETAILS}?booking_id=${bookingId}`,
                {
                    method: 'GET',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    credentials: 'include'
                }
            );

            if (response.ok) {
                const data = await response.json();
                if (data.success) {
                    this.bookingData = data.booking;
                }
            }
        } catch (error) {
            console.warn('Could not load booking details:', error);
        }
    }

    // ========================================
    // UI Setup
    // ========================================

    setupUI() {
        this.updateStatusHeader();
        this.updatePaymentDetails();
        this.updateBookingSummary();
        this.updateActionButtons();
        this.updateProgressIndicator();
    }

    updateStatusHeader() {
        const statusHeader = document.querySelector('.status-header');
        const statusIcon = document.querySelector('.status-icon');
        const statusTitle = document.querySelector('.status-title');
        const statusSubtitle = document.querySelector('.status-subtitle');
        
        if (!statusHeader || !statusIcon || !statusTitle || !statusSubtitle) return;

        const status = this.paymentData.status;
        let iconClass, titleText, subtitleText, headerClass;

        switch (status) {
            case PAYMENT_RESULT_CONFIG.STATUS_TYPES.SUCCESS:
                iconClass = 'fas fa-check-circle';
                titleText = 'Ödeme Başarılı!';
                subtitleText = 'Rezervasyonunuz onaylandı ve size e-posta ile bilgi gönderildi.';
                headerClass = 'status-success';
                break;
            
            case PAYMENT_RESULT_CONFIG.STATUS_TYPES.ERROR:
                iconClass = 'fas fa-times-circle';
                titleText = 'Ödeme Başarısız!';
                subtitleText = 'Ödemeniz işlenemedi. Lütfen tekrar deneyin veya farklı bir ödeme yöntemi seçin.';
                headerClass = 'status-error';
                break;
            
            case PAYMENT_RESULT_CONFIG.STATUS_TYPES.PENDING:
                iconClass = 'fas fa-clock';
                titleText = 'Ödeme Beklemede';
                subtitleText = 'Ödemeniz işleniyor. Kısa süre içinde sonuç hakkında bilgilendirileceksiniz.';
                headerClass = 'status-pending';
                break;
            
            case PAYMENT_RESULT_CONFIG.STATUS_TYPES.CANCELLED:
                iconClass = 'fas fa-ban';
                titleText = 'Ödeme İptal Edildi';
                subtitleText = 'Ödeme işlemi iptal edildi. İsterseniz tekrar rezervasyon yapabilirsiniz.';
                headerClass = 'status-error';
                break;
            
            default:
                iconClass = 'fas fa-question-circle';
                titleText = 'Bilinmeyen Durum';
                subtitleText = 'Ödeme durumu belirlenemedi. Lütfen müşteri hizmetleri ile iletişime geçin.';
                headerClass = 'status-error';
        }

        statusHeader.className = `status-header ${headerClass}`;
        statusIcon.innerHTML = `<i class="${iconClass}"></i>`;
        statusTitle.textContent = titleText;
        statusSubtitle.textContent = subtitleText;

        // Announce status to screen readers
        PaymentResultUtils.announceToScreenReader(`${titleText} ${subtitleText}`);
    }

    updatePaymentDetails() {
        const detailsContainer = document.querySelector('.payment-details .details-grid');
        if (!detailsContainer || !this.paymentData) return;

        const details = [
            {
                label: 'Ödeme ID',
                value: this.paymentData.payment_id,
                icon: 'fas fa-hashtag',
                copyable: true
            },
            {
                label: 'Tutar',
                value: PaymentResultUtils.formatCurrency(this.paymentData.amount, this.paymentData.currency),
                icon: 'fas fa-lira-sign'
            },
            {
                label: 'Ödeme Tarihi',
                value: PaymentResultUtils.formatDate(this.paymentData.created_at),
                icon: 'fas fa-calendar-alt'
            },
            {
                label: 'Ödeme Yöntemi',
                value: this.getPaymentMethodName(this.paymentData.payment_method),
                icon: 'fas fa-credit-card'
            },
            {
                label: 'Durum',
                value: this.getStatusName(this.paymentData.status),
                icon: 'fas fa-info-circle'
            }
        ];

        // Add transaction ID if available
        if (this.paymentData.transaction_id) {
            details.push({
                label: 'İşlem ID',
                value: this.paymentData.transaction_id,
                icon: 'fas fa-receipt',
                copyable: true
            });
        }

        detailsContainer.innerHTML = details.map(detail => this.createDetailItem(detail)).join('');
    }

    createDetailItem(detail) {
        const copyButton = detail.copyable ? 
            `<button class="copy-btn ml-2 text-primary-500 hover:text-primary-700" 
                     onclick="window.paymentResultManager.copyToClipboard('${detail.value}')"
                     aria-label="${detail.label} kopyala">
                <i class="fas fa-copy text-sm"></i>
             </button>` : '';

        return `
            <div class="detail-item">
                <div class="detail-label">
                    <i class="${detail.icon}"></i>
                    ${PaymentResultUtils.sanitizeHTML(detail.label)}
                </div>
                <div class="detail-value">
                    ${PaymentResultUtils.sanitizeHTML(detail.value)}
                    ${copyButton}
                </div>
            </div>
        `;
    }

    updateBookingSummary() {
        const summaryContainer = document.querySelector('.booking-summary');
        if (!summaryContainer || !this.bookingData) return;

        const services = this.bookingData.services || [];
        const totalAmount = this.bookingData.total_amount || this.paymentData.amount;

        const summaryHTML = `
            <div class="booking-summary-title">
                <i class="fas fa-car"></i>
                Rezervasyon Detayları
            </div>
            
            <div class="booking-item">
                <span class="booking-item-label">Araç Yıkama</span>
                <span class="booking-item-value">${PaymentResultUtils.sanitizeHTML(this.bookingData.carwash_name || 'Belirtilmemiş')}</span>
            </div>
            
            <div class="booking-item">
                <span class="booking-item-label">Tarih & Saat</span>
                <span class="booking-item-value">${PaymentResultUtils.formatDate(this.bookingData.appointment_date)}</span>
            </div>
            
            ${services.map(service => `
                <div class="booking-item">
                    <span class="booking-item-label">${PaymentResultUtils.sanitizeHTML(service.name)}</span>
                    <span class="booking-item-value">${PaymentResultUtils.formatCurrency(service.price)}</span>
                </div>
            `).join('')}
            
            <div class="booking-item">
                <span class="booking-item-label">Toplam Tutar</span>
                <span class="booking-item-value">${PaymentResultUtils.formatCurrency(totalAmount)}</span>
            </div>
        `;

        summaryContainer.innerHTML = summaryHTML;
    }

    updateActionButtons() {
        const buttonsContainer = document.querySelector('.action-buttons');
        if (!buttonsContainer) return;

        const status = this.paymentData.status;
        let buttonsHTML = '';

        switch (status) {
            case PAYMENT_RESULT_CONFIG.STATUS_TYPES.SUCCESS:
                buttonsHTML = `
                    <button class="btn btn-primary" onclick="window.paymentResultManager.downloadReceipt()">
                        <i class="fas fa-download"></i>
                        Fişi İndir
                    </button>
                    <button class="btn btn-secondary" onclick="window.paymentResultManager.sendReceiptByEmail()">
                        <i class="fas fa-envelope"></i>
                        E-posta ile Gönder
                    </button>
                    <a href="/carwash_project/frontend/dashboard.html" class="btn btn-success">
                        <i class="fas fa-tachometer-alt"></i>
                        Panele Dön
                    </a>
                `;
                break;
            
            case PAYMENT_RESULT_CONFIG.STATUS_TYPES.ERROR:
            case PAYMENT_RESULT_CONFIG.STATUS_TYPES.CANCELLED:
                buttonsHTML = `
                    <a href="/carwash_project/frontend/booking.html${this.bookingData ? `?carwash=${this.bookingData.carwash_id}` : ''}" class="btn btn-primary">
                        <i class="fas fa-redo"></i>
                        Tekrar Dene
                    </a>
                    <a href="/carwash_project/frontend/search.html" class="btn btn-secondary">
                        <i class="fas fa-search"></i>
                        Başka Araç Yıkama Bul
                    </a>
                `;
                break;
            
            case PAYMENT_RESULT_CONFIG.STATUS_TYPES.PENDING:
                buttonsHTML = `
                    <button class="btn btn-primary" onclick="window.paymentResultManager.checkPaymentStatus()" id="checkStatusBtn">
                        <i class="fas fa-sync-alt"></i>
                        Durumu Kontrol Et
                    </button>
                    <a href="/carwash_project/frontend/dashboard.html" class="btn btn-secondary">
                        <i class="fas fa-tachometer-alt"></i>
                        Panele Dön
                    </a>
                `;
                break;
        }

        buttonsContainer.innerHTML = buttonsHTML;
    }

    updateProgressIndicator() {
        const progressContainer = document.querySelector('.progress-indicator');
        if (!progressContainer) return;

        const steps = [
            { id: 'booking', label: 'Rezervasyon', icon: 'fas fa-calendar-plus' },
            { id: 'payment', label: 'Ödeme', icon: 'fas fa-credit-card' },
            { id: 'confirmation', label: 'Onay', icon: 'fas fa-check-circle' }
        ];

        const currentStep = this.getCurrentStep();

        const progressHTML = steps.map((step, index) => {
            let stepClass = 'progress-step';
            if (index < currentStep) stepClass += ' completed';
            if (index === currentStep) stepClass += ' active';

            return `
                <div class="${stepClass}">
                    <div class="progress-step-icon">
                        <i class="${step.icon}"></i>
                    </div>
                    <div class="progress-step-label">${step.label}</div>
                </div>
            `;
        }).join('');

        progressContainer.innerHTML = progressHTML;
    }

    getCurrentStep() {
        const status = this.paymentData.status;
        switch (status) {
            case PAYMENT_RESULT_CONFIG.STATUS_TYPES.SUCCESS:
                return 2; // Confirmation step
            case PAYMENT_RESULT_CONFIG.STATUS_TYPES.PENDING:
                return 1; // Payment step
            default:
                return 0; // Booking step
        }
    }

    // ========================================
    // Event Listeners
    // ========================================

    setupEventListeners() {
        // Keyboard navigation
        document.addEventListener('keydown', (e) => this.handleKeyboardNavigation(e));

        // Window beforeunload for pending payments
        if (this.paymentData?.status === PAYMENT_RESULT_CONFIG.STATUS_TYPES.PENDING) {
            window.addEventListener('beforeunload', (e) => {
                e.preventDefault();
                e.returnValue = 'Ödemeniz hala işleniyor. Sayfayı kapatmak istediğinizden emin misiniz?';
            });
        }

        // Print shortcut
        document.addEventListener('keydown', (e) => {
            if ((e.ctrlKey || e.metaKey) && e.key === 'p') {
                e.preventDefault();
                this.printReceipt();
            }
        });
    }

    // ========================================
    // Actions
    // ========================================

    async downloadReceipt() {
        try {
            this.showButtonLoading('downloadBtn');
            
            const response = await fetch(
                `${PAYMENT_RESULT_CONFIG.API_BASE}${PAYMENT_RESULT_CONFIG.ENDPOINTS.DOWNLOAD_RECEIPT}?payment_id=${this.paymentData.payment_id}&format=${PAYMENT_RESULT_CONFIG.RECEIPT_FORMAT}`,
                {
                    method: 'GET',
                    credentials: 'include'
                }
            );

            if (!response.ok) {
                throw new Error('Download failed');
            }

            const blob = await response.blob();
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `fiş-${this.paymentData.payment_id}.pdf`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            window.URL.revokeObjectURL(url);

            this.showNotification('Fiş başarıyla indirildi', 'success');

        } catch (error) {
            console.error('Download receipt error:', error);
            this.showNotification(PAYMENT_RESULT_CONFIG.ERRORS.RECEIPT_DOWNLOAD_FAILED, 'error');
        } finally {
            this.hideButtonLoading('downloadBtn');
        }
    }

    async sendReceiptByEmail() {
        try {
            this.showButtonLoading('emailBtn');
            
            const response = await fetch(
                `${PAYMENT_RESULT_CONFIG.API_BASE}${PAYMENT_RESULT_CONFIG.ENDPOINTS.SEND_RECEIPT}`,
                {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    credentials: 'include',
                    body: JSON.stringify({
                        payment_id: this.paymentData.payment_id
                    })
                }
            );

            const data = await response.json();

            if (data.success) {
                this.showNotification('Fiş e-posta adresinize gönderildi', 'success');
            } else {
                throw new Error(data.error || 'Email send failed');
            }

        } catch (error) {
            console.error('Send receipt email error:', error);
            this.showNotification(PAYMENT_RESULT_CONFIG.ERRORS.RECEIPT_SEND_FAILED, 'error');
        } finally {
            this.hideButtonLoading('emailBtn');
        }
    }

    async checkPaymentStatus() {
        try {
            this.showButtonLoading('checkStatusBtn');
            
            await this.verifyPaymentAndLoadData();
            this.setupUI();
            
            if (this.paymentData.status !== PAYMENT_RESULT_CONFIG.STATUS_TYPES.PENDING) {
                this.showNotification('Ödeme durumu güncellendi', 'success');
                
                // Remove beforeunload listener
                window.removeEventListener('beforeunload', this.beforeUnloadHandler);
                
                // Setup new animations
                this.startAnimations();
            } else {
                this.showNotification('Ödeme hala işleniyor', 'info');
            }

        } catch (error) {
            console.error('Check payment status error:', error);
            this.showNotification('Durum kontrol edilemedi', 'error');
        } finally {
            this.hideButtonLoading('checkStatusBtn');
        }
    }

    async copyToClipboard(text) {
        try {
            const success = await PaymentResultUtils.copyToClipboard(text);
            if (success) {
                this.showNotification('Panoya kopyalandı', 'success');
            } else {
                throw new Error('Copy failed');
            }
        } catch (error) {
            console.error('Copy to clipboard error:', error);
            this.showNotification('Kopyalanamadı', 'error');
        }
    }

    printReceipt() {
        window.print();
    }

    // ========================================
    // Animations
    // ========================================

    startAnimations() {
        // Animate status header
        setTimeout(() => {
            const statusHeader = document.querySelector('.status-header');
            if (statusHeader) {
                statusHeader.classList.add('animate-fade-in-down');
            }
        }, PAYMENT_RESULT_CONFIG.ANIMATION_DELAY);

        // Animate status icon
        setTimeout(() => {
            const statusIcon = document.querySelector('.status-icon');
            if (statusIcon) {
                statusIcon.classList.add('animate-scale-in');
            }
        }, PAYMENT_RESULT_CONFIG.ANIMATION_DELAY * 2);

        // Animate details
        setTimeout(() => {
            const details = document.querySelectorAll('.detail-item');
            details.forEach((detail, index) => {
                setTimeout(() => {
                    detail.classList.add('animate-fade-in-up');
                }, index * 100);
            });
        }, PAYMENT_RESULT_CONFIG.ANIMATION_DELAY * 3);

        // Show confetti for successful payments
        if (this.paymentData?.status === PAYMENT_RESULT_CONFIG.STATUS_TYPES.SUCCESS) {
            this.showConfetti();
        }
    }

    showConfetti() {
        // Simple confetti effect using CSS animations
        const confettiContainer = document.createElement('div');
        confettiContainer.className = 'confetti-container fixed inset-0 pointer-events-none z-50';
        
        for (let i = 0; i < 50; i++) {
            const confetti = document.createElement('div');
            confetti.className = 'confetti absolute w-2 h-2 opacity-80';
            confetti.style.left = Math.random() * 100 + '%';
            confetti.style.backgroundColor = `hsl(${Math.random() * 360}, 70%, 60%)`;
            confetti.style.animation = `confetti-fall ${2 + Math.random() * 3}s linear forwards`;
            confetti.style.animationDelay = Math.random() * 2 + 's';
            confettiContainer.appendChild(confetti);
        }
        
        document.body.appendChild(confettiContainer);
        
        setTimeout(() => {
            if (confettiContainer.parentElement) {
                document.body.removeChild(confettiContainer);
            }
        }, PAYMENT_RESULT_CONFIG.CONFETTI_DURATION);
    }

    // ========================================
    // Auto Redirect
    // ========================================

    setupAutoRedirect() {
        let countdown = PAYMENT_RESULT_CONFIG.COUNTDOWN_DURATION / 1000;
        
        // Create countdown element
        const countdownElement = document.createElement('div');
        countdownElement.className = 'countdown-notice bg-blue-50 border border-blue-200 rounded-lg p-3 mt-4 text-center text-sm text-blue-800';
        countdownElement.innerHTML = `
            <div class="flex items-center justify-center gap-2">
                <i class="fas fa-info-circle"></i>
                <span>Sayfayı kapatabilir veya <span id="countdown">${countdown}</span> saniye sonra otomatik olarak panele yönlendirileceksiniz.</span>
                <button onclick="window.paymentResultManager.cancelAutoRedirect()" class="ml-2 text-blue-600 hover:text-blue-800 underline">
                    İptal
                </button>
            </div>
        `;
        
        const paymentDetails = document.querySelector('.payment-details');
        if (paymentDetails) {
            paymentDetails.appendChild(countdownElement);
        }
        
        // Start countdown
        this.countdownTimer = setInterval(() => {
            countdown--;
            const countdownSpan = document.getElementById('countdown');
            if (countdownSpan) {
                countdownSpan.textContent = countdown;
            }
            
            if (countdown <= 0) {
                this.redirectToDashboard();
            }
        }, 1000);
    }

    cancelAutoRedirect() {
        if (this.countdownTimer) {
            clearInterval(this.countdownTimer);
            this.countdownTimer = null;
        }
        
        const countdownNotice = document.querySelector('.countdown-notice');
        if (countdownNotice) {
            countdownNotice.remove();
        }
        
        this.showNotification('Otomatik yönlendirme iptal edildi', 'info');
    }

    redirectToDashboard() {
        window.location.href = '/carwash_project/frontend/dashboard.html';
    }

    // ========================================
    // UI State Management
    // ========================================

    showPageLoading() {
        const loadingOverlay = document.createElement('div');
        loadingOverlay.id = 'pageLoading';
        loadingOverlay.className = 'fixed inset-0 bg-white bg-opacity-90 flex items-center justify-center z-50';
        loadingOverlay.innerHTML = `
            <div class="text-center">
                <div class="spinner mb-4"></div>
                <p class="text-gray-600">Ödeme durumu kontrol ediliyor...</p>
            </div>
        `;
        document.body.appendChild(loadingOverlay);
    }

    hidePageLoading() {
        const loadingOverlay = document.getElementById('pageLoading');
        if (loadingOverlay) {
            loadingOverlay.remove();
        }
    }

    showButtonLoading(buttonId) {
        const button = document.getElementById(buttonId);
        if (button) {
            button.disabled = true;
            const originalContent = button.innerHTML;
            button.dataset.originalContent = originalContent;
            button.innerHTML = '<div class="spinner mr-2"></div>Yükleniyor...';
        }
    }

    hideButtonLoading(buttonId) {
        const button = document.getElementById(buttonId);
        if (button && button.dataset.originalContent) {
            button.disabled = false;
            button.innerHTML = button.dataset.originalContent;
            delete button.dataset.originalContent;
        }
    }

    // ========================================
    // Notifications
    // ========================================

    showNotification(message, type = 'info') {
        // Remove existing notifications
        const existingNotifications = document.querySelectorAll('.payment-notification');
        existingNotifications.forEach(notification => notification.remove());

        const notification = document.createElement('div');
        notification.className = `payment-notification notification ${type} fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg max-w-sm transform translate-x-full transition-transform duration-300`;
        
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
                <i class="${icons[type] || icons.info} mr-3"></i>
                <span>${PaymentResultUtils.sanitizeHTML(message)}</span>
                <button class="ml-3 text-white hover:text-gray-200" onclick="this.parentElement.parentElement.remove()">
                    <i class="fas fa-times"></i>
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
                setTimeout(() => {
                    if (notification.parentElement) {
                        notification.remove();
                    }
                }, 300);
            }
        }, PAYMENT_RESULT_CONFIG.NOTIFICATION_DURATION);

        // Announce to screen readers
        PaymentResultUtils.announceToScreenReader(message);
    }

    // ========================================
    // Helper Methods
    // ========================================

    getPaymentMethodName(method) {
        const methods = {
            'credit_card': 'Kredi Kartı',
            'debit_card': 'Banka Kartı',
            'bank_transfer': 'Banka Havalesi',
            'digital_wallet': 'Dijital Cüzdan',
            'cash': 'Nakit'
        };
        return methods[method] || 'Belirtilmemiş';
    }

    getStatusName(status) {
        const statuses = {
            'success': 'Başarılı',
            'error': 'Başarısız',
            'pending': 'Beklemede',
            'cancelled': 'İptal Edildi'
        };
        return statuses[status] || 'Bilinmiyor';
    }

    handleKeyboardNavigation(e) {
        switch (e.key) {
            case 'r':
                if (e.ctrlKey && this.paymentData?.status === PAYMENT_RESULT_CONFIG.STATUS_TYPES.SUCCESS) {
                    e.preventDefault();
                    this.downloadReceipt();
                }
                break;
            case 'e':
                if (e.ctrlKey && this.paymentData?.status === PAYMENT_RESULT_CONFIG.STATUS_TYPES.SUCCESS) {
                    e.preventDefault();
                    this.sendReceiptByEmail();
                }
                break;
        }
    }

    handleInitError(error) {
        console.error('Payment result initialization error:', error);
        
        const container = document.querySelector('.main-content') || document.body;
        container.innerHTML = `
            <div class="error-container text-center p-8 max-w-md mx-auto">
                <i class="fas fa-exclamation-triangle text-red-500 text-4xl mb-4"></i>
                <h2 class="text-xl font-semibold text-gray-900 mb-2">Bir Hata Oluştu</h2>
                <p class="text-gray-600 mb-4">${error.message || 'Ödeme durumu kontrol edilirken bir hata oluştu. Lütfen daha sonra tekrar deneyin.'}</p>
                <a href="/carwash_project/frontend/index.html" class="btn btn-primary">
                    <i class="fas fa-home"></i>
                    Ana Sayfaya Dön
                </a>
            </div>
        `;
    }
}

// Initialize
document.addEventListener('DOMContentLoaded', () => {
    window.paymentResultManager = new PaymentResultManager();
});
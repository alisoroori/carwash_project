/**
 * CarWash Payment History JavaScript
 * Handles payment history display, filtering, and management
 * Following CarWash project conventions
 * 
 * @version 1.0.0
 * @author CarWash Team
 */

'use strict';

// ========================================
// Configuration & Constants
// ========================================

const PAYMENT_CONFIG = {
    // API Endpoints - Following project structure
    API_BASE: '/carwash_project/backend',
    ENDPOINTS: {
        GET_PAYMENTS: '/api/get_payment_history.php',
        GET_PAYMENT_DETAIL: '/api/get_payment_detail.php',
        EXPORT_PAYMENTS: '/api/export_payments.php'
    },
    
    // Pagination settings
    ITEMS_PER_PAGE: 10,
    MAX_PAGINATION_BUTTONS: 5,
    
    // Performance settings
    DEBOUNCE_DELAY: 300,
    REQUEST_TIMEOUT: 10000,
    
    // Date format
    DATE_FORMAT: {
        locale: 'tr-TR',
        options: {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        }
    },
    
    // Currency format
    CURRENCY_FORMAT: {
        locale: 'tr-TR',
        options: {
            style: 'currency',
            currency: 'TRY'
        }
    },
    
    // Status mapping
    STATUS_MAPPING: {
        'completed': 'Tamamlandı',
        'pending': 'Beklemede',
        'cancelled': 'İptal Edildi',
        'failed': 'Başarısız'
    },
    
    // Status colors
    STATUS_COLORS: {
        'completed': 'bg-green-100 text-green-800',
        'pending': 'bg-yellow-100 text-yellow-800',
        'cancelled': 'bg-gray-100 text-gray-800',
        'failed': 'bg-red-100 text-red-800'
    }
};

// ========================================
// Utility Functions
// ========================================

const PaymentUtils = {
    /**
     * Debounce function to limit API calls
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
     * Format date for Turkish locale
     */
    formatDate(dateString) {
        try {
            const date = new Date(dateString);
            return date.toLocaleDateString(
                PAYMENT_CONFIG.DATE_FORMAT.locale, 
                PAYMENT_CONFIG.DATE_FORMAT.options
            );
        } catch (error) {
            console.error('Date formatting error:', error);
            return dateString;
        }
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
        if (element) {
            element.classList.add('loading');
            element.setAttribute('aria-busy', 'true');
        }
    },

    /**
     * Hide loading state
     */
    hideLoading(element) {
        if (element) {
            element.classList.remove('loading');
            element.removeAttribute('aria-busy');
        }
    },

    /**
     * Generate unique ID
     */
    generateId() {
        return 'payment_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
    },

    /**
     * Parse amount range filter
     */
    parseAmountRange(rangeString) {
        if (!rangeString) return null;
        
        if (rangeString.includes('+')) {
            const min = parseInt(rangeString.replace('+', ''));
            return { min, max: null };
        } else {
            const [min, max] = rangeString.split('-').map(num => parseInt(num));
            return { min: min || 0, max: max || null };
        }
    }
};

// ========================================
// Payment History Manager Class
// ========================================

class PaymentHistoryManager {
    constructor() {
        this.payments = [];
        this.filteredPayments = [];
        this.currentPage = 1;
        this.totalPages = 1;
        this.isLoading = false;
        this.sortField = 'date';
        this.sortDirection = 'desc';
        this.filters = {
            status: '',
            dateFrom: '',
            dateTo: '',
            amount: ''
        };
        
        this.init();
    }

    // ========================================
    // Initialization
    // ========================================

    async init() {
        try {
            this.setupEventListeners();
            await this.loadPaymentHistory();
            this.updateSummaryCards();
            
            console.log('Payment History Manager initialized successfully');
        } catch (error) {
            console.error('Error initializing Payment History Manager:', error);
            this.handleInitError(error);
        }
    }

    setupEventListeners() {
        // Filter event listeners
        const statusFilter = document.getElementById('statusFilter');
        const dateFromFilter = document.getElementById('dateFromFilter');
        const dateToFilter = document.getElementById('dateToFilter');
        const amountFilter = document.getElementById('amountFilter');

        if (statusFilter) {
            statusFilter.addEventListener('change', () => this.handleFilterChange());
        }

        if (dateFromFilter) {
            dateFromFilter.addEventListener('change', () => this.handleFilterChange());
        }

        if (dateToFilter) {
            dateToFilter.addEventListener('change', () => this.handleFilterChange());
        }

        if (amountFilter) {
            amountFilter.addEventListener('change', () => this.handleFilterChange());
        }

        // Modal events
        this.setupModalEvents();

        // Keyboard navigation
        document.addEventListener('keydown', (e) => this.handleKeyboardNavigation(e));

        // User menu events
        this.setupUserMenuEvents();
    }

    setupModalEvents() {
        const modal = document.getElementById('paymentModal');
        if (!modal) return;

        // Close modal on backdrop click
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                this.closePaymentModal();
            }
        });

        // Close modal on escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && !modal.classList.contains('hidden')) {
                this.closePaymentModal();
            }
        });
    }

    setupUserMenuEvents() {
        const userMenuButton = document.getElementById('user-menu-button');
        if (userMenuButton) {
            userMenuButton.addEventListener('click', () => {
                // Toggle user menu dropdown if implemented
                console.log('User menu clicked');
            });
        }
    }

    handleFilterChange = PaymentUtils.debounce(() => {
        this.applyFilters();
    }, PAYMENT_CONFIG.DEBOUNCE_DELAY);

    // ========================================
    // Data Loading
    // ========================================

    async loadPaymentHistory() {
        if (this.isLoading) return;

        try {
            this.isLoading = true;
            this.showLoadingState();

            const response = await this.fetchWithTimeout(
                `${PAYMENT_CONFIG.API_BASE}${PAYMENT_CONFIG.ENDPOINTS.GET_PAYMENTS}`,
                {
                    method: 'GET',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    credentials: 'include' // Include session cookies
                }
            );

            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }

            const data = await response.json();

            if (data.success) {
                this.payments = data.payments || [];
                this.filteredPayments = [...this.payments];
                this.renderPaymentsTable();
                this.updatePagination();
                this.announceToScreenReader(`${this.payments.length} ödeme kaydı yüklendi`);
            } else {
                throw new Error(data.error || 'Ödeme geçmişi yüklenemedi');
            }

        } catch (error) {
            console.error('Error loading payment history:', error);
            this.handleLoadError(error);
        } finally {
            this.isLoading = false;
            this.hideLoadingState();
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
            throw error;
        }
    }

    // ========================================
    // Filtering & Sorting
    // ========================================

    applyFilters() {
        // Get filter values
        this.filters.status = document.getElementById('statusFilter')?.value || '';
        this.filters.dateFrom = document.getElementById('dateFromFilter')?.value || '';
        this.filters.dateTo = document.getElementById('dateToFilter')?.value || '';
        this.filters.amount = document.getElementById('amountFilter')?.value || '';

        // Apply filters
        this.filteredPayments = this.payments.filter(payment => {
            return this.matchesStatusFilter(payment) &&
                   this.matchesDateFilter(payment) &&
                   this.matchesAmountFilter(payment);
        });

        // Reset to first page
        this.currentPage = 1;

        // Update display
        this.renderPaymentsTable();
        this.updatePagination();
        this.updateSummaryCards();

        // Announce results
        this.announceToScreenReader(`${this.filteredPayments.length} ödeme kaydı bulundu`);
    }

    matchesStatusFilter(payment) {
        if (!this.filters.status) return true;
        return payment.status === this.filters.status;
    }

    matchesDateFilter(payment) {
        const paymentDate = new Date(payment.created_at);
        
        if (this.filters.dateFrom) {
            const fromDate = new Date(this.filters.dateFrom);
            if (paymentDate < fromDate) return false;
        }
        
        if (this.filters.dateTo) {
            const toDate = new Date(this.filters.dateTo);
            toDate.setHours(23, 59, 59, 999); // End of day
            if (paymentDate > toDate) return false;
        }
        
        return true;
    }

    matchesAmountFilter(payment) {
        if (!this.filters.amount) return true;
        
        const range = PaymentUtils.parseAmountRange(this.filters.amount);
        if (!range) return true;
        
        const amount = parseFloat(payment.amount);
        
        if (range.min && amount < range.min) return false;
        if (range.max && amount > range.max) return false;
        
        return true;
    }

    clearFilters() {
        // Reset filter inputs
        const statusFilter = document.getElementById('statusFilter');
        const dateFromFilter = document.getElementById('dateFromFilter');
        const dateToFilter = document.getElementById('dateToFilter');
        const amountFilter = document.getElementById('amountFilter');

        if (statusFilter) statusFilter.value = '';
        if (dateFromFilter) dateFromFilter.value = '';
        if (dateToFilter) dateToFilter.value = '';
        if (amountFilter) amountFilter.value = '';

        // Reset filters object
        this.filters = {
            status: '',
            dateFrom: '',
            dateTo: '',
            amount: ''
        };

        // Reapply (which will show all)
        this.applyFilters();
    }

    sortTable(field) {
        if (this.sortField === field) {
            this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            this.sortField = field;
            this.sortDirection = 'asc';
        }

        this.filteredPayments.sort((a, b) => {
            let aValue = a[field];
            let bValue = b[field];

            // Handle different data types
            if (field === 'amount') {
                aValue = parseFloat(aValue);
                bValue = parseFloat(bValue);
            } else if (field === 'date' || field === 'created_at') {
                aValue = new Date(aValue);
                bValue = new Date(bValue);
            } else {
                aValue = String(aValue).toLowerCase();
                bValue = String(bValue).toLowerCase();
            }

            if (aValue < bValue) {
                return this.sortDirection === 'asc' ? -1 : 1;
            }
            if (aValue > bValue) {
                return this.sortDirection === 'asc' ? 1 : -1;
            }
            return 0;
        });

        this.renderPaymentsTable();
        this.updateSortIcons();
    }

    updateSortIcons() {
        // Reset all sort icons
        const sortButtons = document.querySelectorAll('[onclick*="sortTable"]');
        sortButtons.forEach(button => {
            const icon = button.querySelector('i');
            if (icon) {
                icon.className = 'fas fa-sort text-gray-400';
            }
        });

        // Update active sort icon
        const activeButton = document.querySelector(`[onclick="sortTable('${this.sortField}')"]`);
        if (activeButton) {
            const icon = activeButton.querySelector('i');
            if (icon) {
                const direction = this.sortDirection === 'asc' ? 'up' : 'down';
                icon.className = `fas fa-sort-${direction} text-gray-600`;
            }
        }
    }

    // ========================================
    // Table Rendering
    // ========================================

    renderPaymentsTable() {
        const tableContainer = document.getElementById('tableContainer');
        const emptyState = document.getElementById('emptyState');
        const paymentsTableBody = document.getElementById('paymentsTableBody');

        if (!paymentsTableBody) return;

        if (this.filteredPayments.length === 0) {
            if (tableContainer) tableContainer.classList.add('hidden');
            if (emptyState) emptyState.classList.remove('hidden');
            return;
        }

        if (tableContainer) tableContainer.classList.remove('hidden');
        if (emptyState) emptyState.classList.add('hidden');

        // Calculate pagination
        const startIndex = (this.currentPage - 1) * PAYMENT_CONFIG.ITEMS_PER_PAGE;
        const endIndex = startIndex + PAYMENT_CONFIG.ITEMS_PER_PAGE;
        const pagePayments = this.filteredPayments.slice(startIndex, endIndex);

        // Render table rows
        paymentsTableBody.innerHTML = pagePayments.map(payment => 
            this.generatePaymentRow(payment)
        ).join('');

        // Setup row event listeners
        this.setupRowEvents();

        // Update pagination info
        this.updatePaginationInfo();
    }

    generatePaymentRow(payment) {
        const statusClass = PAYMENT_CONFIG.STATUS_COLORS[payment.status] || 'bg-gray-100 text-gray-800';
        const statusText = PAYMENT_CONFIG.STATUS_MAPPING[payment.status] || payment.status;

        return `
            <tr class="hover:bg-gray-50 transition-colors duration-150" 
                data-payment-id="${payment.id}"
                role="row">
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                    #${PaymentUtils.sanitizeHTML(payment.order_id || payment.id)}
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    ${PaymentUtils.formatDate(payment.created_at)}
                </td>
                <td class="px-6 py-4 text-sm text-gray-900">
                    <div class="max-w-xs truncate">
                        ${PaymentUtils.sanitizeHTML(payment.carwash_name || 'N/A')}
                    </div>
                </td>
                <td class="px-6 py-4 text-sm text-gray-500">
                    <div class="max-w-xs truncate">
                        ${PaymentUtils.sanitizeHTML(payment.service_name || 'N/A')}
                    </div>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm font-semibold text-gray-900">
                    ${PaymentUtils.formatCurrency(payment.amount)}
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full ${statusClass}">
                        ${statusText}
                    </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                    <div class="flex space-x-2">
                        <button onclick="paymentHistoryManager.showPaymentDetail(${payment.id})" 
                                class="text-carwash-primary hover:text-blue-700 font-medium focus:outline-none focus:ring-2 focus:ring-carwash-primary focus:ring-offset-1 rounded"
                                aria-label="Ödeme detaylarını görüntüle">
                            <i class="fas fa-eye mr-1" aria-hidden="true"></i>
                            Detay
                        </button>
                        ${payment.invoice_url ? `
                            <a href="${payment.invoice_url}" 
                               target="_blank"
                               class="text-green-600 hover:text-green-700 font-medium focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-1 rounded"
                               aria-label="Faturayı indir">
                                <i class="fas fa-download mr-1" aria-hidden="true"></i>
                                Fatura
                            </a>
                        ` : ''}
                    </div>
                </td>
            </tr>
        `;
    }

    setupRowEvents() {
        const rows = document.querySelectorAll('[data-payment-id]');
        rows.forEach(row => {
            // Keyboard navigation
            row.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    const paymentId = row.getAttribute('data-payment-id');
                    this.showPaymentDetail(paymentId);
                }
            });

            // Make row focusable
            row.setAttribute('tabindex', '0');
        });
    }

    // ========================================
    // Payment Detail Modal
    // ========================================

    async showPaymentDetail(paymentId) {
        try {
            PaymentUtils.showLoading(document.body);

            const response = await this.fetchWithTimeout(
                `${PAYMENT_CONFIG.API_BASE}${PAYMENT_CONFIG.ENDPOINTS.GET_PAYMENT_DETAIL}?id=${paymentId}`,
                {
                    method: 'GET',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    credentials: 'include'
                }
            );

            const data = await response.json();

            if (data.success) {
                this.renderPaymentDetail(data.payment);
                this.openPaymentModal();
            } else {
                throw new Error(data.error || 'Ödeme detayları yüklenemedi');
            }

        } catch (error) {
            console.error('Error loading payment detail:', error);
            this.showNotification('Ödeme detayları yüklenirken hata oluştu', 'error');
        } finally {
            PaymentUtils.hideLoading(document.body);
        }
    }

    renderPaymentDetail(payment) {
        const paymentDetail = document.getElementById('paymentDetail');
        if (!paymentDetail) return;

        const statusClass = PAYMENT_CONFIG.STATUS_COLORS[payment.status] || 'bg-gray-100 text-gray-800';
        const statusText = PAYMENT_CONFIG.STATUS_MAPPING[payment.status] || payment.status;

        paymentDetail.innerHTML = `
            <div class="space-y-6">
                <!-- Payment Overview -->
                <div class="bg-gray-50 rounded-lg p-4">
                    <h4 class="text-lg font-semibold text-gray-900 mb-3">Ödeme Bilgileri</h4>
                    <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Sipariş No</dt>
                            <dd class="text-base font-semibold text-gray-900">#${PaymentUtils.sanitizeHTML(payment.order_id || payment.id)}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Ödeme Tarihi</dt>
                            <dd class="text-base text-gray-900">${PaymentUtils.formatDate(payment.created_at)}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Tutar</dt>
                            <dd class="text-lg font-bold text-gray-900">${PaymentUtils.formatCurrency(payment.amount)}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Durum</dt>
                            <dd>
                                <span class="inline-flex px-3 py-1 text-sm font-semibold rounded-full ${statusClass}">
                                    ${statusText}
                                </span>
                            </dd>
                        </div>
                    </dl>
                </div>

                <!-- Service Details -->
                <div class="border rounded-lg p-4">
                    <h4 class="text-lg font-semibold text-gray-900 mb-3">Hizmet Detayları</h4>
                    <dl class="space-y-3">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Araç Yıkama</dt>
                            <dd class="text-base text-gray-900">${PaymentUtils.sanitizeHTML(payment.carwash_name || 'N/A')}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Hizmet</dt>
                            <dd class="text-base text-gray-900">${PaymentUtils.sanitizeHTML(payment.service_name || 'N/A')}</dd>
                        </div>
                        ${payment.appointment_date ? `
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Randevu Tarihi</dt>
                                <dd class="text-base text-gray-900">${PaymentUtils.formatDate(payment.appointment_date)}</dd>
                            </div>
                        ` : ''}
                        ${payment.vehicle_info ? `
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Araç Bilgisi</dt>
                                <dd class="text-base text-gray-900">${PaymentUtils.sanitizeHTML(payment.vehicle_info)}</dd>
                            </div>
                        ` : ''}
                    </dl>
                </div>

                <!-- Payment Method -->
                ${payment.payment_method ? `
                    <div class="border rounded-lg p-4">
                        <h4 class="text-lg font-semibold text-gray-900 mb-3">Ödeme Yöntemi</h4>
                        <p class="text-base text-gray-900">${PaymentUtils.sanitizeHTML(payment.payment_method)}</p>
                    </div>
                ` : ''}

                <!-- Actions -->
                <div class="flex flex-col sm:flex-row gap-3 pt-4 border-t">
                    ${payment.invoice_url ? `
                        <a href="${payment.invoice_url}" 
                           target="_blank"
                           class="flex items-center justify-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition-colors duration-200">
                            <i class="fas fa-download mr-2" aria-hidden="true"></i>
                            Faturayı İndir
                        </a>
                    ` : ''}
                    ${payment.status === 'completed' && payment.booking_id ? `
                        <a href="/carwash_project/backend/dashboard/Customer_Dashboard.php?view=bookings&id=${payment.booking_id}"
                           class="flex items-center justify-center px-4 py-2 bg-carwash-primary text-white rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-carwash-primary focus:ring-offset-2 transition-colors duration-200">
                            <i class="fas fa-calendar-check mr-2" aria-hidden="true"></i>
                            Rezervasyonu Görüntüle
                        </a>
                    ` : ''}
                </div>
            </div>
        `;
    }

    openPaymentModal() {
        const modal = document.getElementById('paymentModal');
        if (modal) {
            modal.classList.remove('hidden');
            
            // Focus management
            const firstFocusable = modal.querySelector('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])');
            if (firstFocusable) {
                setTimeout(() => firstFocusable.focus(), 100);
            }
            
            // Prevent body scroll
            document.body.style.overflow = 'hidden';
        }
    }

    closePaymentModal() {
        const modal = document.getElementById('paymentModal');
        if (modal) {
            modal.classList.add('hidden');
            
            // Restore body scroll
            document.body.style.overflow = '';
        }
    }

    // ========================================
    // Summary Cards
    // ========================================

    updateSummaryCards() {
        const totalAmountEl = document.getElementById('totalAmount');
        const totalTransactionsEl = document.getElementById('totalTransactions');
        const pendingAmountEl = document.getElementById('pendingAmount');

        // Calculate totals from filtered payments
        const totalAmount = this.filteredPayments
            .filter(p => p.status === 'completed')
            .reduce((sum, p) => sum + parseFloat(p.amount), 0);

        const totalTransactions = this.filteredPayments.length;

        const pendingAmount = this.filteredPayments
            .filter(p => p.status === 'pending')
            .reduce((sum, p) => sum + parseFloat(p.amount), 0);

        if (totalAmountEl) {
            totalAmountEl.textContent = PaymentUtils.formatCurrency(totalAmount);
        }

        if (totalTransactionsEl) {
            totalTransactionsEl.textContent = totalTransactions.toString();
        }

        if (pendingAmountEl) {
            pendingAmountEl.textContent = PaymentUtils.formatCurrency(pendingAmount);
        }
    }

    // ========================================
    // Pagination
    // ========================================

    updatePagination() {
        this.totalPages = Math.ceil(this.filteredPayments.length / PAYMENT_CONFIG.ITEMS_PER_PAGE);
        
        const paginationContainer = document.getElementById('pagination');
        if (!paginationContainer) return;

        if (this.totalPages <= 1) {
            paginationContainer.innerHTML = '';
            return;
        }

        let buttons = [];

        // Previous button
        buttons.push(`
            <button onclick="paymentHistoryManager.goToPage(${this.currentPage - 1})" 
                    ${this.currentPage === 1 ? 'disabled' : ''}
                    class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-l-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-carwash-primary focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed">
                <i class="fas fa-chevron-left" aria-hidden="true"></i>
                <span class="sr-only">Önceki sayfa</span>
            </button>
        `);

        // Page number buttons
        const startPage = Math.max(1, this.currentPage - Math.floor(PAYMENT_CONFIG.MAX_PAGINATION_BUTTONS / 2));
        const endPage = Math.min(this.totalPages, startPage + PAYMENT_CONFIG.MAX_PAGINATION_BUTTONS - 1);

        for (let i = startPage; i <= endPage; i++) {
            const isActive = i === this.currentPage;
            buttons.push(`
                <button onclick="paymentHistoryManager.goToPage(${i})" 
                        class="px-3 py-2 text-sm font-medium ${isActive ? 
                            'text-white bg-carwash-primary border-carwash-primary' : 
                            'text-gray-500 bg-white border-gray-300 hover:bg-gray-50'
                        } border focus:outline-none focus:ring-2 focus:ring-carwash-primary focus:ring-offset-2"
                        ${isActive ? 'aria-current="page"' : ''}>
                    ${i}
                </button>
            `);
        }

        // Next button
        buttons.push(`
            <button onclick="paymentHistoryManager.goToPage(${this.currentPage + 1})" 
                    ${this.currentPage === this.totalPages ? 'disabled' : ''}
                    class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-r-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-carwash-primary focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed">
                <i class="fas fa-chevron-right" aria-hidden="true"></i>
                <span class="sr-only">Sonraki sayfa</span>
            </button>
        `);

        paginationContainer.innerHTML = buttons.join('');
    }

    updatePaginationInfo() {
        const showingCountEl = document.getElementById('showingCount');
        const totalCountEl = document.getElementById('totalCount');

        if (showingCountEl && totalCountEl) {
            const startIndex = (this.currentPage - 1) * PAYMENT_CONFIG.ITEMS_PER_PAGE + 1;
            const endIndex = Math.min(this.currentPage * PAYMENT_CONFIG.ITEMS_PER_PAGE, this.filteredPayments.length);
            
            showingCountEl.textContent = this.filteredPayments.length > 0 ? `${startIndex}-${endIndex}` : '0';
            totalCountEl.textContent = this.filteredPayments.length.toString();
        }
    }

    goToPage(page) {
        if (page < 1 || page > this.totalPages || page === this.currentPage) return;
        
        this.currentPage = page;
        this.renderPaymentsTable();
        this.updatePagination();

        // Scroll to top of table
        const tableContainer = document.getElementById('tableContainer');
        if (tableContainer) {
            tableContainer.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
    }

    // ========================================
    // Export Functionality
    // ========================================

    async exportPayments() {
        try {
            PaymentUtils.showLoading(document.body);

            const params = new URLSearchParams({
                format: 'excel',
                ...this.filters
            });

            const response = await this.fetchWithTimeout(
                `${PAYMENT_CONFIG.API_BASE}${PAYMENT_CONFIG.ENDPOINTS.EXPORT_PAYMENTS}?${params}`,
                {
                    method: 'GET',
                    credentials: 'include'
                }
            );

            if (response.ok) {
                const blob = await response.blob();
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `odeme_gecmisi_${new Date().getTime()}.xlsx`;
                document.body.appendChild(a);
                a.click();
                window.URL.revokeObjectURL(url);
                document.body.removeChild(a);

                this.showNotification('Ödeme geçmişi başarıyla dışa aktarıldı', 'success');
            } else {
                throw new Error('Dışa aktarma işlemi başarısız');
            }

        } catch (error) {
            console.error('Export error:', error);
            this.showNotification('Dışa aktarma sırasında hata oluştu', 'error');
        } finally {
            PaymentUtils.hideLoading(document.body);
        }
    }

    // ========================================
    // UI State Management
    // ========================================

    showLoadingState() {
        const loadingState = document.getElementById('loadingState');
        const tableContainer = document.getElementById('tableContainer');
        const emptyState = document.getElementById('emptyState');

        if (loadingState) loadingState.classList.remove('hidden');
        if (tableContainer) tableContainer.classList.add('hidden');
        if (emptyState) emptyState.classList.add('hidden');
    }

    hideLoadingState() {
        const loadingState = document.getElementById('loadingState');
        if (loadingState) loadingState.classList.add('hidden');
    }

    handleLoadError(error) {
        const loadingState = document.getElementById('loadingState');
        if (loadingState) {
            loadingState.innerHTML = `
                <div class="text-center py-8">
                    <div class="text-red-500 text-4xl mb-4">
                        <i class="fas fa-exclamation-triangle" aria-hidden="true"></i>
                    </div>
                    <h3 class="text-lg font-semibold text-gray-900 mb-2">Yükleme Hatası</h3>
                    <p class="text-gray-600 mb-4">${error.message || 'Ödeme geçmişi yüklenirken hata oluştu'}</p>
                    <button onclick="paymentHistoryManager.loadPaymentHistory()" 
                            class="bg-carwash-primary text-white px-4 py-2 rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-carwash-primary focus:ring-offset-2 transition-colors duration-200">
                        <i class="fas fa-redo mr-2" aria-hidden="true"></i>
                        Tekrar Dene
                    </button>
                </div>
            `;
        }

        this.showNotification('Ödeme geçmişi yüklenirken hata oluştu', 'error');
    }

    handleInitError(error) {
        console.error('Initialization error:', error);
        this.showNotification('Sayfa başlatılırken hata oluştu', 'error');
    }

    // ========================================
    // Notifications
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
                <span>${PaymentUtils.sanitizeHTML(message)}</span>
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

    // ========================================
    // Accessibility
    // ========================================

    handleKeyboardNavigation(e) {
        switch (e.key) {
            case 'Escape':
                const modal = document.getElementById('paymentModal');
                if (modal && !modal.classList.contains('hidden')) {
                    this.closePaymentModal();
                }
                break;
        }
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

// Export functions for HTML onclick handlers
window.applyFilters = function() {
    if (window.paymentHistoryManager) {
        window.paymentHistoryManager.applyFilters();
    }
};

window.clearFilters = function() {
    if (window.paymentHistoryManager) {
        window.paymentHistoryManager.clearFilters();
    }
};

window.sortTable = function(field) {
    if (window.paymentHistoryManager) {
        window.paymentHistoryManager.sortTable(field);
    }
};

window.exportPayments = function() {
    if (window.paymentHistoryManager) {
        window.paymentHistoryManager.exportPayments();
    }
};

window.closePaymentModal = function() {
    if (window.paymentHistoryManager) {
        window.paymentHistoryManager.closePaymentModal();
    }
};

// ========================================
// Application Initialization
// ========================================

document.addEventListener('DOMContentLoaded', function() {
    try {
        // Initialize the payment history manager
        window.paymentHistoryManager = new PaymentHistoryManager();
        console.log('CarWash Payment History initialized successfully');
        
    } catch (error) {
        console.error('Failed to initialize CarWash Payment History:', error);
        
        // Show user-friendly error message
        const mainContent = document.getElementById('main-content');
        if (mainContent) {
            mainContent.innerHTML = `
                <div class="error-container text-center p-8">
                    <i class="fas fa-exclamation-triangle text-red-500 text-4xl mb-4" aria-hidden="true"></i>
                    <h2 class="text-xl font-semibold text-gray-900 mb-2">Uygulama Başlatılamadı</h2>
                    <p class="text-gray-600 mb-4">Ödeme geçmişi servisi şu anda kullanılamıyor. Lütfen sayfayı yenileyin.</p>
                    <button class="bg-carwash-primary text-white px-4 py-2 rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-carwash-primary focus:ring-offset-2 transition-colors duration-200"
                            onclick="window.location.reload()">
                        <i class="fas fa-redo mr-2" aria-hidden="true"></i>
                        Sayfayı Yenile
                    </button>
                </div>
            `;
        }
    }
});

// Export classes and utilities for external use
window.PaymentHistoryManager = PaymentHistoryManager;
window.PaymentUtils = PaymentUtils;
window.PAYMENT_CONFIG = PAYMENT_CONFIG;

// Handle page visibility changes
document.addEventListener('visibilitychange', function() {
    if (!document.hidden && window.paymentHistoryManager) {
        // Refresh data when page becomes visible again
        setTimeout(() => {
            window.paymentHistoryManager.loadPaymentHistory();
        }, 1000);
    }
});
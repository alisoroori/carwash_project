let currentPage = 1;
const perPage = 10;

// Load payments
async function loadPayments(page = 1, filters = {}) {
    try {
        const queryParams = new URLSearchParams({
            page,
            perPage,
            ...filters
        });

        const response = await fetch(`../backend/api/get_payments.php?${queryParams}`);
        const data = await response.json();

        if (data.success) {
            renderPayments(data.payments);
            renderPagination(data.total, page);
            updateShowingCount(data.payments.length, data.total);
        }
    } catch (error) {
        console.error('Error loading payments:', error);
    }
}

// Render payments table
function renderPayments(payments) {
    const tbody = document.getElementById('paymentsTableBody');
    tbody.innerHTML = payments.map(payment => `
        <tr>
            <td class="px-6 py-4 whitespace-nowrap">
                #${payment.order_id}
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
                ${formatDate(payment.created_at)}
            </td>
            <td class="px-6 py-4">
                ${payment.items.map(item => item.service_name).join(', ')}
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
                ${payment.total} TL
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                    ${getStatusClass(payment.status)}">
                    ${getStatusText(payment.status)}
                </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm">
                <button onclick="showPaymentDetail(${payment.order_id})"
                        class="text-blue-600 hover:text-blue-900">
                    Detaylar
                </button>
            </td>
        </tr>
    `).join('');
}

// Show payment detail
async function showPaymentDetail(orderId) {
    try {
        const response = await fetch(`../backend/api/get_payment_detail.php?order_id=${orderId}`);
        const data = await response.json();

        if (data.success) {
            const modal = document.getElementById('paymentModal');
            const detail = document.getElementById('paymentDetail');
            
            detail.innerHTML = `
                <div class="space-y-4">
                    <div class="flex justify-between items-center">
                        <h2 class="text-2xl font-bold">Sipariş #${data.payment.order_id}</h2>
                        <button onclick="closeModal()" class="text-gray-500 hover:text-gray-700">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    
                    <div class="border-t pt-4">
                        <h3 class="font-semibold mb-2">Hizmet Detayları</h3>
                        ${data.payment.items.map(item => `
                            <div class="flex justify-between py-2">
                                <span>${item.service_name}</span>
                                <span>${item.price} TL</span>
                            </div>
                        `).join('')}
        </div>

        <div class="border-t pt-4">
            <h3 class="font-semibold mb-2">Ödeme Bilgileri</h3>
            <div class="space-y-2">
                <div class="flex justify-between">
                    <span>Ara Toplam</span>
                    <span>${data.payment.subtotal} TL</span>
                </div>
                <div class="flex justify-between">
                    <span>KDV</span>
                    <span>${data.payment.tax} TL</span>
                </div>
                ${data.payment.discount ? `
                    <div class="flex justify-between text-green-600">
                        <span>İndirim</span>
                        <span>-${data.payment.discount} TL</span>
                    </div>
                ` : ''}
                <div class="flex justify-between font-bold pt-2 border-t">
                    <span>Toplam</span>
                    <span>${data.payment.total} TL</span>
                </div>
            </div>
        </div>
        ${data.payment.status === 'completed' ? `
            <div class="mt-4 flex justify-end">
                <button onclick="downloadReceipt(${data.payment.order_id})"
                        class="bg-blue-600 text-white px-4 py-2 rounded-full hover:bg-blue-700">
                    <i class="fas fa-download mr-2"></i>
                    Fatura İndir
                </button>
            </div>
        ` : ''}
    </div>
`;
            
            modal.classList.remove('hidden');
        }
    } catch (error) {
        console.error('Error loading payment detail:', error);
    }
}

// Download receipt
function downloadReceipt(orderId) {
    window.open(`../backend/api/download_receipt.php?order_id=${orderId}`, '_blank');
}

// Helper functions
function formatDate(dateString) {
    return new Date(dateString).toLocaleDateString('tr-TR');
}

function getStatusClass(status) {
    const classes = {
        completed: 'bg-green-100 text-green-800',
        pending: 'bg-yellow-100 text-yellow-800',
        cancelled: 'bg-red-100 text-red-800'
    };
    return classes[status] || 'bg-gray-100 text-gray-800';
}

function getStatusText(status) {
    const texts = {
        completed: 'Tamamlandı',
        pending: 'Beklemede',
        cancelled: 'İptal Edildi'
    };
    return texts[status] || status;
}

function closeModal() {
    document.getElementById('paymentModal').classList.add('hidden');
}

// Event Listeners
document.getElementById('statusFilter').addEventListener('change', function() {
    currentPage = 1;
    loadPayments(currentPage, { status: this.value });
});

document.getElementById('dateFilter').addEventListener('change', function() {
    currentPage = 1;
    loadPayments(currentPage, { date: this.value });
});

// Initialize
document.addEventListener('DOMContentLoaded', () => loadPayments(1));
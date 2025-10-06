async function checkPaymentStatus() {
    const urlParams = new URLSearchParams(window.location.search);
    const paymentId = urlParams.get('payment_id');
    const token = urlParams.get('token');

    if (!paymentId || !token) {
        showError('Geçersiz ödeme bilgisi');
        return;
    }

    try {
        const response = await fetch('../backend/api/check_payment.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ paymentId, token })
        });

        const data = await response.json();

        if (data.success) {
            showSuccess(data.order);
        } else {
            showError(data.error || 'Ödeme işlemi başarısız');
        }
    } catch (error) {
        console.error('Error checking payment:', error);
        showError('Sistem hatası');
    }
}

function showSuccess(order) {
    const card = document.getElementById('resultCard');
    card.innerHTML = `
        <div class="text-6xl text-green-500 mb-6">
            <i class="fas fa-check-circle"></i>
        </div>
        <h2 class="text-2xl font-bold text-green-600 mb-4">
            Ödeme Başarılı
        </h2>
        <div class="text-gray-600 mb-6">
            <p class="mb-2">Sipariş No: #${order.id}</p>
            <p class="mb-2">Tutar: ${order.total} TL</p>
            <p>Tarih: ${new Date().toLocaleDateString('tr-TR')}</p>
        </div>
        <div class="space-y-4">
            <a href="profile.html#orders" 
               class="block w-full bg-blue-600 text-white px-6 py-3 rounded-full hover:bg-blue-700">
                Siparişlerim
            </a>
            <a href="index.html" 
               class="block w-full text-gray-600 px-6 py-3 rounded-full hover:bg-gray-100">
                Ana Sayfa
            </a>
        </div>
    `;
}

function showError(message) {
    const card = document.getElementById('resultCard');
    card.innerHTML = `
        <div class="text-6xl text-red-500 mb-6">
            <i class="fas fa-times-circle"></i>
        </div>
        <h2 class="text-2xl font-bold text-red-600 mb-4">
            Ödeme Başarısız
        </h2>
        <p class="text-gray-600 mb-6">${message}</p>
        <div class="space-y-4">
            <a href="checkout.html" 
               class="block w-full bg-blue-600 text-white px-6 py-3 rounded-full hover:bg-blue-700">
                Tekrar Dene
            </a>
            <a href="cart.html" 
               class="block w-full text-gray-600 px-6 py-3 rounded-full hover:bg-gray-100">
                Sepete Dön
            </a>
        </div>
    `;
}

// Initialize
document.addEventListener('DOMContentLoaded', checkPaymentStatus);
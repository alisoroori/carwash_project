let orderData = {
    items: [],
    subtotal: 0,
    tax: 0,
    discount: 0,
    total: 0
};

// Load order data
async function loadOrderData() {
    try {
        const response = await fetch('../backend/api/get_cart.php');
        const data = await response.json();
        
        if (data.success) {
            orderData = data.cart;
            renderOrderSummary();
            updateTotals();
        } else {
            window.location.href = 'cart.html';
        }
    } catch (error) {
        console.error('Error loading order:', error);
        window.location.href = 'cart.html';
    }
}

// Render order summary
function renderOrderSummary() {
    const container = document.getElementById('orderSummary');
    container.innerHTML = orderData.items.map(item => `
        <div class="flex justify-between items-center">
            <div>
                <h3 class="font-semibold">${item.service_name}</h3>
                <p class="text-sm text-gray-600">${item.carwash_name}</p>
                <p class="text-xs text-gray-500">${item.date} - ${item.time}</p>
            </div>
            <div class="font-bold">${item.price} TL</div>
        </div>
    `).join('');
}

// Update totals display
function updateTotals() {
    document.getElementById('subtotal').textContent = orderData.subtotal.toFixed(2) + ' TL';
    document.getElementById('tax').textContent = orderData.tax.toFixed(2) + ' TL';
    
    if (orderData.discount > 0) {
        document.getElementById('discountRow').style.display = 'flex';
        document.getElementById('discount').textContent = '-' + orderData.discount.toFixed(2) + ' TL';
    }
    
    document.getElementById('total').textContent = orderData.total.toFixed(2) + ' TL';
}

// Apply coupon code
async function applyCoupon() {
    const code = document.getElementById('couponCode').value;
    if (!code) return;

    try {
        const response = await fetch('../backend/api/apply_coupon.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ code })
        });
        
        const data = await response.json();
        
        if (data.success) {
            orderData.discount = data.discount;
            orderData.total = orderData.subtotal + orderData.tax - orderData.discount;
            updateTotals();
            alert('İndirim uygulandı!');
        } else {
            alert(data.error || 'Geçersiz kupon kodu');
        }
    } catch (error) {
        console.error('Error applying coupon:', error);
        alert('Kupon uygulanırken bir hata oluştu');
    }
}

// Initiate payment
async function initiatePayment(orderData) {
    try {
        const response = await fetch('../backend/api/initiate_payment.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(orderData)
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Redirect to payment page
            window.location.href = data.paymentUrl;
        } else {
            showError(data.error || 'Ödeme başlatılırken bir hata oluştu');
        }
    } catch (error) {
        console.error('Payment error:', error);
        showError('Ödeme işlemi başlatılamadı');
    }
}

// Handle payment form submission
document.getElementById('paymentForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    formData.append('order_data', JSON.stringify(orderData));

    try {
        const response = await fetch('../backend/api/process_payment.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            window.location.href = `confirmation.html?order_id=${data.order_id}`;
        } else {
            alert(data.error || 'Ödeme işlemi başarısız');
        }
    } catch (error) {
        console.error('Payment error:', error);
        alert('Ödeme işlemi sırasında bir hata oluştu');
    }
});

// Initialize page
document.addEventListener('DOMContentLoaded', loadOrderData);
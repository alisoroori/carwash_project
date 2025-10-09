document.addEventListener('DOMContentLoaded', function() {
    const bookingId = new URLSearchParams(window.location.search).get('booking');
    if (!bookingId) {
        window.location.href = '/carwash_project/frontend/dashboard/customer/';
        return;
    }

    loadBookingDetails(bookingId);
    setupEventListeners();
});

async function loadBookingDetails(bookingId) {
    try {
        const response = await fetch(`/carwash_project/backend/api/booking/details.php?id=${bookingId}`);
        const data = await response.json();

        if (data.success) {
            document.getElementById('bookingId').value = bookingId;
            document.getElementById('bookingDetails').innerHTML = `
                <div class="booking-info">
                    <p><strong>Car Wash:</strong> ${data.booking.carwash_name}</p>
                    <p><strong>Service:</strong> ${data.booking.service_name}</p>
                    <p><strong>Date:</strong> ${formatDate(data.booking.booking_date)}</p>
                    <p><strong>Time:</strong> ${formatTime(data.booking.booking_time)}</p>
                </div>
            `;
            document.getElementById('totalAmount').textContent = 
                `$${data.booking.total_price.toFixed(2)}`;
        } else {
            showNotification(data.error, 'error');
        }
    } catch (error) {
        console.error('Error loading booking details:', error);
        showNotification('Failed to load booking details', 'error');
    }
}

function setupEventListeners() {
    const paymentForm = document.getElementById('paymentForm');
    const cardNumber = document.getElementById('cardNumber');
    const expiryDate = document.getElementById('expiryDate');
    const cvv = document.getElementById('cvv');

    paymentForm.addEventListener('submit', handlePayment);
    
    // Format card number
    cardNumber.addEventListener('input', function(e) {
        let value = e.target.value.replace(/\s/g, '').replace(/\D/g, '');
        let formatted = value.match(/.{1,4}/g)?.join(' ') ?? '';
        e.target.value = formatted;
    });

    // Format expiry date
    expiryDate.addEventListener('input', function(e) {
        let value = e.target.value.replace(/\D/g, '');
        if (value.length >= 2) {
            value = value.slice(0,2) + '/' + value.slice(2);
        }
        e.target.value = value;
    });

    // Format CVV
    cvv.addEventListener('input', function(e) {
        e.target.value = e.target.value.replace(/\D/g, '');
    });
}

async function handlePayment(e) {
    e.preventDefault();
    
    const payButton = e.target.querySelector('button[type="submit"]');
    payButton.disabled = true;
    payButton.textContent = 'Processing...';

    try {
        const formData = new FormData(e.target);
        const response = await fetch('/carwash_project/backend/api/payment/process.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                booking_id: formData.get('booking_id'),
                card_number: formData.get('cardNumber').replace(/\s/g, ''),
                expiry_date: formData.get('expiryDate'),
                cvv: formData.get('cvv')
            })
        });

        const data = await response.json();
        if (data.success) {
            showReceipt(data.receipt);
        } else {
            showNotification(data.error, 'error');
        }
    } catch (error) {
        console.error('Payment error:', error);
        showNotification('Payment failed. Please try again.', 'error');
    } finally {
        payButton.disabled = false;
        payButton.textContent = 'Pay Now';
    }
}

function showReceipt(receiptData) {
    document.getElementById('receiptDateTime').textContent = 
        new Date().toLocaleString();
    
    document.getElementById('receiptContent').innerHTML = `
        <div class="receipt-details">
            <h3>Payment Successful</h3>
            <p><strong>Transaction ID:</strong> ${receiptData.transaction_id}</p>
            <p><strong>Amount Paid:</strong> $${receiptData.amount.toFixed(2)}</p>
            <p><strong>Payment Method:</strong> Card ending in ${receiptData.card_last4}</p>
            <hr>
            <h4>Booking Details</h4>
            <p><strong>Car Wash:</strong> ${receiptData.carwash_name}</p>
            <p><strong>Service:</strong> ${receiptData.service_name}</p>
            <p><strong>Date:</strong> ${formatDate(receiptData.booking_date)}</p>
            <p><strong>Time:</strong> ${formatTime(receiptData.booking_time)}</p>
        </div>
    `;

    document.getElementById('receiptModal').style.display = 'block';
}

async function downloadReceipt() {
    const bookingId = document.getElementById('bookingId').value;
    try {
        const response = await fetch(
            `/carwash_project/backend/api/payment/download_receipt.php?booking_id=${bookingId}`
        );
        const blob = await response.blob();
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `receipt-${bookingId}.pdf`;
        document.body.appendChild(a);
        a.click();
        window.URL.revokeObjectURL(url);
        a.remove();
    } catch (error) {
        console.error('Error downloading receipt:', error);
        showNotification('Failed to download receipt', 'error');
    }
}

async function emailReceipt() {
    const bookingId = document.getElementById('bookingId').value;
    try {
        const response = await fetch('/carwash_project/backend/api/payment/email_receipt.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ booking_id: bookingId })
        });

        const data = await response.json();
        if (data.success) {
            showNotification('Receipt sent to your email', 'success');
        } else {
            showNotification(data.error, 'error');
        }
    } catch (error) {
        console.error('Error emailing receipt:', error);
        showNotification('Failed to send receipt', 'error');
    }
}

function formatDate(dateStr) {
    return new Date(dateStr).toLocaleDateString();
}

function formatTime(timeStr) {
    return new Date(`2000-01-01T${timeStr}`).toLocaleTimeString([], 
        { hour: '2-digit', minute: '2-digit' });
}

function showNotification(message, type) {
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.textContent = message;
    document.body.appendChild(notification);
    setTimeout(() => notification.remove(), 3000);
}
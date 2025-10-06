let cart = {
    items: [],
    subtotal: 0,
    tax: 0,
    total: 0
};

// Load cart data from localStorage
function loadCart() {
    const savedCart = localStorage.getItem('carwashCart');
    if (savedCart) {
        cart = JSON.parse(savedCart);
        renderCart();
        updateTotals();
    }
}

// Render cart items
function renderCart() {
    const container = document.getElementById('cartItems');
    if (cart.items.length === 0) {
        container.innerHTML = '<p class="text-gray-500">Sepetiniz boş</p>';
        return;
    }

    container.innerHTML = cart.items.map((item, index) => `
        <div class="flex items-center justify-between border-b pb-4 mb-4">
            <div>
                <h3 class="font-semibold">${item.service_name}</h3>
                <p class="text-gray-600">${item.carwash_name}</p>
                <p class="text-sm text-gray-500">
                    ${item.date} - ${item.time}
                </p>
            </div>
            <div class="text-right">
                <p class="font-bold">${item.price} TL</p>
                <button onclick="removeItem(${index})" 
                        class="text-red-600 text-sm hover:text-red-800">
                    <i class="fas fa-trash"></i> Kaldır
                </button>
            </div>
        </div>
    `).join('');
}

// Update totals
function updateTotals() {
    cart.subtotal = cart.items.reduce((sum, item) => sum + parseFloat(item.price), 0);
    cart.tax = cart.subtotal * 0.18;
    cart.total = cart.subtotal + cart.tax;

    document.getElementById('subtotal').textContent = cart.subtotal.toFixed(2) + ' TL';
    document.getElementById('tax').textContent = cart.tax.toFixed(2) + ' TL';
    document.getElementById('total').textContent = cart.total.toFixed(2) + ' TL';
}

// Remove item from cart
function removeItem(index) {
    cart.items.splice(index, 1);
    localStorage.setItem('carwashCart', JSON.stringify(cart));
    renderCart();
    updateTotals();
}

// Proceed to checkout
function proceedToCheckout() {
    if (cart.items.length === 0) {
        alert('Sepetiniz boş!');
        return;
    }

    // Save cart data to session
    fetch('../backend/api/save_cart.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(cart)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            window.location.href = 'checkout.html';
        } else {
            alert(data.error || 'Bir hata oluştu');
        }
    });
}

// Initialize cart on page load
document.addEventListener('DOMContentLoaded', loadCart);
// Tab Management - مدیریت تب‌ها
// EN: Tab management functionality
// TR: Sekme yönetimi işlevselliği
let activeTab = 'profile';
const sections = ['profile', 'reservations', 'orders', 'settings'];

function showSection(sectionId) {
    sections.forEach(section => {
        const element = document.getElementById(`${section}Section`);
        if (element) {
            element.classList.toggle('hidden', section !== sectionId);
        }
    });
    
    // Update active tab styling
    document.querySelectorAll('nav a').forEach(link => {
        const isActive = link.getAttribute('href').slice(1) === sectionId;
        link.classList.toggle('text-blue-600', isActive);
        link.classList.toggle('bg-blue-50', isActive);
    });
    
    if (sectionId === 'reservations') {
        loadReservations();
    } else if (sectionId === 'orders') {
        loadOrders();
    }
}

// Profile Data Management - مدیریت اطلاعات پروفایل
// TR: Profil Bilgileri Yönetimi
async function loadProfileData() {
    try {
        const response = await fetch('../backend/api/get_profile.php');
        const data = await response.json();
        
        if (data.success) {
            // Fill form fields
            document.querySelector('[name="name"]').value = data.user.name || '';
            document.querySelector('[name="email"]').value = data.user.email || '';
            document.querySelector('[name="phone"]').value = data.user.phone || '';
            
            // Update profile image and username
            document.getElementById('userName').textContent = data.user.name;
            if (data.user.profile_image) {
                document.getElementById('profileImage').src = data.user.profile_image;
            }
        }
    } catch (error) {
        console.error('Error loading profile:', error);
        showError('Profil bilgileri yüklenemedi');
    }
}

// Reservation Management - مدیریت رزروها
// TR: Rezervasyon Yönetimi
async function loadReservations() {
    try {
        const response = await fetch('../backend/api/get_reservations.php');
        const data = await response.json();
        
        if (data.success) {
            const container = document.getElementById('reservationsList');
            container.innerHTML = data.reservations.map(reservation => `
                <div class="border rounded-lg p-4 flex justify-between items-center">
                    <div>
                        <h4 class="font-semibold">${reservation.service_name}</h4>
                        <p class="text-sm text-gray-600">${reservation.date} - ${reservation.time}</p>
                        <span class="inline-block px-2 py-1 text-xs rounded-full ${getStatusClass(reservation.status)}">
                            ${getStatusText(reservation.status)}
                        </span>
                    </div>
                    <div class="space-x-2">
                        ${reservation.status === 'pending' ? `
                            <button onclick="cancelReservation(${reservation.id})"
                                    class="text-red-600 hover:text-red-800">
                                <i class="fas fa-times"></i> İptal
                            </button>
                        ` : ''}
                    </div>
                </div>
            `).join('') || '<p class="text-gray-500">Henüz rezervasyonunuz bulunmamaktadır.</p>';
        }
    } catch (error) {
        console.error('Error loading reservations:', error);
        showError('Rezervasyonlar yüklenemedi');
    }
}

// Order Management - مدیریت سفارش‌ها
// TR: Sipariş Yönetimi
async function loadOrders() {
    try {
        const response = await fetch('../backend/api/get_orders.php');
        const data = await response.json();
        
        if (data.success) {
            const container = document.getElementById('ordersList');
            container.innerHTML = data.orders.map(order => `
                <div class="border rounded-lg p-4">
                    <div class="flex justify-between items-center mb-2">
                        <h4 class="font-semibold">Sipariş #${order.id}</h4>
                        <span class="text-gray-600">${formatDate(order.created_at)}</span>
                    </div>
                    <div class="text-sm text-gray-600">
                        <p>Toplam: ${order.total} TL</p>
                        <p>Durum: ${getStatusText(order.status)}</p>
                    </div>
                    <div class="mt-2 space-x-2">
                        <button onclick="viewOrderDetail(${order.id})"
                                class="text-blue-600 hover:text-blue-800 text-sm">
                            Detayları Görüntüle
                        </button>
                        ${order.status === 'completed' ? `
                            <button onclick="downloadInvoice(${order.id})"
                                    class="text-green-600 hover:text-green-800 text-sm">
                                <i class="fas fa-file-pdf"></i> Fatura İndir
                            </button>
                        ` : ''}
                    </div>
                </div>
            `).join('') || '<p class="text-gray-500">Henüz siparişiniz bulunmamaktadır.</p>';
        }
    } catch (error) {
        console.error('Error loading orders:', error);
        showError('Siparişler yüklenemedi');
    }
}

// Settings Management - مدیریت تنظیمات
// TR: Ayarlar Yönetimi
document.getElementById('settingsForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    try {
        const response = await fetch('../backend/api/update_password.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            showSuccess('Şifreniz başarıyla güncellendi');
            this.reset();
        } else {
            showError(data.error || 'Şifre güncellenirken bir hata oluştu');
        }
    } catch (error) {
        console.error('Error updating password:', error);
        showError('Şifre güncellenirken bir hata oluştu');
    }
});

// Utility Functions - توابع کمکی
// TR: Yardımcı Fonksiyonlar
function formatDate(dateString) {
    return new Date(dateString).toLocaleDateString('tr-TR');
}

function getStatusClass(status) {
    const classes = {
        pending: 'bg-yellow-100 text-yellow-800',
        completed: 'bg-green-100 text-green-800',
        cancelled: 'bg-red-100 text-red-800'
    };
    return classes[status] || 'bg-gray-100 text-gray-800';
}

function getStatusText(status) {
    const texts = {
        pending: 'Beklemede',
        completed: 'Tamamlandı',
        cancelled: 'İptal Edildi'
    };
    return texts[status] || status;
}

function showError(message) {
    // Add your error notification logic here
    alert(message);
}

function showSuccess(message) {
    // Add your success notification logic here
    alert(message);
}

// Add this function to the existing profile.js file
function downloadInvoice(orderId) {
    window.open(`../backend/api/download_invoice.php?order_id=${orderId}`, '_blank');
}

// Initialize - راه‌اندازی
// TR: Başlangıç
document.addEventListener('DOMContentLoaded', () => {
    // Load initial data
    loadProfileData();
    
    // Setup tab navigation
    document.querySelectorAll('nav a').forEach(link => {
        link.addEventListener('click', (e) => {
            e.preventDefault();
            const sectionId = e.currentTarget.getAttribute('href').slice(1);
            showSection(sectionId);
        });
    });
});
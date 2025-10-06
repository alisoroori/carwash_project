// Booking functionality
let selectedService = null;

function openBookingModal(serviceType, price) {
    selectedService = { type: serviceType, price: price };
    document.getElementById('serviceType').value = serviceType;
    document.getElementById('servicePrice').value = price;
    document.getElementById('bookingModal').classList.remove('hidden');
    
    // Set minimum date to today
    const dateInput = document.querySelector('input[name="booking_date"]');
    dateInput.min = new Date().toISOString().split('T')[0];
    
    // Clear previous time slots
    const timeSelect = document.querySelector('select[name="booking_time"]');
    timeSelect.innerHTML = '';
}

function closeBookingModal() {
    document.getElementById('bookingModal').classList.add('hidden');
}

// Update available time slots when date is selected
document.querySelector('input[name="booking_date"]').addEventListener('change', async function(e) {
    const date = e.target.value;
    const timeSelect = document.querySelector('select[name="booking_time"]');
    timeSelect.innerHTML = '<option value="">Yükleniyor...</option>';

    try {
        const response = await fetch('../backend/api/get_available_times.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `date=${date}&service_type=${selectedService.type}`
        });
        
        const data = await response.json();
        
        if (data.success) {
            timeSelect.innerHTML = data.times.map(time => 
                `<option value="${time}">${time}</option>`
            ).join('');
        } else {
            timeSelect.innerHTML = '<option value="">Uygun saat bulunamadı</option>';
        }
    } catch (error) {
        console.error('Error fetching times:', error);
        timeSelect.innerHTML = '<option value="">Hata oluştu</option>';
    }
});

// Handle form submission
document.getElementById('bookingForm').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    
    try {
        const response = await fetch('../backend/api/create_booking.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'Başarılı!',
                text: 'Rezervasyonunuz oluşturuldu. Onay emaili gönderildi.',
                confirmButtonText: 'Tamam'
            });
            closeBookingModal();
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Hata!',
                text: data.error || 'Rezervasyon oluşturulurken bir hata oluştu.',
                confirmButtonText: 'Tamam'
            });
        }
    } catch (error) {
        console.error('Error creating booking:', error);
        Swal.fire({
            icon: 'error',
            title: 'Hata!',
            text: 'Rezervasyon oluşturulurken bir hata oluştu.',
            confirmButtonText: 'Tamam'
        });
    }
});
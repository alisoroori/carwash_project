document.addEventListener('DOMContentLoaded', function() {
    loadServices();
    setupEventListeners();
});

async function loadServices() {
    try {
        const response = await fetch('/carwash_project/backend/api/services/get_services.php');
        const data = await response.json();
        
        if (data.success) {
            renderServices(data.services);
        } else {
            showNotification(data.error, 'error');
        }
    } catch (error) {
        console.error('Error loading services:', error);
        showNotification('Failed to load services', 'error');
    }
}

function renderServices(services) {
    const grid = document.getElementById('servicesGrid');
    grid.innerHTML = services.map(service => `
        <div class="service-card">
            <div class="service-header">
                <h3>${service.name}</h3>
                <span class="service-price">$${service.price.toFixed(2)}</span>
            </div>
            <div class="service-details">
                <p>${service.description}</p>
                <p><strong>Duration:</strong> ${service.duration} minutes</p>
            </div>
            <ul class="service-features">
                ${service.features ? service.features.map(feature => 
                    `<li>${feature}</li>`).join('') : ''}
            </ul>
            <button class="btn-book" 
                    onclick="openBookingModal(${service.id})">
                Book Now
            </button>
        </div>
    `).join('');
}

function setupEventListeners() {
    // Search functionality
    document.getElementById('searchInput').addEventListener('input', filterServices);
    
    // Sorting functionality
    document.getElementById('sortSelect').addEventListener('change', sortServices);
    
    // Booking form submission
    document.getElementById('bookingForm').addEventListener('submit', handleBooking);
    
    // Modal close button
    document.querySelector('.modal .close').addEventListener('click', closeBookingModal);
}

function filterServices(e) {
    const searchTerm = e.target.value.toLowerCase();
    const cards = document.querySelectorAll('.service-card');
    
    cards.forEach(card => {
        const name = card.querySelector('h3').textContent.toLowerCase();
        const description = card.querySelector('.service-details p').textContent.toLowerCase();
        
        if (name.includes(searchTerm) || description.includes(searchTerm)) {
            card.style.display = '';
        } else {
            card.style.display = 'none';
        }
    });
}

async function openBookingModal(serviceId) {
    document.getElementById('serviceId').value = serviceId;
    
    // Set minimum date to today
    const dateInput = document.getElementById('bookingDate');
    const today = new Date().toISOString().split('T')[0];
    dateInput.min = today;
    dateInput.value = today;
    
    // Load available time slots
    await loadTimeSlots(serviceId, today);
    
    document.getElementById('bookingModal').style.display = 'block';
}

async function loadTimeSlots(serviceId, date) {
    try {
        const response = await fetch(
            `/carwash_project/backend/api/booking/get_timeslots.php?service_id=${serviceId}&date=${date}`
        );
        const data = await response.json();
        
        if (data.success) {
            const select = document.getElementById('bookingTime');
            select.innerHTML = data.slots.map(slot => 
                `<option value="${slot.time}">${slot.time}</option>`
            ).join('');
        } else {
            showNotification(data.error, 'error');
        }
    } catch (error) {
        console.error('Error loading time slots:', error);
        showNotification('Failed to load available times', 'error');
    }
}

async function handleBooking(e) {
    e.preventDefault();
    
    try {
        const formData = new FormData(e.target);
        const response = await fetch('/carwash_project/backend/api/booking/create.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(Object.fromEntries(formData))
        });
        
        const data = await response.json();
        if (data.success) {
            showNotification('Booking created successfully!', 'success');
            closeBookingModal();
            window.location.href = `/carwash_project/frontend/dashboard/customer/bookings.html`;
        } else {
            showNotification(data.error, 'error');
        }
    } catch (error) {
        console.error('Error creating booking:', error);
        showNotification('Failed to create booking', 'error');
    }
}

function closeBookingModal() {
    document.getElementById('bookingModal').style.display = 'none';
}

function showNotification(message, type) {
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.textContent = message;
    document.body.appendChild(notification);
    setTimeout(() => notification.remove(), 3000);
}
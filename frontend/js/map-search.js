let map;
let markers = [];
let currentPosition;

document.addEventListener('DOMContentLoaded', function() {
    initializeMap();
    setupEventListeners();
    loadServices();
});

async function initializeMap() {
    // Initialize the map centered on user's location
    map = L.map('map');
    
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors'
    }).addTo(map);

    // Get user's location
    try {
        const position = await getCurrentPosition();
        currentPosition = [position.coords.latitude, position.coords.longitude];
        map.setView(currentPosition, 13);
        
        // Add marker for user's location
        L.marker(currentPosition, {
            icon: L.divIcon({
                className: 'user-marker',
                html: '📍'
            })
        }).addTo(map);

        // Initial search in the area
        searchCarWashes();
    } catch (error) {
        console.error('Error getting location:', error);
        map.setView([51.505, -0.09], 13); // Default location
    }
}

function getCurrentPosition() {
    return new Promise((resolve, reject) => {
        navigator.geolocation.getCurrentPosition(resolve, reject);
    });
}

function setupEventListeners() {
    document.getElementById('searchButton').addEventListener('click', searchCarWashes);
    document.getElementById('searchInput').addEventListener('input', debounce(searchCarWashes, 500));
    document.getElementById('serviceFilter').addEventListener('change', searchCarWashes);
    document.getElementById('ratingFilter').addEventListener('change', searchCarWashes);
    
    // Close modal when clicking outside
    window.onclick = function(event) {
        if (event.target == document.getElementById('detailsModal')) {
            document.getElementById('detailsModal').style.display = 'none';
        }
    };
}

async function searchCarWashes() {
    const searchTerm = document.getElementById('searchInput').value;
    const serviceFilter = document.getElementById('serviceFilter').value;
    const ratingFilter = document.getElementById('ratingFilter').value;
    
    try {
        const response = await fetch(`/carwash_project/backend/api/search_carwash.php?` + 
            `query=${encodeURIComponent(searchTerm)}` +
            `&service=${encodeURIComponent(serviceFilter)}` +
            `&rating=${encodeURIComponent(ratingFilter)}` +
            `&lat=${currentPosition[0]}` +
            `&lng=${currentPosition[1]}`
        );
        
        const data = await response.json();
        if (data.success) {
            clearMarkers();
            renderResults(data.results);
            addMarkersToMap(data.results);
        } else {
            showNotification(data.error, 'error');
        }
    } catch (error) {
        console.error('Error searching car washes:', error);
        showNotification('Failed to search car washes', 'error');
    }
}

function renderResults(carwashes) {
    const resultsList = document.getElementById('resultsList');
    resultsList.innerHTML = carwashes.map(carwash => `
        <div class="carwash-item" onclick="showCarWashDetails(${carwash.id})">
            <h3>${carwash.name}</h3>
            <div class="rating">
                ${'★'.repeat(Math.round(carwash.rating))}
                ${'☆'.repeat(5 - Math.round(carwash.rating))}
                (${carwash.rating_count})
            </div>
            <div class="address">${carwash.address}</div>
        </div>
    `).join('');
}

function addMarkersToMap(carwashes) {
    carwashes.forEach(carwash => {
        const marker = L.marker([carwash.latitude, carwash.longitude])
            .bindPopup(`
                <div class="marker-popup">
                    <h3>${carwash.name}</h3>
                    <div class="rating">
                        ${'★'.repeat(Math.round(carwash.rating))}
                        (${carwash.rating_count})
                    </div>
                    <div class="actions">
                        <button class="btn btn-primary" 
                                onclick="showCarWashDetails(${carwash.id})">
                            View Details
                        </button>
                        <button class="btn btn-secondary" 
                                onclick="getDirections(${carwash.latitude}, ${carwash.longitude})">
                            Directions
                        </button>
                    </div>
                </div>
            `);
        markers.push(marker);
        marker.addTo(map);
    });
}

function clearMarkers() {
    markers.forEach(marker => marker.remove());
    markers = [];
}

async function showCarWashDetails(carwashId) {
    try {
        const response = await fetch(`/carwash_project/backend/api/carwash/details.php?id=${carwashId}`);
        const data = await response.json();
        
        if (data.success) {
            document.getElementById('carwashDetails').innerHTML = `
                <h2>${data.carwash.name}</h2>
                <div class="rating">
                    ${'★'.repeat(Math.round(data.carwash.rating))}
                    (${data.carwash.rating_count} reviews)
                </div>
                <p class="address">${data.carwash.address}</p>
                <p class="phone">${data.carwash.phone}</p>
                <h3>Services</h3>
                <ul class="services-list">
                    ${data.carwash.services.map(service => `
                        <li>
                            <span>${service.name}</span>
                            <span>$${service.price.toFixed(2)}</span>
                        </li>
                    `).join('')}
                </ul>
                <button class="btn-primary" onclick="location.href='/carwash_project/frontend/booking.html?carwash=${carwashId}'">
                    Book Now
                </button>
            `;
            document.getElementById('detailsModal').style.display = 'block';
        } else {
            showNotification(data.error, 'error');
        }
    } catch (error) {
        console.error('Error loading car wash details:', error);
        showNotification('Failed to load details', 'error');
    }
}

function getDirections(lat, lng) {
    window.open(`https://www.google.com/maps/dir/?api=1&destination=${lat},${lng}`);
}

function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

function showNotification(message, type) {
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.textContent = message;
    document.body.appendChild(notification);
    setTimeout(() => notification.remove(), 3000);
}
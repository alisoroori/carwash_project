let map;
let markers = [];
let currentView = 'grid';

// Initialize map
function initMap() {
    map = L.map('map').setView([41.0082, 28.9784], 13); // Istanbul coordinates
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors'
    }).addTo(map);

    // Get user location
    if ("geolocation" in navigator) {
        navigator.geolocation.getCurrentPosition(function(position) {
            map.setView([position.coords.latitude, position.coords.longitude], 13);
            searchNearby(position.coords.latitude, position.coords.longitude);
        });
    }
}

// Search for nearby carwashes
async function searchNearby(lat, lng) {
    try {
        const response = await fetch(`../backend/api/search_carwash.php?lat=${lat}&lng=${lng}`);
        const data = await response.json();
        displayResults(data);
        updateMap(data);
    } catch (error) {
        console.error('Error fetching results:', error);
    }
}

// Display results in grid/list
function displayResults(results) {
    const container = document.getElementById('results_container');
    container.innerHTML = '';
    document.getElementById('result_count').textContent = results.length;

    results.forEach(result => {
        const card = createResultCard(result);
        container.appendChild(card);
    });
}

// Create result card
function createResultCard(data) {
    const div = document.createElement('div');
    div.className = 'bg-white rounded-lg shadow-md overflow-hidden';
    div.innerHTML = `
        <img src="${data.image || 'images/default-carwash.jpg'}" 
             alt="${data.business_name}" 
             class="w-full h-48 object-cover">
        <div class="p-4">
            <h3 class="text-lg font-semibold">${data.business_name}</h3>
            <div class="flex items-center mt-2">
                <div class="text-yellow-400">
                    ${createStarRating(data.rating)}
                </div>
                <span class="ml-2 text-gray-600">${data.rating} (${data.review_count})</span>
            </div>
            <p class="text-gray-600 mt-2">${data.address}</p>
            <div class="mt-4 flex justify-between items-center">
                <span class="text-blue-600 font-semibold">${data.price_range}</span>
                <a href="carwash_detail.html?id=${data.id}" 
                   class="bg-blue-600 text-white px-4 py-2 rounded-full hover:bg-blue-700">
                    Rezervasyon Yap
                </a>
            </div>
        </div>
    `;
    return div;
}

// Create star rating HTML
function createStarRating(rating) {
    const fullStars = Math.floor(rating);
    const hasHalfStar = rating % 1 >= 0.5;
    let html = '';
    
    for (let i = 0; i < 5; i++) {
        if (i < fullStars) {
            html += '<i class="fas fa-star"></i>';
        } else if (i === fullStars && hasHalfStar) {
            html += '<i class="fas fa-star-half-alt"></i>';
        } else {
            html += '<i class="far fa-star"></i>';
        }
    }
    return html;
}

// Update map markers
function updateMap(results) {
    // Clear existing markers
    markers.forEach(marker => map.removeLayer(marker));
    markers = [];

    // Add new markers
    results.forEach(result => {
        const marker = L.marker([result.lat, result.lng])
            .bindPopup(`
                <strong>${result.business_name}</strong><br>
                Rating: ${result.rating}<br>
                <a href="carwash_detail.html?id=${result.id}">Detaylar</a>
            `)
            .addTo(map);
        markers.push(marker);
    });
}

// Toggle view between grid and list
function toggleView(view) {
    const container = document.getElementById('results_container');
    currentView = view;
    
    if (view === 'list') {
        container.classList.remove('md:grid-cols-2');
        container.classList.add('md:grid-cols-1');
    } else {
        container.classList.remove('md:grid-cols-1');
        container.classList.add('md:grid-cols-2');
    }
}

// Initialize map on page load
document.addEventListener('DOMContentLoaded', initMap);

// Add event listeners for filters
document.querySelectorAll('select, input').forEach(element => {
    element.addEventListener('change', () => {
        const filters = {
            location: document.getElementById('location').value,
            service_type: document.getElementById('service_type').value,
            price_range: document.getElementById('price_range').value,
            sort_by: document.getElementById('sort_by').value
        };
        applyFilters(filters);
    });
});

class CarWashSearch {
    constructor() {
        this.form = document.getElementById('searchForm');
        this.resultsContainer = document.getElementById('searchResults');
        this.init();
    }

    init() {
        this.form.addEventListener('submit', (e) => {
            e.preventDefault();
            this.performSearch();
        });

        // Initialize location autocomplete
        this.initLocationAutocomplete();
    }

    async performSearch() {
        const location = document.getElementById('location').value;
        const serviceType = document.getElementById('serviceType').value;

        try {
            const response = await fetch('../backend/api/search/find_carwash.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    location,
                    serviceType
                })
            });

            const data = await response.json();
            if (data.success) {
                this.renderResults(data.carwashes);
            } else {
                this.showError(data.error);
            }
        } catch (error) {
            this.showError('Search failed. Please try again.');
        }
    }

    renderResults(carwashes) {
        this.resultsContainer.innerHTML = carwashes.map(carwash => `
            <div class="bg-white rounded-lg shadow-lg overflow-hidden">
                <img src="${carwash.image}" alt="${carwash.name}" 
                     class="w-full h-48 object-cover">
                <div class="p-6">
                    <h3 class="text-xl font-semibold mb-2">${carwash.name}</h3>
                    <div class="flex items-center mb-2">
                        <i class="fas fa-star text-yellow-400"></i>
                        <span class="ml-1">${carwash.rating} (${carwash.reviews} reviews)</span>
                    </div>
                    <p class="text-gray-600 mb-4">${carwash.address}</p>
                    <div class="flex justify-between items-center">
                        <span class="text-blue-600 font-semibold">
                            Starting from ${carwash.price_range}
                        </span>
                        <button onclick="window.location.href='booking.php?id=${carwash.id}'"
                                class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                            Book Now
                        </button>
                    </div>
                </div>
            </div>
        `).join('');
    }

    showError(message) {
        this.resultsContainer.innerHTML = `
            <div class="col-span-3 text-center py-8">
                <div class="text-red-600 mb-2">
                    <i class="fas fa-exclamation-circle text-xl"></i>
                </div>
                <p class="text-gray-600">${message}</p>
            </div>
        `;
    }

    initLocationAutocomplete() {
        // Initialize location autocomplete using browser's geolocation
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition((position) => {
                this.reverseGeocode(position.coords.latitude, position.coords.longitude);
            });
        }
    }

    async reverseGeocode(lat, lng) {
        try {
            const response = await fetch(
                `../backend/api/location/reverse_geocode.php?lat=${lat}&lng=${lng}`
            );
            const data = await response.json();
            if (data.success) {
                document.getElementById('location').value = data.address;
            }
        } catch (error) {
            console.error('Geocoding failed:', error);
        }
    }
}

// Initialize search functionality
const carWashSearch = new CarWashSearch();
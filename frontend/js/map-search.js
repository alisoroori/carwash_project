/**
 * CarWash Map Search JavaScript
 * Integrated map search functionality following CarWash project conventions
 * 
 * @version 1.0.0
 * @author CarWash Team
 */

'use strict';

// ========================================
// Configuration & Constants
// ========================================

const MAP_SEARCH_CONFIG = {
    // API Endpoints - Following project structure
    API_BASE: '/carwash_project/backend',
    ENDPOINTS: {
        SEARCH: '/api/search_carwash.php',
        DETAILS: '/api/carwash_details.php',
        SERVICES: '/api/services.php'
    },
    
    // Map Configuration
    MAP: {
        DEFAULT_CENTER: [41.0082, 28.9784], // Istanbul, Turkey
        DEFAULT_ZOOM: 13,
        USER_ZOOM: 15,
        SEARCH_RADIUS: 10000, // 10km in meters
        TILE_LAYER: 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
        ATTRIBUTION: '© OpenStreetMap contributors'
    },
    
    // Performance Settings
    DEBOUNCE_DELAY: 500,
    MAX_RESULTS: 50,
    NOTIFICATION_DURATION: 3000,
    
    // Animation Settings
    ANIMATION_DURATION: 300,
    ZOOM_ANIMATION: true,
    
    // Turkish localization
    LANGUAGE: 'tr',
    
    // Error Messages
    ERRORS: {
        LOCATION_DENIED: 'Konum erişimi reddedildi. Varsayılan konum kullanılıyor.',
        LOCATION_UNAVAILABLE: 'Konum bilgisi alınamadı.',
        SEARCH_FAILED: 'Arama sırasında bir hata oluştu.',
        DETAILS_FAILED: 'Detaylar yüklenirken hata oluştu.',
        NETWORK_ERROR: 'Ağ bağlantısı hatası.'
    }
};

// ========================================
// Utility Functions
// ========================================

const MapSearchUtils = {
    /**
     * Debounce function to limit API calls
     */
    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    },

    /**
     * Calculate distance between two points using Haversine formula
     */
    calculateDistance(lat1, lon1, lat2, lon2) {
        const R = 6371; // Earth's radius in km
        const dLat = this.deg2rad(lat2 - lat1);
        const dLon = this.deg2rad(lon2 - lon1);
        const a = 
            Math.sin(dLat/2) * Math.sin(dLat/2) +
            Math.cos(this.deg2rad(lat1)) * Math.cos(this.deg2rad(lat2)) * 
            Math.sin(dLon/2) * Math.sin(dLon/2);
        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
        return R * c;
    },

    deg2rad(deg) {
        return deg * (Math.PI/180);
    },

    /**
     * Format distance for display
     */
    formatDistance(distance) {
        return distance < 1 ? 
            `${Math.round(distance * 1000)} m` : 
            `${distance.toFixed(1)} km`;
    },

    /**
     * Format price for Turkish locale
     */
    formatPrice(price) {
        return new Intl.NumberFormat('tr-TR', {
            style: 'currency',
            currency: 'TRY'
        }).format(price);
    },

    /**
     * Sanitize HTML to prevent XSS
     */
    sanitizeHTML(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    },

    /**
     * Show loading state
     */
    showLoading(element) {
        element.classList.add('loading');
        element.setAttribute('aria-busy', 'true');
    },

    /**
     * Hide loading state
     */
    hideLoading(element) {
        element.classList.remove('loading');
        element.removeAttribute('aria-busy');
    },

    /**
     * Generate star rating HTML
     */
    generateStarRating(rating, reviewCount = 0) {
        const fullStars = Math.floor(rating);
        const hasHalfStar = rating % 1 !== 0;
        const emptyStars = 5 - Math.ceil(rating);
        
        let starsHTML = '';
        
        // Full stars
        for (let i = 0; i < fullStars; i++) {
            starsHTML += '<i class="fas fa-star text-yellow-400"></i>';
        }
        
        // Half star
        if (hasHalfStar) {
            starsHTML += '<i class="fas fa-star-half-alt text-yellow-400"></i>';
        }
        
        // Empty stars
        for (let i = 0; i < emptyStars; i++) {
            starsHTML += '<i class="far fa-star text-gray-300"></i>';
        }
        
        return `
            <div class="flex items-center" role="img" aria-label="${rating} üzerinden 5 yıldız">
                ${starsHTML}
                <span class="ml-2 text-sm text-gray-600">(${reviewCount})</span>
            </div>
        `;
    }
};

// ========================================
// Map Manager Class
// ========================================

class MapSearchManager {
    constructor() {
        this.map = null;
        this.markers = [];
        this.markerCluster = null;
        this.userMarker = null;
        this.currentPosition = null;
        this.searchResults = [];
        this.isLoading = false;
        this.services = [];
        
        this.init();
    }

    // ========================================
    // Initialization
    // ========================================

    async init() {
        try {
            await this.initializeMap();
            this.setupEventListeners();
            await this.loadServices();
            await this.getUserLocation();
            
            console.log('Map Search initialized successfully');
        } catch (error) {
            console.error('Error initializing Map Search:', error);
            this.handleInitError(error);
        }
    }

    async initializeMap() {
        if (!window.L) {
            throw new Error('Leaflet library not found');
        }

        // Initialize map
        this.map = L.map('map', {
            center: MAP_SEARCH_CONFIG.MAP.DEFAULT_CENTER,
            zoom: MAP_SEARCH_CONFIG.MAP.DEFAULT_ZOOM,
            zoomControl: true,
            attributionControl: true
        });

        // Add tile layer
        L.tileLayer(MAP_SEARCH_CONFIG.MAP.TILE_LAYER, {
            attribution: MAP_SEARCH_CONFIG.MAP.ATTRIBUTION,
            maxZoom: 19
        }).addTo(this.map);

        // Initialize marker cluster if available
        if (window.L.markerClusterGroup) {
            this.markerCluster = L.markerClusterGroup({
                chunkedLoading: true,
                spiderfyOnMaxZoom: true,
                showCoverageOnHover: false
            });
            this.map.addLayer(this.markerCluster);
        }

        // Setup map events
        this.setupMapEvents();
    }

    setupMapEvents() {
        // Map move event for live search
        this.map.on('moveend', MapSearchUtils.debounce(() => {
            if (this.currentPosition) {
                this.searchCarWashes();
            }
        }, MAP_SEARCH_CONFIG.DEBOUNCE_DELAY));

        // Map click to close popups
        this.map.on('click', () => {
            this.closeDetailsModal();
        });
    }

    setupEventListeners() {
        // Search button
        const searchButton = document.getElementById('searchButton');
        if (searchButton) {
            searchButton.addEventListener('click', () => this.searchCarWashes());
        }

        // Search input with debounced search
        const searchInput = document.getElementById('searchInput');
        if (searchInput) {
            searchInput.addEventListener('input', 
                MapSearchUtils.debounce(() => this.searchCarWashes(), MAP_SEARCH_CONFIG.DEBOUNCE_DELAY)
            );
            
            // Enter key search
            searchInput.addEventListener('keypress', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    this.searchCarWashes();
                }
            });
        }

        // Filter changes
        const serviceFilter = document.getElementById('serviceFilter');
        if (serviceFilter) {
            serviceFilter.addEventListener('change', () => this.searchCarWashes());
        }

        const ratingFilter = document.getElementById('ratingFilter');
        if (ratingFilter) {
            ratingFilter.addEventListener('change', () => this.searchCarWashes());
        }

        // Modal events
        this.setupModalEvents();

        // Keyboard navigation
        document.addEventListener('keydown', (e) => this.handleKeyboardNavigation(e));
    }

    setupModalEvents() {
        const modal = document.getElementById('detailsModal');
        if (!modal) return;

        // Close modal on backdrop click
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                this.closeDetailsModal();
            }
        });

        // Close button
        const closeButton = modal.querySelector('.close, [data-close]');
        if (closeButton) {
            closeButton.addEventListener('click', () => this.closeDetailsModal());
        }
    }

    // ========================================
    // Location Management
    // ========================================

    async getUserLocation() {
        try {
            const position = await this.getCurrentPosition();
            this.currentPosition = [position.coords.latitude, position.coords.longitude];
            
            // Update map view
            this.map.setView(this.currentPosition, MAP_SEARCH_CONFIG.MAP.USER_ZOOM);
            
            // Add user location marker
            this.addUserLocationMarker();
            
            // Perform initial search
            await this.searchCarWashes();
            
            this.showNotification('Konumunuz belirlendi', 'success');
            
        } catch (error) {
            console.warn('Geolocation error:', error);
            this.handleLocationError(error);
        }
    }

    getCurrentPosition() {
        return new Promise((resolve, reject) => {
            if (!navigator.geolocation) {
                reject(new Error('Geolocation not supported'));
                return;
            }

            navigator.geolocation.getCurrentPosition(
                resolve,
                reject,
                {
                    enableHighAccuracy: true,
                    timeout: 10000,
                    maximumAge: 300000 // 5 minutes
                }
            );
        });
    }

    addUserLocationMarker() {
        if (!this.currentPosition) return;

        // Remove existing user marker
        if (this.userMarker) {
            this.map.removeLayer(this.userMarker);
        }

        // Create custom user icon
        const userIcon = L.divIcon({
            className: 'user-location-marker',
            html: `
                <div class="relative">
                    <div class="w-6 h-6 bg-blue-600 rounded-full border-2 border-white shadow-lg"></div>
                    <div class="absolute inset-0 w-6 h-6 bg-blue-600 rounded-full animate-ping opacity-75"></div>
                </div>
            `,
            iconSize: [24, 24],
            iconAnchor: [12, 12]
        });

        this.userMarker = L.marker(this.currentPosition, { 
            icon: userIcon,
            zIndexOffset: 1000
        }).addTo(this.map);

        this.userMarker.bindPopup(`
            <div class="text-center p-2">
                <i class="fas fa-user-circle text-blue-600 text-2xl mb-2"></i>
                <p class="font-semibold">Konumunuz</p>
            </div>
        `);
    }

    handleLocationError(error) {
        let message = MAP_SEARCH_CONFIG.ERRORS.LOCATION_UNAVAILABLE;
        
        switch (error.code) {
            case error.PERMISSION_DENIED:
                message = MAP_SEARCH_CONFIG.ERRORS.LOCATION_DENIED;
                break;
            case error.POSITION_UNAVAILABLE:
                message = MAP_SEARCH_CONFIG.ERRORS.LOCATION_UNAVAILABLE;
                break;
            case error.TIMEOUT:
                message = 'Konum bilgisi zaman aşımına uğradı.';
                break;
        }

        this.showNotification(message, 'warning');
        
        // Use default location
        this.currentPosition = MAP_SEARCH_CONFIG.MAP.DEFAULT_CENTER;
        this.map.setView(this.currentPosition, MAP_SEARCH_CONFIG.MAP.DEFAULT_ZOOM);
        this.searchCarWashes();
    }

    // ========================================
    // Services Management
    // ========================================

    async loadServices() {
        try {
            const response = await fetch(`${MAP_SEARCH_CONFIG.API_BASE}${MAP_SEARCH_CONFIG.ENDPOINTS.SERVICES}`);
            const data = await response.json();
            
            if (data.success) {
                this.services = data.services || [];
                this.populateServiceFilter();
            } else {
                console.warn('Failed to load services:', data.error);
            }
        } catch (error) {
            console.error('Error loading services:', error);
        }
    }

    populateServiceFilter() {
        const serviceFilter = document.getElementById('serviceFilter');
        if (!serviceFilter || this.services.length === 0) return;

        // Clear existing options except the first one
        while (serviceFilter.children.length > 1) {
            serviceFilter.removeChild(serviceFilter.lastChild);
        }

        // Add service options
        this.services.forEach(service => {
            const option = document.createElement('option');
            option.value = service.id;
            option.textContent = service.name;
            serviceFilter.appendChild(option);
        });
    }

    // ========================================
    // Search Functionality
    // ========================================

    async searchCarWashes() {
        if (this.isLoading) return;

        try {
            this.isLoading = true;
            this.showSearchLoading();

            const searchParams = this.buildSearchParams();
            const response = await fetch(`${MAP_SEARCH_CONFIG.API_BASE}${MAP_SEARCH_CONFIG.ENDPOINTS.SEARCH}?${searchParams}`);
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }

            const data = await response.json();
            
            if (data.success) {
                this.searchResults = data.results || [];
                this.renderSearchResults();
                this.updateMapMarkers();
                this.updateResultsCount();
                
                // Announce to screen readers
                this.announceSearchResults();
            } else {
                throw new Error(data.error || 'Arama başarısız');
            }

        } catch (error) {
            console.error('Search error:', error);
            this.handleSearchError(error);
        } finally {
            this.isLoading = false;
            this.hideSearchLoading();
        }
    }

    buildSearchParams() {
        const searchInput = document.getElementById('searchInput');
        const serviceFilter = document.getElementById('serviceFilter');
        const ratingFilter = document.getElementById('ratingFilter');

        const params = new URLSearchParams({
            lat: this.currentPosition ? this.currentPosition[0] : MAP_SEARCH_CONFIG.MAP.DEFAULT_CENTER[0],
            lng: this.currentPosition ? this.currentPosition[1] : MAP_SEARCH_CONFIG.MAP.DEFAULT_CENTER[1],
            radius: MAP_SEARCH_CONFIG.MAP.SEARCH_RADIUS,
            limit: MAP_SEARCH_CONFIG.MAX_RESULTS
        });

        if (searchInput && searchInput.value.trim()) {
            params.append('query', searchInput.value.trim());
        }

        if (serviceFilter && serviceFilter.value) {
            params.append('service', serviceFilter.value);
        }

        if (ratingFilter && ratingFilter.value) {
            params.append('rating', ratingFilter.value);
        }

        return params.toString();
    }

    renderSearchResults() {
        const resultsList = document.getElementById('resultsList');
        if (!resultsList) return;

        if (this.searchResults.length === 0) {
            resultsList.innerHTML = `
                <div class="text-center py-8 text-gray-500">
                    <i class="fas fa-search text-4xl mb-4"></i>
                    <p class="text-lg">Sonuç bulunamadı</p>
                    <p class="text-sm">Arama kriterlerinizi değiştirerek tekrar deneyin</p>
                </div>
            `;
            return;
        }

        const resultsHTML = this.searchResults.map(carwash => this.generateCarWashCard(carwash)).join('');
        resultsList.innerHTML = resultsHTML;

        // Add click events to cards
        this.setupCardEvents();
    }

    generateCarWashCard(carwash) {
        const distance = this.currentPosition ? 
            MapSearchUtils.calculateDistance(
                this.currentPosition[0], 
                this.currentPosition[1],
                carwash.latitude, 
                carwash.longitude
            ) : null;

        return `
            <article class="carwash-item bg-white rounded-lg shadow-md p-4 cursor-pointer hover:shadow-lg transition-shadow"
                     data-carwash-id="${carwash.id}"
                     onclick="window.mapSearchManager.showCarWashDetails(${carwash.id})"
                     role="button"
                     tabindex="0"
                     aria-label="Araç yıkama detayları: ${MapSearchUtils.sanitizeHTML(carwash.name)}">
                
                <div class="flex justify-between items-start mb-3">
                    <h3 class="text-lg font-semibold text-gray-900 pr-2">
                        ${MapSearchUtils.sanitizeHTML(carwash.name)}
                    </h3>
                    ${distance ? `<span class="text-sm text-blue-600 font-medium">${MapSearchUtils.formatDistance(distance)}</span>` : ''}
                </div>
                
                <div class="mb-3">
                    ${MapSearchUtils.generateStarRating(carwash.rating || 0, carwash.rating_count || 0)}
                </div>
                
                <div class="flex items-start text-sm text-gray-600 mb-3">
                    <i class="fas fa-map-marker-alt mt-1 mr-2 text-gray-400 flex-shrink-0"></i>
                    <span>${MapSearchUtils.sanitizeHTML(carwash.address || 'Adres bilgisi yok')}</span>
                </div>
                
                ${carwash.phone ? `
                    <div class="flex items-center text-sm text-gray-600 mb-3">
                        <i class="fas fa-phone mt-1 mr-2 text-gray-400"></i>
                        <span>${MapSearchUtils.sanitizeHTML(carwash.phone)}</span>
                    </div>
                ` : ''}
                
                <div class="flex justify-between items-center">
                    <div class="text-sm text-green-600 font-medium">
                        ${carwash.min_price ? `${MapSearchUtils.formatPrice(carwash.min_price)} - ${MapSearchUtils.formatPrice(carwash.max_price)}` : 'Fiyat bilgisi yok'}
                    </div>
                    <button class="bg-blue-600 text-white px-3 py-1 rounded text-sm hover:bg-blue-700 transition-colors"
                            onclick="event.stopPropagation(); window.location.href='/carwash_project/frontend/booking.html?carwash=${carwash.id}'"
                            aria-label="${MapSearchUtils.sanitizeHTML(carwash.name)} için rezervasyon yap">
                        Rezervasyon
                    </button>
                </div>
            </article>
        `;
    }

    setupCardEvents() {
        const cards = document.querySelectorAll('.carwash-item');
        cards.forEach(card => {
            // Keyboard navigation
            card.addEventListener('keypress', (e) => {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    card.click();
                }
            });

            // Focus management
            card.addEventListener('focus', () => {
                card.style.outline = '2px solid #3b82f6';
            });

            card.addEventListener('blur', () => {
                card.style.outline = 'none';
            });
        });
    }

    updateResultsCount() {
        const countElement = document.getElementById('resultsCount');
        if (countElement) {
            countElement.textContent = `${this.searchResults.length} sonuç bulundu`;
        }
    }

    announceSearchResults() {
        const message = `${this.searchResults.length} araç yıkama sonucu bulundu`;
        this.announceToScreenReader(message);
    }

    // ========================================
    // Map Markers Management
    // ========================================

    updateMapMarkers() {
        this.clearMarkers();
        
        if (this.searchResults.length === 0) return;

        this.searchResults.forEach(carwash => {
            if (carwash.latitude && carwash.longitude) {
                const marker = this.createCarWashMarker(carwash);
                this.markers.push(marker);
                
                if (this.markerCluster) {
                    this.markerCluster.addLayer(marker);
                } else {
                    marker.addTo(this.map);
                }
            }
        });

        // Fit map to show all markers
        this.fitMapToMarkers();
    }

    createCarWashMarker(carwash) {
        // Create custom marker icon
        const markerIcon = L.divIcon({
            className: 'carwash-marker',
            html: `
                <div class="relative">
                    <div class="w-8 h-8 bg-blue-600 rounded-full border-2 border-white shadow-lg flex items-center justify-center">
                        <i class="fas fa-car text-white text-xs"></i>
                    </div>
                </div>
            `,
            iconSize: [32, 32],
            iconAnchor: [16, 16],
            popupAnchor: [0, -16]
        });

        const marker = L.marker([carwash.latitude, carwash.longitude], { 
            icon: markerIcon 
        });

        // Create popup content
        const popupContent = this.createMarkerPopup(carwash);
        marker.bindPopup(popupContent, {
            maxWidth: 300,
            className: 'carwash-popup'
        });

        // Add marker events
        marker.on('click', () => {
            this.onMarkerClick(carwash);
        });

        return marker;
    }

    createMarkerPopup(carwash) {
        const distance = this.currentPosition ? 
            MapSearchUtils.calculateDistance(
                this.currentPosition[0], 
                this.currentPosition[1],
                carwash.latitude, 
                carwash.longitude
            ) : null;

        return `
            <div class="carwash-popup-content p-3">
                <h3 class="font-semibold text-gray-900 mb-2">${MapSearchUtils.sanitizeHTML(carwash.name)}</h3>
                
                <div class="mb-2">
                    ${MapSearchUtils.generateStarRating(carwash.rating || 0, carwash.rating_count || 0)}
                </div>
                
                <div class="text-sm text-gray-600 mb-2">
                    <i class="fas fa-map-marker-alt mr-1"></i>
                    ${MapSearchUtils.sanitizeHTML(carwash.address || 'Adres bilgisi yok')}
                </div>
                
                ${distance ? `
                    <div class="text-sm text-blue-600 font-medium mb-3">
                        ${MapSearchUtils.formatDistance(distance)} mesafede
                    </div>
                ` : ''}
                
                <div class="flex gap-2">
                    <button class="flex-1 bg-blue-600 text-white px-3 py-2 rounded text-sm hover:bg-blue-700 transition-colors"
                            onclick="window.mapSearchManager.showCarWashDetails(${carwash.id})">
                        <i class="fas fa-info-circle mr-1"></i>
                        Detaylar
                    </button>
                    <button class="flex-1 bg-green-600 text-white px-3 py-2 rounded text-sm hover:bg-green-700 transition-colors"
                            onclick="window.mapSearchManager.getDirections(${carwash.latitude}, ${carwash.longitude})">
                        <i class="fas fa-directions mr-1"></i>
                        Yol Tarifi
                    </button>
                </div>
            </div>
        `;
    }

    onMarkerClick(carwash) {
        // Highlight corresponding card
        const card = document.querySelector(`[data-carwash-id="${carwash.id}"]`);
        if (card) {
            card.scrollIntoView({ 
                behavior: 'smooth', 
                block: 'center' 
            });
            
            // Add highlight effect
            card.classList.add('highlighted');
            setTimeout(() => {
                card.classList.remove('highlighted');
            }, 2000);
        }
    }

    clearMarkers() {
        if (this.markerCluster) {
            this.markerCluster.clearLayers();
        } else {
            this.markers.forEach(marker => {
                this.map.removeLayer(marker);
            });
        }
        this.markers = [];
    }

    fitMapToMarkers() {
        if (this.markers.length === 0) return;

        const group = new L.featureGroup(this.markers);
        const bounds = group.getBounds();
        
        // Include user location in bounds if available
        if (this.userMarker) {
            bounds.extend(this.userMarker.getLatLng());
        }

        this.map.fitBounds(bounds, { 
            padding: [20, 20],
            maxZoom: 16
        });
    }

    // ========================================
    // Car Wash Details Modal
    // ========================================

    async showCarWashDetails(carwashId) {
        try {
            MapSearchUtils.showLoading(document.body);
            
            const response = await fetch(`${MAP_SEARCH_CONFIG.API_BASE}${MAP_SEARCH_CONFIG.ENDPOINTS.DETAILS}?id=${carwashId}`);
            const data = await response.json();
            
            if (data.success) {
                this.renderCarWashDetails(data.carwash);
                this.openDetailsModal();
            } else {
                throw new Error(data.error || 'Detaylar yüklenemedi');
            }
            
        } catch (error) {
            console.error('Error loading car wash details:', error);
            this.showNotification(MAP_SEARCH_CONFIG.ERRORS.DETAILS_FAILED, 'error');
        } finally {
            MapSearchUtils.hideLoading(document.body);
        }
    }

    renderCarWashDetails(carwash) {
        const detailsContainer = document.getElementById('carwashDetails');
        if (!detailsContainer) return;

        const distance = this.currentPosition ? 
            MapSearchUtils.calculateDistance(
                this.currentPosition[0], 
                this.currentPosition[1],
                carwash.latitude, 
                carwash.longitude
            ) : null;

        detailsContainer.innerHTML = `
            <div class="carwash-details">
                <div class="flex justify-between items-start mb-4">
                    <h2 class="text-2xl font-bold text-gray-900">${MapSearchUtils.sanitizeHTML(carwash.name)}</h2>
                    ${distance ? `<span class="text-lg text-blue-600 font-semibold">${MapSearchUtils.formatDistance(distance)}</span>` : ''}
                </div>
                
                <div class="mb-4">
                    ${MapSearchUtils.generateStarRating(carwash.rating || 0, carwash.rating_count || 0)}
                </div>
                
                <div class="space-y-3 mb-6">
                    <div class="flex items-start">
                        <i class="fas fa-map-marker-alt mt-1 mr-3 text-gray-400"></i>
                        <span class="text-gray-700">${MapSearchUtils.sanitizeHTML(carwash.address || 'Adres bilgisi yok')}</span>
                    </div>
                    
                    ${carwash.phone ? `
                        <div class="flex items-center">
                            <i class="fas fa-phone mr-3 text-gray-400"></i>
                            <a href="tel:${carwash.phone}" class="text-blue-600 hover:underline">${MapSearchUtils.sanitizeHTML(carwash.phone)}</a>
                        </div>
                    ` : ''}
                    
                    ${carwash.email ? `
                        <div class="flex items-center">
                            <i class="fas fa-envelope mr-3 text-gray-400"></i>
                            <a href="mailto:${carwash.email}" class="text-blue-600 hover:underline">${MapSearchUtils.sanitizeHTML(carwash.email)}</a>
                        </div>
                    ` : ''}
                    
                    ${carwash.working_hours ? `
                        <div class="flex items-start">
                            <i class="fas fa-clock mt-1 mr-3 text-gray-400"></i>
                            <span class="text-gray-700">${MapSearchUtils.sanitizeHTML(carwash.working_hours)}</span>
                        </div>
                    ` : ''}
                </div>
                
                ${carwash.services && carwash.services.length > 0 ? `
                    <div class="mb-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-3">Hizmetler</h3>
                        <ul class="space-y-2">
                            ${carwash.services.map(service => `
                                <li class="flex justify-between items-center p-3 bg-gray-50 rounded-lg">
                                    <span class="font-medium">${MapSearchUtils.sanitizeHTML(service.name)}</span>
                                    <span class="text-green-600 font-semibold">${MapSearchUtils.formatPrice(service.price)}</span>
                                </li>
                            `).join('')}
                        </ul>
                    </div>
                ` : ''}
                
                <div class="flex gap-3">
                    <button class="flex-1 bg-blue-600 text-white px-4 py-3 rounded-lg font-semibold hover:bg-blue-700 transition-colors"
                            onclick="window.location.href='/carwash_project/frontend/booking.html?carwash=${carwash.id}'">
                        <i class="fas fa-calendar-plus mr-2"></i>
                        Rezervasyon Yap
                    </button>
                    <button class="px-4 py-3 border border-gray-300 rounded-lg font-semibold hover:bg-gray-50 transition-colors"
                            onclick="window.mapSearchManager.getDirections(${carwash.latitude}, ${carwash.longitude})">
                        <i class="fas fa-directions mr-2"></i>
                        Yol Tarifi
                    </button>
                </div>
            </div>
        `;
    }

    openDetailsModal() {
        const modal = document.getElementById('detailsModal');
        if (modal) {
            modal.style.display = 'block';
            modal.classList.add('show');
            
            // Focus management
            const firstFocusable = modal.querySelector('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])');
            if (firstFocusable) {
                firstFocusable.focus();
            }
            
            // Prevent body scroll
            document.body.style.overflow = 'hidden';
        }
    }

    closeDetailsModal() {
        const modal = document.getElementById('detailsModal');
        if (modal) {
            modal.style.display = 'none';
            modal.classList.remove('show');
            
            // Restore body scroll
            document.body.style.overflow = '';
        }
    }

    // ========================================
    // Directions & External Navigation
    // ========================================

    getDirections(lat, lng) {
        if (!lat || !lng) {
            this.showNotification('Konum bilgisi eksik', 'error');
            return;
        }

        // Use Google Maps for directions
        const url = `https://www.google.com/maps/dir/?api=1&destination=${lat},${lng}`;
        window.open(url, '_blank');
    }

    // ========================================
    // UI State Management
    // ========================================

    showSearchLoading() {
        const searchButton = document.getElementById('searchButton');
        const resultsList = document.getElementById('resultsList');
        
        if (searchButton) {
            searchButton.disabled = true;
            searchButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Aranıyor...';
        }
        
        if (resultsList) {
            MapSearchUtils.showLoading(resultsList);
        }
    }

    hideSearchLoading() {
        const searchButton = document.getElementById('searchButton');
        const resultsList = document.getElementById('resultsList');
        
        if (searchButton) {
            searchButton.disabled = false;
            searchButton.innerHTML = '<i class="fas fa-search mr-2"></i>Ara';
        }
        
        if (resultsList) {
            MapSearchUtils.hideLoading(resultsList);
        }
    }

    handleSearchError(error) {
        const resultsList = document.getElementById('resultsList');
        if (resultsList) {
            resultsList.innerHTML = `
                <div class="text-center py-8 text-red-500">
                    <i class="fas fa-exclamation-triangle text-4xl mb-4"></i>
                    <p class="text-lg font-semibold">Arama Hatası</p>
                    <p class="text-sm">${error.message || MAP_SEARCH_CONFIG.ERRORS.SEARCH_FAILED}</p>
                    <button class="mt-4 bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 transition-colors"
                            onclick="window.mapSearchManager.searchCarWashes()">
                        Tekrar Dene
                    </button>
                </div>
            `;
        }
        
        this.showNotification(MAP_SEARCH_CONFIG.ERRORS.SEARCH_FAILED, 'error');
    }

    // ========================================
    // Notifications
    // ========================================

    showNotification(message, type = 'info') {
        // Remove existing notifications
        const existingNotifications = document.querySelectorAll('.map-notification');
        existingNotifications.forEach(notification => notification.remove());

        const notification = document.createElement('div');
        notification.className = `map-notification notification ${type} fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg max-w-sm`;
        
        const colors = {
            success: 'bg-green-500 text-white',
            error: 'bg-red-500 text-white',
            warning: 'bg-yellow-500 text-white',
            info: 'bg-blue-500 text-white'
        };
        
        notification.className += ` ${colors[type] || colors.info}`;
        
        const icons = {
            success: 'fas fa-check-circle',
            error: 'fas fa-exclamation-circle',
            warning: 'fas fa-exclamation-triangle',
            info: 'fas fa-info-circle'
        };
        
        notification.innerHTML = `
            <div class="flex items-center">
                <i class="${icons[type] || icons.info} mr-3"></i>
                <span>${MapSearchUtils.sanitizeHTML(message)}</span>
                <button class="ml-3 text-white hover:text-gray-200" onclick="this.parentElement.parentElement.remove()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `;
        
        document.body.appendChild(notification);
        
        // Auto remove
        setTimeout(() => {
            if (notification.parentElement) {
                notification.remove();
            }
        }, MAP_SEARCH_CONFIG.NOTIFICATION_DURATION);

        // Announce to screen readers
        this.announceToScreenReader(message);
    }

    // ========================================
    // Accessibility
    // ========================================

    handleKeyboardNavigation(e) {
        switch (e.key) {
            case 'Escape':
                this.closeDetailsModal();
                break;
            case 'Enter':
                if (e.target.matches('.carwash-item')) {
                    e.preventDefault();
                    e.target.click();
                }
                break;
        }
    }

    announceToScreenReader(message) {
        const announcement = document.createElement('div');
        announcement.setAttribute('aria-live', 'polite');
        announcement.setAttribute('aria-atomic', 'true');
        announcement.className = 'sr-only';
        announcement.textContent = message;
        document.body.appendChild(announcement);
        
        setTimeout(() => {
            if (announcement.parentElement) {
                document.body.removeChild(announcement);
            }
        }, 1000);
    }

    // ========================================
    // Error Handling
    // ========================================

    handleInitError(error) {
        console.error('Initialization error:', error);
        
        const mapContainer = document.getElementById('map');
        if (mapContainer) {
            mapContainer.innerHTML = `
                <div class="flex flex-col items-center justify-center h-full text-gray-500">
                    <i class="fas fa-exclamation-triangle text-4xl mb-4"></i>
                    <p class="text-lg font-semibold">Harita Yüklenemedi</p>
                    <p class="text-sm mb-4">Harita servisi şu anda kullanılamıyor</p>
                    <button class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 transition-colors"
                            onclick="window.location.reload()">
                        Sayfayı Yenile
                    </button>
                </div>
            `;
        }
        
        this.showNotification('Harita servisi başlatılamadı', 'error');
    }
}

// ========================================
// Global Functions for HTML Integration
// ========================================

// Export functions for HTML onclick handlers
window.showCarWashDetails = function(carwashId) {
    if (window.mapSearchManager) {
        window.mapSearchManager.showCarWashDetails(carwashId);
    }
};

window.getDirections = function(lat, lng) {
    if (window.mapSearchManager) {
        window.mapSearchManager.getDirections(lat, lng);
    }
};

// ========================================
// Application Initialization
// ========================================

document.addEventListener('DOMContentLoaded', function() {
    try {
        // Initialize the map search manager
        window.mapSearchManager = new MapSearchManager();
        console.log('CarWash Map Search initialized successfully');
        
    } catch (error) {
        console.error('Failed to initialize CarWash Map Search:', error);
        
        // Show user-friendly error message
        const container = document.getElementById('map') || document.body;
        container.innerHTML = `
            <div class="error-container text-center p-8">
                <i class="fas fa-exclamation-triangle text-red-500 text-4xl mb-4"></i>
                <h2 class="text-xl font-semibold text-gray-900 mb-2">Uygulama Başlatılamadı</h2>
                <p class="text-gray-600 mb-4">Harita arama servisi şu anda kullanılamıyor. Lütfen sayfayı yenileyin.</p>
                <button class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 transition-colors"
                        onclick="window.location.reload()">
                    Sayfayı Yenile
                </button>
            </div>
        `;
    }
});

// Export classes and utilities for external use
window.MapSearchManager = MapSearchManager;
window.MapSearchUtils = MapSearchUtils;
window.MAP_SEARCH_CONFIG = MAP_SEARCH_CONFIG;
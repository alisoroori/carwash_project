/**
 * CarWash Search Page JavaScript
 * Handles search functionality, map integration, filtering, and UI interactions
 * Following CarWash project conventions
 * 
 * @version 1.0.0
 * @author CarWash Team
 */

'use strict';

// ========================================
// Configuration & Constants
// ========================================

const SEARCH_CONFIG = {
    // API Endpoints - Following project structure
    API_BASE: '/carwash_project/backend',
    ENDPOINTS: {
        SEARCH: '/api/search_carwash.php',
        DETAILS: '/api/carwash_details.php',
        BOOKING: '/booking/create_booking.php'
    },
    
    // Map Configuration
    MAP: {
        DEFAULT_CENTER: [41.0082, 28.9784], // Istanbul coordinates
        DEFAULT_ZOOM: 12,
        MARKER_CLUSTER: true
    },
    
    // Performance Settings
    DEBOUNCE_DELAY: 300,
    MAX_RESULTS: 50,
    
    // Animation Durations
    ANIMATION_DURATION: 300,
    
    // Turkish language support
    LANGUAGE: 'tr',
    
    // Default filters
    DEFAULT_FILTERS: {
        location: '',
        service_type: '',
        price_range: '',
        sort_by: 'rating'
    }
};

// ========================================
// Utility Functions
// ========================================

const SearchUtils = {
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
     * Format price range for display
     */
    formatPriceRange(min, max) {
        if (!min && !max) return 'Fiyat bilgisi yok';
        if (!max) return `${min}+ TL`;
        return `${min}-${max} TL`;
    },

    /**
     * Calculate distance between two points
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
     * Show error message
     */
    showError(message, container) {
        container.innerHTML = `
            <div class="error-message" role="alert">
                <i class="fas fa-exclamation-triangle" aria-hidden="true"></i>
                <span>${this.sanitizeHTML(message)}</span>
            </div>
        `;
    },

    /**
     * Announce to screen readers
     */
    announceToScreenReader(message) {
        const announcement = document.createElement('div');
        announcement.setAttribute('aria-live', 'polite');
        announcement.setAttribute('aria-atomic', 'true');
        announcement.className = 'sr-only';
        announcement.textContent = message;
        document.body.appendChild(announcement);
        
        setTimeout(() => {
            document.body.removeChild(announcement);
        }, 1000);
    }
};

// ========================================
// Map Manager Class
// ========================================

class MapManager {
    constructor() {
        this.map = null;
        this.markers = [];
        this.markerCluster = null;
        this.userLocation = null;
        this.init();
    }

    init() {
        this.initializeMap();
        this.getUserLocation();
    }

    initializeMap() {
        try {
            // Initialize Leaflet map
            this.map = L.map('map').setView(
                SEARCH_CONFIG.MAP.DEFAULT_CENTER, 
                SEARCH_CONFIG.MAP.DEFAULT_ZOOM
            );

            // Add tile layer
            L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                attribution: '© OpenStreetMap contributors'
            }).addTo(this.map);

            // Initialize marker cluster if enabled
            if (SEARCH_CONFIG.MAP.MARKER_CLUSTER && window.L.markerClusterGroup) {
                this.markerCluster = L.markerClusterGroup();
                this.map.addLayer(this.markerCluster);
            }

            // Set up map event handlers
            this.setupMapEvents();

        } catch (error) {
            console.error('Error initializing map:', error);
            this.showMapError();
        }
    }

    setupMapEvents() {
        this.map.on('moveend', SearchUtils.debounce(() => {
            this.onMapMove();
        }, SEARCH_CONFIG.DEBOUNCE_DELAY));

        this.map.on('zoomend', () => {
            this.onMapZoom();
        });
    }

    onMapMove() {
        // Update search when map is moved
        const center = this.map.getCenter();
        const bounds = this.map.getBounds();
        
        // Trigger search update if auto-update is enabled
        if (window.searchManager && window.searchManager.autoUpdate) {
            window.searchManager.updateSearchByMapBounds(bounds);
        }
    }

    onMapZoom() {
        // Handle zoom level changes
        const zoomLevel = this.map.getZoom();
        console.log('Map zoom level:', zoomLevel);
    }

    getUserLocation() {
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(
                (position) => {
                    this.userLocation = {
                        lat: position.coords.latitude,
                        lng: position.coords.longitude
                    };
                    
                    // Center map on user location
                    this.map.setView([this.userLocation.lat, this.userLocation.lng], 14);
                    
                    // Add user location marker
                    this.addUserLocationMarker();
                },
                (error) => {
                    console.warn('Geolocation error:', error);
                },
                {
                    enableHighAccuracy: true,
                    timeout: 10000,
                    maximumAge: 300000
                }
            );
        }
    }

    addUserLocationMarker() {
        if (!this.userLocation) return;

        const userIcon = L.divIcon({
            className: 'user-location-marker',
            html: '<i class="fas fa-user-circle text-blue-600 text-2xl"></i>',
            iconSize: [30, 30],
            iconAnchor: [15, 15]
        });

        L.marker([this.userLocation.lat, this.userLocation.lng], { icon: userIcon })
            .addTo(this.map)
            .bindPopup('Konumunuz')
            .openPopup();
    }

    clearMarkers() {
        if (this.markerCluster) {
            this.markerCluster.clearLayers();
        } else {
            this.markers.forEach(marker => this.map.removeLayer(marker));
        }
        this.markers = [];
    }

    addCarWashMarkers(carWashes) {
        this.clearMarkers();

        carWashes.forEach(carwash => {
            if (!carwash.latitude || !carwash.longitude) return;

            const marker = this.createCarWashMarker(carwash);
            this.markers.push(marker);

            if (this.markerCluster) {
                this.markerCluster.addLayer(marker);
            } else {
                marker.addTo(this.map);
            }
        });

        // Fit map to show all markers
        if (this.markers.length > 0) {
            const group = new L.featureGroup(this.markers);
            this.map.fitBounds(group.getBounds().pad(0.1));
        }
    }

    createCarWashMarker(carwash) {
        const icon = L.divIcon({
            className: 'carwash-marker',
            html: `<div class="marker-pin">
                     <i class="fas fa-car text-white"></i>
                   </div>`,
            iconSize: [30, 40],
            iconAnchor: [15, 40],
            popupAnchor: [0, -40]
        });

        const marker = L.marker([carwash.latitude, carwash.longitude], { icon });

        const popupContent = this.createPopupContent(carwash);
        marker.bindPopup(popupContent);

        // Add click event
        marker.on('click', () => {
            this.onMarkerClick(carwash);
        });

        return marker;
    }

    createPopupContent(carwash) {
        const distance = this.userLocation ? 
            SearchUtils.calculateDistance(
                this.userLocation.lat, 
                this.userLocation.lng,
                carwash.latitude, 
                carwash.longitude
            ).toFixed(1) : null;

        return `
            <div class="map-popup">
                <div class="popup-title">${SearchUtils.sanitizeHTML(carwash.name)}</div>
                <div class="popup-address">
                    <i class="fas fa-map-marker-alt"></i>
                    ${SearchUtils.sanitizeHTML(carwash.address)}
                </div>
                ${distance ? `<div class="popup-distance">${distance} km mesafede</div>` : ''}
                <div class="popup-rating">
                    ${this.generateStarRating(carwash.rating)}
                    <span>(${carwash.review_count || 0} değerlendirme)</span>
                </div>
                <div class="popup-actions">
                    <button class="popup-btn" onclick="window.searchManager.showCarWashDetails(${carwash.id})">
                        Detayları Gör
                    </button>
                    <button class="popup-btn" onclick="window.searchManager.bookCarWash(${carwash.id})">
                        Rezervasyon Yap
                    </button>
                </div>
            </div>
        `;
    }

    generateStarRating(rating) {
        const stars = [];
        for (let i = 1; i <= 5; i++) {
            if (i <= rating) {
                stars.push('<i class="fas fa-star star"></i>');
            } else {
                stars.push('<i class="far fa-star star empty"></i>');
            }
        }
        return stars.join('');
    }

    onMarkerClick(carwash) {
        // Highlight corresponding card in results
        const card = document.querySelector(`[data-carwash-id="${carwash.id}"]`);
        if (card) {
            card.scrollIntoView({ behavior: 'smooth', block: 'center' });
            card.classList.add('highlighted');
            setTimeout(() => {
                card.classList.remove('highlighted');
            }, 2000);
        }
    }

    showMapError() {
        const mapContainer = document.getElementById('map');
        mapContainer.innerHTML = `
            <div class="map-error">
                <i class="fas fa-exclamation-triangle"></i>
                <p>Harita yüklenirken bir hata oluştu</p>
                <button onclick="window.mapManager.init()">Tekrar Dene</button>
            </div>
        `;
    }
}

// ========================================
// Search Manager Class
// ========================================

class SearchManager {
    constructor() {
        this.currentFilters = { ...SEARCH_CONFIG.DEFAULT_FILTERS };
        this.currentView = 'grid';
        this.searchResults = [];
        this.isLoading = false;
        this.autoUpdate = false;
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.initializeFilters();
        this.loadInitialResults();
    }

    setupEventListeners() {
        // Filter form submission
        const filterInputs = document.querySelectorAll('#location, #service_type, #price_range, #sort_by');
        filterInputs.forEach(input => {
            input.addEventListener('change', SearchUtils.debounce(() => {
                this.updateFilters();
                this.performSearch();
            }, SEARCH_CONFIG.DEBOUNCE_DELAY));
        });

        // Search input with suggestions
        const locationInput = document.getElementById('location');
        if (locationInput) {
            locationInput.addEventListener('input', SearchUtils.debounce((e) => {
                this.handleLocationSearch(e.target.value);
            }, SEARCH_CONFIG.DEBOUNCE_DELAY));
        }

        // View toggle buttons
        const viewButtons = document.querySelectorAll('[onclick^="toggleView"]');
        viewButtons.forEach(button => {
            button.addEventListener('click', (e) => {
                e.preventDefault();
                const view = button.getAttribute('onclick').match(/'(\w+)'/)[1];
                this.toggleView(view);
            });
        });

        // Keyboard navigation
        document.addEventListener('keydown', (e) => {
            this.handleKeyboardNavigation(e);
        });
    }

    initializeFilters() {
        // Set default values
        Object.keys(this.currentFilters).forEach(key => {
            const input = document.getElementById(key);
            if (input) {
                input.value = this.currentFilters[key];
            }
        });
    }

    updateFilters() {
        const filterInputs = document.querySelectorAll('#location, #service_type, #price_range, #sort_by');
        filterInputs.forEach(input => {
            this.currentFilters[input.id] = input.value;
        });
    }

    async loadInitialResults() {
        try {
            this.isLoading = true;
            this.showLoadingState();
            
            await this.performSearch();
            
        } catch (error) {
            console.error('Error loading initial results:', error);
            this.showErrorState('Sonuçlar yüklenirken bir hata oluştu');
        } finally {
            this.isLoading = false;
            this.hideLoadingState();
        }
    }

    async performSearch() {
        if (this.isLoading) return;

        try {
            this.isLoading = true;
            this.showLoadingState();

            const searchParams = new URLSearchParams({
                ...this.currentFilters,
                limit: SEARCH_CONFIG.MAX_RESULTS
            });

            const response = await fetch(`${SEARCH_CONFIG.API_BASE}${SEARCH_CONFIG.ENDPOINTS.SEARCH}?${searchParams}`, {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                }
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();

            if (data.success) {
                this.searchResults = data.results || [];
                this.displayResults();
                this.updateMap();
                this.updateResultCount();
                
                // Announce results to screen readers
                SearchUtils.announceToScreenReader(
                    `${this.searchResults.length} araç yıkama sonucu bulundu`
                );
            } else {
                throw new Error(data.message || 'Arama başarısız');
            }

        } catch (error) {
            console.error('Search error:', error);
            this.showErrorState('Arama sırasında bir hata oluştu. Lütfen tekrar deneyin.');
        } finally {
            this.isLoading = false;
            this.hideLoadingState();
        }
    }

    displayResults() {
        const container = document.getElementById('results_container');
        if (!container) return;

        if (this.searchResults.length === 0) {
            container.innerHTML = `
                <div class="no-results col-span-full">
                    <i class="fas fa-search"></i>
                    <h3>Sonuç bulunamadı</h3>
                    <p>Arama kriterlerinizi değiştirerek tekrar deneyin</p>
                </div>
            `;
            return;
        }

        // Sort results
        this.sortResults();

        // Generate cards HTML
        const cardsHTML = this.searchResults.map(carwash => this.generateCarWashCard(carwash)).join('');
        container.innerHTML = cardsHTML;

        // Set grid classes based on view
        container.className = this.currentView === 'grid' 
            ? 'grid grid-cols-1 md:grid-cols-2 gap-4'
            : 'space-y-4';
    }

    generateCarWashCard(carwash) {
        const distance = window.mapManager && window.mapManager.userLocation ? 
            SearchUtils.calculateDistance(
                window.mapManager.userLocation.lat,
                window.mapManager.userLocation.lng,
                carwash.latitude,
                carwash.longitude
            ).toFixed(1) : null;

        const features = carwash.features ? carwash.features.split(',').slice(0, 3) : [];

        return `
            <article class="carwash-card" data-carwash-id="${carwash.id}" role="article">
                <div class="carwash-image">
                    <img src="${carwash.image_url || '/carwash_project/frontend/images/default-carwash.jpg'}" 
                         alt="${SearchUtils.sanitizeHTML(carwash.name)}" 
                         loading="lazy"
                         onerror="this.src='/carwash_project/frontend/images/default-carwash.jpg'">
                    ${carwash.is_featured ? '<div class="carwash-badge">Öne Çıkan</div>' : ''}
                </div>
                
                <div class="carwash-content">
                    <h3 class="carwash-title">${SearchUtils.sanitizeHTML(carwash.name)}</h3>
                    
                    <div class="carwash-address">
                        <i class="fas fa-map-marker-alt" aria-hidden="true"></i>
                        <span>${SearchUtils.sanitizeHTML(carwash.address)}</span>
                        ${distance ? `<span class="ml-2 text-primary-600">(${distance} km)</span>` : ''}
                    </div>
                    
                    ${features.length > 0 ? `
                        <div class="carwash-features">
                            ${features.map(feature => 
                                `<span class="feature-tag">${SearchUtils.sanitizeHTML(feature.trim())}</span>`
                            ).join('')}
                        </div>
                    ` : ''}
                    
                    <div class="carwash-rating">
                        <div class="stars" role="img" aria-label="${carwash.rating} yıldız">
                            ${this.generateStarRating(carwash.rating)}
                        </div>
                        <span class="rating-text">
                            ${carwash.rating}/5 (${carwash.review_count || 0} değerlendirme)
                        </span>
                    </div>
                    
                    <div class="carwash-footer">
                        <div class="price-range">
                            ${SearchUtils.formatPriceRange(carwash.min_price, carwash.max_price)}
                        </div>
                        <button class="book-btn" 
                                onclick="window.searchManager.bookCarWash(${carwash.id})"
                                aria-label="${SearchUtils.sanitizeHTML(carwash.name)} için rezervasyon yap">
                            <i class="fas fa-calendar-plus mr-2"></i>
                            Rezervasyon Yap
                        </button>
                    </div>
                </div>
            </article>
        `;
    }

    generateStarRating(rating) {
        const stars = [];
        const fullStars = Math.floor(rating);
        const hasHalfStar = rating % 1 !== 0;

        for (let i = 1; i <= 5; i++) {
            if (i <= fullStars) {
                stars.push('<i class="fas fa-star star"></i>');
            } else if (i === fullStars + 1 && hasHalfStar) {
                stars.push('<i class="fas fa-star-half-alt star"></i>');
            } else {
                stars.push('<i class="far fa-star star empty"></i>');
            }
        }
        return stars.join('');
    }

    sortResults() {
        const sortBy = this.currentFilters.sort_by;
        
        this.searchResults.sort((a, b) => {
            switch (sortBy) {
                case 'price_low':
                    return (a.min_price || 0) - (b.min_price || 0);
                case 'price_high':
                    return (b.max_price || 0) - (a.max_price || 0);
                case 'distance':
                    if (!window.mapManager || !window.mapManager.userLocation) return 0;
                    const distanceA = SearchUtils.calculateDistance(
                        window.mapManager.userLocation.lat,
                        window.mapManager.userLocation.lng,
                        a.latitude, a.longitude
                    );
                    const distanceB = SearchUtils.calculateDistance(
                        window.mapManager.userLocation.lat,
                        window.mapManager.userLocation.lng,
                        b.latitude, b.longitude
                    );
                    return distanceA - distanceB;
                case 'rating':
                default:
                    return (b.rating || 0) - (a.rating || 0);
            }
        });
    }

    updateMap() {
        if (window.mapManager) {
            window.mapManager.addCarWashMarkers(this.searchResults);
        }
    }

    updateResultCount() {
        const countElement = document.getElementById('result_count');
        if (countElement) {
            countElement.textContent = this.searchResults.length;
        }
    }

    toggleView(view) {
        this.currentView = view;
        
        // Update button states
        const buttons = document.querySelectorAll('.view-toggle button');
        buttons.forEach(btn => {
            btn.classList.remove('active');
            if (btn.getAttribute('onclick').includes(view)) {
                btn.classList.add('active');
            }
        });
        
        // Re-render results with new view
        this.displayResults();
        
        SearchUtils.announceToScreenReader(`Görünüm ${view === 'grid' ? 'kart' : 'liste'} olarak değiştirildi`);
    }

    async handleLocationSearch(query) {
        if (query.length < 2) return;

        try {
            // This would typically call a geocoding service
            // For now, we'll use a simple local implementation
            const suggestions = await this.getLocationSuggestions(query);
            this.showLocationSuggestions(suggestions);
        } catch (error) {
            console.error('Location search error:', error);
        }
    }

    async getLocationSuggestions(query) {
        // Mock implementation - replace with actual geocoding API
        const mockSuggestions = [
            'İstanbul, Türkiye',
            'Ankara, Türkiye',
            'İzmir, Türkiye',
            'Bursa, Türkiye',
            'Antalya, Türkiye'
        ];

        return mockSuggestions.filter(city => 
            city.toLowerCase().includes(query.toLowerCase())
        ).slice(0, 5);
    }

    showLocationSuggestions(suggestions) {
        const input = document.getElementById('location');
        let suggestionsContainer = document.getElementById('location-suggestions');
        
        if (!suggestionsContainer) {
            suggestionsContainer = document.createElement('div');
            suggestionsContainer.id = 'location-suggestions';
            suggestionsContainer.className = 'search-suggestions';
            input.parentElement.appendChild(suggestionsContainer);
        }

        if (suggestions.length === 0) {
            suggestionsContainer.style.display = 'none';
            return;
        }

        suggestionsContainer.innerHTML = suggestions.map((suggestion, index) => 
            `<div class="suggestion-item" data-index="${index}" onclick="window.searchManager.selectLocationSuggestion('${suggestion}')">
                ${SearchUtils.sanitizeHTML(suggestion)}
            </div>`
        ).join('');

        suggestionsContainer.style.display = 'block';
    }

    selectLocationSuggestion(suggestion) {
        const input = document.getElementById('location');
        input.value = suggestion;
        
        const suggestionsContainer = document.getElementById('location-suggestions');
        if (suggestionsContainer) {
            suggestionsContainer.style.display = 'none';
        }

        this.updateFilters();
        this.performSearch();
    }

    async bookCarWash(carwashId) {
        try {
            // Redirect to booking page with pre-selected car wash
            window.location.href = `${SEARCH_CONFIG.API_BASE}/booking/booking_form.php?carwash_id=${carwashId}`;
        } catch (error) {
            console.error('Booking error:', error);
            alert('Rezervasyon sayfasına yönlendirilirken bir hata oluştu');
        }
    }

    async showCarWashDetails(carwashId) {
        try {
            // This would typically open a modal or navigate to details page
            window.location.href = `carwash_details.html?id=${carwashId}`;
        } catch (error) {
            console.error('Details error:', error);
        }
    }

    updateSearchByMapBounds(bounds) {
        // Update search based on map bounds
        // This would be implemented for live map searching
        console.log('Updating search by map bounds:', bounds);
    }

    handleKeyboardNavigation(e) {
        // Handle keyboard navigation for accessibility
        switch (e.key) {
            case 'Escape':
                // Close suggestions
                const suggestions = document.getElementById('location-suggestions');
                if (suggestions) {
                    suggestions.style.display = 'none';
                }
                break;
            case 'ArrowDown':
            case 'ArrowUp':
                this.navigateSuggestions(e);
                break;
            case 'Enter':
                this.selectActiveSuggestion(e);
                break;
        }
    }

    navigateSuggestions(e) {
        const suggestions = document.querySelectorAll('.suggestion-item');
        if (suggestions.length === 0) return;

        const active = document.querySelector('.suggestion-item.active');
        let nextIndex = 0;

        if (active) {
            const currentIndex = parseInt(active.dataset.index);
            if (e.key === 'ArrowDown') {
                nextIndex = (currentIndex + 1) % suggestions.length;
            } else {
                nextIndex = (currentIndex - 1 + suggestions.length) % suggestions.length;
            }
            active.classList.remove('active');
        }

        suggestions[nextIndex].classList.add('active');
        e.preventDefault();
    }

    selectActiveSuggestion(e) {
        const active = document.querySelector('.suggestion-item.active');
        if (active) {
            active.click();
            e.preventDefault();
        }
    }

    showLoadingState() {
        const container = document.getElementById('results_container');
        if (container) {
            SearchUtils.showLoading(container);
        }
    }

    hideLoadingState() {
        const container = document.getElementById('results_container');
        if (container) {
            SearchUtils.hideLoading(container);
        }
    }

    showErrorState(message) {
        const container = document.getElementById('results_container');
        if (container) {
            SearchUtils.showError(message, container);
        }
    }
}

// ========================================
// Accessibility Manager
// ========================================

class AccessibilityManager {
    constructor() {
        this.init();
    }

    init() {
        this.setupFocusManagement();
        this.setupScreenReaderSupport();
        this.setupKeyboardNavigation();
    }

    setupFocusManagement() {
        // Add focus-visible polyfill behavior
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Tab') {
                document.body.classList.add('keyboard-navigation');
            }
        });

        document.addEventListener('mousedown', () => {
            document.body.classList.remove('keyboard-navigation');
        });
    }

    setupScreenReaderSupport() {
        // Ensure proper ARIA labels and live regions
        const searchForm = document.querySelector('.search-filters');
        if (searchForm) {
            searchForm.setAttribute('role', 'search');
            searchForm.setAttribute('aria-label', 'Araç yıkama arama filtreleri');
        }

        const resultsContainer = document.getElementById('results_container');
        if (resultsContainer) {
            resultsContainer.setAttribute('aria-live', 'polite');
            resultsContainer.setAttribute('aria-label', 'Arama sonuçları');
        }
    }

    setupKeyboardNavigation() {
        // Implement skip links
        this.addSkipLink();
        
        // Enhanced keyboard navigation for cards
        document.addEventListener('keydown', (e) => {
            if (e.target.matches('.carwash-card, .carwash-card *')) {
                this.handleCardNavigation(e);
            }
        });
    }

    addSkipLink() {
        const skipLink = document.createElement('a');
        skipLink.href = '#results_container';
        skipLink.textContent = 'Sonuçlara atla';
        skipLink.className = 'skip-link sr-only focus:not-sr-only focus:absolute focus:top-4 focus:left-4 focus:bg-blue-600 focus:text-white focus:px-4 focus:py-2 focus:rounded';
        document.body.insertBefore(skipLink, document.body.firstChild);
    }

    handleCardNavigation(e) {
        const cards = document.querySelectorAll('.carwash-card');
        const currentCard = e.target.closest('.carwash-card');
        const currentIndex = Array.from(cards).indexOf(currentCard);

        switch (e.key) {
            case 'ArrowRight':
            case 'ArrowDown':
                e.preventDefault();
                const nextIndex = (currentIndex + 1) % cards.length;
                cards[nextIndex].focus();
                break;
            case 'ArrowLeft':
            case 'ArrowUp':
                e.preventDefault();
                const prevIndex = (currentIndex - 1 + cards.length) % cards.length;
                cards[prevIndex].focus();
                break;
            case 'Home':
                e.preventDefault();
                cards[0].focus();
                break;
            case 'End':
                e.preventDefault();
                cards[cards.length - 1].focus();
                break;
        }
    }
}

// ========================================
// Performance Monitor
// ========================================

class PerformanceMonitor {
    constructor() {
        this.metrics = {};
        this.init();
    }

    init() {
        this.measurePageLoad();
        this.setupImageLazyLoading();
        this.monitorSearchPerformance();
    }

    measurePageLoad() {
        window.addEventListener('load', () => {
            if ('performance' in window) {
                const perfData = window.performance.timing;
                const pageLoadTime = perfData.loadEventEnd - perfData.navigationStart;
                this.metrics.pageLoadTime = pageLoadTime;
                console.log(`Page load time: ${pageLoadTime}ms`);
            }
        });
    }

    setupImageLazyLoading() {
        if ('IntersectionObserver' in window) {
            const imageObserver = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                                                if (img.dataset.src) {
                                                    img.src = img.dataset.src;
                                                    img.removeAttribute('data-src');
                                                    imageObserver.unobserve(img);
                                                }
                                            }
                                        });
                                    });
                        
                                    document.querySelectorAll('img[data-src]').forEach(img => {
                                        imageObserver.observe(img);
                                    });
                                }
                            }
                        
                            monitorSearchPerformance() {
                                // Example: measure search API response time
                                // Could be expanded as needed
                            }
                        }

[
    {
        "type": "command",
        "details": {
            "key": "browser.openDevTools"
        }
    }
]

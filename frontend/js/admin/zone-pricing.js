class ZonePricing {
    constructor() {
        this.endpoints = {
            zones: '/carwash_project/backend/api/admin/zones/list.php',
            pricing: '/carwash_project/backend/api/admin/zones/pricing.php',
            analytics: '/carwash_project/backend/api/admin/zones/analytics.php'
        };
        this.map = null;
        this.drawingManager = null;
        this.zones = new Map();
        this.init();
    }

    async init() {
        await this.loadGoogleMaps();
        this.initializeMap();
        this.setupEventListeners();
        await this.loadZones();
    }

    async loadGoogleMaps() {
        return new Promise((resolve, reject) => {
            const script = document.createElement('script');
            script.src = `https://maps.googleapis.com/maps/api/js?key=${process.env.GOOGLE_MAPS_API_KEY}&libraries=drawing`;
            script.onload = resolve;
            script.onerror = reject;
            document.head.appendChild(script);
        });
    }

    initializeMap() {
        // Initialize Google Maps
        this.map = new google.maps.Map(document.getElementById('map'), {
            center: { lat: 41.0082, lng: 28.9784 }, // Istanbul coordinates
            zoom: 12,
            styles: this.getMapStyles()
        });

        // Initialize drawing tools
        this.drawingManager = new google.maps.drawing.DrawingManager({
            drawingMode: google.maps.drawing.OverlayType.POLYGON,
            drawingControl: true,
            drawingControlOptions: {
                position: google.maps.ControlPosition.TOP_CENTER,
                drawingModes: ['polygon']
            },
            polygonOptions: {
                fillColor: '#3B82F6',
                fillOpacity: 0.3,
                strokeWeight: 2,
                strokeColor: '#2563EB',
                editable: true
            }
        });

        this.drawingManager.setMap(this.map);
        
        // Handle new zone creation
        google.maps.event.addListener(
            this.drawingManager, 
            'polygoncomplete', 
            polygon => this.handleNewZone(polygon)
        );
    }

    setupEventListeners() {
        // Zone list interactions
        document.getElementById('zoneList')?.addEventListener('click', (e) => {
            if (e.target.matches('.edit-zone')) {
                this.editZone(e.target.dataset.zoneId);
            } else if (e.target.matches('.delete-zone')) {
                this.deleteZone(e.target.dataset.zoneId);
            }
        });

        // Price update form
        document.getElementById('zonePricingForm')?.addEventListener('submit', (e) => {
            e.preventDefault();
            this.updateZonePricing(new FormData(e.target));
        });

        // Analytics period selector
        document.getElementById('analyticsPeriod')?.addEventListener('change', (e) => {
            this.loadZoneAnalytics(e.target.value);
        });
    }

    async loadZones() {
        try {
            const response = await fetch(this.endpoints.zones);
            const zones = await response.json();
            
            this.clearZones();
            zones.forEach(zone => this.displayZone(zone));
            this.updateZoneList(zones);
        } catch (error) {
            this.showError('Failed to load zones');
        }
    }

    async handleNewZone(polygon) {
        const coordinates = polygon.getPath().getArray().map(coord => ({
            lat: coord.lat(),
            lng: coord.lng()
        }));

        try {
            const response = await fetch(this.endpoints.zones, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    coordinates,
                    name: `Zone ${this.zones.size + 1}`,
                    basePrice: 0
                })
            });

            const result = await response.json();
            if (result.success) {
                this.zones.set(result.zoneId, polygon);
                this.showSuccess('Zone created successfully');
                await this.loadZones();
            } else {
                polygon.setMap(null);
                this.showError(result.message);
            }
        } catch (error) {
            polygon.setMap(null);
            this.showError('Failed to create zone');
        }
    }

    async updateZonePricing(formData) {
        try {
            const response = await fetch(this.endpoints.pricing, {
                method: 'POST',
                body: formData
            });

            const result = await response.json();
            if (result.success) {
                this.showSuccess('Pricing updated successfully');
                await this.loadZones();
            } else {
                this.showError(result.message);
            }
        } catch (error) {
            this.showError('Failed to update pricing');
        }
    }

    getMapStyles() {
        return [
            {
                featureType: 'poi',
                elementType: 'labels',
                stylers: [{ visibility: 'off' }]
            },
            {
                featureType: 'transit',
                elementType: 'labels',
                stylers: [{ visibility: 'off' }]
            }
        ];
    }

    clearZones() {
        this.zones.forEach(polygon => polygon.setMap(null));
        this.zones.clear();
    }

    showSuccess(message) {
        const alert = document.createElement('div');
        alert.className = 'success-alert';
        alert.textContent = message;
        document.body.appendChild(alert);
        setTimeout(() => alert.remove(), 3000);
    }

    showError(message) {
        const alert = document.createElement('div');
        alert.className = 'error-alert';
        alert.textContent = message;
        document.body.appendChild(alert);
        setTimeout(() => alert.remove(), 5000);
    }
}

// Initialize zone pricing
document.addEventListener('DOMContentLoaded', () => new ZonePricing());
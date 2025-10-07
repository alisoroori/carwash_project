class ServiceAreaVisualizer {
    constructor(map) {
        this.map = map;
        this.polygons = new Map();
        this.activeInfoWindow = null;
    }

    async loadServiceAreas(carwashId) {
        const response = await fetch(`/carwash_project/backend/api/locations/get_service_areas.php?carwash_id=${carwashId}`);
        const areas = await response.json();
        
        areas.forEach(area => this.drawServiceArea(area));
    }

    drawServiceArea(area) {
        const polygon = new google.maps.Polygon({
            paths: area.coordinates,
            strokeColor: '#2563EB',
            strokeOpacity: 0.8,
            strokeWeight: 2,
            fillColor: '#3B82F6',
            fillOpacity: 0.35,
            map: this.map
        });

        this.polygons.set(area.id, polygon);
        this.addPolygonListeners(polygon, area);
    }
}
class ConflictResolver {
    constructor() {
        this.endpoints = {
            conflicts: '/carwash_project/backend/api/carwash/conflicts/list.php',
            resolve: '/carwash_project/backend/api/carwash/conflicts/resolve.php',
            suggest: '/carwash_project/backend/api/carwash/conflicts/suggestions.php'
        };
        this.activeConflicts = new Map();
        this.resolutionQueue = [];
        this.init();
    }

    async init() {
        this.setupEventListeners();
        await this.loadConflicts();
        this.startAutoRefresh();
    }

    setupEventListeners() {
        // Refresh conflicts
        document.getElementById('refreshConflicts')?.addEventListener('click', () => {
            this.loadConflicts();
        });

        // Auto-resolve button
        document.getElementById('autoResolveAll')?.addEventListener('click', () => {
            this.autoResolveConflicts();
        });

        // Filter conflicts
        document.getElementById('conflictTypeFilter')?.addEventListener('change', (e) => {
            this.filterConflicts(e.target.value);
        });

        // Resolution queue processing
        document.getElementById('processQueue')?.addEventListener('click', () => {
            this.processResolutionQueue();
        });
    }

    async loadConflicts() {
        try {
            const response = await fetch(this.endpoints.conflicts);
            const conflicts = await response.json();
            
            this.activeConflicts.clear();
            conflicts.forEach(conflict => this.activeConflicts.set(conflict.id, conflict));
            
            this.renderConflicts();
            this.updateStats();
        } catch (error) {
            this.showError('Failed to load conflicts');
        }
    }

    renderConflicts() {
        const container = document.getElementById('conflictList');
        if (!container) return;

        container.innerHTML = Array.from(this.activeConflicts.values()).map(conflict => `
            <div class="conflict-card ${conflict.severity}" data-id="${conflict.id}">
                <div class="conflict-header">
                    <span class="conflict-type">${conflict.type}</span>
                    <span class="conflict-time">
                        ${new Date(conflict.detected_at).toLocaleString()}
                    </span>
                </div>
                <div class="conflict-details">
                    <p>${this.sanitizeHTML(conflict.description)}</p>
                    ${this.renderConflictingBookings(conflict.bookings)}
                </div>
                <div class="conflict-suggestions">
                    ${this.renderSuggestions(conflict.suggestions)}
                </div>
                <div class="conflict-actions">
                    <button class="resolve-btn" onclick="conflictResolver.resolveConflict('${conflict.id}')">
                        Resolve
                    </button>
                    <button class="suggest-btn" onclick="conflictResolver.getSuggestions('${conflict.id}')">
                        Get Suggestions
                    </button>
                </div>
            </div>
        `).join('');
    }

    renderConflictingBookings(bookings) {
        return `
            <div class="conflicting-bookings">
                <h4>Affected Bookings:</h4>
                ${bookings.map(booking => `
                    <div class="booking-item">
                        <span class="booking-id">#${booking.id}</span>
                        <span class="booking-time">
                            ${new Date(booking.time).toLocaleTimeString()}
                        </span>
                        <span class="booking-service">${booking.service_name}</span>
                        <span class="booking-customer">${booking.customer_name}</span>
                    </div>
                `).join('')}
            </div>
        `;
    }

    renderSuggestions(suggestions) {
        if (!suggestions?.length) return '';
        
        return `
            <div class="suggestions-list">
                <h4>Resolution Suggestions:</h4>
                ${suggestions.map((suggestion, index) => `
                    <div class="suggestion-item">
                        <input type="radio" 
                               name="suggestion" 
                               id="suggestion-${index}"
                               value="${suggestion.id}">
                        <label for="suggestion-${index}">
                            ${this.sanitizeHTML(suggestion.description)}
                        </label>
                    </div>
                `).join('')}
            </div>
        `;
    }

    async resolveConflict(conflictId) {
        const conflict = this.activeConflicts.get(conflictId);
        if (!conflict) return;

        const selectedSuggestion = document.querySelector(`[name="suggestion"]:checked`)?.value;

        try {
            const response = await fetch(this.endpoints.resolve, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    conflict_id: conflictId,
                    suggestion_id: selectedSuggestion
                })
            });

            const result = await response.json();
            if (result.success) {
                this.activeConflicts.delete(conflictId);
                this.showSuccess('Conflict resolved successfully');
                this.renderConflicts();
            } else {
                this.showError(result.message);
            }
        } catch (error) {
            this.showError('Failed to resolve conflict');
        }
    }

    async getSuggestions(conflictId) {
        try {
            const response = await fetch(`${this.endpoints.suggest}?conflict_id=${conflictId}`);
            const suggestions = await response.json();
            
            const conflictCard = document.querySelector(`[data-id="${conflictId}"]`);
            if (conflictCard) {
                const suggestionsContainer = conflictCard.querySelector('.conflict-suggestions');
                suggestionsContainer.innerHTML = this.renderSuggestions(suggestions);
            }
        } catch (error) {
            this.showError('Failed to get suggestions');
        }
    }

    startAutoRefresh() {
        setInterval(() => this.loadConflicts(), 30000); // Refresh every 30 seconds
    }

    sanitizeHTML(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
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

// Initialize conflict resolver
document.addEventListener('DOMContentLoaded', () => new ConflictResolver());
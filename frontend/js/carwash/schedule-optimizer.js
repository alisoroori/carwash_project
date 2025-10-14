class ScheduleOptimizer {
    constructor(calendar) {
        this.calendar = calendar;
        this.metrics = {
            efficiency: 0,
            utilization: 0,
            conflicts: 0
        };
        this.endpoints = {
            optimize: '/carwash_project/backend/api/carwash/schedules/optimize.php',
            metrics: '/carwash_project/backend/api/carwash/schedules/metrics.php',
            apply: '/carwash_project/backend/api/carwash/schedules/apply-optimization.php'
        };
        this.optimizationFactors = {
            peakHours: true,
            weatherConditions: true,
            staffAvailability: true,
            equipmentMaintenance: true
        };
        this.currentOptimization = null;
        this.init();
    }

    async init() {
        this.setupEventListeners();
        this.initializeOptimizationPanel();
        await this.loadMetrics();
    }

    setupEventListeners() {
        // Optimization factor toggles
        document.querySelectorAll('.optimization-factor').forEach(toggle => {
            toggle.addEventListener('change', (e) => {
                this.optimizationFactors[e.target.name] = e.target.checked;
                this.updateOptimizationPreview();
            });
        });

        // Run optimization button
        document.getElementById('runOptimization')?.addEventListener('click', () => {
            this.runOptimization();
        });

        // Apply optimization button
        document.getElementById('applyOptimization')?.addEventListener('click', () => {
            this.applyOptimization();
        });

        // Date range selection
        document.getElementById('optimizationRange')?.addEventListener('change', (e) => {
            this.updateOptimizationPreview();
        });
    }

    initializeOptimizationPanel() {
        const panel = document.getElementById('optimizationPanel');
        if (!panel) return;

        panel.innerHTML = `
            <div class="optimization-controls">
                <h3>Optimization Factors</h3>
                <div class="factor-toggles">
                    <label class="factor-toggle">
                        <input type="checkbox" 
                               name="peakHours" 
                               class="optimization-factor" 
                               checked>
                        Peak Hours Analysis
                    </label>
                    <label class="factor-toggle">
                        <input type="checkbox" 
                               name="weatherConditions" 
                               class="optimization-factor" 
                               checked>
                        Weather Conditions
                    </label>
                    <label class="factor-toggle">
                        <input type="checkbox" 
                               name="staffAvailability" 
                               class="optimization-factor" 
                               checked>
                        Staff Availability
                    </label>
                    <label class="factor-toggle">
                        <input type="checkbox" 
                               name="equipmentMaintenance" 
                               class="optimization-factor" 
                               checked>
                        Equipment Maintenance
                    </label>
                </div>
                <div class="optimization-range">
                    <label>Optimization Period:</label>
                    <select id="optimizationRange">
                        <option value="day">Next 24 Hours</option>
                        <option value="week">Next Week</option>
                        <option value="month">Next Month</option>
                    </select>
                </div>
                <button id="runOptimization" class="primary-btn">
                    Run Optimization
                </button>
            </div>
            <div id="optimizationPreview" class="preview-panel"></div>
        `;
    }

    async runOptimization() {
        try {
            const response = await fetch(this.endpoints.optimize, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    factors: this.optimizationFactors,
                    range: document.getElementById('optimizationRange').value
                })
            });

            const result = await response.json();
            if (result.success) {
                this.currentOptimization = result.optimization;
                this.showOptimizationResults(result.optimization);
                this.showSuccess('Optimization completed successfully');
            } else {
                this.showError(result.message);
            }
        } catch (error) {
            this.showError('Failed to run optimization');
        }
    }

    showOptimizationResults(optimization) {
        const preview = document.getElementById('optimizationPreview');
        if (!preview) return;

        preview.innerHTML = `
            <div class="optimization-results">
                <h4>Optimization Results</h4>
                <div class="metrics-summary">
                    <div class="metric">
                        <span>Efficiency Improvement</span>
                        <strong>${optimization.efficiency_improvement}%</strong>
                    </div>
                    <div class="metric">
                        <span>Revenue Impact</span>
                        <strong>₺${optimization.revenue_impact}</strong>
                    </div>
                    <div class="metric">
                        <span>Resource Utilization</span>
                        <strong>${optimization.resource_utilization}%</strong>
                    </div>
                </div>
                ${this.renderScheduleChanges(optimization.changes)}
                <button id="applyOptimization" class="success-btn">
                    Apply Optimization
                </button>
            </div>
        `;
    }

    renderScheduleChanges(changes) {
        return `
            <div class="schedule-changes">
                <h5>Proposed Changes</h5>
                <div class="changes-list">
                    ${changes.map(change => `
                        <div class="change-item ${change.impact}">
                            <span class="change-type">${change.type}</span>
                            <span class="change-description">
                                ${this.sanitizeHTML(change.description)}
                            </span>
                            <span class="change-impact">${change.impact_value}</span>
                        </div>
                    `).join('')}
                </div>
            </div>
        `;
    }

    async applyOptimization() {
        if (!this.currentOptimization) {
            this.showError('No optimization to apply');
            return;
        }

        try {
            const response = await fetch(this.endpoints.apply, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    optimization_id: this.currentOptimization.id
                })
            });

            const result = await response.json();
            if (result.success) {
                this.showSuccess('Optimization applied successfully');
                this.currentOptimization = null;
                this.updateOptimizationPreview();
            } else {
                this.showError(result.message);
            }
        } catch (error) {
            this.showError('Failed to apply optimization');
        }
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

    async optimize() {
        const currentSchedules = await this.calendar.getAllSchedules();
        const bookingData = await this.getHistoricalBookingData();
        
        const optimizedSchedules = this.generateOptimizedSchedules(
            currentSchedules, 
            bookingData
        );

        return {
            schedules: optimizedSchedules,
            improvements: this.calculateImprovements(
                currentSchedules, 
                optimizedSchedules
            )
        };
    }

    async analyzeSchedule() {
        const schedules = await this.calendar.getAllSchedules();
        const bookingData = await this.getHistoricalBookingData();

        return {
            efficiency: this.calculateEfficiency(schedules, bookingData),
            peakHours: this.identifyPeakHours(bookingData),
            suggestions: this.generateOptimizationSuggestions(schedules, bookingData)
        };
    }

    generateOptimizationSuggestions(schedules, bookingData) {
        const suggestions = [];

        // Analyze peak hours
        const peakHours = this.identifyPeakHours(bookingData);
        for (const peak of peakHours) {
            if (peak.utilization > 0.8) {
                suggestions.push({
                    type: 'capacity',
                    priority: 'high',
                    description: `${peak.day} günü ${peak.hour}:00'da kapasite artırımı önerilir`,
                    reason: 'Yüksek doluluk oranı'
                });
            }
        }

        // Analyze low utilization periods
        const lowUtilization = this.identifyLowUtilizationPeriods(schedules, bookingData);
        for (const period of lowUtilization) {
            suggestions.push({
                type: 'schedule_adjustment',
                priority: 'medium',
                description: `${period.day} günü ${period.hour}:00 saati için program düzenlemesi önerilir`,
                reason: 'Düşük doluluk oranı'
            });
        }

        return suggestions;
    }

    async applyOptimization(optimizationType) {
        const analysis = await this.analyzeSchedule();
        const schedules = await this.calendar.getAllSchedules();

        switch (optimizationType) {
            case 'peak_hours':
                return this.optimizePeakHours(schedules, analysis.peakHours);
            case 'efficiency':
                return this.optimizeEfficiency(schedules, analysis);
            case 'balance':
                return this.optimizeWorkloadBalance(schedules, analysis);
            default:
                throw new Error('Invalid optimization type');
        }
    }

    calculateMetrics(schedules, bookingData) {
        return {
            efficiency: this.calculateEfficiency(schedules, bookingData),
            utilization: this.calculateUtilization(schedules, bookingData),
            conflicts: this.countConflicts(schedules)
        };
    }
}

// Initialize schedule optimizer
document.addEventListener('DOMContentLoaded', () => new ScheduleOptimizer());
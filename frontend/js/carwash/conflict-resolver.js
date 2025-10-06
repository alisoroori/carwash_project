class ConflictResolver {
    constructor(calendar) {
        this.calendar = calendar;
    }

    async suggestResolutions(conflicts) {
        const suggestions = [];
        
        for (const conflict of conflicts) {
            suggestions.push(...await this.generateSuggestions(conflict));
        }

        return this.rankSuggestions(suggestions);
    }

    async generateSuggestions(conflict) {
        const suggestions = [];
        const { service, schedule } = conflict;

        // Suggestion 1: Adjust time slots
        suggestions.push({
            type: 'time_adjust',
            description: `${service} için zamanı değiştir`,
            changes: [{
                ...schedule,
                start: this.findNextAvailableSlot(schedule)
            }],
            impact: 'low'
        });

        // Suggestion 2: Split schedule
        if (this.canSplitSchedule(schedule)) {
            suggestions.push({
                type: 'split',
                description: `${service} programını böl`,
                changes: this.generateSplitSchedules(schedule),
                impact: 'medium'
            });
        }

        // Suggestion 3: Increase capacity
        suggestions.push({
            type: 'capacity',
            description: `${service} kapasite artırımı`,
            changes: [{
                ...schedule,
                maxBookings: schedule.maxBookings + 1
            }],
            impact: 'high'
        });

        return suggestions;
    }

    rankSuggestions(suggestions) {
        return suggestions.sort((a, b) => {
            const impactScore = { low: 3, medium: 2, high: 1 };
            return impactScore[a.impact] - impactScore[b.impact];
        });
    }

    async applySuggestion(suggestion) {
        try {
            await this.calendar.updateSchedules(suggestion.changes);
            return true;
        } catch (error) {
            console.error('Error applying suggestion:', error);
            return false;
        }
    }
}
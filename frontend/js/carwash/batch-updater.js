class BatchUpdater {
    constructor(formId, serviceSelector, calendar) {
        this.form = document.getElementById(formId);
        this.serviceSelector = serviceSelector;
        this.calendar = calendar;
        this.init();
    }

    init() {
        this.renderForm();
        this.attachEventListeners();
    }

    renderForm() {
        this.form.innerHTML = `
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700">Gün</label>
                    <select name="day" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                        <option value="1">Pazartesi</option>
                        <option value="2">Salı</option>
                        <option value="3">Çarşamba</option>
                        <option value="4">Perşembe</option>
                        <option value="5">Cuma</option>
                        <option value="6">Cumartesi</option>
                        <option value="0">Pazar</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Maksimum Randevu</label>
                    <input type="number" name="maxBookings" min="1" value="1"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Başlangıç Saati</label>
                    <input type="time" name="startTime"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700">Bitiş Saati</label>
                    <input type="time" name="endTime"
                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                </div>
            </div>
            <div class="mt-4">
                <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700">
                    Güncelle
                </button>
            </div>
        `;
    }

    async handleSubmit(event) {
        event.preventDefault();
        const formData = new FormData(this.form);
        const selectedServices = this.serviceSelector.getSelectedServices();

        if (selectedServices.length === 0) {
            alert('Lütfen en az bir hizmet seçin');
            return;
        }

        const scheduleData = {
            services: selectedServices,
            schedules: [{
                day: formData.get('day'),
                start: formData.get('startTime'),
                end: formData.get('endTime'),
                max: formData.get('maxBookings')
            }]
        };

        try {
            const response = await fetch('../../api/carwash/batch_update_availability.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(scheduleData)
            });

            const data = await response.json();
            
            if (data.success) {
                this.calendar.updateSchedule(scheduleData.schedules);
            } else if (data.type === 'schedule_conflict') {
                this.showConflicts(data.conflicts);
            } else {
                alert(data.error || 'Güncelleme başarısız');
            }
        } catch (error) {
            console.error('Error updating schedules:', error);
            alert('Sistem hatası');
        }
    }
}
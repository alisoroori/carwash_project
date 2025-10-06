class ScheduleTemplates {
    constructor(calendar) {
        this.calendar = calendar;
        this.templates = this.loadTemplates();
        this.init();
    }

    init() {
        this.renderTemplatePanel();
        this.setupEventListeners();
    }

    renderTemplatePanel() {
        const panel = document.createElement('div');
        panel.className = 'bg-white rounded-lg shadow-lg p-4 mb-4';
        panel.innerHTML = `
            <h3 class="text-lg font-semibold mb-3">Program Şablonları</h3>
            <div class="space-y-2" id="templateList">
                ${this.renderTemplateList()}
            </div>
            <button id="addTemplate" class="mt-3 text-blue-600 hover:text-blue-800">
                <i class="fas fa-plus"></i> Yeni Şablon
            </button>
        `;

        this.calendar.container.parentNode.insertBefore(panel, this.calendar.container);
    }

    renderTemplateList() {
        return this.templates.map((template, index) => `
            <div class="flex items-center justify-between bg-gray-50 p-2 rounded">
                <span>${template.name}</span>
                <div class="space-x-2">
                    <button class="text-blue-600 hover:text-blue-800" 
                            onclick="scheduleTemplates.applyTemplate(${index})">
                        <i class="fas fa-play"></i>
                    </button>
                    <button class="text-red-600 hover:text-red-800"
                            onclick="scheduleTemplates.deleteTemplate(${index})">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        `).join('');
    }

    loadTemplates() {
        return JSON.parse(localStorage.getItem('scheduleTemplates') || '[]');
    }

    saveTemplates() {
        localStorage.setItem('scheduleTemplates', JSON.stringify(this.templates));
    }

    async addTemplate() {
        const name = await this.promptTemplateName();
        if (!name) return;

        const currentSchedules = await this.calendar.getAllSchedules();
        this.templates.push({
            name,
            schedules: currentSchedules
        });

        this.saveTemplates();
        this.updateTemplateList();
    }

    async applyTemplate(index) {
        const template = this.templates[index];
        if (confirm(`"${template.name}" şablonunu uygulamak istiyor musunuz?`)) {
            await this.calendar.updateSchedules(template.schedules);
        }
    }

    deleteTemplate(index) {
        if (confirm('Bu şablonu silmek istediğinizden emin misiniz?')) {
            this.templates.splice(index, 1);
            this.saveTemplates();
            this.updateTemplateList();
        }
    }

    promptTemplateName() {
        return new Promise(resolve => {
            const name = prompt('Şablon adını girin:');
            resolve(name);
        });
    }

    updateTemplateList() {
        const list = document.getElementById('templateList');
        list.innerHTML = this.renderTemplateList();
    }
}
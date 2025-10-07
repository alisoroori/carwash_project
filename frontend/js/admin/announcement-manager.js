class AnnouncementManager {
    constructor() {
        this.init();
    }

    async init() {
        this.setupEventListeners();
        await this.loadAnnouncements();
    }

    setupEventListeners() {
        document.getElementById('announcementForm').addEventListener('submit', (e) => {
            e.preventDefault();
            this.saveAnnouncement();
        });
    }

    async loadAnnouncements() {
        try {
            const response = await fetch('../../api/admin/get_announcements.php');
            const data = await response.json();
            
            if (data.success) {
                this.renderAnnouncements(data.announcements);
            }
        } catch (error) {
            console.error('Error loading announcements:', error);
        }
    }

    renderAnnouncements(announcements) {
        const container = document.getElementById('announcementsList');
        container.innerHTML = announcements.map(announcement => `
            <div class="bg-white rounded-lg shadow p-6 mb-4">
                <div class="flex justify-between items-start">
                    <div>
                        <h3 class="font-semibold text-lg">${announcement.title}</h3>
                        <span class="px-2 py-1 rounded-full text-xs ${this.getTypeClass(announcement.type)}">
                            ${announcement.type}
                        </span>
                    </div>
                    <div class="space-x-2">
                        <button onclick="announcementManager.editAnnouncement(${announcement.id})"
                                class="text-blue-600 hover:text-blue-800">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button onclick="announcementManager.deleteAnnouncement(${announcement.id})"
                                class="text-red-600 hover:text-red-800">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
                <div class="mt-2">${announcement.content}</div>
                <div class="mt-2 text-sm text-gray-500">
                    ${this.formatDate(announcement.start_date)} - ${this.formatDate(announcement.end_date)}
                </div>
            </div>
        `).join('');
    }

    getTypeClass(type) {
        const classes = {
            'info': 'bg-blue-100 text-blue-800',
            'warning': 'bg-yellow-100 text-yellow-800',
            'promotion': 'bg-green-100 text-green-800'
        };
        return classes[type] || 'bg-gray-100 text-gray-800';
    }

    formatDate(dateString) {
        return new Date(dateString).toLocaleDateString('tr-TR');
    }
}
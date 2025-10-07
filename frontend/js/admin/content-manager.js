class ContentManager {
    constructor() {
        this.currentSection = 'pages';
        this.editor = null;
        this.init();
    }

    async init() {
        this.initTinyMCE();
        this.setupEventListeners();
        await this.loadSection(this.currentSection);
    }

    initTinyMCE() {
        tinymce.init({
            selector: '#pageContent',
            height: 500,
            plugins: 'link image code table lists',
            toolbar: 'undo redo | formatselect | bold italic | alignleft aligncenter alignright | bullist numlist | link image'
        });
    }

    setupEventListeners() {
        document.querySelectorAll('nav a').forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const section = e.target.closest('a').dataset.section;
                this.loadSection(section);
            });
        });

        document.getElementById('pageForm').addEventListener('submit', (e) => {
            e.preventDefault();
            this.savePage();
        });
    }

    async loadSection(section) {
        this.currentSection = section;
        const contentArea = document.getElementById('contentArea');
        
        try {
            const response = await fetch(`../../api/admin/get_${section}.php`);
            const data = await response.json();
            
            if (data.success) {
                contentArea.innerHTML = this.renderSection(section, data[section]);
            }
        } catch (error) {
            console.error('Error loading section:', error);
        }
    }

    renderSection(section, items) {
        switch(section) {
            case 'pages':
                return this.renderPages(items);
            case 'announcements':
                return this.renderAnnouncements(items);
            case 'faqs':
                return this.renderFAQs(items);
            default:
                return '<p>Section not found</p>';
        }
    }

    renderPages(pages) {
        return `
            <div class="mb-4 flex justify-between items-center">
                <h2 class="text-2xl font-bold">Sayfalar</h2>
                <button onclick="contentManager.showPageModal()"
                        class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                    <i class="fas fa-plus"></i> Yeni Sayfa
                </button>
            </div>
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <table class="min-w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Başlık</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">URL</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Durum</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">İşlemler</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        ${pages.map(page => `
                            <tr>
                                <td class="px-6 py-4">${page.title}</td>
                                <td class="px-6 py-4">${page.slug}</td>
                                <td class="px-6 py-4">
                                    <span class="px-2 py-1 rounded-full text-xs ${
                                        page.status === 'published' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'
                                    }">
                                        ${page.status}
                                    </span>
                                </td>
                                <td class="px-6 py-4 space-x-2">
                                    <button onclick="contentManager.editPage(${page.id})"
                                            class="text-blue-600 hover:text-blue-800">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button onclick="contentManager.deletePage(${page.id})"
                                            class="text-red-600 hover:text-red-800">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            </div>
        `;
    }

    async savePage() {
        const form = document.getElementById('pageForm');
        const formData = new FormData(form);
        formData.append('content', tinymce.get('pageContent').getContent());

        try {
            const response = await fetch('../../api/admin/save_page.php', {
                method: 'POST',
                body: formData
            });

            const data = await response.json();
            
            if (data.success) {
                this.closeModal('pageModal');
                this.loadSection('pages');
            } else {
                alert(data.error || 'Failed to save page');
            }
        } catch (error) {
            console.error('Error saving page:', error);
        }
    }
}

// Initialize
const contentManager = new ContentManager();
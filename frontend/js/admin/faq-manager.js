class FAQManager {
    constructor() {
        this.init();
        this.sortable = null;
    }

    async init() {
        await this.loadFAQs();
        this.initSortable();
        this.setupEventListeners();
    }

    initSortable() {
        const container = document.getElementById('faqList');
        this.sortable = new Sortable(container, {
            animation: 150,
            handle: '.drag-handle',
            onEnd: async (evt) => {
                await this.updateOrder();
            }
        });
    }

    async loadFAQs() {
        try {
            const response = await fetch('../../api/admin/get_faqs.php');
            const data = await response.json();
            
            if (data.success) {
                this.renderFAQs(data.faqs);
            }
        } catch (error) {
            console.error('Error loading FAQs:', error);
        }
    }

    renderFAQs(faqs) {
        const container = document.getElementById('faqList');
        container.innerHTML = faqs.map(faq => `
            <div class="bg-white rounded-lg shadow p-6 mb-4" data-id="${faq.id}">
                <div class="flex items-center">
                    <div class="drag-handle cursor-move mr-4">
                        <i class="fas fa-grip-vertical text-gray-400"></i>
                    </div>
                    <div class="flex-1">
                        <h3 class="font-semibold">${faq.question}</h3>
                        <div class="mt-2 text-gray-600">${faq.answer}</div>
                        <div class="mt-2">
                            <span class="text-sm text-gray-500">${faq.category}</span>
                        </div>
                    </div>
                    <div class="ml-4 space-x-2">
                        <button onclick="faqManager.editFAQ(${faq.id})"
                                class="text-blue-600 hover:text-blue-800">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button onclick="faqManager.deleteFAQ(${faq.id})"
                                class="text-red-600 hover:text-red-800">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            </div>
        `).join('');
    }
}
class NotificationCenter {
    constructor() {
        this.container = this.createContainer();
        this.unreadCount = 0;
        this.init();
    }

    createContainer() {
        const container = document.createElement('div');
        container.className = 'fixed top-4 right-4 z-50';
        container.innerHTML = `
            <button id="notificationToggle" 
                    class="bg-white p-2 rounded-full shadow-lg hover:bg-gray-50 relative">
                <i class="fas fa-bell text-gray-600"></i>
                <span id="notificationBadge" 
                      class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center hidden">
                </span>
            </button>
            <div id="notificationPanel" 
                 class="hidden mt-2 w-80 bg-white rounded-lg shadow-xl">
                <div class="p-4 border-b">
                    <h3 class="font-semibold">Bildirimler</h3>
                </div>
                <div id="notificationList" class="max-h-96 overflow-y-auto">
                    <!-- Notifications will be loaded here -->
                </div>
            </div>
        `;
        document.body.appendChild(container);
        return container;
    }

    async init() {
        this.setupEventListeners();
        await this.loadNotifications();
        this.startPolling();
    }

    setupEventListeners() {
        const toggle = document.getElementById('notificationToggle');
        const panel = document.getElementById('notificationPanel');

        toggle.addEventListener('click', () => {
            panel.classList.toggle('hidden');
        });

        document.addEventListener('click', (e) => {
            if (!this.container.contains(e.target)) {
                panel.classList.add('hidden');
            }
        });
    }

    async loadNotifications() {
        try {
            const response = await fetch('../api/notifications/get_notifications.php');
            const data = await response.json();

            if (data.success) {
                this.renderNotifications(data.notifications);
                this.updateUnreadCount(data.unreadCount);
            }
        } catch (error) {
            console.error('Error loading notifications:', error);
        }
    }

    renderNotifications(notifications) {
        const list = document.getElementById('notificationList');
        list.innerHTML = notifications.length ? notifications.map(notification => `
            <div class="p-4 border-b hover:bg-gray-50 ${notification.status === 'unread' ? 'bg-blue-50' : ''}"
                 data-id="${notification.id}">
                <div class="flex justify-between items-start">
                    <div>
                        <h4 class="font-semibold">${notification.title}</h4>
                        <p class="text-sm text-gray-600">${notification.message}</p>
                    </div>
                    <span class="text-xs text-gray-500">
                        ${this.formatDate(notification.created_at)}
                    </span>
                </div>
            </div>
        `).join('') : `
            <div class="p-4 text-center text-gray-500">
                Bildirim bulunmuyor
            </div>
        `;
    }

    updateUnreadCount(count) {
        const badge = document.getElementById('notificationBadge');
        this.unreadCount = count;
        
        if (count > 0) {
            badge.textContent = count > 9 ? '9+' : count;
            badge.classList.remove('hidden');
        } else {
            badge.classList.add('hidden');
        }
    }

    formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('tr-TR', {
            day: 'numeric',
            month: 'short',
            hour: '2-digit',
            minute: '2-digit'
        });
    }

    startPolling() {
        setInterval(() => this.loadNotifications(), 30000); // Poll every 30 seconds
    }
}
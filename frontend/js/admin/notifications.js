document.addEventListener('DOMContentLoaded', function() {
    loadNotifications();
    setupEventListeners();
});

function setupEventListeners() {
    document.getElementById('createNotificationBtn')
        .addEventListener('click', () => openNotificationModal());
    
    document.getElementById('typeFilter')
        .addEventListener('change', loadNotifications);
    
    document.getElementById('statusFilter')
        .addEventListener('change', loadNotifications);
    
    document.getElementById('notificationForm')
        .addEventListener('submit', handleNotificationSubmit);
}

async function loadNotifications() {
    try {
        const type = document.getElementById('typeFilter').value;
        const status = document.getElementById('statusFilter').value;
        
        const response = await fetch(
            `/carwash_project/backend/api/admin/notifications.php?type=${type}&status=${status}`
        );
        
        const data = await response.json();
        if (data.success) {
            renderNotifications(data.notifications);
        } else {
            showNotification(data.error, 'error');
        }
    } catch (error) {
        console.error('Error loading notifications:', error);
        showNotification('Failed to load notifications', 'error');
    }
}

function renderNotifications(notifications) {
    const list = document.getElementById('notificationsList');
    list.innerHTML = notifications.map(notification => `
        <div class="notification-card ${notification.status}">
            <div class="notification-header">
                <h3>${notification.title}</h3>
                <span class="badge ${notification.type}">${notification.type}</span>
            </div>
            <p class="message">${notification.message}</p>
            <div class="notification-meta">
                <span>Created by: ${notification.created_by_name}</span>
                <span>Read by: ${notification.read_count} users</span>
            </div>
            <div class="notification-actions">
                <button onclick="editNotification(${notification.id})" 
                        class="btn-action">Edit</button>
                <button onclick="deleteNotification(${notification.id})" 
                        class="btn-action delete">Delete</button>
            </div>
        </div>
    `).join('');
}

async function handleNotificationSubmit(e) {
    e.preventDefault();
    
    const notificationId = document.getElementById('notificationId').value;
    const method = notificationId ? 'PUT' : 'POST';
    
    try {
        const response = await fetch('/carwash_project/backend/api/admin/notifications.php', {
            method: method,
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                id: notificationId,
                title: document.getElementById('title').value,
                message: document.getElementById('message').value,
                type: document.getElementById('type').value,
                target_role: document.getElementById('targetRole').value,
                scheduled_at: document.getElementById('scheduledAt').value || null,
                expires_at: document.getElementById('expiresAt').value || null
            })
        });

        const data = await response.json();
        if (data.success) {
            showNotification('Notification saved successfully', 'success');
            closeNotificationModal();
            loadNotifications();
        } else {
            showNotification(data.error, 'error');
        }
    } catch (error) {
        console.error('Error saving notification:', error);
        showNotification('Failed to save notification', 'error');
    }
}

function showNotification(message, type) {
    const notification = document.createElement('div');
    notification.className = `toast ${type}`;
    notification.textContent = message;
    document.body.appendChild(notification);
    setTimeout(() => notification.remove(), 3000);
}

function openNotificationModal(notificationId = null) {
    const modal = document.getElementById('notificationModal');
    const form = document.getElementById('notificationForm');
    
    if (notificationId) {
        document.getElementById('modalTitle').textContent = 'Edit Notification';
        loadNotificationDetails(notificationId);
    } else {
        document.getElementById('modalTitle').textContent = 'Create Notification';
        form.reset();
    }
    
    modal.style.display = 'block';
}

function closeNotificationModal() {
    document.getElementById('notificationModal').style.display = 'none';
}

async function loadNotificationDetails(id) {
    try {
        const response = await fetch(
            `/carwash_project/backend/api/admin/notifications.php?id=${id}`
        );
        const data = await response.json();
        
        if (data.success) {
            const notification = data.notification;
            document.getElementById('notificationId').value = notification.id;
            document.getElementById('title').value = notification.title;
            document.getElementById('message').value = notification.message;
            document.getElementById('type').value = notification.type;
            document.getElementById('targetRole').value = notification.target_role;
            document.getElementById('scheduledAt').value = notification.scheduled_at;
            document.getElementById('expiresAt').value = notification.expires_at;
        }
    } catch (error) {
        console.error('Error loading notification details:', error);
        showNotification('Failed to load notification details', 'error');
    }
}
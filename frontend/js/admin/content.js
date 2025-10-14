document.addEventListener('DOMContentLoaded', function() {
    loadContent();
    setupEventListeners();
});

function setupEventListeners() {
    // Add content button
    document.getElementById('addContentBtn').addEventListener('click', () => {
        openContentModal();
    });

    // Filters
    document.getElementById('typeFilter').addEventListener('change', loadContent);
    document.getElementById('statusFilter').addEventListener('change', loadContent);

    // Form submission
    document.getElementById('contentForm').addEventListener('submit', handleContentSubmit);

    // Modal close button
    document.querySelector('.modal .close').addEventListener('click', closeContentModal);
}

async function loadContent() {
    try {
        const type = document.getElementById('typeFilter').value;
        const status = document.getElementById('statusFilter').value;
        
        let url = '/carwash_project/backend/api/admin/content.php';
        if (type || status) {
            url += `?type=${type}&status=${status}`;
        }

        const response = await fetch(url);
        const data = await response.json();

        if (data.success) {
            renderContentList(data.contents);
        } else {
            showNotification(data.error, 'error');
        }
    } catch (error) {
        console.error('Error loading content:', error);
        showNotification('Failed to load content', 'error');
    }
}

function renderContentList(contents) {
    const tbody = document.getElementById('contentList');
    tbody.innerHTML = contents.map(content => `
        <tr>
            <td>${content.title}</td>
            <td>${content.type}</td>
            <td>
                <span class="status-badge ${content.status}">
                    ${content.status}
                </span>
            </td>
            <td>${new Date(content.updated_at).toLocaleString()}</td>
            <td>
                <button onclick="editContent(${content.id})" class="btn-action">
                    Edit
                </button>
                <button onclick="deleteContent(${content.id})" class="btn-action delete">
                    Delete
                </button>
            </td>
        </tr>
    `).join('');
}

async function handleContentSubmit(e) {
    e.preventDefault();
    
    const contentId = document.getElementById('contentId').value;
    const method = contentId ? 'PUT' : 'POST';
    
    try {
        const response = await fetch('/carwash_project/backend/api/admin/content.php', {
            method: method,
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                id: contentId,
                title: document.getElementById('title').value,
                type: document.getElementById('type').value,
                content: document.getElementById('content').value,
                status: document.getElementById('status').value
            })
        });

        const data = await response.json();
        if (data.success) {
            showNotification('Content saved successfully', 'success');
            closeContentModal();
            loadContent();
        } else {
            showNotification(data.error, 'error');
        }
    } catch (error) {
        console.error('Error saving content:', error);
        showNotification('Failed to save content', 'error');
    }
}

function openContentModal(contentId = null) {
    const modal = document.getElementById('contentModal');
    const form = document.getElementById('contentForm');
    const modalTitle = document.getElementById('modalTitle');

    modalTitle.textContent = contentId ? 'Edit Content' : 'Add New Content';
    document.getElementById('contentId').value = contentId;

    if (contentId) {
        // Load content data for editing
        fetchContentDetails(contentId);
    } else {
        form.reset();
    }

    modal.style.display = 'block';
}

function closeContentModal() {
    document.getElementById('contentModal').style.display = 'none';
}

async function fetchContentDetails(id) {
    try {
        const response = await fetch(`/carwash_project/backend/api/admin/content.php?id=${id}`);
        const data = await response.json();

        if (data.success) {
            const content = data.content;
            document.getElementById('title').value = content.title;
            document.getElementById('type').value = content.type;
            document.getElementById('content').value = content.content;
            document.getElementById('status').value = content.status;
        }
    } catch (error) {
        console.error('Error fetching content details:', error);
        showNotification('Failed to load content details', 'error');
    }
}

async function deleteContent(id) {
    if (!confirm('Are you sure you want to delete this content?')) {
        return;
    }

    try {
        const response = await fetch(`/carwash_project/backend/api/admin/content.php?id=${id}`, {
            method: 'DELETE'
        });

        const data = await response.json();
        if (data.success) {
            showNotification('Content deleted successfully', 'success');
            loadContent();
        } else {
            showNotification(data.error, 'error');
        }
    } catch (error) {
        console.error('Error deleting content:', error);
        showNotification('Failed to delete content', 'error');
    }
}

function showNotification(message, type) {
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.textContent = message;
    document.body.appendChild(notification);
    setTimeout(() => notification.remove(), 3000);
}
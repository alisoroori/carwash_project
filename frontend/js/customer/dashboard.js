document.addEventListener('DOMContentLoaded', function() {
    initializeEventListeners();
    setupReviewModal();
});

function initializeEventListeners() {
    // Profile form submission
    const profileForm = document.getElementById('profileForm');
    if (profileForm) {
        profileForm.addEventListener('submit', handleProfileUpdate);
    }

    // Review buttons
    document.querySelectorAll('.btn-review').forEach(button => {
        button.addEventListener('click', () => openReviewModal(button.dataset.bookingId));
    });
}

async function handleProfileUpdate(e) {
    e.preventDefault();
    
    try {
        const formData = new FormData(e.target);
        const response = await fetch('/carwash_project/backend/api/customer/profile.php', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();
        if (data.success) {
            showNotification('Profile updated successfully', 'success');
        } else {
            showNotification(data.error, 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('Failed to update profile', 'error');
    }
}

function setupReviewModal() {
    const modal = document.getElementById('reviewModal');
    const closeBtn = modal.querySelector('.close');
    const form = document.getElementById('reviewForm');

    closeBtn.onclick = () => modal.style.display = 'none';
    window.onclick = (e) => {
        if (e.target == modal) modal.style.display = 'none';
    };

    form.onsubmit = handleReviewSubmission;
}

function openReviewModal(bookingId) {
    const modal = document.getElementById('reviewModal');
    document.getElementById('bookingId').value = bookingId;
    modal.style.display = 'block';
}

async function handleReviewSubmission(e) {
    e.preventDefault();
    
    try {
        const formData = new FormData(e.target);
        const response = await fetch('/carwash_project/backend/api/customer/review.php', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();
        if (data.success) {
            showNotification('Review submitted successfully', 'success');
            document.getElementById('reviewModal').style.display = 'none';
            location.reload();
        } else {
            showNotification(data.error, 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('Failed to submit review', 'error');
    }
}

function showNotification(message, type) {
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.textContent = message;
    document.body.appendChild(notification);
    setTimeout(() => notification.remove(), 3000);
}
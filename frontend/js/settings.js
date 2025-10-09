document.addEventListener('DOMContentLoaded', function() {
    loadUserSettings();
    setupEventListeners();
    setupPasswordStrength();
});

function setupEventListeners() {
    // Navigation
    document.querySelectorAll('.settings-nav a').forEach(link => {
        link.addEventListener('click', (e) => {
            e.preventDefault();
            const targetId = e.target.getAttribute('href').substring(1);
            showSection(targetId);
        });
    });

    // Forms
    document.getElementById('profileForm').addEventListener('submit', handleProfileUpdate);
    document.getElementById('passwordForm').addEventListener('submit', handlePasswordChange);
    document.getElementById('notificationPrefsForm').addEventListener('submit', handleNotificationPrefs);
    
    // Two-factor toggle
    document.getElementById('twoFactorToggle').addEventListener('change', handleTwoFactorToggle);
}

async function loadUserSettings() {
    try {
        const response = await fetch('/carwash_project/backend/api/settings/get_settings.php');
        const data = await response.json();
        
        if (data.success) {
            // Fill profile form
            document.getElementById('name').value = data.settings.name;
            document.getElementById('email').value = data.settings.email;
            document.getElementById('phone').value = data.settings.phone;

            // Set notification preferences
            const notifForm = document.getElementById('notificationPrefsForm');
            for (const [key, value] of Object.entries(data.settings.notifications)) {
                const input = notifForm.querySelector(`[name="${key}"]`);
                if (input) input.checked = value;
            }

            // Set 2FA status
            document.getElementById('twoFactorToggle').checked = data.settings.two_factor_enabled;
        }
    } catch (error) {
        console.error('Error loading settings:', error);
        showNotification('Failed to load settings', 'error');
    }
}

async function handleProfileUpdate(e) {
    e.preventDefault();
    
    try {
        const response = await fetch('/carwash_project/backend/api/settings/update_profile.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                name: document.getElementById('name').value,
                email: document.getElementById('email').value,
                phone: document.getElementById('phone').value
            })
        });

        const data = await response.json();
        if (data.success) {
            showNotification('Profile updated successfully', 'success');
        } else {
            showNotification(data.error, 'error');
        }
    } catch (error) {
        console.error('Error updating profile:', error);
        showNotification('Failed to update profile', 'error');
    }
}

async function handlePasswordChange(e) {
    e.preventDefault();
    
    const newPassword = document.getElementById('newPassword').value;
    const confirmPassword = document.getElementById('confirmPassword').value;

    if (newPassword !== confirmPassword) {
        showNotification('Passwords do not match', 'error');
        return;
    }

    try {
        const response = await fetch('/carwash_project/backend/api/settings/change_password.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                current_password: document.getElementById('currentPassword').value,
                new_password: newPassword
            })
        });

        const data = await response.json();
        if (data.success) {
            showNotification('Password changed successfully', 'success');
            document.getElementById('passwordForm').reset();
        } else {
            showNotification(data.error, 'error');
        }
    } catch (error) {
        console.error('Error changing password:', error);
        showNotification('Failed to change password', 'error');
    }
}

async function handleNotificationPrefs(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const preferences = {};
    for (const [key, value] of formData.entries()) {
        preferences[key] = true;
    }

    try {
        const response = await fetch('/carwash_project/backend/api/settings/update_preferences.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(preferences)
        });

        const data = await response.json();
        if (data.success) {
            showNotification('Preferences updated successfully', 'success');
        } else {
            showNotification(data.error, 'error');
        }
    } catch (error) {
        console.error('Error updating preferences:', error);
        showNotification('Failed to update preferences', 'error');
    }
}

function setupPasswordStrength() {
    const passwordInput = document.getElementById('newPassword');
    const strengthIndicator = document.querySelector('.password-strength');

    passwordInput.addEventListener('input', () => {
        const password = passwordInput.value;
        const strength = calculatePasswordStrength(password);
        
        strengthIndicator.className = 'password-strength';
        strengthIndicator.classList.add(strength.level);
        strengthIndicator.textContent = `Password Strength: ${strength.text}`;
    });
}

function calculatePasswordStrength(password) {
    let score = 0;
    
    // Length check
    if (password.length >= 8) score++;
    if (password.length >= 12) score++;
    
    // Character type checks
    if (/[A-Z]/.test(password)) score++;
    if (/[a-z]/.test(password)) score++;
    if (/[0-9]/.test(password)) score++;
    if (/[^A-Za-z0-9]/.test(password)) score++;

    // Return strength level and text
    if (score >= 5) return { level: 'strong', text: 'Strong' };
    if (score >= 3) return { level: 'medium', text: 'Medium' };
    return { level: 'weak', text: 'Weak' };
}

function showNotification(message, type) {
    const notification = document.createElement('div');
    notification.className = `notification ${type}`;
    notification.textContent = message;
    document.body.appendChild(notification);
    setTimeout(() => notification.remove(), 3000);
}

function showSection(sectionId) {
    // Hide all sections
    document.querySelectorAll('.settings-section').forEach(section => {
        section.classList.remove('active');
    });
    
    // Show selected section
    document.getElementById(sectionId).classList.add('active');
    
    // Update navigation
    document.querySelectorAll('.settings-nav a').forEach(link => {
        link.classList.remove('active');
        if (link.getAttribute('href') === `#${sectionId}`) {
            link.classList.add('active');
        }
    });
}
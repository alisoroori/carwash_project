<?php

require_once __DIR__ . '/../includes/bootstrap.php';
// Auto-fix helper for profile images
require_once __DIR__ . '/../includes/profile_auto_fix.php';
// Reusable profile upload helper (consolidated upload logic)
require_once __DIR__ . '/../includes/profile_upload_helper.php';

// Enable automatic booking completion trigger (runs max once per 5 minutes)
define('ENABLE_AUTO_COMPLETION_TRIGGER', true);
require_once __DIR__ . '/../includes/auto_complete_trigger.php';

use App\Classes\Auth;
use App\Classes\Database;

// Require customer authentication
Auth::requireRole(['customer']);

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['name'] ?? $_SESSION['full_name'] ?? 'User';
$user_email = $_SESSION['email'] ?? '';

// Fetch complete user profile data from database (canonical: users + user_profiles)
$db = Database::getInstance();
$userData = $db->fetchOne(
    "SELECT 
        u.id, u.full_name, u.username, u.email, u.phone, u.profile_image, u.address,
        up.city, up.state, up.postal_code, up.country, up.birth_date, up.gender, 
        up.notification_settings, up.preferences, up.profile_image AS profile_img_extended,
        up.phone AS phone_extended, up.home_phone, up.national_id, up.driver_license,
        up.address AS profile_address
    FROM users u 
    LEFT JOIN user_profiles up ON u.id = up.user_id 
    WHERE u.id = :user_id",
    ['user_id' => $user_id]
);

// Lightweight logging helper that prefers App\\Classes\\Logger when available
if (!function_exists('cw_log_debug')) {
    function cw_log_debug($msg) {
        if (class_exists('\\App\\Classes\\Logger') && method_exists('\\App\\Classes\\Logger', 'debug')) {
            try { \App\Classes\Logger::debug($msg); } catch (Throwable $__e) { error_log($msg); }
        } else {
            error_log($msg);
        }
    }
}

cw_log_debug("[Customer_Dashboard] user_id={$user_id} - DB fetch profile_img=" . (
    isset($userData['profile_img']) ? $userData['profile_img'] : 'NULL'
));

// Extract user data with defaults (prefer user_profiles columns)
$user_phone = $userData['phone_extended'] ?? $userData['phone'] ?? '';
$user_home_phone = $userData['home_phone'] ?? '';
$user_national_id = $userData['national_id'] ?? '';
$user_driver_license = $userData['driver_license'] ?? '';
$user_profile_image = $userData['profile_img_extended'] ?? $userData['profile_image'] ?? '';
$user_address = $userData['profile_address'] ?? $userData['address'] ?? '';
$user_city = $userData['city'] ?? '';
$user_username = $userData['username'] ?? '';
$user_state = $userData['state'] ?? '';
$user_postal_code = $userData['postal_code'] ?? '';
$user_country = $userData['country'] ?? '';
$user_birth_date = $userData['birth_date'] ?? '';
$user_gender = $userData['gender'] ?? '';
$user_notification_settings = $userData['notification_settings'] ? json_decode($userData['notification_settings'], true) : null;
$user_preferences = $userData['preferences'] ? json_decode($userData['preferences'], true) : null;

// Debug: log resolved user profile image from DB and session values
$sess1 = $_SESSION['user']['profile_image'] ?? 'MISSING';
$sess2 = $_SESSION['profile_image'] ?? 'MISSING';
cw_log_debug("[Customer_Dashboard] user_id={$user_id} - resolved user_profile_image={$user_profile_image}; session[user][profile_image]={$sess1}; session[profile_image]={$sess2}");

// Attempt automatic fixes on dashboard load to ensure session/DB/file consistency
if (function_exists('autoFixProfileImage')) {
    try {
        $autoResult = autoFixProfileImage($user_id);
        cw_log_debug('[profile_auto_fix] ' . $autoResult);
    } catch (Exception $e) {
        cw_log_debug('[profile_auto_fix] Exception: ' . $e->getMessage());
    }
}

// Handle profile update form submission
$uploadError = '';
$successMessage = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    cw_log_debug("Profile update POST detected, user_id={$user_id}, REQUEST_METHOD={$_SERVER['REQUEST_METHOD']}, action={$_POST['action']}, X_REQUESTED_WITH=" . ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? 'not set'));
    // Validate CSRF token (log mismatch but don't block)
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        cw_log_debug("CSRF token mismatch for user_id={$user_id}: received '" . ($_POST['csrf_token'] ?? 'none') . "', expected '" . ($_SESSION['csrf_token'] ?? 'none') . "'");
    }
    
// Handle profile image upload (delegated to consolidated helper)
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] !== UPLOAD_ERR_NO_FILE) {
        if ($_FILES['profile_image']['error'] !== UPLOAD_ERR_OK) {
            $errorMessages = [
                UPLOAD_ERR_INI_SIZE => 'File too large (server limit).',
                UPLOAD_ERR_FORM_SIZE => 'File too large (form limit).',
                UPLOAD_ERR_PARTIAL => 'File upload was interrupted.',
                UPLOAD_ERR_NO_TMP_DIR => 'Server error: no temporary directory.',
                UPLOAD_ERR_CANT_WRITE => 'Server error: cannot write file.',
                UPLOAD_ERR_EXTENSION => 'File upload stopped by extension.',
            ];
            $uploadError = $errorMessages[$_FILES['profile_image']['error']] ?? 'Unknown upload error.';
            cw_log_debug("Profile image upload failed for user_id={$user_id}: " . $_FILES['profile_image']['error']);
        } else {
            // Use unified helper for uploads and DB/session updates
            try {
                $uploadResult = handleProfileUpload($user_id, $_FILES['profile_image']);
            } catch (Throwable $e) {
                $uploadResult = ['success' => false, 'error' => 'Upload handler exception: ' . $e->getMessage()];
            }

            if (empty($uploadResult) || empty($uploadResult['success'])) {
                $uploadError = $uploadResult['error'] ?? $uploadResult['message'] ?? 'Upload failed';
                cw_log_debug("handleProfileUpload failed for user_id={$user_id}: " . ($uploadError));
                // For AJAX, return JSON immediately
                if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                    if (ob_get_level()) { ob_clean(); }
                    header('Content-Type: application/json');
                    echo json_encode(['success' => false, 'error' => 'profile_image_not_saved', 'message' => $uploadError]);
                    exit;
                }
            } else {
                // Success: helper already updated DB and session, set a friendly message
                $successMessage = $uploadResult['message'] ?? 'Profile image updated successfully.';
            }
        }
    }
        
        // If no upload error, process other profile fields
        if (empty($uploadError)) {
            // Split fields between `users` (identity) and `user_profiles` (extended)
            $userFields = ['name', 'username', 'email'];
            $profileFields = ['phone', 'home_phone', 'national_id', 'driver_license', 'city', 'address'];

            $userUpdate = [];
            $profileUpdate = [];

            foreach ($userFields as $f) {
                if (isset($_POST[$f])) {
                    $userUpdate[$f] = trim($_POST[$f]);
                }
            }
            foreach ($profileFields as $f) {
                if (isset($_POST[$f])) {
                    $profileUpdate[$f] = trim($_POST[$f]);
                }
            }

            if (!empty($userUpdate)) {
                $db->update('users', $userUpdate, ['id' => $user_id]);
            }

            if (!empty($profileUpdate)) {
                $existing = $db->fetchOne('SELECT user_id FROM user_profiles WHERE user_id = :user_id', ['user_id' => $user_id]);
                if ($existing) {
                    $db->update('user_profiles', $profileUpdate, ['user_id' => $user_id]);
                } else {
                    $insert = array_merge(['user_id' => $user_id], $profileUpdate);
                    $db->insert('user_profiles', $insert);
                }
            }

            $successMessage = 'Profile updated successfully.';

            // Re-select authoritative fresh user (users + user_profiles) and update session
            try {
                $fresh = $db->fetchOne("
                    SELECT 
                        u.id, u.full_name, u.email, u.phone, u.profile_image, u.address,
                        up.city, up.state, up.postal_code, up.country, up.birth_date, up.gender, 
                        up.notification_settings, up.preferences, up.profile_image AS profile_img_extended,
                        up.phone AS phone_extended, up.home_phone, up.national_id, up.driver_license
                    FROM users u 
                    LEFT JOIN user_profiles up ON u.id = up.user_id 
                    WHERE u.id = :user_id
                ", ['user_id' => $user_id]);

                if ($fresh) {
                    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
                    $_SESSION['user'] = array_merge($_SESSION['user'] ?? [], [
                        'id' => $fresh['id'] ?? $user_id,
                        'full_name' => $fresh['full_name'] ?? '',
                        'email' => $fresh['email'] ?? '',
                        'username' => $fresh['username'] ?? ''
                    ]);

                    $canonical = $fresh['profile_img_extended'] ?? $fresh['profile_image'] ?? '';
                    if ($canonical) {
                        $_SESSION['profile_image'] = $canonical;
                        $_SESSION['user']['profile_image'] = $canonical;
                    }
                    $_SESSION['profile_image_ts'] = time();

                    // Also refresh top-level session shortcuts
                    $_SESSION['name'] = $_SESSION['user']['full_name'];
                    $_SESSION['email'] = $_SESSION['user']['email'];
                    $_SESSION['username'] = $_SESSION['user']['username'];
                }
            } catch (Throwable $e) {
                cw_log_debug('Failed to refresh session after profile update: ' . $e->getMessage());
            }
        }
        
        // Regenerate CSRF token after form submission
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        
        // Store any upload error in flash session for display after redirect
        if (!empty($uploadError)) {
            $_SESSION['flash_error'] = $uploadError;
        }
        
        // For AJAX requests, return JSON response
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            cw_log_debug("AJAX request detected for profile update, user_id={$user_id}, uploadError=" . ($uploadError ?: 'none'));
            // Clean output buffer to ensure pure JSON response
            if (ob_get_level()) {
                ob_clean();
            }
            header('Content-Type: application/json');
            if (!empty($uploadError)) {
                echo json_encode([
                    'success' => false,
                    'error' => 'profile_image_not_saved',
                    'message' => $uploadError
                ]);
            } else {
                // Ensure we have an authoritative fresh DB row to return to client
                if (!isset($fresh) || empty($fresh)) {
                    try {
                        $fresh = $db->fetchOne(
                            "SELECT u.*, up.profile_image AS profile_img, up.address AS profile_address, up.city AS profile_city, up.phone AS profile_phone, up.home_phone, up.national_id, up.driver_license FROM users u LEFT JOIN user_profiles up ON u.id = up.user_id WHERE u.id = :user_id",
                            ['user_id' => $user_id]
                        );
                    } catch (Throwable $_e) { $fresh = null; }
                }

                $ts = $_SESSION['profile_image_ts'] ?? time();
                $canonical = $fresh['profile_img'] ?? $fresh['profile_image'] ?? ($_SESSION['user']['profile_image'] ?? '');
                $profileWithCb = $canonical ? ($canonical . (strpos($canonical, '?') === false ? '?cb=' . $ts : '&cb=' . $ts)) : '';

                echo json_encode([
                    'success' => true,
                    'message' => $successMessage,
                    'profile_image' => $profileWithCb,
                    'profile_image_ts' => $ts,
                    'user' => $fresh
                ]);
            }
            exit;
        }
        
        // Store active tab so page reloads to profile section
        $_SESSION['active_tab'] = 'profile';
    }

// If upload succeeded, inject a small script to update avatar images on the page
// This helps when forms are submitted via AJAX or when the page reload still shows cached images
if (!empty($successMessage) && !empty($_SESSION['profile_image'])) {
    $newImg = htmlspecialchars($_SESSION['profile_image'], ENT_QUOTES, 'UTF-8');
    $ts = intval($_SESSION['profile_image_ts'] ?? time());
    echo <<<HTML
<script>
(function(){
    // Batch DOM writes inside rAF to avoid forced reflow/layout thrash
    // Use `cb` cache-buster for client-driven updates to align with `refreshProfileImages` behavior
    var newSrc = '{$newImg}' + (('{$newImg}'.indexOf('?') === -1) ? '?cb={$ts}' : '&cb={$ts}');

    function updateAvatars(){
        // Collect elements to update (reads only)
        var ids = ['headerProfileImage','sidebarProfileImage','mobileMenuAvatar'];
        var els = [];
        ids.forEach(function(id){ var el = document.getElementById(id); if(el) els.push(el); });
        document.querySelectorAll('.profile-img, .sidebar-avatar-img').forEach(function(img){ els.push(img); });

        if (els.length === 0) {
            // Use targeted selector instead of expensive querySelectorAll on ALL images
            // Schedule with rAF for better performance (non-blocking)
            requestAnimationFrame(function(){
                try {
                    // More specific selectors prevent heavy DOM traversal
                    var fallbackImgs = document.querySelectorAll('img[class*="avatar"], img[class*="profile"], img[id*="avatar"], img[id*="profile"]');
                    if (fallbackImgs.length > 0 && fallbackImgs[0]) {
                        fallbackImgs[0].src = newSrc;
                    }
                } catch (e) { /* ignore */ }
            });
            return;
        }

        // Perform writes in one rAF callback (pure writes, no reads)
        requestAnimationFrame(function(){
            // Batch all writes together without any reads to prevent forced reflow
            for (var i = 0; i < els.length; i++) {
                try { 
                    if (els[i] && els[i].src !== undefined) {
                        els[i].src = newSrc;
                    }
                } catch (e) { /* ignore update errors */ }
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', updateAvatars);
    } else {
        updateAvatars();
    }
})();
</script>
HTML;
}

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Dashboard header variables
$dashboard_type = 'customer';
$page_title = 'Müşteri Paneli - CarWash';
$current_page = 'dashboard';

// Ensure $base_url is available (some templates expect this variable)
if (!isset($base_url)) {
    if (defined('BASE_URL')) {
        $base_url = BASE_URL;
    } else {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $base_url = $protocol . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/carwash_project';
    }
}
?>

<!DOCTYPE html>
<html lang="tr" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    
    <!-- CSRF Token Meta Tag for JavaScript -->
    <meta name="csrf-token" content="<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8'); ?>">
    
    <!-- TailwindCSS - Production Build -->
    <link rel="stylesheet" href="<?php echo $base_url; ?>/dist/output.css">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="/carwash_project/frontend/vendor/fontawesome/css/all.min.css">
    
    <!-- Initialize Global CONFIG object with CSRF token -->
    <script>
        window.CONFIG = window.CONFIG || {};
        window.CONFIG.CSRF_TOKEN = '<?php echo htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>';
        window.CONFIG.BASE_URL = '<?php echo htmlspecialchars($base_url, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>';
    </script>
    <script>
        // Expose a canonical profile image URL and helper for client-side code
        window.CARWASH = window.CARWASH || {};
        window.CARWASH.profile = window.CARWASH.profile || {};
        window.CARWASH.profile.canonical = '<?php echo htmlspecialchars($user_profile_image ?: ($base_url . "/frontend/images/default-avatar.svg"), ENT_QUOTES, "UTF-8"); ?>';
        window.CARWASH.profile.ts = '<?php echo intval($_SESSION['profile_image_ts'] ?? time()); ?>';
        window.getCanonicalProfileImage = function() {
            var url = window.CARWASH.profile.canonical || '<?php echo $base_url . "/frontend/images/default-avatar.svg"; ?>';
            var ts = window.CARWASH.profile.ts || '<?php echo intval($_SESSION['profile_image_ts'] ?? time()); ?>';
            var sep = url.indexOf('?') === -1 ? '?' : '&';
            return url + sep + 'cb=' + ts;
        };
    </script>
    
    <!-- CSRF Helper - Auto-inject CSRF tokens in all POST requests -->
    <script defer src="<?php echo $base_url; ?>/frontend/js/csrf-helper.js"></script>
    
    <!-- Vehicle manager & local Alpine factories (load before Alpine so factories can register) -->
    <!-- Ensure api-utils is loaded before vehicleManager (provides window.apiCall) -->
    <script defer src="<?php echo $base_url; ?>/frontend/js/api-utils.js"></script>
    <script defer src="<?php echo $base_url; ?>/frontend/js/vehicleManager.js"></script>
    <script defer src="<?php echo $base_url; ?>/frontend/js/alpine-components.js"></script>
    <!-- Global filterCarWashes function for carwash selection -->
    <script>
        window.filterCarWashes = function(){
            // Placeholder - will be overridden when carwash section loads
            console.log('filterCarWashes called but not yet initialized');
        };
    </script>
    <!-- Check if page reloaded after successful profile update -->
    <script>
        (function(){
            // Check if we just reloaded from a successful profile update
            try {
                if (sessionStorage.getItem('profile_update_success') === 'true') {
                    // Clear the flag
                    sessionStorage.removeItem('profile_update_success');
                    
                    // Show success message after DOM is ready with extended duration (4 seconds)
                    if (document.readyState === 'loading') {
                        document.addEventListener('DOMContentLoaded', function() {
                            if (window.showGlobalToast) {
                                window.showGlobalToast('✓ Bilgileriniz güncellendi ve sayfa yenilendi', 'success', 4000);
                            } else if (window.showSuccess) {
                                window.showSuccess('Bilgileriniz başarıyla güncellendi');
                            }
                        });
                    } else {
                        if (window.showGlobalToast) {
                            window.showGlobalToast('✓ Bilgileriniz güncellendi ve sayfa yenilendi', 'success', 4000);
                        } else if (window.showSuccess) {
                            window.showSuccess('Bilgileriniz başarıyla güncellendi');
                        }
                    }
                }
            } catch (e) {
                console.warn('Could not check profile update status', e);
            }
        })();
    </script>
    <!-- Lightweight Alpine data factory for profile section (defines profileSection) -->
    <script>
        (function(){
            // Initialize profile state with PHP data from database (authoritative values on load)
            const profileInit = {
                editMode: false,
                profileData: {
                    name: <?php echo json_encode($user_name); ?>,
                    email: <?php echo json_encode($user_email); ?>,
                    username: <?php echo json_encode($user_username ?? ''); ?>,
                    phone: <?php echo json_encode($user_phone); ?>,
                    home_phone: <?php echo json_encode($user_home_phone); ?>,
                    national_id: <?php echo json_encode($user_national_id); ?>,
                    driver_license: <?php echo json_encode($user_driver_license); ?>,
                    city: <?php echo json_encode($user_city); ?>,
                    address: <?php echo json_encode($user_address); ?>,
                    profile_image: <?php echo json_encode($user_profile_image); ?>
                }
            };

            // Expose factory used by Alpine's x-data="profileSection()"
            function profileSection() {
                const state = {
                    editMode: profileInit.editMode || false,
                    profileData: profileInit.profileData || {},
                    toggleEdit() {
                        this.editMode = !this.editMode;
                        if (!this.editMode) {
                            // Clear password fields when exiting edit mode
                            const form = document.getElementById('profileForm');
                            if (form) {
                                ['current_password','new_password','confirm_password'].forEach(n => {
                                    const f = form.querySelector(`[name="${n}"]`);
                                    if (f) f.value = '';
                                });
                            }
                        }
                    },
                    updateProfile(data) {
                        if (!data || typeof data !== 'object') return;
                        Object.keys(data).forEach(k => {
                            if (k === 'profile_image') {
                                // Strip any existing cache-busters from the URL before storing
                                var cleanUrl = data[k] ? data[k].split('?')[0].split('#')[0] : '';
                                this.profileData.profile_image = cleanUrl;
                                // Force Alpine to re-render by updating timestamp
                                this.profileData._imageTimestamp = Date.now();
                            } else if (data[k] !== undefined) {
                                this.profileData[k] = data[k];
                            }
                        });
                    }
                };

                // One-time setup: listen for profile update events and react
                if (!window.__profileSectionInitialized) {
                    window.__profileSectionInitialized = true;
                    document.addEventListener('profile:update:success', function(e) {
                        try {
                            var imgUrl = (e && e.detail && e.detail.profile_image) ? e.detail.profile_image : (typeof window.getCanonicalProfileImage === 'function' ? window.getCanonicalProfileImage() : null);

                            // Update any Alpine profileSection instance if present
                            var alpineEl = document.querySelector('[x-data*="profileSection"]');
                            var updated = false;
                            if (alpineEl) {
                                // Alpine v3+ exposes __x and $data
                                try {
                                    if (alpineEl.__x && alpineEl.__x.$data) {
                                        alpineEl.__x.$data.updateProfile({ profile_image: imgUrl });
                                        alpineEl.__x.$data.editMode = false;
                                        updated = true;
                                    }
                                } catch (ex) { /* continue to other attempts */ }

                                // Alpine v2 or other builds may attach _x_dataStack
                                try {
                                    if (!updated && alpineEl._x_dataStack && alpineEl._x_dataStack[0]) {
                                        var d = alpineEl._x_dataStack[0];
                                        if (typeof d.updateProfile === 'function') {
                                            d.updateProfile({ profile_image: imgUrl });
                                            d.editMode = false;
                                            updated = true;
                                        }
                                    }
                                } catch (ex) { /* ignore */ }
                            }

                            if (!updated) {
                                // Fallback: update the factory state captured here
                                try { state.updateProfile({ profile_image: imgUrl }); } catch(e) {}
                                state.editMode = false;
                            }

                            // Ensure profile tab remains active
                            document.dispatchEvent(new CustomEvent('restoreTab', { detail: { tab: 'profile' } }));

                            // Refresh header/sidebar/profile avatars
                            if (window.refreshProfileImages) {
                                window.refreshProfileImages(imgUrl);
                            }
                        } catch (err) {
                            console.warn('profile:update:success handler failed', err);
                        }
                    });
                }

                // When the profileSection initializes, proactively refresh avatars
                try {
                    if (typeof window.getCanonicalProfileImage === 'function') {
                        window.refreshProfileImages && window.refreshProfileImages(window.getCanonicalProfileImage());
                    } else {
                        window.refreshProfileImages && window.refreshProfileImages();
                    }
                } catch (e) { /* ignore */ }

                return state;
            }

            // When the factory is available, fetch authoritative profile from the server
            // and overwrite the local state deterministically with the returned `user` payload.
            (function fetchAuthoritativeProfile(){
                var url = (window.CONFIG && window.CONFIG.BASE_URL) ? (window.CONFIG.BASE_URL + '/backend/api/get_profile.php') : '/carwash_project/backend/api/get_profile.php';
                try {
                    fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                        .then(function(res){ if (!res.ok) throw new Error('HTTP ' + res.status); return res.json(); })
                        .then(function(json){
                            if (!json || !json.user) return;
                            var u = json.user;
                            var mapped = {
                                name: u.name || u.full_name || '',
                                email: u.email || '',
                                username: u.username || '',
                                phone: u.phone || u.profile_phone || '',
                                home_phone: u.home_phone || u.profile_home_phone || '',
                                national_id: u.national_id || '',
                                driver_license: u.driver_license || '',
                                city: u.city || u.profile_city || '',
                                address: u.address || u.profile_address || '',
                                profile_image: u.profile_img || u.profile_image || u.profile_image_up || ''
                            };

                            // Update any mounted Alpine instance
                            try {
                                var alpineEl = document.querySelector('[x-data*="profileSection"]');
                                var updated = false;
                                if (alpineEl) {
                                    try {
                                        if (alpineEl.__x && alpineEl.__x.$data && typeof alpineEl.__x.$data.updateProfile === 'function') {
                                            alpineEl.__x.$data.updateProfile(mapped);
                                            updated = true;
                                        }
                                    } catch (e) {}
                                    try {
                                        if (!updated && alpineEl._x_dataStack && alpineEl._x_dataStack[0] && typeof alpineEl._x_dataStack[0].updateProfile === 'function') {
                                            alpineEl._x_dataStack[0].updateProfile(mapped);
                                            updated = true;
                                        }
                                    } catch (e) {}
                                }

                                if (!updated && window.profileSection && typeof window.profileSection === 'function') {
                                    try { window.profileSection().updateProfile(mapped); } catch (e) {}
                                }
                            } catch (e) { /* ignore */ }

                            // Ensure avatars show the authoritative image
                            try { if (window.refreshProfileImages) window.refreshProfileImages(mapped.profile_image || null); } catch (e) {}
                        })
                        .catch(function(err){ console.warn('Failed to fetch authoritative profile:', err); });
                } catch (err) { console.warn('Profile fetch init failed', err); }
            })();

            // History Section Factory
            function historySection() {
                return {
                    bookings: [],
                    loading: true,
                    error: null,
                    
                    init() {
                        console.log('🔄 History section initialized');
                        this.loadHistory();
                    },
                    
                    async loadHistory() {
                        console.log('📡 Loading history...');
                        this.loading = true;
                        this.error = null;
                        
                        try {
                            const response = await fetch('/carwash_project/backend/api/get_reservations.php', {
                                method: 'GET',
                                credentials: 'same-origin',
                                headers: { 
                                    'Accept': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest' 
                                }
                            });
                            
                            console.log('📦 Response status:', response.status, response.ok);
                            
                            if (!response.ok) {
                                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                            }
                            
                            const data = await response.json();
                            console.log('📋 API Response:', data);
                            
                            if (data.success) {
                                this.bookings = data.bookings || [];
                                console.log('✅ Loaded', this.bookings.length, 'past bookings');
                            } else {
                                this.error = data.message || 'Failed to load booking history';
                                console.error('❌ API error:', data);
                            }
                        } catch (err) {
                            this.error = 'Failed to load booking history: ' + err.message;
                            console.error('❌ History load error:', err);
                        } finally {
                            this.loading = false;
                            console.log('🏁 Loading complete. Bookings:', this.bookings.length, 'Error:', this.error);
                        }
                    },
                    
                    formatDate(dateString) {
                        if (!dateString) return 'N/A';
                        const date = new Date(dateString);
                        return date.toLocaleDateString('tr-TR', { year: 'numeric', month: 'long', day: 'numeric' });
                    },
                    
                    formatTime(timeString) {
                        if (!timeString) return 'N/A';
                        return timeString.substring(0, 5); // HH:MM
                    },
                    
                    formatPrice(price) {
                        if (!price) return '0.00';
                        return parseFloat(price).toFixed(2);
                    }
                };
            }

            // Make factories globally available (Alpine will call them by name)
            window.profileSection = profileSection;
            window.historySection = historySection;
        })();
    </script>
    <!-- Alpine.js -->
        <script src="/carwash_project/frontend/vendor/alpine/cdn.min.js" defer></script>
        <script defer>console.log('Alpine initialized');</script>
    
    <style>
        /* ================================
           CSS CUSTOM PROPERTIES (Theme Variables)
           ================================ */
        :root {
            /* Layout Dynamic Heights (computed by JS) */
            --header-height: 80px;           /* Fixed header height */
            --footer-height: auto;           /* Footer height (auto for responsive) */
            --sidebar-width: 250px;          /* Fixed sidebar width (desktop) */
            
            /* Primary Colors */
            --color-primary: #2563eb;        /* Blue-600 */
            --color-primary-light: #3b82f6; /* Blue-500 */
            --color-primary-dark: #1d4ed8;  /* Blue-700 */
            --color-primary-50: #eff6ff;
            --color-primary-100: #dbeafe;
            --color-primary-200: #bfdbfe;
            
            /* Secondary Colors */
            --color-secondary: #9333ea;       /* Purple-600 */
            --color-secondary-light: #a855f7; /* Purple-500 */
            --color-secondary-dark: #7e22ce;  /* Purple-700 */
            --color-secondary-50: #faf5ff;
            --color-secondary-100: #f3e8ff;
            
            /* Success */
            --color-success: #10b981;        /* Green-500 */
            --color-success-light: #34d399;  /* Green-400 */
            --color-success-bg: #f0fdf4;     /* Green-50 */
            
            /* Error */
            --color-error: #ef4444;          /* Red-500 */
            --color-error-light: #f87171;    /* Red-400 */
            --color-error-bg: #fef2f2;       /* Red-50 */
            
            /* Warning */
            --color-warning: #f59e0b;        /* Amber-500 */
            --color-warning-light: #fbbf24;  /* Amber-400 */
            --color-warning-bg: #fffbeb;     /* Amber-50 */
            
            /* Neutral */
            --color-gray-50: #f9fafb;
            --color-gray-100: #f3f4f6;
            --color-gray-200: #e5e7eb;
            --color-gray-300: #d1d5db;
            --color-gray-400: #9ca3af;
            --color-gray-500: #6b7280;
            --color-gray-600: #4b5563;
            --color-gray-700: #374151;
            --color-gray-800: #1f2937;
            --color-gray-900: #111827;
            
            /* Text Colors */
            --text-primary: var(--color-gray-900);
            --text-secondary: var(--color-gray-600);
            --text-inverse: #ffffff;
            
            /* Background Colors */
            --bg-body: var(--color-gray-50);
            --bg-card: #ffffff;
            --bg-sidebar: linear-gradient(to bottom, var(--color-primary), var(--color-secondary));
            
            /* Shadows */
            --shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
            --shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1), 0 2px 4px -2px rgb(0 0 0 / 0.1);
            --shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1), 0 4px 6px -4px rgb(0 0 0 / 0.1);
            --shadow-xl: 0 20px 25px -5px rgb(0 0 0 / 0.1), 0 8px 10px -6px rgb(0 0 0 / 0.1);
            
            /* Border Radius */
            --radius-sm: 0.375rem;
            --radius-md: 0.5rem;
            --radius-lg: 0.75rem;
            --radius-xl: 1rem;
            --radius-2xl: 1.5rem;
            
            /* Transitions */
            --transition-fast: 150ms cubic-bezier(0.4, 0, 0.2, 1);
            --transition-base: 200ms cubic-bezier(0.4, 0, 0.2, 1);
            --transition-slow: 300ms cubic-bezier(0.4, 0, 0.2, 1);
            
            /* Sidebar Spacing */
            --sidebar-item-gap: 15px;
            
            /* Ensure root-level layout sizing is robust for fixed children */
            /* (Non-functional layout assist: does not change visuals) */
            --layout-root-height: 100vh;
        }

        /* Ensure html/body occupy full height for fixed layout calculations */
        html, body {
            height: 100%;
            margin: 0;
            padding: 0 !important;
        }
        
        /* Body needs padding-top to account for fixed header */
        body {
            padding-top: var(--header-height) !important;
        }
        
        /* ================================
           CUSTOM SCROLLBAR
           ================================ */
        ::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        
        ::-webkit-scrollbar-track {
            background: var(--color-gray-100);
        }
        
        ::-webkit-scrollbar-thumb {
            background: var(--color-gray-300);
            border-radius: var(--radius-sm);
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: var(--color-gray-400);
        }
        
        /* Smooth transitions */
        * {
            transition-property: background-color, border-color, color, fill, stroke, opacity, box-shadow, transform;
            transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
            transition-duration: 150ms;
        }
        
        /* Prevent body scroll when mobile menu open */
        body.menu-open {
            overflow: hidden !important;
            position: fixed;
            width: 100%;
        }
        
        /* ================================
           ANIMATIONS
           ================================ */
        /* Mobile sidebar animations */
        @keyframes slideInLeft {
            from {
                transform: translateX(-100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        @keyframes slideOutLeft {
            from {
                transform: translateX(0);
                opacity: 1;
            }
            to {
                transform: translateX(-100%);
                opacity: 0;
            }
        }
        
        @keyframes fadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }
        
        @keyframes fadeOut {
            from {
                opacity: 1;
            }
            to {
                opacity: 0;
            }
        }
        
        /* ================================
           BUTTONS - Consistent States
           ================================ */
        .btn-primary {
            background: linear-gradient(to right, var(--color-primary), var(--color-secondary));
            color: var(--text-inverse);
            transition: all var(--transition-base);
        }
        
        .btn-primary:hover {
            box-shadow: var(--shadow-lg);
            transform: translateY(-1px);
        }
        
        .btn-primary:active {
            box-shadow: var(--shadow-md);
            transform: translateY(0);
        }
        
        .btn-primary:focus {
            outline: 2px solid var(--color-primary-200);
            outline-offset: 2px;
        }
        
        .btn-secondary {
            background-color: var(--bg-card);
            color: var(--color-primary);
            border: 2px solid var(--color-primary);
            transition: all var(--transition-base);
        }
        
        .btn-secondary:hover {
            background-color: var(--color-primary-50);
        }
        
        .btn-secondary:active {
            background-color: var(--color-primary-100);
        }
        
        .btn-secondary:focus {
            outline: 2px solid var(--color-primary-200);
            outline-offset: 2px;
        }
        
        /* ================================
           SIDEBAR STYLING
           ================================ */
        /* Reusable class for the vertical sidebar gradient */
        .sidebar-gradient {
            background: linear-gradient(180deg, #667eea 0%, #764ba2 100%);
        }

        #customer-sidebar {
            /* background is provided via the .sidebar-gradient class on the element */
        }
        
        /* Sidebar Menu Item Spacing */
        #customer-sidebar nav > a {
            margin-bottom: var(--sidebar-item-gap);
        }
        
        #customer-sidebar nav > a:last-child {
            margin-bottom: 0;
        }
        
        /* Ensure sidebar is hidden off-screen on mobile by default */
        @media (max-width: 1023px) {
            #customer-sidebar {
                transition: transform var(--transition-slow);
            }
        }
        
        /* ================================
           CARDS & CONTAINERS
           ================================ */
        .card {
            background-color: var(--bg-card);
            border-radius: var(--radius-2xl);
            box-shadow: var(--shadow-md);
            transition: all var(--transition-base);
        }
        
        .card:hover {
            box-shadow: var(--shadow-lg);
            transform: translateY(-2px);
        }
        
        /* ================================
           FORM INPUT STATES
           ================================ */
        input:focus, textarea:focus, select:focus {
            outline: none;
        }
        
        input.error, textarea.error, select.error {
            border-color: var(--color-error) !important;
            background-color: var(--color-error-bg);
        }
        
        input.success, textarea.success, select.success {
            border-color: var(--color-success) !important;
            background-color: var(--color-success-bg);
        }
        
        input::placeholder, textarea::placeholder {
            color: var(--color-gray-400);
        }
        
        /* Form validation messages */
        .form-error {
            color: var(--color-error);
            font-size: 0.875rem;
            margin-top: 0.25rem;
        }
        
        .form-success {
            color: var(--color-success);
            font-size: 0.875rem;
            margin-top: 0.25rem;
        }
        
        /* ================================
           TEXT UTILITIES
           ================================ */
        .text-primary {
            color: var(--color-primary);
        }
        
        .text-secondary {
            color: var(--color-secondary);
        }
        
        .bg-primary {
            background-color: var(--color-primary);
        }
        
        .bg-secondary {
            background-color: var(--color-secondary);
        }
        
        .bg-gradient-primary {
            background: linear-gradient(to right, var(--color-primary), var(--color-secondary));
        }
        
        /* ================================
           UNIFIED PAGE-SCROLL LAYOUT
           - Header fixed at top
           - Sidebar and Main Content start immediately below header
           - Sidebar height matches Main Content
           - Footer at bottom, nothing overlaps
           - Single page-level scroll only (no internal scrolling)
           ================================ */
        
        /* === 1. Header (Fixed at top) === */
        header {
            position: fixed !important;
            top: 0;
            left: 0;
            right: 0;
            width: 100%;
            height: var(--header-height);
            min-height: var(--header-height);
            z-index: 1000;
            background: white;
            border-bottom: 1px solid #e5e7eb;
            flex-shrink: 0;
        }
        
        /* === 2. Layout Container (Sidebar + Main Content) === */
        /* Starts immediately below the fixed header */
        .dashboard-layout {
            display: flex;
            flex-direction: row;
            width: 100%;
            min-height: calc(100vh - var(--header-height));
            /* Ensure children stretch to same height so sidebar matches main content */
            align-items: stretch;
            /* No margin-top needed since body has padding-top */
        }
        
        /* === 3. Sidebar (Desktop: spans full height of content area) === */
        #customer-sidebar {
            width: var(--sidebar-width);
            min-width: var(--sidebar-width);
            display: flex;
            flex-direction: column;
            background: linear-gradient(to bottom, #2563eb, #7c3aed);
            box-shadow: 4px 0 15px rgba(0,0,0,0.12);
            flex-shrink: 0;
            /* Sidebar height matches main content - both scroll together */
            /* Keep box-sizing so padding doesn't cause overflow */
            box-sizing: border-box;
            /* Add 20px visual increase while keeping layout in sync */
            padding-bottom: 20px;
            overflow: visible;
            align-self: stretch;
        }
        
        /* === 4. Main Content Area === */
        #main-content {
            flex: 1;
            min-width: 0; /* Prevent flex item overflow */
            padding: 1.5rem;
            background-color: var(--bg-body);
            box-sizing: border-box;
            /* No internal scroll - content flows naturally */
            overflow: visible;
            /* Ensure no CSS transform scales are applied to #main-content which
               would visually shrink it without changing layout space and cause
               overflow/footers to misalign. Reset any accidental transforms. */
            transform: scale(0.98) !important;
            
        }
        
        /* Sidebar Profile Section */
        #customer-sidebar .flex-shrink-0:first-of-type {
            padding: 0.75rem;
            flex-shrink: 0;
        }
        
        /* Sidebar Profile Image */
        #customer-sidebar img#sidebarProfileImage {
            width: 56px !important;
            height: 56px !important;
            border-radius: 50%;
            object-fit: cover;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            display: block;
            margin: 0 auto;
        }

        /* Unified avatar container */
        .sidebar-profile-container,
        #headerProfileContainer {
            width: 56px;
            height: 56px;
            border-radius: 50%;
            overflow: hidden;
            display: block;
            box-shadow: 0 4px 6px rgba(0,0,0,0.08);
            margin: 0 auto;
        }

        .sidebar-profile-container img,
        #headerProfileContainer img,
        #userAvatarTop {
            width: 100% !important;
            height: 100% !important;
            object-fit: cover !important;
            border-radius: 50% !important;
            display: block !important;
        }

        /* Header profile image */
        #userAvatarTop {
            width: 56px !important;
            height: 56px !important;
            border-radius: 50%;
            object-fit: cover;
            display: block;
        }

        #headerProfileContainer {
            width: 56px;
            height: 56px;
        }
        
        /* Sidebar Navigation Menu */
        #customer-sidebar nav {
            flex: 1;
            display: flex;
            flex-direction: column;
            padding: 0.5rem;
            overflow: visible; /* No scroll */
        }

        /* Compact sidebar text */
        #customer-sidebar {
            font-size: 13px;
            line-height: 1.2;
        }

        #customer-sidebar nav a {
            display: flex;
            align-items: center;
            padding: 0.6rem 0.75rem;
            margin-bottom: 0.25rem;
            border-radius: 0.5rem;
            transition: background-color 150ms ease;
        }

        #customer-sidebar nav a:last-child {
            margin-bottom: 0;
        }

        /* Sidebar bottom section (Settings) */
        #customer-sidebar .flex-shrink-0.p-3 { 
            padding: 0.5rem;
            flex-shrink: 0;
        }
        
        /* === 4. Main Content Area === */
        #main-content {
            flex: 1;
            min-width: 0; /* Prevent flex item overflow */
            padding: 1.5rem;
            background-color: var(--bg-body);
            box-sizing: border-box;
            /* No internal scroll - content flows naturally */
            overflow: visible;
        }
        
        /* === 5. Footer === */
        footer {
            position: relative;
            width: 100%;
            flex-shrink: 0;
            z-index: 10;
            margin-top: auto;
        }
        
        /* ================================
           DESKTOP LAYOUT (≥901px)
           Sidebar visible, no hamburger
           ================================ */
        @media (min-width: 901px) {
            #customer-sidebar {
                position: relative;
                transform: none !important;
                display: flex !important;
                z-index: 40;
            }
            
            .mobile-hamburger-btn {
                display: none !important;
            }
            
            .sidebar-backdrop {
                display: none !important;
            }
        }
        
        /* ================================
           TABLET LAYOUT (768px - 900px)
           Sidebar fixed overlay with hamburger
           ================================ */
        @media (min-width: 768px) and (max-width: 900px) {
            :root {
                --sidebar-width: 260px;
            }
            
            #customer-sidebar {
                position: fixed;
                top: var(--header-height); /* Start below fixed header */
                left: 0;
                height: calc(100vh - var(--header-height));
                width: var(--sidebar-width);
                max-width: 80vw;
                transform: translateX(-100%);
                transition: transform 300ms ease-in-out;
                z-index: 1100;
                overflow-y: auto;
                -webkit-overflow-scrolling: touch;
            }
            
            #customer-sidebar.sidebar-open,
            #customer-sidebar.translate-x-0 {
                transform: translateX(0) !important;
            }
            
            .mobile-hamburger-btn {
                display: inline-flex !important;
            }
            
            #main-content {
                width: 100%;
                padding: 1rem;
            }

            #customer-sidebar img#sidebarProfileImage,
            .sidebar-profile-container {
                width: 48px !important;
                height: 48px !important;
            }
        }
        
        /* ================================
           MOBILE LAYOUT (<768px)
           Sidebar fixed overlay with hamburger
           ================================ */
        @media (max-width: 767px) {
            :root {
                --sidebar-width: 280px;
            }
            
            #customer-sidebar {
                position: fixed;
                top: var(--header-height); /* Start below fixed header */
                left: 0;
                height: calc(100vh - var(--header-height));
                width: var(--sidebar-width);
                max-width: 85vw;
                transform: translateX(-100%);
                transition: transform 300ms ease-in-out;
                z-index: 1100;
                overflow-y: auto;
                -webkit-overflow-scrolling: touch;
            }
            
            #customer-sidebar.sidebar-open,
            #customer-sidebar.translate-x-0 {
                transform: translateX(0) !important;
            }
            
            .mobile-hamburger-btn {
                display: inline-flex !important;
                top: calc(var(--header-height) + 10px);
                left: 10px;
                width: 42px;
                height: 42px;
            }
            
            #main-content {
                width: 100%;
                padding: 0.75rem;
                padding-top: 60px; /* Space for hamburger button */
            }
            
            #main-content .max-w-7xl {
                padding-left: 0.5rem;
                padding-right: 0.5rem;
            }

            /* Reduce card padding on mobile */
            #main-content .p-6,
            #main-content .md\:p-8 {
                padding: 1rem;
            }

            #customer-sidebar img#sidebarProfileImage,
            .sidebar-profile-container {
                width: 48px !important;
                height: 48px !important;
            }

            #userAvatarTop,
            #headerProfileContainer {
                width: 44px !important;
                height: 44px !important;
            }
        }

        /* ================================
           VERY SMALL SCREENS (<480px)
           Full-width sidebar overlay
           ================================ */
        @media (max-width: 479px) {
            #customer-sidebar {
                width: 100vw;
                max-width: 100vw;
            }
            
            #main-content {
                padding: 0.5rem;
                padding-top: 56px;
            }
            
            .mobile-hamburger-btn {
                top: calc(var(--header-height) + 8px);
                left: 8px;
                width: 38px;
                height: 38px;
                font-size: 14px;
            }
        }

        /* ================================
           SIDEBAR SCROLLBAR (hidden but functional on mobile)
           ================================ */
        #customer-sidebar::-webkit-scrollbar {
            width: 0;
            display: none;
        }
        
        #customer-sidebar {
            scrollbar-width: none;
            -ms-overflow-style: none;
        }
        
        /* ================================
           SCROLL-TO-TOP BUTTON
           ================================ */
        #scrollTopBtn {
            position: fixed;
            right: 20px;
            bottom: 20px;
            z-index: 9999;
            display: none;
            background: linear-gradient(135deg, var(--color-primary), var(--color-secondary));
            color: #fff;
            border: none;
            width: 48px;
            height: 48px;
            border-radius: 50%;
            box-shadow: 0 6px 20px rgba(37, 99, 235, 0.4);
            cursor: pointer;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            transition: transform 180ms ease, opacity 180ms ease, box-shadow 200ms ease;
            opacity: 0;
        }

        #scrollTopBtn.show {
            display: inline-flex;
            opacity: 1;
            transform: translateY(0);
        }

        #scrollTopBtn.hide {
            opacity: 0;
            transform: translateY(8px);
        }
        
        #scrollTopBtn:hover {
            box-shadow: 0 8px 25px rgba(37, 99, 235, 0.6);
            transform: translateY(-2px) scale(1.05);
        }

        #scrollTopBtn:focus {
            outline: 3px solid var(--color-primary-100);
            outline-offset: 2px;
        }
        
        /* Ensure scroll button doesn't interfere on mobile */
        @media (max-width: 768px) {
            #scrollTopBtn {
                right: 15px;
                bottom: 15px;
                width: 44px;
                height: 44px;
                font-size: 18px;
            }
        }

        /* ================================
           GLOBAL CONTAINMENT & OVERFLOW PREVENTION
           ================================ */
        html {
            overflow-x: hidden;
        }
        
        body {
            overflow-x: hidden;
            max-width: 100vw;
        }
        
        /* Prevent cards from overflowing */
        #main-content .bg-white.rounded-2xl,
        #main-content .bg-white.rounded-xl,
        #main-content [class*="rounded-2xl"][class*="shadow"],
        #main-content [class*="rounded-xl"][class*="shadow"] {
            box-sizing: border-box;
            max-width: 100%;
            overflow-wrap: break-word;
            word-break: break-word;
        }
        
        #main-content .grid {
            max-width: 100%;
        }

        /* ================================
           MOBILE HAMBURGER BUTTON
           ================================ */
        .mobile-hamburger-btn {
            display: none;
            position: fixed;
            top: calc(var(--header-height) + 12px);
            left: 12px;
            z-index: 1200;
            width: 44px;
            height: 44px;
            background: linear-gradient(135deg, var(--color-primary), var(--color-secondary));
            color: white;
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(37, 99, 235, 0.4);
            cursor: pointer;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            transition: transform 200ms ease, box-shadow 200ms ease;
        }
        
        .mobile-hamburger-btn:hover {
            transform: scale(1.05);
            box-shadow: 0 6px 20px rgba(37, 99, 235, 0.5);
        }
        
        .mobile-hamburger-btn:focus {
            outline: 3px solid var(--color-primary-100);
            outline-offset: 2px;
        }
        
        .mobile-hamburger-btn .hamburger-icon,
        .mobile-hamburger-btn .close-icon {
            transition: transform 200ms ease, opacity 200ms ease;
        }
        
        .mobile-hamburger-btn.is-open .hamburger-icon {
            display: none;
        }
        
        .mobile-hamburger-btn.is-open .close-icon {
            display: inline;
        }
        
        .mobile-hamburger-btn:not(.is-open) .close-icon {
            display: none;
        }

        /* Show hamburger on tablet/mobile */
        @media (max-width: 900px) {
            .mobile-hamburger-btn {
                display: inline-flex !important;
            }
        }

        /* ================================
           SIDEBAR BACKDROP
           ================================ */
        .sidebar-backdrop {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            z-index: 1050;
            opacity: 0;
            visibility: hidden;
            transition: opacity 300ms ease, visibility 300ms ease;
        }
        
        .sidebar-backdrop.active {
            opacity: 1;
            visibility: visible;
        }

        /* ================================
           BODY LOCK WHEN MOBILE SIDEBAR OPEN
           ================================ */
        body.menu-open {
            overflow: hidden !important;
        }

        @media (min-width: 901px) {
            body.menu-open {
                overflow: visible !important;
            }
        }

        /* ================================
           FORM & INPUT RESPONSIVENESS
           ================================ */
        @media (max-width: 767px) {
            #main-content form {
                max-width: 100%;
            }
            
            #main-content input,
            #main-content select,
            #main-content textarea {
                max-width: 100%;
                box-sizing: border-box;
            }
            
            #main-content .grid-cols-2 {
                grid-template-columns: 1fr;
            }

            /* Smaller cards on mobile */
            #main-content .p-6 {
                padding: 1rem;
            }
            
            #main-content .p-8,
            #main-content .md\:p-8 {
                padding: 1rem;
            }
        }
        
        /* ================================
           MODAL RESPONSIVENESS
           ================================ */
        @media (max-width: 767px) {
            .fixed.inset-0.z-50 > div {
                max-width: 95vw;
                max-height: 90vh;
                margin: 1rem;
            }
        }
        
        /* Alpine cloak */
        #customer-sidebar[x-cloak] {
            display: none !important;
        }
    </style>
</head>

<body 
    class="bg-gray-50 overflow-x-hidden flex flex-col min-h-screen" 
    x-data="(typeof customerDashboard !== 'undefined') ? customerDashboard() : { mobileMenuOpen: false, currentSection: 'dashboard', init(){} }"
    x-effect="mobileMenuOpen ? document.body.classList.add('menu-open') : document.body.classList.remove('menu-open')"
    @toggle-mobile-sidebar.document="mobileMenuOpen = $event.detail.forceClose ? false : !mobileMenuOpen"
>

<!-- ================================
     CUSTOMER HEADER - Loaded from customer_header.php
     ================================ -->
<?php include __DIR__ . '/../includes/customer_header.php'; ?>

<!-- ================================
    LAYOUT WRAPPER - Unified Scroll Layout
    All elements scroll together as a single page
     ================================ -->

<!-- Mobile Overlay (backdrop when sidebar is open on mobile, closes sidebar on click)
     NOTE: avoid aria-hidden/tabindex on overlays that can receive focus — make the backdrop non-focusable
-->
<div
    x-show="mobileMenuOpen"
    @click="mobileMenuOpen = false"
    x-transition:enter="transition-opacity ease-out duration-300"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition-opacity ease-in duration-200"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    class="fixed bg-black bg-opacity-60 z-[1050] lg:hidden"
    style="display: none; top: 80px; left: 0; right: 0; bottom: 0;"
></div>

<!-- Mobile Hamburger Button - Toggles sidebar on tablet/mobile -->
<button 
    id="mobileHamburgerBtn"
    class="mobile-hamburger-btn"
    @click="mobileMenuOpen = !mobileMenuOpen; $el.classList.toggle('is-open', mobileMenuOpen)"
    :class="{'is-open': mobileMenuOpen}"
    :aria-expanded="mobileMenuOpen.toString()"
    aria-label="Toggle navigation menu"
    aria-controls="customer-sidebar"
>
    <i class="fas fa-bars hamburger-icon"></i>
    <i class="fas fa-times close-icon"></i>
</button>

<!-- Sidebar Backdrop (closes sidebar when clicked) -->
<div 
    class="sidebar-backdrop"
    :class="{'active': mobileMenuOpen}"
    @click="mobileMenuOpen = false"
    x-show="mobileMenuOpen"
    style="display: none;"
></div>

<!-- Main Content Wrapper: Flex layout with sidebar and content side by side -->
<div class="dashboard-layout flex flex-row">
    
    <!-- ================================
         SIDEBAR - Spans full height from header to footer
         Desktop: Always visible, grows with main content
         Mobile: Fixed overlay with internal scroll
         ================================ -->
    <aside 
        id="customer-sidebar"
        class="bg-gradient-to-b from-blue-600 via-blue-700 to-purple-700 text-white shadow-2xl
               transition-transform duration-300 ease-in-out
               flex flex-col"
        :class="mobileMenuOpen ? 'translate-x-0 sidebar-open' : '-translate-x-full lg:translate-x-0'"
        x-transition:enter="transition-transform ease-out duration-300"
        x-transition:enter-start="transform -translate-x-full"
        x-transition:enter-end="transform translate-x-0"
        x-transition:leave="transition-transform ease-in duration-200"
        x-transition:leave-start="transform translate-x-0"
        x-transition:leave-end="transform -translate-x-full"
        role="navigation"
        aria-label="Main navigation"
        :inert="!mobileMenuOpen && window.innerWidth < 1024"
    >
        <!-- User Profile Section (Better readability, always visible at top) -->
        <div class="flex-shrink-0 p-4 border-b border-white border-opacity-20 bg-blue-800 bg-opacity-30">
            <div class="text-center">
                <div class="sidebar-profile-container mx-auto mb-2">
                    <img 
                        id="sidebarProfileImage" 
                        src="<?php echo htmlspecialchars($header_profile_src); ?>" 
                        alt="<?php echo htmlspecialchars($user_name); ?>"
                        class="sidebar-avatar-img"
                        onerror="this.src='<?php echo $default_avatar; ?>'"
                    >
                </div>
                <h3 class="text-sm font-bold text-white truncate"><?php echo htmlspecialchars($user_name); ?></h3>
                <p class="text-xs text-blue-100 opacity-90 truncate mt-1"><?php echo htmlspecialchars($user_email); ?></p>
            </div>
        </div>
        
        <!-- Navigation Menu (Better spacing and readability) -->
        <nav class="flex-1 px-3 py-3 flex flex-col" 
             aria-label="Primary navigation"
        >
            
            <!-- Dashboard -->
            <a 
                href="#dashboard" 
                @click.prevent="currentSection = 'dashboard'; mobileMenuOpen = false"
                :class="currentSection === 'dashboard' ? 'bg-white bg-opacity-20 shadow-lg font-semibold' : 'hover:bg-white hover:bg-opacity-10'"
                class="flex items-center gap-3 px-3 py-3 rounded-lg transition-all duration-200 group focus:outline-none focus:ring-2 focus:ring-white focus:ring-opacity-50"
                role="menuitem"
                tabindex="0"
            >
                <i class="fas fa-tachometer-alt text-base w-5 h-5 flex items-center justify-center group-hover:scale-110 transition-transform"></i>
                <span class="text-sm font-medium">Genel Bakış</span>
            </a>
            
            <!-- Car Wash Selection -->
            <a 
                href="#carWashSelection" 
                @click.prevent="currentSection = 'carWashSelection'; mobileMenuOpen = false"
                :class="currentSection === 'carWashSelection' ? 'bg-white bg-opacity-20 shadow-lg font-semibold' : 'hover:bg-white hover:bg-opacity-10'"
                class="flex items-center gap-3 px-3 py-3 rounded-lg transition-all duration-200 group focus:outline-none focus:ring-2 focus:ring-white focus:ring-opacity-50"
                role="menuitem"
                tabindex="0"
            >
                <i class="fas fa-hand-pointer text-base w-5 h-5 flex items-center justify-center group-hover:scale-110 transition-transform"></i>
                <span class="text-sm font-medium">Oto Yıkama Seçimi</span>
            </a>
            
            <!-- Reservations -->
            <a 
                href="#reservations" 
                @click.prevent="currentSection = 'reservations'; mobileMenuOpen = false"
                :class="currentSection === 'reservations' ? 'bg-white bg-opacity-20 shadow-lg font-semibold' : 'hover:bg-white hover:bg-opacity-10'"
                class="flex items-center gap-3 px-3 py-3 rounded-lg transition-all duration-200 group focus:outline-none focus:ring-2 focus:ring-white focus:ring-opacity-50"
                role="menuitem"
                tabindex="0"
            >
                <i class="fas fa-calendar-check text-base w-5 h-5 flex items-center justify-center group-hover:scale-110 transition-transform"></i>
                <span class="text-sm font-medium">Rezervasyonlarım</span>
            </a>
            
            <!-- Vehicles -->
            <a 
                href="#vehicles" 
                @click.prevent="currentSection = 'vehicles'; mobileMenuOpen = false"
                :class="currentSection === 'vehicles' ? 'bg-white bg-opacity-20 shadow-lg font-semibold' : 'hover:bg-white hover:bg-opacity-10'"
                class="flex items-center gap-3 px-3 py-3 rounded-lg transition-all duration-200 group focus:outline-none focus:ring-2 focus:ring-white focus:ring-opacity-50"
                role="menuitem"
                tabindex="0"
            >
                <i class="fas fa-car text-base w-5 h-5 flex items-center justify-center group-hover:scale-110 transition-transform"></i>
                <span class="text-sm font-medium">Araçlarım</span>
            </a>
            
            <!-- History -->
            <a 
                href="#history" 
                @click.prevent="currentSection = 'history'; mobileMenuOpen = false"
                :class="currentSection === 'history' ? 'bg-white bg-opacity-20 shadow-lg font-semibold' : 'hover:bg-white hover:bg-opacity-10'"
                class="flex items-center gap-3 px-3 py-3 rounded-lg transition-all duration-200 group focus:outline-none focus:ring-2 focus:ring-white focus:ring-opacity-50"
                role="menuitem"
                tabindex="0"
            >
                <i class="fas fa-history text-base w-5 h-5 flex items-center justify-center group-hover:scale-110 transition-transform"></i>
                <span class="text-sm font-medium">Geçmiş</span>
            </a>
            
            <!-- Profile -->
            <a 
                href="#profile" 
                @click.prevent="currentSection = 'profile'; mobileMenuOpen = false"
                :class="currentSection === 'profile' ? 'bg-white bg-opacity-20 shadow-lg font-semibold' : 'hover:bg-white hover:bg-opacity-10'"
                class="flex items-center gap-3 px-3 py-3 rounded-lg transition-all duration-200 group focus:outline-none focus:ring-2 focus:ring-white focus:ring-opacity-50"
                role="menuitem"
                tabindex="0"
            >
                <i class="fas fa-user-circle text-base w-5 h-5 flex items-center justify-center group-hover:scale-110 transition-transform"></i>
                <span class="text-sm font-medium">Profil</span>
            </a>
            
            <!-- Support -->
            <a 
                href="#support" 
                @click.prevent="currentSection = 'support'; mobileMenuOpen = false"
                :class="currentSection === 'support' ? 'bg-white bg-opacity-20 shadow-lg font-semibold' : 'hover:bg-white hover:bg-opacity-10'"
                class="flex items-center gap-3 px-3 py-3 rounded-lg transition-all duration-200 group focus:outline-none focus:ring-2 focus:ring-white focus:ring-opacity-50"
                role="menuitem"
                tabindex="0"
            >
                <i class="fas fa-headset text-base w-5 h-5 flex items-center justify-center group-hover:scale-110 transition-transform"></i>
                <span class="text-sm font-medium">Destek</span>
            </a>
        </nav>
        
        <!-- Settings (Fixed at bottom, better readability) -->
        <div class="flex-shrink-0 p-3 border-t border-white border-opacity-20 bg-blue-800 bg-opacity-20">
            <a 
                href="#settings" 
                @click.prevent="currentSection = 'settings'; mobileMenuOpen = false"
                :class="currentSection === 'settings' ? 'bg-white bg-opacity-20 shadow-lg font-semibold' : 'hover:bg-white hover:bg-opacity-10'"
                class="flex items-center gap-3 px-3 py-3 rounded-lg transition-all duration-200 group focus:outline-none focus:ring-2 focus:ring-white focus:ring-opacity-50"
                role="menuitem"
                tabindex="0"
            >
                <i class="fas fa-cog text-base w-5 h-5 flex items-center justify-center group-hover:scale-110 transition-transform"></i>
                <span class="text-sm font-medium">Ayarlar</span>
            </a>
        </div>
    </aside>

    <!-- ================================
         MAIN CONTENT AREA - Flows naturally beside sidebar
         Desktop: Fills remaining width beside sidebar
         Mobile: Full width (sidebar is overlay)
         ================================ -->
    <main class="flex-1 bg-gray-50" id="main-content">
        <!-- Use full-width container to ensure main-content expands to contain children -->
        <div class="pt-0 px-6 pb-6 lg:px-8 lg:pb-8 w-full">
            <!-- Global Toast/Error Area - visible even when modals are open -->
            <div id="globalToast" class="fixed top-24 right-6 z-[9999] max-w-md transition-all duration-300 transform translate-x-full opacity-0 pointer-events-none" role="alert" aria-live="assertive">
                <div id="globalToastContent" class="rounded-xl shadow-2xl border px-5 py-4 flex items-start gap-3">
                    <i id="globalToastIcon" class="fas fa-exclamation-circle text-xl mt-0.5"></i>
                    <div class="flex-1">
                        <p id="globalToastMessage" class="font-medium"></p>
                    </div>
                    <button onclick="hideGlobalToast()" class="text-gray-400 hover:text-gray-600 transition-colors">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
            
            <?php
            // Render flash error from session (e.g., after profile upload failure)
            $flashError = $_SESSION['flash_error'] ?? null;
            $flashSuccess = $_SESSION['flash_success'] ?? $_SESSION['success'] ?? null;
            if ($flashError) { unset($_SESSION['flash_error']); }
            if (isset($_SESSION['flash_success'])) { unset($_SESSION['flash_success']); }
            if (isset($_SESSION['success'])) { unset($_SESSION['success']); }
            ?>
            <?php if ($flashError): ?>
                <div class="mb-6" id="serverFlashError">
                    <div class="bg-red-50 border border-red-300 text-red-700 px-4 py-3 rounded-xl relative flex items-center gap-3">
                        <i class="fas fa-exclamation-triangle text-red-500"></i>
                        <span><?php echo htmlspecialchars($flashError); ?></span>
                        <button onclick="this.parentElement.parentElement.remove()" class="ml-auto text-red-400 hover:text-red-600">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            <?php endif; ?>
            <?php if ($flashSuccess): ?>
                <div class="mb-6" id="serverFlashSuccess">
                    <div class="bg-green-50 border border-green-300 text-green-700 px-4 py-3 rounded-xl relative flex items-center gap-3">
                        <i class="fas fa-check-circle text-green-500"></i>
                        <span><?php echo htmlspecialchars($flashSuccess); ?></span>
                        <button onclick="this.parentElement.parentElement.remove()" class="ml-auto text-green-400 hover:text-green-600">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            <?php endif; ?>
        
        <!-- ========== DASHBOARD SECTION ========== -->
        <section x-show="currentSection === 'dashboard'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 transform translate-y-4" x-transition:enter-end="opacity-100 transform translate-y-0" class="space-y-6 pt-6 lg:pt-8">
            <div class="mb-8">
                <h2 class="text-2xl md:text-3xl font-bold text-gray-900 mb-2">Dashboard</h2>
                <p class="text-gray-600">Hoş geldiniz, <?php echo htmlspecialchars($user_name); ?>!</p>
            </div>
            
            <!-- Stats Grid - Responsive with consistent spacing -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 lg:gap-6">
                <!-- Stat Card 1 - Total Reservations -->
                <div class="bg-white rounded-2xl shadow-md border border-gray-100 p-6 hover:shadow-lg hover:-translate-y-1 transition-all duration-300">
                    <div class="flex items-center justify-between mb-4">
                        <h4 class="text-sm font-semibold text-gray-700">Toplam Rezervasyon</h4>
                        <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-calendar-alt text-blue-600 text-xl"></i>
                        </div>
                    </div>
                    <p class="text-3xl font-bold text-gray-900 mb-2">24</p>
                    <p class="text-sm text-gray-500 flex items-center">
                        <i class="fas fa-arrow-up text-green-500 mr-1.5 text-xs"></i>
                        <span>12% artış</span>
                    </p>
                </div>
                
                <!-- Stat Card 2 - Completed -->
                <div class="bg-white rounded-2xl shadow-md border border-gray-100 p-6 hover:shadow-lg hover:-translate-y-1 transition-all duration-300">
                    <div class="flex items-center justify-between mb-4">
                        <h4 class="text-sm font-semibold text-gray-700">Tamamlanan</h4>
                        <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-check-circle text-green-600 text-xl"></i>
                        </div>
                    </div>
                    <p class="text-3xl font-bold text-gray-900 mb-2">18</p>
                    <p class="text-sm text-gray-500">Bu ay</p>
                </div>
                
                <!-- Stat Card 3 - Pending -->
                <div class="bg-white rounded-2xl shadow-md border border-gray-100 p-6 hover:shadow-lg hover:-translate-y-1 transition-all duration-300">
                    <div class="flex items-center justify-between mb-4">
                        <h4 class="text-sm font-semibold text-gray-700">Bekleyen</h4>
                        <div class="w-12 h-12 bg-yellow-100 rounded-xl flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-clock text-yellow-600 text-xl"></i>
                        </div>
                    </div>
                    <p class="text-3xl font-bold text-gray-900 mb-2">4</p>
                    <p class="text-sm text-gray-500">Onay bekliyor</p>
                </div>
                
                <!-- Stat Card 4 - Vehicles -->
                <div class="bg-white rounded-2xl shadow-md border border-gray-100 p-6 hover:shadow-lg hover:-translate-y-1 transition-all duration-300">
                    <div class="flex items-center justify-between mb-4">
                        <h4 class="text-sm font-semibold text-gray-700">Araç</h4>
                        <div class="w-12 h-12 bg-purple-100 rounded-xl flex items-center justify-center flex-shrink-0">
                            <i class="fas fa-car text-purple-600 text-xl"></i>
                        </div>
                    </div>
                    <p class="text-3xl font-bold text-gray-900 mb-2" id="vehicleStatCount">-</p>
                    <p class="text-sm text-gray-500">Aktif</p>
                </div>
            </div>
            
            <!-- Quick Actions - Responsive Grid -->
            <div class="mt-8">
                <h3 class="text-xl font-bold text-gray-900 mb-6">Hızlı İşlemler</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 lg:gap-6">
                    <!-- New Reservation Card -->
                    <div class="bg-gradient-to-br from-blue-500 to-purple-600 rounded-2xl p-6 lg:p-8 text-white shadow-md hover:shadow-xl hover:-translate-y-1 transition-all duration-300">
                        <i class="fas fa-plus-circle text-4xl lg:text-5xl mb-4 opacity-90"></i>
                        <h4 class="text-xl font-bold mb-2">Yeni Rezervasyon</h4>
                        <p class="text-blue-100 mb-6 text-sm lg:text-base">Araç yıkama hizmeti rezervasyonu oluşturun</p>
                        <button 
                            @click="currentSection = 'carWashSelection'"
                            class="bg-white text-blue-600 px-6 py-3 rounded-xl font-semibold hover:bg-blue-50 active:bg-blue-100 transition-colors inline-flex items-center gap-2 shadow-md"
                        >
                            <span>Rezervasyon Yap</span>
                            <i class="fas fa-arrow-right text-sm"></i>
                        </button>
                    </div>
                    
                    <!-- Add Vehicle Card -->
                    <div class="bg-gradient-to-br from-green-500 to-teal-600 rounded-2xl p-6 lg:p-8 text-white shadow-md hover:shadow-xl hover:-translate-y-1 transition-all duration-300">
                        <i class="fas fa-car text-4xl lg:text-5xl mb-4 opacity-90"></i>
                        <h4 class="text-xl font-bold mb-2">Araç Ekle</h4>
                        <p class="text-green-100 mb-6 text-sm lg:text-base">Yeni araç bilgisi kaydedin</p>
                        <button 
                            @click="currentSection = 'vehicles'"
                            class="bg-white text-green-600 px-6 py-3 rounded-xl font-semibold hover:bg-green-50 active:bg-green-100 transition-colors inline-flex items-center gap-2 shadow-md"
                        >
                            <span>Araç Ekle</span>
                            <i class="fas fa-arrow-right text-sm"></i>
                        </button>
                    </div>
                </div>
            </div>
        </section>
        
        <!-- ========== VEHICLES SECTION ========== -->
    <section x-show="currentSection === 'vehicles'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 transform translate-y-4" x-transition:enter-end="opacity-100 transform translate-y-0" class="space-y-6 pt-6 lg:pt-8" x-data="(typeof vehicleManager !== 'undefined') ? vehicleManager() : (window.vehicleManager ? (console.info('Using window.vehicleManager fallback'), window.vehicleManager()) : (console.warn('vehicleManager factory missing � using minimal fallback'), { vehicles: [], showVehicleForm: false, editingVehicle: null, loading: false, message:'', messageType:'', csrfToken: '', imagePreview: '', formData: { brand: '', model: '', license_plate: '', year: '', color: '' } }))" style="display: none;">
            <div class="mb-8">
                <h2 class="text-2xl md:text-3xl font-bold text-gray-900 mb-2">Araçlarım</h2>
                <p class="text-gray-600">Araçlarınızı yönetin</p>
            </div>
            
            <!-- Action Buttons - Responsive -->
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-6">
                <p class="text-sm text-gray-600" x-text="vehicles.length + ' araç kayıtlı'"></p>
                <div class="flex flex-col sm:flex-row gap-3 w-full sm:w-auto">
                    <button 
                        @click="loadVehicles()"
                        class="w-full sm:w-auto h-11 px-5 border-2 border-blue-600 text-blue-600 rounded-xl font-semibold hover:bg-blue-50 active:bg-blue-100 transition-colors inline-flex items-center justify-center gap-2"
                    >
                        <i class="fas fa-sync-alt text-sm"></i>
                        <span>Yenile</span>
                    </button>
                    <button 
                        @click="openVehicleForm()"
                        class="w-full sm:w-auto h-11 px-5 bg-gradient-to-r from-blue-600 to-purple-600 text-white rounded-xl font-semibold hover:shadow-lg active:shadow-md transition-all inline-flex items-center justify-center gap-2"
                    >
                        <i class="fas fa-plus text-sm"></i>
                        <span>Araç Ekle</span>
                    </button>
                </div>
            </div>
            
            <!-- Vehicles Grid - Responsive -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 lg:gap-6" id="vehiclesList">
                <template x-for="vehicle in vehicles" :key="vehicle.id">
                    <div class="bg-white rounded-2xl shadow-md border border-gray-100 p-6 hover:shadow-lg hover:-translate-y-1 transition-all duration-300">
                        <div class="flex items-start gap-4 mb-4">
                            <img 
                                :src="vehicle.image_path || '/carwash_project/frontend/assets/images/default-car.png'" 
                                :alt="vehicle.brand + ' ' + vehicle.model"
                                class="w-20 h-20 rounded-xl object-cover bg-gray-100 flex-shrink-0 ring-2 ring-gray-200"
                                @error="$el.src='/carwash_project/frontend/assets/images/default-car.png'"
                            >
                            <div class="flex-1 min-w-0">
                                <h4 class="font-bold text-lg text-gray-900 truncate mb-1" x-text="vehicle.brand + ' ' + vehicle.model"></h4>
                                <p class="text-sm text-gray-600 flex items-center gap-1.5">
                                    <i class="fas fa-id-card text-xs"></i>
                                    <span x-text="vehicle.license_plate"></span>
                                </p>
                                <div class="flex items-center flex-wrap gap-x-3 gap-y-1 mt-2 text-xs text-gray-500">
                                    <span x-show="vehicle.year" class="flex items-center gap-1">
                                        <i class="fas fa-calendar"></i>
                                        <span x-text="vehicle.year"></span>
                                    </span>
                                    <span x-show="vehicle.color" class="flex items-center gap-1">
                                        <i class="fas fa-palette"></i>
                                        <span x-text="vehicle.color"></span>
                                    </span>
                                    <span x-show="vehicle.vehicle_type" class="flex items-center gap-1">
                                        <i class="fas fa-car"></i>
                                        <span x-text="vehicle.vehicle_type"></span>
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="flex gap-2 pt-4 border-t border-gray-100">
                            <button 
                                @click="editVehicle(vehicle)"
                                class="flex-1 h-10 text-blue-600 hover:bg-blue-50 active:bg-blue-100 rounded-lg transition-colors font-medium text-sm inline-flex items-center justify-center gap-1.5"
                            >
                                <i class="fas fa-edit text-xs"></i>
                                <span>Düzenle</span>
                            </button>
                            <button 
                                @click="deleteVehicle(vehicle.id)"
                                class="flex-1 h-10 text-red-600 hover:bg-red-50 active:bg-red-100 rounded-lg transition-colors font-medium text-sm inline-flex items-center justify-center gap-1.5"
                            >
                                <i class="fas fa-trash text-xs"></i>
                                <span>Sil</span>
                            </button>
                        </div>
                    </div>
                </template>
                
                <!-- Empty State -->
                <template x-if="vehicles.length === 0">
                    <div class="col-span-full text-center py-16 bg-white rounded-2xl border-2 border-dashed border-gray-300">
                        <i class="fas fa-car text-6xl text-gray-300 mb-4"></i>
                        <p class="text-gray-500 text-lg mb-4">Henüz araç yok</p>
                        <button 
                            @click="openVehicleForm()"
                            class="px-6 py-3 bg-gradient-to-r from-blue-600 to-purple-600 text-white rounded-xl font-semibold hover:shadow-lg transition-all inline-flex items-center space-x-2"
                        >
                            <i class="fas fa-plus"></i>
                            <span>İlk Aracınızı Ekleyin</span>
                        </button>
                    </div>
                </template>
            </div>
            
            <!-- Vehicle Form Modal -->
            <div 
                x-show="showVehicleForm"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4 overflow-y-auto"
                style="display: none;"
                @click.self="closeVehicleForm()"
            >
                <div 
                    class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto my-8"
                    x-transition:enter="transition ease-out duration-300 transform"
                    x-transition:enter-start="opacity-0 scale-95"
                    x-transition:enter-end="opacity-100 scale-100"
                    x-transition:leave="transition ease-in duration-200 transform"
                    x-transition:leave-start="opacity-100 scale-100"
                    x-transition:leave-end="opacity-0 scale-95"
                >
                    <div class="sticky top-0 bg-white p-6 border-b border-gray-200 z-10">
                        <div class="flex justify-between items-center">
                            <h3 class="text-2xl font-bold text-gray-900" x-text="editingVehicle ? 'Araç Düzenle' : 'Yeni Araç Ekle'"></h3>
                            <button @click="closeVehicleForm()" class="text-gray-400 hover:text-gray-600 transition-colors p-2 rounded-lg hover:bg-gray-100">
                                <i class="fas fa-times text-2xl"></i>
                            </button>
                        </div>
                    </div>
                    
                    <form id="vehicleForm" @submit.prevent="saveVehicle()" class="p-6" enctype="multipart/form-data">
                        <label for="auto_label_108" class="sr-only">CSRF Token</label>
                        <input type="hidden" name="csrf_token" :value="csrfToken" id="auto_label_108">
                        <label for="auto_label_107" class="sr-only">Action</label>
                        <input type="hidden" name="action" :value="editingVehicle ? 'update' : 'create'" id="auto_label_107">
                        <label for="auto_label_106" class="sr-only">Vehicle ID</label>
                        <input type="hidden" name="id" :value="editingVehicle?.id || ''" id="auto_label_106">
                        
                        <!-- Form Fields Grid -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4">
                            <!-- Brand -->
                            <div class="mb-4">
                                <label for="vehicle_brand" class="block text-sm font-semibold text-gray-700 mb-2">
                                    Marka <span class="text-red-500">*</span>
                                </label>
                                <input 
                                    type="text"
                                    id="vehicle_brand"
                                    name="brand"
                                    x-model="formData.brand"
                                    required
                                    autocomplete="off"
                                    class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg text-gray-900 placeholder-gray-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 focus:outline-none transition-colors"
                                    :class="{'border-red-500': formData.brand === ''}"
                                    placeholder="Toyota"
                                >
                            </div>
                            
                            <!-- Model -->
                            <div class="mb-4">
                                <label for="vehicle_model" class="block text-sm font-semibold text-gray-700 mb-2">
                                    Model <span class="text-red-500">*</span>
                                </label>
                                <input 
                                    type="text"
                                    id="vehicle_model"
                                    name="model"
                                    x-model="formData.model"
                                    required
                                    autocomplete="off"
                                    class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg text-gray-900 placeholder-gray-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 focus:outline-none transition-colors"
                                    :class="{'border-red-500': formData.model === ''}"
                                    placeholder="Corolla"
                                >
                            </div>
                            
                            <!-- License Plate -->
                            <div class="mb-4">
                                <label for="vehicle_license_plate" class="block text-sm font-semibold text-gray-700 mb-2">
                                    Plaka <span class="text-red-500">*</span>
                                </label>
                                <input 
                                    type="text"
                                    id="vehicle_license_plate"
                                    name="license_plate"
                                    x-model="formData.license_plate"
                                    required
                                    autocomplete="off"
                                    class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg text-gray-900 placeholder-gray-400 uppercase focus:border-blue-500 focus:ring-2 focus:ring-blue-200 focus:outline-none transition-colors"
                                    :class="{'border-red-500': formData.license_plate === ''}"
                                    placeholder="34 ABC 123"
                                >
                            </div>
                            
                            <!-- Year -->
                            <div class="mb-4">
                                <label for="vehicle_year" class="block text-sm font-semibold text-gray-700 mb-2">Yıl</label>
                                <input 
                                    type="number"
                                    id="vehicle_year"
                                    name="year"
                                    x-model="formData.year"
                                    min="1900"
                                    :max="new Date().getFullYear()"
                                    autocomplete="off"
                                    class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg text-gray-900 placeholder-gray-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 focus:outline-none transition-colors"
                                    placeholder="2020"
                                >
                            </div>
                            
                            <!-- Color -->
                            <div class="mb-4">
                                <label for="vehicle_color" class="block text-sm font-semibold text-gray-700 mb-2">Renk</label>
                                <input 
                                    type="text"
                                    id="vehicle_color"
                                    name="color"
                                    x-model="formData.color"
                                    autocomplete="off"
                                    class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg text-gray-900 placeholder-gray-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 focus:outline-none transition-colors"
                                    placeholder="Beyaz"
                                >
                            </div>
                            
                            <!-- Vehicle Type -->
                            <div class="mb-4">
                                <label for="vehicle_type" class="block text-sm font-semibold text-gray-700 mb-2">
                                    Araç Tipi <span class="text-red-500">*</span>
                                </label>
                                <select 
                                    id="vehicle_type"
                                    name="vehicle_type"
                                    x-model="formData.vehicle_type"
                                    required
                                    class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg text-gray-900 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 focus:outline-none transition-colors"
                                    :class="{'border-red-500': formData.vehicle_type === ''}"
                                >
                                    <option value="">Seçiniz</option>
                                    <option value="sedan">Sedan</option>
                                    <option value="hatchback">Hatchback</option>
                                    <option value="suv">SUV</option>
                                    <option value="pickup">Pickup</option>
                                    <option value="van">Van</option>
                                    <option value="coupe">Coupe</option>
                                    <option value="convertible">Convertible</option>
                                    <option value="wagon">Wagon</option>
                                    <option value="other">Diğer</option>
                                </select>
                            </div>
                            <div class="mb-4">
                                <label for="vehicle_image" class="block text-sm font-semibold text-gray-700 mb-2">Araç Fotoğrafı</label>
                                <input 
                                    type="file"
                                    id="vehicle_image"
                                    name="vehicle_image"
                                    @change="previewImage($event)"
                                    accept="image/*"
                                    class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg text-gray-900 text-sm file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 cursor-pointer focus:border-blue-500 focus:ring-2 focus:ring-blue-200 focus:outline-none transition-colors"
                                >
                            </div>
                        </div>
                        
                        <!-- Image Preview -->
                        <div class="mb-4">
                            <p class="block text-sm font-semibold text-gray-700 mb-2">Önizleme</p>
                            <img 
                                :src="imagePreview || '/carwash_project/frontend/assets/images/default-car.png'"
                                alt="Preview"
                                class="w-32 h-24 object-cover rounded-lg border-2 border-gray-300 shadow-sm"
                            >
                        </div>
                        
                        <!-- Notes -->
                        <div class="mb-4">
                            <label for="vehicle_notes" class="block text-sm font-semibold text-gray-700 mb-2">Notlar</label>
                            <textarea 
                                id="vehicle_notes"
                                name="notes"
                                x-model="formData.notes"
                                rows="3"
                                class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg text-gray-900 placeholder-gray-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 focus:outline-none transition-colors resize-vertical"
                                placeholder="Araç hakkında ek bilgiler..."
                            ></textarea>
                        </div>
                        <div class="flex flex-col-reverse sm:flex-row justify-end gap-3 pt-4 border-t border-gray-200">
                            <button 
                                type="button"
                                @click="closeVehicleForm()"
                                class="w-full sm:w-auto px-6 py-3 border-2 border-gray-300 text-gray-700 rounded-xl font-semibold hover:bg-gray-50 transition-colors"
                            >
                                İptal
                            </button>
                            <button 
                                type="submit"
                                :disabled="loading"
                                class="w-full sm:w-auto px-6 py-3 bg-gradient-to-r from-blue-600 to-purple-600 text-white rounded-xl font-semibold hover:shadow-lg transition-all disabled:opacity-50 disabled:cursor-not-allowed inline-flex items-center justify-center space-x-2"
                            >
                                <i class="fas" :class="loading ? 'fa-spinner fa-spin' : 'fa-save'"></i>
                                <span x-text="loading ? 'Kaydediliyor...' : 'Kaydet'"></span>
                            </button>
                        </div>
                        
                        <div x-show="message" class="p-4 rounded-xl" :class="messageType === 'error' ? 'bg-red-50 border-2 border-red-200 text-red-700' : 'bg-green-50 border-2 border-green-200 text-green-700'" style="display: none;">
                            <div class="flex items-start space-x-3">
                                <i class="fas text-lg mt-0.5" :class="messageType === 'error' ? 'fa-exclamation-circle' : 'fa-check-circle'"></i>
                                <p class="flex-1" x-text="message"></p>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </section>
        
        <!-- ========== PROFILE SECTION ========== -->
        <section 
            x-show="currentSection === 'profile'" 
            x-transition:enter="transition ease-out duration-300" 
            x-transition:enter-start="opacity-0 transform translate-y-4" 
            x-transition:enter-end="opacity-100 transform translate-y-0" 
            class="space-y-6 pt-6 lg:pt-8" 
            style="display: none;"
            x-data="profileSection()"
        >
            <div class="mb-8 flex justify-between items-center">
                <div>
                    <h2 class="text-2xl md:text-3xl font-bold text-gray-900 mb-2">Profil Ayarları</h2>
                    <p class="text-gray-600" x-text="editMode ? 'Bilgilerinizi güncelleyin' : 'Profil bilgilerinizi görüntüleyin'"></p>
                </div>
                <button 
                    x-show="!editMode"
                    @click="toggleEdit()"
                    class="px-6 py-3 bg-gradient-to-r from-blue-600 to-purple-600 text-white rounded-xl font-semibold hover:shadow-lg transition-all inline-flex items-center gap-2"
                >
                    <i class="fas fa-edit"></i>
                    <span>Düzenle</span>
                </button>
            </div>
            
            <!-- VIEW MODE: Display Profile Info -->
            <div 
                x-show="!editMode"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                class="bg-white rounded-2xl shadow-md border border-gray-100 p-6 md:p-8"
            >
                <div class="space-y-6">
                    <!-- Profile Header -->
                    <div class="flex items-center gap-6 pb-6 border-b border-gray-200">
                        <div class="w-24 h-24 rounded-full overflow-hidden border-4 border-blue-100 bg-gray-100">
                            <img
                                :src="(function(){
                                    var base = '<?php echo BASE_URL; ?>';
                                    var img = profileData.profile_image || '';
                                    if (!img) return base + '/frontend/images/default-avatar.svg';
                                    if (img.startsWith('http://') || img.startsWith('https://')) return img + '?t=' + Date.now();
                                    if (img.startsWith(base)) return img + '?t=' + Date.now();
                                    return base + '/' + img.replace(/^\/+/, '') + '?t=' + Date.now();
                                })()"
                                alt="Profile"
                                class="w-full h-full object-cover"
                                @error="$event.target.src='<?php echo BASE_URL; ?>/frontend/images/default-avatar.svg'; $event.target.onerror=null;"
                            >
                        </div>
                        <div>
                            <h3 class="text-2xl font-bold text-gray-900" x-text="profileData.name"><?php echo htmlspecialchars($userData['name'] ?? $user_name); ?></h3>
                            <p class="text-gray-600 mt-1" x-text="profileData.email"><?php echo htmlspecialchars($userData['email'] ?? $user_email); ?></p>
                        </div>
                    </div>
                    
                    <!-- Profile Details -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-2">
                            <label class="text-sm font-semibold text-gray-500">Kullanıcı Adı</label>
                            <p class="text-base text-gray-900" x-text="profileData.username || '-'"><?php echo htmlspecialchars($user_username ?: '-'); ?></p>
                        </div>
                        <div class="space-y-2">
                            <label class="text-sm font-semibold text-gray-500">Telefon</label>
                            <p class="text-base text-gray-900" x-text="profileData.phone || '-'"><?php echo htmlspecialchars($user_phone ?: '-'); ?></p>
                        </div>
                        <div class="space-y-2">
                            <label class="text-sm font-semibold text-gray-500">Ev Telefonu</label>
                            <p class="text-base text-gray-900" x-text="profileData.home_phone || '-'"><?php echo htmlspecialchars($user_home_phone ?: '-'); ?></p>
                        </div>
                        <div class="space-y-2">
                            <label class="text-sm font-semibold text-gray-500">T.C. Kimlik No</label>
                            <p class="text-base text-gray-900" x-text="profileData.national_id || '-'"><?php echo htmlspecialchars($user_national_id ?: '-'); ?></p>
                        </div>
                        <div class="space-y-2">
                            <label class="text-sm font-semibold text-gray-500">Sürücü Belgesi No</label>
                            <p class="text-base text-gray-900" x-text="profileData.driver_license || '-'"><?php echo htmlspecialchars($user_driver_license ?: '-'); ?></p>
                        </div>
                        <div class="space-y-2">
                            <label class="text-sm font-semibold text-gray-500">Şehir</label>
                            <p class="text-base text-gray-900" x-text="profileData.city || '-'"><?php echo htmlspecialchars($user_city ?: '-'); ?></p>
                        </div>
                        <div class="space-y-2 md:col-span-2">
                            <label class="text-sm font-semibold text-gray-500">Adres</label>
                            <p class="text-base text-gray-900" x-text="profileData.address || '-'"><?php echo htmlspecialchars($user_address ?: '-'); ?></p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- EDIT MODE: Profile Form -->
            <div 
                x-show="editMode"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 scale-95"
                x-transition:enter-end="opacity-100 scale-100"
                class="bg-white rounded-2xl shadow-md border border-gray-100 p-6 md:p-8"
            >
                <?php if (!empty($uploadError)): ?>
                <div class="mb-4 p-4 bg-red-50 border border-red-200 text-red-700 rounded-lg">
                    <i class="fas fa-exclamation-circle mr-2"></i>
                    <?php echo htmlspecialchars($uploadError); ?>
                </div>
                <?php endif; ?>
                <?php if (!empty($successMessage)): ?>
                <div class="mb-4 p-4 bg-green-50 border border-green-200 text-green-700 rounded-lg">
                    <i class="fas fa-check-circle mr-2"></i>
                    <?php echo htmlspecialchars($successMessage); ?>
                </div>
                <?php endif; ?>
                <form id="profileForm" action="<?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?>" class="space-y-6" enctype="multipart/form-data" method="POST">
                    <input type="hidden" name="action" value="update_profile">
                    <?php
                    // Idempotent ensure session and CSRF token for profile & password change forms
                    if (session_status() !== PHP_SESSION_ACTIVE) {
                        \App\Classes\Session::start();
                    }
                    if (empty($_SESSION['csrf_token'])) {
                        $csrf_helper = __DIR__ . '/../../includes/csrf_protect.php';
                        if (file_exists($csrf_helper)) {
                            require_once $csrf_helper;
                            if (function_exists('generate_csrf_token')) {
                                generate_csrf_token();
                            } else {
                                $_SESSION['csrf_token'] = bin2hex(random_bytes(24));
                            }
                        } else {
                            $_SESSION['csrf_token'] = bin2hex(random_bytes(24));
                        }
                    }
                    ?>
                    <label for="auto_label_105" class="sr-only">Csrf token</label>
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>" id="auto_label_105">
                    <!-- Form-level errors (populated by JS) -->
                    <div id="form-errors-container" class="form-error" style="display:none;margin-bottom:0.75rem;">
                        <ul id="form-errors-list" class="list-disc pl-5 text-sm text-red-600"></ul>
                    </div>
                    <!-- Profile Image Upload Section -->
                    <div class="mb-6 pb-6 border-b border-gray-200">
                        <h4 class="text-lg font-bold text-gray-900 mb-4">Profil Fotoğrafı</h4>
                        <div class="flex flex-col md:flex-row items-start md:items-center gap-6">
                            <!-- Current Profile Image -->
                            <div class="flex-shrink-0">
                                <div class="relative w-32 h-32 rounded-full overflow-hidden border-4 border-gray-200 bg-gray-100">
                                    <img
                                        id="profileImagePreview"
                                        :src="(function(){
                                            var base = '<?php echo BASE_URL; ?>';
                                            var img = profileData.profile_image || '';
                                            var cb = '?t=' + Date.now();
                                            if (!img) return base + '/frontend/images/default-avatar.svg';
                                            if (img.startsWith('http://') || img.startsWith('https://')) return img + cb;
                                            if (img.startsWith(base)) return img + cb;
                                            return base + '/' + img.replace(/^\/+/, '') + cb;
                                        })()"
                                        alt="Profile"
                                        class="w-full h-full object-cover"
                                        @error="$event.target.src='<?php echo BASE_URL; ?>/frontend/images/default-avatar.svg'; $event.target.onerror=null;"
                                    >
                                </div>
                            </div>
                            
                            <!-- Upload Controls -->
                            <div class="flex-1">
                                <label for="profile_image" class="block text-sm font-semibold text-gray-700 mb-2">
                                    Yeni Fotoğraf Yükle
                                </label>
                                <input 
                                    type="file" 
                                    id="profileImageInput" 
                                    name="profile_image" 
                                    accept="image/jpeg,image/png,image/jpg,image/webp"
                                    class="block w-full text-sm text-gray-900 border-2 border-gray-300 rounded-lg cursor-pointer bg-gray-50 focus:outline-none focus:border-blue-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
                                >
                                <p class="mt-2 text-xs text-gray-500">JPG, PNG veya WEBP formatında. Maksimum 3MB.</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4">
                        <!-- Name -->
                        <div class="mb-4">
                            <label for="profile_name" class="block text-sm font-semibold text-gray-700 mb-2">
                                Ad Soyad <span class="text-red-500">*</span>
                            </label>
                            <input 
                                type="text"
                                id="profile_name"
                                name="name"
                                value="<?php echo htmlspecialchars($user_name); ?>"
                                required
                                autocomplete="name"
                                class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg text-gray-900 placeholder-gray-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 focus:outline-none transition-colors"
                                placeholder="Adınız Soyadınız"
                            >
                        </div>
                        
                        <!-- Username -->
                        <div class="mb-4">
                            <label for="profile_username" class="block text-sm font-semibold text-gray-700 mb-2">
                                Kullanıcı Adı <span class="text-red-500">*</span>
                            </label>
                            <input 
                                type="text"
                                id="profile_username"
                                name="username"
                                value="<?php echo htmlspecialchars($user_username); ?>"
                                required
                                autocomplete="username"
                                class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg text-gray-900 placeholder-gray-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 focus:outline-none transition-colors"
                                placeholder="kullanici_adi"
                            >
                        </div>

                        <!-- Email -->
                        <div class="mb-4">
                            <label for="profile_email" class="block text-sm font-semibold text-gray-700 mb-2">
                                E-posta <span class="text-red-500">*</span>
                            </label>
                            <input 
                                type="email"
                                id="profile_email"
                                name="email"
                                value="<?php echo htmlspecialchars($user_email); ?>"
                                required
                                autocomplete="email"
                                class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg text-gray-900 placeholder-gray-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 focus:outline-none transition-colors"
                                placeholder="ornek@email.com"
                            >
                        </div>
                        
                        <!-- Phone -->
                        <div class="mb-4">
                            <label for="profile_phone" class="block text-sm font-semibold text-gray-700 mb-2">Telefon</label>
                            <input 
                                type="tel"
                                id="profile_phone"
                                name="phone"
                                value="<?php echo htmlspecialchars($user_phone); ?>"
                                placeholder="+90 555 123 45 67"
                                autocomplete="tel"
                                class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg text-gray-900 placeholder-gray-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 focus:outline-none transition-colors"
                            >
                        </div>
                        
                        <!-- Home Phone (Required) -->
                        <div class="mb-4">
                            <label for="profile_home_phone" class="block text-sm font-semibold text-gray-700 mb-2">
                                Ev Telefonu <span class="text-red-500">*</span>
                            </label>
                            <input 
                                type="tel"
                                id="profile_home_phone"
                                name="home_phone"
                                value="<?php echo htmlspecialchars($user_home_phone); ?>"
                                required
                                placeholder="+90 212 345 67 89"
                                autocomplete="tel-local"
                                class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg text-gray-900 placeholder-gray-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 focus:outline-none transition-colors"
                            >
                        </div>
                        
                        <!-- National ID (Required) -->
                        <div class="mb-4">
                            <label for="profile_national_id" class="block text-sm font-semibold text-gray-700 mb-2">
                                T.C. Kimlik No <span class="text-red-500">*</span>
                            </label>
                            <input 
                                type="text"
                                id="profile_national_id"
                                name="national_id"
                                value="<?php echo htmlspecialchars($user_national_id); ?>"
                                required
                                maxlength="11"
                                pattern="[0-9]{11}"
                                placeholder="12345678901"
                                class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg text-gray-900 placeholder-gray-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 focus:outline-none transition-colors"
                            >
                            <p class="mt-1 text-xs text-gray-500">11 haneli T.C. Kimlik numaranızı girin</p>
                        </div>
                        
                        <!-- Driver License (Optional) -->
                        <div class="mb-4">
                            <label for="profile_driver_license" class="block text-sm font-semibold text-gray-700 mb-2">
                                Sürücü Belgesi No
                            </label>
                            <input 
                                type="text"
                                id="profile_driver_license"
                                name="driver_license"
                                value="<?php echo htmlspecialchars($user_driver_license); ?>"
                                placeholder="A1234567"
                                class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg text-gray-900 placeholder-gray-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 focus:outline-none transition-colors"
                            >
                            <p class="mt-1 text-xs text-gray-500">İsteğe bağlı alan</p>
                        </div>
                        
                        <!-- City -->
                        <div class="mb-4">
                            <label for="profile_city" class="block text-sm font-semibold text-gray-700 mb-2">Şehir</label>
                            <input 
                                type="text"
                                id="profile_city"
                                name="city"
                                value="<?php echo htmlspecialchars($user_city); ?>"
                                placeholder="İstanbul"
                                autocomplete="address-level2"
                                class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg text-gray-900 placeholder-gray-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 focus:outline-none transition-colors"
                            >
                        </div>
                        
                        <!-- Address -->
                        <div class="mb-4 md:col-span-2">
                            <label for="profile_address" class="block text-sm font-semibold text-gray-700 mb-2">Adres</label>
                            <textarea 
                                id="profile_address"
                                name="address"
                                rows="3"
                                placeholder="Tam adresiniz"
                                autocomplete="street-address"
                                class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg text-gray-900 placeholder-gray-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 focus:outline-none transition-colors resize-none"
                            ><?php echo htmlspecialchars($user_address); ?></textarea>
                        </div>
                    </div>
                    
                    <!-- Password Change Section -->
                    <div class="pt-6 border-t border-gray-200">
                        <h4 class="text-lg font-bold text-gray-900 mb-4">Şifre Değiştir</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-4">
                            <!-- Current Password -->
                            <div class="mb-4">
                                <label for="current_password" class="block text-sm font-semibold text-gray-700 mb-2">Mevcut Şifre</label>
                                <input 
                                    type="password"
                                    id="current_password"
                                    name="current_password"
                                    autocomplete="current-password"
                                    class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg text-gray-900 placeholder-gray-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 focus:outline-none transition-colors"
                                    placeholder="••••••••"
                                >
                            </div>
                            
                            <!-- New Password -->
                            <div class="mb-4">
                                <label for="new_password" class="block text-sm font-semibold text-gray-700 mb-2">Yeni Şifre</label>
                                <input 
                                    type="password"
                                    id="new_password"
                                    name="new_password"
                                    autocomplete="new-password"
                                    class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg text-gray-900 placeholder-gray-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 focus:outline-none transition-colors"
                                    placeholder="••••••••"
                                >
                            </div>
                            <!-- Confirm New Password -->
                            <div class="mb-4">
                                <label for="confirm_password" class="block text-sm font-semibold text-gray-700 mb-2">Yeni Şifre (Tekrar)</label>
                                <input 
                                    type="password"
                                    id="confirm_password"
                                    name="confirm_password"
                                    autocomplete="new-password"
                                    class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg text-gray-900 placeholder-gray-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 focus:outline-none transition-colors"
                                    placeholder="••••••••"
                                >
                            </div>
                        </div>
                    </div>
                    
                    <!-- Success/Error Messages -->
                    <div class="hidden mb-4 p-4 border-2 border-green-500 bg-green-50 text-green-700 rounded-lg" id="profile-success">
                        <div class="flex items-center gap-2">
                            <i class="fas fa-check-circle"></i>
                            <span>Profil başarıyla güncellendi!</span>
                        </div>
                    </div>
                    
                    <div class="hidden mb-4 p-4 border-2 border-red-500 bg-red-50 text-red-600 rounded-lg" id="profile-error">
                        <div class="flex items-center gap-2">
                            <i class="fas fa-exclamation-circle"></i>
                            <span>Bir hata oluştu. Lütfen tekrar deneyin.</span>
                        </div>
                    </div>

                    <!-- Global message component (reusable) -->
                    <div id="global-message" class="fixed top-6 right-6 z-50 hidden max-w-md w-full pointer-events-auto">
                        <div id="global-message-box" class="rounded-lg shadow-lg p-4 flex items-start gap-3">
                            <div id="global-message-icon" class="mt-0.5"></div>
                            <div class="flex-1">
                                <div id="global-message-text" class="font-medium"></div>
                                <div id="global-message-sub" class="text-sm mt-1 opacity-80"></div>
                            </div>
                            <button id="global-message-close" aria-label="Close message" class="ml-3 text-gray-600 hover:text-gray-900">&times;</button>
                        </div>
                    </div>

                    <!-- Form-level validation errors (populated by client-side or server responses) -->
                    <div id="form-errors" class="hidden mb-4 p-4 bg-red-50 border border-red-200 text-red-700 rounded-lg">
                        <strong class="block font-semibold mb-2">Lütfen aşağıdaki hataları düzeltin:</strong>
                        <ul id="form-errors-list" class="list-disc pl-5 space-y-1"></ul>
                    </div>
                    
                    <!-- Form Actions -->
                    <div class="flex flex-col-reverse sm:flex-row justify-end gap-3 pt-6 border-t border-gray-200">
                        <button 
                            type="button"
                            @click="toggleEdit()"
                            class="w-full sm:w-auto h-11 px-6 border-2 border-gray-300 text-gray-700 rounded-xl font-semibold hover:bg-gray-50 active:bg-gray-100 transition-colors"
                        >
                            İptal
                        </button>
                        <button 
                            type="submit"
                            class="w-full sm:w-auto h-11 px-6 bg-gradient-to-r from-blue-600 to-purple-600 text-white rounded-xl font-semibold hover:shadow-lg active:shadow-md transition-all inline-flex items-center justify-center gap-2"
                        >
                            <i class="fas fa-save text-sm"></i>
                            <span>Kaydet</span>
                        </button>
                    </div>
                </form>
            </div>
        </section>
        
        <!-- ========== SUPPORT SECTION ========== -->
        <section x-show="currentSection === 'support'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 transform translate-y-4" x-transition:enter-end="opacity-100 transform translate-y-0" class="space-y-6 pt-6 lg:pt-8" style="display: none;">
            <div class="mb-8">
                <h2 class="text-2xl md:text-3xl font-bold text-gray-900 mb-2">Destek</h2>
                <p class="text-gray-600">Yardıma mı ihtiyacınız var? Bize ulaşın</p>
            </div>
            
            <div class="bg-white rounded-2xl shadow-md border border-gray-100 p-6 md:p-8">
                <form class="space-y-6">
                    <!-- Subject -->
                    <div class="mb-4">
                        <label for="support_subject" class="block text-sm font-semibold text-gray-700 mb-2">
                            Konu <span class="text-red-500">*</span>
                        </label>
                        <input 
                            type="text"
                            id="support_subject"
                            name="subject"
                            required
                            placeholder="Sorununuzun kısa açıklaması"
                            class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg text-gray-900 placeholder-gray-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 focus:outline-none transition-colors"
                        >
                    </div>
                    
                    <!-- Category -->
                    <div class="mb-4">
                        <label for="support_category" class="block text-sm font-semibold text-gray-700 mb-2">
                            Kategori <span class="text-red-500">*</span>
                        </label>
                        <select 
                            id="support_category"
                            name="category"
                            required
                            class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg text-gray-900 bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-200 focus:outline-none transition-colors"
                        >
                            <option value="">Kategori seçin</option>
                            <option value="reservation">Rezervasyon</option>
                            <option value="payment">Ödeme</option>
                            <option value="vehicle">Araç Bilgileri</option>
                            <option value="account">Hesap Ayarları</option>
                            <option value="other">Diğer</option>
                        </select>
                    </div>
                    
                    <!-- Message -->
                    <div class="mb-4">
                        <label for="support_message" class="block text-sm font-semibold text-gray-700 mb-2">
                            Mesaj <span class="text-red-500">*</span>
                        </label>
                        <textarea 
                            id="support_message"
                            name="message"
                            rows="6"
                            required
                            placeholder="Sorununuzu detaylı olarak açıklayın"
                            class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg text-gray-900 placeholder-gray-400 focus:border-blue-500 focus:ring-2 focus:ring-blue-200 focus:outline-none transition-colors resize-none"
                        ></textarea>
                        <p class="mt-2 text-xs text-gray-500">Minimum 20 karakter</p>
                    </div>
                    
                    <!-- Success/Error Messages -->
                    <div class="hidden mb-4 p-4 border-2 border-green-500 bg-green-50 text-green-700 rounded-lg" id="support-success">
                        <div class="flex items-center gap-2">
                            <i class="fas fa-check-circle"></i>
                            <span>Mesajınız başarıyla gönderildi!</span>
                        </div>
                    </div>
                    
                    <div class="hidden mb-4 p-4 border-2 border-red-500 bg-red-50 text-red-600 rounded-lg" id="support-error">
                        <div class="flex items-center gap-2">
                            <i class="fas fa-exclamation-circle"></i>
                            <span>Mesaj gönderilemedi. Lütfen tüm alanları doldurun.</span>
                        </div>
                    </div>
                    
                    <!-- Submit Button -->
                    <div class="flex justify-end">
                        <button 
                            type="submit"
                            class="w-full sm:w-auto h-11 px-6 bg-gradient-to-r from-blue-600 to-purple-600 text-white rounded-xl font-semibold hover:shadow-lg active:shadow-md transition-all inline-flex items-center justify-center gap-2"
                        >
                            <i class="fas fa-paper-plane text-sm"></i>
                            <span>Gönder</span>
                        </button>
                    </div>
                </form>
            </div>
        </section>
        
        <!-- ========== SETTINGS SECTION ========== -->
        <section x-show="currentSection === 'settings'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 transform translate-y-4" x-transition:enter-end="opacity-100 transform translate-y-0" class="space-y-6 pt-6 lg:pt-8" style="display: none;">
            <div class="mb-8">
                <h2 class="text-2xl md:text-3xl font-bold text-gray-900 mb-2">Ayarlar</h2>
                <p class="text-gray-600">Hesap ayarlarınızı yönetin</p>
            </div>
            
            <div class="bg-white rounded-2xl shadow-md border border-gray-100 p-6 md:p-8">
                <h3 class="text-xl font-bold text-gray-900 mb-6">Bildirim Tercihleri</h3>
                <div class="flex flex-col gap-4">
                    <label class="flex items-center justify-between p-4 border rounded-lg cursor-pointer hover:bg-gray-50">
                        <div>
                            <h4 class="font-bold">E-posta Bildirimleri</h4>
                            <p class="text-sm text-gray-600">Rezervasyon onayları ve güncellemeler</p>
                        </div>
                        <input type="checkbox" checked class="w-6 h-6 text-blue-600 rounded focus:ring-blue-500" id="auto_171" aria-label="E-posta Bildirimleri">
                    </label>

                    <label class="flex items-center justify-between p-4 border rounded-lg cursor-pointer hover:bg-gray-50">
                        <div>
                            <h4 class="font-bold">SMS Bildirimleri</h4>
                            <p class="text-sm text-gray-600">Acil durumlar için SMS</p>
                        </div>
                        <input type="checkbox" class="w-6 h-6 text-blue-600 rounded focus:ring-blue-500" id="auto_172" aria-label="SMS Bildirimleri">
                    </label>

                    <label class="flex items-center justify-between p-4 border rounded-lg cursor-pointer hover:bg-gray-50">
                        <div>
                            <h4 class="font-bold">Promosyon Bildirimleri</h4>
                            <p class="text-sm text-gray-600">İndirim ve kampanya duyuruları</p>
                        </div>
                        <input type="checkbox" checked class="w-6 h-6 text-blue-600 rounded focus:ring-blue-500" id="auto_173" aria-label="Promosyon Bildirimleri">
                    </label>
                </div>

                <div class="mt-8 pt-6 border-t">
                    <h3 class="text-xl font-bold mb-6">Güvenlik</h3>
                    <div class="space-y-4">
                        <button class="w-full text-left p-4 border rounded-lg hover:bg-gray-50 transition-colors">
                            <h4 class="font-bold">Şifre Değiştir</h4>
                            <p class="text-sm text-gray-600">Hesap güvenliğiniz için şifrenizi güncelleyin</p>
                        </button>

                        <button class="w-full text-left p-4 border rounded-lg hover:bg-gray-50 transition-colors">
                            <h4 class="font-bold">İki Faktörlü Doğrulama</h4>
                            <p class="text-sm text-gray-600">Ek güvenlik katmanı ekleyin</p>
                        </button>
                    </div>
                </div>
            </div>
        </section>
        
                <!-- ========== CARWASH SELECTION SECTION (Extracted from customer_profile.html) ========== -->
                <section x-show="currentSection === 'carWashSelection'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 transform translate-y-4" x-transition:enter-end="opacity-100 transform translate-y-0" class="space-y-6 pt-6 lg:pt-8" style="display: none;">
                        <div class="mb-8">
                                <h2 class="text-3xl font-bold text-gray-800 mb-2">Oto Yıkama Seçimi</h2>
                                <p class="text-gray-600">Size en uygun oto yıkama merkezini bulun ve rezervasyon yapın.</p>
                        </div>

                        <div class="bg-white rounded-2xl shadow-lg p-6 mb-8">
                            <h3 class="text-xl font-bold text-gray-800 mb-4">Filtreleme Seçenekleri</h3>
                            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                                <div>
                                    <label for="cityFilter" class="block text-sm font-bold text-gray-700 mb-2">Şehir</label>
                                    <?php
                                        // Fetch carwashes from DB for dynamic city/district lists and client-side filtering
                                        // Use canonical `carwashes` table and alias columns to the keys the frontend expects
                                        $carwashes = [];
                                        $carwash_error = null;
                                        try {
                                            // Runtime-detect which table exists and prefer a table that contains rows
                                            $pdo = $db->getPdo();
                                            $tblExistsStmt = $pdo->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = :tbl");

                                            // Use canonical `carwashes` table only for customer selection UI
                                            $carwashes = [];
                                            try {
                                                $pdo = $db->getPdo();
                                                $tblExistsStmt->execute(['tbl' => 'carwashes']);
                                                if ((int)$tblExistsStmt->fetchColumn() > 0) {
                                                    // Use a full-row select so new/changed columns are automatically included
                                                    $sql = "SELECT * FROM carwashes ORDER BY name";
                                                    $carwashes = $db->fetchAll($sql);
                                                } else {
                                                    // No canonical table found — surface clear message to JavaScript/UI
                                                    throw new Exception('No `carwashes` table found in database');
                                                }
                                            } catch (Exception $e) {
                                                $carwash_error = $e->getMessage();
                                                $carwashes = [];
                                            }

                                            // Normalize rows to ensure consistent keys for the frontend
                                            foreach ($carwashes as &$cw) {
                                                // name
                                                if (empty($cw['name']) && !empty($cw['business_name'])) $cw['name'] = $cw['business_name'];

                                                // phone: prefer phone, then mobile_phone, then social_media
                                                if (empty($cw['phone'])) {
                                                    if (!empty($cw['mobile_phone'])) $cw['phone'] = $cw['mobile_phone'];
                                                    elseif (!empty($cw['contact_phone'])) $cw['phone'] = $cw['contact_phone'] ?? '';
                                                    else {
                                                        // try social_media JSON
                                                        if (!empty($cw['social_media'])) {
                                                            $sm = json_decode($cw['social_media'], true);
                                                            if (is_array($sm)) {
                                                                foreach (['mobile_phone','mobile','phone','telephone','tel'] as $k) {
                                                                    if (!empty($sm[$k])) { $cw['phone'] = $sm[$k]; break; }
                                                                }
                                                                if (empty($cw['phone']) && isset($sm['whatsapp'])) {
                                                                    if (is_array($sm['whatsapp'])) $cw['phone'] = $sm['whatsapp']['number'] ?? $sm['whatsapp']['phone'] ?? $cw['phone'];
                                                                    elseif (is_string($sm['whatsapp'])) $cw['phone'] = $sm['whatsapp'];
                                                                }
                                                            }
                                                        }
                                                    }
                                                }

                                                // logo path normalization
                                                if (empty($cw['logo_path'])) {
                                                    if (!empty($cw['featured_image'])) $cw['logo_path'] = $cw['featured_image'];
                                                }

                                                // working_hours normalization
                                                if (!empty($cw['working_hours']) && is_string($cw['working_hours'])) {
                                                    $decoded = json_decode($cw['working_hours'], true);
                                                    $cw['working_hours'] = $decoded === null ? $cw['working_hours'] : $decoded;
                                                }

                                                // Provide defaults for missing keys used by frontend
                                                $cw['city'] = $cw['city'] ?? '';
                                                $cw['district'] = $cw['district'] ?? '';
                                                $cw['address'] = $cw['address'] ?? '';
                                                $cw['status'] = $cw['status'] ?? '';
                                                $cw['rating'] = isset($cw['rating']) ? (float)$cw['rating'] : 4.6;
                                                $cw['services'] = isset($cw['services']) ? (is_string($cw['services']) ? (json_decode($cw['services'], true) ?: []) : ($cw['services'] ?: [])) : [];
                                                
                                                // Add favorite status for this user
                                                $cw['isFavorite'] = false;
                                                try {
                                                    $profile = $db->fetchOne("SELECT preferences FROM user_profiles WHERE user_id = ?", [$user_id]);
                                                    if ($profile && !empty($profile['preferences'])) {
                                                        $data = json_decode($profile['preferences'], true);
                                                        $favorites = $data['favorites'] ?? [];
                                                        $cw['isFavorite'] = in_array($cw['id'], $favorites);
                                                    }
                                                } catch (Exception $e) {
                                                    // Ignore errors, default to not favorite
                                                }
                                            }
                                            unset($cw);
                                        } catch (Exception $e) {
                                            // Keep $carwashes empty and record error for JS display
                                            $carwash_error = $e->getMessage();
                                            $carwashes = [];
                                        }

                                        // Collect unique cities for the city filter
                                        $cities = [];
                                        $districtsByCity = [];
                                        foreach ($carwashes as $cw) {
                                            $city = isset($cw['city']) ? trim($cw['city']) : null;
                                            $district = isset($cw['district']) ? trim($cw['district']) : null;
                                            if ($city !== null && $city !== '') {
                                                $cities[$city] = true;
                                                if (!isset($districtsByCity[$city])) $districtsByCity[$city] = [];
                                                if ($district !== null && $district !== '' && !in_array($district, $districtsByCity[$city], true)) {
                                                    $districtsByCity[$city][] = $district;
                                                }
                                            }
                                        }

                                        // Sort city names for consistent UI
                                        $cities = array_keys($cities);
                                        sort($cities, SORT_STRING | SORT_FLAG_CASE);
                                    ?>
                                    <select id="cityFilter" onchange="filterCarWashes()" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                                        <option value="">Tüm Şehirler</option>
                                        <?php foreach ($cities as $c): ?>
                                            <option><?php echo htmlspecialchars($c, ENT_QUOTES, 'UTF-8'); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div>
                                    <label for="districtFilter" class="block text-sm font-bold text-gray-700 mb-2">Mahalle</label>
                                    <select id="districtFilter" onchange="filterCarWashes()" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                                        <option value="">Tüm Mahalleler</option>
                                    </select>
                                </div>
                                <div>
                                    <label for="carWashNameFilter" class="block text-sm font-bold text-gray-700 mb-2">CarWash Adı</label>
                                    <input type="text" id="carWashNameFilter" onkeyup="filterCarWashes()" placeholder="CarWash adı girin..." class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                                </div>
                                <div class="flex items-end">
                                    <label for="favoriteFilter" class="flex items-center cursor-pointer">
                                        <input id="favoriteFilter" type="checkbox" onchange="filterCarWashes()" class="mr-2">
                                        Sadece Favoriler
                                    </label>
                                </div>
                            </div>
                        </div>

                            <div id="carWashList" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                                <?php if (empty($carwashes)): ?>
                                    <div class="col-span-full text-center text-gray-600 py-8">No carwashes available.</div>
                                <?php else: ?>
                                    <!-- Initial server-rendered cards (client-side filtering will replace content when active) -->
                                    <?php foreach ($carwashes as $cw): ?>
                                        <?php
                                            $cw_id = htmlspecialchars($cw['id'] ?? '', ENT_QUOTES, 'UTF-8');
                                            $cw_name = htmlspecialchars($cw['name'] ?? 'Unnamed', ENT_QUOTES, 'UTF-8');
                                            $cw_address = htmlspecialchars($cw['address'] ?? '', ENT_QUOTES, 'UTF-8');
                                            $cw_phone = htmlspecialchars($cw['phone'] ?? '', ENT_QUOTES, 'UTF-8');
                                            $cw_city = htmlspecialchars($cw['city'] ?? '', ENT_QUOTES, 'UTF-8');
                                            $cw_district = htmlspecialchars($cw['district'] ?? '', ENT_QUOTES, 'UTF-8');
                                            $cw_status = htmlspecialchars($cw['status'] ?? '', ENT_QUOTES, 'UTF-8');
                                            
                                            // Check if this carwash is favorited by the user
                                            $is_favorite = false;
                                            try {
                                                $profile = $db->fetchOne("SELECT preferences FROM user_profiles WHERE user_id = ?", [$user_id]);
                                                if ($profile && !empty($profile['preferences'])) {
                                                    $data = json_decode($profile['preferences'], true);
                                                    $favorites = $data['favorites'] ?? [];
                                                    $is_favorite = in_array($cw['id'], $favorites);
                                                }
                                            } catch (Exception $e) {
                                                // Ignore errors, default to not favorite
                                            }
                                        ?>
                                        <div data-id="<?php echo $cw_id; ?>" data-name="<?php echo $cw_name; ?>" class="bg-white rounded-2xl p-6 card-hover shadow-lg flex flex-col">
                                            <div class="flex justify-between items-start mb-4">
                                                <div class="flex items-center gap-3">
                                                    <?php
                                                        $logo_path = $cw['logo_path'] ?? '';
                                                        $logo_url = '';
                                                        if (!empty($logo_path)) {
                                                            $logo_file_path = __DIR__ . '/../../backend/uploads/business_logo/' . basename($logo_path);
                                                            if (file_exists($logo_file_path)) {
                                                                $logo_url = $base_url . '/backend/uploads/business_logo/' . basename($logo_path);
                                                            }
                                                        }
                                                        if (empty($logo_url)) {
                                                            $logo_url = $base_url . '/frontend/assets/img/default-user.png';
                                                        }
                                                    ?>
                                                    <img src="<?php echo htmlspecialchars($logo_url); ?>" alt="<?php echo $cw_name; ?> Logo" class="w-12 h-12 rounded-lg object-cover">
                                                    <div>
                                                        <h4 class="font-bold text-lg"><?php echo $cw_name; ?></h4>
                                                    </div>
                                                </div>
                                                <div class="flex items-center gap-2">
                                                    <button class="favorite-toggle text-gray-400 hover:text-red-500 transition-colors" data-carwash-id="<?php echo $cw_id; ?>" title="Favorilere ekle">
                                                        <i class="<?php echo $is_favorite ? 'fas fa-heart text-red-500' : 'far fa-heart text-gray-400'; ?> text-xl"></i>
                                                    </button>
                                                    <div class="text-right">
                                                        <p class="text-sm text-gray-500"><?php echo $cw_status; ?></p>
                                                    </div>
                                                </div>
                                            </div>
                                            <?php if (!empty($cw_address)): ?><p class="text-sm text-gray-600 mb-2"><i class="fas fa-map-marker-alt mr-2"></i><?php echo $cw_address; ?></p><?php endif; ?>
                                            <?php if (!empty($cw_phone)): ?><p class="text-sm text-gray-600 mb-4"><i class="fas fa-phone mr-2"></i><?php echo $cw_phone; ?></p><?php endif; ?>
                                            <div class="mt-auto">
                                                <button data-id="<?php echo $cw_id; ?>" data-name="<?php echo $cw_name; ?>" class="gradient-bg text-white px-4 py-2 rounded-lg hover:shadow-lg select-for-reservation">Rezervasyon Yap</button>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                                </div>

                            <!-- Inline holder for reservation form when opened from carwash cards -->
                            <div id="carWashInlineFormHolder" class="hidden p-6 bg-white rounded-2xl shadow-lg mb-8"></div>

                            <script>
                        (function(){
                            'use strict';

                            // Provide initial data to client-side filtering from server
                            const allCarWashes = <?php echo json_encode(array_values($carwashes), JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT); ?> || [];
                            const districtsByCity = <?php echo json_encode($districtsByCity, JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT); ?> || {};
                            const carwashLoadError = <?php echo json_encode($carwash_error, JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT); ?> || null;

                            // Log how many records were loaded from server for quick debugging
                            try { console.log('Carwash records available:', Array.isArray(allCarWashes) ? allCarWashes.length : 0); } catch(e){}

                            if (carwashLoadError) {
                                console.error('Carwashes load error:', carwashLoadError);
                                const list = document.getElementById('carWashList');
                                if (list) list.innerHTML = '<div class="col-span-full text-center text-red-600 py-8">Hizmetler alınırken bir hata oluştu.</div>';
                            }

                            // Helper to safely find elements
                            function $id(id){ return document.getElementById(id); }

                            function loadDistrictOptions(){
                                const cityFilter = $id('cityFilter');
                                const districtFilter = $id('districtFilter');
                                const selectedCity = cityFilter.value;

                                districtFilter.innerHTML = '<option value="">Tüm Mahalleler</option>';
                                if (selectedCity && districtsByCity[selectedCity]){
                                    districtsByCity[selectedCity].forEach(d => {
                                        const opt = document.createElement('option'); opt.value = d; opt.textContent = d; districtFilter.appendChild(opt);
                                    });
                                }
                            }

                            function filterCarWashes(){
                                const cityFilter = ($id('cityFilter')?.value || '').toLowerCase();
                                const districtFilter = ($id('districtFilter')?.value || '').toLowerCase();
                                const carWashNameFilter = ($id('carWashNameFilter')?.value || '').toLowerCase();
                                const favoriteFilter = $id('favoriteFilter')?.checked;
                                const carWashListDiv = $id('carWashList');
                                if (!carWashListDiv) return;
                                carWashListDiv.innerHTML = '';

                                const filteredWashes = allCarWashes.filter(carWash => {
                                    const matchesCity = !cityFilter || carWash.city.toLowerCase().includes(cityFilter);
                                    const matchesDistrict = !districtFilter || carWash.district.toLowerCase().includes(districtFilter);
                                    const matchesName = !carWashNameFilter || carWash.name.toLowerCase().includes(carWashNameFilter);
                                    const matchesFavorite = !favoriteFilter || carWash.isFavorite;
                                    return matchesCity && matchesDistrict && matchesName && matchesFavorite;
                                });

                                if (filteredWashes.length === 0) {
                                    carWashListDiv.innerHTML = '<p class="text-gray-600 text-center col-span-full">Seçiminize uygun oto yıkama bulunamadı.</p>';
                                    return;
                                }

                                console.log('Carwash records available:', filteredWashes.length);
                                filteredWashes.forEach(carWash => {
                                    const div = document.createElement('div');
                                    div.className = 'bg-white rounded-2xl p-6 card-hover shadow-lg flex flex-col';
                                    // store id/name attributes to allow the whole card to be clickable
                                    div.setAttribute('data-id', carWash.id || '');
                                    div.setAttribute('data-name', carWash.name || '');
                                    
                                    // Build logo URL
                                    let logoUrl = '/carwash_project/frontend/assets/img/default-user.png';
                                    if (carWash.logo_path) {
                                        const testPath = '/carwash_project/backend/uploads/business_logo/' + carWash.logo_path.split('/').pop();
                                        // For client-side, we'll assume the logo exists if path is provided
                                        logoUrl = testPath;
                                    }
                                    
                                    div.innerHTML = `
                                        <div class="flex justify-between items-start mb-4">
                                            <div class="flex items-center gap-3">
                                                <img src="${logoUrl}" alt="${escapeHtml(carWash.name)} Logo" class="w-12 h-12 rounded-lg object-cover">
                                                <div>
                                                    <h4 class="font-bold text-lg">${escapeHtml(carWash.name)}</h4>
                                                </div>
                                            </div>
                                            <div class="flex items-center gap-2">
                                                <button class="favorite-toggle text-gray-400 hover:text-red-500 transition-colors" data-carwash-id="${carWash.id || ''}" title="Favorilere ekle">
                                                    <i class="${carWash.isFavorite ? 'fas fa-heart text-red-500' : 'far fa-heart text-gray-400'} text-xl"></i>
                                                </button>
                                                <div class="text-right">
                                                    <p class="text-yellow-400 font-semibold">${carWash.rating}</p>
                                                </div>
                                            </div>
                                        </div>
                                        <p class="text-sm text-gray-600 mb-2"><i class="fas fa-map-marker-alt mr-2"></i>${escapeHtml(carWash.district)}, ${escapeHtml(carWash.city)}</p>
                                        <p class="text-sm text-gray-600 mb-4"><i class="fas fa-star text-yellow-400 mr-2"></i>${carWash.rating} (${Math.floor(Math.random()*100)} yorum)</p>
                                        <div class="flex flex-wrap gap-2 mb-4">
                                            ${ (carWash.services || []).map(s=>`<span class="px-2 py-1 text-xs bg-gray-100 rounded">${escapeHtml(s)}</span>`).join('') }
                                        </div>
                                        <button data-id="${carWash.id || ''}" data-name="${escapeAttr(carWash.name)}" class="mt-auto gradient-bg text-white px-4 py-2 rounded-lg hover:shadow-lg select-for-reservation">Rezervasyon Yap</button>
                                    `;
                                    carWashListDiv.appendChild(div);

                                    // Make the whole card clickable (except when clicking controls like links or buttons)
                                    div.addEventListener('click', function(evt){
                                        const tag = (evt.target && evt.target.tagName) ? evt.target.tagName.toLowerCase() : '';
                                        if (tag === 'a' || tag === 'button' || evt.target.closest && evt.target.closest('button, a')) return;
                                        const id = this.getAttribute('data-id') || '';
                                        const name = this.getAttribute('data-name') || '';
                                        if (id) selectCarWashForReservation(name, id);
                                    });
                                });

                                // Make filterCarWashes globally available
                                window.filterCarWashes = filterCarWashes;

                                // Attach reservation handlers
                                document.querySelectorAll('.select-for-reservation').forEach(btn => {
                                    btn.removeEventListener('click', btn._selHandler);
                                    btn._selHandler = function(){
                                        const name = this.getAttribute('data-name') || '';
                                        const id = this.getAttribute('data-id') || '';
                                        selectCarWashForReservation(name, id);
                                    };
                                    btn.addEventListener('click', btn._selHandler);
                                });
                            }

                            function selectCarWashForReservation(carWashName, carWashId){
                                // Show the reservation form inside the carWashSelection section (no section switch)
                                try {
                                    const holder = document.getElementById('carWashInlineFormHolder');
                                    const origFormWrapper = document.getElementById('newReservationForm'); // wrapper DIV for the form
                                    if (holder && origFormWrapper) {
                                        // remember original parent id so we can move it back later
                                        if (!origFormWrapper.dataset.originalParentId) {
                                            // Use a stable restore point inside the reservations section
                                            origFormWrapper.dataset.originalParentId = 'reservationFormRestorePoint';
                                        }

                                        // move into holder
                                        holder.appendChild(origFormWrapper);
                                        holder.classList.remove('hidden');
                                        // hide the grid to focus on form
                                        document.getElementById('carWashList')?.classList.add('hidden');
                                        origFormWrapper.classList.remove('hidden');
                                    }
                                } catch (e) {
                                    console.warn('Could not relocate reservation form into carwash section', e);
                                }

                                // Populate the location field inside the moved form
                                const loc = $id('location');
                                if (loc) {
                                    // Use option value = id (preferred) and text = name
                                    let opt = Array.from(loc.options).find(o => o.value === String(carWashId) || o.textContent === carWashName);
                                    if (!opt) {
                                        opt = document.createElement('option');
                                        opt.value = carWashId || carWashName;
                                        opt.textContent = carWashName || carWashId;
                                        loc.appendChild(opt);
                                    }
                                    // select by id if available, otherwise by name
                                    loc.value = carWashId || carWashName;
                                    // Trigger loading services for this carwash (ensure programmatic selection also loads services)
                                    (function waitAndCallFetch(id, attempts){
                                        attempts = attempts || 0;
                                        try {
                                            if (typeof fetchServicesForCarwash === 'function') {
                                                fetchServicesForCarwash(id);
                                                return;
                                            }
                                        } catch (e) {
                                            // ignore and retry below
                                        }
                                        if (attempts < 5) {
                                            // wait a short time for the function to become available
                                            setTimeout(function(){ waitAndCallFetch(id, attempts + 1); }, 100 * (attempts + 1));
                                        } else {
                                            console.warn('Failed to trigger fetchServicesForCarwash after selecting carwash - function not available');
                                        }
                                    })(loc.value, 0);
                                }

                                // Set hidden id field if present
                                const locId = $id('location_id');
                                if (locId && carWashId) locId.value = carWashId;
                            }

                            function escapeHtml(s){ if(!s) return ''; return String(s).replace(/[&<>\"']/g, function(c){ return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[c]; }); }

                                // -----------------------------
                                // Bookings (Reservations) Management
                                // -----------------------------
                                function getCsrfToken() {
                                    return (window.CONFIG && window.CONFIG.CSRF_TOKEN) || (document.querySelector('meta[name="csrf-token"]') && document.querySelector('meta[name="csrf-token"]').getAttribute('content')) || '';
                                }

                                function statusLabel(status) {
                                    if (!status) return '<span class="px-2 py-1 rounded-full text-xs bg-gray-200 text-gray-700">Bilinmiyor</span>';
                                    const s = status.toLowerCase();
                                    if (s === 'confirmed' || s === 'paid') return '<span class="px-2 py-1 rounded-full text-xs bg-green-100 text-green-800">Onaylandı</span>';
                                    if (s === 'pending' || s === 'processing') return '<span class="px-2 py-1 rounded-full text-xs bg-yellow-100 text-yellow-800">Beklemede</span>';
                                    if (s === 'cancelled' || s === 'cancel') return '<span class="px-2 py-1 rounded-full text-xs bg-red-100 text-red-800">İptal Edildi</span>';
                                    return '<span class="px-2 py-1 rounded-full text-xs bg-gray-100 text-gray-800">'+escapeHtml(status)+'</span>';
                                }

                                async function loadBookings() {
                                    const tbody = document.getElementById('reservationsTableBody');
                                    if (!tbody) return;
                                    // show loading
                                    tbody.innerHTML = '<tr><td colspan="9" class="px-6 py-8 text-center text-sm text-gray-500">Yükleniyor...</td></tr>';
                                    try {
                                        const resp = await fetch('/carwash_project/backend/api/bookings/list.php', {
                                            credentials: 'same-origin',
                                            headers: { 'Accept': 'application/json' }
                                        });

                                        // Read response as text first so we can handle empty/non-JSON responses gracefully
                                        const text = await resp.text();

                                        if (!resp.ok) {
                                            console.error('Bookings API responded with status', resp.status, text);
                                            tbody.innerHTML = '<tr><td colspan="9" class="px-6 py-8 text-center text-sm text-red-500">Rezervasyonlar yüklenemedi.</td></tr>';
                                            return;
                                        }

                                        if (!text || text.trim() === '') {
                                            // No content — treat as empty bookings list
                                            tbody.innerHTML = '<tr><td colspan="9" class="px-6 py-8 text-center text-sm text-gray-500">Aktif rezervasyonunuz yok.</td></tr>';
                                            return;
                                        }

                                        let result;
                                        try {
                                            result = JSON.parse(text);
                                        } catch (parseErr) {
                                            console.error('Failed to parse bookings JSON:', parseErr, text);
                                            tbody.innerHTML = '<tr><td colspan="9" class="px-6 py-8 text-center text-sm text-red-500">Rezervasyon verisi alınamadı.</td></tr>';
                                            return;
                                        }

                                        if (!result || !result.success) {
                                            tbody.innerHTML = '<tr><td colspan="9" class="px-6 py-8 text-center text-sm text-red-500">Rezervasyonlar yüklenemedi.</td></tr>';
                                            return;
                                        }
                                        // Normalize rows: bookings/list.php may merge rows into top-level response
                                        let rows = [];
                                        if (Array.isArray(result.data)) rows = result.data;
                                        else {
                                            for (const k in result) {
                                                if (k === 'success' || k === 'message') continue;
                                                // numeric keys contain rows
                                                if (!isNaN(k)) rows.push(result[k]);
                                            }
                                        }

                                        if (!rows || rows.length === 0) {
                                            tbody.innerHTML = '<tr><td colspan="9" class="px-6 py-8 text-center text-sm text-gray-500">Aktif rezervasyonunuz yok.</td></tr>';
                                            return;
                                        }

                                        // Build rows HTML
                                        const html = rows.map(r => {
                                            const id = r.id || r.booking_id || '';
                                            const carwash = r.carwash_name || r.location || r.customer_name || '';
                                            const plate = r.plate_number || r.vehicle_plate || '';
                                            const service = r.service_name || r.service_type || r.service || '';
                                            const duration = r.duration || 0;
                                            const price = r.price || r.total_price || 0;
                                            const date = r.booking_date || r.date || '';
                                            const time = r.booking_time || r.time || '';
                                            const status = r.status || '';
                                            // Store useful attributes for edit
                                            const dataAttrs = 'data-booking="'+encodeURIComponent(JSON.stringify({id:id,carwash_id:r.carwash_id||r.location_id,service_id:r.service_id||null,date:date,time:time,notes:r.notes||''}))+'"';
                                            return '<tr '+dataAttrs+' class="hover:bg-gray-50">'
                                                +'<td class="px-6 py-4 text-sm font-medium text-gray-900">'+escapeHtml(id)+'</td>'
                                                +'<td class="px-6 py-4 text-sm text-gray-900">'+escapeHtml(carwash)+'</td>'
                                                +'<td class="px-6 py-4 text-sm text-gray-900">'+escapeHtml(plate)+'</td>'
                                                +'<td class="px-6 py-4 text-sm text-gray-900">'+escapeHtml(service)+'</td>'
                                                +'<td class="px-6 py-4 text-sm text-gray-900">'+(duration ? duration + ' dk' : '')+'</td>'
                                                +'<td class="px-6 py-4 text-sm font-medium text-gray-900">'+(price ? ('₺'+parseFloat(price).toFixed(2)) : '')+'</td>'
                                                +'<td class="px-6 py-4 text-sm text-gray-900">'+escapeHtml(date)+'</td>'
                                                +'<td class="px-6 py-4 text-sm text-gray-900">'+escapeHtml(time)+'</td>'
                                                +'<td class="px-6 py-4">'+statusLabel(status)+'</td>'
                                            +'</tr>';
                                        }).join('');

                                        tbody.innerHTML = html;

                                    } catch (err) {
                                        console.error('Load bookings error', err);
                                        tbody.innerHTML = '<tr><td colspan="9" class="px-6 py-8 text-center text-sm text-red-500">Sunucu hatası.</td></tr>';
                                    }
                                }

                                // Expose bookings helpers to global scope so other script blocks can call them
                                // (these functions are defined inside this IIFE but needed elsewhere on the page)
                                try {
                                    window.loadBookings = loadBookings;
                                    window.showEditBookingModalFromRow = showEditBookingModalFromRow;
                                    window.hideEditBookingModal = hideEditBookingModal;
                                    window.submitEditBooking = submitEditBooking;
                                    window.cancelBookingById = cancelBookingById;
                                } catch (e) {
                                    // ignore if assignment fails in restricted environments
                                }

                                function showEditBookingModalFromRow(tr) {
                                    try {
                                        const d = tr.getAttribute('data-booking');
                                        if (!d) return;
                                        const obj = JSON.parse(decodeURIComponent(d));
                                        document.getElementById('edit_booking_id').value = obj.id || '';
                                        document.getElementById('edit_carwash_id').value = obj.carwash_id || '';
                                        document.getElementById('edit_service_id').value = obj.service_id || '';
                                        document.getElementById('edit_date').value = obj.date || '';
                                        document.getElementById('edit_time').value = obj.time || '';
                                        document.getElementById('edit_notes').value = obj.notes || '';
                                        const modal = document.getElementById('editBookingModal');
                                        modal.classList.remove('hidden');
                                        modal.classList.add('flex','items-center','justify-center');
                                    } catch (e) { console.error(e); }
                                }

                                function hideEditBookingModal() {
                                    const modal = document.getElementById('editBookingModal');
                                    if (!modal) return;
                                    modal.classList.add('hidden');
                                    modal.classList.remove('flex','items-center','justify-center');
                                }

                                async function submitEditBooking(evt) {
                                    evt.preventDefault();
                                    const bookingId = document.getElementById('edit_booking_id').value;
                                    const carwashId = document.getElementById('edit_carwash_id').value;
                                    const serviceId = document.getElementById('edit_service_id').value;
                                    const date = document.getElementById('edit_date').value;
                                    const time = document.getElementById('edit_time').value;
                                    const notes = document.getElementById('edit_notes').value;
                                    if (!bookingId) return alert('Booking id missing');
                                    const fd = new FormData();
                                    fd.append('booking_id', bookingId);
                                    fd.append('carwash_id', carwashId);
                                    fd.append('service_id', serviceId);
                                    fd.append('date', date);
                                    fd.append('time', time);
                                    fd.append('notes', notes);
                                    fd.append('csrf_token', getCsrfToken());
                                    try {
                                        const resp = await fetch('/carwash_project/backend/api/bookings/update.php', { method: 'POST', body: fd, credentials: 'same-origin' });
                                        const bodyText = await resp.text();

                                        // Log full response for debugging
                                        console.debug('update.php raw response:', bodyText);

                                        // If server returned non-OK, show error toast and log
                                        if (!resp.ok) {
                                            console.error('Edit booking HTTP error', resp.status, bodyText);
                                            showError('Sunucu hatası: ' + resp.status + '. Detaylar konsolda.');
                                            return;
                                        }

                                        let result = null;
                                        try {
                                            result = bodyText ? JSON.parse(bodyText) : null;
                                        } catch (parseErr) {
                                            console.error('Edit booking non-JSON response (raw):', bodyText, parseErr);
                                            showError('Sunucudan beklenmeyen cevap alındı. Detaylar konsolda.');
                                            return;
                                        }

                                        if (result && result.success) {
                                            hideEditBookingModal();
                                            await loadBookings();
                                            if (result.requires_approval) {
                                                showWarning('Rezervasyonunuz kaydedildi. Tarih veya saat değişikliği nedeniyle onay bekleniyor.');
                                            } else {
                                                showSuccess('Rezervasyon başarıyla güncellendi.');
                                            }
                                            return;
                                        }

                                        const errMsg = (result && result.errors && result.errors.join) ? result.errors.join('\n') : (result && result.message) || 'Güncelleme başarısız';
                                        console.warn('Edit booking failed:', errMsg, result);
                                        showError(errMsg);
                                    } catch (err) {
                                        console.error('Edit booking error', err);
                                        showError('Sunucu hatası oluştu. Detaylar konsolda.');
                                    }
                                }

                                async function cancelBookingById(id) {
                                    if (!confirm('Rezervasyonu iptal etmek istiyor musunuz?')) return;
                                    const fd = new FormData();
                                    fd.append('booking_id', id);
                                    fd.append('csrf_token', getCsrfToken());
                                    try {
                                        const resp = await fetch('/carwash_project/backend/carwash/reservations/cancel.php', { method: 'POST', body: fd, credentials: 'same-origin' });
                                        const result = await resp.json();
                                        if (result && result.success) {
                                            await loadBookings();
                                            return;
                                        }
                                        alert(result && result.error ? result.error : 'İptal başarısız');
                                      } catch (err) { console.error(err); showError('Sunucu hatası'); }
                                }
                            function escapeAttr(s){ return (s||'').replace(/\"/g,'&quot;'); }

                            // Initialize controls when DOM ready
                            document.addEventListener('DOMContentLoaded', function(){
                                loadDistrictOptions();
                                filterCarWashes();
                                // also update districts when city changes
                                $id('cityFilter')?.addEventListener('change', function(){ loadDistrictOptions(); filterCarWashes(); });
                                $id('districtFilter')?.addEventListener('change', filterCarWashes);
                                $id('carWashNameFilter')?.addEventListener('input', filterCarWashes);
                                $id('favoriteFilter')?.addEventListener('change', filterCarWashes);

                                // Attach click handlers to any initial server-rendered cards so whole-card click works
                                try {
                                    document.querySelectorAll('#carWashList > div[data-id]').forEach(function(card){
                                        card.addEventListener('click', function(evt){
                                            const tag = (evt.target && evt.target.tagName) ? evt.target.tagName.toLowerCase() : '';
                                            if (tag === 'a' || tag === 'button' || (evt.target.closest && evt.target.closest('button, a'))) return;
                                            const id = this.getAttribute('data-id') || '';
                                            const name = this.getAttribute('data-name') || '';
                                            if (id) selectCarWashForReservation(name, id);
                                        });
                                    });

                                    // Ensure server-rendered "Rezervasyon Yap" buttons also trigger selection
                                    document.querySelectorAll('.select-for-reservation').forEach(btn => {
                                        btn.removeEventListener('click', btn._selHandler);
                                        btn._selHandler = function(){
                                            const name = this.getAttribute('data-name') || '';
                                            const id = this.getAttribute('data-id') || '';
                                            selectCarWashForReservation(name, id);
                                        };
                                        btn.addEventListener('click', btn._selHandler);
                                    });
                                } catch (e) { console.warn('Attach initial carwash handlers failed', e); }
                            });

                            // Favorites functionality
                            async function loadFavoriteStatus(carwashId) {
                                try {
                                    const resp = await fetch(`/carwash_project/backend/api/favorites.php?carwash_id=${carwashId}`, {
                                        credentials: 'same-origin',
                                        headers: { 'Accept': 'application/json' }
                                    });
                                    const result = await resp.json();
                                    return result.success ? result.is_favorite : false;
                                } catch (e) {
                                    console.error('Failed to load favorite status:', e);
                                    return false;
                                }
                            }

                            async function toggleFavorite(carwashId, button) {
                                try {
                                    const formData = new FormData();
                                    formData.append('carwash_id', carwashId);
                                    formData.append('action', 'toggle');
                                    formData.append('csrf_token', getCsrfToken());

                                    const resp = await fetch('/carwash_project/backend/api/favorites.php', {
                                        method: 'POST',
                                        body: formData,
                                        credentials: 'same-origin'
                                    });
                                    const result = await resp.json();

                                    if (result.success) {
                                        const icon = button.querySelector('i');
                                        if (result.is_favorite) {
                                            icon.className = 'fas fa-heart text-red-500 text-xl';
                                            button.title = 'Favorilerden çıkar';
                                        } else {
                                            icon.className = 'far fa-heart text-gray-400 text-xl';
                                            button.title = 'Favorilere ekle';
                                        }
                                        // Update the carwash data for filtering
                                        const carwash = allCarWashes.find(cw => cw.id == carwashId);
                                        if (carwash) {
                                            carwash.isFavorite = result.is_favorite;
                                        }
                                        return result.is_favorite;
                                    }
                                } catch (e) {
                                    console.error('Failed to toggle favorite:', e);
                                }
                                return false;
                            }

                            // Load favorite status for all visible carwashes
                            async function loadAllFavoriteStatuses() {
                                const favoriteButtons = document.querySelectorAll('.favorite-toggle');
                                for (const button of favoriteButtons) {
                                    const carwashId = button.getAttribute('data-carwash-id');
                                    if (carwashId) {
                                        const isFavorite = await loadFavoriteStatus(carwashId);
                                        const icon = button.querySelector('i');
                                        if (isFavorite) {
                                            icon.className = 'fas fa-heart text-red-500 text-xl';
                                            button.title = 'Favorilerden çıkar';
                                        } else {
                                            icon.className = 'far fa-heart text-gray-400 text-xl';
                                            button.title = 'Favorilere ekle';
                                        }
                                        // Update carwash data
                                        const carwash = allCarWashes.find(cw => cw.id == carwashId);
                                        if (carwash) {
                                            carwash.isFavorite = isFavorite;
                                        }
                                    }
                                }
                            }

                            // Attach favorite button handlers
                            document.addEventListener('click', function(e) {
                                if (e.target.closest('.favorite-toggle')) {
                                    e.preventDefault();
                                    e.stopPropagation();
                                    const button = e.target.closest('.favorite-toggle');
                                    const carwashId = button.getAttribute('data-carwash-id');
                                    if (carwashId) {
                                        toggleFavorite(carwashId, button);
                                    }
                                }
                            });

                            // Load favorites when section becomes visible
                            // Use requestIdleCallback to avoid blocking main thread (fallback to rAF)
                            document.addEventListener('sectionChanged', function(e) {
                                if (e.detail && e.detail.section === 'carWashSelection') {
                                    if (window.requestIdleCallback) {
                                        requestIdleCallback(loadAllFavoriteStatuses, { timeout: 200 });
                                    } else {
                                        requestAnimationFrame(loadAllFavoriteStatuses);
                                    }
                                }
                            });

                        })();
                        </script>
                                </section>

                <!-- ========== RESERVATIONS SECTION (Inserted from customer_profile.html) ========== -->
                <section id="reservations" x-show="currentSection === 'reservations'" class="space-y-6 pt-6 lg:pt-8" style="display: none;">
                    <div class="mb-8">
                        <h2 class="text-3xl font-bold text-gray-800 mb-2">Rezervasyonlarım</h2>
                        <p class="text-gray-600">Tüm rezervasyonlarınızı görüntüleyin ve yönetin</p>
                    </div>

                    <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
                        <!-- Reservation List View -->
                        <div id="reservationListView">
                            <div class="p-6 border-b">
                                <div class="flex justify-between items-center">
                                    <h3 class="text-xl font-bold">Aktif Rezervasyonlar</h3>
                                    <button type="button" id="newReservationBtn" class="gradient-bg text-white px-4 py-2 rounded-lg hover:shadow-lg transition-all">
                                        <i class="fas fa-plus mr-2"></i>Yeni Rezervasyon
                                    </button>
                                </div>
                            </div>

                            <div class="overflow-x-auto">
                                <?php
                                // Load reservations server-side so the Reservation List View shows real data
                                // use existing bootstrap (already included at top of this file) to get DB connection
                                $reservations = [];
                                try {
                                    // Prefer existing $db if available (created earlier in page), otherwise try patterns from config
                                    if (isset($db) && is_object($db) && method_exists($db, 'getPdo')) {
                                        $pdoConn = $db->getPdo();
                                    } elseif (isset($pdo) && $pdo instanceof PDO) {
                                        $pdoConn = $pdo;
                                    } else {
                                        // Fallback to App\Classes\Database singleton if present
                                        if (class_exists('App\\Classes\\Database')) {
                                            $db = App\Classes\Database::getInstance();
                                            $pdoConn = $db->getPdo();
                                        } else {
                                            // As a last resort try variable from config
                                            $pdoConn = ${'pdo'} ?? null;
                                        }
                                    }

                                    if (!isset($pdoConn) || !$pdoConn) {
                                        throw new Exception('Database connection not available');
                                    }
                                    $pdoConn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

                                    // For customer dashboard, show their own bookings
                                    $userId = (int)$_SESSION['user_id'];
                                    
                                    if ($userId) {
                                        // Detect schema differences so joins don't fail on non-existent columns/tables
                                        $colCheck = $pdoConn->prepare("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'bookings' AND column_name = 'vehicle_id'");
                                        $colCheck->execute();
                                        $hasVehicleId = (bool)$colCheck->fetchColumn();

                                        $tblCheck = $pdoConn->prepare("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = 'booking_services'");
                                        $tblCheck->execute();
                                        $hasBookingServices = (bool)$tblCheck->fetchColumn();

                                        // Build query using the available columns/tables
                                        if ($hasVehicleId) {
                                            $vehicleJoin = "LEFT JOIN user_vehicles v ON b.vehicle_id = v.id";
                                            $plateExpr = "COALESCE(v.license_plate, v.plate_number, '') AS plate_number";
                                        } else {
                                            $vehicleJoin = "";
                                            $plateExpr = "COALESCE(b.vehicle_plate, '') AS plate_number";
                                        }

                                        if ($hasBookingServices) {
                                            $serviceJoin = "LEFT JOIN booking_services bs ON bs.booking_id = b.id LEFT JOIN services s ON bs.service_id = s.id";
                                        } else {
                                            // fallback to services linked directly from bookings (if available) or empty
                                            $serviceJoin = "LEFT JOIN services s ON b.service_id = s.id";
                                        }

                                        $sql = "SELECT \
                                            b.id AS booking_id,\n+                                            b.booking_date,\n+                                            b.booking_time,\n+                                            b.status,\n+                                            COALESCE(cw.name, cw.business_name, '') AS carwash_name,\n+                                            {$plateExpr},\n+                                            COALESCE(s.name, b.service_type, '') AS service_name,\n+                                            COALESCE(s.duration, 0) AS duration,\n+                                            COALESCE(s.price, b.total_price, 0) AS price\n+                                        FROM bookings b\n+                                        LEFT JOIN carwashes cw ON b.carwash_id = cw.id\n+                                        {$vehicleJoin}\n+                                        {$serviceJoin}\n+                                        WHERE b.user_id = :user_id\n+                                        ORDER BY b.booking_date DESC, b.booking_time DESC";

                                        $stmt = $pdoConn->prepare($sql);
                                        $stmt->execute(['user_id' => (int)$_SESSION['user_id']]);
                                        $reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                    }
                                } catch (Exception $e) {
                                    error_log('Customer dashboard reservations fetch error: ' . $e->getMessage());
                                    $reservations = [];
                                }
                                ?>

                                <table class="w-full">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">İşletme</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Plaka</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Hizmet</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Süre</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fiyat</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tarih</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Saat</th>
                                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Durum</th>
                                        </tr>
                                    </thead>
                                    <tbody id="reservationsTableBody" class="divide-y divide-gray-200">
                                        <?php if (empty($reservations)): ?>
                                            <tr>
                                                <td colspan="9" class="px-6 py-8 text-center text-sm text-gray-500">Rezervasyon bulunamadı.</td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($reservations as $r): ?>
                                                <tr class="hover:bg-gray-50">
                                                    <td class="px-6 py-4 text-sm text-gray-700"><?php echo htmlspecialchars($r['booking_id'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                                                    <td class="px-6 py-4 text-sm text-gray-700"><?php echo htmlspecialchars($r['carwash_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                                                    <td class="px-6 py-4 text-sm text-gray-700"><?php echo htmlspecialchars($r['plate_number'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                                                    <td class="px-6 py-4 text-sm text-gray-700"><?php echo htmlspecialchars($r['service_name'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                                                    <td class="px-6 py-4 text-sm text-gray-700"><?php echo htmlspecialchars($r['duration'] ?? 0, ENT_QUOTES, 'UTF-8'); ?> dk</td>
                                                    <td class="px-6 py-4 text-sm text-gray-700">₺<?php echo number_format((float)($r['price'] ?? 0), 2); ?></td>
                                                    <td class="px-6 py-4 text-sm text-gray-700"><?php echo htmlspecialchars($r['booking_date'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                                                    <td class="px-6 py-4 text-sm text-gray-700"><?php echo htmlspecialchars($r['booking_time'] ?? '', ENT_QUOTES, 'UTF-8'); ?></td>
                                                    <td class="px-6 py-4"><?php 
                                                        $status = $r['status'] ?? '';
                                                        if ($status === 'confirmed' || $status === 'paid') {
                                                            echo '<span class="px-2 py-1 rounded-full text-xs bg-green-100 text-green-800">Onaylandı</span>';
                                                        } elseif ($status === 'pending' || $status === 'processing') {
                                                            echo '<span class="px-2 py-1 rounded-full text-xs bg-yellow-100 text-yellow-800">Beklemede</span>';
                                                        } elseif ($status === 'cancelled' || $status === 'cancel') {
                                                            echo '<span class="px-2 py-1 rounded-full text-xs bg-red-100 text-red-800">İptal Edildi</span>';
                                                        } else {
                                                            echo '<span class="px-2 py-1 rounded-full text-xs bg-gray-100 text-gray-800">' . htmlspecialchars($status, ENT_QUOTES, 'UTF-8') . '</span>';
                                                        }
                                                    ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- New Reservation Form -->
                        <div id="reservationFormRestorePoint"></div>
                        <div id="newReservationForm" class="p-6 hidden">
                            <h3 class="text-xl font-bold mb-6">Yeni Rezervasyon Oluştur</h3>
                            <form id="newReservationFormElement" class="space-y-6">
                                <div>
                                    <label for="service_id" class="block text-sm font-bold text-gray-700 mb-2">Hizmet Seçin</label>
                                    <?php
                                    // Fetch services dynamically from database
                                    // Initially show placeholder only; JavaScript will populate based on selected carwash
                                    $selectedCarwashId = $_GET['carwash_id'] ?? $_POST['carwash_id'] ?? null;
                                    $services = [];
                                    
                                    if ($selectedCarwashId) {
                                        try {
                                            $services = $db->fetchAll(
                                                "SELECT id, name, price, duration FROM services WHERE carwash_id = :carwash_id AND status = 'active' ORDER BY name ASC",
                                                ['carwash_id' => $selectedCarwashId]
                                            );
                                        } catch (Exception $e) {
                                            // Log error but don't break the page
                                            error_log("Error fetching services: " . $e->getMessage());
                                        }
                                    }
                                    ?>
                                    <select id="service_id" name="service_id" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500" required>
                                        <option value="">Hizmet Seçiniz</option>
                                        <?php foreach ($services as $service): ?>
                                            <option value="<?php echo htmlspecialchars($service['id'], ENT_QUOTES, 'UTF-8'); ?>" 
                                                    data-price="<?php echo htmlspecialchars($service['price'], ENT_QUOTES, 'UTF-8'); ?>"
                                                    data-duration="<?php echo htmlspecialchars($service['duration'], ENT_QUOTES, 'UTF-8'); ?>">
                                                <?php echo htmlspecialchars($service['name'], ENT_QUOTES, 'UTF-8'); ?> - 
                                                <?php echo htmlspecialchars($service['duration'], ENT_QUOTES, 'UTF-8'); ?> dakika - 
                                                ₺<?php echo number_format($service['price'], 2); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <p class="text-sm text-gray-500 mt-1">
                                        <?php if (empty($services)): ?>
                                            Hizmetleri görmek için önce bir konum seçiniz.
                                        <?php endif; ?>
                                    </p>
                                </div>

                                <div>
                                    <label for="vehicle" class="block text-sm font-bold text-gray-700 mb-2">Araç Seçin</label>
                                    <select id="vehicle" name="vehicle_id" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500" required>
                                        <option value="">Araç Seçiniz</option>
                                        <?php
                                        // Load user's vehicles from database
                                        $userVehicles = [];
                                        try {
                                            if (isset($db) && is_object($db)) {
                                                $userVehicles = $db->fetchAll(
                                                    "SELECT id, brand, model, license_plate, year FROM user_vehicles WHERE user_id = :user_id ORDER BY brand, model",
                                                    ['user_id' => $_SESSION['user_id']]
                                                );
                                            }
                                        } catch (Exception $e) {
                                            error_log('Error loading user vehicles: ' . $e->getMessage());
                                        }
                                        
                                        foreach ($userVehicles as $vehicle): 
                                            $displayName = trim($vehicle['brand'] . ' ' . $vehicle['model']);
                                            if (!empty($vehicle['year'])) {
                                                $displayName .= ' (' . $vehicle['year'] . ')';
                                            }
                                            $displayName .= ' - ' . $vehicle['license_plate'];
                                        ?>
                                            <option value="<?php echo htmlspecialchars($vehicle['id'], ENT_QUOTES, 'UTF-8'); ?>">
                                                <?php echo htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8'); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <?php if (empty($userVehicles)): ?>
                                        <p class="text-sm text-gray-500 mt-1">Kayıtlı aracınız bulunmuyor. Lütfen önce araç ekleyin.</p>
                                    <?php endif; ?>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label for="reservationDate" class="block text-sm font-bold text-gray-700 mb-2">Tarih</label>
                                        <input type="date" id="reservationDate" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                                    </div>
                                    <div>
                                            <label for="reservationTime" class="block text-sm font-bold text-gray-700 mb-2">Saat</label>
                                            <input type="time" id="reservationTime" name="reservationTime" step="60" min="00:00" max="23:59" placeholder="00:00" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500" aria-label="Saat (24 saat formatı)">
                                            <p class="text-sm text-gray-500 mt-2">Lütfen saat seçimini 24 saat formatında girin (örn. 08:30 veya 18:45).</p>
                                    </div>
                                </div>

                                <div>
                                    <label for="location" class="block text-sm font-bold text-gray-700 mb-2">Konum</label>
                                    <select id="location" name="location" class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500">
                                        <option value="">Konum Seçiniz</option>
                                        <?php if (!empty($carwashes)): ?>
                                                <?php foreach ($carwashes as $cw_opt): ?>
                                                    <option value="<?php echo htmlspecialchars($cw_opt['id'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($cw_opt['name'] ?? $cw_opt['address'] ?? '', ENT_QUOTES, 'UTF-8'); ?></option>
                                                <?php endforeach; ?>
                                        <?php else: ?>
                                            <!-- fallback options left minimal -->
                                        <?php endif; ?>
                                    </select>
                                    <input type="hidden" id="location_id" name="location_id" value="">
                                </div>

                                <div>
                                    <label for="notes" class="block text-sm font-bold text-gray-700 mb-2">Ek Notlar (İsteğe Bağlı)</label>
                                    <textarea id="notes" rows="3" placeholder="Özel istekleriniz veya notlarınız..." class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-500"></textarea>
                                </div>

                                <div class="flex justify-end space-x-4">
                                    <button type="button" id="cancelNewReservation" class="px-6 py-3 border border-gray-300 text-gray-700 rounded-lg font-bold hover:bg-gray-50 transition-colors">Geri Dön</button>
                                    <button type="submit" id="submitNewReservation" class="gradient-bg text-white px-6 py-3 rounded-lg font-bold hover:shadow-lg transition-all"><i class="fas fa-calendar-plus mr-2"></i>Rezervasyon Yap</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </section>

                <!-- Edit Booking Modal -->
                <div id="editBookingModal" class="fixed inset-0 bg-black bg-opacity-40 z-50 hidden">
                    <div class="bg-white rounded-lg w-full max-w-lg p-6">
                        <h3 class="text-lg font-bold mb-4">Rezervasyonu Düzenle</h3>
                        <form id="editBookingForm" class="space-y-4">
                            <input type="hidden" id="edit_booking_id" name="booking_id" value="">
                            <input type="hidden" id="edit_carwash_id" name="carwash_id" value="">
                            <input type="hidden" id="edit_service_id" name="service_id" value="">

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Tarih</label>
                                <input type="date" id="edit_date" name="date" class="w-full px-3 py-2 border rounded">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Saat</label>
                                <input type="time" id="edit_time" name="time" class="w-full px-3 py-2 border rounded">
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Notlar</label>
                                <textarea id="edit_notes" name="notes" rows="3" class="w-full px-3 py-2 border rounded"></textarea>
                            </div>

                            <div class="flex justify-end space-x-2">
                                <button type="button" id="editCancelBtn" class="px-4 py-2 border rounded">İptal</button>
                                <button type="submit" id="editSaveBtn" class="px-4 py-2 bg-blue-600 text-white rounded">Kaydet</button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- ========== HISTORY SECTION ========== -->
                <section x-show="currentSection === 'history'" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 transform translate-y-4" x-transition:enter-end="opacity-100 transform translate-y-0" class="space-y-6 pt-6 lg:pt-8" style="display: none;" x-data="historySection()">
                    <div class="mb-8">
                        <h2 class="text-3xl font-bold text-gray-800 mb-2">Geçmiş Rezervasyonlar</h2>
                        <p class="text-gray-600">Tamamlanan rezervasyonlarınızın geçmişini görüntüleyin</p>
                    </div>

                    <!-- Error Message -->
                    <div x-show="error" x-transition class="bg-red-50 border-2 border-red-200 rounded-xl p-4 mb-4">
                        <div class="flex items-center gap-3">
                            <i class="fas fa-exclamation-circle text-red-600 text-xl"></i>
                            <p class="text-red-700" x-text="error"></p>
                        </div>
                    </div>

                    <div class="bg-white rounded-2xl shadow-lg overflow-hidden">
                        <div class="p-6 border-b">
                            <div class="flex justify-between items-center">
                                <h3 class="text-xl font-bold">Tamamlanan Rezervasyonlar</h3>
                                <div class="flex gap-2">
                                    <button @click="loadHistory()" :disabled="loading" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-all disabled:opacity-50">
                                        <i class="fas" :class="loading ? 'fa-spinner fa-spin' : 'fa-sync-alt'" x-bind:class="{'fa-spin': loading}"></i>
                                        <span class="ml-2" x-text="loading ? 'Yükleniyor...' : 'Yenile'"></span>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Hizmet</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Oto Yıkama</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Araç</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Tarih</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Saat</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fiyat</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Ödeme</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200" x-show="bookings.length > 0 && !loading" x-transition>
                                    <template x-for="booking in bookings" :key="booking.booking_id">
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900" x-text="'#' + booking.booking_id"></td>
                                            <td class="px-6 py-4 text-sm text-gray-700">
                                                <div>
                                                    <div class="font-medium" x-text="booking.service_name"></div>
                                                    <div class="text-xs text-gray-500" x-show="booking.service_category" x-text="booking.service_category"></div>
                                                    <div class="text-xs text-gray-500" x-show="booking.service_duration" x-text="booking.service_duration"></div>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 text-sm text-gray-700">
                                                <div>
                                                    <div class="font-medium" x-text="booking.carwash_name"></div>
                                                    <div class="text-xs text-gray-500" x-show="booking.carwash_city" x-text="booking.carwash_city"></div>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 text-sm text-gray-700">
                                                <div>
                                                    <div class="font-medium" x-show="booking.vehicle_info" x-text="booking.vehicle_info"></div>
                                                    <div class="text-xs text-gray-500" x-show="booking.vehicle_plate" x-text="booking.vehicle_plate"></div>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700" x-text="formatDate(booking.booking_date)"></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700" x-text="formatTime(booking.booking_time)"></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900" x-text="'₺' + formatPrice(booking.total_price)"></td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full" 
                                                      :class="{
                                                          'bg-green-100 text-green-800': booking.payment_status === 'paid',
                                                          'bg-yellow-100 text-yellow-800': booking.payment_status === 'pending',
                                                          'bg-gray-100 text-gray-800': booking.payment_status === 'refunded'
                                                      }"
                                                      x-text="booking.payment_status === 'paid' ? 'Ödendi' : booking.payment_status === 'pending' ? 'Bekliyor' : 'İade Edildi'">
                                                </span>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                                <tbody x-show="bookings.length === 0 && !loading && !error" x-transition>
                                    <tr>
                                        <td colspan="8" class="px-6 py-8 text-center text-sm text-gray-500">
                                            <div class="flex flex-col items-center">
                                                <i class="fas fa-history text-4xl text-gray-300 mb-4"></i>
                                                <p>Henüz tamamlanan rezervasyon bulunmuyor.</p>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                                <tbody x-show="loading" x-transition>
                                    <tr>
                                        <td colspan="8" class="px-6 py-8 text-center text-sm text-gray-500">
                                            <div class="flex flex-col items-center">
                                                <i class="fas fa-spinner fa-spin text-2xl text-blue-500 mb-4"></i>
                                                <p>Yükleniyor...</p>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </section>

                <!-- Other sections (carWashSelection, history) would follow the same pattern -->

                <script>
                (function(){
                    'use strict';

                    // Normalize various time input formats (e.g., user-typed "8:30 PM") to 24-hour HH:MM
                    function normalizeTimeTo24(s) {
                        if (!s) return '';
                        s = String(s).trim();

                        // Matches formats like "8:30 PM" or "08:30PM"
                        const ampm = s.match(/^(\d{1,2}):(\d{2})\s*([AaPp][Mm])$/);
                        if (ampm) {
                            let hh = parseInt(ampm[1], 10);
                            const mm = ampm[2];
                            const ap = ampm[3].toLowerCase();
                            if (ap === 'pm' && hh < 12) hh += 12;
                            if (ap === 'am' && hh === 12) hh = 0;
                            return (hh < 10 ? '0' + hh : '' + hh) + ':' + mm;
                        }

                        // Matches HH:MM or H:MM (possibly with seconds) — keep only HH:MM
                        const simple = s.match(/^(\d{1,2}):(\d{2})(?::\d{2})?$/);
                        if (simple) {
                            let hh = parseInt(simple[1], 10);
                            const mm = simple[2];
                            if (hh >= 0 && hh <= 24) {
                                return (hh < 10 ? '0' + hh : '' + hh) + ':' + mm;
                            }
                        }

                        // Fallback: return original string
                        return s;
                    }
                    // Show new reservation form (optionally inside the carwash section)
                    function showNewReservationForm(targetHolderId){
                        const origFormWrapper = document.getElementById('newReservationForm');
                        if (targetHolderId && origFormWrapper) {
                            const holder = document.getElementById(targetHolderId);
                            if (holder) {
                                // remember original parent id to restore later
                                if (!origFormWrapper.dataset.originalParentId) {
                                    const parent = origFormWrapper.parentElement;
                                    if (parent && parent.id) origFormWrapper.dataset.originalParentId = parent.id;
                                }
                                holder.appendChild(origFormWrapper);
                                holder.classList.remove('hidden');
                                document.getElementById('carWashList')?.classList.add('hidden');
                                origFormWrapper.classList.remove('hidden');
                                origFormWrapper.scrollIntoView({ behavior: 'smooth' });
                                return;
                            }
                        }

                        // Fallback: show in-place (existing behavior)
                        document.getElementById('newReservationForm')?.classList.remove('hidden');
                        document.getElementById('reservationListView')?.classList.add('hidden');
                        document.getElementById('newReservationForm')?.scrollIntoView({ behavior: 'smooth' });
                    }

                    // Hide new reservation form and restore it to its original parent if moved
                    function hideNewReservationForm(){
                        const origFormWrapper = document.getElementById('newReservationForm');
                        if (!origFormWrapper) return;

                        // restore to original parent if it was moved
                        const origParentId = origFormWrapper.dataset.originalParentId;
                        if (origParentId) {
                            const origParent = document.getElementById(origParentId);
                            if (origParent) {
                                origParent.appendChild(origFormWrapper);
                            }
                            delete origFormWrapper.dataset.originalParentId;
                        }

                        // update visibility of lists
                        document.getElementById('carWashList')?.classList.remove('hidden');
                        document.getElementById('reservationListView')?.classList.remove('hidden');
                        origFormWrapper.classList.add('hidden');
                    }

                    // Submit new reservation: POST to server API, then redirect to invoice/checkout
                    async function submitNewReservation(evt){
                        if (evt && evt.preventDefault) evt.preventDefault();

                        const form = (evt && evt.target && (evt.target.tagName === 'FORM' ? evt.target : evt.target.closest('form'))) || document.getElementById('newReservationFormElement');
                        if (!form) {
                            alert('Form bulunamadı. Lütfen sayfayı yenileyin.');
                            return;
                        }

                        const service = (form.querySelector('#service_id') || form.querySelector('[name="service_id"]') || form.querySelector('#service') || form.querySelector('[name="service"]'))?.value || '';
                        const vehicle = (form.querySelector('#vehicle') || form.querySelector('[name="vehicle"]'))?.value || '';
                        const date = (form.querySelector('#reservationDate') || form.querySelector('[name="reservationDate"]'))?.value || '';
                        let time = (form.querySelector('#reservationTime') || form.querySelector('[name="reservationTime"]'))?.value || '';
                        time = normalizeTimeTo24(time);
                        const location = (form.querySelector('#location') || form.querySelector('[name="location"]'))?.value || '';
                        const location_id = (form.querySelector('#location_id') || form.querySelector('[name="location_id"]'))?.value || '';
                        const notes = (form.querySelector('#notes') || form.querySelector('[name="notes"]'))?.value || '';

                        if (!service || !vehicle || !date || !time || !location) {
                            alert('Lütfen tüm zorunlu alanları doldurun.');
                            return;
                        }

                        // Build FormData for POST
                        const fd = new FormData();
                        fd.append('service_id', service);
                        fd.append('vehicle', vehicle);
                        fd.append('reservationDate', date);
                        fd.append('reservationTime', time);
                        fd.append('location', location);
                        fd.append('location_id', location_id);
                        fd.append('notes', notes);
                        fd.append('csrf_token', window.CONFIG && window.CONFIG.CSRF_TOKEN ? window.CONFIG.CSRF_TOKEN : document.querySelector('meta[name="csrf-token"]').getAttribute('content'));

                        try {
                            const resp = await fetch('/carwash_project/backend/api/reservations/create.php', {
                                method: 'POST',
                                body: fd,
                                credentials: 'same-origin'
                            });
                            const result = await resp.json();
                            if (!result || !result.success) {
                                alert(result && result.message ? result.message : 'Rezervasyon oluşturulamadı.');
                                return;
                            }

                            // Redirect to invoice/checkout page provided by server
                            if (result.redirect) {
                                window.location.href = result.redirect;
                                return;
                            }

                            alert('Rezervasyon oluşturuldu, fakat yönlendirme bilgisi alınamadı.');
                        } catch (err) {
                            console.error('Reservation create error:', err);
                            alert('Sunucu hatası oluştu. Lütfen daha sonra tekrar deneyin.');
                        }
                    }

                    function escapeHtml(s){ if(!s) return ''; return String(s).replace(/[&<>\"']/g, function(c){ return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'})[c]; }); }

                    // Dynamically fetch and populate services based on selected carwash
                    async function fetchServicesForCarwash(carwashId) {
                        const serviceSelect = document.getElementById('service_id');
                        if (!serviceSelect) return;

                        // Clear any previous error message
                        const errElId = 'serviceLoadError';
                        let errEl = document.getElementById(errElId);
                        if (!errEl) {
                            errEl = document.createElement('div');
                            errEl.id = errElId;
                            errEl.className = 'text-sm text-red-600 mt-2';
                            serviceSelect.parentNode.appendChild(errEl);
                        }
                        errEl.textContent = '';

                        // Reset to placeholder and clear old options
                        serviceSelect.innerHTML = '<option value="">Hizmet Seçiniz</option>';
                        
                        if (!carwashId) {
                            errEl.textContent = 'Lütfen bir konum seçin.';
                            console.warn('fetchServicesForCarwash: no carwashId provided');
                            return;
                        }

                        // Diagnostic log: which id is being requested
                        console.log('carwash_id sent:', carwashId);

                        const url = '/carwash_project/backend/carwash/services/get_by_carwash.php?carwash_id=' + encodeURIComponent(carwashId);
                        console.log('Services API URL:', url);

                        // Retry logic: try up to 3 attempts with small delay
                        const maxAttempts = 3;
                        let attempt = 0;
                        let lastErr = null;
                        while (attempt < maxAttempts) {
                            attempt++;
                            try {
                                console.log('fetchServicesForCarwash: attempt', attempt, 'url=', url);
                                const resp = await fetch(url, {
                                    credentials: 'same-origin',
                                    cache: 'no-store',
                                    headers: { 'Accept': 'application/json' }
                                });

                                const text = await resp.text();
                                console.log('fetchServicesForCarwash: response status', resp.status, 'text length', text ? text.length : 0);

                                if (!resp.ok) {
                                    console.error('Services API error', resp.status, text);
                                    lastErr = new Error('HTTP ' + resp.status);
                                    // small backoff before retry
                                    await new Promise(r => setTimeout(r, 250 * attempt));
                                    continue;
                                }

                                let json = null;
                                try { json = text ? JSON.parse(text) : null; } catch (e) { console.error('Failed to parse services JSON', e, text); errEl.textContent = 'Sunucudan geçersiz yanıt alındı.'; return; }

                                console.log('API response:', json);

                                // Normalize rows
                                let rows = [];
                                if (json && Array.isArray(json.data)) rows = json.data;
                                else if (Array.isArray(json)) rows = json;
                                else if (json && json.success && Array.isArray(json.data)) rows = json.data;

                                if (!rows || rows.length === 0) {
                                    console.warn('fetchServicesForCarwash: empty services array returned for carwash', carwashId);
                                    errEl.textContent = 'Hizmet bulunamadı.';
                                    return;
                                }

                                // Populate options (clear first)
                                serviceSelect.innerHTML = '<option value="">Hizmet Seçiniz</option>';
                                rows.forEach(s => {
                                    const opt = document.createElement('option');
                                    opt.value = s.id || s.ID || '';
                                    const name = s.name || s.service_name || '';
                                    const price = (s.price !== undefined && s.price !== null) ? (' - ₺' + parseFloat(s.price).toFixed(2)) : '';
                                    const duration = (s.duration ? (' - ' + s.duration + ' dakika') : '');
                                    opt.textContent = name + duration + price;
                                    if (s.price !== undefined) opt.setAttribute('data-price', s.price);
                                    if (s.duration !== undefined) opt.setAttribute('data-duration', s.duration);
                                    serviceSelect.appendChild(opt);
                                });

                                console.log('Loaded services for carwash', carwashId, 'count', rows.length);
                                return;

                            } catch (err) {
                                console.error('fetchServicesForCarwash attempt', attempt, 'error', err);
                                lastErr = err;
                                // wait before next retry
                                await new Promise(r => setTimeout(r, 300 * attempt));
                                continue;
                            }
                        }

                        // If we reach here, all attempts failed
                        console.error('fetchServicesForCarwash failed after attempts', maxAttempts, lastErr);
                        errEl.textContent = 'Hizmetler yüklenemedi. Lütfen tekrar deneyin.';
                    }

                    // Export to global scope so other inline handlers can call it safely
                    try {
                        if (typeof window !== 'undefined') window.fetchServicesForCarwash = fetchServicesForCarwash;
                    } catch (e) {
                        // non-browser environments may throw; ignore
                    }

                    // Attach handlers
                    document.addEventListener('DOMContentLoaded', function(){
                        document.getElementById('newReservationBtn')?.addEventListener('click', showNewReservationForm);
                        document.getElementById('cancelNewReservation')?.addEventListener('click', hideNewReservationForm);
                        document.getElementById('newReservationFormElement')?.addEventListener('submit', submitNewReservation);
                        
                        // Listen to location changes to dynamically load services
                        document.getElementById('location')?.addEventListener('change', function() {
                            const carwashId = this.value;
                            if (carwashId) {
                                fetchServicesForCarwash(carwashId);
                            }
                        });
                        // support buttons if they exist elsewhere
                        window.showNewReservationForm = showNewReservationForm;
                        window.hideNewReservationForm = hideNewReservationForm;
                        window.submitNewReservation = submitNewReservation;

                        // bookings: load and delegate actions
                        loadBookings();

                        // Delegate Edit / Cancel clicks
                        document.getElementById('reservationsTableBody')?.addEventListener('click', function(e){
                            const target = e.target;
                            if (target.matches('.edit-booking-btn')) {
                                const tr = target.closest('tr');
                                if (tr) showEditBookingModalFromRow(tr);
                                return;
                            }
                            if (target.matches('.cancel-booking-btn')) {
                                const id = target.getAttribute('data-id');
                                if (id) cancelBookingById(id);
                                return;
                            }
                        });

                        // Edit modal handlers
                        document.getElementById('editCancelBtn')?.addEventListener('click', hideEditBookingModal);
                        document.getElementById('editBookingForm')?.addEventListener('submit', submitEditBooking);
                    });

                })();
                </script>
        </div> <!-- END: Max-width container -->
    </main>

</div> <!-- END: Flex Container (Sidebar + Content) -->

<!-- Footer follows naturally at the bottom after content -->

<!-- Scroll-to-Top Button (appears after scrolling) -->
<button id="scrollTopBtn" aria-label="Scroll to top" title="Yukarı çık">
    <i class="fas fa-arrow-up"></i>
</button>

<!-- Dynamic Layout Height Calculator -->
<script>
// ================================
// Layout Height Manager
// Sets header to fixed 80px and computes layout sizes
// Updates CSS variables on load, resize, and content changes
// ================================
(function() {
    'use strict';
    
    let layoutUpdateScheduled = false;
    
    function updateLayoutHeights() {
        // Prevent duplicate updates
        if (layoutUpdateScheduled) return;
        layoutUpdateScheduled = true;
        
        // Use requestAnimationFrame to batch DOM reads/writes
        requestAnimationFrame(() => {
            layoutUpdateScheduled = false;
            
            const root = document.documentElement;
            // Cache viewport width to avoid multiple reflows
            const viewportWidth = window.innerWidth;
            let sidebarWidth = 250;

            if (viewportWidth < 768) {
                sidebarWidth = 250; // Mobile
            } else if (viewportWidth < 900) {
                sidebarWidth = 200; // Small screens
            } else {
                sidebarWidth = 250; // Desktop
            }

            // Batch style property updates
            root.style.setProperty('--header-height', '80px');
            root.style.setProperty('--sidebar-width', `${sidebarWidth}px`);
        });
    }
    
    // Update on load - single call, no setTimeout needed
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', updateLayoutHeights, { once: true });
    } else {
        updateLayoutHeights();
    }
    
    // Update on resize (debounced with rAF instead of setTimeout)
    let resizeTimer;
    window.addEventListener('resize', function() {
        if (resizeTimer) cancelAnimationFrame(resizeTimer);
        resizeTimer = requestAnimationFrame(updateLayoutHeights);
    }, { passive: true });
    
    // Update after images load (use rAF instead of setTimeout)
    window.addEventListener('load', function() {
        requestAnimationFrame(updateLayoutHeights);
    }, { once: true });
})();

// ================================
// Global Toast / Error Display System
// ================================
(function() {
    'use strict';
    
    let toastTimeout = null;
    
    window.showGlobalToast = function(message, type = 'error', duration = 6000) {
        const toast = document.getElementById('globalToast');
        const content = document.getElementById('globalToastContent');
        const icon = document.getElementById('globalToastIcon');
        const msgEl = document.getElementById('globalToastMessage');

        if (!toast || !content || !msgEl) return;

        // Clear any existing timeout
        if (toastTimeout) {
            clearTimeout(toastTimeout);
            toastTimeout = null;
        }

        // Set message
        msgEl.textContent = message;

        // Set styling based on type - use classList for better performance
        content.className = 'rounded-xl shadow-2xl border px-5 py-4 flex items-start gap-3';
        if (type === 'error') {
            content.classList.add('bg-red-50', 'border-red-300', 'text-red-700');
            if (icon) icon.className = 'fas fa-exclamation-circle text-xl mt-0.5 text-red-500';
        } else if (type === 'success') {
            content.classList.add('bg-green-50', 'border-green-300', 'text-green-700');
            if (icon) icon.className = 'fas fa-check-circle text-xl mt-0.5 text-green-500';
        } else {
            content.classList.add('bg-blue-50', 'border-blue-300', 'text-blue-700');
            if (icon) icon.className = 'fas fa-info-circle text-xl mt-0.5 text-blue-500';
        }

        // Show toast using classList for better performance
        toast.classList.remove('translate-x-full', 'opacity-0', 'pointer-events-none');
        toast.classList.add('translate-x-0', 'opacity-100', 'pointer-events-auto');

        // Auto-hide after duration
        if (duration > 0) {
            toastTimeout = setTimeout(function() {
                window.hideGlobalToast();
            }, duration);
        }
    };
    
    window.hideGlobalToast = function() {
        const toast = document.getElementById('globalToast');
        if (!toast) return;

        // Clear timeout if it exists
        if (toastTimeout) {
            clearTimeout(toastTimeout);
            toastTimeout = null;
        }

        // Hide toast using classList
        toast.classList.add('translate-x-full', 'opacity-0', 'pointer-events-none');
        toast.classList.remove('translate-x-0', 'opacity-100', 'pointer-events-auto');
    };
    
    // Shorthand helpers
    window.showError = function(msg) { window.showGlobalToast(msg, 'error'); };
    window.showSuccess = function(msg) { window.showGlobalToast(msg, 'success'); };
})();

// ================================
// Profile Image Refresh Helper
// ================================
window.refreshProfileImages = function(newUrl) {
    // If caller didn't provide a URL, try to obtain canonical URL exposed by header include
    if (!newUrl) {
        if (typeof window.getCanonicalProfileImage === 'function') {
            newUrl = window.getCanonicalProfileImage();
        } else if (window.CARWASH && window.CARWASH.profile && window.CARWASH.profile.canonical) {
            newUrl = window.CARWASH.profile.canonical;
        } else {
            var headerEl = document.getElementById('headerProfileImage');
            if (headerEl) newUrl = headerEl.getAttribute('src') || headerEl.src;
        }
    }

    if (!newUrl) return;

    // Clear any previously cached profile image URLs to prevent 404 errors
    try {
        localStorage.removeItem('carwash_profile_image');
        localStorage.removeItem('carwash_profile_image_ts');
    } catch (e) { /* ignore */ }

    // Always append a lightweight client-side cache-buster so browsers reload
    // (use `cb` to avoid interfering with server-managed `?ts=` param)
    var cb = 'cb=' + Date.now();
    var separator = newUrl.indexOf('?') === -1 ? '?' : '&';
    var newUrlWithCb = newUrl + separator + cb;

    // Persist NEW canonical image and timestamp to localStorage for cross-tab updates
    try {
        localStorage.setItem('carwash_profile_image', newUrl);
        localStorage.setItem('carwash_profile_image_ts', Date.now().toString());
    } catch (e) { /* ignore storage errors */ }
    // Preload the new image first to avoid swapping broken images and to reduce
    // layout thrash; only update DOM once on successful load.
    var pre = new Image();
    var handled = false;
    var fallbackUrl = '<?php echo BASE_URL; ?>/frontend/images/default-avatar.svg';
    
    pre.onerror = function() {
        if (handled) return; handled = true;
        // Image failed to load (404 or other error) - use fallback immediately
        console.warn('Profile image failed to load (404): ' + newUrlWithCb + ' - using fallback');
        requestAnimationFrame(function() {
            var selectors = '#headerProfileImage, #sidebarProfileImage, #mobileMenuAvatar, #profileImagePreview, .profile-img, .sidebar-avatar-img';
            var imgs = document.querySelectorAll(selectors);
            for (var i = 0; i < imgs.length; i++) {
                var el = imgs[i];
                if (el && el.tagName && el.tagName.toLowerCase() === 'img') {
                    el.setAttribute('src', fallbackUrl);
                    el.onerror = null; // Prevent infinite error loops
                }
            }
        });
    };
    
    pre.onload = function() {
        if (handled) return; handled = true;
        // Batch the DOM writes in a single rAF callback.
        requestAnimationFrame(function() {
            // Query selectors once. Avoid reading layout properties here.
            var selectors = '#headerProfileImage, #sidebarProfileImage, #mobileMenuAvatar, #profileImagePreview, .profile-img, .sidebar-avatar-img';
            var imgs = document.querySelectorAll(selectors);
            if (!imgs || imgs.length === 0) return;

            // PHASE 1: Batch all DOM READS first (prevent read-write interleaving)
            var updates = [];
            var newBase = newUrlWithCb.split('cb=')[0].split('?t=')[0];
            
            for (var i = 0; i < imgs.length; i++) {
                var el = imgs[i];
                if (!el) continue;
                
                var isImg = el.tagName && el.tagName.toLowerCase() === 'img';
                var needsUpdate = false;
                
                if (isImg) {
                    var current = el.getAttribute('src') || '';
                    var curBase = current.split('cb=')[0].split('?t=')[0];
                    needsUpdate = (curBase !== newBase || current.indexOf('cb=') === -1);
                } else {
                    var bg = el.style && el.style.backgroundImage ? el.style.backgroundImage : '';
                    needsUpdate = (bg.indexOf(newBase) === -1);
                }
                
                if (needsUpdate) {
                    updates.push({el: el, isImg: isImg});
                }
            }
            
            // PHASE 2: Batch all DOM WRITES (no reads, no forced reflows)
            for (var j = 0; j < updates.length; j++) {
                try {
                    var item = updates[j];
                    if (item.isImg) {
                        item.el.setAttribute('src', newUrlWithCb);
                        item.el.onerror = function() {
                            this.src = fallbackUrl;
                            this.onerror = null;
                        };
                    } else {
                        item.el.style.backgroundImage = 'url("' + newUrlWithCb + '")';
                    }
                } catch (e) { /* ignore per-element errors */ }
            }
        });
    };
    pre.onerror = function() {
        try { window.showError && window.showError('Profil resmi yüklenemedi. Lütfen tekrar deneyin.'); } catch (e) {}
    };
    // Start preload (assign src last to trigger load)
    pre.src = newUrlWithCb;
};

// ================================
// Tab Persistence for Profile Section
// ================================
(function() {
    'use strict';
    
    // Check for server-side active tab or localStorage
    var serverActiveTab = <?php echo json_encode($_SESSION['active_tab'] ?? null); ?>;
    <?php unset($_SESSION['active_tab']); ?>
    var storedTab = localStorage.getItem('customer-dashboard-active-tab');
    var targetTab = serverActiveTab || storedTab;
    
    if (targetTab) {
        // Wait for Alpine to initialize - use rAF instead of setTimeout
        document.addEventListener('DOMContentLoaded', function() {
            requestAnimationFrame(function() {
                // Use a more efficient approach - dispatch custom event
                const event = new CustomEvent('restoreTab', { detail: { tab: targetTab } });
                document.dispatchEvent(event);

                // Clear stored tab after dispatching event
                localStorage.removeItem('customer-dashboard-active-tab');
            });
        });
    }
    
    // Store active tab before form submit
    window.storeActiveTab = function(tabName) {
        localStorage.setItem('customer-dashboard-active-tab', tabName || 'profile');
    };

    // Listen for tab restoration events
    document.addEventListener('restoreTab', function(e) {
        const targetTab = e.detail.tab;
        // Try to set currentSection via Alpine
        const body = document.body;
        if (body && body.__x && body.__x.$data && body.__x.$data.currentSection !== undefined) {
            body.__x.$data.currentSection = targetTab;
        } else if (typeof Alpine !== 'undefined') {
            // Fallback for when Alpine is available but not yet initialized on body
            Alpine.nextTick(function() {
                const el = document.querySelector('[x-data]');
                if (el && el._x_dataStack && el._x_dataStack[0]) {
                    el._x_dataStack[0].currentSection = targetTab;
                }
            });
        }
    });
})();

// ================================
// Profile Form Submit Handler Enhancement
// ================================
(function() {
    'use strict';
    
    document.addEventListener('DOMContentLoaded', function() {
        var profileForm = document.getElementById('profileForm');
        if (!profileForm) return;

        // FileReader-based preview for immediate feedback when selecting an image
        try {
            var fileInput = document.getElementById('profileImageInput');
            if (fileInput) {
                fileInput.addEventListener('change', function(evt) {
                    var f = this.files && this.files[0];
                    if (!f) return;
                    var allowed = ['image/jpeg','image/png','image/webp'];
                    var maxSize = 3 * 1024 * 1024; // 3MB
                    if (allowed.indexOf(f.type) === -1) {
                        window.showError && window.showError('Geçersiz dosya türü. JPG, PNG veya WEBP gerekli.');
                        return;
                    }
                    if (f.size > maxSize) {
                        window.showError && window.showError('Dosya çok büyük. Maksimum 3MB.');
                        return;
                    }
                    
                    // Clear old cached profile image from localStorage to prevent 404 errors
                    try {
                        localStorage.removeItem('carwash_profile_image');
                        localStorage.removeItem('carwash_profile_image_ts');
                    } catch (e) { /* ignore */ }
                    
                    var reader = new FileReader();
                    reader.onload = function(eu) {
                        var preview = document.getElementById('profileImagePreview');
                        if (preview) {
                            preview.src = eu.target.result;
                            preview.onerror = null; // Clear any existing error handlers
                        }
                    };
                    reader.readAsDataURL(f);
                });
            }
        } catch (err) {
            console.warn('Profile preview handler init failed', err);
        }

        profileForm.addEventListener('submit', function(e) {
            e.preventDefault(); // Always prevent default for AJAX handling
            
            // Store current tab before submit for persistence
            window.storeActiveTab && window.storeActiveTab('profile');
            
            // Create FormData and submit via AJAX
            var formData = new FormData(profileForm);
            
            // Add loading state
            var submitBtn = profileForm.querySelector('button[type="submit"]');
            var originalText = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Kaydediliyor...';
            
            // AJAX submission - use form action attribute explicitly
            fetch(profileForm.getAttribute('action'), {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('HTTP ' + response.status + ': ' + response.statusText);
                }
                return response.json();
            })
            .then(result => {
                if (result.success) {
                    // Success: Show Turkish success message with extended duration (3.5 seconds)
                    // This ensures the message is fully visible before page reload
                    if (window.showGlobalToast) {
                        window.showGlobalToast('Bilgileriniz başarıyla güncellendi ✓ Sayfa yenileniyor...', 'success', 3500);
                    } else if (window.showSuccess) {
                        window.showSuccess('Bilgileriniz başarıyla güncellendi');
                    }

                    // Determine authoritative user payload from response
                    var payloadUser = result.user || (result.data && result.data.user) || null;

                    // Map server fields into client-friendly keys
                    var mapped = {};
                    if (payloadUser) {
                        mapped.name = payloadUser.name || payloadUser.full_name || payloadUser.display_name || '';
                        mapped.email = payloadUser.email || '';
                        mapped.username = payloadUser.username || '';
                        mapped.phone = payloadUser.phone || payloadUser.mobile || '';
                        mapped.home_phone = payloadUser.home_phone || payloadUser.homePhone || '';
                        mapped.national_id = payloadUser.national_id || payloadUser.nationalId || '';
                        mapped.driver_license = payloadUser.driver_license || payloadUser.driverLicense || '';
                        mapped.city = payloadUser.city || payloadUser.profile_city || payloadUser.profileCity || '';
                        mapped.address = payloadUser.address || payloadUser.profile_address || payloadUser.profileAddress || '';
                        // profile image can be stored under several keys on the server
                        mapped.profile_image = payloadUser.profile_image_up || payloadUser.profile_img || payloadUser.profile_image || (result.data && result.data.image) || result.profile_image || '';
                    } else {
                        // Fallback to any direct image value returned
                        mapped.profile_image = result.profile_image || (result.data && result.data.image) || '';
                    }

                    // Keep the canonical value in sync for future fallback calls
                    try {
                        window.CARWASH = window.CARWASH || {};
                        window.CARWASH.profile = window.CARWASH.profile || {};
                        if (mapped.profile_image) {
                            window.CARWASH.profile.canonical = mapped.profile_image;
                            window.CARWASH.profile.ts = Date.now();
                        }
                    } catch (e) { /* ignore */ }

                    // Update profile images using refresh helper (adds cache-buster)
                    if (window.refreshProfileImages) {
                        window.refreshProfileImages(mapped.profile_image || null);
                    }

                    // Update Alpine profileSection state deterministically using returned user payload
                    try {
                        var alpineEl = document.querySelector('[x-data*="profileSection"]');
                        var updated = false;
                        if (alpineEl) {
                            // Alpine v3 exposes __x and $data
                            try {
                                if (alpineEl.__x && alpineEl.__x.$data) {
                                    if (typeof alpineEl.__x.$data.updateProfile === 'function') {
                                        alpineEl.__x.$data.updateProfile(mapped);
                                        alpineEl.__x.$data.editMode = false;
                                        updated = true;
                                    }
                                }
                            } catch (err) { /* continue */ }

                            // Alpine v2 or other builds may attach _x_dataStack
                            try {
                                if (!updated && alpineEl._x_dataStack && alpineEl._x_dataStack[0]) {
                                    var d = alpineEl._x_dataStack[0];
                                    if (typeof d.updateProfile === 'function') {
                                        d.updateProfile(mapped);
                                        d.editMode = false;
                                        updated = true;
                                    }
                                }
                            } catch (err) { /* ignore */ }
                        }

                        // If no Alpine instance found, update global factory state if available
                        if (!updated && window.profileSection && typeof window.profileSection === 'function') {
                            try { window.profileSection().updateProfile(mapped); window.profileSection().editMode = false; } catch (err) { /* ignore */ }
                        }
                    } catch (e) {
                        console.warn('Could not update Alpine profileData from AJAX response', e);
                    }

                    // Emit an event so other listeners can react (close modal, refresh UI)
                    try {
                        document.dispatchEvent(new CustomEvent('profile:update:success', { detail: mapped }));
                    } catch (e) { /* ignore */ }

                    // Also restore the profile tab explicitly (keeps the user on Profile view)
                    try {
                        document.dispatchEvent(new CustomEvent('restoreTab', { detail: { tab: 'profile' } }));
                    } catch (e) { /* ignore */ }

                    // Automatically reload page after 3 seconds to show updated data
                    // This delay ensures user can fully read the success notification
                    // Note: This setTimeout is intentional for UX and does not cause performance issues
                    setTimeout(function() {
                        // Store that we're reloading from a successful update
                        try {
                            sessionStorage.setItem('profile_update_success', 'true');
                        } catch (e) { /* ignore */ }
                        
                        // Reload the page to fetch fresh data
                        window.location.reload();
                    }, 3000);
                } else {
                    // Error: Show in global toast if requested, otherwise in form
                    if (result.show_global_error && window.showError) {
                        window.showError(result.message || 'Error updating profile');
                        // Close edit mode so global error is visible
                        var alpineEl = document.querySelector('[x-data*="profileSection"]');
                        if (alpineEl && alpineEl._x_dataStack && alpineEl._x_dataStack[0]) {
                            alpineEl._x_dataStack[0].editMode = false;
                        }
                    } else {
                        // Show error in form
                        showFormErrors([result.message || 'Error updating profile']);
                    }
                }
            })
            .catch(error => {
                console.error('Profile update error:', error);
                // Try to get response text for debugging
                if (error.message.includes('HTTP')) {
                    console.error('HTTP error details:', error.message);
                }
                if (window.showError) {
                    window.showError('Network error occurred: ' + error.message);
                } else {
                    showFormErrors(['Network error occurred: ' + error.message]);
                }
            })
            .finally(() => {
                // Restore button state
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            });
        });
        
        // Helper function to show form errors (build HTML once to avoid layout thrashing)
        function showFormErrors(errors) {
            var container = document.getElementById('form-errors-container');
            var list = document.getElementById('form-errors-list');
            if (!container || !list) return;
            if (!errors || !errors.length) {
                // Hide container if no errors
                list.innerHTML = '';
                container.style.display = 'none';
                return;
            }

            // Build markup in a string and write once to minimize reflows
            var html = '';
            for (var i = 0; i < errors.length; i++) {
                var esc = String(errors[i]).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
                html += '<li>' + esc + '</li>';
            }
            list.innerHTML = html;
            container.style.display = 'block';
        }
    });
})();

</script>

<script>
/* ================================
   Scroll-to-Top Button Handler
   Shows button after 200px scroll, smooth scrolls to top on click
   ================================ */
(function(){
    'use strict';
    var SCROLL_THRESHOLD = 200;
    var btn = document.getElementById('scrollTopBtn');
    if (!btn) return;

    var scheduled = false;
    function updateButtonVisibility() {
        if (scheduled) return;
        scheduled = true;
        requestAnimationFrame(function(){
                var sc = window.pageYOffset || document.documentElement.scrollTop || document.body.scrollTop || 0;
                if (sc > SCROLL_THRESHOLD) {
                    btn.classList.add('show');
                    btn.classList.remove('hide');
                } else {
                    btn.classList.add('hide');
                    btn.classList.remove('show');
                }
            scheduled = false;
        });
    }

    // Event listeners
    document.addEventListener('scroll', updateButtonVisibility, { passive: true });
    window.addEventListener('resize', updateButtonVisibility);

    // Click handler - smooth scroll
    btn.addEventListener('click', function(){
        try {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        } catch(err) {
            window.scrollTo(0, 0);
        }
    });

    // Keyboard activation
    btn.addEventListener('keydown', function(e){
        if (e.key === 'Enter' || e.key === ' ' || e.key === 'Spacebar') {
            e.preventDefault();
            btn.click();
        }
    });

    // Initial check
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', updateButtonVisibility, { once: true });
    } else {
        requestAnimationFrame(updateButtonVisibility);
    }
})();
</script>

<?php 
// Include the universal footer
echo '<!-- Footer include reached -->';
include __DIR__ . '/../includes/footer.php';
?>

<script>
// Temporary: suppress noisy extension messaging error that originates from
// browser extensions returning `true` for async responses but never calling
// sendResponse. This is an external extension bug; the correct fix is to
// disable or update the offending extension. This handler only suppresses
// the exact known message so other unhandled rejections still surface.
window.addEventListener('unhandledrejection', function (ev) {
    try {
        var reason = ev && ev.reason;
        var msg = '';
        if (!reason) return;
        if (typeof reason === 'string') msg = reason;
        else if (reason && reason.message) msg = reason.message;
        else msg = String(reason);

        if (msg && msg.indexOf('A listener indicated an asynchronous response by returning true') !== -1) {
            // Prevent the default unhandledrejection logging for this specific message
            try { ev.preventDefault && ev.preventDefault(); } catch (e) {}
            // Optionally log at info level so developer knows it was suppressed
            if (console && console.info) console.info('Suppressed extension messaging error:', msg);
        }
    } catch (e) {
        // never throw from this handler
    }
});
</script>

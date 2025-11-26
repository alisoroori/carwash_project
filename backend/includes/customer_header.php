<?php
/**
 * Customer Header (Self-contained)
 * Outputs a complete, Turkish-language customer dashboard header.
 * Safe to include on Customer dashboard pages as the primary header.
 */

if (session_status() !== PHP_SESSION_ACTIVE) session_start();
// Lightweight logging helper for header; prefer App\Classes\Logger::debug if available
if (!function_exists('cw_log_debug')) {
    function cw_log_debug($m) {
        if (class_exists('\\App\\Classes\\Logger') && method_exists('\\App\\Classes\\Logger', 'debug')) {
            try { \App\Classes\Logger::debug($m); } catch (Throwable $__e) { error_log($m); }
        } else {
            error_log($m);
        }
    }
}
$sh = $_SESSION['user']['profile_image'] ?? 'NULL';
$sp = $_SESSION['profile_image'] ?? 'NULL';
cw_log_debug("[customer_header] init: user_id=" . ($user_id ?? 'NULL') . " session.user.profile_image={$sh}; session.profile_image={$sp}");

if (!isset($dashboard_type)) $dashboard_type = 'customer';
if (!isset($page_title)) $page_title = 'Müşteri Paneli - CarWash';
if (!isset($current_page)) $current_page = 'dashboard';

$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$base_url = $base_url ?? ($protocol . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/carwash_project');

$user_id = $_SESSION['user_id'] ?? null;
$user_name = $_SESSION['name'] ?? $_SESSION['full_name'] ?? 'Kullanıcı';
$user_email = $_SESSION['email'] ?? '';
$user_role = $_SESSION['role'] ?? 'customer';

// Attempt auto-fix when rendering header to ensure session consistency across pages
$profileFixHelper = __DIR__ . '/profile_auto_fix.php';
if (file_exists($profileFixHelper) && !empty($user_id)) {
    try {
        require_once $profileFixHelper;
        if (function_exists('autoFixProfileImage')) {
            $hf = autoFixProfileImage($user_id);
            cw_log_debug('[profile_auto_fix] header: ' . $hf);
        }
    } catch (Exception $e) {
        cw_log_debug('[profile_auto_fix] header include failed: ' . $e->getMessage());
    }
}

// Header logo is always MyCar logo (fixed branding)
$logo_src = $base_url . '/backend/logo01.png';
$raw = $_SESSION['logo_path'] ?? null;
$sidebar_logo_src = $base_url . '/backend/logo01.png';
// Normalize stored session value: prefer filename-only. If a URL or path is stored, extract basename.
if (!empty($raw)) {
    // If the session contains a full web path or absolute path, reduce to filename
    if (preg_match('#(/|\\\\|https?://)#i', $raw)) {
        $basename = basename($raw);
        if (!empty($basename)) {
            $_SESSION['logo_path'] = $basename;
            $raw = $basename;
        }
    }

    // Build candidate public URL from filename and verify it exists on disk
    $candidate = $base_url . '/backend/uploads/business_logo/' . ltrim($raw, '/');
    $docRoot = rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '\\/') ;
    $filePath = $docRoot . '/carwash_project/backend/uploads/business_logo/' . ltrim($raw, '/');
    if (file_exists($filePath)) {
        $sidebar_logo_src = $candidate;
    } else {
        // try alternate legacy locations and promote if found
        $alts = [
            $docRoot . '/carwash_project/backend/auth/uploads/logos/' . $raw,
            $docRoot . '/carwash_project/backend/uploads/' . $raw,
            $docRoot . '/carwash_project/backend/uploads/profile_images/' . $raw,
        ];
        $found = false;
        foreach ($alts as $a) {
            if (file_exists($a)) {
                $web = str_replace($docRoot, '', $a);
                if ($web === '' || $web[0] !== '/') $web = '/' . ltrim($web, '/');
                // store filename only for consistency
                $_SESSION['logo_path'] = basename($a);
                // Construct public URL pointing to canonical business_logo when possible
                $sidebar_logo_src = $base_url . '/backend/uploads/business_logo/' . basename($a);
                $found = true;
                break;
            }
        }
        if (!$found) {
            $sidebar_logo_src = $base_url . '/backend/logo01.png';
            unset($_SESSION['logo_path']);
        }
    }
}
$profile_src = null;

// Prefer common session locations for profile image (robust across auth flows)
if (!empty($_SESSION['user']) && !empty($_SESSION['user']['profile_image'])) {
    $profile_src = $_SESSION['user']['profile_image'];
} elseif (!empty($_SESSION['user_profile_image'])) {
    $profile_src = $_SESSION['user_profile_image'];
} elseif (!empty($_SESSION['profile_image'])) {
    $profile_src = $_SESSION['profile_image'];
} elseif (!empty($user_profile_image)) {
    // Some dashboards provide $user_profile_image variable prior to including header
    $profile_src = $user_profile_image;
}

// Normalize and validate profile image path; prefer absolute URL if provided,
// otherwise try canonical upload directory. If missing or unreadable, fall back.
$default_avatar = $base_url . '/frontend/images/default-avatar.svg';
if (empty($profile_src)) {
    $profile_src = $default_avatar;
} else {
    // If it's an absolute URL or starts with a slash, accept it and verify readability when possible
    if (preg_match('#^(https?://)#i', $profile_src) || strpos($profile_src, '/') === 0) {
        // For local absolute paths, convert to full URL if needed
        if (strpos($profile_src, 'http') !== 0 && strpos($profile_src, '/') === 0) {
            $profile_src_candidate = rtrim($base_url, '\/') . $profile_src;
        } else {
            $profile_src_candidate = $profile_src;
        }
        $profile_src = $profile_src_candidate;
        // If file exists locally, prefer that. Build filesystem path when possible.
        $parsed = parse_url($profile_src);
            if (!empty($parsed['path'])) {
            $docRoot = rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '\\/');
            $filePath = $docRoot . $parsed['path'];
            if (file_exists($filePath) && is_readable($filePath)) {
                // ok
            } else {
                // Fallback to default avatar if file not readable
                    cw_log_debug('[customer_header] Profile image not readable or missing: ' . $filePath);
                $profile_src = $default_avatar;
                unset($_SESSION['profile_image']);
                unset($_SESSION['user']['profile_image']);
            }
        }
    } else {
        // Relative filename stored in session - assume uploads directory
        $filename = basename($profile_src);
        $uploadsWeb = $base_url . '/backend/auth/uploads/profiles/' . $filename;
        $docRoot = rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '\\/');
        $filePath = $docRoot . '/carwash_project/backend/auth/uploads/profiles/' . $filename;
        if (file_exists($filePath) && is_readable($filePath)) {
            $profile_src = $uploadsWeb;
        } else {
            // Try legacy locations
            $alts = [
                $docRoot . '/carwash_project/backend/auth/uploads/profiles/' . $filename,
                $docRoot . '/carwash_project/backend/uploads/' . $filename,
                $docRoot . '/carwash_project/backend/auth/uploads/' . $filename,
            ];
            $found = false;
            foreach ($alts as $a) {
                if (file_exists($a) && is_readable($a)) {
                    $web = str_replace($docRoot, '', $a);
                    if ($web === '' || $web[0] !== '/') $web = '/' . ltrim($web, '/');
                    $profile_src = (isset($base_url) ? rtrim($base_url, '\\/') : '') . $web;
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                cw_log_debug('[customer_header] Profile image file not found in expected locations: ' . $filename);
                $profile_src = $default_avatar;
                unset($_SESSION['profile_image']);
                unset($_SESSION['user']['profile_image']);
            }
        }
    }
}

// Force a per-session timestamp used to bust cache after login. If the application
// sets this value on login, it will be preserved; otherwise initialize on first page load.
if (empty($_SESSION['profile_image_ts'])) {
    $_SESSION['profile_image_ts'] = time();
}

// Append timestamp param to force cache reload when newer image is uploaded or after login
$profile_src_with_ts = $profile_src . (strpos($profile_src, '?') === false ? '?ts=' . intval($_SESSION['profile_image_ts'] ?? time()) : '&ts=' . intval($_SESSION['profile_image_ts'] ?? time()));

// Header profile src using session variable directly (single source-of-truth)
// Prefer `$_SESSION['user']['profile_image']` when available; fall back to default avatar.
// Use a centralized handler to serve profile images. This ensures a single
// retrieval point and consistent cache-busting via `?ts=` parameter.
$ts = intval($_SESSION['profile_image_ts'] ?? time());
if (!empty($user_id)) {
    $header_profile_src = rtrim($base_url, '\/') . '/backend/profile_image_handler.php?user_id=' . intval($user_id) . '&ts=' . $ts;
} else {
    $header_profile_src = $default_avatar;
}

// Current logged-in user's display name and email (used in header)
$user_name = $_SESSION['name'] ?? $_SESSION['user_name'] ?? 'Kullanıcı';
$user_email = $_SESSION['email'] ?? '';

// URLs
$home_url = $base_url . '/backend/index.php';
$about_url = $base_url . '/backend/about.php';
$contact_url = $base_url . '/backend/contact.php';
$logout_url = $base_url . '/backend/includes/logout.php';
$dashboard_url = $base_url . '/backend/dashboard/Customer_Dashboard.php';

// ---- Workplace status AJAX handler removed for customer header ----

// Load current workplace status removed for customer header

?>
<!-- Customer Header Fragment - Outputs only the header HTML with inline styles -->
<style>
    :root{ --header-height:80px; }
    .customer-header{ position:fixed; top:0; left:0; right:0; z-index:1000; background:#1f2937; color:#fff; backdrop-filter: blur(8px); border-bottom:1px solid rgba(255,255,255,0.06); height:var(--header-height); }
    .customer-container{ max-width:1400px; margin:0 auto; padding:0 1rem; }
    .customer-row{ display:flex; align-items:center; justify-content:space-between; gap:1rem; height:var(--header-height); }
    .dashboard-logo { display:flex; align-items:center; gap:0.75rem; color:#fff; text-decoration:none; }
    .dashboard-logo .logo-image{ width:48px; height:48px; object-fit:cover; border-radius:8px; }
    .dashboard-logo .logo-text{ font-weight:700; font-size:1.1rem; color:#ffffff; }
    .nav-items{ display:flex; gap:0.5rem; align-items:center; }
    .nav-items:empty { display: none; }
    .nav-link{ color:rgba(255,255,255,0.9); padding:0.5rem 0.75rem; border-radius:8px; text-decoration:none; font-weight:600; }
    .nav-link:hover{ background:rgba(255,255,255,0.04); }
    .user-menu{ display:flex; align-items:center; gap:0.75rem; position:relative; }
    .user-avatar{ width:44px; height:44px; border-radius:8px; overflow:hidden; background:#111827; display:flex; align-items:center; justify-content:center; }
    .user-avatar img{ width:100%; height:100%; object-fit:cover; display:block; }
    .user-info{ display:flex; flex-direction:column; align-items:flex-start; color:#fff; font-size:0.85rem; }
    .user-name{ font-weight:700; }
    .user-role{ font-size:0.75rem; opacity:0.85; }
    .user-button{ background:transparent; border:0; color:#fff; display:flex; gap:0.5rem; align-items:center; cursor:pointer; }
    .dropdown{ position:absolute; right:0; top:calc(var(--header-height)); min-width:220px; background:#fff; color:#111827; border-radius:12px; box-shadow:0 10px 30px rgba(0,0,0,0.2); display:none; overflow:hidden; }
    .dropdown.active{ display:block; }
    .dropdown .item{ padding:0.75rem 1rem; display:flex; gap:0.75rem; align-items:center; text-decoration:none; color:inherit; }
    .dropdown .item:hover{ background:#f3f4f6; }
    /* Mobile */
    /* Removed header mobile hamburger styles - hamburger now lives inside main content on mobile */
    .mobile-toggle{ display:none; }
    @media (max-width:900px){ .nav-items{ display:none; } .user-info{ display:none; } }
</style>

<header class="customer-header" role="banner">
    <div class="customer-container">
        <div class="customer-row">
            <div style="display:flex;align-items:center;gap:1rem;">
                <a href="<?php echo $dashboard_url; ?>" class="dashboard-logo" aria-label="MyCar Müşteri Paneli">
                    <img src="<?php echo htmlspecialchars($logo_src); ?>" alt="MyCar" class="logo-image" loading="lazy">
                    <div>
                        <div class="logo-text">MYCAR</div>
                        <div style="font-size:0.75rem; opacity:0.85;">Müşteri Paneli</div>
                    </div>
                </a>
            </div>

            <nav class="nav-items" aria-label="Ana Navigasyon">
                <!-- Primary nav intentionally left empty for customer header per configuration -->
            </nav>

            <div style="display:flex;align-items:center;gap:0.5rem;">
                <!-- Mobile hamburger removed from header for unified mobile UX -->

                <!-- Workplace Status Toggle removed for customer header -->

                <?php
                    // Inlined profile header fragment (copied from customer header)
                    // Expects variables in scope: $user_name, $user_email, $profile_src, $home_url, $logout_url
                ?>

                <?php
                // Profile header fragment - shared between Customer and Seller dashboards
                // Expects variables in scope: $user_name, $user_email, $profile_src, $home_url, $logout_url
                ?>

                <!-- Profile Header Fragment -->
                <div x-data="{ open: false }" class="relative" x-cloak>
                    <button @click="open = !open" @keydown.escape="open = false" @click.away="open = false"
                        class="flex items-center space-x-2 p-2 rounded-lg hover:bg-gray-100 transition-colors focus:outline-none"
                        :aria-expanded="open.toString()" aria-haspopup="true">

                        <div id="headerProfileContainer" class="sidebar-profile-container rounded-full overflow-hidden shadow-sm flex items-center justify-center bg-gradient-to-br from-blue-500 to-purple-600">
                            <img id="headerProfileImage" src="<?php echo htmlspecialchars($header_profile_src, ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($user_name); ?>" class="object-cover w-full h-full" onerror="this.onerror=null;this.src='<?php echo $default_avatar; ?>';">
                            <div id="userAvatarFallback" class="text-white font-semibold text-sm" style="display:none; align-items:center; justify-content:center; width:100%; height:100%;">
                                <?php echo strtoupper(substr($user_name,0,1)); ?>
                            </div>
                        </div>

                        <span id="headerUserNameDisplay" class="hidden md:block text-sm font-medium text-gray-700 max-w-[150px] truncate"><?php echo htmlspecialchars($user_name); ?></span>
                        <i class="fas fa-chevron-down text-xs text-gray-400 hidden md:block"></i>
                    </button>

                    <div x-show="open"
                         x-transition:enter="transition ease-out duration-200"
                         x-transition:enter-start="opacity-0 scale-95 transform -translate-y-2"
                         x-transition:enter-end="opacity-100 scale-100 transform translate-y-0"
                         x-transition:leave="transition ease-in duration-150"
                         x-transition:leave-start="opacity-100 scale-100"
                         x-transition:leave-end="opacity-0 scale-95"
                         class="absolute right-0 mt-2 w-56 bg-white rounded-xl shadow-2xl ring-1 ring-black ring-opacity-5 overflow-hidden z-50"
                         style="display:none;">
                        <div class="px-4 py-3 bg-gradient-to-r from-blue-50 to-purple-50 border-b border-gray-200">
                            <p class="text-sm font-semibold text-gray-900 truncate"><?php echo htmlspecialchars($user_name); ?></p>
                            <p class="text-xs text-gray-600 truncate"><?php echo htmlspecialchars($user_email); ?></p>
                        </div>

                        <div class="py-2">
                            <a href="#profile" @click="open = false; if(typeof window !== 'undefined' && window.document){ try{ document.body.__x && (document.body.__x.$data.currentSection = 'profile'); }catch(e){} }" class="flex items-center px-4 py-2.5 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700 transition-colors">
                                <i class="fas fa-user-circle w-5 text-blue-600 mr-3"></i>
                                <span>Profil</span>
                            </a>
                            <a href="<?php echo htmlspecialchars($home_url); ?>" class="flex items-center px-4 py-2.5 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700 transition-colors">
                                <i class="fas fa-home w-5 text-blue-600 mr-3"></i>
                                <span>Ana Sayfa</span>
                            </a>
                        </div>

                        <div class="border-t border-gray-200">
                            <a href="<?php echo htmlspecialchars($logout_url); ?>" class="flex items-center px-4 py-2.5 text-sm text-red-600 hover:bg-red-50 transition-colors font-medium">
                                <i class="fas fa-sign-out-alt w-5 mr-3"></i>
                                <span>Çıkış Yap</span>
                            </a>
                        </div>
                    </div>

                    <script>
                    // Fallback toggling for pages without Alpine.js
                    (function(){
                        if (typeof Alpine !== 'undefined') return; // Alpine present - Alpine handles toggle
                        // Find the root of this fragment
                        var root = (function(el){ return el && el.parentElement ? el.parentElement : document.body; })(document.currentScript ? document.currentScript.parentElement : null);
                        // If multiple fragments exist, the below will attach to all
                        var roots = document.querySelectorAll('[x-cloak]');
                        roots.forEach(function(r){
                            var btn = r.querySelector('button[aria-haspopup]');
                            var menu = r.querySelector('[x-show]');
                            if (!btn || !menu) return;
                            btn.addEventListener('click', function(ev){ ev.stopPropagation(); var sh = getComputedStyle(menu).display !== 'none'; if(sh){ menu.style.display='none'; btn.setAttribute('aria-expanded','false'); } else { menu.style.display='block'; btn.setAttribute('aria-expanded','true'); } });
                            document.addEventListener('click', function(evt){ if (!r.contains(evt.target)){ menu.style.display='none'; btn.setAttribute('aria-expanded','false'); } });
                            document.addEventListener('keydown', function(evt){ if (evt.key === 'Escape'){ menu.style.display='none'; btn.setAttribute('aria-expanded','false'); } });
                        });
                    })();
                    </script>
                </div>
            </div>
        </div>
    </div>
</header>

<!-- Mobile menu panel -->
<div id="mobileMenu" style="display:none; position:fixed; top:var(--header-height); left:0; right:0; bottom:0; background:rgba(255,255,255,0.97); z-index:1190; overflow:auto;">
    <div style="padding:1rem;">
        <div style="display:flex;align-items:center;gap:0.75rem;margin-bottom:1rem;">
            <img id="mobileMenuAvatar" src="<?php echo htmlspecialchars($header_profile_src, ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($user_name); ?>" style="width:48px;height:48px;border-radius:50%;object-fit:cover;" onerror="this.onerror=null;this.src='<?php echo $default_avatar; ?>';">
            <div>
                <div id="mobileMenuUserName" style="font-weight:700"><?php echo htmlspecialchars($user_name); ?></div>
                <div id="mobileMenuUserEmail" style="font-size:0.85rem;opacity:0.8"><?php echo htmlspecialchars($user_email); ?></div>
            </div>
            <button onclick="toggleMobileMenu(true)" style="margin-left:auto;background:transparent;border:0;font-size:18px"><i class="fas fa-times"></i></button>
        </div>

        <nav style="display:flex;flex-direction:column;gap:0.5rem;">
            <!-- Mobile nav items removed per request; keep logout visible -->
            <a href="<?php echo $logout_url; ?>" class="nav-link" style="color:#ef4444;" onclick="toggleMobileMenu(true)">Çıkış Yap</a>
        </nav>
    </div>
</div>

<script>
function toggleDropdown(btn){
    var menu = document.getElementById('customerUserDropdown');
    if(!menu) return;
    var showing = getComputedStyle(menu).display !== 'none';
    if(showing) {
        menu.style.display = 'none';
        menu.setAttribute('aria-hidden','true');
        document.getElementById('customerUserToggle')?.setAttribute('aria-expanded','false');
    } else {
        menu.style.display = 'block';
        menu.setAttribute('aria-hidden','false');
        document.getElementById('customerUserToggle')?.setAttribute('aria-expanded','true');
    }
}

// Header mobile toggle removed; mobile hamburger lives in main content.

// compute header height dynamically
function updateHeaderHeight(){
    var h = document.querySelector('.customer-header') ? Math.ceil(document.querySelector('.customer-header').getBoundingClientRect().height) : 64;
    document.documentElement.style.setProperty('--header-height', h + 'px');
    document.body.style.paddingTop = h + 'px';
}
window.addEventListener('load', updateHeaderHeight);
window.addEventListener('resize', updateHeaderHeight);
</script>

<!-- DOM-ready safety: update header image after upload if the img is empty or using placeholder -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    try {
        const headerImg = document.getElementById('headerProfileImage');
        if (!headerImg) return;
        const current = headerImg.getAttribute('src') || '';
        const isPlaceholder = current.indexOf('default-avatar') !== -1 || current.indexOf('default-user.png') !== -1 || current.indexOf('placeholder') !== -1;
        if (!current || isPlaceholder) {
            headerImg.src = '<?php echo htmlspecialchars($header_profile_src); ?>';
        }
    } catch (e) { /* ignore errors */ }
});
</script>

<!-- Expose canonical profile image URL for other scripts to consume -->
<script>
    (function(){
        window.CARWASH = window.CARWASH || {};
        window.CARWASH.profile = window.CARWASH.profile || {};
        // Use json_encode to safely escape the PHP string for JS usage
        window.CARWASH.profile.canonical = <?php echo json_encode($header_profile_src); ?>;
        window.getCanonicalProfileImage = function(){ return window.CARWASH.profile.canonical; };
    })();
</script>




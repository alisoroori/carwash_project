<?php
/**
 * Seller Header (Self-contained)
 * Outputs a complete, Turkish-language seller/carwash dashboard header.
 * Safe to include on Carwash dashboard pages as the primary header.
 */

if (session_status() !== PHP_SESSION_ACTIVE) session_start();

if (!isset($dashboard_type)) $dashboard_type = 'carwash';
if (!isset($page_title)) $page_title = 'İşletme Paneli - MyCar';
if (!isset($current_page)) $current_page = 'dashboard';

$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$base_url = $base_url ?? ($protocol . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/carwash_project');

$user_id = $_SESSION['user_id'] ?? null;
$user_name = $_SESSION['name'] ?? $_SESSION['full_name'] ?? 'Kullanıcı';
$user_email = $_SESSION['email'] ?? '';
$user_role = $_SESSION['role'] ?? 'carwash';

$logo_src = $_SESSION['logo_path'] ?? ($base_url . '/backend/logo01.png');
$profile_src = $_SESSION['profile_image'] ?? ($base_url . '/frontend/images/default-avatar.svg');

// URLs
$home_url = $base_url . '/backend/index.php';
$about_url = $base_url . '/backend/about.php';
$contact_url = $base_url . '/backend/contact.php';
$logout_url = $base_url . '/backend/includes/logout.php';
$dashboard_url = $base_url . '/backend/dashboard/Car_Wash_Dashboard.php';

// ---- Workplace status AJAX handler (allows POST to this file to update status) ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax_workplace_status'])) {
    // Simple auth check
    $uid = $_SESSION['user_id'] ?? null;
    if (!$uid) {
        header('Content-Type: application/json', true, 401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }

    $new = $_POST['ajax_workplace_status'] === 'open' ? 'open' : 'closed';
    // Persist to session immediately
    $_SESSION['workplace_status'] = $new;

    // Attempt DB persistence if Database class exists (best-effort)
    try {
        if (class_exists('App\\Classes\\Database')) {
            $db = App\Classes\Database::getInstance();
            // Try updating `users` table - fallback if schema differs is acceptable
            $db->update('users', ['workplace_status' => $new], ['id' => $uid]);
        }
    } catch (Exception $e) {
        // Ignore DB errors here (status still saved to session)
    }

    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'status' => $new]);
    exit;
}

// Load current workplace status from session or DB (best-effort)
$workplace_status = $_SESSION['workplace_status'] ?? null;
if (empty($workplace_status) && isset($user_id)) {
    try {
        if (class_exists('App\\Classes\\Database')) {
            $db = App\Classes\Database::getInstance();
            $row = $db->fetchOne('SELECT workplace_status FROM users WHERE id = :id', ['id' => $user_id]);
            if ($row && isset($row['workplace_status'])) $workplace_status = $row['workplace_status'];
        }
    } catch (Exception $e) {
        // ignore
    }
}
if (empty($workplace_status)) $workplace_status = 'open';

?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <link rel="stylesheet" href="<?php echo $base_url; ?>/dist/output.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root{ --header-height:64px; }
        body{ margin:0; font-family: Inter, system-ui, -apple-system, 'Segoe UI', Roboto, 'Helvetica Neue', Arial; }
        .seller-header{ position:fixed; top:0; left:0; right:0; z-index:1200; background:#1f2937; color:#fff; backdrop-filter: blur(8px); border-bottom:1px solid rgba(255,255,255,0.06); }
        .seller-container{ max-width:1400px; margin:0 auto; padding:0 1rem; }
        .seller-row{ display:flex; align-items:center; justify-content:space-between; gap:1rem; height:var(--header-height); }
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
        .mobile-toggle{ display:none; }
        @media (max-width:900px){ .nav-items{ display:none; } .mobile-toggle{ display:inline-flex; } .user-info{ display:none; } }
        /* space for header */
        body{ padding-top:var(--header-height); }
        /* Workplace toggle styles */
        .workplace-toggle-container { display:flex; align-items:center; gap:0.75rem; margin-right:1rem; }
        .toggle-label { font-size:0.875rem; font-weight:700; color:#fff; display:block; }
        .toggle-switch { position:relative; display:inline-block; width:56px; height:30px; }
        .toggle-switch input { opacity:0; width:0; height:0; }
        .slider { position:absolute; cursor:pointer; top:0; left:0; right:0; bottom:0; background-color:#ef4444; transition:.25s; border-radius:999px; }
        .slider:before { position:absolute; content:''; height:22px; width:22px; left:4px; bottom:4px; background:white; transition:.25s; border-radius:50%; }
        .toggle-switch input:checked + .slider { background-color:#10b981; }
        .toggle-switch input:checked + .slider:before { transform:translateX(26px); }
        .status-indicator { display:flex; align-items:center; gap:0.5rem; padding:0.25rem 0.6rem; border-radius:999px; font-size:0.85rem; font-weight:700; }
        .status-open { background: rgba(16,185,129,0.12); color:#10b981; border:1px solid rgba(16,185,129,0.18); }
        .status-closed { background: rgba(239,68,68,0.10); color:#ef4444; border:1px solid rgba(239,68,68,0.15); }
        @media (max-width:900px) { .toggle-label{ display:none; } }
    </style>
    <!-- Load Alpine.js if not already present (needed for header animations) -->
    <script>
        if (typeof Alpine === 'undefined') {
            var s = document.createElement('script');
            s.src = 'https://unpkg.com/alpinejs@3.12.0/dist/cdn.min.js';
            s.defer = true;
            document.head.appendChild(s);
        }
    </script>
</head>
<body>

<header class="seller-header" role="banner">
    <div class="seller-container">
        <div class="seller-row">
            <div style="display:flex;align-items:center;gap:1rem;">
                <a href="<?php echo $dashboard_url; ?>" class="dashboard-logo" aria-label="MyCar İşletme Paneli">
                    <img src="<?php echo htmlspecialchars($logo_src); ?>" alt="MyCar" class="logo-image" loading="lazy">
                    <div>
                        <div class="logo-text">MYCAR</div>
                        <div style="font-size:0.75rem; opacity:0.85;">İşletme Paneli</div>
                    </div>
                </a>
            </div>

            <nav class="nav-items" aria-label="Ana Navigasyon">
                <!-- Primary nav intentionally left empty for seller header per configuration -->
            </nav>

            <div style="display:flex;align-items:center;gap:0.5rem;">
                <button class="mobile-toggle" aria-expanded="false" aria-controls="mobileMenu" onclick="toggleMobileMenu(this)">
                    <i class="fas fa-bars" style="color:#fff;font-size:20px"></i>
                </button>

                <!-- Workplace Status Toggle -->
                <div class="workplace-toggle-container" id="workplaceToggleRoot">
                    <div class="status-indicator <?php echo ($workplace_status === 'open') ? 'status-open' : 'status-closed'; ?>" id="workplaceStatusIndicator">
                        <span id="workplaceStatusText"><?php echo ($workplace_status === 'open') ? 'Açık' : 'Kapalı'; ?></span>
                    </div>

                    <label class="toggle-switch" title="İşletme Durumu">
                        <input type="checkbox" id="workplaceStatusToggle" <?php echo ($workplace_status === 'open') ? 'checked' : ''; ?> aria-checked="<?php echo ($workplace_status === 'open') ? 'true' : 'false'; ?>">
                        <span class="slider"></span>
                    </label>
                </div>

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

                        <div id="headerProfileContainer" class="rounded-full overflow-hidden shadow-sm flex items-center justify-center bg-gradient-to-br from-blue-500 to-purple-600" style="width:40px;height:40px;">
                            <img id="userAvatarTop" src="<?php echo htmlspecialchars($profile_src); ?>" alt="<?php echo htmlspecialchars($user_name); ?>" class="object-cover w-full h-full" style="border-radius:50%;" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex'">
                            <div id="userAvatarFallback" class="text-white font-semibold text-sm" style="display:none; align-items:center; justify-content:center; width:100%; height:100%;">
                                <?php echo strtoupper(substr($user_name,0,1)); ?>
                            </div>
                        </div>

                        <span class="hidden md:block text-sm font-medium text-gray-700 max-w-[150px] truncate"><?php echo htmlspecialchars($user_name); ?></span>
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
            <img src="<?php echo htmlspecialchars($profile_src); ?>" alt="<?php echo htmlspecialchars($user_name); ?>" style="width:48px;height:48px;border-radius:50%;object-fit:cover;">
            <div>
                <div style="font-weight:700"><?php echo htmlspecialchars($user_name); ?></div>
                <div style="font-size:0.85rem;opacity:0.8"><?php echo htmlspecialchars($user_email); ?></div>
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
    var menu = document.getElementById('sellerUserDropdown');
    if(!menu) return;
    var showing = getComputedStyle(menu).display !== 'none';
    if(showing) {
        menu.style.display = 'none';
        menu.setAttribute('aria-hidden','true');
        document.getElementById('sellerUserToggle')?.setAttribute('aria-expanded','false');
    } else {
        menu.style.display = 'block';
        menu.setAttribute('aria-hidden','false');
        document.getElementById('sellerUserToggle')?.setAttribute('aria-expanded','true');
    }
}

function toggleMobileMenu(close){
    var mm = document.getElementById('mobileMenu');
    if(!mm) return;
    if(close === true){ mm.style.display = 'none'; document.querySelector('.mobile-toggle') && document.querySelector('.mobile-toggle').setAttribute('aria-expanded','false'); return; }
    var showing = getComputedStyle(mm).display !== 'none';
    mm.style.display = showing ? 'none' : 'block';
    document.querySelector('.mobile-toggle') && document.querySelector('.mobile-toggle').setAttribute('aria-expanded', (!showing).toString());
}

// compute header height dynamically
function updateHeaderHeight(){
    var h = document.querySelector('.seller-header') ? Math.ceil(document.querySelector('.seller-header').getBoundingClientRect().height) : 64;
    document.documentElement.style.setProperty('--header-height', h + 'px');
    document.body.style.paddingTop = h + 'px';
}
window.addEventListener('load', updateHeaderHeight);
window.addEventListener('resize', updateHeaderHeight);
</script>

<script>
// Workplace toggle behaviour: sends AJAX POST to this file to persist status
(function(){
    var toggle = document.getElementById('workplaceStatusToggle');
    var indicator = document.getElementById('workplaceStatusIndicator');
    var statusText = document.getElementById('workplaceStatusText');
    if (!toggle) return;

    function setUI(state){
        if(state === 'open'){
            toggle.checked = true;
            indicator.classList.remove('status-closed');
            indicator.classList.add('status-open');
            statusText.textContent = 'Açık';
            toggle.setAttribute('aria-checked','true');
        } else {
            toggle.checked = false;
            indicator.classList.remove('status-open');
            indicator.classList.add('status-closed');
            statusText.textContent = 'Kapalı';
            toggle.setAttribute('aria-checked','false');
        }
    }

    toggle.addEventListener('change', function(){
        var desired = toggle.checked ? 'open' : 'closed';
        // Optimistically update UI
        setUI(desired);

        var form = new FormData();
        form.append('ajax_workplace_status', desired);

        fetch('<?php echo htmlspecialchars($base_url . "/backend/includes/seller_header.php"); ?>', {
            method: 'POST',
            credentials: 'same-origin',
            body: form
        }).then(function(resp){
            return resp.json();
        }).then(function(json){
            if(!json || !json.success){
                // Revert UI on failure
                setUI(json && json.status ? json.status : (desired === 'open' ? 'closed' : 'open'));
                console.warn('Failed to save workplace status', json);
            }
        }).catch(function(err){
            // Revert UI
            setUI(desired === 'open' ? 'closed' : 'open');
            console.error('Error saving workplace status', err);
        });
    });
})();
</script>


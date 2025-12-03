<?php
/**
 * Seller Header (Self-contained)
 * Outputs a complete, Turkish-language seller/carwash dashboard header.
 * Safe to include on Carwash dashboard pages as the primary header.
 */

if (session_status() !== PHP_SESSION_ACTIVE) session_start();

// Load bootstrap early so Database class is available for AJAX handlers
require_once __DIR__ . '/bootstrap.php';

// ---- Workplace status AJAX handler (POST to update, GET to fetch current) ----
// These handlers must run BEFORE any HTML output
// POST expected fields: 'ajax_workplace_status' (optional), 'ajax_is_active' (optional)
// GET: provide ?ajax_get_workplace_status=1 to receive JSON { status, is_active }
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['ajax_get_workplace_status'])) {
    $uid = $_SESSION['user_id'] ?? null;
    if (!$uid) {
        header('Content-Type: application/json', true, 401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }
    try {
        $db = App\Classes\Database::getInstance();
        $cw = $db->fetchOne('SELECT status, COALESCE(is_active,0) AS is_active FROM carwashes WHERE user_id = :uid LIMIT 1', ['uid' => $uid]);
        $status = $cw['status'] ?? null;
        $isActive = isset($cw['is_active']) ? (int)$cw['is_active'] : 0;
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'status' => $status, 'is_active' => $isActive]);
        exit;
    } catch (Exception $e) {
        header('Content-Type: application/json', true, 500);
        echo json_encode(['success' => false, 'message' => 'DB error: ' . $e->getMessage()]);
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['ajax_workplace_status']) || isset($_POST['ajax_is_active']))) {
    $uid = $_SESSION['user_id'] ?? null;
    if (!$uid) {
        header('Content-Type: application/json', true, 401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit;
    }
    
    // Determine new status and is_active values
    $isActiveVal = null;
    if (isset($_POST['ajax_is_active'])) {
        $isActiveVal = (int)$_POST['ajax_is_active'] ? 1 : 0;
    }

    if ($isActiveVal !== null) {
        $new = ($isActiveVal === 1) ? 'Açık' : 'Kapalı';
    } else {
        $incoming = trim((string)($_POST['ajax_workplace_status'] ?? ''));
        if ($incoming === '') {
            header('Content-Type: application/json', true, 400);
            echo json_encode(['success' => false, 'message' => 'Empty status']);
            exit;
        }
        $incomingLower = strtolower($incoming);
        $openVariants = ['açık', 'acik', 'open', 'active'];
        $closedVariants = ['kapalı', 'kapali', 'closed', 'inactive'];
        if (in_array($incomingLower, $openVariants, true)) {
            $new = 'Açık';
            $isActiveVal = 1;
        } elseif (in_array($incomingLower, $closedVariants, true)) {
            $new = 'Kapalı';
            $isActiveVal = 0;
        } else {
            header('Content-Type: application/json', true, 400);
            echo json_encode(['success' => false, 'message' => 'Invalid status token', 'token' => $incoming]);
            exit;
        }
    }

    // Persist to session
    $_SESSION['workplace_status'] = $new;

    // Persist to DB
    try {
        $db = App\Classes\Database::getInstance();
        $pdo = $db->getPdo();
        $upd = $pdo->prepare('UPDATE carwashes SET status = :status, is_active = :is_active, updated_at = NOW() WHERE user_id = :uid');
        $upd->execute(['status' => $new, 'is_active' => $isActiveVal, 'uid' => $uid]);
        $rowCount = $upd->rowCount();
        error_log("[seller_header] Toggle update: user_id={$uid}, status={$new}, is_active={$isActiveVal}, rows_affected={$rowCount}");
        
        header('Content-Type: application/json');
        echo json_encode(['success' => true, 'status' => $new, 'is_active' => (int)$isActiveVal]);
        exit;
    } catch (Exception $e) {
        error_log("[seller_header] Toggle update FAILED: " . $e->getMessage());
        header('Content-Type: application/json', true, 500);
        echo json_encode(['success' => false, 'message' => 'DB error: ' . $e->getMessage()]);
        exit;
    }
}

// Debug flag: enable verbose debug output when APP_DEBUG=true in the environment
$APP_DEBUG = (getenv('APP_DEBUG') !== false) ? filter_var(getenv('APP_DEBUG'), FILTER_VALIDATE_BOOLEAN) : false;

if (!isset($dashboard_type)) $dashboard_type = 'carwash';
if (!isset($page_title)) $page_title = 'İşletme Paneli - MyCar';
if (!isset($current_page)) $current_page = 'dashboard';

$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
$base_url = $base_url ?? ($protocol . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/carwash_project');

$user_id = $_SESSION['user_id'] ?? null;
$user_name = $_SESSION['name'] ?? $_SESSION['full_name'] ?? 'Kullanıcı';
$user_email = $_SESSION['email'] ?? '';
$user_role = $_SESSION['role'] ?? 'carwash';

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
$profile_src = $_SESSION['profile_image'] ?? ($base_url . '/frontend/images/default-avatar.svg');

// Build canonical header profile src (use profile_image_handler like customer header)
$ts = intval($_SESSION['profile_image_ts'] ?? time());
if (!empty($user_id)) {
    $header_profile_src = rtrim($base_url, '\\/') . '/backend/profile_image_handler.php?user_id=' . intval($user_id) . '&ts=' . $ts;
} else {
    $header_profile_src = $base_url . '/frontend/images/default-avatar.svg';
}

// Current logged-in user's display name and email (used in header)
$user_name = $_SESSION['name'] ?? $_SESSION['user_name'] ?? 'Kullanıcı';
$user_email = $_SESSION['email'] ?? '';

// URLs
$home_url = $base_url . '/backend/index.php';
$about_url = $base_url . '/backend/about.php';
$contact_url = $base_url . '/backend/contact.php';
$logout_url = $base_url . '/backend/includes/logout.php';
$dashboard_url = $base_url . '/backend/dashboard/Car_Wash_Dashboard.php';

// Load current workplace status from DB (authoritative source), fallback to session
// IMPORTANT: Always read from DB first to ensure UI matches actual database state
$workplace_status = null;

// First, try to read from carwashes table (authoritative source)
if (!empty($user_id)) {
    try {
        if (class_exists('App\\Classes\\Database')) {
            $db = App\Classes\Database::getInstance();
            $cw = $db->fetchOne('SELECT status, COALESCE(is_active,0) AS is_active FROM carwashes WHERE user_id = :uid LIMIT 1', ['uid' => $user_id]);
            if ($cw && isset($cw['status']) && $cw['status'] !== null && $cw['status'] !== '') {
                $workplace_status = $cw['status'];
                // Sync session with DB value
                $_SESSION['workplace_status'] = $workplace_status;
            } elseif ($cw && isset($cw['is_active'])) {
                // If status is empty but is_active exists, derive status from it
                $workplace_status = ($cw['is_active'] == 1) ? 'Açık' : 'Kapalı';
                $_SESSION['workplace_status'] = $workplace_status;
            }
        }
    } catch (Exception $_e) {
        // ignore carwashes read errors
    }
}

// Fallback to session only if DB read failed
if (empty($workplace_status)) {
    $workplace_status = $_SESSION['workplace_status'] ?? null;
}

// Fallback to users.workplace_status if still empty
if (empty($workplace_status) && isset($user_id)) {
    try {
        if (class_exists('App\\Classes\\Database')) {
            $db = App\Classes\Database::getInstance();
            try {
                $pdoChk = $db->getPdo();
                $col = $pdoChk->query("SHOW COLUMNS FROM users LIKE 'workplace_status'")->fetch();
                if (!empty($col)) {
                    $row = $db->fetchOne('SELECT workplace_status FROM users WHERE id = :id', ['id' => $user_id]);
                    if ($row && isset($row['workplace_status'])) {
                        $workplace_status = $row['workplace_status'];
                        $_SESSION['workplace_status'] = $workplace_status;
                    }
                }
            } catch (Exception $_) {
                // ignore schema check or query errors
            }
        }
    } catch (Exception $e) {
        // ignore
    }
}

// For rendering, compute open/closed using a normalized, case-insensitive check.
// Explicit closed tokens override any is_active flag.
$ws_norm = strtolower((string)($workplace_status ?? ''));
$openTokens = ['açık','acik','open','active','pending','1'];
$closedTokens = ['kapalı','kapali','closed','inactive','0'];
if (in_array($ws_norm, $closedTokens, true) || $workplace_status === 0 || $workplace_status === '0') {
    $is_open = false;
} elseif (in_array($ws_norm, $openTokens, true) || $workplace_status === 1 || $workplace_status === '1') {
    $is_open = true;
} else {
    // Unknown or empty value: default to closed to avoid accidentally exposing a business as open.
    $is_open = false;
}

?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <link rel="stylesheet" href="<?php echo $base_url; ?>/dist/output.css">
        <link rel="stylesheet" href="/carwash_project/frontend/vendor/fontawesome/css/all.min.css">
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
        <!-- Vehicle manager factory (same-origin) - required by vehicle sections -->
        <script src="/carwash_project/frontend/js/vehicleManager.js" defer></script>
        <!-- Alpine.js -->
        <script src="/carwash_project/frontend/vendor/alpine/cdn.min.js" defer></script>
        <?php if (!empty($APP_DEBUG)) {
            echo '<script defer>console.log("seller_header: Alpine initialized");</script>';
        } ?>
        <?php
        // Ensure a CSRF token is available for JS-driven forms and APIs
        $csrf_file = __DIR__ . '/csrf_protect.php';
        if (file_exists($csrf_file)) {
            require_once $csrf_file;
            // generate token if missing
            if (empty($_SESSION['csrf_token'])) generate_csrf_token();
            $csrf_meta = htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8');
            echo "\n    <meta name=\"csrf-token\" content=\"{$csrf_meta}\">\n";
            echo "    <script>window.CONFIG = window.CONFIG || {}; window.CONFIG.CSRF_TOKEN = '{$csrf_meta}';</script>\n";
        }
        ?>
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
                    <div class="status-indicator <?php echo ($is_open) ? 'status-open' : 'status-closed'; ?>" id="workplaceStatusIndicator">
                        <span id="workplaceStatusText"><?php echo ($is_open) ? 'Açık' : 'Kapalı'; ?></span>
                    </div>

                    <label class="toggle-switch" title="İşletme Durumu">
                        <input type="checkbox" id="workplaceStatusToggle" <?php echo ($is_open) ? 'checked' : ''; ?> aria-checked="<?php echo ($is_open) ? 'true' : 'false'; ?>">
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
                            <img id="userAvatarTop" src="<?php echo htmlspecialchars($header_profile_src, ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($user_name); ?>" class="object-cover w-full h-full" style="border-radius:50%;" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex'">
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
            <img id="mobileMenuAvatar" src="<?php echo htmlspecialchars($header_profile_src, ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars($user_name); ?>" style="width:48px;height:48px;border-radius:50%;object-fit:cover;">
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

<!-- Sync profile image to localStorage so index header can read the sidebar's image as source-of-truth -->
<script>
    (function(){
        try {
            // Migration: Clear old relative paths from localStorage
            var oldPath = localStorage.getItem('carwash_profile_image');
            if (oldPath && (oldPath.indexOf('uploads/profiles/') === 0 || oldPath.indexOf('backend/uploads/') !== -1)) {
                // Old relative path detected - clear it so the new absolute URL can be set
                localStorage.removeItem('carwash_profile_image');
                localStorage.removeItem('carwash_profile_image_ts');
            }
            
            var profileSrc = <?php echo json_encode($header_profile_src); ?>;
            if (profileSrc) {
                var ts = Date.now();
                var url = profileSrc + (profileSrc.indexOf('?') === -1 ? ('?ts=' + ts) : ('&ts=' + ts));
                try { localStorage.setItem('carwash_profile_image', url); localStorage.setItem('carwash_profile_image_ts', ts.toString()); } catch(e) { /* ignore storage errors */ }
            }
        } catch (e) {
            // ignore
        }
    })();
</script>

<script>
// Workplace toggle behaviour: sends AJAX POST to this file to persist status
(function(){
    var toggle = document.getElementById('workplaceStatusToggle');
    var indicator = document.getElementById('workplaceStatusIndicator');
    var statusText = document.getElementById('workplaceStatusText');
    if (!toggle) return;

    function setUI(state){
        // Normalize and accept legacy tokens (case-insensitive)
        var s = (state || '').toString();
        var sNorm = s.toLowerCase();
        var openTokens = ['açık','acik','open','active','1'];
        var closedTokens = ['kapalı','kapali','closed','inactive','0'];

        var isOpen = false;
        if (openTokens.indexOf(sNorm) !== -1) isOpen = true;
        else if (closedTokens.indexOf(sNorm) !== -1) isOpen = false;
        else if (s === '1' || s === 1) isOpen = true;
        else if (s === '0' || s === 0) isOpen = false;
        // Unknown token -> leave closed by default

        if(isOpen){
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
        // Send Turkish explicit tokens so user choice is preserved server-side
        var desired = toggle.checked ? 'Açık' : 'Kapalı';
        // Optimistically update UI
        setUI(desired);

        var form = new FormData();
        form.append('ajax_workplace_status', desired);
        form.append('ajax_is_active', toggle.checked ? '1' : '0');

        fetch('<?php echo htmlspecialchars($base_url . "/backend/includes/workplace_status_api.php"); ?>', {
            method: 'POST',
            credentials: 'same-origin',
            body: form
        }).then(function(resp){
            return resp.json();
        }).then(function(json){
            if(!json || !json.success){
                // Revert UI on failure; json.status may be legacy or Turkish token
                setUI(json && json.status ? json.status : (desired === 'Açık' ? 'Kapalı' : 'Açık'));
                console.warn('Failed to save workplace status', json);
                return;
            }
            // If server returned canonical values, update UI accordingly
            if (json.status !== undefined || json.is_active !== undefined) {
                var serverStatus = json.status || '';
                var serverIsActive = (parseInt(json.is_active || 0, 10) === 1);
                // Prefer explicit token; fallback to is_active
                if (serverStatus && serverStatus !== '') setUI(serverStatus);
                else setUI(serverIsActive ? '1' : '0');
            }
        }).catch(function(err){
            // Revert UI
            setUI(desired === 'Açık' ? 'Kapalı' : 'Açık');
            console.error('Error saving workplace status', err);
        });
    });

    // On load, sync with server authoritative state to avoid session/local mismatches
    (function(){
        try {
            fetch('<?php echo htmlspecialchars($base_url . "/backend/includes/workplace_status_api.php"); ?>', { credentials: 'same-origin' })
                .then(r => r.json())
                .then(j => {
                    if (!j || !j.success) return;
                    var st = j.status || '';
                    var ia = (parseInt(j.is_active || 0, 10) === 1);
                    if (st && st !== '') setUI(st);
                    else setUI(ia ? '1' : '0');
                }).catch(function(){ /* ignore */ });
        } catch (e) { /* ignore */ }
    })();
})();
</script>


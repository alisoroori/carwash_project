<?php
/**
 * Customer Header
 * Provides a customer-specific header wrapper. It includes the master
 * `dashboard_header.php` (so all shared head tags and scripts are present)
 * then applies CSS/JS overrides to make the header fixed, move menus
 * outside the header container (so they don't scroll with header), and
 * ensure the MyCar logo is present and aligned.
 */

if (!isset($dashboard_type)) $dashboard_type = 'customer';
if (!isset($page_title)) $page_title = 'Müşteri Paneli - CarWash';
if (!isset($current_page)) $current_page = 'dashboard';

// Include the shared dashboard header (outputs <head> and header markup)
if (file_exists(__DIR__ . '/dashboard_header.php')) {
    include_once __DIR__ . '/dashboard_header.php';
} else {
    // Fallback to general header if dashboard header missing
    if (file_exists(__DIR__ . '/header.php')) include_once __DIR__ . '/header.php';
}

// Ensure we have a usable logo path for the MyCar brand
$base_url = isset($base_url) ? $base_url : ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . '/carwash_project';
$logo_path = $_SESSION['logo_path'] ?? ($base_url . '/backend/logo01.png');
?>

<!-- Customer header overrides: make header fixed, move menus outside header, add logo -->
<style>
    /* Make the existing dashboard header fixed at top */
    .dashboard-header {
        position: fixed !important;
        top: 0 !important;
        left: 0 !important;
        right: 0 !important;
        z-index: 1100 !important;
        will-change: transform;
    }

    /* Ensure page content is not hidden behind the fixed header */
    body {
        padding-top: var(--header-height, 64px) !important;
    }

    /* Move mobile menu / dropdowns to behave as overlays below header */
    #mobileMenu,
    .mobile-menu,
    .mobile-menu-panel,
    .user-dropdown,
    .user-menu .dropdown-menu,
    .dropdown-menu {
        position: fixed !important;
        z-index: 1200 !important;
        top: calc(var(--header-height, 64px)) !important;
        left: 0 !important;
        right: 0 !important;
        width: 100% !important;
        max-height: calc(100vh - var(--header-height, 64px)) !important;
        overflow: auto !important;
        background: rgba(255,255,255,0.98) !important;
        -webkit-backdrop-filter: blur(6px) !important;
        backdrop-filter: blur(6px) !important;
        box-shadow: 0 12px 40px rgba(0,0,0,0.12) !important;
        border-radius: 0 0 12px 12px !important;
    }

    /* Desktop dropdowns should align to the right inside the overlay area */
    .dashboard-header .dropdown-menu {
        right: 1rem !important;
        left: auto !important;
        top: calc(var(--header-height, 64px)) !important;
        min-width: 220px !important;
    }

    /* Logo polish for customer header */
    .dashboard-logo .logo-image {
        width: 44px !important;
        height: 44px !important;
        object-fit: cover !important;
        border-radius: 8px !important;
        display: inline-block !important;
        margin-right: 0.5rem !important;
    }

    /* Ensure header internals are visible on top of overlays */
    .dashboard-header * { z-index: 1110; }

    /* Small tweak for mobile: keep hamburger visible */
    @media (max-width: 640px) {
        body { padding-top: calc(var(--header-height, 64px) + 0px) !important; }
    }
</style>

<script>
/* Move menu nodes outside header and ensure header height is applied */
(function(){
    function updateHeaderHeight(){
        var header = document.querySelector('.dashboard-header');
        if(!header) return;
        var h = Math.ceil(header.getBoundingClientRect().height || 64);
        document.documentElement.style.setProperty('--header-height', h + 'px');
        document.body.style.paddingTop = h + 'px';
    }

    function moveMenusOut(){
        var header = document.querySelector('.dashboard-header');
        if(!header) return;

        // Mobile menu
        var mobile = document.getElementById('mobileMenu') || document.querySelector('.mobile-menu');
        if(mobile && mobile.parentElement !== document.body){
            document.body.appendChild(mobile);
            mobile.classList.add('moved-outside-header');
        }

        // Any desktop dropdowns / user dropdowns
        document.querySelectorAll('.user-dropdown, .dropdown-menu, .mobile-menu-panel, .mobile-menu-nav').forEach(function(el){
            if(el && el.parentElement !== document.body){
                document.body.appendChild(el);
                el.classList.add('moved-outside-header');
            }
        });
    }

    function insertLogo(){
        try{
            var logoHtml = `<img src="<?php echo htmlspecialchars($logo_path, ENT_QUOTES, 'UTF-8'); ?>" alt="MyCar logo" class="logo-image" loading="lazy"/>`;
            var logoWrap = document.querySelector('.dashboard-logo');
            if(logoWrap && !logoWrap.querySelector('.logo-image')){
                // Prepend the logo image
                logoWrap.insertAdjacentHTML('afterbegin', logoHtml);
            }
        }catch(e){ console.error('Insert logo error', e); }
    }

    window.addEventListener('load', function(){ updateHeaderHeight(); moveMenusOut(); insertLogo(); });
    window.addEventListener('resize', updateHeaderHeight);

    // In case header content changes dynamically, observe DOM
    var header = document.querySelector('.dashboard-header');
    if(header){
        var mo = new MutationObserver(function(){ updateHeaderHeight(); moveMenusOut(); });
        mo.observe(header, { childList:true, subtree:true, attributes:true });
    }
})();
</script>

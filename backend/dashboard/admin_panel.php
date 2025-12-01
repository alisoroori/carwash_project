<?php
/**
 * Admin Panel for CarWash Web Application
 * Uses the universal dashboard header/footer system with admin context
 */

// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in and has admin role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit();
}

// Set page-specific variables for the dashboard header
$dashboard_type = 'admin';  // Specify this is the admin dashboard
$page_title = 'Yönetici Paneli - CarWash';
$current_page = 'dashboard';

// Provide language attributes for the header and include the universal dashboard header
if (file_exists(__DIR__ . '/../includes/lang_helper.php')) {
    require_once __DIR__ . '/../includes/lang_helper.php';
    $html_lang_attrs = get_lang_dir_attrs_for_file(__FILE__);
}
// Include Admin Header (standardized)
include '../includes/admin_header.php';
?>
<!-- Lazy-section loader (loads dashboard fragments when they come into view) -->
<script defer src="<?php echo $base_url; ?>/frontend/js/section-loader.js"></script>
<?php
?>

<!-- Dashboard Specific Styles -->
<style>
    /* Admin Panel Specific Styles */
    
    /* Global Page Layout - Remove all gaps */
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }
    
    html {
        scroll-behavior: smooth;
        height: 100%;
    }
    
    body {
        margin: 0 !important;
        padding: 0 !important;
        overflow-x: hidden;
        min-height: 100vh;
    }
    
    /* Dashboard-specific overrides only - Universal fixes included via header */
    
        /* Ensure header is fixed to top with no gap */
        .dashboard-header {
            position: fixed !important;
            top: 0 !important;
            left: 0 !important;
            right: 0 !important;
            width: 100% !important;
            margin: 0 !important;
            z-index: 1000 !important;
        }

        /* Dashboard Container - Full height, connected to header and footer seamlessly */
        .dashboard-wrapper {
            display: flex;
            min-height: 100vh;
            margin-top: 0 !important;
            margin-bottom: 0 !important;
            padding-top: 70px;
            background: #f8fafc;
            position: relative;
        }
        
        /* Mobile Menu Toggle Button */
        .mobile-menu-toggle {
            display: none;
            position: fixed;
            bottom: 20px;
            right: 20px;
            width: 56px;
            height: 56px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            border-radius: 50%;
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
            cursor: pointer;
            z-index: 1000;
            transition: all 0.3s ease;
        }
        
        .mobile-menu-toggle:hover {
            transform: scale(1.1);
            box-shadow: 0 6px 16px rgba(102, 126, 234, 0.5);
        }
        
        .mobile-menu-toggle i {
            font-size: 1.5rem;
        }
        
        .mobile-menu-toggle.active {
            background: linear-gradient(135deg, #dc3545, #c82333);
        }
        
        /* Adjust FAB position on mobile to avoid footer overlap */
        @media (max-width: 767px) {
            .mobile-menu-toggle {
                bottom: 80px;
            }
        }
        
        /* Mobile Overlay */
        .mobile-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 999;
            opacity: 0;
            transition: opacity 0.3s ease;
        }
        
        .mobile-overlay.active {
            display: block;
            opacity: 1;
        }
        
        /* Sidebar Styles */
        /* FarsÃ§a: Ø§Ø³ØªØ§ÛŒÙ„â€ŒÙ‡Ø§ÛŒ Ù†ÙˆØ§Ø± Ú©Ù†Ø§Ø±ÛŒ. */
        /* Türkçe: Kenar Çubuğu Stilleri. */
        /* English: Sidebar Styles. */
        .sidebar {
            width: 280px;
            background: linear-gradient(180deg, #667eea 0%, #764ba2 100%);
            box-shadow: 4px 0 15px rgba(0,0,0,0.1);
            position: relative;
            left: 0;
            overflow-y: auto;
            flex-shrink: 0;
            z-index: 30;
            transition: transform 0.3s ease;
            display: flex;
            flex-direction: column;
        }
        
        /* Mobile Sidebar - Slide from left */
        .sidebar.mobile-hidden {
            transform: translateX(-100%);
        }
        
        .sidebar.mobile-visible {
            transform: translateX(0);
        }
        
        /* Smooth scrollbar for sidebar */
        .sidebar::-webkit-scrollbar {
            width: 8px;
        }
        
        .sidebar::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
        }
        
        .sidebar::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.3);
            border-radius: 10px;
        }
        
        .sidebar::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.5);
        }

        .nav-menu {
            flex: 1;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
        }

        .nav-menu ul {
            list-style: none;
            padding: 1rem 0;
            margin: 0;
        }

        .nav-item {
            margin: 0;
            width: 100%;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 15px 25px;
            color: rgba(255, 255, 255, 0.9);
            text-decoration: none;
            transition: all 0.3s ease;
            border-radius: 0.75rem;
            margin: 0.25rem 1rem;
            position: relative;
            overflow: hidden;
        }
        
        .nav-link::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            height: 100%;
            width: 3px;
            background: white;
            transform: scaleY(0);
            transition: transform 0.3s ease;
        }

        .nav-link:hover {
            background: rgba(255, 255, 255, 0.15);
            color: white;
            transform: translateX(4px);
        }
        
        .nav-link:hover::before {
            transform: scaleY(1);
        }

        .nav-item.active .nav-link {
            background: rgba(255, 255, 255, 0.2);
            color: white;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            font-weight: 500;
        }
        
        .nav-item.active .nav-link::before {
            transform: scaleY(1);
        }

        .nav-link i {
            font-size: 1.1rem;
            width: 20px;
            text-align: center;
            flex-shrink: 0;
        }
        
        .nav-link span {
            flex: 1;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* Main Content - Seamlessly connected and full height */
        .main-content {
            flex: 1;
            padding: 2rem;
            background: #f8fafc;
            margin-bottom: 0 !important;
            display: flex;
            flex-direction: column;
        }

        /* Remove footer top margin for seamless connection */
        footer {
            margin-top: 0 !important;
            margin-bottom: 0 !important;
        }
        
        /* Ensure footer parent wrapper has no gap */
        body > footer,
        main + footer {
            margin-top: 0 !important;
        }
        
        /* Override Tailwind mt-16 class on footer */
        footer.mt-16 {
            margin-top: 0 !important;
        }
        
        /* Override any Tailwind margin utilities */
        .mt-16, .mt-12, .mt-8 {
            margin-top: 0 !important;
        }

        /* Content Section */
        .content-section {
            display: none;
        }

        .content-section.active {
            display: block;
        }

        .section-header {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .section-header h2 {
            color: #333;
            font-size: 1.8rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            margin: 0;
        }
        
        .section-header h2 i {
            font-size: 1.6rem;
        }

        .section-header p {
            color: #666;
            margin-top: 5px;
            margin-bottom: 0;
        }
        
        .section-header > div {
            flex: 1;
        }

        .add-btn {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 500;
            transition: transform 0.2s, box-shadow 0.2s;
        }

        .add-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.3);
        }

        /* Stats Grid */
        /* FarsÃ§a: Ú¯Ø±ÛŒØ¯ Ø¢Ù…Ø§Ø±. */
        /* TÃ¼rkÃ§e: Ä°statistik IzgarasÄ±. */
        /* English: Stats Grid. */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            display: flex;
            flex-direction: row;
            flex-wrap: wrap;
            align-content: normal;
            justify-content: center;
            align-items: center;
            gap: 1rem;
            transition: transform 0.2s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            min-width: 60px;
            min-height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea20, #764ba220);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #667eea;
            font-size: 1.5rem;
            flex-shrink: 0;
        }

        .stat-info h3 {
            font-size: 2rem;
            font-weight: 700;
            color: #333;
            margin-bottom: 5px;
        }

        .stat-info p {
            color: #666;
            font-size: 0.9rem;
        }

        /* Activity Section */
        /* FarsÃ§a: Ø¨Ø®Ø´ ÙØ¹Ø§Ù„ÛŒØª. */
        /* TÃ¼rkÃ§e: Aktivite BÃ¶lÃ¼mÃ¼. */
        /* English: Activity Section. */
        .activity-section {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            margin-bottom: 2rem;
        }

        .activity-section h3 {
            color: #333;
            margin-bottom: 1.5rem;
            font-size: 1.3rem;
        }

        .activity-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .activity-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            background: #f8f9fa;
            border-radius: 8px;
            border-left: 4px solid #667eea;
        }

        .activity-item i {
            color: #667eea;
            font-size: 1.1rem;
        }

        .activity-item span {
            flex: 1;
            color: #333;
        }

        .activity-item time {
            color: #666;
            font-size: 0.85rem;
        }

        /* Filters */
        /* FarsÃ§a: ÙÛŒÙ„ØªØ±Ù‡Ø§. */
        /* TÃ¼rkÃ§e: Filtreler. */
        /* English: Filters. */
        .filters {
            background: white;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            margin-bottom: 2rem;
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        .search-input, .filter-select {
            padding: 10px 15px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 0.9rem;
            transition: border-color 0.3s;
        }

        .search-input:focus, .filter-select:focus {
            outline: none;
            border-color: #667eea;
        }

        .search-input {
            flex: 1;
            max-width: 300px;
        }

        .filter-select {
            min-width: 150px;
        }

        /* Table Styles */
        /* FarsÃ§a: Ø§Ø³ØªØ§ÛŒÙ„â€ŒÙ‡Ø§ÛŒ Ø¬Ø¯ÙˆÙ„. */
        /* TÃ¼rkÃ§e: Tablo Stilleri. */
        /* English: Table Styles. */
        .table-container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            overflow: hidden;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
        }

        .data-table thead {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }

        .data-table th, .data-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #e9ecef;
        }

        .data-table th {
            font-weight: 600;
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .data-table tbody tr:hover {
            background: #f8f9fa;
        }

        /* Status Badges */
        /* FarsÃ§a: Ù†Ø´Ø§Ù†â€ŒÙ‡Ø§ÛŒ ÙˆØ¶Ø¹ÛŒØª. */
        /* TÃ¼rkÃ§e: Durum Rozetleri. */
        /* English: Status Badges. */
        .status-badge, .type-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-badge.active {
            background: #d4edda;
            color: #155724;
        }

        .status-badge.inactive {
            background: #f8d7da;
            color: #721c24;
        }

        .status-badge.maintenance {
            background: #fff3cd;
            color: #856404;
        }

        .type-badge.admin {
            background: #d1ecf1;
            color: #0c5460;
        }

        .type-badge.user {
            background: #e2e3e5;
            color: #383d41;
        }

        .type-badge.premium {
            background: #ffeaa7;
            color: #fdcb6e;
        }

        /* Action Buttons */
        /* FarsÃ§a: Ø¯Ú©Ù…Ù‡â€ŒÙ‡Ø§ÛŒ Ø¹Ù…Ù„ÛŒØ§Øª. */
        /* TÃ¼rkÃ§e: Eylem DÃ¼ÄŸmeleri. */
        /* English: Action Buttons. */
        .action-btn {
            background: none;
            border: none;
            padding: 8px;
            margin: 0 2px;
            border-radius: 5px;
            cursor: pointer;
            transition: background 0.3s;
            font-size: 0.9rem;
        }

        .edit-btn {
            color: #007bff;
        }

        .edit-btn:hover {
            background: #007bff20;
        }

        .delete-btn {
            color: #dc3545;
        }

        .delete-btn:hover {
            background: #dc354520;
        }

        .view-btn {
            color: #28a745;
        }

        .view-btn:hover {
            background: #28a74520;
        }

        /* Reports Grid */
        /* FarsÃ§a: Ú¯Ø±ÛŒØ¯ Ú¯Ø²Ø§Ø±Ø´Ø§Øª. */
        /* TÃ¼rkÃ§e: Rapor IzgarasÄ±. */
        /* English: Reports Grid. */
        .reports-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
        }

        .report-card {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            text-align: center;
        }

        .report-card h3 {
            color: #333;
            margin-bottom: 1rem;
            font-size: 1.3rem;
        }

        .report-card p {
            color: #666;
            margin-bottom: 1.5rem;
        }

        .report-btn {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            transition: transform 0.2s;
        }

        .report-btn:hover {
            transform: translateY(-2px);
        }

        /* Settings Form */
        /* FarsÃ§a: ÙØ±Ù… ØªÙ†Ø¸ÛŒÙ…Ø§Øª. */
        /* TÃ¼rkÃ§e: Ayarlar Formu. */
        /* English: Settings Form. */
        .settings-form {
            background: white;
            padding: 2rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            max-width: 500px;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
        }

        .form-group input, .form-group textarea, .form-group select {
            width: 100%;
            padding: 12px;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 0.9rem;
            transition: border-color 0.3s;
        }

        .form-group input:focus, .form-group textarea:focus, .form-group select:focus {
            outline: none;
            border-color: #667eea;
        }

        .save-btn {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            transition: transform 0.2s;
        }

        .save-btn:hover {
            transform: translateY(-2px);
        }

        /* Modal Styles */
        /* FarsÃ§a: Ø§Ø³ØªØ§ÛŒÙ„â€ŒÙ‡Ø§ÛŒ Ù…ÙˆØ¯Ø§Ù„. */
        /* TÃ¼rkÃ§e: Modal Stilleri. */
        /* English: Modal Styles. */
        .modal {
            display: none;
            position: fixed;
            z-index: 2000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            overflow-y: auto;
            padding: 20px 0;
        }

        .modal-content {
            background: white;
            margin: 2% auto;
            padding: 0;
            border-radius: 10px;
            width: 90%;
            max-width: 500px;
            max-height: 90vh;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            display: flex;
            flex-direction: column;
        }

        .modal-header {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 1.5rem;
            border-radius: 10px 10px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-shrink: 0;
        }

        .modal-header h3 {
            margin: 0;
            font-size: 1.3rem;
        }

        .close {
            color: white;
            font-size: 2rem;
            font-weight: bold;
            cursor: pointer;
            line-height: 1;
        }

        .close:hover {
            opacity: 0.7;
        }

        .modal-body {
            padding: 2rem;
            overflow-y: auto;
            flex: 1;
        }

        .submit-btn {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            width: 100%;
            transition: transform 0.2s;
        }

        .submit-btn:hover {
            transform: translateY(-2px);
        }

        /* Responsive Design - Mobile First Approach */
        
        /* Extra Small Devices (Phones, less than 576px) */
        @media (max-width: 575px) {
            .dashboard-wrapper {
                flex-direction: column;
                margin-top: 0;
                padding-top: 60px;
                min-height: 100vh;
            }
            
            .sidebar {
                width: 100%;
                position: fixed;
                height: 100vh;
                border-radius: 0;
                top: 0;
                left: 0;
                z-index: 1001;
            }
            
            .sidebar.mobile-hidden {
                transform: translateX(-100%);
            }
            
            .sidebar.mobile-visible {
                transform: translateX(0);
            }
            
            .nav-menu ul {
                padding: 0.5rem 0;
            }
            
            .nav-link {
                padding: 14px 20px;
                font-size: 0.9rem;
                margin: 0.2rem 0.75rem;
                min-height: 48px;
            }
            
            .nav-link i {
                font-size: 1.1rem;
                width: 22px;
            }
            
            .nav-link span {
                font-size: 0.95rem;
            }
            
            .main-content {
                padding: 1rem 0.75rem;
                min-height: calc(100vh - 60px);
            }
            
            /* Ensure footer is fully responsive on mobile */
            footer {
                padding: 2rem 1rem !important;
            }
            
            footer .container {
                padding: 0 !important;
            }
            
            .section-header {
                flex-direction: column;
                align-items: stretch;
                gap: 1rem;
                padding: 1.25rem;
            }
            
            .section-header > div {
                width: 100%;
            }
            
            .section-header h2 {
                font-size: 1.5rem;
            }
            
            .section-header h2 i {
                font-size: 1.4rem;
            }
            
            .section-header p {
                font-size: 0.85rem;
            }
            
            .add-btn {
                padding: 10px 18px;
                font-size: 0.875rem;
                width: 100%;
                justify-content: center;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            
            .stat-card {
                padding: 1.25rem;
            }
            
            .stat-icon {
                width: 50px;
                height: 50px;
                min-width: 50px;
                min-height: 50px;
                font-size: 1.25rem;
            }
            
            .stat-info h3 {
                font-size: 1.5rem;
            }
            
            .stat-info p {
                font-size: 0.8rem;
            }
            
            .activity-section {
                padding: 1.25rem;
            }
            
            .activity-section h3 {
                font-size: 1.1rem;
            }
            
            .activity-item {
                flex-direction: column;
                align-items: flex-start;
                padding: 0.875rem;
                gap: 0.5rem;
            }
            
            .activity-item time {
                font-size: 0.75rem;
            }
            
            .filters {
                flex-direction: column;
                align-items: stretch;
                padding: 1rem;
                gap: 0.75rem;
            }
            
            .search-input {
                max-width: 100%;
            }
            
            .filter-select {
                min-width: 100%;
            }
            
            .table-container {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }
            
            .data-table {
                font-size: 0.75rem;
                min-width: 600px;
            }
            
            .data-table th, .data-table td {
                padding: 8px 6px;
                white-space: nowrap;
            }
            
            .status-badge, .type-badge {
                padding: 4px 8px;
                font-size: 0.7rem;
            }
            
            .action-btn {
                padding: 6px;
                font-size: 0.8rem;
            }
            
            .reports-grid {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            
            .report-card {
                padding: 1.25rem;
            }
            
            .report-card h3 {
                font-size: 1.1rem;
            }
            
            .settings-form {
                padding: 1.25rem;
            }
            
            .form-group {
                margin-bottom: 1rem;
            }
            
            .modal {
                padding: 10px 0;
            }
            
            .modal-content {
                width: 95%;
                margin: 5% auto;
                max-height: 85vh;
            }
            
            .modal-header {
                padding: 1rem;
            }
            
            .modal-header h3 {
                font-size: 1.1rem;
            }
            
            .modal-body {
                padding: 1.25rem;
            }
        }
        
        /* Small Devices (Landscape Phones, 576px and up) */
        @media (min-width: 576px) and (max-width: 767px) {
            .dashboard-wrapper {
                flex-direction: column;
                margin-top: 0;
                padding-top: 65px;
            }
            
            .sidebar {
                width: 100%;
                position: fixed;
                height: 100vh;
                top: 0;
                left: 0;
                z-index: 1001;
            }
            
            .sidebar.mobile-hidden {
                transform: translateX(-100%);
            }
            
            .sidebar.mobile-visible {
                transform: translateX(0);
            }
            
            .main-content {
                padding: 1.25rem;
            }
            
            .section-header {
                flex-direction: column;
                gap: 1rem;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 1.25rem;
            }
            
            .filters {
                flex-wrap: wrap;
            }
            
            .search-input {
                flex: 1;
                min-width: 200px;
            }
            
            .table-container {
                overflow-x: auto;
            }
            
            .data-table {
                font-size: 0.85rem;
            }
            
            .reports-grid {
                grid-template-columns: 1fr;
            }
        }
        
        /* Medium Devices (Tablets, 768px and up) */
        @media (min-width: 768px) and (max-width: 1023px) {
            .dashboard-wrapper {
                flex-direction: column;
                margin-top: 0;
                padding-top: 70px;
                min-height: 100vh;
            }
            
            .sidebar {
                width: 100%;
                position: fixed;
                height: 100vh;
                top: 0;
                left: 0;
                z-index: 1001;
                display: flex;
            }
            
            .sidebar.mobile-hidden {
                transform: translateX(-100%);
            }
            
            .sidebar.mobile-visible {
                transform: translateX(0);
            }
            
            .nav-menu {
                width: 100%;
            }
            
            .nav-menu ul {
                display: flex;
                flex-direction: column;
                padding: 1rem;
            }
            
            .nav-item {
                width: 100%;
            }
            
            .nav-link {
                padding: 14px 24px;
                margin: 0.3rem 0.5rem;
                font-size: 1rem;
                min-height: 50px;
            }
            
            .nav-link i {
                font-size: 1.2rem;
            }
            
            .main-content {
                padding: 1.5rem;
                min-height: calc(100vh - 70px);
            }
            
            /* Responsive footer on tablets */
            footer {
                padding: 2.5rem 1.5rem !important;
            }
            
            .section-header {
                flex-direction: row;
                justify-content: space-between;
                align-items: center;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 1.5rem;
            }
            
            .filters {
                flex-direction: row;
                flex-wrap: wrap;
            }
            
            .table-container {
                overflow-x: auto;
            }
            
            .reports-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        /* Large Devices (Desktops, 1024px and up) */
        @media (min-width: 1024px) {
            .dashboard-wrapper {
                flex-direction: row;
            }
            
            .sidebar {
                width: 280px;
                position: relative;
                transform: translateX(0) !important;
            }
            
            .sidebar.mobile-hidden,
            .sidebar.mobile-visible {
                transform: translateX(0) !important;
            }
            
            .mobile-menu-toggle {
                display: none !important;
            }
            
            .mobile-overlay {
                display: none !important;
            }
            
            .main-content {
                padding: 2rem;
            }
            
            .stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            }
            
            .reports-grid {
                grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            }
        }
        
        /* Extra Large Devices (Large Desktops, 1200px and up) */
        @media (min-width: 1200px) {
            .stats-grid {
                grid-template-columns: repeat(4, 1fr);
            }
            
            .main-content {
                max-width: 1400px;
            }
        }
        
        /* Ultra Wide Screens (1600px and up) */
        @media (min-width: 1600px) {
            .dashboard-wrapper {
                max-width: 1800px;
                margin-left: auto;
                margin-right: auto;
            }
        }
        
        /* Landscape Orientation for Mobile Devices */
        @media (max-height: 500px) and (orientation: landscape) {
            .sidebar {
                position: static;
                height: auto;
            }
            
            .nav-menu ul {
                display: flex;
                flex-wrap: wrap;
            }
            
            .nav-item {
                flex: 0 0 auto;
            }
        }
        
        /* Print Styles */
        @media print {
            .sidebar {
                display: none;
            }
            
            .main-content {
                margin-left: 0;
                padding: 0;
            }
            
            .section-header {
                break-inside: avoid;
            }
            
            .add-btn {
                display: none;
            }
            
            .action-btn {
                display: none;
            }
        }
        
        /* Touch Device Optimizations */
        @media (hover: none) and (pointer: coarse) {
            .nav-link {
                padding: 14px 20px;
                min-height: 44px;
            }
            
            .action-btn {
                padding: 10px;
                min-width: 44px;
                min-height: 44px;
            }
            
            .add-btn {
                min-height: 44px;
            }
            
            .stat-card {
                cursor: default;
            }
        }
        
        /* Payment Management Responsive Styles */
        /* FarsÃ§a: Ø§Ø³ØªØ§ÛŒÙ„â€ŒÙ‡Ø§ÛŒ Ø±ÛŒØ³Ù¾Ø§Ù†Ø³ÛŒÙˆ Ù…Ø¯ÛŒØ±ÛŒØª Ù¾Ø±Ø¯Ø§Ø®Øª. */
        /* Türkçe: Ödeme Yönetimi Duyarlı Stilleri. */
        /* English: Payment Management Responsive Styles. */
        
        /* Mobile Payment Stats - Stack vertically */
        @media (max-width: 767px) {
            #payments .stats-grid {
                grid-template-columns: 1fr !important;
                gap: 1rem;
            }
            
            #payments .stat-card {
                padding: 1.5rem;
            }
            
            #payments .stat-icon {
                width: 50px;
                height: 50px;
                min-width: 50px;
                min-height: 50px;
                font-size: 1.2rem;
            }
            
            #payments .stat-info h3 {
                font-size: 1.5rem;
            }
            
            #payments .stat-info p {
                font-size: 0.85rem;
            }
            
            #payments .stat-info small {
                font-size: 0.75rem;
                display: block;
                margin-top: 4px;
            }
            
            /* Payment Filters - Stack on mobile */
            #payments .filters {
                grid-template-columns: 1fr !important;
                gap: 12px;
                padding: 1rem;
            }
            
            #payments .search-input,
            #payments .filter-select {
                width: 100%;
                grid-column: 1 !important;
            }
            
            /* Payment Table - Make scrollable */
            #payments .table-container {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }
            
            #payments .data-table {
                min-width: 800px;
                font-size: 0.85rem;
            }
            
            #payments .data-table th,
            #payments .data-table td {
                padding: 10px 8px;
                white-space: nowrap;
            }
            
            #payments .data-table td div {
                min-width: 120px;
            }
            
            /* Settlement Section Mobile */
            #payments h3 {
                font-size: 1.1rem;
                margin-bottom: 12px;
                display: flex;
                align-items: center;
                flex-wrap: wrap;
            }
            
            #payments h3 i {
                font-size: 1rem;
                margin-right: 8px;
            }
            
            /* Section Header Actions - Stack on mobile */
            #payments .section-header {
                flex-direction: column;
                gap: 1rem;
                padding: 1.5rem;
            }
            
            #payments .section-header > div {
                width: 100%;
            }
            
            #payments .section-header > div:last-child {
                display: flex;
                flex-direction: column;
                gap: 8px;
            }
            
            #payments .section-header .add-btn {
                width: 100%;
                justify-content: center;
            }
        }
        
        /* Tablet Payment Responsive */
        @media (min-width: 768px) and (max-width: 1023px) {
            #payments .stats-grid {
                grid-template-columns: repeat(2, 1fr) !important;
                gap: 1.5rem;
            }
            
            #payments .filters {
                grid-template-columns: repeat(2, 1fr) !important;
                gap: 12px;
            }
            
            #payments .search-input {
                grid-column: 1 / -1 !important;
            }
            
            #payments .table-container {
                overflow-x: auto;
                -webkit-overflow-scrolling: touch;
            }
            
            #payments .data-table {
                min-width: 900px;
            }
            
            #payments .section-header {
                flex-wrap: wrap;
                gap: 1rem;
            }
            
            #payments .section-header > div:last-child {
                width: 100%;
                justify-content: flex-end;
            }
            
            #payments h3 {
                font-size: 1.2rem;
            }
        }
        
        /* Desktop Fine-tuning for Payment Section */
        @media (min-width: 1024px) {
            #payments .stats-grid {
                grid-template-columns: repeat(4, 1fr) !important;
                gap: 1.5rem;
            }
            
            #payments .filters {
                grid-template-columns: 2fr 1fr 1fr 1fr 1fr auto;
            }
        }
        
        /* Override default stats-grid behavior for payment section */
        #payments .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 1.5rem;
            margin-bottom: 24px;
        }
        
        /* Ensure all 4 cards are visible on medium screens */
        @media (min-width: 768px) and (max-width: 991px) {
            #payments .stats-grid {
                grid-template-columns: repeat(2, 1fr) !important;
            }
        }
        
        /* Ensure all 4 cards fit on larger tablets and small desktops */
        @media (min-width: 992px) and (max-width: 1199px) {
            #payments .stats-grid {
                grid-template-columns: repeat(4, 1fr) !important;
                gap: 1rem;
            }
            
            #payments .stat-card {
                padding: 1.5rem;
            }
            
            #payments .stat-info h3 {
                font-size: 1.6rem;
            }
            
            #payments .stat-info p {
                font-size: 0.85rem;
            }
        }
        
        /* Optimal spacing for large screens */
        @media (min-width: 1200px) {
            #payments .stats-grid {
                grid-template-columns: repeat(4, 1fr) !important;
                gap: 2rem;
            }
        }
        
        /* Utility classes for payment section */
        .text-green-600 {
            color: #059669;
            font-size: 0.8rem;
        }
        
        .text-yellow-600 {
            color: #d97706;
            font-size: 0.8rem;
        }
        
        .text-red-600 {
            color: #dc2626;
            font-size: 0.8rem;
        }
        
        .text-blue-600 {
            color: #2563eb;
            font-size: 0.8rem;
        }
        
        /* Pending badge styling */
        .status-badge.pending {
            background: #fef3c7;
            color: #d97706;
        }

        /* Toast Manager Styles */
        :root {
            --toast-top: 1rem;
            --toast-right: 1rem;
            --toast-gap: 0.5rem;
            --toast-min-width: 240px;
            --toast-max-width: 420px;
            --toast-padding: 0.75rem 1rem;
            --toast-radius: 8px;
            --toast-shadow: 0 8px 24px rgba(0,0,0,0.15);
            --toast-duration: 220ms;
            --toast-success-start: #10b981;
            --toast-success-end: #059669;
            --toast-error-start: #ef4444;
            --toast-error-end: #dc2626;
            --toast-info-start: #3b82f6;
            --toast-info-end: #2563eb;
        }

        #toastContainer {
            position: fixed;
            top: var(--toast-top);
            right: var(--toast-right);
            z-index: 2000;
            display: flex;
            flex-direction: column;
            gap: var(--toast-gap);
            pointer-events: none;
        }

        .toast {
            min-width: var(--toast-min-width);
            max-width: var(--toast-max-width);
            background: rgba(0,0,0,0.85);
            color: white;
            padding: var(--toast-padding);
            border-radius: var(--toast-radius);
            box-shadow: var(--toast-shadow);
            display: flex;
            align-items: center;
            gap: 0.6rem;
            font-size: 0.95rem;
            opacity: 0;
            transform: translateY(-6px) scale(0.995);
            transition: opacity var(--toast-duration) ease, transform var(--toast-duration) ease;
            pointer-events: auto;
        }

        .toast.show {
            opacity: 1;
            transform: translateY(0) scale(1);
        }

        .toast.success { background: linear-gradient(90deg,var(--toast-success-start),var(--toast-success-end)); }
        .toast.error { background: linear-gradient(90deg,var(--toast-error-start),var(--toast-error-end)); }
        .toast.info { background: linear-gradient(90deg,var(--toast-info-start),var(--toast-info-end)); }

        .toast .toast-icon { font-size: 1.05rem; }

        .toast .toast-close {
            margin-left: 8px;
            background: transparent;
            border: none;
            color: rgba(255,255,255,0.9);
            font-size: 0.95rem;
            cursor: pointer;
            padding: 4px;
            border-radius: 4px;
        }
        .toast .toast-close:focus { outline: 2px solid rgba(255,255,255,0.2); }

        /* Confirm Modal Styles */
        .confirm-modal-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,0.45);
            z-index: 2100;
            display: none;
        }

        .confirm-modal-dialog {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: #fff;
            color: #111827;
            border-radius: 8px;
            padding: 1rem;
            z-index: 2200;
            min-width: 320px;
            box-shadow: 0 12px 40px rgba(0,0,0,0.2);
            display: none;
        }

        .confirm-modal-title { font-weight: 600; margin-bottom: 0.5rem; }
        .confirm-modal-body { margin-bottom: 0.75rem; }
        .confirm-modal-actions { display:flex; gap:0.5rem; justify-content:flex-end; }
        .confirm-modal .btn-cancel { background:#f3f4f6; border: none; padding:0.5rem 0.75rem; border-radius:6px; cursor:pointer; }
        .confirm-modal .btn-confirm { background:linear-gradient(135deg,#ef4444,#dc2626); color:white; border:none; padding:0.5rem 0.75rem; border-radius:6px; cursor:pointer; }
    </style>



<!-- Mobile Menu Toggle Button -->
<button class="mobile-menu-toggle" id="mobileMenuToggle" onclick="toggleMobileMenu()">
    <i class="fas fa-bars" id="menuIcon"></i>
</button>

<!-- Mobile Overlay -->
<div class="mobile-overlay" id="mobileOverlay" onclick="closeMobileMenu()"></div>

<!-- Toast container + Confirm modal -->
<div id="toastContainer" aria-live="polite" aria-atomic="true"></div>

<div id="confirmModal" class="confirm-modal" aria-hidden="true">
    <div class="confirm-modal-backdrop" id="confirmBackdrop"></div>
    <div class="confirm-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="confirmTitle" id="confirmDialog" tabindex="-1">
        <div id="confirmTitle" class="confirm-modal-title">Onay</div>
        <div id="confirmBody" class="confirm-modal-body">Are you sure?</div>
        <div class="confirm-modal-actions">
            <button id="confirmCancelBtn" class="btn-cancel">Ä°ptal</button>
            <button id="confirmOkBtn" class="btn-confirm">Tamam</button>
        </div>
    </div>
</div>

<!-- Dashboard Wrapper Container -->
<div class="dashboard-wrapper">
    <!-- Sidebar Navigation - Sticky Position -->
    <!-- FarsÃ§a: Ù†ÙˆØ§Ø± Ú©Ù†Ø§Ø±ÛŒ Ù†Ø§ÙˆØ¨Ø±ÛŒ - Ù…ÙˆÙ‚Ø¹ÛŒØª Ú†Ø³Ø¨Ù†Ø¯Ù‡. -->
    <!-- Türkçe: Kenar çubuğu navigasyonu - Yapışkan Konum. -->
    <!-- English: Sidebar Navigation - Sticky Position. -->
    <aside id="sidebar" class="sidebar-fixed fixed top-16 bottom-0 left-0 w-72 bg-white/5 backdrop-blur-sm text-white z-40 shadow-xl">
        <div class="flex flex-col h-full">
            <div class="p-4">
                <!-- Optional top area: logo / user -->
                <div class="mb-4">
                    <div class="text-sm font-semibold">YÃ¶netim</div>
                </div>
            </div>
            <nav class="nav-menu flex-1 overflow-auto px-2 py-3">
                <ul>
                    <!-- Dashboard -->
                    <li class="nav-item active">
                        <a href="#dashboard" class="nav-link" data-section="dashboard">
                            <i class="fas fa-tachometer-alt"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    
                    <!-- User Management -->
                    <li class="nav-item">
                        <a href="#users" class="nav-link" data-section="users">
                            <i class="fas fa-users"></i>
                            <span>KullanÄ±cÄ± YÃ¶netimi</span>
                        </a>
                    </li>
                    
                    <!-- Order Management -->
                    <li class="nav-item">
                        <a href="#orders" class="nav-link" data-section="orders">
                            <i class="fas fa-shopping-cart"></i>
                            <span>SipariÅŸ YÃ¶netimi</span>
                        </a>
                    </li>
                    
                    <!-- Payment Management -->
                    <li class="nav-item">
                        <a href="#payments" class="nav-link" data-section="payments">
                            <i class="fas fa-credit-card"></i>
                            <span>Ödeme Yönetimi</span>
                        </a>
                    </li>
                    
                    <!-- Car Wash Management -->
                    <li class="nav-item">
                        <a href="#carwashes" class="nav-link" data-section="carwashes">
                            <i class="fas fa-warehouse"></i>
                            <span>Otopark YÃ¶netimi</span>
                        </a>
                    </li>
                    
                    <!-- Service Management -->
                    <li class="nav-item">
                        <a href="#services" class="nav-link" data-section="services">
                            <i class="fas fa-concierge-bell"></i>
                            <span>Hizmet YÃ¶netimi</span>
                        </a>
                    </li>
                    
                    <!-- Support Center -->
                    <li class="nav-item">
                        <a href="#support" class="nav-link" data-section="support">
                            <i class="fas fa-headset"></i>
                            <span>Destek Merkezi</span>
                        </a>
                    </li>
                    
                    <!-- Reviews & Ratings -->
                    <li class="nav-item">
                        <a href="#reviews" class="nav-link" data-section="reviews">
                            <i class="fas fa-star"></i>
                            <span>Yorumlar &amp; Puanlar</span>
                        </a>
                    </li>
                    
                    <!-- Reports -->
                    <li class="nav-item">
                        <a href="#reports" class="nav-link" data-section="reports">
                            <i class="fas fa-chart-bar"></i>
                            <span>Raporlar</span>
                        </a>
                    </li>
                    
                    <!-- Notifications -->
                    <li class="nav-item">
                        <a href="#notifications" class="nav-link" data-section="notifications">
                            <i class="fas fa-bell"></i>
                            <span>Bildirimler</span>
                        </a>
                    </li>
                    
                    <!-- CMS -->
                    <li class="nav-item">
                        <a href="#cms" class="nav-link" data-section="cms">
                            <i class="fas fa-file-alt"></i>
                            <span>Ä°Ã§erik YÃ¶netimi</span>
                        </a>
                    </li>
                    
                    <!-- Security & Logs -->
                    <li class="nav-item">
                        <a href="#security" class="nav-link" data-section="security">
                            <i class="fas fa-shield-alt"></i>
                            <span>GÃ¼venlik &amp; Loglar</span>
                        </a>
                    </li>
                    
                </ul>
            </nav>

            <!-- Settings pinned at bottom -->
            <div class="p-4 border-t border-white/20">
                <a href="#settings" class="flex items-center px-3 py-2 rounded-lg hover:bg-white hover:bg-opacity-10 transition-colors">
                    <i class="fas fa-cog mr-3"></i>
                    <span>Ayarlar</span>
                </a>
            </div>
        </div>
    </aside>

    <!-- Main Content -->
    <!-- FarsÃ§a: Ù…Ø­ØªÙˆØ§ÛŒ Ø§ØµÙ„ÛŒ. -->
    <!-- TÃ¼rkÃ§e: Ana Ä°Ã§erik. -->
    <!-- English: Main Content. -->
    <main class="main-content lg:ml-72">
            <!-- Dashboard Section -->
            <section id="dashboard" class="content-section active">
                <div class="section-header">
                    <div>
                        <h2><i class="fas fa-tachometer-alt icon-blue-mr"></i>Dashboard</h2>
                        <p>Sistem genel bakış ve istatistikler</p>
                    </div>
                </div>
                
                <!-- Key Stats Cards (lazy-loaded fragment) -->
                <div class="deferred-section" data-load-url="<?php echo $base_url; ?>/backend/dashboard/sections/analytics_section.php" id="analytics-deferred" aria-label="Analytics" role="region">
                    <div class="p-6 bg-white rounded-md shadow-sm text-sm text-gray-500">Grafikler yÃ¼kleniyorâ€¦</div>
                </div>
                <noscript>
                    <?php if (file_exists(__DIR__ . '/sections/analytics_section.php')) include __DIR__ . '/sections/analytics_section.php'; ?>
                </noscript>

                <!-- Key Stats Cards -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon stat-icon-grad-1">
                            <i class="fas fa-clipboard-list icon-blue"></i>
                        </div>
                        <div class="stat-info">
                            <h3>156</h3>
                            <p>Toplam SipariÅŸler</p>
                            <small class="text-green-600"><i class="fas fa-arrow-up"></i> +12% bu ay</small>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon stat-icon-grad-2">
                            <i class="fas fa-hourglass-half icon-green"></i>
                        </div>
                        <div class="stat-info">
                            <h3>24</h3>
                            <p>Devam Eden SipariÅŸler</p>
                            <small class="text-blue-600"><i class="fas fa-clock"></i> GerÃ§ek zamanlÄ±</small>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon stat-icon-grad-3">
                            <i class="fas fa-times-circle icon-red"></i>
                        </div>
                        <div class="stat-info">
                            <h3>8</h3>
                            <p>Ä°ptal Edilen</p>
                            <small class="text-red-600"><i class="fas fa-arrow-down"></i> -3% bu ay</small>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon stat-icon-grad-4">
                            <i class="fas fa-lira-sign icon-amber"></i>
                        </div>
                        <div class="stat-info">
                            <h3>â‚º45,680</h3>
                            <p>Günlük Gelir</p>
                            <small class="text-green-600"><i class="fas fa-arrow-up"></i> +25% dÃ¼n</small>
                        </div>
                    </div>
                </div>

                <!-- Charts Row -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                    <div class="activity-section">
                        <h3><i class="fas fa-chart-line mr-2"></i>Gelir Trendi (Son 7 GÃ¼n)</h3>
                        <canvas id="revenueChart" class="canvas-max-300"></canvas>
                    </div>
                    
                    <div class="activity-section">
                        <h3><i class="fas fa-users mr-2"></i>Aktif KullanÄ±cÄ±lar</h3>
                        <canvas id="usersChart" class="canvas-max-300"></canvas>
                    </div>
                </div>

                <!-- Recent Notifications -->
                <div class="activity-section">
                    <h3><i class="fas fa-bell mr-2"></i>Son Bildirimler</h3>
                    <div class="activity-list">
                        <div class="activity-item status-border-left-green">
                            <i class="fas fa-shopping-cart icon-green"></i>
                            <span><strong>Yeni SipariÅŸ:</strong> Ahmet YÄ±lmaz - Tam DetaylandÄ±rma</span>
                            <time>5 dakika Ã¶nce</time>
                        </div>
                        <div class="activity-item status-border-left-red">
                            <i class="fas fa-exclamation-triangle icon-red"></i>
                            <span><strong>Ödeme Hatası:</strong> Kart işlemi başarısız - Sipariş #1245</span>
                            <time>15 dakika Ã¶nce</time>
                        </div>
                        <div class="activity-item status-border-left-blue">
                            <i class="fas fa-headset icon-blue"></i>
                            <span><strong>Destek Talebi:</strong> Elif Kara - SipariÅŸ takibi sorunu</span>
                            <time>1 saat Ã¶nce</time>
                        </div>
                        <div class="activity-item status-border-left-amber">
                            <i class="fas fa-star icon-amber"></i>
                            <span><strong>Yeni Yorum:</strong> 5 yÄ±ldÄ±z - "Harika hizmet!"</span>
                            <time>2 saat Ã¶nce</time>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Car Washes Management Section -->
            <!-- FarsÃ§a: Ø¨Ø®Ø´ Ù…Ø¯ÛŒØ±ÛŒØª Ú©Ø§Ø±ÙˆØ§Ø´â€ŒÙ‡Ø§. -->
            <!-- TÃ¼rkÃ§e: Otopark YÃ¶netimi BÃ¶lÃ¼mÃ¼. -->
            <!-- English: Car Washes Management Section. -->
            <section id="carwashes" class="content-section">
                <div class="section-header">
                    <div>
                        <h2>
                            <i class="fas fa-parking" style="color: #667eea; margin-right: 12px;"></i>
                            Otopark YÃ¶netimi
                        </h2>
                        <p>Otopark iÅŸletmelerini yÃ¶netin</p>
                    </div>
                    <button class="add-btn" id="addCarwashBtn">
                        <i class="fas fa-plus"></i>
                        Yeni Otopark Ekle
                    </button>
                </div>

                <!-- Car Wash Filters -->
                <!-- FarsÃ§a: ÙÛŒÙ„ØªØ±Ù‡Ø§ÛŒ Ú©Ø§Ø±ÙˆØ§Ø´. -->
                <!-- TÃ¼rkÃ§e: Otopark Filtreleri. -->
                <!-- English: Car Wash Filters. -->
                <div class="filters">
                    <label for="carwashSearch" class="sr-only">Otopark ara...</label><input type="text" id="carwashSearch" placeholder="Otopark ara..." class="search-input">
                    <label for="statusFilter" class="sr-only">Input</label><select id="statusFilter" class="filter-select">
                        <option value="">TÃ¼m Durumlar</option>
                        <option value="active">Aktif</option>
                        <option value="inactive">Pasif</option>
                        <option value="maintenance">BakÄ±mda</option>
                    </select>
                </div>

                <!-- Car Washes Table -->
                <!-- FarsÃ§a: Ø¬Ø¯ÙˆÙ„ Ú©Ø§Ø±ÙˆØ§Ø´â€ŒÙ‡Ø§. -->
                <!-- TÃ¼rkÃ§e: Otopark Tablosu. -->
                <!-- English: Car Washes Table. -->
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th><i class="fas fa-hashtag mr-1"></i>ID</th>
                                <th><i class="fas fa-building mr-1"></i>Otopark AdÄ±</th>
                                <th><i class="fas fa-map-marker-alt mr-1"></i>Konum</th>
                                <th><i class="fas fa-car mr-1"></i>Kapasite</th>
                                <th><i class="fas fa-money-bill-wave mr-1"></i>Fiyat</th>
                                <th><i class="fas fa-toggle-on mr-1"></i>Durum</th>
                                <th><i class="fas fa-cogs mr-1"></i>Ä°ÅŸlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><strong>#001</strong></td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 10px;">
                                        <i class="fas fa-building" style="color: #667eea; font-size: 20px;"></i>
                                        <div>
                                            <strong>Merkez Otopark</strong><br>
                                            <small style="color: #64748b;">Premium Tesis</small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <i class="fas fa-map-marker-alt" style="color: #dc3545; margin-right: 6px;"></i>
                                    Taksim, Ä°stanbul
                                </td>
                                <td>
                                    <span style="display: flex; align-items: center; gap: 6px;">
                                        <i class="fas fa-car" style="color: #28a745;"></i>
                                        <strong>50</strong> araÃ§
                                    </span>
                                </td>
                                <td>
                                    <strong style="color: #667eea; font-size: 1.05rem;">â‚º25</strong>/saat
                                </td>
                                <td><span class="status-badge active"><i class="fas fa-check-circle"></i> Aktif</span></td>
                                <td>
                                    <button class="action-btn edit-btn" title="DÃ¼zenle">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="action-btn delete-btn" title="Sil">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    <button class="action-btn view-btn" title="Detay">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>#002</strong></td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 10px;">
                                        <i class="fas fa-warehouse" style="color: #28a745; font-size: 20px;"></i>
                                        <div>
                                            <strong>Åžehir Otopark</strong><br>
                                            <small style="color: #64748b;">Standart Tesis</small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <i class="fas fa-map-marker-alt" style="color: #dc3545; margin-right: 6px;"></i>
                                    KadÄ±kÃ¶y, Ä°stanbul
                                </td>
                                <td>
                                    <span style="display: flex; align-items: center; gap: 6px;">
                                        <i class="fas fa-car" style="color: #28a745;"></i>
                                        <strong>75</strong> araÃ§
                                    </span>
                                </td>
                                <td>
                                    <strong style="color: #667eea; font-size: 1.05rem;">â‚º30</strong>/saat
                                </td>
                                <td><span class="status-badge active"><i class="fas fa-check-circle"></i> Aktif</span></td>
                                <td>
                                    <button class="action-btn edit-btn" title="DÃ¼zenle">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="action-btn delete-btn" title="Sil">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    <button class="action-btn view-btn" title="Detay">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>#003</strong></td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 10px;">
                                        <i class="fas fa-store" style="color: #ffc107; font-size: 20px;"></i>
                                        <div>
                                            <strong>AVM Otopark</strong><br>
                                            <small style="color: #64748b;">Alışveriş Merkezi</small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <i class="fas fa-map-marker-alt" style="color: #dc3545; margin-right: 6px;"></i>
                                    BeÅŸiktaÅŸ, Ä°stanbul
                                </td>
                                <td>
                                    <span style="display: flex; align-items: center; gap: 6px;">
                                        <i class="fas fa-car" style="color: #28a745;"></i>
                                        <strong>120</strong> araÃ§
                                    </span>
                                </td>
                                <td>
                                    <strong style="color: #667eea; font-size: 1.05rem;">â‚º20</strong>/saat
                                </td>
                                <td><span class="status-badge maintenance"><i class="fas fa-tools"></i> BakÄ±mda</span></td>
                                <td>
                                    <button class="action-btn edit-btn" title="DÃ¼zenle">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="action-btn delete-btn" title="Sil">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    <button class="action-btn view-btn" title="Detay">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- Orders Management Section -->
            <section id="orders" class="content-section">
                <div class="section-header">
                    <div>
                        <h2><i class="fas fa-shopping-cart" style="color: #667eea; margin-right: 12px;"></i>SipariÅŸ YÃ¶netimi</h2>
                        <p>TÃ¼m sipariÅŸleri gÃ¶rÃ¼ntÃ¼le ve yÃ¶net</p>
                    </div>
                    <button class="add-btn">
                        <i class="fas fa-file-export"></i>
                        Raporları Dışa Aktar
                    </button>
                </div>

                <!-- Order Filters -->
                <div class="filters" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px;">
                    <label for="orderSearch" class="sr-only">Sipariş No, Müşteri Ara...</label><input type="text" id="orderSearch" placeholder="Sipariş No, Müşteri Ara..." class="search-input" style="grid-column: 1 / -1;">
                    
                    <label for="orderStatusFilter" class="sr-only">Input</label><select id="orderStatusFilter" class="filter-select">
                        <option value="">TÃ¼m Durumlar</option>
                        <option value="pending">Beklemede</option>
                        <option value="in-progress">Devam Ediyor</option>
                        <option value="completed">TamamlandÄ±</option>
                        <option value="cancelled">Ä°ptal Edildi</option>
                    </select>
                    
                    <label for="orderServiceFilter" class="sr-only">Input</label><select id="orderServiceFilter" class="filter-select">
                        <option value="">TÃ¼m Hizmetler</option>
                        <option value="wash">Dış Yıkama</option>
                        <option value="interior">Ä°Ã§ Temizlik</option>
                        <option value="detail">Tam DetaylandÄ±rma</option>
                        <option value="wax">Cilalama</option>
                    </select>
                    
                    <label for="orderDateFrom" class="sr-only">BaÅŸlangÄ±Ã§ Tarihi</label><input type="date" id="orderDateFrom" class="filter-select" placeholder="BaÅŸlangÄ±Ã§ Tarihi">
                    <label for="orderDateTo" class="sr-only">BitiÅŸ Tarihi</label><input type="date" id="orderDateTo" class="filter-select" placeholder="BitiÅŸ Tarihi">
                    
                    <button class="add-btn" style="padding: 10px 20px;">
                        <i class="fas fa-filter"></i> Filtrele
                    </button>
                </div>

                <!-- Orders Table -->
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Sipariş No</th>
                                <th>Müşteri</th>
                                <th>Otopark</th>
                                <th>Hizmet</th>
                                <th>Tutar</th>
                                <th>Durum</th>
                                <th>Tarih</th>
                                <th>Ä°ÅŸlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><strong>#ORD-1245</strong></td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 8px;">
                                        <i class="fas fa-user-circle" style="color: #667eea; font-size: 20px;"></i>
                                        <div>
                                            <div><strong>Ahmet YÄ±lmaz</strong></div>
                                            <small style="color: #64748b;">ahmet@email.com</small>
                                        </div>
                                    </div>
                                </td>
                                <td>Merkez Otopark</td>
                                <td><span class="type-badge" style="background: #28a74520; color: #28a745;">Tam DetaylandÄ±rma</span></td>
                                <td><strong>â‚º350</strong></td>
                                <td><span class="status-badge" style="background: #ffc10720; color: #ff8c00; border: 1px solid #ffc107;">Devam Ediyor</span></td>
                                <td>17 Eki 2025, 14:30</td>
                                <td>
                                    <button class="action-btn view-btn" onclick="viewOrder('1245')" title="DetaylarÄ± GÃ¶rÃ¼ntÃ¼le">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="action-btn edit-btn" onclick="updateOrderStatus('1245')" title="Durum GÃ¼ncelle">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="action-btn" style="background: #28a74520; color: #28a745;" onclick="printInvoice('1245')" title="Fatura YazdÄ±r">
                                        <i class="fas fa-print"></i>
                                    </button>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>#ORD-1244</strong></td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 8px;">
                                        <i class="fas fa-user-circle" style="color: #667eea; font-size: 20px;"></i>
                                        <div>
                                            <div><strong>Elif Kara</strong></div>
                                            <small style="color: #64748b;">elif@email.com</small>
                                        </div>
                                    </div>
                                </td>
                                <td>KadÄ±kÃ¶y Otopark</td>
                                <td><span class="type-badge" style="background: #667eea20; color: #667eea;">Ä°Ã§ Temizlik</span></td>
                                <td><strong>â‚º200</strong></td>
                                <td><span class="status-badge active">TamamlandÄ±</span></td>
                                <td>17 Eki 2025, 12:15</td>
                                <td>
                                    <button class="action-btn view-btn" onclick="viewOrder('1244')">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="action-btn edit-btn" onclick="updateOrderStatus('1244')">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="action-btn" style="background: #28a74520; color: #28a745;" onclick="printInvoice('1244')">
                                        <i class="fas fa-print"></i>
                                    </button>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>#ORD-1243</strong></td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 8px;">
                                        <i class="fas fa-user-circle" style="color: #667eea; font-size: 20px;"></i>
                                        <div>
                                            <div><strong>Mehmet Demir</strong></div>
                                            <small style="color: #64748b;">mehmet@email.com</small>
                                        </div>
                                    </div>
                                </td>
                                <td>BeÅŸiktaÅŸ Otopark</td>
                                <td><span class="type-badge" style="background: #dc354520; color: #dc3545;">Dış Yıkama</span></td>
                                <td><strong>â‚º150</strong></td>
                                <td><span class="status-badge inactive">Ä°ptal Edildi</span></td>
                                <td>16 Eki 2025, 18:45</td>
                                <td>
                                    <button class="action-btn view-btn" onclick="viewOrder('1243')">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="action-btn delete-btn" onclick="deleteOrder('1243')" title="Sil">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    <button class="action-btn" style="background: #28a74520; color: #28a745;" onclick="printInvoice('1243')">
                                        <i class="fas fa-print"></i>
                                    </button>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>#ORD-1242</strong></td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 8px;">
                                        <i class="fas fa-user-circle" style="color: #667eea; font-size: 20px;"></i>
                                        <div>
                                            <div><strong>Zeynep Ã–ztÃ¼rk</strong></div>
                                            <small style="color: #64748b;">zeynep@email.com</small>
                                        </div>
                                    </div>
                                </td>
                                <td>ÅžiÅŸli Otopark</td>
                                <td><span class="type-badge" style="background: #ffc10720; color: #ff8c00;">Cilalama</span></td>
                                <td><strong>â‚º280</strong></td>
                                <td><span class="status-badge pending">Beklemede</span></td>
                                <td>17 Eki 2025, 16:00</td>
                                <td>
                                    <button class="action-btn view-btn" onclick="viewOrder('1242')">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="action-btn edit-btn" onclick="updateOrderStatus('1242')">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="action-btn" style="background: #28a74520; color: #28a745;" onclick="printInvoice('1242')">
                                        <i class="fas fa-print"></i>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 24px; padding: 16px; background: white; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.05);">
                    <div style="color: #64748b;">
                        <strong>156</strong> sipariÅŸten <strong>1-10</strong> arasÄ± gÃ¶steriliyor
                    </div>
                    <div style="display: flex; gap: 8px;">
                        <button class="action-btn" style="padding: 8px 12px;"><i class="fas fa-chevron-left"></i></button>
                        <button class="action-btn" style="padding: 8px 16px; background: #667eea; color: white;">1</button>
                        <button class="action-btn" style="padding: 8px 16px;">2</button>
                        <button class="action-btn" style="padding: 8px 16px;">3</button>
                        <button class="action-btn" style="padding: 8px 16px;">...</button>
                        <button class="action-btn" style="padding: 8px 16px;">16</button>
                        <button class="action-btn" style="padding: 8px 12px;"><i class="fas fa-chevron-right"></i></button>
                    </div>
                </div>
            </section>

            <!-- Payment Management Section -->
            <section id="payments" class="content-section">
                <div class="section-header">
                    <div>
                        <h2><i class="fas fa-credit-card" style="color: #28a745; margin-right: 12px;"></i>Ödeme Yönetimi</h2>
                        <p>Tüm ödeme işlemlerini görüntüle ve yönet</p>
                    </div>
                    <div style="display: flex; gap: 12px;">
                        <button class="add-btn" style="background: #28a745;">
                            <i class="fas fa-file-excel"></i>
                            Excel İndir
                        </button>
                        <button class="add-btn" style="background: #dc3545;">
                            <i class="fas fa-file-pdf"></i>
                            PDF İndir
                        </button>
                    </div>
                </div>

                <!-- Payment Stats Cards -->
                <div class="stats-grid" style="margin-bottom: 24px;">
                    <div class="stat-card">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #28a74520, #20c99720);">
                            <i class="fas fa-check-circle" style="color: #28a745;"></i>
                        </div>
                        <div class="stat-info">
                            <h3>₺128,450</h3>
                            <p>Başarılı Ödemeler</p>
                            <small class="text-green-600">132 işlem</small>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #ffc10720, #ff663320);">
                            <i class="fas fa-clock" style="color: #ffc107;"></i>
                        </div>
                        <div class="stat-info">
                            <h3>₺12,300</h3>
                            <p>Bekleyen Ödemeler</p>
                            <small class="text-yellow-600">8 işlem</small>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #dc354520, #c8233320);">
                            <i class="fas fa-times-circle" style="color: #dc3545;"></i>
                        </div>
                        <div class="stat-info">
                            <h3>₺4,820</h3>
                            <p>Başarısız Ödemeler</p>
                            <small class="text-red-600">12 işlem</small>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #667eea20, #764ba220);">
                            <i class="fas fa-wallet" style="color: #667eea;"></i>
                        </div>
                        <div class="stat-info">
                            <h3>₺8,540</h3>
                            <p>Ödenmesi Gereken</p>
                            <small class="text-blue-600">Otopark ödemeleri</small>
                        </div>
                    </div>
                </div>

                <!-- Payment Filters -->
                <div class="filters" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px;">
                    <label for="paymentSearch" class="sr-only">İşlem No, Müşteri Ara...</label><input type="text" id="paymentSearch" placeholder="İşlem No, Müşteri Ara..." class="search-input" style="grid-column: 1 / -1;">
                    
                    <label for="paymentTypeFilter" class="sr-only">Input</label><select id="paymentTypeFilter" class="filter-select">
                        <option value="">Tüm Ödeme Tipleri</option>
                        <option value="online">Online (Kart)</option>
                        <option value="cash">Nakit</option>
                        <option value="bank">Banka Transferi</option>
                    </select>
                    
                    <label for="paymentStatusFilter" class="sr-only">Input</label><select id="paymentStatusFilter" class="filter-select">
                        <option value="">Tüm Durumlar</option>
                        <option value="success">Başarılı</option>
                        <option value="pending">Beklemede</option>
                        <option value="failed">BaÅŸarÄ±sÄ±z</option>
                        <option value="refunded">Ä°ade Edildi</option>
                    </select>
                    
                    <label for="paymentDateFrom" class="sr-only">Date</label><input type="date" id="paymentDateFrom" class="filter-select">
                    <label for="paymentDateTo" class="sr-only">Date</label><input type="date" id="paymentDateTo" class="filter-select">
                    
                    <button class="add-btn">
                        <i class="fas fa-filter"></i> Filtrele
                    </button>
                </div>

                <!-- Payments Table -->
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Ä°ÅŸlem No</th>
                                <th>SipariÅŸ</th>
                                <th>MÃ¼ÅŸteri</th>
                                <th>Tutar</th>
                                <th>Ã–deme Tipi</th>
                                <th>Durum</th>
                                <th>Tarih</th>
                                <th>Ä°ÅŸlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><strong>#PAY-8821</strong></td>
                                <td><a href="#" style="color: #667eea;">#ORD-1245</a></td>
                                <td>
                                    <div>
                                        <strong>Ahmet YÄ±lmaz</strong><br>
                                        <small style="color: #64748b;">ahmet@email.com</small>
                                    </div>
                                </td>
                                <td><strong style="color: #28a745;">â‚º350</strong></td>
                                <td><span class="type-badge" style="background: #667eea20; color: #667eea;"><i class="fas fa-credit-card"></i> Online</span></td>
                                <td><span class="status-badge active"><i class="fas fa-check"></i> BaÅŸarÄ±lÄ±</span></td>
                                <td>17 Eki 2025, 14:35</td>
                                <td>
                                    <button class="action-btn view-btn" title="DetaylarÄ± GÃ¶r">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="action-btn" style="background: #28a74520; color: #28a745;" title="Fatura">
                                        <i class="fas fa-file-invoice"></i>
                                    </button>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>#PAY-8820</strong></td>
                                <td><a href="#" style="color: #667eea;">#ORD-1244</a></td>
                                <td>
                                    <div>
                                        <strong>Elif Kara</strong><br>
                                        <small style="color: #64748b;">elif@email.com</small>
                                    </div>
                                </td>
                                <td><strong style="color: #28a745;">â‚º200</strong></td>
                                <td><span class="type-badge" style="background: #28a74520; color: #28a745;"><i class="fas fa-money-bill-wave"></i> Nakit</span></td>
                                <td><span class="status-badge active"><i class="fas fa-check"></i> BaÅŸarÄ±lÄ±</span></td>
                                <td>17 Eki 2025, 12:20</td>
                                <td>
                                    <button class="action-btn view-btn">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="action-btn" style="background: #28a74520; color: #28a745;">
                                        <i class="fas fa-file-invoice"></i>
                                    </button>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>#PAY-8819</strong></td>
                                <td><a href="#" style="color: #667eea;">#ORD-1243</a></td>
                                <td>
                                    <div>
                                        <strong>Mehmet Demir</strong><br>
                                        <small style="color: #64748b;">mehmet@email.com</small>
                                    </div>
                                </td>
                                <td><strong style="color: #dc3545;">â‚º150</strong></td>
                                <td><span class="type-badge" style="background: #667eea20; color: #667eea;"><i class="fas fa-credit-card"></i> Online</span></td>
                                <td><span class="status-badge inactive"><i class="fas fa-times"></i> BaÅŸarÄ±sÄ±z</span></td>
                                <td>16 Eki 2025, 18:50</td>
                                <td>
                                    <button class="action-btn view-btn">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="action-btn edit-btn" title="Tekrar Dene">
                                        <i class="fas fa-redo"></i>
                                    </button>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>#PAY-8818</strong></td>
                                <td><a href="#" style="color: #667eea;">#ORD-1242</a></td>
                                <td>
                                    <div>
                                        <strong>Zeynep Ã–ztÃ¼rk</strong><br>
                                        <small style="color: #64748b;">zeynep@email.com</small>
                                    </div>
                                </td>
                                <td><strong style="color: #ffc107;">â‚º280</strong></td>
                                <td><span class="type-badge" style="background: #17a2b820; color: #17a2b8;"><i class="fas fa-university"></i> Banka</span></td>
                                <td><span class="status-badge pending"><i class="fas fa-clock"></i> Beklemede</span></td>
                                <td>17 Eki 2025, 16:05</td>
                                <td>
                                    <button class="action-btn view-btn">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="action-btn edit-btn" title="Onayla">
                                        <i class="fas fa-check"></i>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Settlement Section -->
                <div style="margin-top: 32px;">
                    <h3 style="margin-bottom: 16px;"><i class="fas fa-hand-holding-usd mr-2"></i>Otopark Ã–demeleri (Tasfiye)</h3>
                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Otopark</th>
                                    <th>Toplam Gelir</th>
                                    <th>Komisyon (%15)</th>
                                    <th>Ã–denecek Tutar</th>
                                    <th>Durum</th>
                                    <th>Ä°ÅŸlemler</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><strong>Merkez Otopark</strong></td>
                                    <td>â‚º8,500</td>
                                    <td>-â‚º1,275</td>
                                    <td><strong style="color: #28a745;">â‚º7,225</strong></td>
                                    <td><span class="status-badge pending">Ã–deme Bekliyor</span></td>
                                    <td>
                                        <button class="add-btn" style="padding: 8px 16px; font-size: 14px;">
                                            <i class="fas fa-money-check-alt"></i> Ã–de
                                        </button>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>KadÄ±kÃ¶y Otopark</strong></td>
                                    <td>â‚º5,200</td>
                                    <td>-â‚º780</td>
                                    <td><strong style="color: #28a745;">â‚º4,420</strong></td>
                                    <td><span class="status-badge active">Ã–dendi</span></td>
                                    <td>
                                        <button class="action-btn view-btn">
                                            <i class="fas fa-receipt"></i>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            <!-- Users Management Section -->
            <!-- FarsÃ§a: Ø¨Ø®Ø´ Ù…Ø¯ÛŒØ±ÛŒØª Ú©Ø§Ø±Ø¨Ø±Ø§Ù†. -->
            <!-- TÃ¼rkÃ§e: KullanÄ±cÄ± YÃ¶netimi BÃ¶lÃ¼mÃ¼. -->
            <!-- English: Users Management Section. -->
            <section id="users" class="content-section">
                <div class="section-header">
                    <h2>KullanÄ±cÄ± YÃ¶netimi</h2>
                    <button class="add-btn" id="addUserBtn">
                        <i class="fas fa-plus"></i>
                        Yeni KullanÄ±cÄ± Ekle
                    </button>
                </div>

                <!-- User Filters -->
                <!-- FarsÃ§a: ÙÛŒÙ„ØªØ±Ù‡Ø§ÛŒ Ú©Ø§Ø±Ø¨Ø±. -->
                <!-- TÃ¼rkÃ§e: KullanÄ±cÄ± Filtreleri. -->
                <!-- English: User Filters. -->
                <div class="filters">
                    <label for="userSearch" class="sr-only">KullanÄ±cÄ± ara...</label><input type="text" id="userSearch" placeholder="KullanÄ±cÄ± ara..." class="search-input">
                    <label for="userTypeFilter" class="sr-only">Input</label><select id="userTypeFilter" class="filter-select">
                        <option value="">TÃ¼m Tipler</option>
                        <option value="admin">YÃ¶netici</option>
                        <option value="user">KullanÄ±cÄ±</option>
                        <option value="premium">Premium</option>
                    </select>
                </div>

                <!-- Users Table -->
                <!-- FarsÃ§a: Ø¬Ø¯ÙˆÙ„ Ú©Ø§Ø±Ø¨Ø±Ø§Ù†. -->
                <!-- TÃ¼rkÃ§e: KullanÄ±cÄ± Tablosu. -->
                <!-- English: Users Table. -->
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>KullanÄ±cÄ± AdÄ±</th>
                                <th>Email</th>
                                <th>Telefon</th>
                                <th>Tip</th>
                                <th>KayÄ±t Tarihi</th>
                                <th>Durum</th>
                                <th>Ä°ÅŸlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>001</td>
                                <td>Ahmet YÄ±lmaz</td>
                                <td>ahmet@email.com</td>
                                <td>+90 532 123 4567</td>
                                <td><span class="type-badge premium">Premium</span></td>
                                <td>2024-01-15</td>
                                <td><span class="status-badge active">Aktif</span></td>
                                <td>
                                    <button class="action-btn edit-btn">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="action-btn delete-btn">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    <button class="action-btn view-btn">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </td>
                            </tr>
                            <tr>
                                <td>002</td>
                                <td>Elif Kara</td>
                                <td>elif@email.com</td>
                                <td>+90 535 987 6543</td>
                                <td><span class="type-badge user">KullanÄ±cÄ±</span></td>
                                <td>2024-01-20</td>
                                <td><span class="status-badge active">Aktif</span></td>
                                <td>
                                    <button class="action-btn edit-btn">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="action-btn delete-btn">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    <button class="action-btn view-btn">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </td>
                            </tr>
                            <tr>
                                <td>003</td>
                                <td>Mehmet Demir</td>
                                <td>mehmet@email.com</td>
                                <td>+90 533 456 7890</td>
                                <td><span class="type-badge admin">YÃ¶netici</span></td>
                                <td>2024-01-10</td>
                                <td><span class="status-badge active">Aktif</span></td>
                                <td>
                                    <button class="action-btn edit-btn">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="action-btn delete-btn">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    <button class="action-btn view-btn">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- Service Management Section -->
            <section id="services" class="content-section">
                <div class="section-header">
                    <div>
                        <h2><i class="fas fa-concierge-bell" style="color: #17a2b8; margin-right: 12px;"></i>Hizmet YÃ¶netimi</h2>
                        <p>TÃ¼m hizmetleri yÃ¶net, fiyatlandÄ±r ve dÃ¼zenle</p>
                    </div>
                    <button class="add-btn" id="addServiceBtn">
                        <i class="fas fa-plus"></i>
                        Yeni Hizmet Ekle
                    </button>
                </div>

                <!-- Service Filters -->
                <div class="filters" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px;">
                    <label for="serviceSearch" class="sr-only">Hizmet Ara...</label><input type="text" id="serviceSearch" placeholder="Hizmet Ara..." class="search-input" style="grid-column: 1 / -1;">
                    
                    <label for="serviceCategoryFilter" class="sr-only">Input</label><select id="serviceCategoryFilter" class="filter-select">
                        <option value="">TÃ¼m Kategoriler</option>
                        <option value="wash">YÄ±kama</option>
                        <option value="detail">DetaylÄ± BakÄ±m</option>
                        <option value="polish">Cilalama &amp; Koruma</option>
                        <option value="interior">Ä°Ã§ Temizlik</option>
                    </select>
                    
                    <label for="serviceStatusFilter" class="sr-only">Input</label><select id="serviceStatusFilter" class="filter-select">
                        <option value="">TÃ¼m Durumlar</option>
                        <option value="active">Aktif</option>
                        <option value="inactive">Pasif</option>
                    </select>
                    
                    <button class="add-btn">
                        <i class="fas fa-filter"></i> Filtrele
                    </button>
                </div>

                <!-- Services Table -->
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Hizmet AdÄ±</th>
                                <th>Kategori</th>
                                <th>Temel Fiyat</th>
                                <th>SÃ¼re</th>
                                <th>AraÃ§ Tipleri</th>
                                <th>Durum</th>
                                <th>SÄ±ralama</th>
                                <th>Ä°ÅŸlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><strong>#001</strong></td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 10px;">
                                        <i class="fas fa-car" style="color: #667eea; font-size: 20px;"></i>
                                        <div>
                                            <strong>DÄ±ÅŸ YÄ±kama</strong><br>
                                            <small style="color: #64748b;">Basit dÄ±ÅŸ temizlik</small>
                                        </div>
                                    </div>
                                </td>
                                <td><span class="type-badge" style="background: #667eea20; color: #667eea;">YÄ±kama</span></td>
                                <td><strong>â‚º150</strong></td>
                                <td>30 dk</td>
                                <td>
                                    <small>Sedan: â‚º150<br>SUV: â‚º180<br>Kamyonet: â‚º200</small>
                                </td>
                                <td><span class="status-badge active">Aktif</span></td>
                                <td>1</td>
                                <td>
                                    <button class="action-btn edit-btn" onclick="editService(1)" title="DÃ¼zenle">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="action-btn view-btn" onclick="toggleServiceStatus(1)" title="Aktif/Pasif">
                                        <i class="fas fa-toggle-on"></i>
                                    </button>
                                    <button class="action-btn delete-btn" onclick="deleteService(1)" title="Sil">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>#002</strong></td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 10px;">
                                        <i class="fas fa-broom" style="color: #28a745; font-size: 20px;"></i>
                                        <div>
                                            <strong>Ä°Ã§ Temizlik</strong><br>
                                            <small style="color: #64748b;">DetaylÄ± iÃ§ temizlik</small>
                                        </div>
                                    </div>
                                </td>
                                <td><span class="type-badge" style="background: #28a74520; color: #28a745;">Ä°Ã§ Temizlik</span></td>
                                <td><strong>â‚º200</strong></td>
                                <td>45 dk</td>
                                <td>
                                    <small>Sedan: â‚º200<br>SUV: â‚º250<br>Kamyonet: â‚º300</small>
                                </td>
                                <td><span class="status-badge active">Aktif</span></td>
                                <td>2</td>
                                <td>
                                    <button class="action-btn edit-btn" onclick="editService(2)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="action-btn view-btn" onclick="toggleServiceStatus(2)">
                                        <i class="fas fa-toggle-on"></i>
                                    </button>
                                    <button class="action-btn delete-btn" onclick="deleteService(2)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>#003</strong></td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 10px;">
                                        <i class="fas fa-star" style="color: #ffc107; font-size: 20px;"></i>
                                        <div>
                                            <strong>Tam DetaylandÄ±rma</strong><br>
                                            <small style="color: #64748b;">Komple bakÄ±m paketi</small>
                                        </div>
                                    </div>
                                </td>
                                <td><span class="type-badge" style="background: #ffc10720; color: #ff8c00;">DetaylÄ± BakÄ±m</span></td>
                                <td><strong>â‚º350</strong></td>
                                <td>90 dk</td>
                                <td>
                                    <small>Sedan: â‚º350<br>SUV: â‚º450<br>Kamyonet: â‚º550</small>
                                </td>
                                <td><span class="status-badge active">Aktif</span></td>
                                <td>3</td>
                                <td>
                                    <button class="action-btn edit-btn" onclick="editService(3)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="action-btn view-btn" onclick="toggleServiceStatus(3)">
                                        <i class="fas fa-toggle-on"></i>
                                    </button>
                                    <button class="action-btn delete-btn" onclick="deleteService(3)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>#004</strong></td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 10px;">
                                        <i class="fas fa-shield-alt" style="color: #dc3545; font-size: 20px;"></i>
                                        <div>
                                            <strong>Seramik Kaplama</strong><br>
                                            <small style="color: #64748b;">Uzun Ã¶mÃ¼rlÃ¼ koruma</small>
                                        </div>
                                    </div>
                                </td>
                                <td><span class="type-badge" style="background: #dc354520; color: #dc3545;">Cilalama &amp; Koruma</span></td>
                                <td><strong>â‚º850</strong></td>
                                <td>180 dk</td>
                                <td>
                                    <small>Sedan: â‚º850<br>SUV: â‚º1.050<br>Kamyonet: â‚º1.250</small>
                                </td>
                                <td><span class="status-badge inactive">Pasif</span></td>
                                <td>4</td>
                                <td>
                                    <button class="action-btn edit-btn" onclick="editService(4)">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="action-btn view-btn" onclick="toggleServiceStatus(4)">
                                        <i class="fas fa-toggle-off"></i>
                                    </button>
                                    <button class="action-btn delete-btn" onclick="deleteService(4)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- Support Center Section -->
            <section id="support" class="content-section">
                <div class="section-header">
                    <div>
                        <h2><i class="fas fa-headset" style="color: #fd7e14; margin-right: 12px;"></i>Destek Merkezi</h2>
                        <p>MÃ¼ÅŸteri destek taleplerini yÃ¶net ve yanÄ±tla</p>
                    </div>
                    <button class="add-btn" id="addTicketBtn" style="background: linear-gradient(135deg, #fd7e14, #dc3545);">
                        <i class="fas fa-plus"></i>
                        Yeni Talep OluÅŸtur
                    </button>
                </div>

                <!-- Support Stats -->
                <div class="stats-grid" style="margin-bottom: 24px;">
                    <div class="stat-card">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #dc354520, #fd7e1420);">
                            <i class="fas fa-exclamation-circle" style="color: #dc3545;"></i>
                        </div>
                        <div class="stat-info">
                            <h3>8</h3>
                            <p>Yeni Talepler</p>
                            <small class="text-red-600">YanÄ±t gerekli</small>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #ffc10720, #fd7e1420);">
                            <i class="fas fa-clock" style="color: #ffc107;"></i>
                        </div>
                        <div class="stat-info">
                            <h3>15</h3>
                            <p>Devam Eden</p>
                            <small class="text-yellow-600">Ä°ÅŸlemde</small>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #28a74520, #20c99720);">
                            <i class="fas fa-check-circle" style="color: #28a745;"></i>
                        </div>
                        <div class="stat-info">
                            <h3>142</h3>
                            <p>Ã‡Ã¶zÃ¼mlendi</p>
                            <small class="text-green-600">Bu ay</small>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #667eea20, #764ba220);">
                            <i class="fas fa-user-clock" style="color: #667eea;"></i>
                        </div>
                        <div class="stat-info">
                            <h3>2.5 saat</h3>
                            <p>Ort. YanÄ±t SÃ¼resi</p>
                            <small class="text-blue-600">Son 7 gÃ¼n</small>
                        </div>
                    </div>
                </div>

                <!-- Ticket Filters -->
                <div class="filters" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px;">
                    <label for="ticketSearch" class="sr-only">Talep No, MÃ¼ÅŸteri Ara...</label><input type="text" id="ticketSearch" placeholder="Talep No, MÃ¼ÅŸteri Ara..." class="search-input" style="grid-column: 1 / -1;">
                    
                    <label for="ticketStatusFilter" class="sr-only">Input</label><select id="ticketStatusFilter" class="filter-select">
                        <option value="">TÃ¼m Durumlar</option>
                        <option value="new">Yeni</option>
                        <option value="open">AÃ§Ä±k</option>
                        <option value="in_progress">Devam Ediyor</option>
                        <option value="waiting_on_user">KullanÄ±cÄ± Bekleniyor</option>
                        <option value="resolved">Ã‡Ã¶zÃ¼ldÃ¼</option>
                        <option value="closed">KapalÄ±</option>
                    </select>
                    
                    <label for="ticketPriorityFilter" class="sr-only">Input</label><select id="ticketPriorityFilter" class="filter-select">
                        <option value="">TÃ¼m Ã–ncelikler</option>
                        <option value="urgent">Acil</option>
                        <option value="high">YÃ¼ksek</option>
                        <option value="medium">Orta</option>
                        <option value="low">DÃ¼ÅŸÃ¼k</option>
                    </select>
                    
                    <label for="ticketCategoryFilter" class="sr-only">Input</label><select id="ticketCategoryFilter" class="filter-select">
                        <option value="">TÃ¼m Kategoriler</option>
                        <option value="technical">Teknik</option>
                        <option value="billing">Fatura</option>
                        <option value="service">Hizmet</option>
                        <option value="complaint">Åžikayet</option>
                    </select>
                    
                    <button class="add-btn">
                        <i class="fas fa-filter"></i> Filtrele
                    </button>
                </div>

                <!-- Tickets Table -->
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Talep No</th>
                                <th>MÃ¼ÅŸteri</th>
                                <th>Konu</th>
                                <th>Kategori</th>
                                <th>Ã–ncelik</th>
                                <th>Durum</th>
                                <th>Atanan</th>
                                <th>OluÅŸturma</th>
                                <th>Ä°ÅŸlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><strong>#TKT-1023</strong></td>
                                <td>
                                    <div>
                                        <strong>Elif Kara</strong><br>
                                        <small style="color: #64748b;">elif@email.com</small>
                                    </div>
                                </td>
                                <td>Ã–deme alÄ±namadÄ± hatasÄ±</td>
                                <td><span class="type-badge" style="background: #667eea20; color: #667eea;">Teknik</span></td>
                                <td><span class="status-badge" style="background: #dc354520; color: #dc3545; border: 1px solid #dc3545;">Acil</span></td>
                                <td><span class="status-badge" style="background: #ffc10720; color: #ff8c00; border: 1px solid #ffc107;">AÃ§Ä±k</span></td>
                                <td>Ahmet Y.</td>
                                <td>18 Eki 2025, 10:30</td>
                                <td>
                                    <button class="action-btn view-btn" title="Detay">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="action-btn edit-btn" title="YanÄ±tla">
                                        <i class="fas fa-reply"></i>
                                    </button>
                                    <button class="action-btn" style="background: #28a74520; color: #28a745;" title="Ã‡Ã¶z">
                                        <i class="fas fa-check"></i>
                                    </button>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>#TKT-1022</strong></td>
                                <td>
                                    <div>
                                        <strong>Mehmet Demir</strong><br>
                                        <small style="color: #64748b;">mehmet@email.com</small>
                                    </div>
                                </td>
                                <td>Hizmet kalitesi ÅŸikayeti</td>
                                <td><span class="type-badge" style="background: #dc354520; color: #dc3545;">Åžikayet</span></td>
                                <td><span class="status-badge" style="background: #ffc10720; color: #ff8c00;">YÃ¼ksek</span></td>
                                <td><span class="status-badge" style="background: #667eea20; color: #667eea;">Devam Ediyor</span></td>
                                <td>AyÅŸe K.</td>
                                <td>18 Eki 2025, 09:15</td>
                                <td>
                                    <button class="action-btn view-btn">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="action-btn edit-btn">
                                        <i class="fas fa-reply"></i>
                                    </button>
                                    <button class="action-btn" style="background: #28a74520; color: #28a745;">
                                        <i class="fas fa-check"></i>
                                    </button>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>#TKT-1021</strong></td>
                                <td>
                                    <div>
                                        <strong>Zeynep Ã–ztÃ¼rk</strong><br>
                                        <small style="color: #64748b;">zeynep@email.com</small>
                                    </div>
                                </td>
                                <td>Randevu deÄŸiÅŸikliÄŸi talebi</td>
                                <td><span class="type-badge" style="background: #28a74520; color: #28a745;">Hizmet</span></td>
                                <td><span class="status-badge" style="background: #6c757d20; color: #6c757d;">Orta</span></td>
                                <td><span class="status-badge active">Ã‡Ã¶zÃ¼ldÃ¼</span></td>
                                <td>Mehmet A.</td>
                                <td>17 Eki 2025, 16:45</td>
                                <td>
                                    <button class="action-btn view-btn">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="action-btn" style="background: #dc354520; color: #dc3545;" title="Kapat">
                                        <i class="fas fa-times-circle"></i>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- Reviews & Ratings Section -->
            <section id="reviews" class="content-section">
                <div class="section-header">
                    <div>
                        <h2><i class="fas fa-star" style="color: #ffc107; margin-right: 12px;"></i>Yorumlar &amp; Puanlar</h2>
                        <p>MÃ¼ÅŸteri yorumlarÄ±nÄ± yÃ¶net ve denetle</p>
                    </div>
                    <button class="add-btn" style="background: linear-gradient(135deg, #ffc107, #ff9800);">
                        <i class="fas fa-file-export"></i>
                        RaporlarÄ± DÄ±ÅŸa Aktar
                    </button>
                </div>

                <!-- Review Stats -->
                <div class="stats-grid" style="margin-bottom: 24px;">
                    <div class="stat-card">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #ffc10720, #ff980020);">
                            <i class="fas fa-star" style="color: #ffc107;"></i>
                        </div>
                        <div class="stat-info">
                            <h3>4.8</h3>
                            <p>Ortalama Puan</p>
                            <small class="text-yellow-600">248 deÄŸerlendirme</small>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #28a74520, #20c99720);">
                            <i class="fas fa-check-circle" style="color: #28a745;"></i>
                        </div>
                        <div class="stat-info">
                            <h3>186</h3>
                            <p>OnaylanmÄ±ÅŸ</p>
                            <small class="text-green-600">GÃ¶rÃ¼ntÃ¼leniyor</small>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #ffc10720, #fd7e1420);">
                            <i class="fas fa-clock" style="color: #ffc107;"></i>
                        </div>
                        <div class="stat-info">
                            <h3>12</h3>
                            <p>Beklemede</p>
                            <small class="text-orange-600">Moderasyon gerekli</small>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #dc354520, #c8233320);">
                            <i class="fas fa-flag" style="color: #dc3545;"></i>
                        </div>
                        <div class="stat-info">
                            <h3>5</h3>
                            <p>Raporlanan</p>
                            <small class="text-red-600">Ä°nceleme gerekli</small>
                        </div>
                    </div>
                </div>

                <!-- Review Filters -->
                <div class="filters" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px;">
                    <label for="reviewSearch" class="sr-only">MÃ¼ÅŸteri, SipariÅŸ Ara...</label><input type="text" id="reviewSearch" placeholder="MÃ¼ÅŸteri, SipariÅŸ Ara..." class="search-input" style="grid-column: 1 / -1;">
                    
                    <label for="reviewStatusFilter" class="sr-only">Input</label><select id="reviewStatusFilter" class="filter-select">
                        <option value="">TÃ¼m Durumlar</option>
                        <option value="pending">Beklemede</option>
                        <option value="approved">OnaylandÄ±</option>
                        <option value="rejected">Reddedildi</option>
                        <option value="flagged">RaporlandÄ±</option>
                    </select>
                    
                    <label for="reviewRatingFilter" class="sr-only">Input</label><select id="reviewRatingFilter" class="filter-select">
                        <option value="">TÃ¼m Puanlar</option>
                        <option value="5">5 YÄ±ldÄ±z</option>
                        <option value="4">4 YÄ±ldÄ±z</option>
                        <option value="3">3 YÄ±ldÄ±z</option>
                        <option value="2">2 YÄ±ldÄ±z</option>
                        <option value="1">1 YÄ±ldÄ±z</option>
                    </select>
                    
                    <label for="reviewDateFrom" class="sr-only">Date</label><input type="date" id="reviewDateFrom" class="filter-select">
                    
                    <button class="add-btn">
                        <i class="fas fa-filter"></i> Filtrele
                    </button>
                </div>

                <!-- Reviews Table -->
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>MÃ¼ÅŸteri</th>
                                <th>SipariÅŸ</th>
                                <th>Puan</th>
                                <th>Yorum</th>
                                <th>Durum</th>
                                <th>Tarih</th>
                                <th>Ä°ÅŸlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><strong>#REV-245</strong></td>
                                <td>
                                    <div>
                                        <strong>Ahmet YÄ±lmaz</strong><br>
                                        <small style="color: #64748b;">Merkez Otopark</small>
                                    </div>
                                </td>
                                <td><a href="#" style="color: #667eea;">#ORD-1245</a></td>
                                <td>
                                    <div style="color: #ffc107;">
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <strong style="color: #333; margin-left: 5px;">5.0</strong>
                                    </div>
                                </td>
                                <td style="max-width: 300px;">
                                    <small>"Harika hizmet! Ã‡ok memnun kaldÄ±m. Kesinlikle tavsiye ederim."</small>
                                </td>
                                <td><span class="status-badge" style="background: #ffc10720; color: #ff8c00; border: 1px solid #ffc107;">Beklemede</span></td>
                                <td>18 Eki 2025</td>
                                <td>
                                    <button class="action-btn view-btn" title="Detay">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="action-btn" style="background: #28a74520; color: #28a745;" title="Onayla">
                                        <i class="fas fa-check"></i>
                                    </button>
                                    <button class="action-btn delete-btn" title="Reddet">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>#REV-244</strong></td>
                                <td>
                                    <div>
                                        <strong>Elif Kara</strong><br>
                                        <small style="color: #64748b;">KadÄ±kÃ¶y Otopark</small>
                                    </div>
                                </td>
                                <td><a href="#" style="color: #667eea;">#ORD-1244</a></td>
                                <td>
                                    <div style="color: #ffc107;">
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="far fa-star"></i>
                                        <strong style="color: #333; margin-left: 5px;">4.0</strong>
                                    </div>
                                </td>
                                <td style="max-width: 300px;">
                                    <small>"Ä°yi hizmet ama biraz pahalÄ± buldum."</small>
                                </td>
                                <td><span class="status-badge active">OnaylandÄ±</span></td>
                                <td>17 Eki 2025</td>
                                <td>
                                    <button class="action-btn view-btn">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="action-btn edit-btn" title="YanÄ±tla">
                                        <i class="fas fa-reply"></i>
                                    </button>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>#REV-243</strong></td>
                                <td>
                                    <div>
                                        <strong>Mehmet Demir</strong><br>
                                        <small style="color: #64748b;">BeÅŸiktaÅŸ Otopark</small>
                                    </div>
                                </td>
                                <td><a href="#" style="color: #667eea;">#ORD-1243</a></td>
                                <td>
                                    <div style="color: #ffc107;">
                                        <i class="fas fa-star"></i>
                                        <i class="fas fa-star"></i>
                                        <i class="far fa-star"></i>
                                        <i class="far fa-star"></i>
                                        <i class="far fa-star"></i>
                                        <strong style="color: #333; margin-left: 5px;">2.0</strong>
                                    </div>
                                </td>
                                <td style="max-width: 300px;">
                                    <small>"Randevuya geÃ§ kaldÄ±lar ve iÅŸlem hatalÄ±ydÄ±. Memnun kalmadÄ±m."</small>
                                </td>
                                <td><span class="status-badge inactive">Reddedildi</span></td>
                                <td>16 Eki 2025</td>
                                <td>
                                    <button class="action-btn view-btn">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="action-btn" style="background: #dc354520; color: #dc3545;" title="Raporla">
                                        <i class="fas fa-flag"></i>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- Reports Section -->
            <!-- FarsÃ§a: Ø¨Ø®Ø´ Ú¯Ø²Ø§Ø±Ø´Ø§Øª. -->
            <!-- TÃ¼rkÃ§e: Raporlar BÃ¶lÃ¼mÃ¼. -->
            <!-- English: Reports Section. -->
            <section id="reports" class="content-section">
                <div class="section-header">
                    <div>
                        <h2><i class="fas fa-chart-line" style="color: #667eea; margin-right: 12px;"></i>Raporlar ve Analizler</h2>
                        <p>DetaylÄ± iÅŸ analizleri ve raporlarÄ± oluÅŸtur</p>
                    </div>
                </div>

                <!-- Report Stats Overview -->
                <div class="stats-grid" style="margin-bottom: 32px;">
                    <div class="stat-card">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #667eea20, #764ba220);">
                            <i class="fas fa-file-alt" style="color: #667eea;"></i>
                        </div>
                        <div class="stat-info">
                            <h3>248</h3>
                            <p>Toplam Rapor</p>
                            <small style="color: #28a745;"><i class="fas fa-arrow-up"></i> Bu ay 42 rapor</small>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #28a74520, #20c99720);">
                            <i class="fas fa-download" style="color: #28a745;"></i>
                        </div>
                        <div class="stat-info">
                            <h3>1,234</h3>
                            <p>Ä°ndirilen Raporlar</p>
                            <small style="color: #666;"><i class="fas fa-calendar"></i> Son 30 gÃ¼n</small>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #ffc10720, #ff663320);">
                            <i class="fas fa-clock" style="color: #ffc107;"></i>
                        </div>
                        <div class="stat-info">
                            <h3>15</h3>
                            <p>ZamanlanmÄ±ÅŸ Raporlar</p>
                            <small style="color: #666;"><i class="fas fa-bell"></i> Otomatik</small>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #17a2b820, #00bcd420);">
                            <i class="fas fa-chart-bar" style="color: #17a2b8;"></i>
                        </div>
                        <div class="stat-info">
                            <h3>8</h3>
                            <p>Rapor TÃ¼rleri</p>
                            <small style="color: #666;"><i class="fas fa-layer-group"></i> KullanÄ±labilir</small>
                        </div>
                    </div>
                </div>

                <!-- Report Categories Tabs -->
                <div style="margin-bottom: 24px;">
                    <div style="display: flex; gap: 12px; flex-wrap: wrap; background: white; padding: 16px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.08);">
                        <button class="report-tab-btn active" onclick="showReportCategory('financial')" style="padding: 10px 20px; border: 2px solid #667eea; background: linear-gradient(135deg, #667eea, #764ba2); color: white; border-radius: 8px; cursor: pointer; font-weight: 500; transition: all 0.3s;">
                            <i class="fas fa-money-bill-wave"></i> Finansal
                        </button>
                        <button class="report-tab-btn" onclick="showReportCategory('operational')" style="padding: 10px 20px; border: 2px solid #e9ecef; background: white; color: #666; border-radius: 8px; cursor: pointer; font-weight: 500; transition: all 0.3s;">
                            <i class="fas fa-cogs"></i> Operasyonel
                        </button>
                        <button class="report-tab-btn" onclick="showReportCategory('customer')" style="padding: 10px 20px; border: 2px solid #e9ecef; background: white; color: #666; border-radius: 8px; cursor: pointer; font-weight: 500; transition: all 0.3s;">
                            <i class="fas fa-users"></i> MÃ¼ÅŸteri
                        </button>
                        <button class="report-tab-btn" onclick="showReportCategory('performance')" style="padding: 10px 20px; border: 2px solid #e9ecef; background: white; color: #666; border-radius: 8px; cursor: pointer; font-weight: 500; transition: all 0.3s;">
                            <i class="fas fa-chart-pie"></i> Performans
                        </button>
                    </div>
                </div>

                <!-- Financial Reports Tab -->
                <div id="financial-reports" class="report-category active">
                    <h3 style="margin-bottom: 20px; color: #333; display: flex; align-items: center; gap: 10px;">
                        <i class="fas fa-money-bill-wave" style="color: #28a745;"></i>
                        Finansal Raporlar
                    </h3>
                    
                    <div class="reports-grid" style="grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));">
                        <!-- Revenue Report -->
                        <div class="report-card" style="border-left: 4px solid #28a745;">
                            <div style="display: flex; align-items: start; gap: 16px; margin-bottom: 16px;">
                                <div style="width: 50px; height: 50px; background: linear-gradient(135deg, #28a74520, #20c99720); border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                    <i class="fas fa-chart-line" style="color: #28a745; font-size: 1.3rem;"></i>
                                </div>
                                <div style="flex: 1;">
                                    <h3 style="margin: 0 0 8px 0; font-size: 1.1rem;">Gelir Raporu</h3>
                                    <p style="margin: 0; font-size: 0.85rem; color: #666;">Toplam gelir, Ã¶demeler ve kar marjÄ± analizi</p>
                                </div>
                            </div>
                            
                            <div style="background: #f8f9fa; padding: 12px; border-radius: 8px; margin-bottom: 16px;">
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                                    <div>
                                        <small style="color: #666; display: block; margin-bottom: 4px;">Toplam Gelir</small>
                                        <strong style="color: #28a745; font-size: 1.1rem;">â‚º245,890</strong>
                                    </div>
                                    <div>
                                        <small style="color: #666; display: block; margin-bottom: 4px;">Net Kar</small>
                                        <strong style="color: #667eea; font-size: 1.1rem;">â‚º198,340</strong>
                                    </div>
                                    <div>
                                        <small style="color: #666; display: block; margin-bottom: 4px;">Ä°ÅŸlemler</small>
                                        <strong style="color: #333;">1,234</strong>
                                    </div>
                                    <div>
                                        <small style="color: #666; display: block; margin-bottom: 4px;">Ort. SipariÅŸ</small>
                                        <strong style="color: #333;">â‚º199</strong>
                                    </div>
                                </div>
                            </div>
                            
                            <div style="display: flex; gap: 8px; margin-bottom: 12px;">
                                <label for="auto_2" class="sr-only">Date</label>
                                <input type="date" class="filter-select" style="flex: 1; font-size: 0.85rem;" value="2025-10-01" id="auto_2">
                                <label for="auto_3" class="sr-only">Date</label>
                                <input type="date" class="filter-select" style="flex: 1; font-size: 0.85rem;" value="2025-10-19" id="auto_3">
                            </div>
                            
                            <div style="display: flex; gap: 8px;">
                                <button class="report-btn" onclick="downloadReport('revenue', 'pdf')" style="flex: 1; display: flex; align-items: center; justify-content: center; gap: 6px; font-size: 0.9rem; padding: 10px;">
                                    <i class="fas fa-file-pdf"></i> PDF
                                </button>
                                <button class="report-btn" onclick="downloadReport('revenue', 'excel')" style="flex: 1; display: flex; align-items: center; justify-content: center; gap: 6px; font-size: 0.9rem; padding: 10px; background: linear-gradient(135deg, #28a745, #20c997);">
                                    <i class="fas fa-file-excel"></i> Excel
                                </button>
                                <button class="report-btn" onclick="downloadReport('revenue', 'csv')" style="flex: 1; display: flex; align-items: center; justify-content: center; gap: 6px; font-size: 0.9rem; padding: 10px; background: linear-gradient(135deg, #17a2b8, #00bcd4);">
                                    <i class="fas fa-file-csv"></i> CSV
                                </button>
                            </div>
                        </div>

                        <!-- Payment Analysis Report -->
                        <div class="report-card" style="border-left: 4px solid #667eea;">
                            <div style="display: flex; align-items: start; gap: 16px; margin-bottom: 16px;">
                                <div style="width: 50px; height: 50px; background: linear-gradient(135deg, #667eea20, #764ba220); border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                    <i class="fas fa-credit-card" style="color: #667eea; font-size: 1.3rem;"></i>
                                </div>
                                <div style="flex: 1;">
                                    <h3 style="margin: 0 0 8px 0; font-size: 1.1rem;">Ã–deme Analizi</h3>
                                    <p style="margin: 0; font-size: 0.85rem; color: #666;">Ã–deme yÃ¶ntemleri, baÅŸarÄ± oranlarÄ± ve iade analizi</p>
                                </div>
                            </div>
                            
                            <div style="background: #f8f9fa; padding: 12px; border-radius: 8px; margin-bottom: 16px;">
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                                    <div>
                                        <small style="color: #666; display: block; margin-bottom: 4px;">BaÅŸarÄ±lÄ±</small>
                                        <strong style="color: #28a745; font-size: 1.1rem;">%94.5</strong>
                                    </div>
                                    <div>
                                        <small style="color: #666; display: block; margin-bottom: 4px;">BaÅŸarÄ±sÄ±z</small>
                                        <strong style="color: #dc3545; font-size: 1.1rem;">%5.5</strong>
                                    </div>
                                    <div>
                                        <small style="color: #666; display: block; margin-bottom: 4px;">Kredi KartÄ±</small>
                                        <strong style="color: #333;">%68</strong>
                                    </div>
                                    <div>
                                        <small style="color: #666; display: block; margin-bottom: 4px;">Nakit</small>
                                        <strong style="color: #333;">%32</strong>
                                    </div>
                                </div>
                            </div>
                            
                            <div style="display: flex; gap: 8px; margin-bottom: 12px;">
                                <label for="auto_4" class="sr-only">Input</label><select class="filter-select" style="flex: 1; font-size: 0.85rem;" id="auto_4">
                                    <option>Son 7 GÃ¼n</option>
                                    <option>Son 30 GÃ¼n</option>
                                    <option>Son 3 Ay</option>
                                    <option>Bu YÄ±l</option>
                                </select>
                            </div>
                            
                            <div style="display: flex; gap: 8px;">
                                <button class="report-btn" onclick="downloadReport('payment', 'pdf')" style="flex: 1; display: flex; align-items: center; justify-content: center; gap: 6px; font-size: 0.9rem; padding: 10px;">
                                    <i class="fas fa-file-pdf"></i> PDF
                                </button>
                                <button class="report-btn" onclick="downloadReport('payment', 'excel')" style="flex: 1; display: flex; align-items: center; justify-content: center; gap: 6px; font-size: 0.9rem; padding: 10px; background: linear-gradient(135deg, #28a745, #20c997);">
                                    <i class="fas fa-file-excel"></i> Excel
                                </button>
                                <button class="report-btn" onclick="downloadReport('payment', 'csv')" style="flex: 1; display: flex; align-items: center; justify-content: center; gap: 6px; font-size: 0.9rem; padding: 10px; background: linear-gradient(135deg, #17a2b8, #00bcd4);">
                                    <i class="fas fa-file-csv"></i> CSV
                                </button>
                            </div>
                        </div>

                        <!-- Tax Report -->
                        <div class="report-card" style="border-left: 4px solid #ffc107;">
                            <div style="display: flex; align-items: start; gap: 16px; margin-bottom: 16px;">
                                <div style="width: 50px; height: 50px; background: linear-gradient(135deg, #ffc10720, #ff663320); border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                    <i class="fas fa-receipt" style="color: #ffc107; font-size: 1.3rem;"></i>
                                </div>
                                <div style="flex: 1;">
                                    <h3 style="margin: 0 0 8px 0; font-size: 1.1rem;">Vergi Raporu</h3>
                                    <p style="margin: 0; font-size: 0.85rem; color: #666;">KDV, gelir vergisi ve mali beyanlar</p>
                                </div>
                            </div>
                            
                            <div style="background: #f8f9fa; padding: 12px; border-radius: 8px; margin-bottom: 16px;">
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                                    <div>
                                        <small style="color: #666; display: block; margin-bottom: 4px;">Toplam KDV</small>
                                        <strong style="color: #ffc107; font-size: 1.1rem;">â‚º44,260</strong>
                                    </div>
                                    <div>
                                        <small style="color: #666; display: block; margin-bottom: 4px;">Gelir Vergisi</small>
                                        <strong style="color: #fd7e14; font-size: 1.1rem;">â‚º36,870</strong>
                                    </div>
                                    <div>
                                        <small style="color: #666; display: block; margin-bottom: 4px;">Faturalar</small>
                                        <strong style="color: #333;">1,156</strong>
                                    </div>
                                    <div>
                                        <small style="color: #666; display: block; margin-bottom: 4px;">Beyanlar</small>
                                        <strong style="color: #333;">12</strong>
                                    </div>
                                </div>
                            </div>
                            
                            <div style="display: flex; gap: 8px; margin-bottom: 12px;">
                                <label for="auto_5" class="sr-only">Input</label>
                                <select class="filter-select" style="flex: 1; font-size: 0.85rem;" id="auto_5">
                                    <option>Q3 2025</option>
                                    <option>Q2 2025</option>
                                    <option>Q1 2025</option>
                                    <option>2024</option>
                                </select>
                            </div>
                            
                            <div style="display: flex; gap: 8px;">
                                <button class="report-btn" onclick="downloadReport('tax', 'pdf')" style="flex: 1; display: flex; align-items: center; justify-content: center; gap: 6px; font-size: 0.9rem; padding: 10px;">
                                    <i class="fas fa-file-pdf"></i> PDF
                                </button>
                                <button class="report-btn" onclick="downloadReport('tax', 'excel')" style="flex: 1; display: flex; align-items: center; justify-content: center; gap: 6px; font-size: 0.9rem; padding: 10px; background: linear-gradient(135deg, #28a745, #20c997);">
                                    <i class="fas fa-file-excel"></i> Excel
                                </button>
                            </div>
                        </div>

                        <!-- Commission Report -->
                        <div class="report-card" style="border-left: 4px solid #17a2b8;">
                            <div style="display: flex; align-items: start; gap: 16px; margin-bottom: 16px;">
                                <div style="width: 50px; height: 50px; background: linear-gradient(135deg, #17a2b820, #00bcd420); border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                    <i class="fas fa-hand-holding-usd" style="color: #17a2b8; font-size: 1.3rem;"></i>
                                </div>
                                <div style="flex: 1;">
                                    <h3 style="margin: 0 0 8px 0; font-size: 1.1rem;">Komisyon Raporu</h3>
                                    <p style="margin: 0; font-size: 0.85rem; color: #666;">Otopark komisyonlarÄ± ve Ã¶demeler</p>
                                </div>
                            </div>
                            
                            <div style="background: #f8f9fa; padding: 12px; border-radius: 8px; margin-bottom: 16px;">
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                                    <div>
                                        <small style="color: #666; display: block; margin-bottom: 4px;">Toplam Komisyon</small>
                                        <strong style="color: #17a2b8; font-size: 1.1rem;">â‚º36,883</strong>
                                    </div>
                                    <div>
                                        <small style="color: #666; display: block; margin-bottom: 4px;">Ã–denen</small>
                                        <strong style="color: #28a745; font-size: 1.1rem;">â‚º28,343</strong>
                                    </div>
                                    <div>
                                        <small style="color: #666; display: block; margin-bottom: 4px;">Bekleyen</small>
                                        <strong style="color: #ffc107;">â‚º8,540</strong>
                                    </div>
                                    <div>
                                        <small style="color: #666; display: block; margin-bottom: 4px;">Otopark SayÄ±sÄ±</small>
                                        <strong style="color: #333;">24</strong>
                                    </div>
                                </div>
                            </div>
                            
                            <div style="display: flex; gap: 8px; margin-bottom: 12px;">
                                <label for="auto_6" class="sr-only">Input</label>
                                <input type="month" class="filter-select" style="flex: 1; font-size: 0.85rem;" value="2025-10" id="auto_6">
                            </div>
                            
                            <div style="display: flex; gap: 8px;">
                                <button class="report-btn" onclick="downloadReport('commission', 'pdf')" style="flex: 1; display: flex; align-items: center; justify-content: center; gap: 6px; font-size: 0.9rem; padding: 10px;">
                                    <i class="fas fa-file-pdf"></i> PDF
                                </button>
                                <button class="report-btn" onclick="downloadReport('commission', 'excel')" style="flex: 1; display: flex; align-items: center; justify-content: center; gap: 6px; font-size: 0.9rem; padding: 10px; background: linear-gradient(135deg, #28a745, #20c997);">
                                    <i class="fas fa-file-excel"></i> Excel
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Operational Reports Tab -->
                <div id="operational-reports" class="report-category" style="display: none;">
                    <h3 style="margin-bottom: 20px; color: #333; display: flex; align-items: center; gap: 10px;">
                        <i class="fas fa-cogs" style="color: #667eea;"></i>
                        Operasyonel Raporlar
                    </h3>
                    
                    <div class="reports-grid" style="grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));">
                        <!-- Order Report -->
                        <div class="report-card" style="border-left: 4px solid #667eea;">
                            <div style="display: flex; align-items: start; gap: 16px; margin-bottom: 16px;">
                                <div style="width: 50px; height: 50px; background: linear-gradient(135deg, #667eea20, #764ba220); border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                    <i class="fas fa-clipboard-list" style="color: #667eea; font-size: 1.3rem;"></i>
                                </div>
                                <div style="flex: 1;">
                                    <h3 style="margin: 0 0 8px 0; font-size: 1.1rem;">SipariÅŸ Raporu</h3>
                                    <p style="margin: 0; font-size: 0.85rem; color: #666;">Tamamlanan, iptal edilen ve bekleyen sipariÅŸler</p>
                                </div>
                            </div>
                            
                            <div style="background: #f8f9fa; padding: 12px; border-radius: 8px; margin-bottom: 16px;">
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                                    <div>
                                        <small style="color: #666; display: block; margin-bottom: 4px;">Toplam SipariÅŸ</small>
                                        <strong style="color: #667eea; font-size: 1.1rem;">1,456</strong>
                                    </div>
                                    <div>
                                        <small style="color: #666; display: block; margin-bottom: 4px;">Tamamlanan</small>
                                        <strong style="color: #28a745; font-size: 1.1rem;">1,368</strong>
                                    </div>
                                    <div>
                                        <small style="color: #666; display: block; margin-bottom: 4px;">Ä°ptal Edilen</small>
                                        <strong style="color: #dc3545;">64</strong>
                                    </div>
                                    <div>
                                        <small style="color: #666; display: block; margin-bottom: 4px;">Devam Eden</small>
                                        <strong style="color: #ffc107;">24</strong>
                                    </div>
                                </div>
                            </div>
                            
                            <div style="display: flex; gap: 8px; margin-bottom: 12px;">
                                <label for="auto_7" class="sr-only">Date From</label>
                                <input type="date" class="filter-select" style="flex: 1; font-size: 0.85rem;" value="2025-10-01" id="auto_7">
                                <label for="auto_8" class="sr-only">Date To</label>
                                <input type="date" class="filter-select" style="flex: 1; font-size: 0.85rem;" value="2025-10-19" id="auto_8">
                            </div>
                            
                            <div style="display: flex; gap: 8px;">
                                <button class="report-btn" onclick="downloadReport('orders', 'pdf')" style="flex: 1; display: flex; align-items: center; justify-content: center; gap: 6px; font-size: 0.9rem; padding: 10px;">
                                    <i class="fas fa-file-pdf"></i> PDF
                                </button>
                                <button class="report-btn" onclick="downloadReport('orders', 'excel')" style="flex: 1; display: flex; align-items: center; justify-content: center; gap: 6px; font-size: 0.9rem; padding: 10px; background: linear-gradient(135deg, #28a745, #20c997);">
                                    <i class="fas fa-file-excel"></i> Excel
                                </button>
                            </div>
                        </div>

                        <!-- Service Performance Report -->
                        <div class="report-card" style="border-left: 4px solid #28a745;">
                            <div style="display: flex; align-items: start; gap: 16px; margin-bottom: 16px;">
                                <div style="width: 50px; height: 50px; background: linear-gradient(135deg, #28a74520, #20c99720); border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                    <i class="fas fa-tools" style="color: #28a745; font-size: 1.3rem;"></i>
                                </div>
                                <div style="flex: 1;">
                                    <h3 style="margin: 0 0 8px 0; font-size: 1.1rem;">Hizmet PerformansÄ±</h3>
                                    <p style="margin: 0; font-size: 0.85rem; color: #666;">En Ã§ok tercih edilen hizmetler ve sÃ¼re analizi</p>
                                </div>
                            </div>
                            
                            <div style="background: #f8f9fa; padding: 12px; border-radius: 8px; margin-bottom: 16px;">
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                                    <div>
                                        <small style="color: #666; display: block; margin-bottom: 4px;">Aktif Hizmetler</small>
                                        <strong style="color: #28a745; font-size: 1.1rem;">34</strong>
                                    </div>
                                    <div>
                                        <small style="color: #666; display: block; margin-bottom: 4px;">Toplam KullanÄ±m</small>
                                        <strong style="color: #667eea; font-size: 1.1rem;">2,876</strong>
                                    </div>
                                    <div>
                                        <small style="color: #666; display: block; margin-bottom: 4px;">Ort. SÃ¼re</small>
                                        <strong style="color: #333;">45 dk</strong>
                                    </div>
                                    <div>
                                        <small style="color: #666; display: block; margin-bottom: 4px;">Memnuniyet</small>
                                        <strong style="color: #ffc107;">4.7â˜…</strong>
                                    </div>
                                </div>
                            </div>
                            
                            <div style="display: flex; gap: 8px; margin-bottom: 12px;">
                                <label for="auto_9" class="sr-only">Input</label>
                                <select class="filter-select" style="flex: 1; font-size: 0.85rem;" id="auto_9">
                                    <option>Bu Ay</option>
                                    <option>Son 3 Ay</option>
                                    <option>Bu YÄ±l</option>
                                </select>
                            </div>
                            
                            <div style="display: flex; gap: 8px;">
                                <button class="report-btn" onclick="downloadReport('services', 'pdf')" style="flex: 1; display: flex; align-items: center; justify-content: center; gap: 6px; font-size: 0.9rem; padding: 10px;">
                                    <i class="fas fa-file-pdf"></i> PDF
                                </button>
                                <button class="report-btn" onclick="downloadReport('services', 'excel')" style="flex: 1; display: flex; align-items: center; justify-content: center; gap: 6px; font-size: 0.9rem; padding: 10px; background: linear-gradient(135deg, #28a745, #20c997);">
                                    <i class="fas fa-file-excel"></i> Excel
                                </button>
                            </div>
                        </div>

                        <!-- Carwash Performance Report -->
                        <div class="report-card" style="border-left: 4px solid #fd7e14;">
                            <div style="display: flex; align-items: start; gap: 16px; margin-bottom: 16px;">
                                <div style="width: 50px; height: 50px; background: linear-gradient(135deg, #fd7e1420, #dc354520); border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                    <i class="fas fa-car" style="color: #fd7e14; font-size: 1.3rem;"></i>
                                </div>
                                <div style="flex: 1;">
                                    <h3 style="margin: 0 0 8px 0; font-size: 1.1rem;">Otopark PerformansÄ±</h3>
                                    <p style="margin: 0; font-size: 0.85rem; color: #666;">Otopark bazlÄ± performans ve gelir analizi</p>
                                </div>
                            </div>
                            
                            <div style="background: #f8f9fa; padding: 12px; border-radius: 8px; margin-bottom: 16px;">
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                                    <div>
                                        <small style="color: #666; display: block; margin-bottom: 4px;">Toplam Otopark</small>
                                        <strong style="color: #fd7e14; font-size: 1.1rem;">24</strong>
                                    </div>
                                    <div>
                                        <small style="color: #666; display: block; margin-bottom: 4px;">Aktif</small>
                                        <strong style="color: #28a745; font-size: 1.1rem;">22</strong>
                                    </div>
                                    <div>
                                        <small style="color: #666; display: block; margin-bottom: 4px;">En YÃ¼ksek Gelir</small>
                                        <strong style="color: #333;">â‚º45K</strong>
                                    </div>
                                    <div>
                                        <small style="color: #666; display: block; margin-bottom: 4px;">Kapasite</small>
                                        <strong style="color: #333;">%78</strong>
                                    </div>
                                </div>
                            </div>
                            
                            <div style="display: flex; gap: 8px; margin-bottom: 12px;">
                                <label for="auto_10" class="sr-only">Input</label>
                                <select class="filter-select" style="flex: 1; font-size: 0.85rem;" id="auto_10">
                                    <option>TÃ¼m Otoparklar</option>
                                    <option>En Ä°yi 10</option>
                                    <option>En DÃ¼ÅŸÃ¼k 10</option>
                                </select>
                            </div>
                            
                            <div style="display: flex; gap: 8px;">
                                <button class="report-btn" onclick="downloadReport('carwash', 'pdf')" style="flex: 1; display: flex; align-items: center; justify-content: center; gap: 6px; font-size: 0.9rem; padding: 10px;">
                                    <i class="fas fa-file-pdf"></i> PDF
                                </button>
                                <button class="report-btn" onclick="downloadReport('carwash', 'excel')" style="flex: 1; display: flex; align-items: center; justify-content: center; gap: 6px; font-size: 0.9rem; padding: 10px; background: linear-gradient(135deg, #28a745, #20c997);">
                                    <i class="fas fa-file-excel"></i> Excel
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Customer Reports Tab -->
                <div id="customer-reports" class="report-category" style="display: none;">
                    <h3 style="margin-bottom: 20px; color: #333; display: flex; align-items: center; gap: 10px;">
                        <i class="fas fa-users" style="color: #17a2b8;"></i>
                        MÃ¼ÅŸteri RaporlarÄ±
                    </h3>
                    
                    <div class="reports-grid" style="grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));">
                        <!-- Customer Analytics Report -->
                        <div class="report-card" style="border-left: 4px solid #17a2b8;">
                            <div style="display: flex; align-items: start; gap: 16px; margin-bottom: 16px;">
                                <div style="width: 50px; height: 50px; background: linear-gradient(135deg, #17a2b820, #00bcd420); border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                    <i class="fas fa-user-chart" style="color: #17a2b8; font-size: 1.3rem;"></i>
                                </div>
                                <div style="flex: 1;">
                                    <h3 style="margin: 0 0 8px 0; font-size: 1.1rem;">MÃ¼ÅŸteri Analizi</h3>
                                    <p style="margin: 0; font-size: 0.85rem; color: #666;">MÃ¼ÅŸteri davranÄ±ÅŸlarÄ±, sadakat ve segmentasyon</p>
                                </div>
                            </div>
                            
                            <div style="background: #f8f9fa; padding: 12px; border-radius: 8px; margin-bottom: 16px;">
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                                    <div>
                                        <small style="color: #666; display: block; margin-bottom: 4px;">Toplam MÃ¼ÅŸteri</small>
                                        <strong style="color: #17a2b8; font-size: 1.1rem;">3,456</strong>
                                    </div>
                                    <div>
                                        <small style="color: #666; display: block; margin-bottom: 4px;">Aktif</small>
                                        <strong style="color: #28a745; font-size: 1.1rem;">2,134</strong>
                                    </div>
                                    <div>
                                        <small style="color: #666; display: block; margin-bottom: 4px;">Yeni (30 gÃ¼n)</small>
                                        <strong style="color: #333;">287</strong>
                                    </div>
                                    <div>
                                        <small style="color: #666; display: block; margin-bottom: 4px;">Sadakat OranÄ±</small>
                                        <strong style="color: #ffc107;">%68</strong>
                                    </div>
                                </div>
                            </div>
                            
                            <div style="display: flex; gap: 8px; margin-bottom: 12px;">
                                <label for="auto_11" class="sr-only">Input</label>
                                <select class="filter-select" style="flex: 1; font-size: 0.85rem;" id="auto_11">
                                    <option>TÃ¼m MÃ¼ÅŸteriler</option>
                                    <option>Premium</option>
                                    <option>Standart</option>
                                    <option>Yeni</option>
                                </select>
                            </div>
                            
                            <div style="display: flex; gap: 8px;">
                                <button class="report-btn" onclick="downloadReport('customers', 'pdf')" style="flex: 1; display: flex; align-items: center; justify-content: center; gap: 6px; font-size: 0.9rem; padding: 10px;">
                                    <i class="fas fa-file-pdf"></i> PDF
                                </button>
                                <button class="report-btn" onclick="downloadReport('customers', 'excel')" style="flex: 1; display: flex; align-items: center; justify-content: center; gap: 6px; font-size: 0.9rem; padding: 10px; background: linear-gradient(135deg, #28a745, #20c997);">
                                    <i class="fas fa-file-excel"></i> Excel
                                </button>
                            </div>
                        </div>

                        <!-- Reviews Report -->
                        <div class="report-card" style="border-left: 4px solid #ffc107;">
                            <div style="display: flex; align-items: start; gap: 16px; margin-bottom: 16px;">
                                <div style="width: 50px; height: 50px; background: linear-gradient(135deg, #ffc10720, #ff663320); border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                    <i class="fas fa-star" style="color: #ffc107; font-size: 1.3rem;"></i>
                                </div>
                                <div style="flex: 1;">
                                    <h3 style="margin: 0 0 8px 0; font-size: 1.1rem;">DeÄŸerlendirme Raporu</h3>
                                    <p style="margin: 0; font-size: 0.85rem; color: #666;">MÃ¼ÅŸteri memnuniyeti ve geri bildirim analizi</p>
                                </div>
                            </div>
                            
                            <div style="background: #f8f9fa; padding: 12px; border-radius: 8px; margin-bottom: 16px;">
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                                    <div>
                                        <small style="color: #666; display: block; margin-bottom: 4px;">Ort. Puan</small>
                                        <strong style="color: #ffc107; font-size: 1.1rem;">4.6â˜…</strong>
                                    </div>
                                    <div>
                                        <small style="color: #666; display: block; margin-bottom: 4px;">Toplam Yorum</small>
                                        <strong style="color: #667eea; font-size: 1.1rem;">1,876</strong>
                                    </div>
                                    <div>
                                        <small style="color: #666; display: block; margin-bottom: 4px;">Olumlu</small>
                                        <strong style="color: #28a745;">%87</strong>
                                    </div>
                                    <div>
                                        <small style="color: #666; display: block; margin-bottom: 4px;">Olumsuz</small>
                                        <strong style="color: #dc3545;">%13</strong>
                                    </div>
                                </div>
                            </div>
                            
                            <div style="display: flex; gap: 8px; margin-bottom: 12px;">
                                <label for="auto_12" class="sr-only">Date From</label>
                                <input type="date" class="filter-select" style="flex: 1; font-size: 0.85rem;" value="2025-10-01" id="auto_12">
                                <label for="auto_13" class="sr-only">Date To</label>
                                <input type="date" class="filter-select" style="flex: 1; font-size: 0.85rem;" value="2025-10-19" id="auto_13">
                            </div>
                            
                            <div style="display: flex; gap: 8px;">
                                <button class="report-btn" onclick="downloadReport('reviews', 'pdf')" style="flex: 1; display: flex; align-items: center; justify-content: center; gap: 6px; font-size: 0.9rem; padding: 10px;">
                                    <i class="fas fa-file-pdf"></i> PDF
                                </button>
                                <button class="report-btn" onclick="downloadReport('reviews', 'excel')" style="flex: 1; display: flex; align-items: center; justify-content: center; gap: 6px; font-size: 0.9rem; padding: 10px; background: linear-gradient(135deg, #28a745, #20c997);">
                                    <i class="fas fa-file-excel"></i> Excel
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Performance Reports Tab -->
                <div id="performance-reports" class="report-category" style="display: none;">
                    <h3 style="margin-bottom: 20px; color: #333; display: flex; align-items: center; gap: 10px;">
                        <i class="fas fa-chart-pie" style="color: #fd7e14;"></i>
                        Performans RaporlarÄ±
                    </h3>
                    
                    <div class="reports-grid" style="grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));">
                        <!-- Comprehensive Analytics Report -->
                        <div class="report-card" style="border-left: 4px solid #667eea;">
                            <div style="display: flex; align-items: start; gap: 16px; margin-bottom: 16px;">
                                <div style="width: 50px; height: 50px; background: linear-gradient(135deg, #667eea20, #764ba220); border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                    <i class="fas fa-chart-area" style="color: #667eea; font-size: 1.3rem;"></i>
                                </div>
                                <div style="flex: 1;">
                                    <h3 style="margin: 0 0 8px 0; font-size: 1.1rem;">KapsamlÄ± Analiz</h3>
                                    <p style="margin: 0; font-size: 0.85rem; color: #666;">TÃ¼m metriklerin detaylÄ± performans raporu</p>
                                </div>
                            </div>
                            
                            <div style="background: #f8f9fa; padding: 12px; border-radius: 8px; margin-bottom: 16px;">
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                                    <div>
                                        <small style="color: #666; display: block; margin-bottom: 4px;">BÃ¼yÃ¼me OranÄ±</small>
                                        <strong style="color: #28a745; font-size: 1.1rem;">+24%</strong>
                                    </div>
                                    <div>
                                        <small style="color: #666; display: block; margin-bottom: 4px;">ROI</small>
                                        <strong style="color: #667eea; font-size: 1.1rem;">%156</strong>
                                    </div>
                                    <div>
                                        <small style="color: #666; display: block; margin-bottom: 4px;">Maliyet/Gelir</small>
                                        <strong style="color: #333;">%34</strong>
                                    </div>
                                    <div>
                                        <small style="color: #666; display: block; margin-bottom: 4px;">Verimlilik</small>
                                        <strong style="color: #28a745;">%91</strong>
                                    </div>
                                </div>
                            </div>
                            
                            <div style="display: flex; gap: 8px; margin-bottom: 12px;">
                                <label for="auto_14" class="sr-only">Input</label>
                                <select class="filter-select" style="flex: 1; font-size: 0.85rem;" id="auto_14">
                                    <option>Son 12 Ay</option>
                                    <option>Bu YÄ±l</option>
                                    <option>GeÃ§en YÄ±l</option>
                                </select>
                            </div>
                            
                            <div style="display: flex; gap: 8px;">
                                <button class="report-btn" onclick="downloadReport('analytics', 'pdf')" style="flex: 1; display: flex; align-items: center; justify-content: center; gap: 6px; font-size: 0.9rem; padding: 10px;">
                                    <i class="fas fa-file-pdf"></i> PDF
                                </button>
                                <button class="report-btn" onclick="downloadReport('analytics', 'excel')" style="flex: 1; display: flex; align-items: center; justify-content: center; gap: 6px; font-size: 0.9rem; padding: 10px; background: linear-gradient(135deg, #28a745, #20c997);">
                                    <i class="fas fa-file-excel"></i> Excel
                                </button>
                            </div>
                        </div>

                        <!-- Executive Summary -->
                        <div class="report-card" style="border-left: 4px solid #dc3545;">
                            <div style="display: flex; align-items: start; gap: 16px; margin-bottom: 16px;">
                                <div style="width: 50px; height: 50px; background: linear-gradient(135deg, #dc354520, #c8233320); border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                    <i class="fas fa-briefcase" style="color: #dc3545; font-size: 1.3rem;"></i>
                                </div>
                                <div style="flex: 1;">
                                    <h3 style="margin: 0 0 8px 0; font-size: 1.1rem;">YÃ¶netici Ã–zeti</h3>
                                    <p style="margin: 0; font-size: 0.85rem; color: #666;">Ãœst yÃ¶netim iÃ§in Ã¶zet performans raporu</p>
                                </div>
                            </div>
                            
                            <div style="background: #f8f9fa; padding: 12px; border-radius: 8px; margin-bottom: 16px;">
                                <div style="display: grid; grid-template-columns: 1fr; gap: 8px;">
                                    <div style="padding: 8px; background: white; border-radius: 6px;">
                                        <small style="color: #666; display: block;">ðŸ“Š Toplam Gelir</small>
                                        <strong style="color: #28a745;">â‚º245,890 (+18%)</strong>
                                    </div>
                                    <div style="padding: 8px; background: white; border-radius: 6px;">
                                        <small style="color: #666; display: block;">ðŸ‘¥ Yeni MÃ¼ÅŸteriler</small>
                                        <strong style="color: #17a2b8;">287 (+24%)</strong>
                                    </div>
                                    <div style="padding: 8px; background: white; border-radius: 6px;">
                                        <small style="color: #666; display: block;">â­ MÃ¼ÅŸteri Memnuniyeti</small>
                                        <strong style="color: #ffc107;">4.6/5.0</strong>
                                    </div>
                                </div>
                            </div>
                            
                            <div style="display: flex; gap: 8px; margin-bottom: 12px;">
                                <label for="auto_15" class="sr-only">Input</label>
                                <select class="filter-select" style="flex: 1; font-size: 0.85rem;" id="auto_15">
                                    <option>Bu Ã‡eyrek</option>
                                    <option>GeÃ§en Ã‡eyrek</option>
                                    <option>YÄ±llÄ±k</option>
                                </select>
                            </div>
                            
                            <div style="display: flex; gap: 8px;">
                                <button class="report-btn" onclick="downloadReport('executive', 'pdf')" style="flex: 1; display: flex; align-items: center; justify-content: center; gap: 6px; font-size: 0.9rem; padding: 10px;">
                                    <i class="fas fa-file-pdf"></i> PDF
                                </button>
                                <button class="report-btn" onclick="downloadReport('executive', 'pptx')" style="flex: 1; display: flex; align-items: center; justify-content: center; gap: 6px; font-size: 0.9rem; padding: 10px; background: linear-gradient(135deg, #fd7e14, #dc3545);">
                                    <i class="fas fa-file-powerpoint"></i> PPT
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Scheduled Reports Section -->
                <div style="margin-top: 32px; background: white; padding: 24px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.08);">
                    <h3 style="margin-bottom: 20px; color: #333; display: flex; align-items: center; justify-content: space-between;">
                        <span><i class="fas fa-calendar-alt" style="color: #667eea; margin-right: 10px;"></i>ZamanlanmÄ±ÅŸ Raporlar</span>
                        <button class="add-btn" style="padding: 8px 16px; font-size: 0.9rem;">
                            <i class="fas fa-plus"></i> Yeni Zamanlama
                        </button>
                    </h3>
                    
                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Rapor AdÄ±</th>
                                    <th>Periyot</th>
                                    <th>Format</th>
                                    <th>Son Ã‡alÄ±ÅŸma</th>
                                    <th>Durum</th>
                                    <th>Ä°ÅŸlemler</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><strong>HaftalÄ±k Gelir Raporu</strong></td>
                                    <td>Her Pazartesi 09:00</td>
                                    <td><span class="type-badge" style="background: #dc354520; color: #dc3545;"><i class="fas fa-file-pdf"></i> PDF</span></td>
                                    <td>18 Eki 2025, 09:05</td>
                                    <td><span class="status-badge active"><i class="fas fa-check"></i> Aktif</span></td>
                                    <td>
                                        <button class="action-btn edit-btn" title="DÃ¼zenle">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="action-btn view-btn" title="Ã‡alÄ±ÅŸtÄ±r">
                                            <i class="fas fa-play"></i>
                                        </button>
                                        <button class="action-btn delete-btn" title="Sil">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>AylÄ±k Performans Ã–zeti</strong></td>
                                    <td>Her ayÄ±n 1'i, 08:00</td>
                                    <td><span class="type-badge" style="background: #28a74520; color: #28a745;"><i class="fas fa-file-excel"></i> Excel</span></td>
                                    <td>01 Eki 2025, 08:12</td>
                                    <td><span class="status-badge active"><i class="fas fa-check"></i> Aktif</span></td>
                                    <td>
                                        <button class="action-btn edit-btn">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="action-btn view-btn">
                                            <i class="fas fa-play"></i>
                                        </button>
                                        <button class="action-btn delete-btn">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>GÃ¼nlÃ¼k SipariÅŸ Raporu</strong></td>
                                    <td>Her gÃ¼n 23:00</td>
                                    <td><span class="type-badge" style="background: #17a2b820; color: #17a2b8;"><i class="fas fa-file-csv"></i> CSV</span></td>
                                    <td>18 Eki 2025, 23:03</td>
                                    <td><span class="status-badge active"><i class="fas fa-check"></i> Aktif</span></td>
                                    <td>
                                        <button class="action-btn edit-btn">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="action-btn view-btn">
                                            <i class="fas fa-play"></i>
                                        </button>
                                        <button class="action-btn delete-btn">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            <!-- Notifications Section -->
            <!-- FarsÃ§a: Ø¨Ø®Ø´ Ø§Ø¹Ù„Ø§Ù†â€ŒÙ‡Ø§. -->
            <!-- TÃ¼rkÃ§e: Bildirimler BÃ¶lÃ¼mÃ¼. -->
            <!-- English: Notifications Section. -->
            <section id="notifications" class="content-section">
                <div class="section-header">
                    <div>
                        <h2><i class="fas fa-bell" style="color: #667eea; margin-right: 12px;"></i>Bildirim YÃ¶netimi</h2>
                        <p>KullanÄ±cÄ±lara bildirim gÃ¶nder ve geÃ§miÅŸ bildirimleri yÃ¶net</p>
                    </div>
                    <button class="add-btn" id="sendNotificationBtn">
                        <i class="fas fa-paper-plane"></i>
                        Yeni Bildirim GÃ¶nder
                    </button>
                </div>

                <!-- Notification Stats -->
                <div class="stats-grid" style="margin-bottom: 24px;">
                    <div class="stat-card">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #667eea20, #764ba220);">
                            <i class="fas fa-paper-plane" style="color: #667eea;"></i>
                        </div>
                        <div class="stat-info">
                            <h3>42</h3>
                            <p>BugÃ¼n GÃ¶nderilen</p>
                            <small class="text-blue-600">Son 24 saat</small>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #ffc10720, #fd7e1420);">
                            <i class="fas fa-clock" style="color: #ffc107;"></i>
                        </div>
                        <div class="stat-info">
                            <h3>8</h3>
                            <p>Bekleyen</p>
                            <small class="text-orange-600">ZamanlanmÄ±ÅŸ</small>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #dc354520, #c8233320);">
                            <i class="fas fa-exclamation-triangle" style="color: #dc3545;"></i>
                        </div>
                        <div class="stat-info">
                            <h3>3</h3>
                            <p>BaÅŸarÄ±sÄ±z</p>
                            <small class="text-red-600">Tekrar denenecek</small>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #28a74520, #20c99720);">
                            <i class="fas fa-users" style="color: #28a745;"></i>
                        </div>
                        <div class="stat-info">
                            <h3>1,248</h3>
                            <p>Toplam KullanÄ±cÄ±</p>
                            <small class="text-green-600">Aktif alÄ±cÄ±lar</small>
                        </div>
                    </div>
                </div>

                <!-- Notification Filters -->
                <div class="filters" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px;">
                    <label for="notificationSearch" class="sr-only">Konu, Mesaj Ara...</label><input type="text" id="notificationSearch" placeholder="Konu, Mesaj Ara..." class="search-input" style="grid-column: 1 / -1;">
                    
                    <label for="notificationTypeFilter" class="sr-only">Input</label><select id="notificationTypeFilter" class="filter-select">
                        <option value="">TÃ¼m Tipler</option>
                        <option value="email">Email</option>
                        <option value="sms">SMS</option>
                        <option value="push">Push Notification</option>
                        <option value="in_app">In-App</option>
                    </select>
                    
                    <label for="notificationStatusFilter" class="sr-only">Input</label><select id="notificationStatusFilter" class="filter-select">
                        <option value="">TÃ¼m Durumlar</option>
                        <option value="sent">GÃ¶nderildi</option>
                        <option value="pending">Bekliyor</option>
                        <option value="failed">BaÅŸarÄ±sÄ±z</option>
                        <option value="scheduled">ZamanlanmÄ±ÅŸ</option>
                    </select>
                    
                    <label for="notificationDateFrom" class="sr-only">Date</label><input type="date" id="notificationDateFrom" class="filter-select">
                    
                    <button class="add-btn">
                        <i class="fas fa-filter"></i> Filtrele
                    </button>
                </div>

                <!-- Notifications Table -->
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Konu</th>
                                <th>Mesaj</th>
                                <th>Tip</th>
                                <th>Hedef</th>
                                <th>Durum</th>
                                <th>GÃ¶nderim ZamanÄ±</th>
                                <th>Ä°ÅŸlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><strong>#NOT-523</strong></td>
                                <td>Ã–zel Ä°ndirim KampanyasÄ±</td>
                                <td style="max-width: 300px;">
                                    <small>TÃ¼m hizmetlerde %20 indirim! BugÃ¼n...</small>
                                </td>
                                <td>
                                    <span class="type-badge" style="background: #667eea20; color: #667eea;">
                                        <i class="fas fa-envelope"></i> Email
                                    </span>
                                </td>
                                <td>TÃ¼m KullanÄ±cÄ±lar (1,248)</td>
                                <td><span class="status-badge active">GÃ¶nderildi</span></td>
                                <td>18 Eki 2025, 14:30</td>
                                <td>
                                    <button class="action-btn view-btn" title="Detay">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="action-btn edit-btn" title="Tekrar GÃ¶nder">
                                        <i class="fas fa-redo"></i>
                                    </button>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>#NOT-522</strong></td>
                                <td>Randevu HatÄ±rlatmasÄ±</td>
                                <td style="max-width: 300px;">
                                    <small>YarÄ±nki randevunuzu unutmayÄ±n...</small>
                                </td>
                                <td>
                                    <span class="type-badge" style="background: #28a74520; color: #28a745;">
                                        <i class="fas fa-sms"></i> SMS
                                    </span>
                                </td>
                                <td>Premium KullanÄ±cÄ±lar (324)</td>
                                <td><span class="status-badge active">GÃ¶nderildi</span></td>
                                <td>18 Eki 2025, 10:00</td>
                                <td>
                                    <button class="action-btn view-btn">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="action-btn edit-btn">
                                        <i class="fas fa-redo"></i>
                                    </button>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>#NOT-521</strong></td>
                                <td>Sistem BakÄ±m Bildirimi</td>
                                <td style="max-width: 300px;">
                                    <small>YarÄ±n 02:00 - 04:00 arasÄ± sistem...</small>
                                </td>
                                <td>
                                    <span class="type-badge" style="background: #ffc10720; color: #ff8c00;">
                                        <i class="fas fa-bell"></i> Push
                                    </span>
                                </td>
                                <td>TÃ¼m KullanÄ±cÄ±lar (1,248)</td>
                                <td><span class="status-badge" style="background: #ffc10720; color: #ff8c00;">ZamanlanmÄ±ÅŸ</span></td>
                                <td>19 Eki 2025, 01:00</td>
                                <td>
                                    <button class="action-btn view-btn">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="action-btn delete-btn" title="Ä°ptal Et">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- CMS Section -->
            <!-- FarsÃ§a: Ø¨Ø®Ø´ Ù…Ø¯ÛŒØ±ÛŒØª Ù…Ø­ØªÙˆØ§. -->
            <!-- TÃ¼rkÃ§e: Ä°Ã§erik YÃ¶netimi BÃ¶lÃ¼mÃ¼. -->
            <!-- English: CMS Section. -->
            <section id="cms" class="content-section">
                <div class="section-header">
                    <div>
                        <h2><i class="fas fa-file-alt" style="color: #764ba2; margin-right: 12px;"></i>Ä°Ã§erik YÃ¶netimi (CMS)</h2>
                        <p>Web sitesi sayfalarÄ±nÄ± ve iÃ§eriklerini yÃ¶net</p>
                    </div>
                    <button class="add-btn" id="addCmsPageBtn" style="background: linear-gradient(135deg, #764ba2, #667eea);">
                        <i class="fas fa-plus"></i>
                        Yeni Sayfa Ekle
                    </button>
                </div>

                <!-- CMS Stats -->
                <div class="stats-grid" style="margin-bottom: 24px;">
                    <div class="stat-card">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #764ba220, #667eea20);">
                            <i class="fas fa-file-alt" style="color: #764ba2;"></i>
                        </div>
                        <div class="stat-info">
                            <h3>12</h3>
                            <p>Toplam Sayfa</p>
                            <small class="text-purple-600">YayÄ±nda</small>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #ffc10720, #fd7e1420);">
                            <i class="fas fa-edit" style="color: #ffc107;"></i>
                        </div>
                        <div class="stat-info">
                            <h3>3</h3>
                            <p>Taslak</p>
                            <small class="text-orange-600">Beklemede</small>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #667eea20, #764ba220);">
                            <i class="fas fa-images" style="color: #667eea;"></i>
                        </div>
                        <div class="stat-info">
                            <h3>248</h3>
                            <p>Medya DosyasÄ±</p>
                            <small class="text-blue-600">KÃ¼tÃ¼phane</small>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #28a74520, #20c99720);">
                            <i class="fas fa-chart-line" style="color: #28a745;"></i>
                        </div>
                        <div class="stat-info">
                            <h3>8,542</h3>
                            <p>Toplam GÃ¶rÃ¼ntÃ¼leme</p>
                            <small class="text-green-600">Bu ay</small>
                        </div>
                    </div>
                </div>

                <!-- CMS Filters -->
                <div class="filters" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px;">
                    <label for="cmsSearch" class="sr-only">Sayfa Ara...</label><input type="text" id="cmsSearch" placeholder="Sayfa Ara..." class="search-input" style="grid-column: 1 / -1;">
                    
                    <label for="cmsStatusFilter" class="sr-only">Input</label><select id="cmsStatusFilter" class="filter-select">
                        <option value="">TÃ¼m Durumlar</option>
                        <option value="published">YayÄ±nda</option>
                        <option value="draft">Taslak</option>
                        <option value="scheduled">ZamanlanmÄ±ÅŸ</option>
                    </select>
                    
                    <label for="cmsTypeFilter" class="sr-only">Input</label><select id="cmsTypeFilter" class="filter-select">
                        <option value="">TÃ¼m Tipler</option>
                        <option value="page">Sayfa</option>
                        <option value="post">Blog YazÄ±sÄ±</option>
                        <option value="faq">SSS</option>
                    </select>
                    
                    <button class="add-btn">
                        <i class="fas fa-filter"></i> Filtrele
                    </button>
                </div>

                <!-- CMS Pages Table -->
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Sayfa BaÅŸlÄ±ÄŸÄ±</th>
                                <th>URL</th>
                                <th>Tip</th>
                                <th>Durum</th>
                                <th>Son GÃ¼ncelleme</th>
                                <th>GÃ¶rÃ¼ntÃ¼lenme</th>
                                <th>Ä°ÅŸlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><strong>#1</strong></td>
                                <td>
                                    <div>
                                        <strong>HakkÄ±mÄ±zda</strong><br>
                                        <small style="color: #64748b;">Åžirket bilgileri ve tarihÃ§e</small>
                                    </div>
                                </td>
                                <td><code>/hakkimizda</code></td>
                                <td><span class="type-badge" style="background: #667eea20; color: #667eea;">Sayfa</span></td>
                                <td><span class="status-badge active">YayÄ±nda</span></td>
                                <td>17 Eki 2025</td>
                                <td>2,348</td>
                                <td>
                                    <button class="action-btn edit-btn" title="DÃ¼zenle">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="action-btn view-btn" title="Ã–nizle">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="action-btn delete-btn" title="Sil">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>#2</strong></td>
                                <td>
                                    <div>
                                        <strong>Ä°letiÅŸim</strong><br>
                                        <small style="color: #64748b;">Ä°letiÅŸim formu ve bilgiler</small>
                                    </div>
                                </td>
                                <td><code>/iletisim</code></td>
                                <td><span class="type-badge" style="background: #667eea20; color: #667eea;">Sayfa</span></td>
                                <td><span class="status-badge active">YayÄ±nda</span></td>
                                <td>16 Eki 2025</td>
                                <td>1,892</td>
                                <td>
                                    <button class="action-btn edit-btn">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="action-btn view-btn">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="action-btn delete-btn">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>#3</strong></td>
                                <td>
                                    <div>
                                        <strong>Gizlilik PolitikasÄ±</strong><br>
                                        <small style="color: #64748b;">KVKK ve gizlilik kurallarÄ±</small>
                                    </div>
                                </td>
                                <td><code>/gizlilik-politikasi</code></td>
                                <td><span class="type-badge" style="background: #667eea20; color: #667eea;">Sayfa</span></td>
                                <td><span class="status-badge" style="background: #ffc10720; color: #ff8c00;">Taslak</span></td>
                                <td>15 Eki 2025</td>
                                <td>-</td>
                                <td>
                                    <button class="action-btn edit-btn">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="action-btn view-btn">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="action-btn" style="background: #28a74520; color: #28a745;" title="YayÄ±nla">
                                        <i class="fas fa-check"></i>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Media Library Section -->
                <div style="margin-top: 32px;">
                    <h3 style="margin-bottom: 16px; display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-images"></i>
                        Medya KÃ¼tÃ¼phanesi
                        <button class="add-btn" style="margin-left: auto; padding: 8px 16px; font-size: 0.875rem;">
                            <i class="fas fa-upload"></i> Dosya YÃ¼kle
                        </button>
                    </h3>
                    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 16px; background: white; padding: 20px; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.05);">
                        <div style="border: 2px dashed #e9ecef; border-radius: 8px; padding: 20px; text-align: center; cursor: pointer; transition: all 0.3s;">
                            <i class="fas fa-image" style="font-size: 3rem; color: #667eea; margin-bottom: 8px;"></i>
                            <p style="font-size: 0.875rem; color: #64748b;">image1.jpg</p>
                            <small style="color: #94a3b8;">248 KB</small>
                        </div>
                        <div style="border: 2px dashed #e9ecef; border-radius: 8px; padding: 20px; text-align: center; cursor: pointer; transition: all 0.3s;">
                            <i class="fas fa-file-pdf" style="font-size: 3rem; color: #dc3545; margin-bottom: 8px;"></i>
                            <p style="font-size: 0.875rem; color: #64748b;">katalog.pdf</p>
                            <small style="color: #94a3b8;">1.2 MB</small>
                        </div>
                        <div style="border: 2px dashed #e9ecef; border-radius: 8px; padding: 20px; text-align: center; cursor: pointer; transition: all 0.3s;">
                            <i class="fas fa-image" style="font-size: 3rem; color: #667eea; margin-bottom: 8px;"></i>
                            <p style="font-size: 0.875rem; color: #64748b;">banner.png</p>
                            <small style="color: #94a3b8;">512 KB</small>
                        </div>
                        <div style="border: 2px dashed #e9ecef; border-radius: 8px; padding: 20px; text-align: center; cursor: pointer; transition: all 0.3s;">
                            <i class="fas fa-plus-circle" style="font-size: 3rem; color: #28a745; margin-bottom: 8px;"></i>
                            <p style="font-size: 0.875rem; color: #64748b;">Yeni YÃ¼kle</p>
                            <small style="color: #94a3b8;">Dosya Ekle</small>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Security & Logs Section -->
            <!-- FarsÃ§a: Ø¨Ø®Ø´ Ø§Ù…Ù†ÛŒØª Ùˆ Ù„Ø§Ú¯â€ŒÙ‡Ø§. -->
            <!-- TÃ¼rkÃ§e: GÃ¼venlik & Loglar BÃ¶lÃ¼mÃ¼. -->
            <!-- English: Security & Logs Section. -->
            <section id="security" class="content-section">
                <div class="section-header">
                    <div>
                        <h2><i class="fas fa-shield-alt" style="color: #28a745; margin-right: 12px;"></i>GÃ¼venlik &amp; Sistem LoglarÄ±</h2>
                        <p>Sistem gÃ¼venliÄŸini izle ve denetim kayÄ±tlarÄ±nÄ± incele</p>
                    </div>
                    <button class="add-btn" style="background: linear-gradient(135deg, #28a745, #20c997);">
                        <i class="fas fa-download"></i>
                        Log DÄ±ÅŸa Aktar
                    </button>
                </div>

                <!-- Security Stats -->
                <div class="stats-grid" style="margin-bottom: 24px;">
                    <div class="stat-card">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #dc354520, #c8233320);">
                            <i class="fas fa-exclamation-triangle" style="color: #dc3545;"></i>
                        </div>
                        <div class="stat-info">
                            <h3>5</h3>
                            <p>BaÅŸarÄ±sÄ±z GiriÅŸ</p>
                            <small class="text-red-600">Son 24 saat</small>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #667eea20, #764ba220);">
                            <i class="fas fa-users" style="color: #667eea;"></i>
                        </div>
                        <div class="stat-info">
                            <h3>48</h3>
                            <p>Aktif Oturum</p>
                            <small class="text-blue-600">Åžu an online</small>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #28a74520, #20c99720);">
                            <i class="fas fa-clipboard-list" style="color: #28a745;"></i>
                        </div>
                        <div class="stat-info">
                            <h3>1,248</h3>
                            <p>Denetim KaydÄ±</p>
                            <small class="text-green-600">BugÃ¼n</small>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #ffc10720, #fd7e1420);">
                            <i class="fas fa-database" style="color: #ffc107;"></i>
                        </div>
                        <div class="stat-info">
                            <h3>2 saat Ã¶nce</h3>
                            <p>Son Yedekleme</p>
                            <small class="text-orange-600">Otomatik</small>
                        </div>
                    </div>
                </div>

                <!-- Security Tabs -->
                <div style="margin-bottom: 24px;">
                    <div style="display: flex; gap: 8px; border-bottom: 2px solid #e9ecef; padding-bottom: 8px;">
                        <button class="tab-btn active" data-tab="audit-logs" style="padding: 12px 24px; background: linear-gradient(135deg, #667eea, #764ba2); color: white; border: none; border-radius: 8px 8px 0 0; cursor: pointer; font-weight: 500;">
                            Denetim LoglarÄ±
                        </button>
                        <button class="tab-btn" data-tab="login-logs" style="padding: 12px 24px; background: white; color: #333; border: none; border-radius: 8px 8px 0 0; cursor: pointer; font-weight: 500;">
                            GiriÅŸ LoglarÄ±
                        </button>
                        <button class="tab-btn" data-tab="active-sessions" style="padding: 12px 24px; background: white; color: #333; border: none; border-radius: 8px 8px 0 0; cursor: pointer; font-weight: 500;">
                            Aktif Oturumlar
                        </button>
                        <button class="tab-btn" data-tab="backups" style="padding: 12px 24px; background: white; color: #333; border: none; border-radius: 8px 8px 0 0; cursor: pointer; font-weight: 500;">
                            Yedeklemeler
                        </button>
                    </div>
                </div>

                <!-- Audit Logs Tab -->
                <div id="audit-logs" class="tab-content active">
                    <!-- Audit Log Filters -->
                    <div class="filters" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px;">
                        <label for="auditSearch" class="sr-only">KullanÄ±cÄ±, Aksiyon Ara...</label><input type="text" id="auditSearch" placeholder="KullanÄ±cÄ±, Aksiyon Ara..." class="search-input" style="grid-column: 1 / -1;">
                        
                        <label for="auditActionFilter" class="sr-only">Input</label><select id="auditActionFilter" class="filter-select">
                            <option value="">TÃ¼m Aksiyonlar</option>
                            <option value="create">OluÅŸturma</option>
                            <option value="update">GÃ¼ncelleme</option>
                            <option value="delete">Silme</option>
                            <option value="login">GiriÅŸ</option>
                            <option value="logout">Ã‡Ä±kÄ±ÅŸ</option>
                        </select>
                        
                        <label for="auditEntityFilter" class="sr-only">Input</label><select id="auditEntityFilter" class="filter-select">
                            <option value="">TÃ¼m VarlÄ±klar</option>
                            <option value="user">KullanÄ±cÄ±</option>
                            <option value="order">SipariÅŸ</option>
                            <option value="payment">Ã–deme</option>
                            <option value="service">Hizmet</option>
                        </select>
                        
                        <label for="auditDateFrom" class="sr-only">Date</label><input type="date" id="auditDateFrom" class="filter-select">
                        
                        <button class="add-btn">
                            <i class="fas fa-filter"></i> Filtrele
                        </button>
                    </div>

                    <!-- Audit Logs Table -->
                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>KullanÄ±cÄ±</th>
                                    <th>Aksiyon</th>
                                    <th>VarlÄ±k</th>
                                    <th>AÃ§Ä±klama</th>
                                    <th>IP Adresi</th>
                                    <th>Zaman</th>
                                    <th>Detay</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><strong>#AL-8542</strong></td>
                                    <td>Admin KullanÄ±cÄ±</td>
                                    <td>
                                        <span class="type-badge" style="background: #28a74520; color: #28a745;">
                                            <i class="fas fa-plus"></i> CREATE
                                        </span>
                                    </td>
                                    <td>User #245</td>
                                    <td>Yeni kullanÄ±cÄ± oluÅŸturuldu</td>
                                    <td><code>192.168.1.100</code></td>
                                    <td>18 Eki 2025, 14:32</td>
                                    <td>
                                        <button class="action-btn view-btn">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>#AL-8541</strong></td>
                                    <td>Admin KullanÄ±cÄ±</td>
                                    <td>
                                        <span class="type-badge" style="background: #667eea20; color: #667eea;">
                                            <i class="fas fa-edit"></i> UPDATE
                                        </span>
                                    </td>
                                    <td>Service #12</td>
                                    <td>Hizmet fiyatÄ± gÃ¼ncellendi</td>
                                    <td><code>192.168.1.100</code></td>
                                    <td>18 Eki 2025, 13:15</td>
                                    <td>
                                        <button class="action-btn view-btn">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>#AL-8540</strong></td>
                                    <td>Support User</td>
                                    <td>
                                        <span class="type-badge" style="background: #dc354520; color: #dc3545;">
                                            <i class="fas fa-trash"></i> DELETE
                                        </span>
                                    </td>
                                    <td>Review #432</td>
                                    <td>Yorum silindi</td>
                                    <td><code>192.168.1.105</code></td>
                                    <td>18 Eki 2025, 11:48</td>
                                    <td>
                                        <button class="action-btn view-btn">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Login Logs Tab -->
                <div id="login-logs" class="tab-content" style="display: none;">
                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>KullanÄ±cÄ±</th>
                                    <th>Durum</th>
                                    <th>IP Adresi</th>
                                    <th>TarayÄ±cÄ±</th>
                                    <th>Konum</th>
                                    <th>Zaman</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><strong>#LG-4523</strong></td>
                                    <td>admin@otoparkdemotime.com</td>
                                    <td><span class="status-badge active">BaÅŸarÄ±lÄ±</span></td>
                                    <td><code>192.168.1.100</code></td>
                                    <td>Chrome 119.0</td>
                                    <td>Ä°stanbul, TÃ¼rkiye</td>
                                    <td>18 Eki 2025, 09:30</td>
                                </tr>
                                <tr>
                                    <td><strong>#LG-4522</strong></td>
                                    <td>support@otoparkdemotime.com</td>
                                    <td><span class="status-badge active">BaÅŸarÄ±lÄ±</span></td>
                                    <td><code>192.168.1.105</code></td>
                                    <td>Firefox 118.0</td>
                                    <td>Ankara, TÃ¼rkiye</td>
                                    <td>18 Eki 2025, 08:15</td>
                                </tr>
                                <tr>
                                    <td><strong>#LG-4521</strong></td>
                                    <td>unknown@example.com</td>
                                    <td><span class="status-badge inactive">BaÅŸarÄ±sÄ±z</span></td>
                                    <td><code>185.45.67.89</code></td>
                                    <td>Chrome 119.0</td>
                                    <td>Bilinmeyen</td>
                                    <td>18 Eki 2025, 03:42</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Active Sessions Tab -->
                <div id="active-sessions" class="tab-content" style="display: none;">
                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>KullanÄ±cÄ±</th>
                                    <th>IP Adresi</th>
                                    <th>TarayÄ±cÄ±</th>
                                    <th>Son Aktivite</th>
                                    <th>Oturum SÃ¼resi</th>
                                    <th>Ä°ÅŸlemler</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Admin KullanÄ±cÄ±</td>
                                    <td><code>192.168.1.100</code></td>
                                    <td>Chrome 119.0</td>
                                    <td>2 dakika Ã¶nce</td>
                                    <td>5 saat 12 dk</td>
                                    <td>
                                        <button class="action-btn delete-btn" title="Oturumu SonlandÄ±r">
                                            <i class="fas fa-sign-out-alt"></i>
                                        </button>
                                    </td>
                                </tr>
                                <tr>
                                    <td>Support User</td>
                                    <td><code>192.168.1.105</code></td>
                                    <td>Firefox 118.0</td>
                                    <td>15 dakika Ã¶nce</td>
                                    <td>3 saat 45 dk</td>
                                    <td>
                                        <button class="action-btn delete-btn">
                                            <i class="fas fa-sign-out-alt"></i>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Backups Tab -->
                <div id="backups" class="tab-content" style="display: none;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; padding: 20px; background: white; border-radius: 12px; box-shadow: 0 2px 8px rgba(0,0,0,0.05);">
                        <div>
                            <h4 style="margin: 0 0 8px 0;">VeritabanÄ± Yedekleme</h4>
                            <p style="margin: 0; color: #64748b;">Son yedekleme: 18 Ekim 2025, 12:00</p>
                        </div>
                        <button class="add-btn" style="background: linear-gradient(135deg, #28a745, #20c997);">
                            <i class="fas fa-plus"></i>
                            Yeni Yedek Al
                        </button>
                    </div>
                    
                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Yedek ID</th>
                                    <th>Dosya AdÄ±</th>
                                    <th>Boyut</th>
                                    <th>Tip</th>
                                    <th>OluÅŸturulma</th>
                                    <th>Ä°ÅŸlemler</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><strong>#BK-245</strong></td>
                                    <td>backup_2025_10_18_12_00.sql</td>
                                    <td>24.5 MB</td>
                                    <td><span class="type-badge" style="background: #28a74520; color: #28a745;">Otomatik</span></td>
                                    <td>18 Eki 2025, 12:00</td>
                                    <td>
                                        <button class="action-btn view-btn" title="Ä°ndir">
                                            <i class="fas fa-download"></i>
                                        </button>
                                        <button class="action-btn edit-btn" title="Geri YÃ¼kle">
                                            <i class="fas fa-redo"></i>
                                        </button>
                                        <button class="action-btn delete-btn" title="Sil">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>#BK-244</strong></td>
                                    <td>backup_2025_10_17_12_00.sql</td>
                                    <td>23.8 MB</td>
                                    <td><span class="type-badge" style="background: #28a74520; color: #28a745;">Otomatik</span></td>
                                    <td>17 Eki 2025, 12:00</td>
                                    <td>
                                        <button class="action-btn view-btn">
                                            <i class="fas fa-download"></i>
                                        </button>
                                        <button class="action-btn edit-btn">
                                            <i class="fas fa-redo"></i>
                                        </button>
                                        <button class="action-btn delete-btn">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>#BK-243</strong></td>
                                    <td>backup_2025_10_16_manual.sql</td>
                                    <td>22.9 MB</td>
                                    <td><span class="type-badge" style="background: #667eea20; color: #667eea;">Manuel</span></td>
                                    <td>16 Eki 2025, 16:30</td>
                                    <td>
                                        <button class="action-btn view-btn">
                                            <i class="fas fa-download"></i>
                                        </button>
                                        <button class="action-btn edit-btn">
                                            <i class="fas fa-redo"></i>
                                        </button>
                                        <button class="action-btn delete-btn">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            <!-- Settings Section -->
            <!-- FarsÃ§a: Ø¨Ø®Ø´ ØªÙ†Ø¸ÛŒÙ…Ø§Øª. -->
            <!-- TÃ¼rkÃ§e: Ayarlar BÃ¶lÃ¼mÃ¼. -->
            <!-- English: Settings Section. -->
            <section id="settings" class="content-section">
                <div class="section-header">
                    <div>
                        <h2><i class="fas fa-cog" style="color: #667eea; margin-right: 12px;"></i>Sistem AyarlarÄ±</h2>
                        <p>TÃ¼m sistem ayarlarÄ±nÄ± yapÄ±landÄ±r ve yÃ¶net</p>
                    </div>
                    <button class="add-btn" style="background: linear-gradient(135deg, #28a745, #20c997);">
                        <i class="fas fa-save"></i>
                        TÃ¼m AyarlarÄ± Kaydet
                    </button>
                </div>

                <!-- Settings Tabs -->
                <div style="margin-bottom: 24px;">
                    <div style="display: flex; gap: 8px; border-bottom: 2px solid #e9ecef; padding-bottom: 8px; flex-wrap: wrap;">
                        <button class="settings-tab-btn active" data-settings-tab="general" style="padding: 12px 20px; background: linear-gradient(135deg, #667eea, #764ba2); color: white; border: none; border-radius: 8px 8px 0 0; cursor: pointer; font-weight: 500; transition: all 0.3s;">
                            <i class="fas fa-globe"></i> Genel
                        </button>
                        <button class="settings-tab-btn" data-settings-tab="payment" style="padding: 12px 20px; background: white; color: #333; border: none; border-radius: 8px 8px 0 0; cursor: pointer; font-weight: 500; transition: all 0.3s;">
                            <i class="fas fa-credit-card"></i> Ã–deme
                        </button>
                        <button class="settings-tab-btn" data-settings-tab="notifications" style="padding: 12px 20px; background: white; color: #333; border: none; border-radius: 8px 8px 0 0; cursor: pointer; font-weight: 500; transition: all 0.3s;">
                            <i class="fas fa-bell"></i> Bildirimler
                        </button>
                        <button class="settings-tab-btn" data-settings-tab="rbac" style="padding: 12px 20px; background: white; color: #333; border: none; border-radius: 8px 8px 0 0; cursor: pointer; font-weight: 500; transition: all 0.3s;">
                            <i class="fas fa-user-shield"></i> RBAC
                        </button>
                        <button class="settings-tab-btn" data-settings-tab="security" style="padding: 12px 20px; background: white; color: #333; border: none; border-radius: 8px 8px 0 0; cursor: pointer; font-weight: 500; transition: all 0.3s;">
                            <i class="fas fa-shield-alt"></i> GÃ¼venlik
                        </button>
                        <button class="settings-tab-btn" data-settings-tab="backup" style="padding: 12px 20px; background: white; color: #333; border: none; border-radius: 8px 8px 0 0; cursor: pointer; font-weight: 500; transition: all 0.3s;">
                            <i class="fas fa-database"></i> Yedekleme
                        </button>
                        <button class="settings-tab-btn" data-settings-tab="email" style="padding: 12px 20px; background: white; color: #333; border: none; border-radius: 8px 8px 0 0; cursor: pointer; font-weight: 500; transition: all 0.3s;">
                            <i class="fas fa-envelope"></i> Email
                        </button>
                    </div>
                </div>

                <!-- General Settings Tab -->
                <div id="general" class="settings-tab-content active" style="background: white; padding: 2rem; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.08);">
                    <h3 style="margin-bottom: 1.5rem; color: #333; display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-globe"></i> Genel Ayarlar
                    </h3>

                    <div class="form-group">
                        <label for="auto_16" class="sr-only">Site adÄ±nÄ± girin</label>
                        <input type="text" value="CarWash YÃ¶netim Sistemi" placeholder="Site adÄ±nÄ± girin" id="auto_16">
                        <small style="color: #64748b;">Web sitesinde gÃ¶rÃ¼necek isim</small>
                    </div>

                    <div class="form-group">
                        <label for="auto_17" class="sr-only">admin@example.com</label>
                        <input type="email" value="admin@otoparkdemotime.com" placeholder="admin@example.com" id="auto_17">
                        <small style="color: #64748b;">Sistem bildirimleri bu adrese gÃ¶nderilecek</small>
                    </div>

                    <div class="form-group">
                        <label for="auto_18">Saat Dilimi</label>
                        <select id="auto_18">
                            <option value="Europe/Istanbul" selected>Europe/Istanbul (GMT+3)</option>
                            <option value="UTC">UTC (GMT+0)</option>
                            <option value="Europe/London">Europe/London (GMT+0)</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="auto_21">Dil</label>
                        <select id="auto_21">
                            <option value="tr" selected>TÃ¼rkÃ§e</option>
                            <option value="en">English</option>
                            <option value="fa">ÙØ§Ø±Ø³ÛŒ</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="auto_23">Para Birimi</label>
                        <select id="auto_23">
                            <option value="TRY" selected>â‚º TÃ¼rk LirasÄ± (TRY)</option>
                            <option value="USD">$ US Dollar (USD)</option>
                            <option value="EUR">â‚¬ Euro (EUR)</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="auto_24" class="sr-only">BakÄ±m Modu</label>
                        <input type="checkbox" id="auto_24" checked style="width: auto; margin: 0;">
                        <span style="margin-left: 8px;">BakÄ±m Modu</span>
                        <small style="display: block; color: #64748b; margin-top: 6px;">Aktif olduÄŸunda site ziyaretÃ§ilere kapalÄ± olacak</small>
                    </div>

                    <button class="save-btn" style="margin-top: 1rem;">
                        <i class="fas fa-save"></i> Kaydet
                    </button>
                </div>

                <!-- Payment Settings Tab -->
                <div id="payment" class="settings-tab-content" style="display: none; background: white; padding: 2rem; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.08);">
                    <form method="POST" id="paymentSettingsForm" class="settings-form">
                    <h3 style="margin-bottom: 1.5rem; color: #333; display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-credit-card"></i> Ã–deme AyarlarÄ±
                    </h3>
                    <div class="form-group">
                        <label for="auto_26" class="sr-only">Platform Komisyon (%)</label>
                        <input type="number" value="15" min="0" max="100" step="0.1" id="auto_26">
                        <small style="color: #64748b;">Platform komisyon yÃ¼zdesi</small>
                    </div>
                    <div class="form-group">
                        <label for="auto_27" class="sr-only">Platform Komisyon (gÃ¶rsel)</label>
                        <input type="number" value="15" min="0" max="100" step="0.1" id="auto_27">
                    </div>
                    <div class="form-group">
                        <label for="auto_28" class="sr-only">Minimum Ã–deme TutarÄ±</label>
                        <input type="number" value="50" min="0" id="auto_28">
                        <small style="color: #64748b;">Tasfiye iÃ§in minimum tutar</small>
                    </div>
                    <div class="form-group">
                        <label for="auto_29" class="sr-only">Minimum Ã–deme (gÃ¶rsel)</label>
                        <input type="number" value="50" min="0" id="auto_29">
                    </div>
                    
                    <button class="save-btn" style="margin-top: 1rem;">
                        <i class="fas fa-save"></i> Kaydet
                    </button>
                    </form>

                    <h4 style="margin: 2rem 0 1rem 0; color: #333;">Ã–deme AÄŸ GeÃ§itleri</h4>
                    
                    <!-- Stripe -->
                    <form method="POST" id="stripeSettingsForm" class="settings-form">
                    <div style="border: 2px solid #e9ecef; border-radius: 8px; padding: 1.5rem; margin-bottom: 1rem;">
                            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 1rem;">
                            <i class="fab fa-stripe" style="font-size: 2rem; color: #635bff;"></i>
                            <div style="flex: 1;">
                                <h5 style="margin: 0;">Stripe</h5>
                                <small style="color: #64748b;">Kredi kartÄ± Ã¶demeleri</small>
                            </div>
                            <label for="auto_30" class="sr-only">Live Mode</label>
                            <input type="checkbox" checked style="width: auto; margin: 0;" id="auto_30">
                            <label for="auto_31" class="sr-only">Test Mode</label>
                            <input type="checkbox" checked style="width: auto; margin: 0;" id="auto_31">
                            <span>Aktif</span>
                        </div>
                        <div class="form-group">
                            <label for="auto_32">Publishable Key</label>
                            <input type="text" placeholder="pk_live_..." id="auto_32" autocomplete="off">
                                <label for="auto_33" class="sr-only">Publishable Key (alt)</label>
                                <input type="text" placeholder="pk_live_..." id="auto_33" autocomplete="off">
                            </div>
                            <div class="form-group">
                                <label for="auto_34">Secret Key</label>
                                <input type="password" placeholder="sk_live_..." id="auto_34" autocomplete="off">
                            </div>
                            <div class="form-group">
                                <label for="auto_35">Secret Key (alt)</label>
                                <input type="password" placeholder="sk_live_..." id="auto_35" autocomplete="off">
                            </div>
                    </div>
                    <button class="save-btn" style="margin-top: 1rem;">
                        <i class="fas fa-save"></i> Kaydet
                    </button>
                    </form>

                    <!-- PayPal -->
                    <form method="POST" id="paypalSettingsForm" class="settings-form">
                    <div style="border: 2px solid #e9ecef; border-radius: 8px; padding: 1.5rem; margin-bottom: 1rem;">
                        <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 1rem;">
                            <i class="fab fa-paypal" style="font-size: 2rem; color: #00457c;"></i>
                            <div style="flex: 1;">
                                <h5 style="margin: 0;">PayPal</h5>
                                <small style="color: #64748b;">PayPal Ã¶demeleri</small>
                            </div>
                            <label for="auto_36" class="sr-only">Enable PayPal</label>
                            <input type="checkbox" style="width: auto; margin: 0;" id="auto_36">
                            <label for="auto_37" class="sr-only">Sandbox Mode</label>
                            <input type="checkbox" style="width: auto; margin: 0;" id="auto_37">
                            <span>Aktif</span>
                        </div>
                        <div class="form-group">
                            <label for="auto_38">Client ID</label>
                            <input type="text" placeholder="AXxxx..." id="auto_38" autocomplete="off">
                        </div>
                        <div class="form-group">
                            <label for="auto_39">Client ID (alt)</label>
                            <input type="text" placeholder="AXxxx..." id="auto_39" autocomplete="off">
                        </div>
                        <div class="form-group">
                            <label for="auto_40">Secret Key</label>
                            <input type="password" placeholder="ECxxx..." id="auto_40" autocomplete="off">
                        </div>
                        <div class="form-group">
                            <label for="auto_41">Secret Key (alt)</label>
                            <input type="password" placeholder="ECxxx..." id="auto_41" autocomplete="off">
                        </div>
                    </div>
                    <button class="save-btn" style="margin-top: 1rem;">
                        <i class="fas fa-save"></i> Kaydet
                    </button>
                    </form>

                    <!-- iyzico -->
                    <form method="POST" id="iyzicoSettingsForm" class="settings-form">
                    <div style="border: 2px solid #e9ecef; border-radius: 8px; padding: 1.5rem;">
                        <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 1rem;">
                            <i class="fas fa-credit-card" style="font-size: 2rem; color: #ff6600;"></i>
                            <div style="flex: 1;">
                                <h5 style="margin: 0;">iyzico</h5>
                                <small style="color: #64748b;">TÃ¼rkiye kredi kartÄ± Ã¶demeleri</small>
                            </div>
                            <label for="auto_42" class="sr-only">Enable</label>
                            <input type="checkbox" checked style="width: auto; margin: 0;" id="auto_42">
                            <label for="auto_43" class="sr-only">Test Mode</label>
                            <input type="checkbox" checked style="width: auto; margin: 0;" id="auto_43">
                            <span>Aktif</span>
                        </div>
                        <div class="form-group">
                            <label for="auto_44">API Key</label>
                            <input type="text" placeholder="sandbox-xxx..." id="auto_44" autocomplete="off">
                        </div>
                        <div class="form-group">
                            <label for="auto_45">API Key (alt)</label>
                            <input type="text" placeholder="sandbox-xxx..." id="auto_45" autocomplete="off">
                        </div>
                        <div class="form-group">
                            <label for="auto_46">Secret Key</label>
                            <input type="password" placeholder="sandbox-xxx..." id="auto_46" autocomplete="off">
                        </div>
                        <div class="form-group">
                            <label for="auto_47">Secret Key (alt)</label>
                            <input type="password" placeholder="sandbox-xxx..." id="auto_47" autocomplete="off">
                        </div>
                    </div>
                    
                    <button class="save-btn" style="margin-top: 1.5rem;">
                        <i class="fas fa-save"></i> Kaydet
                    </button>
                    </form>
                </div>

                <!-- Notifications Settings Tab -->
                <div id="notificationstab" class="settings-tab-content" style="display: none; background: white; padding: 2rem; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.08);">
                    <h3 style="margin-bottom: 1.5rem; color: #333; display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-bell"></i> Bildirim AyarlarÄ±
                    </h3>

                    <!-- Email Notifications -->
                    <form method="POST" id="emailNotificationForm" class="settings-form">
                    <div class="form-group">
                        <label for="auto_48">SMTP Host</label>
                        <input type="text" value="smtp.gmail.com" placeholder="smtp.example.com" id="auto_48">
                    </div>
                    <div class="form-group">
                        <label for="auto_51">SMTP Port</label>
                        <input type="number" value="587" id="auto_51">
                    </div>
                    <div class="form-group">
                        <label for="auto_52">Encryption</label>
                        <select id="auto_52">
                            <option value="tls" selected>TLS</option>
                            <option value="ssl">SSL</option>
                            <option value="none">None</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="auto_54">From Email</label>
                        <input type="text" value="no-reply@otoparkdemotime.com" id="auto_54">
                    </div>
                    <div class="form-group">
                        <label for="auto_55">SMTP Username</label>
                        <input type="text" placeholder="â€¢â€¢â€¢â€¢â€¢â€¢â€¢â€¢" id="auto_55" autocomplete="username">
                    </div>
                    <div class="form-group">
                        <label for="auto_56">SMTP Password</label>
                        <input type="password" placeholder="â€¢â€¢â€¢â€¢â€¢â€¢â€¢â€¢" id="auto_56" autocomplete="current-password">
                    </div>
                    <button class="save-btn" style="margin-top: 1rem;">
                        <i class="fas fa-save"></i> Kaydet
                    </button>
                    </form>

                    <!-- SMS Notifications -->
                    <form method="POST" id="smsNotificationForm" class="settings-form">
                    <h4 style="margin: 2rem 0 1rem 0; color: #333; display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-sms"></i> SMS Bildirimleri
                    </h4>
                    <div class="form-group">
                        <label for="auto_57" class="sr-only">Enable SMS</label>
                        <input type="checkbox" id="auto_57" checked style="width: auto; margin: 0;">
                    </div>
                    <div class="form-group" style="display:flex; gap:8px; align-items:center;">
                        <label for="auto_59" class="sr-only">Twilio Account SID</label>
                        <input type="text" placeholder="ACxxxxxxxxxx" id="auto_59" autocomplete="off">
                    </div>
                    <div class="form-group">
                        <label for="auto_60">Twilio Auth Token</label>
                        <input type="password" placeholder="â€¢â€¢â€¢â€¢â€¢â€¢â€¢â€¢" id="auto_60" autocomplete="off">
                    </div>
                    <div class="form-group">
                        <label for="auto_61">GÃ¶nderen Numara</label>
                        <input type="tel" value="+905551234567" id="auto_61">
                    </div>
                    <button class="save-btn" style="margin-top: 1rem;">
                        <i class="fas fa-save"></i> Kaydet
                    </button>
                    </form>

                    <!-- Push Notifications -->
                    <form method="POST" id="pushNotificationForm" class="settings-form">
                    <h4 style="margin: 2rem 0 1rem 0; color: #333; display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-bell"></i> Push Bildirimleri (Firebase)
                    </h4>
                    <div class="form-group">
                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                            <input type="checkbox" id="auto_63" style="width: auto; margin: 0;">
                            <span>Aktif Et</span>
                        </label>
                    </div>
                    <div class="form-group">
                        <label for="auto_65">Firebase Server Key</label>
                        <input type="password" placeholder="â€¢â€¢â€¢â€¢â€¢â€¢â€¢â€¢" id="auto_65" autocomplete="off">
                    </div>
                    <button class="save-btn" style="margin-top: 1rem;">
                        <i class="fas fa-save"></i> Kaydet
                    </button>
                    </form>
                </div>

                <!-- RBAC Settings Tab -->
                <div id="rbac" class="settings-tab-content" style="display: none; background: white; padding: 2rem; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.08);">
                    <h3 style="margin-bottom: 1.5rem; color: #333; display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-user-shield"></i> Rol ve Ä°zin YÃ¶netimi (RBAC)
                    </h3>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 2rem;">
                        <!-- Roles List -->
                        <div>
                            <h4 style="margin-bottom: 1rem;">Sistem Rolleri</h4>
                            <div style="display: flex; flex-direction: column; gap: 1rem;">
                                <div style="border: 2px solid #667eea; border-radius: 8px; padding: 1rem; background: #667eea10;">
                                    <div style="display: flex; justify-content: space-between; align-items: center;">
                                        <div>
                                            <h5 style="margin: 0; color: #667eea;">SuperAdmin</h5>
                                            <small style="color: #64748b;">Seviye: 100 - Tam Yetki</small>
                                        </div>
                                        <button class="action-btn edit-btn">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                    </div>
                                </div>
                                
                                <div style="border: 2px solid #28a745; border-radius: 8px; padding: 1rem; background: #28a74510;">
                                    <div style="display: flex; justify-content: space-between; align-items: center;">
                                        <div>
                                            <h5 style="margin: 0; color: #28a745;">Admin</h5>
                                            <small style="color: #64748b;">Seviye: 80 - YÃ¶netici</small>
                                        </div>
                                        <button class="action-btn edit-btn">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                    </div>
                                </div>
                                
                                <div style="border: 2px solid #ffc107; border-radius: 8px; padding: 1rem; background: #ffc10710;">
                                    <div style="display: flex; justify-content: space-between; align-items: center;">
                                        <div>
                                            <h5 style="margin: 0; color: #ff8c00;">Manager</h5>
                                            <small style="color: #64748b;">Seviye: 60 - MÃ¼dÃ¼r</small>
                                        </div>
                                        <button class="action-btn edit-btn">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                    </div>
                                </div>
                                
                                <div style="border: 2px solid #dc3545; border-radius: 8px; padding: 1rem; background: #dc354510;">
                                    <div style="display: flex; justify-content: space-between; align-items: center;">
                                        <div>
                                            <h5 style="margin: 0; color: #dc3545;">Support</h5>
                                            <small style="color: #64748b;">Seviye: 40 - Destek</small>
                                        </div>
                                        <button class="action-btn edit-btn">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                    </div>
                                </div>
                                
                                <div style="border: 2px solid #6c757d; border-radius: 8px; padding: 1rem; background: #6c757d10;">
                                    <div style="display: flex; justify-content: space-between; align-items: center;">
                                        <div>
                                            <h5 style="margin: 0; color: #6c757d;">Auditor</h5>
                                            <small style="color: #64748b;">Seviye: 20 - DenetÃ§i</small>
                                        </div>
                                        <button class="action-btn edit-btn">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Permissions -->
                        <div>
                            <h4 style="margin-bottom: 1rem;">Ä°zin Kategorileri</h4>
                            <div style="display: flex; flex-direction: column; gap: 1rem;">
                                <div style="border: 1px solid #e9ecef; border-radius: 8px; padding: 1rem;">
                                    <h5 style="margin: 0 0 0.5rem 0; display: flex; align-items: center; gap: 8px;">
                                        <i class="fas fa-users" style="color: #667eea;"></i>
                                        KullanÄ±cÄ± Ä°zinleri
                                    </h5>
                                    <div style="display: flex; flex-wrap: wrap; gap: 0.5rem; font-size: 0.875rem;">
                                        <span style="background: #e9ecef; padding: 4px 8px; border-radius: 4px;">users.view</span>
                                        <span style="background: #e9ecef; padding: 4px 8px; border-radius: 4px;">users.create</span>
                                        <span style="background: #e9ecef; padding: 4px 8px; border-radius: 4px;">users.edit</span>
                                        <span style="background: #e9ecef; padding: 4px 8px; border-radius: 4px;">users.delete</span>
                                    </div>
                                </div>
                                
                                <div style="border: 1px solid #e9ecef; border-radius: 8px; padding: 1rem;">
                                    <h5 style="margin: 0 0 0.5rem 0; display: flex; align-items: center; gap: 8px;">
                                        <i class="fas fa-shopping-cart" style="color: #28a745;"></i>
                                        SipariÅŸ Ä°zinleri
                                    </h5>
                                    <div style="display: flex; flex-wrap: wrap; gap: 0.5rem; font-size: 0.875rem;">
                                        <span style="background: #e9ecef; padding: 4px 8px; border-radius: 4px;">orders.view</span>
                                        <span style="background: #e9ecef; padding: 4px 8px; border-radius: 4px;">orders.edit</span>
                                        <span style="background: #e9ecef; padding: 4px 8px; border-radius: 4px;">orders.cancel</span>
                                    </div>
                                </div>
                                
                                <div style="border: 1px solid #e9ecef; border-radius: 8px; padding: 1rem;">
                                    <h5 style="margin: 0 0 0.5rem 0; display: flex; align-items: center; gap: 8px;">
                                        <i class="fas fa-credit-card" style="color: #ffc107;"></i>
                                        Ã–deme Ä°zinleri
                                    </h5>
                                    <div style="display: flex; flex-wrap: wrap; gap: 0.5rem; font-size: 0.875rem;">
                                        <span style="background: #e9ecef; padding: 4px 8px; border-radius: 4px;">payments.view</span>
                                        <span style="background: #e9ecef; padding: 4px 8px; border-radius: 4px;">payments.process</span>
                                        <span style="background: #e9ecef; padding: 4px 8px; border-radius: 4px;">payments.refund</span>
                                    </div>
                                </div>
                                
                                <div style="border: 1px solid #e9ecef; border-radius: 8px; padding: 1rem;">
                                    <h5 style="margin: 0 0 0.5rem 0; display: flex; align-items: center; gap: 8px;">
                                        <i class="fas fa-cog" style="color: #dc3545;"></i>
                                        Sistem Ä°zinleri
                                    </h5>
                                    <div style="display: flex; flex-wrap: wrap; gap: 0.5rem; font-size: 0.875rem;">
                                        <span style="background: #e9ecef; padding: 4px 8px; border-radius: 4px;">settings.view</span>
                                        <span style="background: #e9ecef; padding: 4px 8px; border-radius: 4px;">settings.edit</span>
                                        <span style="background: #e9ecef; padding: 4px 8px; border-radius: 4px;">logs.view</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <button class="add-btn" style="margin-top: 1.5rem;">
                        <i class="fas fa-plus"></i> Yeni Rol Ekle
                    </button>
                </div>

                <!-- Security Settings Tab -->
                <div id="securitytab" class="settings-tab-content" style="display: none; background: white; padding: 2rem; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.08);">
                    <h3 style="margin-bottom: 1.5rem; color: #333; display: flex; align-items: center; gap: 8px;">
                        <i for="auto_66" class="sr-only">Input<input type="checkbox" checked style="width: auto; margin: 0;" id="auto_66">         </i></h3>
                    
                    <div class="form-group">
                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                            <label for="auto_67" class="sr-only">Input</label><input type="checkbox" checked style="width: auto; margin: 0;" id="auto_67">
                            <span>Ä°ki FaktÃ¶rlÃ¼ Kimlik DoÄŸrulama (2FA) Zorunlu</span>
                        </label>
                    <label for="auto_68" class="sr-only">Input</label><input type="number" value="60" min="5" max="1440" id="auto_68">nÄ±cÄ±larÄ± iÃ§in 2FA zorunlu olacak
                    </div>
                    
                    <div class="form-group">
                        <label for="auto_69">Oturum Zaman AÅŸÄ±mÄ± (dakika)</label>
                        <input type="number" value="60" min="5" max="1440" id="auto_69">
                        <small style="color: #64748b; display:block;">KullanÄ±cÄ± etkin deÄŸilse otomatik Ã§Ä±kÄ±ÅŸ sÃ¼resi</small>
                    </div>

                    <div class="form-group">
                        <label for="auto_70">BoÅŸta Kalma (dakika)</label>
                        <input type="number" value="5" min="3" max="10" id="auto_70">
                    </div>

                    <div class="form-group">
                        <label for="auto_71">Maksimum BaÅŸarÄ±sÄ±z GiriÅŸ Denemesi</label>
                        <input type="number" value="5" min="1" max="100" id="auto_71">
                        <small style="color: #64748b; display:block;">Bu sayÄ±da baÅŸarÄ±sÄ±z giriÅŸten sonra hesap kilitlenecek</small>
                    </div>

                    <div class="form-group">
                        <label for="auto_72">Hesap Kilitlenme SÃ¼resi (dakika)</label>
                        <input type="number" value="30" min="1" max="1440" id="auto_72">
                    </div>

                    <div class="form-group">
                        <label for="auto_73">Minimum Åžifre UzunluÄŸu</label>
                        <input type="number" value="8" min="6" max="128" id="auto_73">
                    </div>

                    <div class="form-group">
                        <label for="auto_74">Åžifre GeÃ§erlilik SÃ¼resi (gÃ¼n)</label>
                        <input type="number" value="30" min="1" max="3650" id="auto_74">
                    </div>

                    <div class="form-group">
                        <label for="auto_75">Minimum Åžifre KurallarÄ±</label>
                        <input type="checkbox" checked style="width: auto; margin: 0;" id="auto_75">
                        <small style="color: #64748b; display:block; margin-top:6px;">Zorunlu minimum karakter kurallarÄ± uygulanacak</small>
                    </div>

                    <div class="form-group">
                        <label for="auto_76">Åžifre KarmaÅŸÄ±klÄ±k KurallarÄ±</label>
                        <input type="checkbox" checked style="width: auto; margin: 0;" id="auto_76">
                        <small style="color: #64748b; display:block; margin-top:6px;">BÃ¼yÃ¼k/kÃ¼Ã§Ã¼k harf, rakam ve Ã¶zel karakter gerektirir</small>
                    </div>

                    <div class="form-group">
                        <label for="auto_77">Åžifre Yenileme ZorunluluÄŸu (gÃ¼n)</label>
                        <input type="number" value="90" min="0" max="3650" id="auto_77">
                    </div>
                    
                    <div class="form-group">
                        <label for="auto_78">Ã…Âžifre DeÃ„ÂŸiÃ…ÂŸtirme Periyodu (gÃƒÂ¼n)</label>
                        <label for="auto_78" class="sr-only">Input</label><input type="number" value="90" min="0" max="365" id="auto_78">
                        <small style="color: #64748b;">0 girerek devre<label for="auto_79" class="sr-only">Input</label><input type="checkbox" style="width: auto; margin: 0;" id="auto_79"> </small></div>
                    
                    <h4 style="margin: 2rem 0 1rem 0;">IP Beyaz Listesi</h4>
                    <div class="form-group">
                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                            <label for="auto_80" class="sr-only">Input</label><input type="checkbox" style="width: auto; margin: 0;" id="auto_80">
                            <span>IP KÃ„Â±sÃ„Â±tlamasÃ„Â± Aktif</span>
                        </label>
                        <small style="color: #64748b;">Sadece belirlenen IP adreslerinden eriÃ…ÂŸime izin ver</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="auto_81">Ã„Â°zin Verilen IP Adresleri (her satÃ„Â±rda bir IP)</label>
                        <label for="auto_81" class="sr-only">192.168.1.1
192.168.1.2
10.0.0.1</label><textarea rows="5" placeholder="192.168.1.1
192.168.1.2
10.0.0.1" id="auto_81"></textarea>
                    </div>
                    
                    <button class="save-btn" style="margin-top: 1rem;">
                        <i class="fas fa-save"></i> Kaydet
                    </button>
                </div>

                <!-- Backup Settings Tab -->
                <div id="backuptab" class="settings-tab-content" style="display: none; background: white; padding: 2rem; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.08);">
                    <form method="POST" id="backupSettingsForm" class="settings-form">
                    <h3 style="margin-bottom: 1.5rem; color: #333; display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-database"></i> Yedekleme AyarlarÄ±
                    </h3>
                    
                    <div class="form-group">
                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                            <input type="checkbox" checked id="auto_83" style="width: auto; margin: 0;">
                            <span>Otomatik Yedekleme Aktif</span>
                        </label>
                        <small style="color: #64748b;">Belirlenen zamanlarda otomatik yedek alÄ±nacak</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="auto_85">Yedekleme SÄ±klÄ±ÄŸÄ±</label>
                        <select id="auto_85">
                            <option value="hourly">Saatlik</option>
                            <option value="daily" selected>GÃ¼nlÃ¼k</option>
                            <option value="weekly">HaftalÄ±k</option>
                            <option value="monthly">AylÄ±k</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="auto_86">Yedekleme Saati</label>
                        <input type="time" value="02:00" id="auto_86">
                        <small style="color: #64748b;">Yedekleme iÅŸleminin yapÄ±lacaÄŸÄ± saat</small>
                    </div>

                    <div class="form-group">
                        <label for="auto_87">Yedek Saklama SÃ¼resi (gÃ¼n)</label>
                        <input type="number" value="30" min="1" max="365" id="auto_87">
                        <small style="color: #64748b;">Bu sÃ¼reden eski yedekler otomatik silinecek</small>
                    </div>

                    <div class="form-group">
                        <label for="auto_88">Yedek Saklama Azami (gÃ¼n)</label>
                        <input type="number" value="10" min="1" max="100" id="auto_88">
                    </div>
                    
                    <div class="form-group">
                        <label for="auto_89">Maksimum Yedek SayÄ±sÄ±</label>
                        <div>
                            <label for="auto_89" class="sr-only">Input</label>
                            <input type="checkbox" checked style="width: auto; margin: 0;" id="auto_89">
                        </div>
                    </div>

                    <h4 style="margin: 2rem 0 1rem 0;">Yedek Depolama</h4>

                    <div class="form-group">
                        <label for="auto_90" class="block">Yedek KlasÃ¶rÃ¼</label>
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <label for="auto_90" class="sr-only">Backup path</label>
                            <input type="text" value="/var/backups/carwash" id="auto_90" class="w-full px-3 py-2 border rounded-lg">
                            <label for="auto_91" class="sr-only">Use local storage</label>
                            <input type="checkbox" checked id="auto_91" style="width: auto; margin: 0;">
                            <span>Yerel Sunucuda Sakla</span>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="auto_94">FTP Host</label>
                        <input type="text" placeholder="ftp.example.com" id="auto_94">
                        <label for="auto_95" class="sr-only">Enable FTP</label>
                        <input type="checkbox" style="width: auto; margin: 0;" id="auto_95">
                        <span>Uzak Sunucuya YÃ¼kle (FTP/SFTP)</span>
                    </div>

                    <div class="form-group">
                        <label for="auto_97">FTP Host (alt)</label>
                        <input type="text" placeholder="ftp.example.com" id="auto_97">
                    </div>

                    <div class="form-group">
                        <label for="auto_99">FTP Username</label>
                        <input type="text" placeholder="username" id="auto_99" autocomplete="username">
                    </div>
                    <div class="form-group">
                        <label for="auto_100">FTP Password</label>
                        <input type="password" placeholder="â€¢â€¢â€¢â€¢â€¢â€¢â€¢â€¢" id="auto_100" autocomplete="off">
                    </div>
                    
                    <button class="save-btn" style="margin-top: 1rem;">
                        <i class="fas fa-save"></i> Kaydet
                    </button>
                    </form>
                </div>

                <!-- Email Templates Tab -->
                <div id="emailtab" class="settings-tab-content" style="display: none; background: white; padding: 2rem; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.08);">
                    <h3 style="margin-bottom: 1.5rem; color: #333; display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-envelope"></i> Email Ã…ÂžablonlarÃ„Â±
                    </h3>
                    
                    <div style="display: flex; flex-direction: column; gap: 1rem;">
                        <!-- Welcome Email -->
                        <div style="border: 1px solid #e9ecef; border-radius: 8px; padding: 1.5rem;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                                <div>
                                    <h5 style="margin: 0;">HoÃ…ÂŸ Geldin Emaili</h5>
                                    <small style="color: #64748b;">Yeni kullanÃ„Â±cÃ„Â± kaydÃ„Â±nda gÃƒÂ¶nderilen email<label for="auto_101" class="sr-only">Input</label><input type="text" value="CarWash'a HoÃ…ÂŸ Geldiniz!" id="auto_101">                            <button class="action-btn edit-btn">
                                    <i class="fas fa-edit"></i> DÃƒÂ¼zenle
                                </button>
                            </small></div>
                            <div class="form-group" style="margin-bottom: 0.5rem;">
                                <label for="auto_102">Konu</label>
                                <label for="auto_102" class="sr-only">Input</label><input type="text" value="CarWash'a HoÃ…ÂŸ Geldiniz!" id="auto_102">
                            </div>
                            <div class="form-group" style="margin-bottom: 0;">
                                <label for="auto_103">GÃƒÂ¶vde</label>
                                <label for="auto_103" class="sr-only">Input</label><textarea rows="3" readonly id="auto_103">Merhaba {{user_name}}, CarWash ailesine hoÅŸ geldiniz!</textarea>
                            </div>
                        </div>
                        
                        <!-- Order Confirmation -->
                        <div style="border: 1px solid #e9ecef; border-radius: 8px; padding: 1.5rem;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                                <div>
                                    <h5 style="margin: 0;">SipariÃ…ÂŸ OnayÃ„Â±</h5>
                                    <small style="color: #64748b;">SipariÃ…ÂŸ oluÃ…ÂŸturulduÃ„ÂŸunda gÃƒÂ¶nderile<label for="auto_104" class="sr-only">Input</label><input type="text" value="SipariÃ…ÂŸiniz AlÃ„Â±ndÃ„Â± - #{{order_id}}" id="auto_104">                        <button class="action-btn edit-btn">
                                    <i class="fas fa-edit"></i> DÃƒÂ¼zenle
                                </button>
                            </small></div>
                            <div class="form-group" style="margin-bottom: 0.5rem;">
                                <label for="auto_105">Konu</label>
                                <label for="auto_105" class="sr-only">Input</label><input type="text" value="SipariÃ…ÂŸiniz AlÃ„Â±ndÃ„Â± - #{{order_id}}" id="auto_105">
                            </div>
                            <div class="form-group" style="margin-bottom: 0;">
                                <label for="auto_106">GÃƒÂ¶vde</label>
              <label for="auto_106" class="sr-only">Input</label><textarea rows="3" readonly id="auto_106">SipariÅŸiniz baÅŸarÄ±yla alÄ±ndÄ±. SipariÅŸ No: {{order_id}}</textarea>
                            </div>
                        </div>
                        
                        <!-- Password Reset -->
                        <div style="border: 1px solid #e9ecef; border-radius: 8px; padding: 1.5rem;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                                <div>
                                    <h5 style="margin: 0;">Ã…Âžifre SÃ„Â±fÃ„Â±rlama</h5>
                                    <small style="color: #64748b;">Ã…Âžifre sÃ„Â±fÃ„Â±rlama ta<label for="auto_107" class="sr-only">Input</label><input type="text" value="Ã…Âžifre SÃ„Â±fÃ„Â±rlama Talebi" id="auto_107">     </small></div>
                                <button class="action-btn edit-btn">
                                    <i class="fas fa-edit"></i> DÃƒÂ¼zenle
                                </button>
                            </div>
                            <div class="form-group" style="margin-bottom: 0.5rem;">
                                <label for="auto_108">Konu</label>
                                <label for="auto_108" class="sr-only">Input</label><input type="text" value="Ã…Âžifre SÃ„Â±fÃ„Â±rlama Talebi" id="auto_108">
                            </div>
                            <div class="form-group" style="margin-bottom: 0;">
                                <label for="auto_109">GÃƒÂ¶vde</label>
    <label for="auto_109" class="sr-only">Input</label><textarea rows="3" readonly id="auto_109">Åžifrenizi sÄ±fÄ±rlamak iÃ§in aÅŸaÄŸÄ±daki linke tÄ±klayÄ±n: {{reset_link}}</textarea>
                            </div>
                        </div>
                    </div>
                    
                    <div style="margin-top: 1.5rem; padding: 1rem; background: #667eea10; border-radius: 8px; border-left: 4px solid #667eea;">
                        <p style="margin: 0; color: #333;"><strong>KullanÃ„Â±labilir DeÃ„ÂŸiÃ…ÂŸkenler:</strong></p>
                        <code style="display: block; margin-top: 0.5rem; font-size: 0.875rem;">
                            {{user_name}}, {{user_email}}, {{order_id}}, {{service_name}}, {{price}}, {{date}}, {{time}}, {{reset_link}}
                        </code>
                    </div>
                </div>
            
    
</div>

<!-- Modals -->
<!-- Add Car Wash Modal -->
    <!-- FarsÃƒÂ§a: Ã™Â…Ã™ÂˆÃ˜Â¯Ã˜Â§Ã™Â„ Ã˜Â§Ã™ÂÃ˜Â²Ã™ÂˆÃ˜Â¯Ã™Â† ÃšÂ©Ã˜Â§Ã˜Â±Ã™ÂˆÃ˜Â§Ã˜Â´. -->
    <!-- TÃƒÂ¼rkÃƒÂ§e: Otopark Ekle ModalÃ„Â±. -->
    <!-- English: Add Car Wash Modal. -->
    <div id="carwashModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Yeni Otopark Ekle</h3>
                <span class="close" id="closeCarwashModal">Ã—</span>
            </div>
            <div class="modal-body">
                <form id="carwashForm">
                    <div class="form-group">
                        <label for="carwashName">Otopark AdÃ„Â±</label>
                        <label for="carwashName" class="sr-only">Input</label><input type="text" id="carwashName" required>
                    </div>
                    <div class="form-group">
                        <label for="carwashAddress">Adres</label>
                        <label for="carwashAddress" class="sr-only">Input</label><textarea id="carwashAddress" required></textarea>
                    </div>
                    <div class="form-group">
                        <label for="carwashCapacity">Kapasite</label>
                        <label for="carwashCapacity" class="sr-only">Input</label><input type="number" id="carwashCapacity" required>
                    </div>
                    <div class="form-group">
                        <label for="carwashPrice">Saat ÃƒÂœcreti (Ã¢Â‚Âº)</label>
                        <label for="carwashPrice" class="sr-only">Input</label><input type="number" id="carwashPrice" required>
                    </div>
                    <div class="form-group">
                        <label for="carwashStatus">Durum</label>
                        <select id="carwashStatus" required title="Durum">
                            <option value="active">Aktif</option>
                            <option value="inactive">Pasif</option>
                            <option value="maintenance">BakÃ„Â±mda</option>
                        </select>
                    </div>
                    <button type="submit" class="submit-btn">Otopark Ekle</button>
                </form>
            </div>
        </div>
    </div>

<!-- Add Service Modal -->
    <!-- FarsÃƒÂ§a: Ã™Â…Ã™ÂˆÃ˜Â¯Ã˜Â§Ã™Â„ Ã˜Â§Ã™ÂÃ˜Â²Ã™ÂˆÃ˜Â¯Ã™Â† Ã˜Â®Ã˜Â¯Ã™Â…Ã˜Â§Ã˜Âª. -->
    <!-- TÃƒÂ¼rkÃƒÂ§e: Hizmet Ekle ModalÃ„Â±. -->
    <!-- English: Add Service Modal. -->
    <div id="serviceModal" class="modal">
        <div class="modal-content" style="max-width: 700px;">
            <div class="modal-header">
                <h3><i class="fas fa-concierge-bell mr-2"></i>Yeni Hizmet Ekle</h3>
                <span class="close" id="closeServiceModal">Ã—</span>
            </div>
            <div class="modal-body">
                <form id="serviceForm">
                    <div class="form-group">
                        <label for="serviceName"><i class="fas fa-tag mr-1"></i>Hizmet AdÃ„Â± *</label>
                        <label for="serviceName" class="sr-only">Service name</label><input type="text" id="serviceName" name="service_name" placeholder="ÃƒÂ–rn: DÃ„Â±Ã…ÂŸ YÃ„Â±kama" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="serviceDescription"><i class="fas fa-align-left mr-1"></i>AÃƒÂ§Ã„Â±klama</label>
                        <label for="serviceDescription" class="sr-only">Description</label><textarea id="serviceDescription" name="description" rows="3" placeholder="Hizmet aÃƒÂ§Ã„Â±klamasÃ„Â±..."></textarea>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div class="form-group">
                            <label for="serviceCategory"><i class="fas fa-layer-group mr-1"></i>Kategori *</label>
                            <label for="serviceCategory" class="sr-only">Category</label><select id="serviceCategory" name="category" required>
                                <option value="">Kategori SeÃƒÂ§in</option>
                                <option value="wash">YÃ„Â±kama</option>
                                <option value="detail">DetaylÃ„Â± BakÃ„Â±m</option>
                                <option value="polish">Cilalama &amp; Koruma</option>
                                <option value="interior">Ã„Â°ÃƒÂ§ Temizlik</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="serviceDuration"><i class="fas fa-clock mr-1"></i>SÃƒÂ¼re (dakika) *</label>
                            <label for="serviceDuration" class="sr-only">Duration</label><input type="number" id="serviceDuration" name="duration" min="1" placeholder="30" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="priceSedan"><i class="fas fa-car mr-1"></i>AraÃƒÂ§ Tipi FiyatlandÃ„Â±rmasÃ„Â± *</label>
                        <div style="background: #f8f9fa; padding: 1rem; border-radius: 8px; margin-top: 0.5rem;">
                            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem; margin-bottom: 0.5rem;">
                                <div>
                                    <label style="font-size: 0.85rem; color: #666; font-weight: normal;" for="priceSedan">Sedan (Ã¢Â‚Âº) *</label>
                                    <label for="priceSedan" class="sr-only">Price sedan</label><input type="number" id="priceSedan" name="price_sedan" min="0" step="0.01" placeholder="150" required style="margin-top: 0.25rem;">
                                </div>
                                <div>
                                    <label style="font-size: 0.85rem; color: #666; font-weight: normal;" for="priceSUV">SUV (Ã¢Â‚Âº) *</label>
                                    <label for="priceSUV" class="sr-only">Price suv</label><input type="number" id="priceSUV" name="price_suv" min="0" step="0.01" placeholder="180" required style="margin-top: 0.25rem;">
                                </div>
                                <div>
                                    <label style="font-size: 0.85rem; color: #666; font-weight: normal;" for="priceTruck">Kamyonet (Ã¢Â‚Âº) *</label>
                                    <label for="priceTruck" class="sr-only">Price truck</label><input type="number" id="priceTruck" name="price_truck" min="0" step="0.01" placeholder="200" required style="margin-top: 0.25rem;">
                                </div>
                            </div>
                            <small style="color: #666; font-size: 0.8rem;">
                                <i class="fas fa-info-circle"></i> Her araÃƒÂ§ tipi iÃƒÂ§in farklÃ„Â± fiyat belirleyebilirsiniz
                            </small>
                        </div>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div class="form-group">
                            <label for="serviceOrder"><i class="fas fa-sort-numeric-up mr-1"></i>SÃ„Â±ralama</label>
                            <label for="serviceOrder" class="sr-only">Sort order</label><input type="number" id="serviceOrder" name="sort_order" min="1" placeholder="1" value="1">
                        </div>
                        
                        <div class="form-group">
                            <label for="serviceStatus"><i class="fas fa-toggle-on mr-1"></i>Durum *</label>
                            <label for="serviceStatus" class="sr-only">Status</label><select id="serviceStatus" name="status" required>
                                <option value="active">Aktif</option>
                                <option value="inactive">Pasif</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="serviceIcon"><i class="fas fa-icons mr-1"></i>Ã„Â°kon (Font Awesome sÃ„Â±nÃ„Â±fÃ„Â±)</label>
                        <label for="serviceIcon" class="sr-only">Icon</label><input type="text" id="serviceIcon" name="icon" placeholder="fas fa-car" value="fas fa-car">
                        <small style="color: #666; font-size: 0.8rem; display: block; margin-top: 0.25rem;">
                            <i class="fas fa-lightbulb"></i> ÃƒÂ–rnek: fas fa-car, fas fa-broom, fas fa-star, fas fa-shield-alt
                        </small>
                    </div>
                    
                    <button type="submit" class="submit-btn">
                        <i class="fas fa-save mr-2"></i>Hizmeti Kaydet
                    </button>
                </form>
            </div>
        </div>
    </div>

<!-- Add Ticket Modal -->
    <!-- FarsÃƒÂ§a: Ã™Â…Ã™ÂˆÃ˜Â¯Ã˜Â§Ã™Â„ Ã˜Â§Ã™ÂÃ˜Â²Ã™ÂˆÃ˜Â¯Ã™Â† Ã˜ÂªÃ›ÂŒÃšÂ©Ã˜Âª. -->
    <!-- TÃƒÂ¼rkÃƒÂ§e: Destek Talebi Ekle ModalÃ„Â±. -->
    <!-- English: Add Ticket Modal. -->
    <div id="ticketModal" class="modal">
        <div class="modal-content" style="max-width: 650px;">
            <div class="modal-header" style="background: linear-gradient(135deg, #fd7e14, #dc3545);">
                <h3><i class="fas fa-ticket-alt mr-2"></i>Yeni Destek Talebi OluÃ…ÂŸtur</h3>
                <span class="close" id="closeTicketModal">Ã—</span>
            </div>
            <div class="modal-body">
                <form id="ticketForm">
                    <div class="form-group">
                        <label for="ticketCustomer"><i class="fas fa-user mr-1"></i>MÃƒÂ¼Ã…ÂŸteri SeÃƒÂ§in *</label>
                        <label for="ticketCustomer" class="sr-only">Customer id</label><select id="ticketCustomer" name="customer_id" required>
                            <option value="">MÃƒÂ¼Ã…ÂŸteri SeÃƒÂ§in</option>
                            <option value="1">Ahmet YÃ„Â±lmaz - ahmet@email.com</option>
                            <option value="2">Elif Kara - elif@email.com</option>
                            <option value="3">Mehmet Demir - mehmet@email.com</option>
                            <option value="4">Zeynep ÃƒÂ–ztÃƒÂ¼rk - zeynep@email.com</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="ticketSubject"><i class="fas fa-heading mr-1"></i>Konu *</label>
                        <label for="ticketSubject" class="sr-only">Subject</label><input type="text" id="ticketSubject" name="subject" placeholder="Talep konusu..." required>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div class="form-group">
                            <label for="ticketCategory"><i class="fas fa-tag mr-1"></i>Kategori *</label>
                            <label for="ticketCategory" class="sr-only">Category</label><select id="ticketCategory" name="category" required>
                                <option value="">Kategori SeÃƒÂ§in</option>
                                <option value="technical">Teknik Destek</option>
                                <option value="billing">ÃƒÂ–deme &amp; Fatura</option>
                                <option value="service">Hizmet SorularÃ„Â±</option>
                                <option value="complaint">Ã…Âžikayet</option>
                                <option value="other">DiÃ„ÂŸer</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="ticketPriority"><i class="fas fa-exclamation-circle mr-1"></i>ÃƒÂ–ncelik *</label>
                            <label for="ticketPriority" class="sr-only">Priority</label><select id="ticketPriority" name="priority" required>
                                <option value="">ÃƒÂ–ncelik SeÃƒÂ§in</option>
                                <option value="low">DÃƒÂ¼Ã…ÂŸÃƒÂ¼k</option>
                                <option value="medium" selected>Orta</option>
                                <option value="high">YÃƒÂ¼ksek</option>
                                <option value="urgent">Acil</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="ticketMessage"><i class="fas fa-align-left mr-1"></i>Mesaj *</label>
                        <label for="ticketMessage" class="sr-only">Message</label><textarea id="ticketMessage" name="message" rows="5" placeholder="Talep detaylarÃ„Â±nÃ„Â± yazÃ„Â±n..." required></textarea>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div class="form-group">
                            <label for="ticketAssignedTo"><i class="fas fa-user-tag mr-1"></i>Atanan KiÃ…ÂŸi</label>
                            <label for="ticketAssignedTo" class="sr-only">Assigned to</label><select id="ticketAssignedTo" name="assigned_to">
                                <option value="">Atama YapÃ„Â±lmadÃ„Â±</option>
                                <option value="1">Destek Ekibi - Ahmet</option>
                                <option value="2">Destek Ekibi - AyÃ…ÂŸe</option>
                                <option value="3">Destek Ekibi - Mehmet</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="ticketStatus"><i class="fas fa-flag mr-1"></i>Durum</label>
                            <label for="ticketStatus" class="sr-only">Status</label><select id="ticketStatus" name="status">
                                <option value="new" selected>Yeni</option>
                                <option value="open">AÃƒÂ§Ã„Â±k</option>
                                <option value="in_progress">Devam Ediyor</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="ticketAttachment"><i class="fas fa-paperclip mr-1"></i>Dosya Ekle (Opsiyonel)</label>
                        <label for="ticketAttachment" class="sr-only">Attachment</label><input type="file" id="ticketAttachment" name="attachment" accept="image/*,.pdf,.doc,.docx" style="padding: 8px;">
                        <small style="color: #666; font-size: 0.8rem; display: block; margin-top: 0.25rem;">
                            <i class="fas fa-info-circle"></i> Maksimum dosya boyutu: 5MB
                        </small>
                    </div>
                    
                    <button type="submit" class="submit-btn" style="background: linear-gradient(135deg, #fd7e14, #dc3545);">
                        <i class="fas fa-paper-plane mr-2"></i>Talebi OluÃ…ÂŸtur
                    </button>
                </form>
            </div>
        </div>
    </div>

<!-- Add User Modal -->
    <!-- FarsÃƒÂ§a: Ã™Â…Ã™ÂˆÃ˜Â¯Ã˜Â§Ã™Â„ Ã˜Â§Ã™ÂÃ˜Â²Ã™ÂˆÃ˜Â¯Ã™Â† ÃšÂ©Ã˜Â§Ã˜Â±Ã˜Â¨Ã˜Â±. -->
    <!-- TÃƒÂ¼rkÃƒÂ§e: KullanÃ„Â±cÃ„Â± Ekle ModalÃ„Â±. -->
    <!-- English: Add User Modal. -->
    <div id="userModal" class="modal">
        <div class="modal-content" style="max-width: 650px; max-height: 90vh; overflow-y: auto;">
            <div class="modal-header">
                <h3>Yeni KullanÃ„Â±cÃ„Â± Ekle</h3>
                <span class="close" id="closeUserModal">Ã—</span>
            </div>
            <div class="modal-body">
                <form id="userForm">
                    <div class="form-group">
                        <label for="userName">KullanÃ„Â±cÃ„Â± AdÃ„Â± *</label>
                        <label for="userName" class="sr-only">Username</label><input type="text" name="username" id="userName" required placeholder="kullanici_adi" autocomplete="username">
                        <small style="color: #64748b;">Benzersiz kullanÃ„Â±cÃ„Â± adÃ„Â±</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="userEmail">Email Adresi *</label>
                        <label for="userEmail" class="sr-only">Email</label><input type="email" name="email" id="userEmail" required placeholder="ornek@email.com">
                    </div>
                    
                    <div class="form-group">
                        <label for="userPassword">Åžifre *</label>
                        <label for="userPassword" class="sr-only">Password</label>
                        <input type="password" name="password" id="userPassword" required placeholder="GÃ¼Ã§lÃ¼ ÅŸifre" autocomplete="new-password">
                        <small style="color: #64748b;">En az 8 karakter, bÃƒÂ¼yÃƒÂ¼k/kÃƒÂ¼ÃƒÂ§ÃƒÂ¼k harf ve rakam iÃƒÂ§ermeli</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="userPasswordConfirm">Åžifre Tekrar *</label>
                        <label for="userPasswordConfirm" class="sr-only">Password confirm</label>
                        <input type="password" name="password_confirm" id="userPasswordConfirm" required placeholder="Åžifreyi tekrar girin" autocomplete="new-password">
                    </div>
                    
                    <div class="form-group">
                        <label for="userFullName">Tam AdÃ„Â± *</label>
                        <label for="userFullName" class="sr-only">Full name</label><input type="text" name="full_name" id="userFullName" required placeholder="Ad Soyad">
                    </div>
                    
                    <div class="form-group">
                        <label for="userPhone">Telefon</label>
                        <label for="userPhone" class="sr-only">Phone</label><input type="tel" name="phone" id="userPhone" placeholder="+90 555 123 4567">
                    </div>
                    
                    <div class="form-group">
                        <label for="userRole">Rol *</label>
                        <select name="role_id" id="userRole" required title="Rol">
                            <option value="">Rol SeÃƒÂ§in</option>
                            <option value="1">SuperAdmin - Tam Yetki</option>
                            <option value="2">Admin - YÃƒÂ¶netici</option>
                            <option value="3">Manager - MÃƒÂ¼dÃƒÂ¼r</option>
                            <option value="4">Support - Destek</option>
                            <option value="5">Auditor - DenetÃƒÂ§i</option>
                        </select>
                        <small style="color: #64748b;">KullanÃ„Â±cÃ„Â±nÃ„Â±n eriÃ…ÂŸim seviyesini belirler</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="userStatus">Durum</label>
                        <select name="status" id="userStatus" title="Durum">
                            <option value="active">Aktif</option>
                            <option value="inactive">Pasif</option>
                            <option value="suspended">AskÃ„Â±ya AlÃ„Â±nmÃ„Â±Ã…ÂŸ</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                            <label for="userRequire2FA" class="sr-only">Require 2fa</label><input type="checkbox" name="require_2fa" id="userRequire2FA" style="width: auto; margin: 0;">
                            <span>Ã„Â°ki FaktÃƒÂ¶rlÃƒÂ¼ Kimlik DoÃ„ÂŸrulama Zorunlu</span>
                        </label>
                        <small style="color: #64748b;">KullanÃ„Â±cÃ„Â± ilk giriÃ…ÂŸte 2FA kurulumu yapacak</small>
                    </div>
                    
                    <div class="form-group">
                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                            <label for="userEmailVerified" class="sr-only">Email verified</label><input type="checkbox" name="email_verified" id="userEmailVerified" checked style="width: auto; margin: 0;">
                            <span>Email DoÃ„ÂŸrulanmÃ„Â±Ã…ÂŸ Olarak Ã„Â°Ã…ÂŸaretle</span>
                        </label>
                    </div>
                    
                    <button type="submit" class="submit-btn">
                        <i class="fas fa-user-plus"></i>
                        KullanÃ„Â±cÃ„Â± OluÃ…ÂŸtur
                    </button>
                </form>
            </div>
        </div>
    </div>

<!-- Add CMS Page Modal -->
    <!-- FarsÃƒÂ§a: Ã™Â…Ã™ÂˆÃ˜Â¯Ã˜Â§Ã™Â„ Ã˜Â§Ã™ÂÃ˜Â²Ã™ÂˆÃ˜Â¯Ã™Â† Ã˜ÂµÃ™ÂÃ˜Â­Ã™Â‡ CMS. -->
    <!-- TÃƒÂ¼rkÃƒÂ§e: CMS SayfasÃ„Â± Ekle ModalÃ„Â±. -->
    <!-- English: Add CMS Page Modal. -->
    <div id="cmsPageModal" class="modal">
        <div class="modal-content" style="max-width: 900px; max-height: 90vh; overflow-y: auto;">
            <div class="modal-header" style="background: linear-gradient(135deg, #764ba2, #667eea);">
                <h3><i class="fas fa-file-alt mr-2"></i>Yeni Sayfa OluÃ…ÂŸtur</h3>
                <span class="close" id="closeCmsPageModal">Ã—</span>
            </div>
            <div class="modal-body">
                <form id="cmsPageForm">
                    <!-- Page Basic Information -->
                    <div style="background: #f8f9fa; padding: 16px; border-radius: 8px; margin-bottom: 24px; border-left: 4px solid #764ba2;">
                        <h4 style="margin: 0 0 16px 0; color: #764ba2; display: flex; align-items: center; gap: 8px;">
                            <i class="fas fa-info-circle"></i>
                            Sayfa Bilgileri
                        </h4>
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                            <div class="form-group">
                                <label for="pageTitle"><i class="fas fa-heading mr-1"></i>Sayfa BaÃ…ÂŸlÃ„Â±Ã„ÂŸÃ„Â± *</label>
                                <label for="pageTitle" class="sr-only">Page title</label><input type="text" name="page_title" id="pageTitle" required placeholder="ÃƒÂ–rn: HakkÃ„Â±mÃ„Â±zda">
                                <small style="color: #64748b;">Sayfa baÃ…ÂŸlÃ„Â±Ã„ÂŸÃ„Â± (meta title)</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="pageSlug"><i class="fas fa-link mr-1"></i>URL Slug *</label>
                                <label for="pageSlug" class="sr-only">Page slug</label><input type="text" name="page_slug" id="pageSlug" required placeholder="ÃƒÂ–rn: hakkimizda">
                                <small style="color: #64748b;">URL dostu metin (otomatik oluÃ…ÂŸturulur)</small>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="pageDescription"><i class="fas fa-align-left mr-1"></i>KÃ„Â±sa AÃƒÂ§Ã„Â±klama</label>
                            <label for="pageDescription" class="sr-only">Page description</label><textarea name="page_description" id="pageDescription" rows="2" placeholder="Sayfa meta aÃƒÂ§Ã„Â±klamasÃ„Â± (SEO iÃƒÂ§in ÃƒÂ¶nemli)"></textarea>
                            <small style="color: #64748b;">150-160 karakter ÃƒÂ¶nerilir</small>
                        </div>
                    </div>

                    <!-- Page Content -->
                    <div style="background: #f8f9fa; padding: 16px; border-radius: 8px; margin-bottom: 24px; border-left: 4px solid #667eea;">
                        <h4 style="margin: 0 0 16px 0; color: #667eea; display: flex; align-items: center; gap: 8px;">
                            <i class="fas fa-file-code"></i>
                            Sayfa Ã„Â°ÃƒÂ§eriÃ„ÂŸi
                        </h4>
                        
                        <div class="form-group">
                            <label for="pageContent"><i class="fas fa-paragraph mr-1"></i>Ana Ã„Â°ÃƒÂ§erik *</label>
                            <label for="pageContent" class="sr-only">Page content</label><textarea name="page_content" id="pageContent" rows="10" required placeholder="Sayfa iÃƒÂ§eriÃ„ÂŸini buraya yazÃ„Â±n... HTML etiketleri kullanabilirsiniz."></textarea>
                            <small style="color: #64748b;">
                                <i class="fas fa-lightbulb"></i> 
                                HTML etiketleri desteklenir: &lt;h1&gt;, &lt;p&gt;, &lt;div&gt;, &lt;strong&gt;, &lt;em&gt;, &lt;ul&gt;, &lt;ol&gt;, &lt;a&gt;, &lt;img&gt;
                            </small>
                        </div>
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                            <div class="form-group">
                                <label for="featuredImage"><i class="fas fa-image mr-1"></i>ÃƒÂ–ne ÃƒÂ‡Ã„Â±kan GÃƒÂ¶rsel (URL)</label>
                                <label for="featuredImage" class="sr-only">Featured image</label><input type="url" name="featured_image" id="featuredImage" placeholder="https://example.com/image.jpg">
                                <small style="color: #64748b;">Sayfa gÃƒÂ¶rseli URL'si</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="backgroundColor"><i class="fas fa-palette mr-1"></i>Arka Plan Rengi</label>
                                <label for="backgroundColor" class="sr-only">Background color</label><input type="color" name="background_color" id="backgroundColor" value="#ffffff" style="height: 45px; padding: 4px;">
                                <small style="color: #64748b;">Sayfa arka plan rengi</small>
                            </div>
                        </div>
                    </div>

                    <!-- Page Settings -->
                    <div style="background: #f8f9fa; padding: 16px; border-radius: 8px; margin-bottom: 24px; border-left: 4px solid #28a745;">
                        <h4 style="margin: 0 0 16px 0; color: #28a745; display: flex; align-items: center; gap: 8px;">
                            <i class="fas fa-cog"></i>
                            Sayfa AyarlarÃ„Â±
                        </h4>
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px;">
                            <div class="form-group">
                                <label for="pageCategory"><i class="fas fa-list-alt mr-1"></i>Kategori *</label>
                                <label for="pageCategory" class="sr-only">Page category</label><select name="page_category" id="pageCategory" required>
                                    <option value="">Kategori SeÃƒÂ§in</option>
                                    <option value="about">HakkÃ„Â±mÃ„Â±zda</option>
                                    <option value="services">Hizmetler</option>
                                    <option value="contact">Ã„Â°letiÃ…ÂŸim</option>
                                    <option value="help">YardÃ„Â±m &amp; SSS</option>
                                    <option value="legal">Yasal</option>
                                    <option value="blog">Blog</option>
                                    <option value="other">DiÃ„ÂŸer</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="pageStatus"><i class="fas fa-flag mr-1"></i>Durum *</label>
                                <label for="pageStatus" class="sr-only">Page status</label><select name="page_status" id="pageStatus" required>
                                    <option value="draft">Taslak</option>
                                    <option value="published" selected>YayÃ„Â±nda</option>
                                    <option value="archived">ArÃ…ÂŸivlendi</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="pageLanguage"><i class="fas fa-language mr-1"></i>Dil *</label>
                                <label for="pageLanguage" class="sr-only">Page language</label><select name="page_language" id="pageLanguage" required>
                                    <option value="tr" selected>TÃƒÂ¼rkÃƒÂ§e</option>
                                    <option value="en">English</option>
                                    <option value="ar">Ã˜Â§Ã™Â„Ã˜Â¹Ã˜Â±Ã˜Â¨Ã™ÂŠÃ˜Â©</option>
                                    <option value="fa">Ã™ÂÃ˜Â§Ã˜Â±Ã˜Â³Ã›ÂŒ</option>
                                </select>
                            </div>
                        </div>
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-top: 16px;">
                            <div class="form-group">
                                <label for="pageOrder"><i class="fas fa-sort-numeric-up mr-1"></i>SÃ„Â±ralama</label>
                                <label for="pageOrder" class="sr-only">Page order</label><input type="number" name="page_order" id="pageOrder" value="0" min="0" placeholder="0">
                                <small style="color: #64748b;">Sayfa sÃ„Â±ralama numarasÃ„Â± (0 = en ÃƒÂ¼stte)</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="pageAuthor"><i class="fas fa-user-tie mr-1"></i>Yazar</label>
                                <label for="pageAuthor" class="sr-only">Page author</label><select name="page_author" id="pageAuthor">
                                    <option value="1" selected>Admin</option>
                                    <option value="2">Editor</option>
                                    <option value="3">Content Manager</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- SEO Settings -->
                    <div style="background: #f8f9fa; padding: 16px; border-radius: 8px; margin-bottom: 24px; border-left: 4px solid #ffc107;">
                        <h4 style="margin: 0 0 16px 0; color: #ffc107; display: flex; align-items: center; gap: 8px;">
                            <i class="fas fa-search"></i>
                            SEO AyarlarÃ„Â±
                        </h4>
                        
                        <div class="form-group">
                            <label for="metaKeywords"><i class="fas fa-tag mr-1"></i>Meta Anahtar Kelimeler</label>
                            <label for="metaKeywords" class="sr-only">Meta keywords</label><input type="text" name="meta_keywords" id="metaKeywords" placeholder="otopark, araÃƒÂ§ yÃ„Â±kama, temizlik, bakÃ„Â±m">
                            <small style="color: #64748b;">VirgÃƒÂ¼lle ayrÃ„Â±lmÃ„Â±Ã…ÂŸ anahtar kelimeler</small>
                        </div>
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                            <div class="form-group">
                                <label for="robotsMeta"><i class="fas fa-robot mr-1"></i>Robots Meta Tag</label>
                                <label for="robotsMeta" class="sr-only">Robots meta</label><select name="robots_meta" id="robotsMeta">
                                    <option value="index,follow" selected>Index, Follow (ÃƒÂ–nerilen)</option>
                                    <option value="noindex,follow">No Index, Follow</option>
                                    <option value="index,nofollow">Index, No Follow</option>
                                    <option value="noindex,nofollow">No Index, No Follow</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="ogImage"><i class="fas fa-share-alt mr-1"></i>Open Graph GÃƒÂ¶rseli</label>
                                <label for="ogImage" class="sr-only">Og image</label><input type="url" name="og_image" id="ogImage" placeholder="https://example.com/og-image.jpg">
                                <small style="color: #64748b;">Sosyal medya paylaÃ…ÂŸÃ„Â±m gÃƒÂ¶rseli</small>
                            </div>
                        </div>
                    </div>

                    <!-- Advanced Settings -->
                    <div style="background: #f8f9fa; padding: 16px; border-radius: 8px; margin-bottom: 24px; border-left: 4px solid #17a2b8;">
                        <h4 style="margin: 0 0 16px 0; color: #17a2b8; display: flex; align-items: center; gap: 8px;">
                            <i class="fas fa-sliders-h"></i>
                            GeliÃ…ÂŸmiÃ…ÂŸ Ayarlar
                        </h4>
                        
                        <div class="form-group">
                            <label for="customCss"><i class="fas fa-code mr-1"></i>ÃƒÂ–zel CSS</label>
                            <label for="customCss" class="sr-only">Custom css</label><textarea name="custom_css" id="customCss" rows="4" placeholder=".my-class { color: blue; }"></textarea>
                            <small style="color: #64748b;">Bu sayfa iÃƒÂ§in ÃƒÂ¶zel CSS kodlarÃ„Â±</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="customJs"><i class="fas fa-file-code mr-1"></i>ÃƒÂ–zel JavaScript</label>
                            <label for="customJs" class="sr-only">Custom js</label><textarea name="custom_js" id="customJs" rows="4" placeholder="console.log('Page loaded');"></textarea>
                            <small style="color: #64748b;">Bu sayfa iÃƒÂ§in ÃƒÂ¶zel JavaScript kodlarÃ„Â±</small>
                        </div>
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px; margin-top: 16px;">
                            <div class="form-group">
                                <label style="display: flex; align-items: center; cursor: pointer;">
                                    <label for="showInMenu" class="sr-only">Show in menu</label><input type="checkbox" name="show_in_menu" id="showInMenu" checked style="margin-right: 8px;">
                                    <i class="fas fa-bars mr-1"></i>
                                    MenÃƒÂ¼de GÃƒÂ¶ster
                                </label>
                            </div>
                            
                            <div class="form-group">
                                <label style="display: flex; align-items: center; cursor: pointer;">
                                    <label for="showInFooter" class="sr-only">Show in footer</label><input type="checkbox" name="show_in_footer" id="showInFooter" style="margin-right: 8px;">
                                    <i class="fas fa-shoe-prints mr-1"></i>
                                    Footer'da GÃƒÂ¶ster
                                </label>
                            </div>
                            
                            <div class="form-group">
                                <label style="display: flex; align-items: center; cursor: pointer;">
                                    <label for="requireAuth" class="sr-only">Require auth</label><input type="checkbox" name="require_auth" id="requireAuth" style="margin-right: 8px;">
                                    <i class="fas fa-lock mr-1"></i>
                                    GiriÃ…ÂŸ Gerekli
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Submit Buttons -->
                    <div style="display: flex; gap: 12px; justify-content: flex-end;">
                        <button type="button" class="report-btn" onclick="document.getElementById('cmsPageModal').style.display='none'" style="background: #6c757d; padding: 12px 24px;">
                            <i class="fas fa-times"></i>
                            Ã„Â°ptal
                        </button>
                        <button type="submit" class="submit-btn" style="background: linear-gradient(135deg, #764ba2, #667eea); display: flex; align-items: center; gap: 8px;">
                            <i class="fas fa-save"></i>
                            SayfayÃ„Â± Kaydet
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Toast & Confirm helpers (non-blocking)
        (function(){
            function createToastContainer(){
                window.__toastContainer = document.getElementById('toastContainer');
                if(!window.__toastContainer){
                    const c = document.createElement('div');
                    c.id = 'toastContainer';
                    document.body.appendChild(c);
                    window.__toastContainer = c;
                }
            }

            // requestIdleCallback polyfill (lightweight)
            window.requestIdleCallback = window.requestIdleCallback || function(cb){ return setTimeout(cb, 16); };

            function showToast(message, type = 'info', duration = 4000){
                try { createToastContainer(); } catch(e){}
                const el = document.createElement('div');
                el.className = 'toast ' + (type || 'info');
                el.setAttribute('role','alert');

                const icon = document.createElement('span');
                icon.className = 'toast-icon';
                icon.textContent = (type==='success'? 'âœ“' : (type==='error'? 'âš ' : 'â„¹'));

                const body = document.createElement('div');
                body.style.flex = '1';
                body.style.marginLeft = '6px';
                body.textContent = message;

                const closeBtn = document.createElement('button');
                closeBtn.className = 'toast-close';
                closeBtn.setAttribute('aria-label','Close notification');
                closeBtn.innerHTML = '&times;';
                closeBtn.addEventListener('click', () => hide());

                el.appendChild(icon);
                el.appendChild(body);
                el.appendChild(closeBtn);

                window.__toastContainer.appendChild(el);
                // animate in
                requestAnimationFrame(()=> el.classList.add('show'));
                const hide = ()=>{
                    el.classList.remove('show');
                    setTimeout(()=> { try { el.remove(); } catch(e){} }, 260);
                };
                if(duration>0) setTimeout(hide, duration);
                return {
                    dismiss: hide
                };
            }

            function showConfirm(message, title){
                return new Promise((resolve)=>{
                    const backdrop = document.getElementById('confirmBackdrop');
                    const dialog = document.getElementById('confirmDialog');
                    const titleEl = document.getElementById('confirmTitle');
                    const bodyEl = document.getElementById('confirmBody');
                    const okBtn = document.getElementById('confirmOkBtn');
                    const cancelBtn = document.getElementById('confirmCancelBtn');

                    if(!dialog || !backdrop){
                        // fallback to native confirm if modal not present
                        resolve(window.confirm(message));
                        return;
                    }

                    // Set accessible attributes and content
                    titleEl.textContent = title || 'Onay';
                    bodyEl.textContent = message || '';
                    dialog.setAttribute('role','dialog');
                    dialog.setAttribute('aria-modal','true');
                    dialog.setAttribute('aria-labelledby','confirmTitle');
                    dialog.setAttribute('aria-describedby','confirmBody');

                    const previouslyFocused = document.activeElement;

                    // show
                    backdrop.style.display = 'block';
                    dialog.style.display = 'block';
                    dialog.setAttribute('aria-hidden','false');

                    // collect all focusable elements inside the dialog
                    const focusableSelector = 'a[href], area[href], input:not([disabled]):not([type="hidden"]), select:not([disabled]), textarea:not([disabled]), button:not([disabled]), iframe, [tabindex]:not([tabindex="-1"])';
                    let focusable = Array.prototype.slice.call(dialog.querySelectorAll(focusableSelector));
                    // ensure OK and Cancel are present and ordered
                    if (!focusable.includes(okBtn)) focusable.unshift(okBtn);
                    if (!focusable.includes(cancelBtn)) focusable.push(cancelBtn);

                    // focus primary action (OK)
                    try { okBtn.focus(); } catch(e){}

                    function trapFocus(e){
                        if (e.key === 'Tab'){
                            if (focusable.length === 0) { e.preventDefault(); return; }
                            const idx = focusable.indexOf(document.activeElement);
                            let next = 0;
                            if (e.shiftKey){
                                next = (idx <= 0) ? focusable.length - 1 : idx - 1;
                            } else {
                                next = (idx === -1 || idx === focusable.length - 1) ? 0 : idx + 1;
                            }
                            focusable[next].focus();
                            e.preventDefault();
                        } else if (e.key === 'Escape'){
                            onCancel();
                        }
                    }

                    // If focus somehow moves outside, bring it back
                    function enforceFocus(e){
                        if (!dialog.contains(e.target)){
                            // move focus to first focusable
                            (focusable[0] || dialog).focus();
                        }
                    }

                    function cleanup(){
                        backdrop.style.display = 'none';
                        dialog.style.display = 'none';
                        dialog.setAttribute('aria-hidden','true');
                        okBtn.removeEventListener('click', onOk);
                        cancelBtn.removeEventListener('click', onCancel);
                        dialog.removeEventListener('keydown', trapFocus);
                        document.removeEventListener('focusin', enforceFocus);
                        backdrop.removeEventListener('click', onCancel);
                        // restore focus
                        try { if (previouslyFocused && previouslyFocused.focus) previouslyFocused.focus(); } catch(e){}
                    }

                    function onOk(){ cleanup(); resolve(true); }
                    function onCancel(){ cleanup(); resolve(false); }

                    okBtn.addEventListener('click', onOk);
                    cancelBtn.addEventListener('click', onCancel);
                    backdrop.addEventListener('click', onCancel);
                    dialog.addEventListener('keydown', trapFocus);
                    document.addEventListener('focusin', enforceFocus);
                });
            }

            window.showToast = showToast;
            window.showConfirm = showConfirm;
        })();

        // Mobile Menu Toggle Functions
        // FarsÃƒÂ§a: Ã˜ÂªÃ™ÂˆÃ˜Â§Ã˜Â¨Ã˜Â¹ Ã˜ÂªÃ˜ÂºÃ›ÂŒÃ›ÂŒÃ˜Â± Ã™Â…Ã™Â†Ã™ÂˆÃ›ÂŒ Ã™Â…Ã™ÂˆÃ˜Â¨Ã˜Â§Ã›ÂŒÃ™Â„.
        // TÃƒÂ¼rkÃƒÂ§e: Mobil MenÃƒÂ¼ GeÃƒÂ§iÃ…ÂŸ FonksiyonlarÃ„Â±.
        // English: Mobile Menu Toggle Functions.
        function toggleMobileMenu() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('mobileOverlay');
            const menuIcon = document.getElementById('menuIcon');
            const toggleBtn = document.getElementById('mobileMenuToggle');
            
            if (sidebar.classList.contains('mobile-visible')) {
                closeMobileMenu();
            } else {
                sidebar.classList.remove('mobile-hidden');
                sidebar.classList.add('mobile-visible');
                overlay.classList.add('active');
                menuIcon.classList.remove('fa-bars');
                menuIcon.classList.add('fa-times');
                toggleBtn.classList.add('active');
            }
        }
        
        function closeMobileMenu() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('mobileOverlay');
            const menuIcon = document.getElementById('menuIcon');
            const toggleBtn = document.getElementById('mobileMenuToggle');
            
            sidebar.classList.remove('mobile-visible');
            sidebar.classList.add('mobile-hidden');
            overlay.classList.remove('active');
            menuIcon.classList.remove('fa-times');
            menuIcon.classList.add('fa-bars');
            toggleBtn.classList.remove('active');
        }
        
        // Show/hide mobile menu button based on screen size
        function checkScreenSize() {
            const toggleBtn = document.getElementById('mobileMenuToggle');
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('mobileOverlay');
            
            if (window.innerWidth < 1024) {
                // Mobile/Tablet: Show FAB, hide sidebar by default
                toggleBtn.style.display = 'flex';
                toggleBtn.style.alignItems = 'center';
                toggleBtn.style.justifyContent = 'center';
                
                // Only add mobile-hidden if not already visible
                if (!sidebar.classList.contains('mobile-visible')) {
                    sidebar.classList.add('mobile-hidden');
                }
            } else {
                // Desktop: Hide FAB, always show sidebar
                toggleBtn.style.display = 'none';
                sidebar.classList.remove('mobile-hidden');
                sidebar.classList.remove('mobile-visible');
                overlay.classList.remove('active');
            }
        }
        
        // Run on load and resize
        window.addEventListener('load', checkScreenSize);
        window.addEventListener('resize', checkScreenSize);
        
        // Navigation functionality
        // FarsÃƒÂ§a: Ã˜Â¹Ã™Â…Ã™Â„ÃšÂ©Ã˜Â±Ã˜Â¯ Ã™Â†Ã˜Â§Ã™ÂˆÃ˜Â¨Ã˜Â±Ã›ÂŒ.
        // TÃƒÂ¼rkÃƒÂ§e: Navigasyon iÃ…ÂŸlevselliÃ„ÂŸi.
        // English: Navigation functionality.
        // Defer attaching non-critical nav handlers to idle time to improve initial responsiveness
        requestIdleCallback(function(){
            document.querySelectorAll('.nav-link').forEach(link => {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    // Remove active class from all nav items and sections
                    document.querySelectorAll('.nav-item').forEach(item => item.classList.remove('active'));
                    document.querySelectorAll('.content-section').forEach(section => section.classList.remove('active'));
                    
                    // Add active class to clicked nav item
                    this.parentElement.classList.add('active');
                    
                    // Show corresponding section
                    const sectionId = this.getAttribute('data-section');
                    const sec = document.getElementById(sectionId);
                    if (sec) sec.classList.add('active');
                    
                    // Close mobile menu after selection
                    if (window.innerWidth < 1024) {
                        closeMobileMenu();
                    }
                });
            });
        }, { timeout: 500 });

        // Modal functionality
        // FarsÃƒÂ§a: Ã˜Â¹Ã™Â…Ã™Â„ÃšÂ©Ã˜Â±Ã˜Â¯ Ã™Â…Ã™ÂˆÃ˜Â¯Ã˜Â§Ã™Â„.
        // TÃƒÂ¼rkÃƒÂ§e: Modal iÃ…ÂŸlevselliÃ„ÂŸi.
        // English: Modal functionality.
        const carwashModal = document.getElementById('carwashModal');
        const addCarwashBtn = document.getElementById('addCarwashBtn');
        const closeCarwashModal = document.getElementById('closeCarwashModal');

        addCarwashBtn.addEventListener('click', () => {
            carwashModal.style.display = 'block';
        });

        closeCarwashModal.addEventListener('click', () => {
            carwashModal.style.display = 'none';
        });

        window.addEventListener('click', (e) => {
            if (e.target === carwashModal) {
                carwashModal.style.display = 'none';
            }
        });

        // Form submission
        // FarsÃƒÂ§a: Ã˜Â§Ã˜Â±Ã˜Â³Ã˜Â§Ã™Â„ Ã™ÂÃ˜Â±Ã™Â….
        // TÃƒÂ¼rkÃƒÂ§e: Form gÃƒÂ¶nderimi.
        // English: Form submission.
        document.getElementById('carwashForm').addEventListener('submit', function(e) {
            e.preventDefault();
            // Here you would typically send data to PHP backend
            // FarsÃƒÂ§a: Ã˜Â¯Ã˜Â± Ã˜Â§Ã›ÂŒÃ™Â†Ã˜Â¬Ã˜Â§ Ã˜Â´Ã™Â…Ã˜Â§ Ã™Â…Ã˜Â¹Ã™Â…Ã™ÂˆÃ™Â„Ã˜Â§Ã™Â‹ Ã˜Â¯Ã˜Â§Ã˜Â¯Ã™Â‡Ã¢Â€ÂŒÃ™Â‡Ã˜Â§ Ã˜Â±Ã˜Â§ Ã˜Â¨Ã™Â‡ Ã˜Â¨ÃšÂ©Ã¢Â€ÂŒÃ˜Â§Ã™Â†Ã˜Â¯ PHP Ã˜Â§Ã˜Â±Ã˜Â³Ã˜Â§Ã™Â„ Ã™Â…Ã›ÂŒÃ¢Â€ÂŒÃšÂ©Ã™Â†Ã›ÂŒÃ˜Â¯.
            // TÃƒÂ¼rkÃƒÂ§e: Burada tipik olarak verileri PHP arka ucuna gÃƒÂ¶nderirsiniz.
            // English: Here you would typically send data to PHP backend.
            showToast('Otopark baÃ…ÂŸarÃ„Â±yla eklendi!', 'success');
            carwashModal.style.display = 'none';
        });

        // User Modal Functions
        // FarsÃƒÂ§a: Ã˜ÂªÃ™ÂˆÃ˜Â§Ã˜Â¨Ã˜Â¹ Ã™Â…Ã™ÂˆÃ˜Â¯Ã˜Â§Ã™Â„ ÃšÂ©Ã˜Â§Ã˜Â±Ã˜Â¨Ã˜Â±.
        // TÃƒÂ¼rkÃƒÂ§e: KullanÃ„Â±cÃ„Â± Modal FonksiyonlarÃ„Â±.
        // English: User Modal Functions.
        const userModal = document.getElementById('userModal');
        const addUserBtn = document.getElementById('addUserBtn');
        const closeUserModal = document.getElementById('closeUserModal');

        if (addUserBtn) {
            addUserBtn.addEventListener('click', () => {
                userModal.style.display = 'block';
            });
        }

        if (closeUserModal) {
            closeUserModal.addEventListener('click', () => {
                userModal.style.display = 'none';
            });
        }

        window.addEventListener('click', (e) => {
            if (e.target === userModal) {
                userModal.style.display = 'none';
            }
        });

        // CMS Page Modal Functions
        // FarsÃƒÂ§a: Ã˜ÂªÃ™ÂˆÃ˜Â§Ã˜Â¨Ã˜Â¹ Ã™Â…Ã™ÂˆÃ˜Â¯Ã˜Â§Ã™Â„ Ã˜ÂµÃ™ÂÃ˜Â­Ã™Â‡ CMS.
        // TÃƒÂ¼rkÃƒÂ§e: CMS SayfasÃ„Â± Modal FonksiyonlarÃ„Â±.
        // English: CMS Page Modal Functions.
        const cmsPageModal = document.getElementById('cmsPageModal');
        const addCmsPageBtn = document.getElementById('addCmsPageBtn');
        const closeCmsPageModal = document.getElementById('closeCmsPageModal');

        if (addCmsPageBtn) {
            addCmsPageBtn.addEventListener('click', () => {
                cmsPageModal.style.display = 'block';
            });
        }

        if (closeCmsPageModal) {
            closeCmsPageModal.addEventListener('click', () => {
                cmsPageModal.style.display = 'none';
            });
        }

        window.addEventListener('click', (e) => {
            if (e.target === cmsPageModal) {
                cmsPageModal.style.display = 'none';
            }
        });

        // Auto-generate URL slug from page title (Turkish character support)
        // FarsÃƒÂ§a: Ã˜ÂªÃ™ÂˆÃ™Â„Ã›ÂŒÃ˜Â¯ Ã˜Â®Ã™ÂˆÃ˜Â¯ÃšÂ©Ã˜Â§Ã˜Â± URL Ã˜Â§Ã˜Â² Ã˜Â¹Ã™Â†Ã™ÂˆÃ˜Â§Ã™Â† Ã˜ÂµÃ™ÂÃ˜Â­Ã™Â‡ (Ã™Â¾Ã˜Â´Ã˜ÂªÃ›ÂŒÃ˜Â¨Ã˜Â§Ã™Â†Ã›ÂŒ Ã˜Â§Ã˜Â² ÃšÂ©Ã˜Â§Ã˜Â±Ã˜Â§ÃšÂ©Ã˜ÂªÃ˜Â±Ã™Â‡Ã˜Â§Ã›ÂŒ Ã˜ÂªÃ˜Â±ÃšÂ©Ã›ÂŒ).
        // TÃƒÂ¼rkÃƒÂ§e: Sayfa baÃ…ÂŸlÃ„Â±Ã„ÂŸÃ„Â±ndan otomatik URL slug ÃƒÂ¼retimi (TÃƒÂ¼rkÃƒÂ§e karakter desteÃ„ÂŸi).
        // English: Auto-generate URL slug from page title (Turkish character support).
        const pageTitleInput = document.getElementById('pageTitle');
        const pageSlugInput = document.getElementById('pageSlug');

        if (pageTitleInput && pageSlugInput) {
            pageTitleInput.addEventListener('input', function() {
                let slug = this.value
                    .toLowerCase()
                    // Turkish character replacements
                    .replace(/Ã„ÂŸ/g, 'g')
                    .replace(/ÃƒÂ¼/g, 'u')
                    .replace(/Ã…ÂŸ/g, 's')
                    .replace(/Ã„Â±/g, 'i')
                    .replace(/ÃƒÂ¶/g, 'o')
                    .replace(/ÃƒÂ§/g, 'c')
                    // Replace spaces and special characters with hyphens
                    .replace(/[^a-z0-9]+/g, '-')
                    // Remove leading and trailing hyphens
                    .replace(/^-|-$/g, '');
                
                pageSlugInput.value = slug;
            });
        }

        // CMS Page Form Validation and Submission
        // FarsÃƒÂ§a: Ã˜Â§Ã˜Â¹Ã˜ÂªÃ˜Â¨Ã˜Â§Ã˜Â±Ã˜Â³Ã™Â†Ã˜Â¬Ã›ÂŒ Ã™Âˆ Ã˜Â§Ã˜Â±Ã˜Â³Ã˜Â§Ã™Â„ Ã™ÂÃ˜Â±Ã™Â… Ã˜ÂµÃ™ÂÃ˜Â­Ã™Â‡ CMS.
        // TÃƒÂ¼rkÃƒÂ§e: CMS SayfasÃ„Â± Form DoÃ„ÂŸrulama ve GÃƒÂ¶nderimi.
        // English: CMS Page Form Validation and Submission.
        const cmsPageForm = document.getElementById('cmsPageForm');

        if (cmsPageForm) {
            cmsPageForm.addEventListener('submit', function(e) {
                e.preventDefault();

                // Get form values
                const title = document.getElementById('pageTitle').value.trim();
                const slug = document.getElementById('pageSlug').value.trim();
                const content = document.getElementById('pageContent').value.trim();
                const category = document.getElementById('pageCategory').value;
                const status = document.getElementById('pageStatus').value;

                // Validation
                if (title.length < 3) {
                    showToast('Ã¢ÂÂŒ Hata!\n\nSayfa baÃ…ÂŸlÃ„Â±Ã„ÂŸÃ„Â± en az 3 karakter olmalÃ„Â±dÃ„Â±r.', 'error');
                    return;
                }

                if (title.length > 200) {
                    showToast('Ã¢ÂÂŒ Hata!\n\nSayfa baÃ…ÂŸlÃ„Â±Ã„ÂŸÃ„Â± maksimum 200 karakter olabilir.', 'error');
                    return;
                }

                if (!slug.match(/^[a-z0-9-]+$/)) {
                    showToast('Ã¢ÂÂŒ Hata!\n\nURL slug sadece kÃƒÂ¼ÃƒÂ§ÃƒÂ¼k harf, rakam ve tire (-) iÃƒÂ§erebilir.', 'error');
                    return;
                }

                if (slug.length < 3) {
                    showToast('Ã¢ÂÂŒ Hata!\n\nURL slug en az 3 karakter olmalÃ„Â±dÃ„Â±r.', 'error');
                    return;
                }

                if (content.length < 50) {
                    showToast('Ã¢ÂÂŒ Hata!\n\nSayfa iÃƒÂ§eriÃ„ÂŸi en az 50 karakter olmalÃ„Â±dÃ„Â±r.', 'error');
                    return;
                }

                if (!category) {
                    showToast('Ã¢ÂÂŒ Hata!\n\nLÃƒÂ¼tfen bir kategori seÃƒÂ§in.', 'error');
                    return;
                }

                // Get optional fields
                const description = document.getElementById('pageDescription').value;
                const language = document.getElementById('pageLanguage').value;

                // Success message (TODO: Replace with actual backend API call)
                    showToast('Ã¢ÂœÂ… BaÃ…ÂŸarÃ„Â±lÃ„Â±!\n\n' +
                        'Ã¢Â”ÂÃ¢Â”ÂÃ¢Â”ÂÃ¢Â”ÂÃ¢Â”ÂÃ¢Â”ÂÃ¢Â”ÂÃ¢Â”ÂÃ¢Â”ÂÃ¢Â”ÂÃ¢Â”ÂÃ¢Â”ÂÃ¢Â”ÂÃ¢Â”ÂÃ¢Â”ÂÃ¢Â”ÂÃ¢Â”ÂÃ¢Â”ÂÃ¢Â”ÂÃ¢Â”ÂÃ¢Â”ÂÃ¢Â”ÂÃ¢Â”ÂÃ¢Â”ÂÃ¢Â”ÂÃ¢Â”ÂÃ¢Â”ÂÃ¢Â”ÂÃ¢Â”ÂÃ¢Â”Â\n' +
                        'Ã°ÂŸÂ“Â„ Sayfa BaÃ…ÂŸlÃ„Â±Ã„ÂŸÃ„Â±: ' + title + '\n' +
                        'Ã°ÂŸÂ”Â— URL Slug: ' + slug + '\n' +
                        'Ã°ÂŸÂ“Â Kategori: ' + getCategoryName(category) + '\n' +
                        'Ã°ÂŸÂ‘ÂÃ¯Â¸Â Durum: ' + getStatusName(status) + '\n' +
                        'Ã°ÂŸÂŒÂ Dil: ' + language.toUpperCase() + '\n' +
                        'Ã°ÂŸÂ“Â Ã„Â°ÃƒÂ§erik UzunluÃ„ÂŸu: ' + content.length + ' karakter\n' +
                        'Ã¢Â”ÂÃ¢Â”ÂÃ¢Â”ÂÃ¢Â”ÂÃ¢Â”ÂÃ¢Â”ÂÃ¢Â”ÂÃ¢Â”ÂÃ¢Â”ÂÃ¢Â”ÂÃ¢Â”ÂÃ¢Â”ÂÃ¢Â”ÂÃ¢Â”ÂÃ¢Â”ÂÃ¢Â”ÂÃ¢Â”ÂÃ¢Â”ÂÃ¢Â”ÂÃ¢Â”ÂÃ¢Â”ÂÃ¢Â”ÂÃ¢Â”ÂÃ¢Â”ÂÃ¢Â”ÂÃ¢Â”ÂÃ¢Â”ÂÃ¢Â”ÂÃ¢Â”ÂÃ¢Â”Â\n\n' +
                        'Sayfa baÃ…ÂŸarÃ„Â±yla oluÃ…ÂŸturuldu!', 'success', 7000);

                // TODO: Backend Integration
                // const formData = new FormData(this);
                // fetch('/backend/api/cms/create_page.php', {
                //     method: 'POST',
                //     body: formData
                // })
                // .then(response => response.json())
                // .then(data => {
                //     if (data.success) {
                //         showToast('Ã¢ÂœÂ… Sayfa baÃ…ÂŸarÃ„Â±yla oluÃ…ÂŸturuldu!', 'success');
                //         cmsPageModal.style.display = 'none';
                //         this.reset();
                //         // Refresh the page list
                //         location.reload();
                //     } else {
                //         showToast('Ã¢ÂÂŒ Hata: ' + data.message, 'error');
                //     }
                // })
                // .catch(error => {
                //     showToast('Ã¢ÂÂŒ Bir hata oluÃ…ÂŸtu: ' + error.message, 'error');
                // });

                // Close modal and reset form
                cmsPageModal.style.display = 'none';
                this.reset();
            });
        }

        // Helper function to get category name
        function getCategoryName(value) {
            const categories = {
                'about': 'HakkÃ„Â±mÃ„Â±zda',
                'services': 'Hizmetler',
                'contact': 'Ã„Â°letiÃ…ÂŸim',
                'help': 'YardÃ„Â±m & SSS',
                'legal': 'Yasal',
                'blog': 'Blog',
                'other': 'DiÃ„ÂŸer'
            };
            return categories[value] || value;
        }

        // Helper function to get status name
        function getStatusName(value) {
            const statuses = {
                'draft': 'Taslak',
                'published': 'YayÃ„Â±nda',
                'archived': 'ArÃ…ÂŸivlendi'
            };
            return statuses[value] || value;
        }

        // Service Modal Functions
        // FarsÃƒÂ§a: Ã˜ÂªÃ™ÂˆÃ˜Â§Ã˜Â¨Ã˜Â¹ Ã™Â…Ã™ÂˆÃ˜Â¯Ã˜Â§Ã™Â„ Ã˜Â®Ã˜Â¯Ã™Â…Ã˜Â§Ã˜Âª.
        // TÃƒÂ¼rkÃƒÂ§e: Hizmet Modal FonksiyonlarÃ„Â±.
        // English: Service Modal Functions.
        const serviceModal = document.getElementById('serviceModal');
        const addServiceBtn = document.getElementById('addServiceBtn');
        const closeServiceModal = document.getElementById('closeServiceModal');

        if (addServiceBtn) {
            addServiceBtn.addEventListener('click', () => {
                serviceModal.style.display = 'block';
            });
        }

        if (closeServiceModal) {
            closeServiceModal.addEventListener('click', () => {
                serviceModal.style.display = 'none';
            });
        }

        window.addEventListener('click', (e) => {
            if (e.target === serviceModal) {
                serviceModal.style.display = 'none';
            }
        });

        // Ticket Modal Functions
        // FarsÃƒÂ§a: Ã˜ÂªÃ™ÂˆÃ˜Â§Ã˜Â¨Ã˜Â¹ Ã™Â…Ã™ÂˆÃ˜Â¯Ã˜Â§Ã™Â„ Ã˜ÂªÃ›ÂŒÃšÂ©Ã˜Âª.
        // TÃƒÂ¼rkÃƒÂ§e: Destek Talebi Modal FonksiyonlarÃ„Â±.
        // English: Ticket Modal Functions.
        const ticketModal = document.getElementById('ticketModal');
        const addTicketBtn = document.getElementById('addTicketBtn');
        const closeTicketModal = document.getElementById('closeTicketModal');

        if (addTicketBtn) {
            addTicketBtn.addEventListener('click', () => {
                ticketModal.style.display = 'block';
            });
        }

        if (closeTicketModal) {
            closeTicketModal.addEventListener('click', () => {
                ticketModal.style.display = 'none';
            });
        }

        window.addEventListener('click', (e) => {
            if (e.target === ticketModal) {
                ticketModal.style.display = 'none';
            }
        });

        // Ticket Form Submission with Validation
        // FarsÃƒÂ§a: Ã˜Â§Ã˜Â±Ã˜Â³Ã˜Â§Ã™Â„ Ã™ÂÃ˜Â±Ã™Â… Ã˜ÂªÃ›ÂŒÃšÂ©Ã˜Âª Ã˜Â¨Ã˜Â§ Ã˜Â§Ã˜Â¹Ã˜ÂªÃ˜Â¨Ã˜Â§Ã˜Â±Ã˜Â³Ã™Â†Ã˜Â¬Ã›ÂŒ.
        // TÃƒÂ¼rkÃƒÂ§e: DoÃ„ÂŸrulama ile Destek Talebi Formu GÃƒÂ¶nderimi.
        // English: Ticket Form Submission with Validation.
        document.getElementById('ticketForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Get form values
            const formData = new FormData(this);
            const customerId = formData.get('customer_id');
            const subject = formData.get('subject');
            const category = formData.get('category');
            const priority = formData.get('priority');
            const message = formData.get('message');
            
            // Validation
            if (!customerId) {
                showToast('Ã¢ÂÂŒ LÃƒÂ¼tfen bir mÃƒÂ¼Ã…ÂŸteri seÃƒÂ§in!', 'error');
                return;
            }
            
            if (!subject || subject.length < 5) {
                showToast('Ã¢ÂÂŒ Konu en az 5 karakter olmalÃ„Â±dÃ„Â±r!', 'error');
                return;
            }
            
            if (!category) {
                showToast('Ã¢ÂÂŒ LÃƒÂ¼tfen bir kategori seÃƒÂ§in!', 'error');
                return;
            }
            
            if (!priority) {
                showToast('Ã¢ÂÂŒ LÃƒÂ¼tfen bir ÃƒÂ¶ncelik seviyesi seÃƒÂ§in!', 'error');
                return;
            }
            
            if (!message || message.length < 10) {
                showToast('Ã¢ÂÂŒ Mesaj en az 10 karakter olmalÃ„Â±dÃ„Â±r!', 'error');
                return;
            }
            
            // Prepare data for backend
            const ticketData = {
                customer_id: customerId,
                subject: subject,
                category: category,
                priority: priority,
                message: message,
                assigned_to: formData.get('assigned_to') || null,
                status: formData.get('status') || 'new',
                attachment: formData.get('attachment')
            };
            
            // TODO: Send to backend API
            // fetch('/backend/api/admin/tickets/create', {
            //     method: 'POST',
            //     headers: { 'Content-Type': 'application/json' },
            //     body: JSON.stringify(ticketData)
            // })
            // .then(response => response.json())
            // .then(data => {
            //     if (data.success) {
            //         showToast('Ã¢ÂœÂ… Destek talebi baÃ…ÂŸarÃ„Â±yla oluÃ…ÂŸturuldu!', 'success');
            //         ticketModal.style.display = 'none';
            //         this.reset();
            //         // Reload tickets table
            //         location.reload();
            //     } else {
            //         showToast('Ã¢ÂÂŒ Hata: ' + data.message, 'error');
            //     }
            // })
            // .catch(error => {
            //     showToast('Ã¢ÂÂŒ Bir hata oluÃ…ÂŸtu: ' + error.message, 'error');
            // });
            
            // For now, just show success message
            console.log('Creating ticket:', ticketData);
            showToast('Ã¢ÂœÂ… Destek talebi baÃ…ÂŸarÃ„Â±yla oluÃ…ÂŸturuldu!\n\n' +
                'Konu: ' + subject + '\n' +
                'Kategori: ' + category + '\n' +
                'ÃƒÂ–ncelik: ' + priority, 'success', 5000);
            ticketModal.style.display = 'none';
            this.reset();
        });

        // Service Form Submission with Validation
        // FarsÃƒÂ§a: Ã˜Â§Ã˜Â±Ã˜Â³Ã˜Â§Ã™Â„ Ã™ÂÃ˜Â±Ã™Â… Ã˜Â®Ã˜Â¯Ã™Â…Ã˜Â§Ã˜Âª Ã˜Â¨Ã˜Â§ Ã˜Â§Ã˜Â¹Ã˜ÂªÃ˜Â¨Ã˜Â§Ã˜Â±Ã˜Â³Ã™Â†Ã˜Â¬Ã›ÂŒ.
        // TÃƒÂ¼rkÃƒÂ§e: DoÃ„ÂŸrulama ile Hizmet Formu GÃƒÂ¶nderimi.
        // English: Service Form Submission with Validation.
        document.getElementById('serviceForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Get form values
            const formData = new FormData(this);
            const serviceName = formData.get('service_name');
            const category = formData.get('category');
            const duration = formData.get('duration');
            const priceSedan = formData.get('price_sedan');
            const priceSUV = formData.get('price_suv');
            const priceTruck = formData.get('price_truck');
            const status = formData.get('status');
            
            // Validation
            if (!serviceName || serviceName.length < 3) {
                showToast('Ã¢ÂÂŒ Hizmet adÃ„Â± en az 3 karakter olmalÃ„Â±dÃ„Â±r!', 'error');
                return;
            }
            
            if (!category) {
                showToast('Ã¢ÂÂŒ LÃƒÂ¼tfen bir kategori seÃƒÂ§in!', 'error');
                return;
            }
            
            if (!duration || duration < 1) {
                showToast('Ã¢ÂÂŒ Hizmet sÃƒÂ¼resi en az 1 dakika olmalÃ„Â±dÃ„Â±r!', 'error');
                return;
            }
            
            if (!priceSedan || priceSedan <= 0) {
                showToast('Ã¢ÂÂŒ Sedan fiyatÃ„Â± geÃƒÂ§erli bir deÃ„ÂŸer olmalÃ„Â±dÃ„Â±r!', 'error');
                return;
            }
            
            if (!priceSUV || priceSUV <= 0) {
                showToast('Ã¢ÂÂŒ SUV fiyatÃ„Â± geÃƒÂ§erli bir deÃ„ÂŸer olmalÃ„Â±dÃ„Â±r!', 'error');
                return;
            }
            
            if (!priceTruck || priceTruck <= 0) {
                showToast('Ã¢ÂÂŒ Kamyonet fiyatÃ„Â± geÃƒÂ§erli bir deÃ„ÂŸer olmalÃ„Â±dÃ„Â±r!', 'error');
                return;
            }
            
            // Prepare data for backend
            const serviceData = {
                name: serviceName,
                description: formData.get('description'),
                category: category,
                duration: parseInt(duration),
                pricing: {
                    sedan: parseFloat(priceSedan),
                    suv: parseFloat(priceSUV),
                    truck: parseFloat(priceTruck)
                },
                sort_order: formData.get('sort_order') || 1,
                status: status,
                icon: formData.get('icon') || 'fas fa-car'
            };
            
            // TODO: Send to backend API
            // fetch('/backend/api/admin/services/create', {
            //     method: 'POST',
            //     headers: { 'Content-Type': 'application/json' },
            //     body: JSON.stringify(serviceData)
            // })
            // .then(response => response.json())
            // .then(data => {
            //     if (data.success) {
            //         showToast('Ã¢ÂœÂ… Hizmet baÃ…ÂŸarÃ„Â±yla oluÃ…ÂŸturuldu!', 'success');
            //         serviceModal.style.display = 'none';
            //         this.reset();
            //         // Reload services table
            //         location.reload();
            //     } else {
            //         showToast('Ã¢ÂÂŒ Hata: ' + data.message, 'error');
            //     }
            // })
            // .catch(error => {
            //     showToast('Ã¢ÂÂŒ Bir hata oluÃ…ÂŸtu: ' + error.message, 'error');
            // });
            
            // For now, just show success message
            console.log('Creating service:', serviceData);
            showToast('Ã¢ÂœÂ… Hizmet baÃ…ÂŸarÃ„Â±yla oluÃ…ÂŸturuldu!\n\n' +
                'Hizmet: ' + serviceName + '\n' +
                'Kategori: ' + category + '\n' +
                'SÃƒÂ¼re: ' + duration + ' dk\n' +
                'Sedan: Ã¢Â‚Âº' + priceSedan + '\n' +
                'SUV: Ã¢Â‚Âº' + priceSUV + '\n' +
                'Kamyonet: Ã¢Â‚Âº' + priceTruck, 'success', 6000);
            serviceModal.style.display = 'none';
            this.reset();
        });

        // User Form Submission with Validation
        // FarsÃƒÂ§a: Ã˜Â§Ã˜Â±Ã˜Â³Ã˜Â§Ã™Â„ Ã™ÂÃ˜Â±Ã™Â… ÃšÂ©Ã˜Â§Ã˜Â±Ã˜Â¨Ã˜Â± Ã˜Â¨Ã˜Â§ Ã˜Â§Ã˜Â¹Ã˜ÂªÃ˜Â¨Ã˜Â§Ã˜Â±Ã˜Â³Ã™Â†Ã˜Â¬Ã›ÂŒ.
        // TÃƒÂ¼rkÃƒÂ§e: DoÃ„ÂŸrulama ile KullanÃ„Â±cÃ„Â± Formu GÃƒÂ¶nderimi.
        // English: User Form Submission with Validation.
        document.getElementById('userForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Get form values
            const formData = new FormData(this);
            const password = formData.get('password');
            const passwordConfirm = formData.get('password_confirm');
            const email = formData.get('email');
            const username = formData.get('username');
            const roleId = formData.get('role_id');
            
            // Validation
            if (!username || username.length < 3) {
                showToast('KullanÃ„Â±cÃ„Â± adÃ„Â± en az 3 karakter olmalÃ„Â±dÃ„Â±r!', 'error');
                return;
            }
            
            if (!email || !email.includes('@')) {
                showToast('GeÃƒÂ§erli bir email adresi girin!', 'error');
                return;
            }
            
            if (!password || password.length < 8) {
                showToast('Ã…Âžifre en az 8 karakter olmalÃ„Â±dÃ„Â±r!', 'error');
                return;
            }
            
            if (password !== passwordConfirm) {
                showToast('Ã…Âžifreler eÃ…ÂŸleÃ…ÂŸmiyor!', 'error');
                return;
            }
            
            if (!roleId) {
                showToast('LÃƒÂ¼tfen bir rol seÃƒÂ§in!', 'error');
                return;
            }
            
            // Password strength check
            const hasUpperCase = /[A-Z]/.test(password);
            const hasLowerCase = /[a-z]/.test(password);
            const hasNumbers = /\d/.test(password);
            
            if (!hasUpperCase || !hasLowerCase || !hasNumbers) {
                showToast('Ã…Âžifre en az bir bÃƒÂ¼yÃƒÂ¼k harf, bir kÃƒÂ¼ÃƒÂ§ÃƒÂ¼k harf ve bir rakam iÃƒÂ§ermelidir!', 'error');
                return;
            }
            
            // Prepare data for backend
            const userData = {
                username: formData.get('username'),
                email: formData.get('email'),
                password: formData.get('password'),
                full_name: formData.get('full_name'),
                phone: formData.get('phone'),
                role_id: formData.get('role_id'),
                status: formData.get('status'),
                require_2fa: formData.get('require_2fa') ? 1 : 0,
                email_verified: formData.get('email_verified') ? 1 : 0
            };
            
            // TODO: Send to backend API
            // fetch('/backend/api/admin/users/create', {
            //     method: 'POST',
            //     headers: { 'Content-Type': 'application/json' },
            //     body: JSON.stringify(userData)
            // })
            // .then(response => response.json())
            // .then(data => {
            //     if (data.success) {
            //         showToast('KullanÃ„Â±cÃ„Â± baÃ…ÂŸarÃ„Â±yla oluÃ…ÂŸturuldu!', 'success');
            //         userModal.style.display = 'none';
            //         this.reset();
            //         // Reload user table
            //         location.reload();
            //     } else {
            //         showToast('Hata: ' + data.message, 'error');
            //     }
            // })
            // .catch(error => {
            //     showToast('Bir hata oluÃ…ÂŸtu: ' + error.message, 'error');
            // });
            
            // For now, just show success message
            console.log('Creating user:', userData);
            showToast('KullanÃ„Â±cÃ„Â± baÃ…ÂŸarÃ„Â±yla oluÃ…ÂŸturuldu!', 'success', 4500);
            userModal.style.display = 'none';
            this.reset();
        });

        // Search and filter functionality (basic implementation)
        // FarsÃƒÂ§a: Ã˜Â¹Ã™Â…Ã™Â„ÃšÂ©Ã˜Â±Ã˜Â¯ Ã˜Â¬Ã˜Â³Ã˜ÂªÃ˜Â¬Ã™Âˆ Ã™Âˆ Ã™ÂÃ›ÂŒÃ™Â„Ã˜ÂªÃ˜Â± (Ã™Â¾Ã›ÂŒÃ˜Â§Ã˜Â¯Ã™Â‡Ã¢Â€ÂŒÃ˜Â³Ã˜Â§Ã˜Â²Ã›ÂŒ Ã™Â¾Ã˜Â§Ã›ÂŒÃ™Â‡).
        // TÃƒÂ¼rkÃƒÂ§e: Arama ve filtreleme iÃ…ÂŸlevselliÃ„ÂŸi (temel uygulama).
        // English: Search and filter functionality (basic implementation).
        document.getElementById('carwashSearch').addEventListener('input', function() {
            // Implement search functionality
            // FarsÃƒÂ§a: Ã˜Â¹Ã™Â…Ã™Â„ÃšÂ©Ã˜Â±Ã˜Â¯ Ã˜Â¬Ã˜Â³Ã˜ÂªÃ˜Â¬Ã™Âˆ Ã˜Â±Ã˜Â§ Ã™Â¾Ã›ÂŒÃ˜Â§Ã˜Â¯Ã™Â‡Ã¢Â€ÂŒÃ˜Â³Ã˜Â§Ã˜Â²Ã›ÂŒ ÃšÂ©Ã™Â†Ã›ÂŒÃ˜Â¯.
            // TÃƒÂ¼rkÃƒÂ§e: Arama iÃ…ÂŸlevselliÃ„ÂŸini uygulayÃ„Â±n.
            // English: Implement search functionality.
            console.log('Searching for:', this.value);
        });

        // Service Management Functions
        // FarsÃƒÂ§a: Ã˜ÂªÃ™ÂˆÃ˜Â§Ã˜Â¨Ã˜Â¹ Ã™Â…Ã˜Â¯Ã›ÂŒÃ˜Â±Ã›ÂŒÃ˜Âª Ã˜Â®Ã˜Â¯Ã™Â…Ã˜Â§Ã˜Âª.
        // TÃƒÂ¼rkÃƒÂ§e: Hizmet YÃƒÂ¶netimi FonksiyonlarÃ„Â±.
        // English: Service Management Functions.
        
        function editService(serviceId) {
            // TODO: Load service data and populate modal
            console.log('Editing service:', serviceId);
            showToast('Ã°ÂŸÂ”Â§ Hizmet dÃƒÂ¼zenleme ÃƒÂ¶zelliÃ„ÂŸi yakÃ„Â±nda eklenecek!\n\nService ID: ' + serviceId, 'info');
            
            // Future implementation:
            // fetch('/backend/api/admin/services/' + serviceId)
            // .then(response => response.json())
            // .then(data => {
            //     // Populate form with service data
            //     document.getElementById('serviceName').value = data.name;
            //     document.getElementById('serviceDescription').value = data.description;
            //     // ... populate other fields
            //     serviceModal.style.display = 'block';
            // });
        }
        
        function toggleServiceStatus(serviceId) {
            showConfirm('Bu hizmetin durumunu deÃ„ÂŸiÃ…ÂŸtirmek istediÃ„ÂŸinizden emin misiniz?').then(function(confirmed){
                if (!confirmed) return;
                // TODO: Send to backend API
                console.log('Toggling service status:', serviceId);
                showToast('Ã¢ÂœÂ… Hizmet durumu deÃ„ÂŸiÃ…ÂŸtirildi!\n\nService ID: ' + serviceId, 'success');
                
                // Future implementation:
                // fetch('/backend/api/admin/services/' + serviceId + '/toggle-status', {
                //     method: 'POST'
                // })
                // .then(response => response.json())
                // .then(data => {
                //     if (data.success) {
                //         showToast('Durum deÃ„ÂŸiÃ…ÂŸtirildi!', 'success');
                //         location.reload();
                //     }
                // });
            });
        }
        
        function deleteService(serviceId) {
            showConfirm('Ã¢ÂšÂ Ã¯Â¸Â Bu hizmeti silmek istediÃ„ÂŸinizden emin misiniz?\n\nBu iÃ…ÂŸlem geri alÃ„Â±namaz!').then(function(confirmed){
                if (!confirmed) return;
                // TODO: Send to backend API
                console.log('Deleting service:', serviceId);
                showToast('Ã°ÂŸÂ—Â‘Ã¯Â¸Â Hizmet silindi!\n\nService ID: ' + serviceId, 'success');
                
                // Future implementation:
                // fetch('/backend/api/admin/services/' + serviceId, {
                //     method: 'DELETE'
                // })
                // .then(response => response.json())
                // .then(data => {
                //     if (data.success) {
                //         showToast('Hizmet silindi!', 'success');
                //         location.reload();
                //     } else {
                //         showToast('Hata: ' + data.message, 'error');
                //     }
                // });
            });
        }

        // Security Tabs Functionality
        // FarsÃƒÂ§a: Ã˜Â¹Ã™Â…Ã™Â„ÃšÂ©Ã˜Â±Ã˜Â¯ Ã˜ÂªÃ˜Â¨Ã¢Â€ÂŒÃ™Â‡Ã˜Â§Ã›ÂŒ Ã˜Â§Ã™Â…Ã™Â†Ã›ÂŒÃ˜ÂªÃ›ÂŒ.
        // TÃƒÂ¼rkÃƒÂ§e: GÃƒÂ¼venlik Sekmeleri Ã„Â°Ã…ÂŸlevselliÃ„ÂŸi.
        // English: Security Tabs Functionality.
        // Defer tab attachment to idle time (non-critical)
        requestIdleCallback(function(){
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const tabId = this.getAttribute('data-tab');
                    
                    // Remove active class from all tabs
                    document.querySelectorAll('.tab-btn').forEach(b => {
                        b.style.background = 'white';
                        b.style.color = '#333';
                    });
                    
                    // Hide all tab contents
                    document.querySelectorAll('.tab-content').forEach(content => {
                        content.style.display = 'none';
                    });
                    
                    // Activate clicked tab
                    this.style.background = 'linear-gradient(135deg, #667eea, #764ba2)';
                    this.style.color = 'white';
                    
                    // Show corresponding content
                    const content = document.getElementById(tabId);
                    if (content) {
                        content.style.display = 'block';
                    }
                });
            });
        }, { timeout: 500 });

        // Settings Tabs Functionality
        // FarsÃƒÂ§a: Ã˜Â¹Ã™Â…Ã™Â„ÃšÂ©Ã˜Â±Ã˜Â¯ Ã˜ÂªÃ˜Â¨Ã¢Â€ÂŒÃ™Â‡Ã˜Â§Ã›ÂŒ Ã˜ÂªÃ™Â†Ã˜Â¸Ã›ÂŒÃ™Â…Ã˜Â§Ã˜Âª.
        // TÃƒÂ¼rkÃƒÂ§e: Ayarlar Sekmeleri Ã„Â°Ã…ÂŸlevselliÃ„ÂŸi.
        // English: Settings Tabs Functionality.
        requestIdleCallback(function(){
            document.querySelectorAll('.settings-tab-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    const tabId = this.getAttribute('data-settings-tab');
                    
                    // Remove active class from all settings tabs
                    document.querySelectorAll('.settings-tab-btn').forEach(b => {
                        b.style.background = 'white';
                        b.style.color = '#333';
                        b.classList.remove('active');
                    });
                    
                    // Hide all settings tab contents
                    document.querySelectorAll('.settings-tab-content').forEach(content => {
                        content.style.display = 'none';
                    });
                    
                    // Activate clicked tab
                    this.style.background = 'linear-gradient(135deg, #667eea, #764ba2)';
                    this.style.color = 'white';
                    this.classList.add('active');
                });
            });
        }, { timeout: 500 });
        

        // Report Category Tabs Functionality
        // FarsÃƒÂ§a: Ã˜Â¹Ã™Â…Ã™Â„ÃšÂ©Ã˜Â±Ã˜Â¯ Ã˜ÂªÃ˜Â¨Ã¢Â€ÂŒÃ™Â‡Ã˜Â§Ã›ÂŒ Ã˜Â¯Ã˜Â³Ã˜ÂªÃ™Â‡Ã¢Â€ÂŒÃ˜Â¨Ã™Â†Ã˜Â¯Ã›ÂŒ ÃšÂ¯Ã˜Â²Ã˜Â§Ã˜Â±Ã˜Â´.
        // TÃƒÂ¼rkÃƒÂ§e: Rapor Kategorisi Sekmeleri Ã„Â°Ã…ÂŸlevselliÃ„ÂŸi.
        // English: Report Category Tabs Functionality.
        function showReportCategory(category) {
            // Hide all report categories
            document.querySelectorAll('.report-category').forEach(cat => {
                cat.style.display = 'none';
            });
            
            // Remove active class from all buttons
            document.querySelectorAll('.report-tab-btn').forEach(btn => {
                btn.style.background = 'white';
                btn.style.color = '#666';
                btn.style.border = '2px solid #e9ecef';
                btn.classList.remove('active');
            });
            
            // Show selected category
            const selectedCategory = document.getElementById(category + '-reports');
            if (selectedCategory) {
                selectedCategory.style.display = 'block';
            }
            
            // Activate clicked button
            event.target.closest('button').style.background = 'linear-gradient(135deg, #667eea, #764ba2)';
            event.target.closest('button').style.color = 'white';
            event.target.closest('button').style.border = '2px solid #667eea';
            event.target.closest('button').classList.add('active');
        }

        // Report Download Functionality
        // FarsÃƒÂ§a: Ã˜Â¹Ã™Â…Ã™Â„ÃšÂ©Ã˜Â±Ã˜Â¯ Ã˜Â¯Ã˜Â§Ã™Â†Ã™Â„Ã™ÂˆÃ˜Â¯ ÃšÂ¯Ã˜Â²Ã˜Â§Ã˜Â±Ã˜Â´.
        // TÃƒÂ¼rkÃƒÂ§e: Rapor Ã„Â°ndirme Ã„Â°Ã…ÂŸlevselliÃ„ÂŸi.
        // English: Report Download Functionality.
        function downloadReport(reportType, format) {
            // Show loading notification
            const loadingMsg = `Ã°ÂŸÂ“ÂŠ ${reportType.toUpperCase()} raporu ${format.toUpperCase()} formatÃ„Â±nda hazÃ„Â±rlanÃ„Â±yor...`;
            console.log(loadingMsg);
            
            // TODO: Replace with actual backend API call
            // Example API call structure:
            /*
            fetch(`/backend/api/admin/reports/download`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    report_type: reportType,
                    format: format,
                    date_from: document.querySelector(`#${reportType}DateFrom`)?.value,
                    date_to: document.querySelector(`#${reportType}DateTo`)?.value
                })
            })
            .then(response => response.blob())
            .then(blob => {
                // Create download link
                const url = window.URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `${reportType}_report_${new Date().toISOString().split('T')[0]}.${format}`;
                document.body.appendChild(a);
                a.click();
                window.URL.revokeObjectURL(url);
                document.body.removeChild(a);
                
                showToast('Ã¢ÂœÂ… Rapor baÃ…ÂŸarÃ„Â±yla indirildi!', 'success');
            })
            .catch(error => {
                console.error('Download error:', error);
                showToast('Ã¢ÂÂŒ Rapor indirme hatasÃ„Â±: ' + error.message, 'error');
            });
            */
            
            // Temporary simulation for demonstration
            const reportNames = {
                'revenue': 'Gelir Raporu',
                'payment': 'ÃƒÂ–deme Analizi',
                'tax': 'Vergi Raporu',
                'commission': 'Komisyon Raporu',
                'orders': 'SipariÃ…ÂŸ Raporu',
                'services': 'Hizmet PerformansÃ„Â±',
                'carwash': 'Otopark PerformansÃ„Â±',
                'customers': 'MÃƒÂ¼Ã…ÂŸteri Analizi',
                'reviews': 'DeÃ„ÂŸerlendirme Raporu',
                'analytics': 'KapsamlÃ„Â± Analiz',
                'executive': 'YÃƒÂ¶netici ÃƒÂ–zeti'
            };
            
            const formatIcons = {
                'pdf': 'Ã°ÂŸÂ“Â„',
                'excel': 'Ã°ÂŸÂ“ÂŠ',
                'csv': 'Ã°ÂŸÂ“Â‹',
                'pptx': 'Ã°ÂŸÂ“Â½Ã¯Â¸Â'
            };
            
            // Non-blocking simulated download notification (replace blocking alert)
            function showToast(text) {
                const t = document.createElement('div');
                t.className = 'site-toast';
                t.textContent = text;
                // Basic inline styles to avoid external CSS dependency
                t.style.position = 'fixed';
                t.style.right = '20px';
                t.style.bottom = '20px';
                t.style.background = 'rgba(0,0,0,0.85)';
                t.style.color = 'white';
                t.style.padding = '12px 16px';
                t.style.borderRadius = '8px';
                t.style.boxShadow = '0 6px 20px rgba(0,0,0,0.2)';
                t.style.zIndex = 99999;
                t.style.fontSize = '0.95rem';
                t.style.opacity = '0';
                t.style.transition = 'opacity 240ms ease, transform 240ms ease';
                t.style.transform = 'translateY(8px)';
                document.body.appendChild(t);
                // trigger animate
                requestAnimationFrame(() => {
                    t.style.opacity = '1';
                    t.style.transform = 'translateY(0)';
                });
                // remove after 4s
                setTimeout(() => {
                    t.style.opacity = '0';
                    t.style.transform = 'translateY(8px)';
                    setTimeout(() => t.remove(), 300);
                }, 4000);
            }

            // Simulate download delay (non-blocking)
            setTimeout(() => {
                const msg = `${formatIcons[format]} ${reportNames[reportType]} - ${format.toUpperCase()} formatÄ±nda baÅŸarÄ±yla indirildi! ` +
                            `Tarih: ${new Date().toLocaleDateString('tr-TR')} Saat: ${new Date().toLocaleTimeString('tr-TR')}`;
                showToast(msg);
                console.log(`Downloaded: ${reportNames[reportType]} as ${format.toUpperCase()}`);
            }, 500);
        }

        // Add CSS for report category styling
        const reportCategoryStyle = document.createElement('style');
        reportCategoryStyle.textContent = `
            .report-category {
                animation: fadeIn 0.3s ease;
            }
            
            @keyframes fadeIn {
                from {
                    opacity: 0;
                    transform: translateY(10px);
                }
                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }
            
            .report-tab-btn {
                transition: all 0.3s ease;
            }
            
            .report-tab-btn:hover {
                transform: translateY(-2px);
                box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
            }
            
            .report-card {
                transition: all 0.3s ease;
            }
            
            .report-card:hover {
                transform: translateY(-5px);
                box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
            }
            
            .report-btn {
                transition: all 0.2s ease;
            }
            
            .report-btn:hover {
                transform: scale(1.02);
                box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
            }
        `;
        document.head.appendChild(reportCategoryStyle);
    </script>

    <!-- Chart.js Library -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    
    <!-- Dashboard Charts Initialization -->
    <script>
        // Revenue Chart (deferred initialization)
        requestIdleCallback(function(){
            const revenueCtx = document.getElementById('revenueChart');
            if (revenueCtx) {
                new Chart(revenueCtx, {
                    type: 'line',
                    data: {
                        labels: ['Pzt', 'Sal', 'Çar', 'Per', 'Cum', 'Cmt', 'Paz'],
                        datasets: [{
                            label: 'Günlük Gelir (₺)',
                            data: [35000, 42000, 38000, 45000, 52000, 48000, 45680],
                            borderColor: '#667eea',
                            backgroundColor: 'rgba(102, 126, 234, 0.1)',
                            tension: 0.4,
                            fill: true,
                            borderWidth: 3
                        }]
                    },
                    options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: {
                            display: true,
                            position: 'top'
                        },
                        tooltip: {
                            mode: 'index',
                            intersect: false,
                            callbacks: {
                                label: function(context) {
                                    return context.dataset.label + ': ₺' + context.parsed.y.toLocaleString('tr-TR');
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return '₺' + (value/1000) + 'K';
                                }
                            }
                        }
                    }
                    }
                });
            }
        }, { timeout: 500 });

        // Users Chart
        const usersCtx = document.getElementById('usersChart');
        if (usersCtx) {
            new Chart(usersCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Müşteriler', 'Otopark Sahipleri', 'Sürücüler'],
                    datasets: [{
                        label: 'Kullanıcı Dağılımı',
                        data: [158, 24, 45],
                        backgroundColor: [
                            '#667eea',
                            '#28a745',
                            '#ffc107'
                        ],
                        borderWidth: 2,
                        borderColor: '#fff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                padding: 15,
                                font: {
                                    size: 12
                                }
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = ((context.parsed / total) * 100).toFixed(1);
                                    return context.label + ': ' + context.parsed + ' (' + percentage + '%)';
                                }
                            }
                        }
                    }
                }
            });
        }
    </script>

<?php
// Include the universal footer
include '../includes/footer.php';
?>

</div>

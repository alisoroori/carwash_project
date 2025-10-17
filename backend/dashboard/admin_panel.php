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

// Include the universal dashboard header
include '../includes/dashboard_header.php';
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
        /* Farsça: استایل‌های نوار کناری. */
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
        /* Farsça: گرید آمار. */
        /* Türkçe: İstatistik Izgarası. */
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
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea20, #764ba220);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #667eea;
            font-size: 1.5rem;
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
        /* Farsça: بخش فعالیت. */
        /* Türkçe: Aktivite Bölümü. */
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
        /* Farsça: فیلترها. */
        /* Türkçe: Filtreler. */
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
        /* Farsça: استایل‌های جدول. */
        /* Türkçe: Tablo Stilleri. */
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
        /* Farsça: نشان‌های وضعیت. */
        /* Türkçe: Durum Rozetleri. */
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
        /* Farsça: دکمه‌های عملیات. */
        /* Türkçe: Eylem Düğmeleri. */
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
        /* Farsça: گرید گزارشات. */
        /* Türkçe: Rapor Izgarası. */
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
        /* Farsça: فرم تنظیمات. */
        /* Türkçe: Ayarlar Formu. */
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
        /* Farsça: استایل‌های مودال. */
        /* Türkçe: Modal Stilleri. */
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
        }

        .modal-content {
            background: white;
            margin: 5% auto;
            padding: 0;
            border-radius: 10px;
            width: 90%;
            max-width: 500px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }

        .modal-header {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 1.5rem;
            border-radius: 10px 10px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
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
            
            .modal-content {
                width: 95%;
                margin: 10% auto;
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
    </style>
</head>
<body>

<!-- Mobile Menu Toggle Button -->
<button class="mobile-menu-toggle" id="mobileMenuToggle" onclick="toggleMobileMenu()">
    <i class="fas fa-bars" id="menuIcon"></i>
</button>

<!-- Mobile Overlay -->
<div class="mobile-overlay" id="mobileOverlay" onclick="closeMobileMenu()"></div>

<!-- Dashboard Wrapper Container -->
<div class="dashboard-wrapper">
    <!-- Sidebar Navigation - Sticky Position -->
    <!-- Farsça: نوار کناری ناوبری - موقعیت چسبنده. -->
    <!-- Türkçe: Kenar çubuğu navigasyonu - Yapışkan Konum. -->
    <!-- English: Sidebar Navigation - Sticky Position. -->
    <aside class="sidebar" id="sidebar">
            <nav class="nav-menu">
                <ul>
                    <li class="nav-item active">
                        <a href="#dashboard" class="nav-link" data-section="dashboard">
                            <i class="fas fa-tachometer-alt"></i>
                            <span>Gösterge Paneli</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="#carwashes" class="nav-link" data-section="carwashes">
                            <i class="fas fa-parking"></i>
                            <span>Otopark Yönetimi</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="#users" class="nav-link" data-section="users">
                            <i class="fas fa-users"></i>
                            <span>Kullanıcı Yönetimi</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="#bookings" class="nav-link" data-section="bookings">
                            <i class="fas fa-calendar-check"></i>
                            <span>Rezervasyonlar</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="#reports" class="nav-link" data-section="reports">
                            <i class="fas fa-chart-bar"></i>
                            <span>Raporlar</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="#settings" class="nav-link" data-section="settings">
                            <i class="fas fa-cog"></i>
                            <span>Ayarlar</span>
                        </a>
                    </li>
                </ul>
            </nav>
    </aside>

    <!-- Main Content -->
    <!-- Farsça: محتوای اصلی. -->
    <!-- Türkçe: Ana İçerik. -->
    <!-- English: Main Content. -->
    <main class="main-content">
            <!-- Dashboard Section -->
            <!-- Farsça: بخش داشبورد. -->
            <!-- Türkçe: Gösterge Paneli Bölümü. -->
            <!-- English: Dashboard Section. -->
            <section id="dashboard" class="content-section active">
                <div class="section-header">
                    <h2>Gösterge Paneli</h2>
                    <p>Sistem genel bakış ve istatistikler</p>
                </div>
                
                <!-- Stats Cards -->
                <!-- Farsça: کارت‌های آمار. -->
                <!-- Türkçe: İstatistik Kartları. -->
                <!-- English: Stats Cards. -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-car-wash"></i>
                        </div>
                        <div class="stat-info">
                            <h3>24</h3>
                            <p>Toplam Otopark</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-info">
                            <h3>158</h3>
                            <p>Kayıtlı Kullanıcı</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <div class="stat-info">
                            <h3>89</h3>
                            <p>Bugünkü Rezervasyonlar</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-money-bill-wave"></i>
                        </div>
                        <div class="stat-info">
                            <h3>₺15,240</h3>
                            <p>Aylık Gelir</p>
                        </div>
                    </div>
                </div>

                <!-- Recent Activity -->
                <!-- Farsça: فعالیت‌های اخیر. -->
                <!-- Türkçe: Son Aktiviteler. -->
                <!-- English: Recent Activity. -->
                <div class="activity-section">
                    <h3>Son Aktiviteler</h3>
                    <div class="activity-list">
                        <div class="activity-item">
                            <i class="fas fa-plus-circle"></i>
                            <span>Yeni otopark eklendi: "Merkez Otopark"</span>
                            <time>2 saat önce</time>
                        </div>
                        <div class="activity-item">
                            <i class="fas fa-user-plus"></i>
                            <span>Yeni kullanıcı kaydı: Ahmet Yılmaz</span>
                            <time>4 saat önce</time>
                        </div>
                        <div class="activity-item">
                            <i class="fas fa-edit"></i>
                            <span>"Şehir Otopark" bilgileri güncellendi</span>
                            <time>1 gün önce</time>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Car Washes Management Section -->
            <!-- Farsça: بخش مدیریت کارواش‌ها. -->
            <!-- Türkçe: Otopark Yönetimi Bölümü. -->
            <!-- English: Car Washes Management Section. -->
            <section id="carwashes" class="content-section">
                <div class="section-header">
                    <div>
                        <h2>
                            <i class="fas fa-parking" style="color: #667eea; margin-right: 12px;"></i>
                            Otopark Yönetimi
                        </h2>
                        <p>Otopark işletmelerini yönetin</p>
                    </div>
                    <button class="add-btn" id="addCarwashBtn">
                        <i class="fas fa-plus"></i>
                        Yeni Otopark Ekle
                    </button>
                </div>

                <!-- Car Wash Filters -->
                <!-- Farsça: فیلترهای کارواش. -->
                <!-- Türkçe: Otopark Filtreleri. -->
                <!-- English: Car Wash Filters. -->
                <div class="filters">
                    <input type="text" id="carwashSearch" placeholder="Otopark ara..." class="search-input">
                    <select id="statusFilter" class="filter-select">
                        <option value="">Tüm Durumlar</option>
                        <option value="active">Aktif</option>
                        <option value="inactive">Pasif</option>
                        <option value="maintenance">Bakımda</option>
                    </select>
                </div>

                <!-- Car Washes Table -->
                <!-- Farsça: جدول کارواش‌ها. -->
                <!-- Türkçe: Otopark Tablosu. -->
                <!-- English: Car Washes Table. -->
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Otopark Adı</th>
                                <th>Konum</th>
                                <th>Kapasite</th>
                                <th>Fiyat</th>
                                <th>Durum</th>
                                <th>İşlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>001</td>
                                <td>Merkez Otopark</td>
                                <td>Taksim, İstanbul</td>
                                <td>50</td>
                                <td>₺25/saat</td>
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
                                <td>Şehir Otopark</td>
                                <td>Kadıköy, İstanbul</td>
                                <td>75</td>
                                <td>₺30/saat</td>
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
                                <td>AVM Otopark</td>
                                <td>Beşiktaş, İstanbul</td>
                                <td>120</td>
                                <td>₺20/saat</td>
                                <td><span class="status-badge maintenance">Bakımda</span></td>
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

            <!-- Users Management Section -->
            <!-- Farsça: بخش مدیریت کاربران. -->
            <!-- Türkçe: Kullanıcı Yönetimi Bölümü. -->
            <!-- English: Users Management Section. -->
            <section id="users" class="content-section">
                <div class="section-header">
                    <h2>Kullanıcı Yönetimi</h2>
                    <button class="add-btn" id="addUserBtn">
                        <i class="fas fa-plus"></i>
                        Yeni Kullanıcı Ekle
                    </button>
                </div>

                <!-- User Filters -->
                <!-- Farsça: فیلترهای کاربر. -->
                <!-- Türkçe: Kullanıcı Filtreleri. -->
                <!-- English: User Filters. -->
                <div class="filters">
                    <input type="text" id="userSearch" placeholder="Kullanıcı ara..." class="search-input">
                    <select id="userTypeFilter" class="filter-select">
                        <option value="">Tüm Tipler</option>
                        <option value="admin">Yönetici</option>
                        <option value="user">Kullanıcı</option>
                        <option value="premium">Premium</option>
                    </select>
                </div>

                <!-- Users Table -->
                <!-- Farsça: جدول کاربران. -->
                <!-- Türkçe: Kullanıcı Tablosu. -->
                <!-- English: Users Table. -->
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Kullanıcı Adı</th>
                                <th>Email</th>
                                <th>Telefon</th>
                                <th>Tip</th>
                                <th>Kayıt Tarihi</th>
                                <th>Durum</th>
                                <th>İşlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>001</td>
                                <td>Ahmet Yılmaz</td>
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
                                <td><span class="type-badge user">Kullanıcı</span></td>
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
                                <td><span class="type-badge admin">Yönetici</span></td>
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

            <!-- Bookings Section -->
            <!-- Farsça: بخش رزروها. -->
            <!-- Türkçe: Rezervasyonlar Bölümü. -->
            <!-- English: Bookings Section. -->
            <section id="bookings" class="content-section">
                <div class="section-header">
                    <h2>Rezervasyonlar</h2>
                </div>

                <div class="filters">
                    <input type="date" id="bookingDate" class="search-input">
                    <select id="bookingStatus" class="filter-select">
                        <option value="">Tüm Rezervasyonlar</option>
                        <option value="confirmed">Onaylandı</option>
                        <option value="pending">Beklemede</option>
                        <option value="cancelled">İptal Edildi</option>
                    </select>
                </div>

                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Rezervasyon ID</th>
                                <th>Kullanıcı</th>
                                <th>Otopark</th>
                                <th>Tarih</th>
                                <th>Saat</th>
                                <th>Süre</th>
                                <th>Tutar</th>
                                <th>Durum</th>
                                <th>İşlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>R001</td>
                                <td>Ahmet Yılmaz</td>
                                <td>Merkez Otopark</td>
                                <td>2024-01-25</td>
                                <td>14:30</td>
                                <td>3 saat</td>
                                <td>₺75</td>
                                <td><span class="status-badge active">Onaylandı</span></td>
                                <td>
                                    <button class="action-btn view-btn">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="action-btn delete-btn">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- Reports Section -->
            <!-- Farsça: بخش گزارشات. -->
            <!-- Türkçe: Raporlar Bölümü. -->
            <!-- English: Reports Section. -->
            <section id="reports" class="content-section">
                <div class="section-header">
                    <h2>Raporlar</h2>
                </div>
                <div class="reports-grid">
                    <div class="report-card">
                        <h3>Günlük Rapor</h3>
                        <p>Bugünkü rezervasyonlar ve gelir</p>
                        <button class="report-btn">PDF İndir</button>
                    </div>
                    <div class="report-card">
                        <h3>Aylık Rapor</h3>
                        <p>Bu ayın detaylı analizi</p>
                        <button class="report-btn">PDF İndir</button>
                    </div>
                    <div class="report-card">
                        <h3>Kullanıcı Raporu</h3>
                        <p>Kullanıcı istatistikleri</p>
                        <button class="report-btn">PDF İndir</button>
                    </div>
                </div>
            </section>

            <!-- Settings Section -->
            <!-- Farsça: بخش تنظیمات. -->
            <!-- Türkçe: Ayarlar Bölümü. -->
            <!-- English: Settings Section. -->
            <section id="settings" class="content-section">
                <div class="section-header">
                    <h2>Sistem Ayarları</h2>
                </div>
                <div class="settings-form">
                    <div class="form-group">
                        <label>Site Adı</label>
                        <input type="text" value="Otopark Yönetim Sistemi">
                    </div>
                    <div class="form-group">
                        <label>Admin Email</label>
                        <input type="email" value="admin@otoparkdemotime.com">
                    </div>
                    <div class="form-group">
                        <label>Varsayılan Saat Ücreti (₺)</label>
                        <input type="number" value="25">
                    </div>
                    <button class="save-btn">Ayarları Kaydet</button>
                </div>
            </section>
    </main>
</div>

<!-- Modals -->
<!-- Add Car Wash Modal -->
    <!-- Farsça: مودال افزودن کارواش. -->
    <!-- Türkçe: Otopark Ekle Modalı. -->
    <!-- English: Add Car Wash Modal. -->
    <div id="carwashModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Yeni Otopark Ekle</h3>
                <span class="close" id="closeCarwashModal">&times;</span>
            </div>
            <div class="modal-body">
                <form id="carwashForm">
                    <div class="form-group">
                        <label>Otopark Adı</label>
                        <input type="text" id="carwashName" required>
                    </div>
                    <div class="form-group">
                        <label>Adres</label>
                        <textarea id="carwashAddress" required></textarea>
                    </div>
                    <div class="form-group">
                        <label>Kapasite</label>
                        <input type="number" id="carwashCapacity" required>
                    </div>
                    <div class="form-group">
                        <label>Saat Ücreti (₺)</label>
                        <input type="number" id="carwashPrice" required>
                    </div>
                    <div class="form-group">
                        <label>Durum</label>
                        <select id="carwashStatus" required>
                            <option value="active">Aktif</option>
                            <option value="inactive">Pasif</option>
                            <option value="maintenance">Bakımda</option>
                        </select>
                    </div>
                    <button type="submit" class="submit-btn">Otopark Ekle</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Mobile Menu Toggle Functions
        // Farsça: توابع تغییر منوی موبایل.
        // Türkçe: Mobil Menü Geçiş Fonksiyonları.
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
        // Farsça: عملکرد ناوبری.
        // Türkçe: Navigasyon işlevselliği.
        // English: Navigation functionality.
        document.querySelectorAll('.nav-link').forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                
                // Remove active class from all nav items and sections
                // Farsça: حذف کلاس فعال از همه آیتم‌های ناوبری و بخش‌ها.
                // Türkçe: Tüm navigasyon öğelerinden ve bölümlerden aktif sınıfını kaldır.
                // English: Remove active class from all nav items and sections.
                document.querySelectorAll('.nav-item').forEach(item => item.classList.remove('active'));
                document.querySelectorAll('.content-section').forEach(section => section.classList.remove('active'));
                
                // Add active class to clicked nav item
                // Farsça: اضافه کردن کلاس فعال به آیتم ناوبری کلیک شده.
                // Türkçe: Tıklanan navigasyon öğesine aktif sınıfını ekle.
                // English: Add active class to clicked nav item.
                this.parentElement.classList.add('active');
                
                // Show corresponding section
                // Farsça: نمایش بخش مربوطه.
                // Türkçe: İlgili bölümü göster.
                // English: Show corresponding section.
                const sectionId = this.getAttribute('data-section');
                document.getElementById(sectionId).classList.add('active');
                
                // Close mobile menu after selection
                if (window.innerWidth < 1024) {
                    closeMobileMenu();
                }
            });
        });

        // Modal functionality
        // Farsça: عملکرد مودال.
        // Türkçe: Modal işlevselliği.
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
        // Farsça: ارسال فرم.
        // Türkçe: Form gönderimi.
        // English: Form submission.
        document.getElementById('carwashForm').addEventListener('submit', function(e) {
            e.preventDefault();
            // Here you would typically send data to PHP backend
            // Farsça: در اینجا شما معمولاً داده‌ها را به بک‌اند PHP ارسال می‌کنید.
            // Türkçe: Burada tipik olarak verileri PHP arka ucuna gönderirsiniz.
            // English: Here you would typically send data to PHP backend.
            alert('Otopark başarıyla eklendi!');
            carwashModal.style.display = 'none';
        });

        // Search and filter functionality (basic implementation)
        // Farsça: عملکرد جستجو و فیلتر (پیاده‌سازی پایه).
        // Türkçe: Arama ve filtreleme işlevselliği (temel uygulama).
        // English: Search and filter functionality (basic implementation).
        document.getElementById('carwashSearch').addEventListener('input', function() {
            // Implement search functionality
            // Farsça: عملکرد جستجو را پیاده‌سازی کنید.
            // Türkçe: Arama işlevselliğini uygulayın.
            // English: Implement search functionality.
            console.log('Searching for:', this.value);
        });
    </script>

<?php
// Include the universal footer
include '../includes/footer.php';
?>
</body>
</html>

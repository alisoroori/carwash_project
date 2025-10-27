﻿<?php
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
        /* Farsça: استایل‌های ریسپانسیو مدیریت پرداخت. */
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
                            <span>Kullanıcı Yönetimi</span>
                        </a>
                    </li>
                    
                    <!-- Order Management -->
                    <li class="nav-item">
                        <a href="#orders" class="nav-link" data-section="orders">
                            <i class="fas fa-shopping-cart"></i>
                            <span>Sipariş Yönetimi</span>
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
                            <span>Otopark Yönetimi</span>
                        </a>
                    </li>
                    
                    <!-- Service Management -->
                    <li class="nav-item">
                        <a href="#services" class="nav-link" data-section="services">
                            <i class="fas fa-concierge-bell"></i>
                            <span>Hizmet Yönetimi</span>
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
                            <span>Yorumlar & Puanlar</span>
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
                            <span>İçerik Yönetimi</span>
                        </a>
                    </li>
                    
                    <!-- Security & Logs -->
                    <li class="nav-item">
                        <a href="#security" class="nav-link" data-section="security">
                            <i class="fas fa-shield-alt"></i>
                            <span>Güvenlik & Loglar</span>
                        </a>
                    </li>
                    
                    <!-- Settings -->
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
            <section id="dashboard" class="content-section active">
                <div class="section-header">
                    <div>
                        <h2><i class="fas fa-tachometer-alt" style="color: #667eea; margin-right: 12px;"></i>Dashboard</h2>
                        <p>Sistem genel bakış ve istatistikler</p>
                    </div>
                </div>
                
                <!-- Key Stats Cards -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #667eea20, #764ba220);">
                            <i class="fas fa-clipboard-list" style="color: #667eea;"></i>
                        </div>
                        <div class="stat-info">
                            <h3>156</h3>
                            <p>Toplam Siparişler</p>
                            <small class="text-green-600"><i class="fas fa-arrow-up"></i> +12% bu ay</small>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #28a74520, #20c99720);">
                            <i class="fas fa-hourglass-half" style="color: #28a745;"></i>
                        </div>
                        <div class="stat-info">
                            <h3>24</h3>
                            <p>Devam Eden Siparişler</p>
                            <small class="text-blue-600"><i class="fas fa-clock"></i> Gerçek zamanlı</small>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #dc354520, #c8233320);">
                            <i class="fas fa-times-circle" style="color: #dc3545;"></i>
                        </div>
                        <div class="stat-info">
                            <h3>8</h3>
                            <p>İptal Edilen</p>
                            <small class="text-red-600"><i class="fas fa-arrow-down"></i> -3% bu ay</small>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #ffc10720, #ff663320);">
                            <i class="fas fa-lira-sign" style="color: #ffc107;"></i>
                        </div>
                        <div class="stat-info">
                            <h3>₺45,680</h3>
                            <p>Günlük Gelir</p>
                            <small class="text-green-600"><i class="fas fa-arrow-up"></i> +25% dün</small>
                        </div>
                    </div>
                </div>

                <!-- Charts Row -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                    <div class="activity-section">
                        <h3><i class="fas fa-chart-line mr-2"></i>Gelir Trendi (Son 7 Gün)</h3>
                        <canvas id="revenueChart" style="max-height: 300px;"></canvas>
                    </div>
                    
                    <div class="activity-section">
                        <h3><i class="fas fa-users mr-2"></i>Aktif Kullanıcılar</h3>
                        <canvas id="usersChart" style="max-height: 300px;"></canvas>
                    </div>
                </div>

                <!-- Recent Notifications -->
                <div class="activity-section">
                    <h3><i class="fas fa-bell mr-2"></i>Son Bildirimler</h3>
                    <div class="activity-list">
                        <div class="activity-item" style="border-left-color: #28a745;">
                            <i class="fas fa-shopping-cart" style="color: #28a745;"></i>
                            <span><strong>Yeni Sipariş:</strong> Ahmet Yılmaz - Tam Detaylandırma</span>
                            <time>5 dakika önce</time>
                        </div>
                        <div class="activity-item" style="border-left-color: #dc3545;">
                            <i class="fas fa-exclamation-triangle" style="color: #dc3545;"></i>
                            <span><strong>Ödeme Hatası:</strong> Kart işlemi başarısız - Sipariş #1245</span>
                            <time>15 dakika önce</time>
                        </div>
                        <div class="activity-item" style="border-left-color: #667eea;">
                            <i class="fas fa-headset" style="color: #667eea;"></i>
                            <span><strong>Destek Talebi:</strong> Elif Kara - Sipariş takibi sorunu</span>
                            <time>1 saat önce</time>
                        </div>
                        <div class="activity-item" style="border-left-color: #ffc107;">
                            <i class="fas fa-star" style="color: #ffc107;"></i>
                            <span><strong>Yeni Yorum:</strong> 5 yıldız - "Harika hizmet!"</span>
                            <time>2 saat önce</time>
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
                                <th><i class="fas fa-hashtag mr-1"></i>ID</th>
                                <th><i class="fas fa-building mr-1"></i>Otopark Adı</th>
                                <th><i class="fas fa-map-marker-alt mr-1"></i>Konum</th>
                                <th><i class="fas fa-car mr-1"></i>Kapasite</th>
                                <th><i class="fas fa-money-bill-wave mr-1"></i>Fiyat</th>
                                <th><i class="fas fa-toggle-on mr-1"></i>Durum</th>
                                <th><i class="fas fa-cogs mr-1"></i>İşlemler</th>
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
                                    Taksim, İstanbul
                                </td>
                                <td>
                                    <span style="display: flex; align-items: center; gap: 6px;">
                                        <i class="fas fa-car" style="color: #28a745;"></i>
                                        <strong>50</strong> araç
                                    </span>
                                </td>
                                <td>
                                    <strong style="color: #667eea; font-size: 1.05rem;">₺25</strong>/saat
                                </td>
                                <td><span class="status-badge active"><i class="fas fa-check-circle"></i> Aktif</span></td>
                                <td>
                                    <button class="action-btn edit-btn" title="Düzenle">
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
                                            <strong>Şehir Otopark</strong><br>
                                            <small style="color: #64748b;">Standart Tesis</small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <i class="fas fa-map-marker-alt" style="color: #dc3545; margin-right: 6px;"></i>
                                    Kadıköy, İstanbul
                                </td>
                                <td>
                                    <span style="display: flex; align-items: center; gap: 6px;">
                                        <i class="fas fa-car" style="color: #28a745;"></i>
                                        <strong>75</strong> araç
                                    </span>
                                </td>
                                <td>
                                    <strong style="color: #667eea; font-size: 1.05rem;">₺30</strong>/saat
                                </td>
                                <td><span class="status-badge active"><i class="fas fa-check-circle"></i> Aktif</span></td>
                                <td>
                                    <button class="action-btn edit-btn" title="Düzenle">
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
                                    Beşiktaş, İstanbul
                                </td>
                                <td>
                                    <span style="display: flex; align-items: center; gap: 6px;">
                                        <i class="fas fa-car" style="color: #28a745;"></i>
                                        <strong>120</strong> araç
                                    </span>
                                </td>
                                <td>
                                    <strong style="color: #667eea; font-size: 1.05rem;">₺20</strong>/saat
                                </td>
                                <td><span class="status-badge maintenance"><i class="fas fa-tools"></i> Bakımda</span></td>
                                <td>
                                    <button class="action-btn edit-btn" title="Düzenle">
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
                        <h2><i class="fas fa-shopping-cart" style="color: #667eea; margin-right: 12px;"></i>Sipariş Yönetimi</h2>
                        <p>Tüm siparişleri görüntüle ve yönet</p>
                    </div>
                    <button class="add-btn">
                        <i class="fas fa-file-export"></i>
                        Raporları Dışa Aktar
                    </button>
                </div>

                <!-- Order Filters -->
                <div class="filters" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px;">
                    <input type="text" id="orderSearch" placeholder="Sipariş No, Müşteri Ara..." class="search-input" style="grid-column: 1 / -1;">
                    
                    <select id="orderStatusFilter" class="filter-select">
                        <option value="">Tüm Durumlar</option>
                        <option value="pending">Beklemede</option>
                        <option value="in-progress">Devam Ediyor</option>
                        <option value="completed">Tamamlandı</option>
                        <option value="cancelled">İptal Edildi</option>
                    </select>
                    
                    <select id="orderServiceFilter" class="filter-select">
                        <option value="">Tüm Hizmetler</option>
                        <option value="wash">Dış Yıkama</option>
                        <option value="interior">İç Temizlik</option>
                        <option value="detail">Tam Detaylandırma</option>
                        <option value="wax">Cilalama</option>
                    </select>
                    
                    <input type="date" id="orderDateFrom" class="filter-select" placeholder="Başlangıç Tarihi">
                    <input type="date" id="orderDateTo" class="filter-select" placeholder="Bitiş Tarihi">
                    
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
                                <th>İşlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><strong>#ORD-1245</strong></td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 8px;">
                                        <i class="fas fa-user-circle" style="color: #667eea; font-size: 20px;"></i>
                                        <div>
                                            <div><strong>Ahmet Yılmaz</strong></div>
                                            <small style="color: #64748b;">ahmet@email.com</small>
                                        </div>
                                    </div>
                                </td>
                                <td>Merkez Otopark</td>
                                <td><span class="type-badge" style="background: #28a74520; color: #28a745;">Tam Detaylandırma</span></td>
                                <td><strong>₺350</strong></td>
                                <td><span class="status-badge" style="background: #ffc10720; color: #ff8c00; border: 1px solid #ffc107;">Devam Ediyor</span></td>
                                <td>17 Eki 2025, 14:30</td>
                                <td>
                                    <button class="action-btn view-btn" onclick="viewOrder('1245')" title="Detayları Görüntüle">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="action-btn edit-btn" onclick="updateOrderStatus('1245')" title="Durum Güncelle">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="action-btn" style="background: #28a74520; color: #28a745;" onclick="printInvoice('1245')" title="Fatura Yazdır">
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
                                <td>Kadıköy Otopark</td>
                                <td><span class="type-badge" style="background: #667eea20; color: #667eea;">İç Temizlik</span></td>
                                <td><strong>₺200</strong></td>
                                <td><span class="status-badge active">Tamamlandı</span></td>
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
                                <td>Beşiktaş Otopark</td>
                                <td><span class="type-badge" style="background: #dc354520; color: #dc3545;">Dış Yıkama</span></td>
                                <td><strong>₺150</strong></td>
                                <td><span class="status-badge inactive">İptal Edildi</span></td>
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
                                            <div><strong>Zeynep Öztürk</strong></div>
                                            <small style="color: #64748b;">zeynep@email.com</small>
                                        </div>
                                    </div>
                                </td>
                                <td>Şişli Otopark</td>
                                <td><span class="type-badge" style="background: #ffc10720; color: #ff8c00;">Cilalama</span></td>
                                <td><strong>₺280</strong></td>
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
                        <strong>156</strong> siparişten <strong>1-10</strong> arası gösteriliyor
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
                    <input type="text" id="paymentSearch" placeholder="İşlem No, Müşteri Ara..." class="search-input" style="grid-column: 1 / -1;">
                    
                    <select id="paymentTypeFilter" class="filter-select">
                        <option value="">Tüm Ödeme Tipleri</option>
                        <option value="online">Online (Kart)</option>
                        <option value="cash">Nakit</option>
                        <option value="bank">Banka Transferi</option>
                    </select>
                    
                    <select id="paymentStatusFilter" class="filter-select">
                        <option value="">Tüm Durumlar</option>
                        <option value="success">Başarılı</option>
                        <option value="pending">Beklemede</option>
                        <option value="failed">Başarısız</option>
                        <option value="refunded">İade Edildi</option>
                    </select>
                    
                    <input type="date" id="paymentDateFrom" class="filter-select">
                    <input type="date" id="paymentDateTo" class="filter-select">
                    
                    <button class="add-btn">
                        <i class="fas fa-filter"></i> Filtrele
                    </button>
                </div>

                <!-- Payments Table -->
                <div class="table-container">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>İşlem No</th>
                                <th>Sipariş</th>
                                <th>Müşteri</th>
                                <th>Tutar</th>
                                <th>Ödeme Tipi</th>
                                <th>Durum</th>
                                <th>Tarih</th>
                                <th>İşlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><strong>#PAY-8821</strong></td>
                                <td><a href="#" style="color: #667eea;">#ORD-1245</a></td>
                                <td>
                                    <div>
                                        <strong>Ahmet Yılmaz</strong><br>
                                        <small style="color: #64748b;">ahmet@email.com</small>
                                    </div>
                                </td>
                                <td><strong style="color: #28a745;">₺350</strong></td>
                                <td><span class="type-badge" style="background: #667eea20; color: #667eea;"><i class="fas fa-credit-card"></i> Online</span></td>
                                <td><span class="status-badge active"><i class="fas fa-check"></i> Başarılı</span></td>
                                <td>17 Eki 2025, 14:35</td>
                                <td>
                                    <button class="action-btn view-btn" title="Detayları Gör">
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
                                <td><strong style="color: #28a745;">₺200</strong></td>
                                <td><span class="type-badge" style="background: #28a74520; color: #28a745;"><i class="fas fa-money-bill-wave"></i> Nakit</span></td>
                                <td><span class="status-badge active"><i class="fas fa-check"></i> Başarılı</span></td>
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
                                <td><strong style="color: #dc3545;">₺150</strong></td>
                                <td><span class="type-badge" style="background: #667eea20; color: #667eea;"><i class="fas fa-credit-card"></i> Online</span></td>
                                <td><span class="status-badge inactive"><i class="fas fa-times"></i> Başarısız</span></td>
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
                                        <strong>Zeynep Öztürk</strong><br>
                                        <small style="color: #64748b;">zeynep@email.com</small>
                                    </div>
                                </td>
                                <td><strong style="color: #ffc107;">₺280</strong></td>
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
                    <h3 style="margin-bottom: 16px;"><i class="fas fa-hand-holding-usd mr-2"></i>Otopark Ödemeleri (Tasfiye)</h3>
                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Otopark</th>
                                    <th>Toplam Gelir</th>
                                    <th>Komisyon (%15)</th>
                                    <th>Ödenecek Tutar</th>
                                    <th>Durum</th>
                                    <th>İşlemler</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><strong>Merkez Otopark</strong></td>
                                    <td>₺8,500</td>
                                    <td>-₺1,275</td>
                                    <td><strong style="color: #28a745;">₺7,225</strong></td>
                                    <td><span class="status-badge pending">Ödeme Bekliyor</span></td>
                                    <td>
                                        <button class="add-btn" style="padding: 8px 16px; font-size: 14px;">
                                            <i class="fas fa-money-check-alt"></i> Öde
                                        </button>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Kadıköy Otopark</strong></td>
                                    <td>₺5,200</td>
                                    <td>-₺780</td>
                                    <td><strong style="color: #28a745;">₺4,420</strong></td>
                                    <td><span class="status-badge active">Ödendi</span></td>
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

            <!-- Service Management Section -->
            <section id="services" class="content-section">
                <div class="section-header">
                    <div>
                        <h2><i class="fas fa-concierge-bell" style="color: #17a2b8; margin-right: 12px;"></i>Hizmet Yönetimi</h2>
                        <p>Tüm hizmetleri yönet, fiyatlandır ve düzenle</p>
                    </div>
                    <button class="add-btn" id="addServiceBtn">
                        <i class="fas fa-plus"></i>
                        Yeni Hizmet Ekle
                    </button>
                </div>

                <!-- Service Filters -->
                <div class="filters" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px;">
                    <input type="text" id="serviceSearch" placeholder="Hizmet Ara..." class="search-input" style="grid-column: 1 / -1;">
                    
                    <select id="serviceCategoryFilter" class="filter-select">
                        <option value="">Tüm Kategoriler</option>
                        <option value="wash">Yıkama</option>
                        <option value="detail">Detaylı Bakım</option>
                        <option value="polish">Cilalama & Koruma</option>
                        <option value="interior">İç Temizlik</option>
                    </select>
                    
                    <select id="serviceStatusFilter" class="filter-select">
                        <option value="">Tüm Durumlar</option>
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
                                <th>Hizmet Adı</th>
                                <th>Kategori</th>
                                <th>Temel Fiyat</th>
                                <th>Süre</th>
                                <th>Araç Tipleri</th>
                                <th>Durum</th>
                                <th>Sıralama</th>
                                <th>İşlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><strong>#001</strong></td>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 10px;">
                                        <i class="fas fa-car" style="color: #667eea; font-size: 20px;"></i>
                                        <div>
                                            <strong>Dış Yıkama</strong><br>
                                            <small style="color: #64748b;">Basit dış temizlik</small>
                                        </div>
                                    </div>
                                </td>
                                <td><span class="type-badge" style="background: #667eea20; color: #667eea;">Yıkama</span></td>
                                <td><strong>₺150</strong></td>
                                <td>30 dk</td>
                                <td>
                                    <small>Sedan: ₺150<br>SUV: ₺180<br>Kamyonet: ₺200</small>
                                </td>
                                <td><span class="status-badge active">Aktif</span></td>
                                <td>1</td>
                                <td>
                                    <button class="action-btn edit-btn" onclick="editService(1)" title="Düzenle">
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
                                            <strong>İç Temizlik</strong><br>
                                            <small style="color: #64748b;">Detaylı iç temizlik</small>
                                        </div>
                                    </div>
                                </td>
                                <td><span class="type-badge" style="background: #28a74520; color: #28a745;">İç Temizlik</span></td>
                                <td><strong>₺200</strong></td>
                                <td>45 dk</td>
                                <td>
                                    <small>Sedan: ₺200<br>SUV: ₺250<br>Kamyonet: ₺300</small>
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
                                            <strong>Tam Detaylandırma</strong><br>
                                            <small style="color: #64748b;">Komple bakım paketi</small>
                                        </div>
                                    </div>
                                </td>
                                <td><span class="type-badge" style="background: #ffc10720; color: #ff8c00;">Detaylı Bakım</span></td>
                                <td><strong>₺350</strong></td>
                                <td>90 dk</td>
                                <td>
                                    <small>Sedan: ₺350<br>SUV: ₺450<br>Kamyonet: ₺550</small>
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
                                            <small style="color: #64748b;">Uzun ömürlü koruma</small>
                                        </div>
                                    </div>
                                </td>
                                <td><span class="type-badge" style="background: #dc354520; color: #dc3545;">Cilalama & Koruma</span></td>
                                <td><strong>₺850</strong></td>
                                <td>180 dk</td>
                                <td>
                                    <small>Sedan: ₺850<br>SUV: ₺1.050<br>Kamyonet: ₺1.250</small>
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
                        <p>Müşteri destek taleplerini yönet ve yanıtla</p>
                    </div>
                    <button class="add-btn" id="addTicketBtn" style="background: linear-gradient(135deg, #fd7e14, #dc3545);">
                        <i class="fas fa-plus"></i>
                        Yeni Talep Oluştur
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
                            <small class="text-red-600">Yanıt gerekli</small>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #ffc10720, #fd7e1420);">
                            <i class="fas fa-clock" style="color: #ffc107;"></i>
                        </div>
                        <div class="stat-info">
                            <h3>15</h3>
                            <p>Devam Eden</p>
                            <small class="text-yellow-600">İşlemde</small>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #28a74520, #20c99720);">
                            <i class="fas fa-check-circle" style="color: #28a745;"></i>
                        </div>
                        <div class="stat-info">
                            <h3>142</h3>
                            <p>Çözümlendi</p>
                            <small class="text-green-600">Bu ay</small>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #667eea20, #764ba220);">
                            <i class="fas fa-user-clock" style="color: #667eea;"></i>
                        </div>
                        <div class="stat-info">
                            <h3>2.5 saat</h3>
                            <p>Ort. Yanıt Süresi</p>
                            <small class="text-blue-600">Son 7 gün</small>
                        </div>
                    </div>
                </div>

                <!-- Ticket Filters -->
                <div class="filters" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px;">
                    <input type="text" id="ticketSearch" placeholder="Talep No, Müşteri Ara..." class="search-input" style="grid-column: 1 / -1;">
                    
                    <select id="ticketStatusFilter" class="filter-select">
                        <option value="">Tüm Durumlar</option>
                        <option value="new">Yeni</option>
                        <option value="open">Açık</option>
                        <option value="in_progress">Devam Ediyor</option>
                        <option value="waiting_on_user">Kullanıcı Bekleniyor</option>
                        <option value="resolved">Çözüldü</option>
                        <option value="closed">Kapalı</option>
                    </select>
                    
                    <select id="ticketPriorityFilter" class="filter-select">
                        <option value="">Tüm Öncelikler</option>
                        <option value="urgent">Acil</option>
                        <option value="high">Yüksek</option>
                        <option value="medium">Orta</option>
                        <option value="low">Düşük</option>
                    </select>
                    
                    <select id="ticketCategoryFilter" class="filter-select">
                        <option value="">Tüm Kategoriler</option>
                        <option value="technical">Teknik</option>
                        <option value="billing">Fatura</option>
                        <option value="service">Hizmet</option>
                        <option value="complaint">Şikayet</option>
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
                                <th>Müşteri</th>
                                <th>Konu</th>
                                <th>Kategori</th>
                                <th>Öncelik</th>
                                <th>Durum</th>
                                <th>Atanan</th>
                                <th>Oluşturma</th>
                                <th>İşlemler</th>
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
                                <td>Ödeme alınamadı hatası</td>
                                <td><span class="type-badge" style="background: #667eea20; color: #667eea;">Teknik</span></td>
                                <td><span class="status-badge" style="background: #dc354520; color: #dc3545; border: 1px solid #dc3545;">Acil</span></td>
                                <td><span class="status-badge" style="background: #ffc10720; color: #ff8c00; border: 1px solid #ffc107;">Açık</span></td>
                                <td>Ahmet Y.</td>
                                <td>18 Eki 2025, 10:30</td>
                                <td>
                                    <button class="action-btn view-btn" title="Detay">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="action-btn edit-btn" title="Yanıtla">
                                        <i class="fas fa-reply"></i>
                                    </button>
                                    <button class="action-btn" style="background: #28a74520; color: #28a745;" title="Çöz">
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
                                <td>Hizmet kalitesi şikayeti</td>
                                <td><span class="type-badge" style="background: #dc354520; color: #dc3545;">Şikayet</span></td>
                                <td><span class="status-badge" style="background: #ffc10720; color: #ff8c00;">Yüksek</span></td>
                                <td><span class="status-badge" style="background: #667eea20; color: #667eea;">Devam Ediyor</span></td>
                                <td>Ayşe K.</td>
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
                                        <strong>Zeynep Öztürk</strong><br>
                                        <small style="color: #64748b;">zeynep@email.com</small>
                                    </div>
                                </td>
                                <td>Randevu değişikliği talebi</td>
                                <td><span class="type-badge" style="background: #28a74520; color: #28a745;">Hizmet</span></td>
                                <td><span class="status-badge" style="background: #6c757d20; color: #6c757d;">Orta</span></td>
                                <td><span class="status-badge active">Çözüldü</span></td>
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
                        <h2><i class="fas fa-star" style="color: #ffc107; margin-right: 12px;"></i>Yorumlar & Puanlar</h2>
                        <p>Müşteri yorumlarını yönet ve denetle</p>
                    </div>
                    <button class="add-btn" style="background: linear-gradient(135deg, #ffc107, #ff9800);">
                        <i class="fas fa-file-export"></i>
                        Raporları Dışa Aktar
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
                            <small class="text-yellow-600">248 değerlendirme</small>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #28a74520, #20c99720);">
                            <i class="fas fa-check-circle" style="color: #28a745;"></i>
                        </div>
                        <div class="stat-info">
                            <h3>186</h3>
                            <p>Onaylanmış</p>
                            <small class="text-green-600">Görüntüleniyor</small>
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
                            <small class="text-red-600">İnceleme gerekli</small>
                        </div>
                    </div>
                </div>

                <!-- Review Filters -->
                <div class="filters" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px;">
                    <input type="text" id="reviewSearch" placeholder="Müşteri, Sipariş Ara..." class="search-input" style="grid-column: 1 / -1;">
                    
                    <select id="reviewStatusFilter" class="filter-select">
                        <option value="">Tüm Durumlar</option>
                        <option value="pending">Beklemede</option>
                        <option value="approved">Onaylandı</option>
                        <option value="rejected">Reddedildi</option>
                        <option value="flagged">Raporlandı</option>
                    </select>
                    
                    <select id="reviewRatingFilter" class="filter-select">
                        <option value="">Tüm Puanlar</option>
                        <option value="5">5 Yıldız</option>
                        <option value="4">4 Yıldız</option>
                        <option value="3">3 Yıldız</option>
                        <option value="2">2 Yıldız</option>
                        <option value="1">1 Yıldız</option>
                    </select>
                    
                    <input type="date" id="reviewDateFrom" class="filter-select">
                    
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
                                <th>Müşteri</th>
                                <th>Sipariş</th>
                                <th>Puan</th>
                                <th>Yorum</th>
                                <th>Durum</th>
                                <th>Tarih</th>
                                <th>İşlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><strong>#REV-245</strong></td>
                                <td>
                                    <div>
                                        <strong>Ahmet Yılmaz</strong><br>
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
                                    <small>"Harika hizmet! Çok memnun kaldım. Kesinlikle tavsiye ederim."</small>
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
                                        <small style="color: #64748b;">Kadıköy Otopark</small>
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
                                    <small>"İyi hizmet ama biraz pahalı buldum."</small>
                                </td>
                                <td><span class="status-badge active">Onaylandı</span></td>
                                <td>17 Eki 2025</td>
                                <td>
                                    <button class="action-btn view-btn">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="action-btn edit-btn" title="Yanıtla">
                                        <i class="fas fa-reply"></i>
                                    </button>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>#REV-243</strong></td>
                                <td>
                                    <div>
                                        <strong>Mehmet Demir</strong><br>
                                        <small style="color: #64748b;">Beşiktaş Otopark</small>
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
                                    <small>"Randevuya geç kaldılar ve işlem hatalıydı. Memnun kalmadım."</small>
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
            <!-- Farsça: بخش گزارشات. -->
            <!-- Türkçe: Raporlar Bölümü. -->
            <!-- English: Reports Section. -->
            <section id="reports" class="content-section">
                <div class="section-header">
                    <div>
                        <h2><i class="fas fa-chart-line" style="color: #667eea; margin-right: 12px;"></i>Raporlar ve Analizler</h2>
                        <p>Detaylı iş analizleri ve raporları oluştur</p>
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
                            <p>İndirilen Raporlar</p>
                            <small style="color: #666;"><i class="fas fa-calendar"></i> Son 30 gün</small>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #ffc10720, #ff663320);">
                            <i class="fas fa-clock" style="color: #ffc107;"></i>
                        </div>
                        <div class="stat-info">
                            <h3>15</h3>
                            <p>Zamanlanmış Raporlar</p>
                            <small style="color: #666;"><i class="fas fa-bell"></i> Otomatik</small>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #17a2b820, #00bcd420);">
                            <i class="fas fa-chart-bar" style="color: #17a2b8;"></i>
                        </div>
                        <div class="stat-info">
                            <h3>8</h3>
                            <p>Rapor Türleri</p>
                            <small style="color: #666;"><i class="fas fa-layer-group"></i> Kullanılabilir</small>
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
                            <i class="fas fa-users"></i> Müşteri
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
                                    <p style="margin: 0; font-size: 0.85rem; color: #666;">Toplam gelir, ödemeler ve kar marjı analizi</p>
                                </div>
                            </div>
                            
                            <div style="background: #f8f9fa; padding: 12px; border-radius: 8px; margin-bottom: 16px;">
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                                    <div>
                                        <small style="color: #666; display: block; margin-bottom: 4px;">Toplam Gelir</small>
                                        <strong style="color: #28a745; font-size: 1.1rem;">₺245,890</strong>
                                    </div>
                                    <div>
                                        <small style="color: #666; display: block; margin-bottom: 4px;">Net Kar</small>
                                        <strong style="color: #667eea; font-size: 1.1rem;">₺198,340</strong>
                                    </div>
                                    <div>
                                        <small style="color: #666; display: block; margin-bottom: 4px;">İşlemler</small>
                                        <strong style="color: #333;">1,234</strong>
                                    </div>
                                    <div>
                                        <small style="color: #666; display: block; margin-bottom: 4px;">Ort. Sipariş</small>
                                        <strong style="color: #333;">₺199</strong>
                                    </div>
                                </div>
                            </div>
                            
                            <div style="display: flex; gap: 8px; margin-bottom: 12px;">
                                <input type="date" class="filter-select" style="flex: 1; font-size: 0.85rem;" value="2025-10-01">
                                <input type="date" class="filter-select" style="flex: 1; font-size: 0.85rem;" value="2025-10-19">
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
                                    <h3 style="margin: 0 0 8px 0; font-size: 1.1rem;">Ödeme Analizi</h3>
                                    <p style="margin: 0; font-size: 0.85rem; color: #666;">Ödeme yöntemleri, başarı oranları ve iade analizi</p>
                                </div>
                            </div>
                            
                            <div style="background: #f8f9fa; padding: 12px; border-radius: 8px; margin-bottom: 16px;">
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                                    <div>
                                        <small style="color: #666; display: block; margin-bottom: 4px;">Başarılı</small>
                                        <strong style="color: #28a745; font-size: 1.1rem;">%94.5</strong>
                                    </div>
                                    <div>
                                        <small style="color: #666; display: block; margin-bottom: 4px;">Başarısız</small>
                                        <strong style="color: #dc3545; font-size: 1.1rem;">%5.5</strong>
                                    </div>
                                    <div>
                                        <small style="color: #666; display: block; margin-bottom: 4px;">Kredi Kartı</small>
                                        <strong style="color: #333;">%68</strong>
                                    </div>
                                    <div>
                                        <small style="color: #666; display: block; margin-bottom: 4px;">Nakit</small>
                                        <strong style="color: #333;">%32</strong>
                                    </div>
                                </div>
                            </div>
                            
                            <div style="display: flex; gap: 8px; margin-bottom: 12px;">
                                <select class="filter-select" style="flex: 1; font-size: 0.85rem;">
                                    <option>Son 7 Gün</option>
                                    <option>Son 30 Gün</option>
                                    <option>Son 3 Ay</option>
                                    <option>Bu Yıl</option>
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
                                        <strong style="color: #ffc107; font-size: 1.1rem;">₺44,260</strong>
                                    </div>
                                    <div>
                                        <small style="color: #666; display: block; margin-bottom: 4px;">Gelir Vergisi</small>
                                        <strong style="color: #fd7e14; font-size: 1.1rem;">₺36,870</strong>
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
                                <select class="filter-select" style="flex: 1; font-size: 0.85rem;">
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
                                    <p style="margin: 0; font-size: 0.85rem; color: #666;">Otopark komisyonları ve ödemeler</p>
                                </div>
                            </div>
                            
                            <div style="background: #f8f9fa; padding: 12px; border-radius: 8px; margin-bottom: 16px;">
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                                    <div>
                                        <small style="color: #666; display: block; margin-bottom: 4px;">Toplam Komisyon</small>
                                        <strong style="color: #17a2b8; font-size: 1.1rem;">₺36,883</strong>
                                    </div>
                                    <div>
                                        <small style="color: #666; display: block; margin-bottom: 4px;">Ödenen</small>
                                        <strong style="color: #28a745; font-size: 1.1rem;">₺28,343</strong>
                                    </div>
                                    <div>
                                        <small style="color: #666; display: block; margin-bottom: 4px;">Bekleyen</small>
                                        <strong style="color: #ffc107;">₺8,540</strong>
                                    </div>
                                    <div>
                                        <small style="color: #666; display: block; margin-bottom: 4px;">Otopark Sayısı</small>
                                        <strong style="color: #333;">24</strong>
                                    </div>
                                </div>
                            </div>
                            
                            <div style="display: flex; gap: 8px; margin-bottom: 12px;">
                                <input type="month" class="filter-select" style="flex: 1; font-size: 0.85rem;" value="2025-10">
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
                                    <h3 style="margin: 0 0 8px 0; font-size: 1.1rem;">Sipariş Raporu</h3>
                                    <p style="margin: 0; font-size: 0.85rem; color: #666;">Tamamlanan, iptal edilen ve bekleyen siparişler</p>
                                </div>
                            </div>
                            
                            <div style="background: #f8f9fa; padding: 12px; border-radius: 8px; margin-bottom: 16px;">
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                                    <div>
                                        <small style="color: #666; display: block; margin-bottom: 4px;">Toplam Sipariş</small>
                                        <strong style="color: #667eea; font-size: 1.1rem;">1,456</strong>
                                    </div>
                                    <div>
                                        <small style="color: #666; display: block; margin-bottom: 4px;">Tamamlanan</small>
                                        <strong style="color: #28a745; font-size: 1.1rem;">1,368</strong>
                                    </div>
                                    <div>
                                        <small style="color: #666; display: block; margin-bottom: 4px;">İptal Edilen</small>
                                        <strong style="color: #dc3545;">64</strong>
                                    </div>
                                    <div>
                                        <small style="color: #666; display: block; margin-bottom: 4px;">Devam Eden</small>
                                        <strong style="color: #ffc107;">24</strong>
                                    </div>
                                </div>
                            </div>
                            
                            <div style="display: flex; gap: 8px; margin-bottom: 12px;">
                                <input type="date" class="filter-select" style="flex: 1; font-size: 0.85rem;" value="2025-10-01">
                                <input type="date" class="filter-select" style="flex: 1; font-size: 0.85rem;" value="2025-10-19">
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
                                    <h3 style="margin: 0 0 8px 0; font-size: 1.1rem;">Hizmet Performansı</h3>
                                    <p style="margin: 0; font-size: 0.85rem; color: #666;">En çok tercih edilen hizmetler ve süre analizi</p>
                                </div>
                            </div>
                            
                            <div style="background: #f8f9fa; padding: 12px; border-radius: 8px; margin-bottom: 16px;">
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                                    <div>
                                        <small style="color: #666; display: block; margin-bottom: 4px;">Aktif Hizmetler</small>
                                        <strong style="color: #28a745; font-size: 1.1rem;">34</strong>
                                    </div>
                                    <div>
                                        <small style="color: #666; display: block; margin-bottom: 4px;">Toplam Kullanım</small>
                                        <strong style="color: #667eea; font-size: 1.1rem;">2,876</strong>
                                    </div>
                                    <div>
                                        <small style="color: #666; display: block; margin-bottom: 4px;">Ort. Süre</small>
                                        <strong style="color: #333;">45 dk</strong>
                                    </div>
                                    <div>
                                        <small style="color: #666; display: block; margin-bottom: 4px;">Memnuniyet</small>
                                        <strong style="color: #ffc107;">4.7★</strong>
                                    </div>
                                </div>
                            </div>
                            
                            <div style="display: flex; gap: 8px; margin-bottom: 12px;">
                                <select class="filter-select" style="flex: 1; font-size: 0.85rem;">
                                    <option>Bu Ay</option>
                                    <option>Son 3 Ay</option>
                                    <option>Bu Yıl</option>
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
                                    <h3 style="margin: 0 0 8px 0; font-size: 1.1rem;">Otopark Performansı</h3>
                                    <p style="margin: 0; font-size: 0.85rem; color: #666;">Otopark bazlı performans ve gelir analizi</p>
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
                                        <small style="color: #666; display: block; margin-bottom: 4px;">En Yüksek Gelir</small>
                                        <strong style="color: #333;">₺45K</strong>
                                    </div>
                                    <div>
                                        <small style="color: #666; display: block; margin-bottom: 4px;">Kapasite</small>
                                        <strong style="color: #333;">%78</strong>
                                    </div>
                                </div>
                            </div>
                            
                            <div style="display: flex; gap: 8px; margin-bottom: 12px;">
                                <select class="filter-select" style="flex: 1; font-size: 0.85rem;">
                                    <option>Tüm Otoparklar</option>
                                    <option>En İyi 10</option>
                                    <option>En Düşük 10</option>
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
                        Müşteri Raporları
                    </h3>
                    
                    <div class="reports-grid" style="grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));">
                        <!-- Customer Analytics Report -->
                        <div class="report-card" style="border-left: 4px solid #17a2b8;">
                            <div style="display: flex; align-items: start; gap: 16px; margin-bottom: 16px;">
                                <div style="width: 50px; height: 50px; background: linear-gradient(135deg, #17a2b820, #00bcd420); border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                    <i class="fas fa-user-chart" style="color: #17a2b8; font-size: 1.3rem;"></i>
                                </div>
                                <div style="flex: 1;">
                                    <h3 style="margin: 0 0 8px 0; font-size: 1.1rem;">Müşteri Analizi</h3>
                                    <p style="margin: 0; font-size: 0.85rem; color: #666;">Müşteri davranışları, sadakat ve segmentasyon</p>
                                </div>
                            </div>
                            
                            <div style="background: #f8f9fa; padding: 12px; border-radius: 8px; margin-bottom: 16px;">
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                                    <div>
                                        <small style="color: #666; display: block; margin-bottom: 4px;">Toplam Müşteri</small>
                                        <strong style="color: #17a2b8; font-size: 1.1rem;">3,456</strong>
                                    </div>
                                    <div>
                                        <small style="color: #666; display: block; margin-bottom: 4px;">Aktif</small>
                                        <strong style="color: #28a745; font-size: 1.1rem;">2,134</strong>
                                    </div>
                                    <div>
                                        <small style="color: #666; display: block; margin-bottom: 4px;">Yeni (30 gün)</small>
                                        <strong style="color: #333;">287</strong>
                                    </div>
                                    <div>
                                        <small style="color: #666; display: block; margin-bottom: 4px;">Sadakat Oranı</small>
                                        <strong style="color: #ffc107;">%68</strong>
                                    </div>
                                </div>
                            </div>
                            
                            <div style="display: flex; gap: 8px; margin-bottom: 12px;">
                                <select class="filter-select" style="flex: 1; font-size: 0.85rem;">
                                    <option>Tüm Müşteriler</option>
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
                                    <h3 style="margin: 0 0 8px 0; font-size: 1.1rem;">Değerlendirme Raporu</h3>
                                    <p style="margin: 0; font-size: 0.85rem; color: #666;">Müşteri memnuniyeti ve geri bildirim analizi</p>
                                </div>
                            </div>
                            
                            <div style="background: #f8f9fa; padding: 12px; border-radius: 8px; margin-bottom: 16px;">
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                                    <div>
                                        <small style="color: #666; display: block; margin-bottom: 4px;">Ort. Puan</small>
                                        <strong style="color: #ffc107; font-size: 1.1rem;">4.6★</strong>
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
                                <input type="date" class="filter-select" style="flex: 1; font-size: 0.85rem;" value="2025-10-01">
                                <input type="date" class="filter-select" style="flex: 1; font-size: 0.85rem;" value="2025-10-19">
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
                        Performans Raporları
                    </h3>
                    
                    <div class="reports-grid" style="grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));">
                        <!-- Comprehensive Analytics Report -->
                        <div class="report-card" style="border-left: 4px solid #667eea;">
                            <div style="display: flex; align-items: start; gap: 16px; margin-bottom: 16px;">
                                <div style="width: 50px; height: 50px; background: linear-gradient(135deg, #667eea20, #764ba220); border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                                    <i class="fas fa-chart-area" style="color: #667eea; font-size: 1.3rem;"></i>
                                </div>
                                <div style="flex: 1;">
                                    <h3 style="margin: 0 0 8px 0; font-size: 1.1rem;">Kapsamlı Analiz</h3>
                                    <p style="margin: 0; font-size: 0.85rem; color: #666;">Tüm metriklerin detaylı performans raporu</p>
                                </div>
                            </div>
                            
                            <div style="background: #f8f9fa; padding: 12px; border-radius: 8px; margin-bottom: 16px;">
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px;">
                                    <div>
                                        <small style="color: #666; display: block; margin-bottom: 4px;">Büyüme Oranı</small>
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
                                <select class="filter-select" style="flex: 1; font-size: 0.85rem;">
                                    <option>Son 12 Ay</option>
                                    <option>Bu Yıl</option>
                                    <option>Geçen Yıl</option>
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
                                    <h3 style="margin: 0 0 8px 0; font-size: 1.1rem;">Yönetici Özeti</h3>
                                    <p style="margin: 0; font-size: 0.85rem; color: #666;">Üst yönetim için özet performans raporu</p>
                                </div>
                            </div>
                            
                            <div style="background: #f8f9fa; padding: 12px; border-radius: 8px; margin-bottom: 16px;">
                                <div style="display: grid; grid-template-columns: 1fr; gap: 8px;">
                                    <div style="padding: 8px; background: white; border-radius: 6px;">
                                        <small style="color: #666; display: block;">📊 Toplam Gelir</small>
                                        <strong style="color: #28a745;">₺245,890 (+18%)</strong>
                                    </div>
                                    <div style="padding: 8px; background: white; border-radius: 6px;">
                                        <small style="color: #666; display: block;">👥 Yeni Müşteriler</small>
                                        <strong style="color: #17a2b8;">287 (+24%)</strong>
                                    </div>
                                    <div style="padding: 8px; background: white; border-radius: 6px;">
                                        <small style="color: #666; display: block;">⭐ Müşteri Memnuniyeti</small>
                                        <strong style="color: #ffc107;">4.6/5.0</strong>
                                    </div>
                                </div>
                            </div>
                            
                            <div style="display: flex; gap: 8px; margin-bottom: 12px;">
                                <select class="filter-select" style="flex: 1; font-size: 0.85rem;">
                                    <option>Bu Çeyrek</option>
                                    <option>Geçen Çeyrek</option>
                                    <option>Yıllık</option>
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
                        <span><i class="fas fa-calendar-alt" style="color: #667eea; margin-right: 10px;"></i>Zamanlanmış Raporlar</span>
                        <button class="add-btn" style="padding: 8px 16px; font-size: 0.9rem;">
                            <i class="fas fa-plus"></i> Yeni Zamanlama
                        </button>
                    </h3>
                    
                    <div class="table-container">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Rapor Adı</th>
                                    <th>Periyot</th>
                                    <th>Format</th>
                                    <th>Son Çalışma</th>
                                    <th>Durum</th>
                                    <th>İşlemler</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><strong>Haftalık Gelir Raporu</strong></td>
                                    <td>Her Pazartesi 09:00</td>
                                    <td><span class="type-badge" style="background: #dc354520; color: #dc3545;"><i class="fas fa-file-pdf"></i> PDF</span></td>
                                    <td>18 Eki 2025, 09:05</td>
                                    <td><span class="status-badge active"><i class="fas fa-check"></i> Aktif</span></td>
                                    <td>
                                        <button class="action-btn edit-btn" title="Düzenle">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="action-btn view-btn" title="Çalıştır">
                                            <i class="fas fa-play"></i>
                                        </button>
                                        <button class="action-btn delete-btn" title="Sil">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <tr>
                                    <td><strong>Aylık Performans Özeti</strong></td>
                                    <td>Her ayın 1'i, 08:00</td>
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
                                    <td><strong>Günlük Sipariş Raporu</strong></td>
                                    <td>Her gün 23:00</td>
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
            <!-- Farsça: بخش اعلان‌ها. -->
            <!-- Türkçe: Bildirimler Bölümü. -->
            <!-- English: Notifications Section. -->
            <section id="notifications" class="content-section">
                <div class="section-header">
                    <div>
                        <h2><i class="fas fa-bell" style="color: #667eea; margin-right: 12px;"></i>Bildirim Yönetimi</h2>
                        <p>Kullanıcılara bildirim gönder ve geçmiş bildirimleri yönet</p>
                    </div>
                    <button class="add-btn" id="sendNotificationBtn">
                        <i class="fas fa-paper-plane"></i>
                        Yeni Bildirim Gönder
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
                            <p>Bugün Gönderilen</p>
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
                            <small class="text-orange-600">Zamanlanmış</small>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #dc354520, #c8233320);">
                            <i class="fas fa-exclamation-triangle" style="color: #dc3545;"></i>
                        </div>
                        <div class="stat-info">
                            <h3>3</h3>
                            <p>Başarısız</p>
                            <small class="text-red-600">Tekrar denenecek</small>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #28a74520, #20c99720);">
                            <i class="fas fa-users" style="color: #28a745;"></i>
                        </div>
                        <div class="stat-info">
                            <h3>1,248</h3>
                            <p>Toplam Kullanıcı</p>
                            <small class="text-green-600">Aktif alıcılar</small>
                        </div>
                    </div>
                </div>

                <!-- Notification Filters -->
                <div class="filters" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px;">
                    <input type="text" id="notificationSearch" placeholder="Konu, Mesaj Ara..." class="search-input" style="grid-column: 1 / -1;">
                    
                    <select id="notificationTypeFilter" class="filter-select">
                        <option value="">Tüm Tipler</option>
                        <option value="email">Email</option>
                        <option value="sms">SMS</option>
                        <option value="push">Push Notification</option>
                        <option value="in_app">In-App</option>
                    </select>
                    
                    <select id="notificationStatusFilter" class="filter-select">
                        <option value="">Tüm Durumlar</option>
                        <option value="sent">Gönderildi</option>
                        <option value="pending">Bekliyor</option>
                        <option value="failed">Başarısız</option>
                        <option value="scheduled">Zamanlanmış</option>
                    </select>
                    
                    <input type="date" id="notificationDateFrom" class="filter-select">
                    
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
                                <th>Gönderim Zamanı</th>
                                <th>İşlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><strong>#NOT-523</strong></td>
                                <td>Özel İndirim Kampanyası</td>
                                <td style="max-width: 300px;">
                                    <small>Tüm hizmetlerde %20 indirim! Bugün...</small>
                                </td>
                                <td>
                                    <span class="type-badge" style="background: #667eea20; color: #667eea;">
                                        <i class="fas fa-envelope"></i> Email
                                    </span>
                                </td>
                                <td>Tüm Kullanıcılar (1,248)</td>
                                <td><span class="status-badge active">Gönderildi</span></td>
                                <td>18 Eki 2025, 14:30</td>
                                <td>
                                    <button class="action-btn view-btn" title="Detay">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="action-btn edit-btn" title="Tekrar Gönder">
                                        <i class="fas fa-redo"></i>
                                    </button>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>#NOT-522</strong></td>
                                <td>Randevu Hatırlatması</td>
                                <td style="max-width: 300px;">
                                    <small>Yarınki randevunuzu unutmayın...</small>
                                </td>
                                <td>
                                    <span class="type-badge" style="background: #28a74520; color: #28a745;">
                                        <i class="fas fa-sms"></i> SMS
                                    </span>
                                </td>
                                <td>Premium Kullanıcılar (324)</td>
                                <td><span class="status-badge active">Gönderildi</span></td>
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
                                <td>Sistem Bakım Bildirimi</td>
                                <td style="max-width: 300px;">
                                    <small>Yarın 02:00 - 04:00 arası sistem...</small>
                                </td>
                                <td>
                                    <span class="type-badge" style="background: #ffc10720; color: #ff8c00;">
                                        <i class="fas fa-bell"></i> Push
                                    </span>
                                </td>
                                <td>Tüm Kullanıcılar (1,248)</td>
                                <td><span class="status-badge" style="background: #ffc10720; color: #ff8c00;">Zamanlanmış</span></td>
                                <td>19 Eki 2025, 01:00</td>
                                <td>
                                    <button class="action-btn view-btn">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button class="action-btn delete-btn" title="İptal Et">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <!-- CMS Section -->
            <!-- Farsça: بخش مدیریت محتوا. -->
            <!-- Türkçe: İçerik Yönetimi Bölümü. -->
            <!-- English: CMS Section. -->
            <section id="cms" class="content-section">
                <div class="section-header">
                    <div>
                        <h2><i class="fas fa-file-alt" style="color: #764ba2; margin-right: 12px;"></i>İçerik Yönetimi (CMS)</h2>
                        <p>Web sitesi sayfalarını ve içeriklerini yönet</p>
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
                            <small class="text-purple-600">Yayında</small>
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
                            <p>Medya Dosyası</p>
                            <small class="text-blue-600">Kütüphane</small>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #28a74520, #20c99720);">
                            <i class="fas fa-chart-line" style="color: #28a745;"></i>
                        </div>
                        <div class="stat-info">
                            <h3>8,542</h3>
                            <p>Toplam Görüntüleme</p>
                            <small class="text-green-600">Bu ay</small>
                        </div>
                    </div>
                </div>

                <!-- CMS Filters -->
                <div class="filters" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px;">
                    <input type="text" id="cmsSearch" placeholder="Sayfa Ara..." class="search-input" style="grid-column: 1 / -1;">
                    
                    <select id="cmsStatusFilter" class="filter-select">
                        <option value="">Tüm Durumlar</option>
                        <option value="published">Yayında</option>
                        <option value="draft">Taslak</option>
                        <option value="scheduled">Zamanlanmış</option>
                    </select>
                    
                    <select id="cmsTypeFilter" class="filter-select">
                        <option value="">Tüm Tipler</option>
                        <option value="page">Sayfa</option>
                        <option value="post">Blog Yazısı</option>
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
                                <th>Sayfa Başlığı</th>
                                <th>URL</th>
                                <th>Tip</th>
                                <th>Durum</th>
                                <th>Son Güncelleme</th>
                                <th>Görüntülenme</th>
                                <th>İşlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><strong>#1</strong></td>
                                <td>
                                    <div>
                                        <strong>Hakkımızda</strong><br>
                                        <small style="color: #64748b;">Şirket bilgileri ve tarihçe</small>
                                    </div>
                                </td>
                                <td><code>/hakkimizda</code></td>
                                <td><span class="type-badge" style="background: #667eea20; color: #667eea;">Sayfa</span></td>
                                <td><span class="status-badge active">Yayında</span></td>
                                <td>17 Eki 2025</td>
                                <td>2,348</td>
                                <td>
                                    <button class="action-btn edit-btn" title="Düzenle">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button class="action-btn view-btn" title="Önizle">
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
                                        <strong>İletişim</strong><br>
                                        <small style="color: #64748b;">İletişim formu ve bilgiler</small>
                                    </div>
                                </td>
                                <td><code>/iletisim</code></td>
                                <td><span class="type-badge" style="background: #667eea20; color: #667eea;">Sayfa</span></td>
                                <td><span class="status-badge active">Yayında</span></td>
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
                                        <strong>Gizlilik Politikası</strong><br>
                                        <small style="color: #64748b;">KVKK ve gizlilik kuralları</small>
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
                                    <button class="action-btn" style="background: #28a74520; color: #28a745;" title="Yayınla">
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
                        Medya Kütüphanesi
                        <button class="add-btn" style="margin-left: auto; padding: 8px 16px; font-size: 0.875rem;">
                            <i class="fas fa-upload"></i> Dosya Yükle
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
                            <p style="font-size: 0.875rem; color: #64748b;">Yeni Yükle</p>
                            <small style="color: #94a3b8;">Dosya Ekle</small>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Security & Logs Section -->
            <!-- Farsça: بخش امنیت و لاگ‌ها. -->
            <!-- Türkçe: Güvenlik & Loglar Bölümü. -->
            <!-- English: Security & Logs Section. -->
            <section id="security" class="content-section">
                <div class="section-header">
                    <div>
                        <h2><i class="fas fa-shield-alt" style="color: #28a745; margin-right: 12px;"></i>Güvenlik & Sistem Logları</h2>
                        <p>Sistem güvenliğini izle ve denetim kayıtlarını incele</p>
                    </div>
                    <button class="add-btn" style="background: linear-gradient(135deg, #28a745, #20c997);">
                        <i class="fas fa-download"></i>
                        Log Dışa Aktar
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
                            <p>Başarısız Giriş</p>
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
                            <small class="text-blue-600">Şu an online</small>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #28a74520, #20c99720);">
                            <i class="fas fa-clipboard-list" style="color: #28a745;"></i>
                        </div>
                        <div class="stat-info">
                            <h3>1,248</h3>
                            <p>Denetim Kaydı</p>
                            <small class="text-green-600">Bugün</small>
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-icon" style="background: linear-gradient(135deg, #ffc10720, #fd7e1420);">
                            <i class="fas fa-database" style="color: #ffc107;"></i>
                        </div>
                        <div class="stat-info">
                            <h3>2 saat önce</h3>
                            <p>Son Yedekleme</p>
                            <small class="text-orange-600">Otomatik</small>
                        </div>
                    </div>
                </div>

                <!-- Security Tabs -->
                <div style="margin-bottom: 24px;">
                    <div style="display: flex; gap: 8px; border-bottom: 2px solid #e9ecef; padding-bottom: 8px;">
                        <button class="tab-btn active" data-tab="audit-logs" style="padding: 12px 24px; background: linear-gradient(135deg, #667eea, #764ba2); color: white; border: none; border-radius: 8px 8px 0 0; cursor: pointer; font-weight: 500;">
                            Denetim Logları
                        </button>
                        <button class="tab-btn" data-tab="login-logs" style="padding: 12px 24px; background: white; color: #333; border: none; border-radius: 8px 8px 0 0; cursor: pointer; font-weight: 500;">
                            Giriş Logları
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
                        <input type="text" id="auditSearch" placeholder="Kullanıcı, Aksiyon Ara..." class="search-input" style="grid-column: 1 / -1;">
                        
                        <select id="auditActionFilter" class="filter-select">
                            <option value="">Tüm Aksiyonlar</option>
                            <option value="create">Oluşturma</option>
                            <option value="update">Güncelleme</option>
                            <option value="delete">Silme</option>
                            <option value="login">Giriş</option>
                            <option value="logout">Çıkış</option>
                        </select>
                        
                        <select id="auditEntityFilter" class="filter-select">
                            <option value="">Tüm Varlıklar</option>
                            <option value="user">Kullanıcı</option>
                            <option value="order">Sipariş</option>
                            <option value="payment">Ödeme</option>
                            <option value="service">Hizmet</option>
                        </select>
                        
                        <input type="date" id="auditDateFrom" class="filter-select">
                        
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
                                    <th>Kullanıcı</th>
                                    <th>Aksiyon</th>
                                    <th>Varlık</th>
                                    <th>Açıklama</th>
                                    <th>IP Adresi</th>
                                    <th>Zaman</th>
                                    <th>Detay</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><strong>#AL-8542</strong></td>
                                    <td>Admin Kullanıcı</td>
                                    <td>
                                        <span class="type-badge" style="background: #28a74520; color: #28a745;">
                                            <i class="fas fa-plus"></i> CREATE
                                        </span>
                                    </td>
                                    <td>User #245</td>
                                    <td>Yeni kullanıcı oluşturuldu</td>
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
                                    <td>Admin Kullanıcı</td>
                                    <td>
                                        <span class="type-badge" style="background: #667eea20; color: #667eea;">
                                            <i class="fas fa-edit"></i> UPDATE
                                        </span>
                                    </td>
                                    <td>Service #12</td>
                                    <td>Hizmet fiyatı güncellendi</td>
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
                                    <th>Kullanıcı</th>
                                    <th>Durum</th>
                                    <th>IP Adresi</th>
                                    <th>Tarayıcı</th>
                                    <th>Konum</th>
                                    <th>Zaman</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><strong>#LG-4523</strong></td>
                                    <td>admin@otoparkdemotime.com</td>
                                    <td><span class="status-badge active">Başarılı</span></td>
                                    <td><code>192.168.1.100</code></td>
                                    <td>Chrome 119.0</td>
                                    <td>İstanbul, Türkiye</td>
                                    <td>18 Eki 2025, 09:30</td>
                                </tr>
                                <tr>
                                    <td><strong>#LG-4522</strong></td>
                                    <td>support@otoparkdemotime.com</td>
                                    <td><span class="status-badge active">Başarılı</span></td>
                                    <td><code>192.168.1.105</code></td>
                                    <td>Firefox 118.0</td>
                                    <td>Ankara, Türkiye</td>
                                    <td>18 Eki 2025, 08:15</td>
                                </tr>
                                <tr>
                                    <td><strong>#LG-4521</strong></td>
                                    <td>unknown@example.com</td>
                                    <td><span class="status-badge inactive">Başarısız</span></td>
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
                                    <th>Kullanıcı</th>
                                    <th>IP Adresi</th>
                                    <th>Tarayıcı</th>
                                    <th>Son Aktivite</th>
                                    <th>Oturum Süresi</th>
                                    <th>İşlemler</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Admin Kullanıcı</td>
                                    <td><code>192.168.1.100</code></td>
                                    <td>Chrome 119.0</td>
                                    <td>2 dakika önce</td>
                                    <td>5 saat 12 dk</td>
                                    <td>
                                        <button class="action-btn delete-btn" title="Oturumu Sonlandır">
                                            <i class="fas fa-sign-out-alt"></i>
                                        </button>
                                    </td>
                                </tr>
                                <tr>
                                    <td>Support User</td>
                                    <td><code>192.168.1.105</code></td>
                                    <td>Firefox 118.0</td>
                                    <td>15 dakika önce</td>
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
                            <h4 style="margin: 0 0 8px 0;">Veritabanı Yedekleme</h4>
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
                                    <th>Dosya Adı</th>
                                    <th>Boyut</th>
                                    <th>Tip</th>
                                    <th>Oluşturulma</th>
                                    <th>İşlemler</th>
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
                                        <button class="action-btn view-btn" title="İndir">
                                            <i class="fas fa-download"></i>
                                        </button>
                                        <button class="action-btn edit-btn" title="Geri Yükle">
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
            <!-- Farsça: بخش تنظیمات. -->
            <!-- Türkçe: Ayarlar Bölümü. -->
            <!-- English: Settings Section. -->
            <section id="settings" class="content-section">
                <div class="section-header">
                    <div>
                        <h2><i class="fas fa-cog" style="color: #667eea; margin-right: 12px;"></i>Sistem Ayarları</h2>
                        <p>Tüm sistem ayarlarını yapılandır ve yönet</p>
                    </div>
                    <button class="add-btn" style="background: linear-gradient(135deg, #28a745, #20c997);">
                        <i class="fas fa-save"></i>
                        Tüm Ayarları Kaydet
                    </button>
                </div>

                <!-- Settings Tabs -->
                <div style="margin-bottom: 24px;">
                    <div style="display: flex; gap: 8px; border-bottom: 2px solid #e9ecef; padding-bottom: 8px; flex-wrap: wrap;">
                        <button class="settings-tab-btn active" data-settings-tab="general" style="padding: 12px 20px; background: linear-gradient(135deg, #667eea, #764ba2); color: white; border: none; border-radius: 8px 8px 0 0; cursor: pointer; font-weight: 500; transition: all 0.3s;">
                            <i class="fas fa-globe"></i> Genel
                        </button>
                        <button class="settings-tab-btn" data-settings-tab="payment" style="padding: 12px 20px; background: white; color: #333; border: none; border-radius: 8px 8px 0 0; cursor: pointer; font-weight: 500; transition: all 0.3s;">
                            <i class="fas fa-credit-card"></i> Ödeme
                        </button>
                        <button class="settings-tab-btn" data-settings-tab="notifications" style="padding: 12px 20px; background: white; color: #333; border: none; border-radius: 8px 8px 0 0; cursor: pointer; font-weight: 500; transition: all 0.3s;">
                            <i class="fas fa-bell"></i> Bildirimler
                        </button>
                        <button class="settings-tab-btn" data-settings-tab="rbac" style="padding: 12px 20px; background: white; color: #333; border: none; border-radius: 8px 8px 0 0; cursor: pointer; font-weight: 500; transition: all 0.3s;">
                            <i class="fas fa-user-shield"></i> RBAC
                        </button>
                        <button class="settings-tab-btn" data-settings-tab="security" style="padding: 12px 20px; background: white; color: #333; border: none; border-radius: 8px 8px 0 0; cursor: pointer; font-weight: 500; transition: all 0.3s;">
                            <i class="fas fa-shield-alt"></i> Güvenlik
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
                        <label>Site Adı *</label>
                        <input type="text" value="CarWash Yönetim Sistemi" placeholder="Site adını girin">
                        <small style="color: #64748b;">Web sitesinde görünecek isim</small>
                    </div>
                    <div class="form-group">
                        <label>Admin Email *</label>
                        <input type="email" value="admin@otoparkdemotime.com" placeholder="admin@example.com">
                        <small style="color: #64748b;">Sistem bildirimleri bu adrese gönderilecek</small>
                    </div>
                    <div class="form-group">
                        <label>Saat Dilimi</label>
                        <select>
                            <option value="Europe/Istanbul" selected>Europe/Istanbul (GMT+3)</option>
                            <option value="UTC">UTC (GMT+0)</option>
                            <option value="Europe/London">Europe/London (GMT+0)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Dil</label>
                        <select>
                            <option value="tr" selected>Türkçe</option>
                            <option value="en">English</option>
                            <option value="fa">فارسی</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Para Birimi</label>
                        <select>
                            <option value="TRY" selected>₺ Türk Lirası (TRY)</option>
                            <option value="USD">$ US Dollar (USD)</option>
                            <option value="EUR">€ Euro (EUR)</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                            <input type="checkbox" checked style="width: auto; margin: 0;">
                            <span>Bakım Modu</span>
                        </label>
                        <small style="color: #64748b;">Aktif olduğunda site ziyaretçilere kapalı olacak</small>
                    </div>
                    <button class="save-btn" style="margin-top: 1rem;">
                        <i class="fas fa-save"></i> Kaydet
                    </button>
                </div>

                <!-- Payment Settings Tab -->
                <div id="payment" class="settings-tab-content" style="display: none; background: white; padding: 2rem; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.08);">
                    <h3 style="margin-bottom: 1.5rem; color: #333; display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-credit-card"></i> Ödeme Ayarları
                    </h3>
                    <div class="form-group">
                        <label>Komisyon Oranı (%)</label>
                        <input type="number" value="15" min="0" max="100" step="0.1">
                        <small style="color: #64748b;">Platform komisyon yüzdesi</small>
                    </div>
                    <div class="form-group">
                        <label>Minimum Ödeme Tutarı (₺)</label>
                        <input type="number" value="50" min="0">
                        <small style="color: #64748b;">Tasfiye için minimum tutar</small>
                    </div>
                    
                    <h4 style="margin: 2rem 0 1rem 0; color: #333;">Ödeme Ağ Geçitleri</h4>
                    
                    <!-- Stripe -->
                    <div style="border: 2px solid #e9ecef; border-radius: 8px; padding: 1.5rem; margin-bottom: 1rem;">
                        <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 1rem;">
                            <i class="fab fa-stripe" style="font-size: 2rem; color: #635bff;"></i>
                            <div style="flex: 1;">
                                <h5 style="margin: 0;">Stripe</h5>
                                <small style="color: #64748b;">Kredi kartı ödemeleri</small>
                            </div>
                            <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; margin: 0;">
                                <input type="checkbox" checked style="width: auto; margin: 0;">
                                <span>Aktif</span>
                            </label>
                        </div>
                        <div class="form-group" style="margin-bottom: 1rem;">
                            <label>Publishable Key</label>
                            <input type="text" placeholder="pk_live_...">
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label>Secret Key</label>
                            <input type="password" placeholder="sk_live_...">
                        </div>
                    </div>

                    <!-- PayPal -->
                    <div style="border: 2px solid #e9ecef; border-radius: 8px; padding: 1.5rem; margin-bottom: 1rem;">
                        <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 1rem;">
                            <i class="fab fa-paypal" style="font-size: 2rem; color: #00457c;"></i>
                            <div style="flex: 1;">
                                <h5 style="margin: 0;">PayPal</h5>
                                <small style="color: #64748b;">PayPal ödemeleri</small>
                            </div>
                            <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; margin: 0;">
                                <input type="checkbox" style="width: auto; margin: 0;">
                                <span>Aktif</span>
                            </label>
                        </div>
                        <div class="form-group" style="margin-bottom: 1rem;">
                            <label>Client ID</label>
                            <input type="text" placeholder="AXxxx...">
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label>Secret Key</label>
                            <input type="password" placeholder="ECxxx...">
                        </div>
                    </div>

                    <!-- iyzico -->
                    <div style="border: 2px solid #e9ecef; border-radius: 8px; padding: 1.5rem;">
                        <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 1rem;">
                            <i class="fas fa-credit-card" style="font-size: 2rem; color: #ff6600;"></i>
                            <div style="flex: 1;">
                                <h5 style="margin: 0;">iyzico</h5>
                                <small style="color: #64748b;">Türkiye kredi kartı ödemeleri</small>
                            </div>
                            <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; margin: 0;">
                                <input type="checkbox" checked style="width: auto; margin: 0;">
                                <span>Aktif</span>
                            </label>
                        </div>
                        <div class="form-group" style="margin-bottom: 1rem;">
                            <label>API Key</label>
                            <input type="text" placeholder="sandbox-xxx...">
                        </div>
                        <div class="form-group" style="margin-bottom: 0;">
                            <label>Secret Key</label>
                            <input type="password" placeholder="sandbox-xxx...">
                        </div>
                    </div>

                    <button class="save-btn" style="margin-top: 1.5rem;">
                        <i class="fas fa-save"></i> Kaydet
                    </button>
                </div>

                <!-- Notifications Settings Tab -->
                <div id="notificationstab" class="settings-tab-content" style="display: none; background: white; padding: 2rem; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.08);">
                    <h3 style="margin-bottom: 1.5rem; color: #333; display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-bell"></i> Bildirim Ayarları
                    </h3>
                    
                    <!-- Email Notifications -->
                    <h4 style="margin: 1.5rem 0 1rem 0;">Email Bildirimleri (SMTP)</h4>
                    <div class="form-group">
                        <label>SMTP Host</label>
                        <input type="text" value="smtp.gmail.com" placeholder="smtp.example.com">
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div class="form-group">
                            <label>SMTP Port</label>
                            <input type="number" value="587">
                        </div>
                        <div class="form-group">
                            <label>Encryption</label>
                            <select>
                                <option value="tls" selected>TLS</option>
                                <option value="ssl">SSL</option>
                                <option value="none">None</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>SMTP Username</label>
                        <input type="text" value="no-reply@otoparkdemotime.com">
                    </div>
                    <div class="form-group">
                        <label>SMTP Password</label>
                        <input type="password" placeholder="••••••••">
                    </div>
                    
                    <!-- SMS Notifications -->
                    <h4 style="margin: 2rem 0 1rem 0;">SMS Bildirimleri (Twilio)</h4>
                    <div class="form-group">
                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                            <input type="checkbox" checked style="width: auto; margin: 0;">
                            <span>SMS Bildirimlerini Aktif Et</span>
                        </label>
                    </div>
                    <div class="form-group">
                        <label>Twilio Account SID</label>
                        <input type="text" placeholder="ACxxxxxxxxxx">
                    </div>
                    <div class="form-group">
                        <label>Twilio Auth Token</label>
                        <input type="password" placeholder="••••••••">
                    </div>
                    <div class="form-group">
                        <label>Gönderen Numara</label>
                        <input type="tel" value="+905551234567">
                    </div>
                    
                    <!-- Push Notifications -->
                    <h4 style="margin: 2rem 0 1rem 0;">Push Notifications (Firebase)</h4>
                    <div class="form-group">
                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                            <input type="checkbox" style="width: auto; margin: 0;">
                            <span>Push Notification'ları Aktif Et</span>
                        </label>
                    </div>
                    <div class="form-group">
                        <label>Firebase Server Key</label>
                        <input type="password" placeholder="••••••••">
                    </div>
                    
                    <button class="save-btn" style="margin-top: 1rem;">
                        <i class="fas fa-save"></i> Kaydet
                    </button>
                </div>

                <!-- RBAC Settings Tab -->
                <div id="rbac" class="settings-tab-content" style="display: none; background: white; padding: 2rem; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.08);">
                    <h3 style="margin-bottom: 1.5rem; color: #333; display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-user-shield"></i> Rol ve İzin Yönetimi (RBAC)
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
                                            <small style="color: #64748b;">Seviye: 80 - Yönetici</small>
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
                                            <small style="color: #64748b;">Seviye: 60 - Müdür</small>
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
                                            <small style="color: #64748b;">Seviye: 20 - Denetçi</small>
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
                            <h4 style="margin-bottom: 1rem;">İzin Kategorileri</h4>
                            <div style="display: flex; flex-direction: column; gap: 1rem;">
                                <div style="border: 1px solid #e9ecef; border-radius: 8px; padding: 1rem;">
                                    <h5 style="margin: 0 0 0.5rem 0; display: flex; align-items: center; gap: 8px;">
                                        <i class="fas fa-users" style="color: #667eea;"></i>
                                        Kullanıcı İzinleri
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
                                        Sipariş İzinleri
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
                                        Ödeme İzinleri
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
                                        Sistem İzinleri
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
                        <i class="fas fa-shield-alt"></i> Güvenlik Ayarları
                    </h3>
                    
                    <div class="form-group">
                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                            <input type="checkbox" checked style="width: auto; margin: 0;">
                            <span>İki Faktörlü Kimlik Doğrulama (2FA) Zorunlu</span>
                        </label>
                        <small style="color: #64748b;">Tüm admin kullanıcıları için 2FA zorunlu olacak</small>
                    </div>
                    
                    <div class="form-group">
                        <label>Oturum Zaman Aşımı (dakika)</label>
                        <input type="number" value="60" min="5" max="1440">
                        <small style="color: #64748b;">İşlem yapılmadığında otomatik çıkış süresi</small>
                    </div>
                    
                    <div class="form-group">
                        <label>Maksimum Başarısız Giriş Denemesi</label>
                        <input type="number" value="5" min="3" max="10">
                        <small style="color: #64748b;">Bu sayıda başarısız girişten sonra hesap kilitlenecek</small>
                    </div>
                    
                    <div class="form-group">
                        <label>Hesap Kilitleme Süresi (dakika)</label>
                        <input type="number" value="30" min="5" max="1440">
                    </div>
                    
                    <div class="form-group">
                        <label>Minimum Şifre Uzunluğu</label>
                        <input type="number" value="8" min="6" max="20">
                    </div>
                    
                    <div class="form-group">
                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                            <input type="checkbox" checked style="width: auto; margin: 0;">
                            <span>Şifre Karmaşıklık Kuralları</span>
                        </label>
                        <small style="color: #64748b;">Büyük/küçük harf, rakam ve özel karakter gerektirir</small>
                    </div>
                    
                    <div class="form-group">
                        <label>Şifre Değiştirme Periyodu (gün)</label>
                        <input type="number" value="90" min="0" max="365">
                        <small style="color: #64748b;">0 girerek devre dışı bırakabilirsiniz</small>
                    </div>
                    
                    <h4 style="margin: 2rem 0 1rem 0;">IP Beyaz Listesi</h4>
                    <div class="form-group">
                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                            <input type="checkbox" style="width: auto; margin: 0;">
                            <span>IP Kısıtlaması Aktif</span>
                        </label>
                        <small style="color: #64748b;">Sadece belirlenen IP adreslerinden erişime izin ver</small>
                    </div>
                    
                    <div class="form-group">
                        <label>İzin Verilen IP Adresleri (her satırda bir IP)</label>
                        <textarea rows="5" placeholder="192.168.1.1&#10;192.168.1.2&#10;10.0.0.1"></textarea>
                    </div>
                    
                    <button class="save-btn" style="margin-top: 1rem;">
                        <i class="fas fa-save"></i> Kaydet
                    </button>
                </div>

                <!-- Backup Settings Tab -->
                <div id="backuptab" class="settings-tab-content" style="display: none; background: white; padding: 2rem; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.08);">
                    <h3 style="margin-bottom: 1.5rem; color: #333; display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-database"></i> Yedekleme Ayarları
                    </h3>
                    
                    <div class="form-group">
                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                            <input type="checkbox" checked style="width: auto; margin: 0;">
                            <span>Otomatik Yedekleme Aktif</span>
                        </label>
                        <small style="color: #64748b;">Belirlenen zamanlarda otomatik yedek alınacak</small>
                    </div>
                    
                    <div class="form-group">
                        <label>Yedekleme Sıklığı</label>
                        <select>
                            <option value="hourly">Saatlik</option>
                            <option value="daily" selected>Günlük</option>
                            <option value="weekly">Haftalık</option>
                            <option value="monthly">Aylık</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Yedekleme Saati</label>
                        <input type="time" value="02:00">
                        <small style="color: #64748b;">Yedekleme işleminin yapılacağı saat</small>
                    </div>
                    
                    <div class="form-group">
                        <label>Yedek Saklama Süresi (gün)</label>
                        <input type="number" value="30" min="1" max="365">
                        <small style="color: #64748b;">Bu süreden eski yedekler otomatik silinecek</small>
                    </div>
                    
                    <div class="form-group">
                        <label>Maksimum Yedek Sayısı</label>
                        <input type="number" value="10" min="1" max="100">
                    </div>
                    
                    <h4 style="margin: 2rem 0 1rem 0;">Yedek Depolama</h4>
                    
                    <div class="form-group">
                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                            <input type="checkbox" checked style="width: auto; margin: 0;">
                            <span>Yerel Sunucuda Sakla</span>
                        </label>
                    </div>
                    
                    <div class="form-group">
                        <label>Yerel Yedek Klasörü</label>
                        <input type="text" value="/var/backups/carwash">
                    </div>
                    
                    <div class="form-group">
                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                            <input type="checkbox" style="width: auto; margin: 0;">
                            <span>Uzak Sunucuya Yükle (FTP/SFTP)</span>
                        </label>
                    </div>
                    
                    <div class="form-group">
                        <label>FTP Host</label>
                        <input type="text" placeholder="ftp.example.com">
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div class="form-group">
                            <label>FTP Username</label>
                            <input type="text" placeholder="username">
                        </div>
                        <div class="form-group">
                            <label>FTP Password</label>
                            <input type="password" placeholder="••••••••">
                        </div>
                    </div>
                    
                    <button class="save-btn" style="margin-top: 1rem;">
                        <i class="fas fa-save"></i> Kaydet
                    </button>
                </div>

                <!-- Email Templates Tab -->
                <div id="emailtab" class="settings-tab-content" style="display: none; background: white; padding: 2rem; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.08);">
                    <h3 style="margin-bottom: 1.5rem; color: #333; display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-envelope"></i> Email Şablonları
                    </h3>
                    
                    <div style="display: flex; flex-direction: column; gap: 1rem;">
                        <!-- Welcome Email -->
                        <div style="border: 1px solid #e9ecef; border-radius: 8px; padding: 1.5rem;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                                <div>
                                    <h5 style="margin: 0;">Hoş Geldin Emaili</h5>
                                    <small style="color: #64748b;">Yeni kullanıcı kaydında gönderilen email</small>
                                </div>
                                <button class="action-btn edit-btn">
                                    <i class="fas fa-edit"></i> Düzenle
                                </button>
                            </div>
                            <div class="form-group" style="margin-bottom: 0.5rem;">
                                <label>Konu</label>
                                <input type="text" value="CarWash'a Hoş Geldiniz!">
                            </div>
                            <div class="form-group" style="margin-bottom: 0;">
                                <label>Gövde</label>
                                <textarea rows="3" readonly>Merhaba {{user_name}}, CarWash ailesine hoş geldiniz!</textarea>
                            </div>
                        </div>
                        
                        <!-- Order Confirmation -->
                        <div style="border: 1px solid #e9ecef; border-radius: 8px; padding: 1.5rem;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                                <div>
                                    <h5 style="margin: 0;">Sipariş Onayı</h5>
                                    <small style="color: #64748b;">Sipariş oluşturulduğunda gönderilen email</small>
                                </div>
                                <button class="action-btn edit-btn">
                                    <i class="fas fa-edit"></i> Düzenle
                                </button>
                            </div>
                            <div class="form-group" style="margin-bottom: 0.5rem;">
                                <label>Konu</label>
                                <input type="text" value="Siparişiniz Alındı - #{{order_id}}">
                            </div>
                            <div class="form-group" style="margin-bottom: 0;">
                                <label>Gövde</label>
                                <textarea rows="3" readonly>Siparişiniz başarıyla alındı. Sipariş No: {{order_id}}</textarea>
                            </div>
                        </div>
                        
                        <!-- Password Reset -->
                        <div style="border: 1px solid #e9ecef; border-radius: 8px; padding: 1.5rem;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                                <div>
                                    <h5 style="margin: 0;">Şifre Sıfırlama</h5>
                                    <small style="color: #64748b;">Şifre sıfırlama talebi için email</small>
                                </div>
                                <button class="action-btn edit-btn">
                                    <i class="fas fa-edit"></i> Düzenle
                                </button>
                            </div>
                            <div class="form-group" style="margin-bottom: 0.5rem;">
                                <label>Konu</label>
                                <input type="text" value="Şifre Sıfırlama Talebi">
                            </div>
                            <div class="form-group" style="margin-bottom: 0;">
                                <label>Gövde</label>
                                <textarea rows="3" readonly>Şifrenizi sıfırlamak için aşağıdaki linke tıklayın: {{reset_link}}</textarea>
                            </div>
                        </div>
                    </div>
                    
                    <div style="margin-top: 1.5rem; padding: 1rem; background: #667eea10; border-radius: 8px; border-left: 4px solid #667eea;">
                        <p style="margin: 0; color: #333;"><strong>Kullanılabilir Değişkenler:</strong></p>
                        <code style="display: block; margin-top: 0.5rem; font-size: 0.875rem;">
                            {{user_name}}, {{user_email}}, {{order_id}}, {{service_name}}, {{price}}, {{date}}, {{time}}, {{reset_link}}
                        </code>
                    </div>
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

<!-- Add Service Modal -->
    <!-- Farsça: مودال افزودن خدمات. -->
    <!-- Türkçe: Hizmet Ekle Modalı. -->
    <!-- English: Add Service Modal. -->
    <div id="serviceModal" class="modal">
        <div class="modal-content" style="max-width: 700px;">
            <div class="modal-header">
                <h3><i class="fas fa-concierge-bell mr-2"></i>Yeni Hizmet Ekle</h3>
                <span class="close" id="closeServiceModal">&times;</span>
            </div>
            <div class="modal-body">
                <form id="serviceForm">
                    <div class="form-group">
                        <label><i class="fas fa-tag mr-1"></i>Hizmet Adı *</label>
                        <input type="text" id="serviceName" name="service_name" placeholder="Örn: Dış Yıkama" required>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-align-left mr-1"></i>Açıklama</label>
                        <textarea id="serviceDescription" name="description" rows="3" placeholder="Hizmet açıklaması..."></textarea>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div class="form-group">
                            <label><i class="fas fa-layer-group mr-1"></i>Kategori *</label>
                            <select id="serviceCategory" name="category" required>
                                <option value="">Kategori Seçin</option>
                                <option value="wash">Yıkama</option>
                                <option value="detail">Detaylı Bakım</option>
                                <option value="polish">Cilalama & Koruma</option>
                                <option value="interior">İç Temizlik</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fas fa-clock mr-1"></i>Süre (dakika) *</label>
                            <input type="number" id="serviceDuration" name="duration" min="1" placeholder="30" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-car mr-1"></i>Araç Tipi Fiyatlandırması *</label>
                        <div style="background: #f8f9fa; padding: 1rem; border-radius: 8px; margin-top: 0.5rem;">
                            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 1rem; margin-bottom: 0.5rem;">
                                <div>
                                    <label style="font-size: 0.85rem; color: #666; font-weight: normal;">Sedan (₺) *</label>
                                    <input type="number" id="priceSedan" name="price_sedan" min="0" step="0.01" placeholder="150" required style="margin-top: 0.25rem;">
                                </div>
                                <div>
                                    <label style="font-size: 0.85rem; color: #666; font-weight: normal;">SUV (₺) *</label>
                                    <input type="number" id="priceSUV" name="price_suv" min="0" step="0.01" placeholder="180" required style="margin-top: 0.25rem;">
                                </div>
                                <div>
                                    <label style="font-size: 0.85rem; color: #666; font-weight: normal;">Kamyonet (₺) *</label>
                                    <input type="number" id="priceTruck" name="price_truck" min="0" step="0.01" placeholder="200" required style="margin-top: 0.25rem;">
                                </div>
                            </div>
                            <small style="color: #666; font-size: 0.8rem;">
                                <i class="fas fa-info-circle"></i> Her araç tipi için farklı fiyat belirleyebilirsiniz
                            </small>
                        </div>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div class="form-group">
                            <label><i class="fas fa-sort-numeric-up mr-1"></i>Sıralama</label>
                            <input type="number" id="serviceOrder" name="sort_order" min="1" placeholder="1" value="1">
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fas fa-toggle-on mr-1"></i>Durum *</label>
                            <select id="serviceStatus" name="status" required>
                                <option value="active">Aktif</option>
                                <option value="inactive">Pasif</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-icons mr-1"></i>İkon (Font Awesome sınıfı)</label>
                        <input type="text" id="serviceIcon" name="icon" placeholder="fas fa-car" value="fas fa-car">
                        <small style="color: #666; font-size: 0.8rem; display: block; margin-top: 0.25rem;">
                            <i class="fas fa-lightbulb"></i> Örnek: fas fa-car, fas fa-broom, fas fa-star, fas fa-shield-alt
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
    <!-- Farsça: مودال افزودن تیکت. -->
    <!-- Türkçe: Destek Talebi Ekle Modalı. -->
    <!-- English: Add Ticket Modal. -->
    <div id="ticketModal" class="modal">
        <div class="modal-content" style="max-width: 650px;">
            <div class="modal-header" style="background: linear-gradient(135deg, #fd7e14, #dc3545);">
                <h3><i class="fas fa-ticket-alt mr-2"></i>Yeni Destek Talebi Oluştur</h3>
                <span class="close" id="closeTicketModal">&times;</span>
            </div>
            <div class="modal-body">
                <form id="ticketForm">
                    <div class="form-group">
                        <label><i class="fas fa-user mr-1"></i>Müşteri Seçin *</label>
                        <select id="ticketCustomer" name="customer_id" required>
                            <option value="">Müşteri Seçin</option>
                            <option value="1">Ahmet Yılmaz - ahmet@email.com</option>
                            <option value="2">Elif Kara - elif@email.com</option>
                            <option value="3">Mehmet Demir - mehmet@email.com</option>
                            <option value="4">Zeynep Öztürk - zeynep@email.com</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-heading mr-1"></i>Konu *</label>
                        <input type="text" id="ticketSubject" name="subject" placeholder="Talep konusu..." required>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div class="form-group">
                            <label><i class="fas fa-tag mr-1"></i>Kategori *</label>
                            <select id="ticketCategory" name="category" required>
                                <option value="">Kategori Seçin</option>
                                <option value="technical">Teknik Destek</option>
                                <option value="billing">Ödeme & Fatura</option>
                                <option value="service">Hizmet Soruları</option>
                                <option value="complaint">Şikayet</option>
                                <option value="other">Diğer</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fas fa-exclamation-circle mr-1"></i>Öncelik *</label>
                            <select id="ticketPriority" name="priority" required>
                                <option value="">Öncelik Seçin</option>
                                <option value="low">Düşük</option>
                                <option value="medium" selected>Orta</option>
                                <option value="high">Yüksek</option>
                                <option value="urgent">Acil</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-align-left mr-1"></i>Mesaj *</label>
                        <textarea id="ticketMessage" name="message" rows="5" placeholder="Talep detaylarını yazın..." required></textarea>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                        <div class="form-group">
                            <label><i class="fas fa-user-tag mr-1"></i>Atanan Kişi</label>
                            <select id="ticketAssignedTo" name="assigned_to">
                                <option value="">Atama Yapılmadı</option>
                                <option value="1">Destek Ekibi - Ahmet</option>
                                <option value="2">Destek Ekibi - Ayşe</option>
                                <option value="3">Destek Ekibi - Mehmet</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fas fa-flag mr-1"></i>Durum</label>
                            <select id="ticketStatus" name="status">
                                <option value="new" selected>Yeni</option>
                                <option value="open">Açık</option>
                                <option value="in_progress">Devam Ediyor</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label><i class="fas fa-paperclip mr-1"></i>Dosya Ekle (Opsiyonel)</label>
                        <input type="file" id="ticketAttachment" name="attachment" accept="image/*,.pdf,.doc,.docx" style="padding: 8px;">
                        <small style="color: #666; font-size: 0.8rem; display: block; margin-top: 0.25rem;">
                            <i class="fas fa-info-circle"></i> Maksimum dosya boyutu: 5MB
                        </small>
                    </div>
                    
                    <button type="submit" class="submit-btn" style="background: linear-gradient(135deg, #fd7e14, #dc3545);">
                        <i class="fas fa-paper-plane mr-2"></i>Talebi Oluştur
                    </button>
                </form>
            </div>
        </div>
    </div>

<!-- Add User Modal -->
    <!-- Farsça: مودال افزودن کاربر. -->
    <!-- Türkçe: Kullanıcı Ekle Modalı. -->
    <!-- English: Add User Modal. -->
    <div id="userModal" class="modal">
        <div class="modal-content" style="max-width: 650px; max-height: 90vh; overflow-y: auto;">
            <div class="modal-header">
                <h3>Yeni Kullanıcı Ekle</h3>
                <span class="close" id="closeUserModal">&times;</span>
            </div>
            <div class="modal-body">
                <form id="userForm">
                    <div class="form-group">
                        <label>Kullanıcı Adı *</label>
                        <input type="text" name="username" id="userName" required placeholder="kullanici_adi">
                        <small style="color: #64748b;">Benzersiz kullanıcı adı</small>
                    </div>
                    
                    <div class="form-group">
                        <label>Email Adresi *</label>
                        <input type="email" name="email" id="userEmail" required placeholder="ornek@email.com">
                    </div>
                    
                    <div class="form-group">
                        <label>Şifre *</label>
                        <input type="password" name="password" id="userPassword" required placeholder="Güçlü şifre">
                        <small style="color: #64748b;">En az 8 karakter, büyük/küçük harf ve rakam içermeli</small>
                    </div>
                    
                    <div class="form-group">
                        <label>Şifre Tekrar *</label>
                        <input type="password" name="password_confirm" id="userPasswordConfirm" required placeholder="Şifreyi tekrar girin">
                    </div>
                    
                    <div class="form-group">
                        <label>Tam Adı *</label>
                        <input type="text" name="full_name" id="userFullName" required placeholder="Ad Soyad">
                    </div>
                    
                    <div class="form-group">
                        <label>Telefon</label>
                        <input type="tel" name="phone" id="userPhone" placeholder="+90 555 123 4567">
                    </div>
                    
                    <div class="form-group">
                        <label>Rol *</label>
                        <select name="role_id" id="userRole" required>
                            <option value="">Rol Seçin</option>
                            <option value="1">SuperAdmin - Tam Yetki</option>
                            <option value="2">Admin - Yönetici</option>
                            <option value="3">Manager - Müdür</option>
                            <option value="4">Support - Destek</option>
                            <option value="5">Auditor - Denetçi</option>
                        </select>
                        <small style="color: #64748b;">Kullanıcının erişim seviyesini belirler</small>
                    </div>
                    
                    <div class="form-group">
                        <label>Durum</label>
                        <select name="status" id="userStatus">
                            <option value="active">Aktif</option>
                            <option value="inactive">Pasif</option>
                            <option value="suspended">Askıya Alınmış</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                            <input type="checkbox" name="require_2fa" id="userRequire2FA" style="width: auto; margin: 0;">
                            <span>İki Faktörlü Kimlik Doğrulama Zorunlu</span>
                        </label>
                        <small style="color: #64748b;">Kullanıcı ilk girişte 2FA kurulumu yapacak</small>
                    </div>
                    
                    <div class="form-group">
                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                            <input type="checkbox" name="email_verified" id="userEmailVerified" checked style="width: auto; margin: 0;">
                            <span>Email Doğrulanmış Olarak İşaretle</span>
                        </label>
                    </div>
                    
                    <button type="submit" class="submit-btn">
                        <i class="fas fa-user-plus"></i>
                        Kullanıcı Oluştur
                    </button>
                </form>
            </div>
        </div>
    </div>

<!-- Add CMS Page Modal -->
    <!-- Farsça: مودال افزودن صفحه CMS. -->
    <!-- Türkçe: CMS Sayfası Ekle Modalı. -->
    <!-- English: Add CMS Page Modal. -->
    <div id="cmsPageModal" class="modal">
        <div class="modal-content" style="max-width: 900px; max-height: 90vh; overflow-y: auto;">
            <div class="modal-header" style="background: linear-gradient(135deg, #764ba2, #667eea);">
                <h3><i class="fas fa-file-alt mr-2"></i>Yeni Sayfa Oluştur</h3>
                <span class="close" id="closeCmsPageModal">&times;</span>
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
                                <label><i class="fas fa-heading mr-1"></i>Sayfa Başlığı *</label>
                                <input type="text" name="page_title" id="pageTitle" required placeholder="Örn: Hakkımızda">
                                <small style="color: #64748b;">Sayfa başlığı (meta title)</small>
                            </div>
                            
                            <div class="form-group">
                                <label><i class="fas fa-link mr-1"></i>URL Slug *</label>
                                <input type="text" name="page_slug" id="pageSlug" required placeholder="Örn: hakkimizda">
                                <small style="color: #64748b;">URL dostu metin (otomatik oluşturulur)</small>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fas fa-align-left mr-1"></i>Kısa Açıklama</label>
                            <textarea name="page_description" id="pageDescription" rows="2" placeholder="Sayfa meta açıklaması (SEO için önemli)"></textarea>
                            <small style="color: #64748b;">150-160 karakter önerilir</small>
                        </div>
                    </div>

                    <!-- Page Content -->
                    <div style="background: #f8f9fa; padding: 16px; border-radius: 8px; margin-bottom: 24px; border-left: 4px solid #667eea;">
                        <h4 style="margin: 0 0 16px 0; color: #667eea; display: flex; align-items: center; gap: 8px;">
                            <i class="fas fa-file-code"></i>
                            Sayfa İçeriği
                        </h4>
                        
                        <div class="form-group">
                            <label><i class="fas fa-paragraph mr-1"></i>Ana İçerik *</label>
                            <textarea name="page_content" id="pageContent" rows="10" required placeholder="Sayfa içeriğini buraya yazın... HTML etiketleri kullanabilirsiniz."></textarea>
                            <small style="color: #64748b;">
                                <i class="fas fa-lightbulb"></i> 
                                HTML etiketleri desteklenir: &lt;h1&gt;, &lt;p&gt;, &lt;div&gt;, &lt;strong&gt;, &lt;em&gt;, &lt;ul&gt;, &lt;ol&gt;, &lt;a&gt;, &lt;img&gt;
                            </small>
                        </div>
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                            <div class="form-group">
                                <label><i class="fas fa-image mr-1"></i>Öne Çıkan Görsel (URL)</label>
                                <input type="url" name="featured_image" id="featuredImage" placeholder="https://example.com/image.jpg">
                                <small style="color: #64748b;">Sayfa görseli URL'si</small>
                            </div>
                            
                            <div class="form-group">
                                <label><i class="fas fa-palette mr-1"></i>Arka Plan Rengi</label>
                                <input type="color" name="background_color" id="backgroundColor" value="#ffffff" style="height: 45px; padding: 4px;">
                                <small style="color: #64748b;">Sayfa arka plan rengi</small>
                            </div>
                        </div>
                    </div>

                    <!-- Page Settings -->
                    <div style="background: #f8f9fa; padding: 16px; border-radius: 8px; margin-bottom: 24px; border-left: 4px solid #28a745;">
                        <h4 style="margin: 0 0 16px 0; color: #28a745; display: flex; align-items: center; gap: 8px;">
                            <i class="fas fa-cog"></i>
                            Sayfa Ayarları
                        </h4>
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px;">
                            <div class="form-group">
                                <label><i class="fas fa-list-alt mr-1"></i>Kategori *</label>
                                <select name="page_category" id="pageCategory" required>
                                    <option value="">Kategori Seçin</option>
                                    <option value="about">Hakkımızda</option>
                                    <option value="services">Hizmetler</option>
                                    <option value="contact">İletişim</option>
                                    <option value="help">Yardım & SSS</option>
                                    <option value="legal">Yasal</option>
                                    <option value="blog">Blog</option>
                                    <option value="other">Diğer</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label><i class="fas fa-flag mr-1"></i>Durum *</label>
                                <select name="page_status" id="pageStatus" required>
                                    <option value="draft">Taslak</option>
                                    <option value="published" selected>Yayında</option>
                                    <option value="archived">Arşivlendi</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label><i class="fas fa-language mr-1"></i>Dil *</label>
                                <select name="page_language" id="pageLanguage" required>
                                    <option value="tr" selected>Türkçe</option>
                                    <option value="en">English</option>
                                    <option value="ar">العربية</option>
                                    <option value="fa">فارسی</option>
                                </select>
                            </div>
                        </div>
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-top: 16px;">
                            <div class="form-group">
                                <label><i class="fas fa-sort-numeric-up mr-1"></i>Sıralama</label>
                                <input type="number" name="page_order" id="pageOrder" value="0" min="0" placeholder="0">
                                <small style="color: #64748b;">Sayfa sıralama numarası (0 = en üstte)</small>
                            </div>
                            
                            <div class="form-group">
                                <label><i class="fas fa-user-tie mr-1"></i>Yazar</label>
                                <select name="page_author" id="pageAuthor">
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
                            SEO Ayarları
                        </h4>
                        
                        <div class="form-group">
                            <label><i class="fas fa-tag mr-1"></i>Meta Anahtar Kelimeler</label>
                            <input type="text" name="meta_keywords" id="metaKeywords" placeholder="otopark, araç yıkama, temizlik, bakım">
                            <small style="color: #64748b;">Virgülle ayrılmış anahtar kelimeler</small>
                        </div>
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px;">
                            <div class="form-group">
                                <label><i class="fas fa-robot mr-1"></i>Robots Meta Tag</label>
                                <select name="robots_meta" id="robotsMeta">
                                    <option value="index,follow" selected>Index, Follow (Önerilen)</option>
                                    <option value="noindex,follow">No Index, Follow</option>
                                    <option value="index,nofollow">Index, No Follow</option>
                                    <option value="noindex,nofollow">No Index, No Follow</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label><i class="fas fa-share-alt mr-1"></i>Open Graph Görseli</label>
                                <input type="url" name="og_image" id="ogImage" placeholder="https://example.com/og-image.jpg">
                                <small style="color: #64748b;">Sosyal medya paylaşım görseli</small>
                            </div>
                        </div>
                    </div>

                    <!-- Advanced Settings -->
                    <div style="background: #f8f9fa; padding: 16px; border-radius: 8px; margin-bottom: 24px; border-left: 4px solid #17a2b8;">
                        <h4 style="margin: 0 0 16px 0; color: #17a2b8; display: flex; align-items: center; gap: 8px;">
                            <i class="fas fa-sliders-h"></i>
                            Gelişmiş Ayarlar
                        </h4>
                        
                        <div class="form-group">
                            <label><i class="fas fa-code mr-1"></i>Özel CSS</label>
                            <textarea name="custom_css" id="customCss" rows="4" placeholder=".my-class { color: blue; }"></textarea>
                            <small style="color: #64748b;">Bu sayfa için özel CSS kodları</small>
                        </div>
                        
                        <div class="form-group">
                            <label><i class="fas fa-file-code mr-1"></i>Özel JavaScript</label>
                            <textarea name="custom_js" id="customJs" rows="4" placeholder="console.log('Page loaded');"></textarea>
                            <small style="color: #64748b;">Bu sayfa için özel JavaScript kodları</small>
                        </div>
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px; margin-top: 16px;">
                            <div class="form-group">
                                <label style="display: flex; align-items: center; cursor: pointer;">
                                    <input type="checkbox" name="show_in_menu" id="showInMenu" checked style="margin-right: 8px;">
                                    <i class="fas fa-bars mr-1"></i>
                                    Menüde Göster
                                </label>
                            </div>
                            
                            <div class="form-group">
                                <label style="display: flex; align-items: center; cursor: pointer;">
                                    <input type="checkbox" name="show_in_footer" id="showInFooter" style="margin-right: 8px;">
                                    <i class="fas fa-shoe-prints mr-1"></i>
                                    Footer'da Göster
                                </label>
                            </div>
                            
                            <div class="form-group">
                                <label style="display: flex; align-items: center; cursor: pointer;">
                                    <input type="checkbox" name="require_auth" id="requireAuth" style="margin-right: 8px;">
                                    <i class="fas fa-lock mr-1"></i>
                                    Giriş Gerekli
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Submit Buttons -->
                    <div style="display: flex; gap: 12px; justify-content: flex-end;">
                        <button type="button" class="report-btn" onclick="document.getElementById('cmsPageModal').style.display='none'" style="background: #6c757d; padding: 12px 24px;">
                            <i class="fas fa-times"></i>
                            İptal
                        </button>
                        <button type="submit" class="submit-btn" style="background: linear-gradient(135deg, #764ba2, #667eea); display: flex; align-items: center; gap: 8px;">
                            <i class="fas fa-save"></i>
                            Sayfayı Kaydet
                        </button>
                    </div>
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

        // User Modal Functions
        // Farsça: توابع مودال کاربر.
        // Türkçe: Kullanıcı Modal Fonksiyonları.
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
        // Farsça: توابع مودال صفحه CMS.
        // Türkçe: CMS Sayfası Modal Fonksiyonları.
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
        // Farsça: تولید خودکار URL از عنوان صفحه (پشتیبانی از کاراکترهای ترکی).
        // Türkçe: Sayfa başlığından otomatik URL slug üretimi (Türkçe karakter desteği).
        // English: Auto-generate URL slug from page title (Turkish character support).
        const pageTitleInput = document.getElementById('pageTitle');
        const pageSlugInput = document.getElementById('pageSlug');

        if (pageTitleInput && pageSlugInput) {
            pageTitleInput.addEventListener('input', function() {
                let slug = this.value
                    .toLowerCase()
                    // Turkish character replacements
                    .replace(/ğ/g, 'g')
                    .replace(/ü/g, 'u')
                    .replace(/ş/g, 's')
                    .replace(/ı/g, 'i')
                    .replace(/ö/g, 'o')
                    .replace(/ç/g, 'c')
                    // Replace spaces and special characters with hyphens
                    .replace(/[^a-z0-9]+/g, '-')
                    // Remove leading and trailing hyphens
                    .replace(/^-|-$/g, '');
                
                pageSlugInput.value = slug;
            });
        }

        // CMS Page Form Validation and Submission
        // Farsça: اعتبارسنجی و ارسال فرم صفحه CMS.
        // Türkçe: CMS Sayfası Form Doğrulama ve Gönderimi.
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
                    alert('❌ Hata!\n\nSayfa başlığı en az 3 karakter olmalıdır.');
                    return;
                }

                if (title.length > 200) {
                    alert('❌ Hata!\n\nSayfa başlığı maksimum 200 karakter olabilir.');
                    return;
                }

                if (!slug.match(/^[a-z0-9-]+$/)) {
                    alert('❌ Hata!\n\nURL slug sadece küçük harf, rakam ve tire (-) içerebilir.');
                    return;
                }

                if (slug.length < 3) {
                    alert('❌ Hata!\n\nURL slug en az 3 karakter olmalıdır.');
                    return;
                }

                if (content.length < 50) {
                    alert('❌ Hata!\n\nSayfa içeriği en az 50 karakter olmalıdır.');
                    return;
                }

                if (!category) {
                    alert('❌ Hata!\n\nLütfen bir kategori seçin.');
                    return;
                }

                // Get optional fields
                const description = document.getElementById('pageDescription').value;
                const language = document.getElementById('pageLanguage').value;

                // Success message (TODO: Replace with actual backend API call)
                alert('✅ Başarılı!\n\n' +
                      '━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n' +
                      '📄 Sayfa Başlığı: ' + title + '\n' +
                      '🔗 URL Slug: ' + slug + '\n' +
                      '📁 Kategori: ' + getCategoryName(category) + '\n' +
                      '👁️ Durum: ' + getStatusName(status) + '\n' +
                      '🌐 Dil: ' + language.toUpperCase() + '\n' +
                      '📝 İçerik Uzunluğu: ' + content.length + ' karakter\n' +
                      '━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n' +
                      'Sayfa başarıyla oluşturuldu!');

                // TODO: Backend Integration
                // const formData = new FormData(this);
                // fetch('/backend/api/cms/create_page.php', {
                //     method: 'POST',
                //     body: formData
                // })
                // .then(response => response.json())
                // .then(data => {
                //     if (data.success) {
                //         alert('✅ Sayfa başarıyla oluşturuldu!');
                //         cmsPageModal.style.display = 'none';
                //         this.reset();
                //         // Refresh the page list
                //         location.reload();
                //     } else {
                //         alert('❌ Hata: ' + data.message);
                //     }
                // })
                // .catch(error => {
                //     alert('❌ Bir hata oluştu: ' + error.message);
                // });

                // Close modal and reset form
                cmsPageModal.style.display = 'none';
                this.reset();
            });
        }

        // Helper function to get category name
        function getCategoryName(value) {
            const categories = {
                'about': 'Hakkımızda',
                'services': 'Hizmetler',
                'contact': 'İletişim',
                'help': 'Yardım & SSS',
                'legal': 'Yasal',
                'blog': 'Blog',
                'other': 'Diğer'
            };
            return categories[value] || value;
        }

        // Helper function to get status name
        function getStatusName(value) {
            const statuses = {
                'draft': 'Taslak',
                'published': 'Yayında',
                'archived': 'Arşivlendi'
            };
            return statuses[value] || value;
        }

        // Service Modal Functions
        // Farsça: توابع مودال خدمات.
        // Türkçe: Hizmet Modal Fonksiyonları.
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
        // Farsça: توابع مودال تیکت.
        // Türkçe: Destek Talebi Modal Fonksiyonları.
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
        // Farsça: ارسال فرم تیکت با اعتبارسنجی.
        // Türkçe: Doğrulama ile Destek Talebi Formu Gönderimi.
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
                alert('❌ Lütfen bir müşteri seçin!');
                return;
            }
            
            if (!subject || subject.length < 5) {
                alert('❌ Konu en az 5 karakter olmalıdır!');
                return;
            }
            
            if (!category) {
                alert('❌ Lütfen bir kategori seçin!');
                return;
            }
            
            if (!priority) {
                alert('❌ Lütfen bir öncelik seviyesi seçin!');
                return;
            }
            
            if (!message || message.length < 10) {
                alert('❌ Mesaj en az 10 karakter olmalıdır!');
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
            //         alert('✅ Destek talebi başarıyla oluşturuldu!');
            //         ticketModal.style.display = 'none';
            //         this.reset();
            //         // Reload tickets table
            //         location.reload();
            //     } else {
            //         alert('❌ Hata: ' + data.message);
            //     }
            // })
            // .catch(error => {
            //     alert('❌ Bir hata oluştu: ' + error.message);
            // });
            
            // For now, just show success message
            console.log('Creating ticket:', ticketData);
            alert('✅ Destek talebi başarıyla oluşturuldu!\n\n' +
                  'Konu: ' + subject + '\n' +
                  'Kategori: ' + category + '\n' +
                  'Öncelik: ' + priority);
            ticketModal.style.display = 'none';
            this.reset();
        });

        // Service Form Submission with Validation
        // Farsça: ارسال فرم خدمات با اعتبارسنجی.
        // Türkçe: Doğrulama ile Hizmet Formu Gönderimi.
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
                alert('❌ Hizmet adı en az 3 karakter olmalıdır!');
                return;
            }
            
            if (!category) {
                alert('❌ Lütfen bir kategori seçin!');
                return;
            }
            
            if (!duration || duration < 1) {
                alert('❌ Hizmet süresi en az 1 dakika olmalıdır!');
                return;
            }
            
            if (!priceSedan || priceSedan <= 0) {
                alert('❌ Sedan fiyatı geçerli bir değer olmalıdır!');
                return;
            }
            
            if (!priceSUV || priceSUV <= 0) {
                alert('❌ SUV fiyatı geçerli bir değer olmalıdır!');
                return;
            }
            
            if (!priceTruck || priceTruck <= 0) {
                alert('❌ Kamyonet fiyatı geçerli bir değer olmalıdır!');
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
            //         alert('✅ Hizmet başarıyla oluşturuldu!');
            //         serviceModal.style.display = 'none';
            //         this.reset();
            //         // Reload services table
            //         location.reload();
            //     } else {
            //         alert('❌ Hata: ' + data.message);
            //     }
            // })
            // .catch(error => {
            //     alert('❌ Bir hata oluştu: ' + error.message);
            // });
            
            // For now, just show success message
            console.log('Creating service:', serviceData);
            alert('✅ Hizmet başarıyla oluşturuldu!\n\n' +
                  'Hizmet: ' + serviceName + '\n' +
                  'Kategori: ' + category + '\n' +
                  'Süre: ' + duration + ' dk\n' +
                  'Sedan: ₺' + priceSedan + '\n' +
                  'SUV: ₺' + priceSUV + '\n' +
                  'Kamyonet: ₺' + priceTruck);
            serviceModal.style.display = 'none';
            this.reset();
        });

        // User Form Submission with Validation
        // Farsça: ارسال فرم کاربر با اعتبارسنجی.
        // Türkçe: Doğrulama ile Kullanıcı Formu Gönderimi.
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
                alert('Kullanıcı adı en az 3 karakter olmalıdır!');
                return;
            }
            
            if (!email || !email.includes('@')) {
                alert('Geçerli bir email adresi girin!');
                return;
            }
            
            if (!password || password.length < 8) {
                alert('Şifre en az 8 karakter olmalıdır!');
                return;
            }
            
            if (password !== passwordConfirm) {
                alert('Şifreler eşleşmiyor!');
                return;
            }
            
            if (!roleId) {
                alert('Lütfen bir rol seçin!');
                return;
            }
            
            // Password strength check
            const hasUpperCase = /[A-Z]/.test(password);
            const hasLowerCase = /[a-z]/.test(password);
            const hasNumbers = /\d/.test(password);
            
            if (!hasUpperCase || !hasLowerCase || !hasNumbers) {
                alert('Şifre en az bir büyük harf, bir küçük harf ve bir rakam içermelidir!');
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
            //         alert('Kullanıcı başarıyla oluşturuldu!');
            //         userModal.style.display = 'none';
            //         this.reset();
            //         // Reload user table
            //         location.reload();
            //     } else {
            //         alert('Hata: ' + data.message);
            //     }
            // })
            // .catch(error => {
            //     alert('Bir hata oluştu: ' + error.message);
            // });
            
            // For now, just show success message
            console.log('Creating user:', userData);
            alert('Kullanıcı başarıyla oluşturuldu!');
            userModal.style.display = 'none';
            this.reset();
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

        // Service Management Functions
        // Farsça: توابع مدیریت خدمات.
        // Türkçe: Hizmet Yönetimi Fonksiyonları.
        // English: Service Management Functions.
        
        function editService(serviceId) {
            // TODO: Load service data and populate modal
            console.log('Editing service:', serviceId);
            alert('🔧 Hizmet düzenleme özelliği yakında eklenecek!\n\nService ID: ' + serviceId);
            
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
            if (confirm('Bu hizmetin durumunu değiştirmek istediğinizden emin misiniz?')) {
                // TODO: Send to backend API
                console.log('Toggling service status:', serviceId);
                alert('✅ Hizmet durumu değiştirildi!\n\nService ID: ' + serviceId);
                
                // Future implementation:
                // fetch('/backend/api/admin/services/' + serviceId + '/toggle-status', {
                //     method: 'POST'
                // })
                // .then(response => response.json())
                // .then(data => {
                //     if (data.success) {
                //         alert('Durum değiştirildi!');
                //         location.reload();
                //     }
                // });
            }
        }
        
        function deleteService(serviceId) {
            if (confirm('⚠️ Bu hizmeti silmek istediğinizden emin misiniz?\n\nBu işlem geri alınamaz!')) {
                // TODO: Send to backend API
                console.log('Deleting service:', serviceId);
                alert('🗑️ Hizmet silindi!\n\nService ID: ' + serviceId);
                
                // Future implementation:
                // fetch('/backend/api/admin/services/' + serviceId, {
                //     method: 'DELETE'
                // })
                // .then(response => response.json())
                // .then(data => {
                //     if (data.success) {
                //         alert('Hizmet silindi!');
                //         location.reload();
                //     } else {
                //         alert('Hata: ' + data.message);
                //     }
                // });
            }
        }

        // Security Tabs Functionality
        // Farsça: عملکرد تب‌های امنیتی.
        // Türkçe: Güvenlik Sekmeleri İşlevselliği.
        // English: Security Tabs Functionality.
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

        // Settings Tabs Functionality
        // Farsça: عملکرد تب‌های تنظیمات.
        // Türkçe: Ayarlar Sekmeleri İşlevselliği.
        // English: Settings Tabs Functionality.
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
                
                // Show corresponding content
                // Handle special cases where tab name differs from content id
                let contentId = tabId;
                if (tabId === 'notifications') {
                    contentId = 'notificationstab';
                } else if (tabId === 'security') {
                    contentId = 'securitytab';
                } else if (tabId === 'backup') {
                    contentId = 'backuptab';
                } else if (tabId === 'email') {
                    contentId = 'emailtab';
                }
                
                const content = document.getElementById(contentId);
                if (content) {
                    content.style.display = 'block';
                }
            });
        });

        // Report Category Tabs Functionality
        // Farsça: عملکرد تب‌های دسته‌بندی گزارش.
        // Türkçe: Rapor Kategorisi Sekmeleri İşlevselliği.
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
        // Farsça: عملکرد دانلود گزارش.
        // Türkçe: Rapor İndirme İşlevselliği.
        // English: Report Download Functionality.
        function downloadReport(reportType, format) {
            // Show loading notification
            const loadingMsg = `📊 ${reportType.toUpperCase()} raporu ${format.toUpperCase()} formatında hazırlanıyor...`;
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
                
                alert('✅ Rapor başarıyla indirildi!');
            })
            .catch(error => {
                console.error('Download error:', error);
                alert('❌ Rapor indirme hatası: ' + error.message);
            });
            */
            
            // Temporary simulation for demonstration
            const reportNames = {
                'revenue': 'Gelir Raporu',
                'payment': 'Ödeme Analizi',
                'tax': 'Vergi Raporu',
                'commission': 'Komisyon Raporu',
                'orders': 'Sipariş Raporu',
                'services': 'Hizmet Performansı',
                'carwash': 'Otopark Performansı',
                'customers': 'Müşteri Analizi',
                'reviews': 'Değerlendirme Raporu',
                'analytics': 'Kapsamlı Analiz',
                'executive': 'Yönetici Özeti'
            };
            
            const formatIcons = {
                'pdf': '📄',
                'excel': '📊',
                'csv': '📋',
                'pptx': '📽️'
            };
            
            // Simulate download delay
            setTimeout(() => {
                alert(`${formatIcons[format]} ${reportNames[reportType]} - ${format.toUpperCase()} formatında başarıyla indirildi!\n\n` +
                      `📅 Tarih: ${new Date().toLocaleDateString('tr-TR')}\n` +
                      `⏰ Saat: ${new Date().toLocaleTimeString('tr-TR')}\n\n` +
                      `💡 Not: Gerçek uygulamada bu dosya otomatik olarak indirilecektir.`);
                
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
        // Revenue Chart
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
</body>
</html>

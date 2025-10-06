<?php
// Farsça: این فایل شامل کدهای HTML، CSS و PHP صفحه مدیریت ادمین است.
// Türkçe: Bu dosya, yönetici panelinin HTML, CSS ve PHP kodlarını içermektedir.
// English: This file contains the HTML, CSS, and PHP code for the admin panel.

// Farsça: در اینجا می‌توانید کدهای PHP را اضافه کنید که قبل از رندر شدن HTML اجرا می‌شوند.
// Türkçe: Buraya HTML oluşturulmadan önce çalışacak PHP kodlarını ekleyebilirsiniz.
// English: You can add PHP code here that executes before the HTML is rendered.

// مثال: بررسی اینکه کاربر لاگین کرده است یا خیر
// Example: Check if the user is logged in
// Örnek: Kullanıcının giriş yapıp yapmadığını kontrol etme
// session_start();
// if (!isset($_SESSION['admin_logged_in'])) {
//     header('Location: login.php');
//     exit();
// }

// مثال: دریافت داده‌ها از دیتابیس
// Example: Fetching data from a database
// Örnek: Veritabanından veri çekme
// $total_carwashes = 24; // فرض کنید از دیتابیس می‌آید
// $registered_users = 158; // فرض کنید از دیتابیس می‌آید
// $today_bookings = 89; // فرض کنید از دیتابیس می‌آید
// $monthly_revenue = '15,240'; // فرض کنید از دیتابیس می‌آید
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yönetici Paneli - Otopark Yönetim Sistemi</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        /* Reset and base styles */
        /* Farsça: بازنشانی و استایل‌های پایه. */
        /* Türkçe: Sıfırlama ve temel stiller. */
        /* English: Reset and base styles. */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f7fa;
            color: #333;
            line-height: 1.6;
        }

        /* Header Styles */
        /* Farsça: استایل‌های سربرگ. */
        /* Türkçe: Başlık Stilleri. */
        /* English: Header Styles. */
        .admin-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 1rem 2rem;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
        }

        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1200px;
            margin: 0 auto;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .logo i {
            font-size: 1.8rem;
            color: #ffd700;
        }

        .logo h1 {
            font-size: 1.5rem;
            font-weight: 600;
        }

        .admin-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .logout-btn {
            background: rgba(255,255,255,0.2);
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 5px;
            cursor: pointer;
            transition: background 0.3s;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .logout-btn:hover {
            background: rgba(255,255,255,0.3);
        }

        /* Admin Container */
        /* Farsça: کانتینر ادمین. */
        /* Türkçe: Yönetici Konteyneri. */
        /* English: Admin Container. */
        .admin-container {
            display: flex;
            margin-top: 80px;
            min-height: calc(100vh - 80px);
        }

        /* Sidebar Styles */
        /* Farsça: استایل‌های نوار کناری. */
        /* Türkçe: Kenar Çubuğu Stilleri. */
        /* English: Sidebar Styles. */
        .sidebar {
            width: 250px;
            background: white;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
            position: fixed;
            left: 0;
            top: 80px;
            bottom: 0;
            overflow-y: auto;
        }

        .nav-menu ul {
            list-style: none;
            padding: 1rem 0;
        }

        .nav-item {
            margin: 0;
        }

        .nav-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 15px 25px;
            color: #666;
            text-decoration: none;
            transition: all 0.3s;
            border-left: 3px solid transparent;
        }

        .nav-link:hover {
            background: #f8f9fa;
            color: #667eea;
        }

        .nav-item.active .nav-link {
            background: linear-gradient(135deg, #667eea20, #764ba220);
            color: #667eea;
            border-left-color: #667eea;
            font-weight: 500;
        }

        .nav-link i {
            font-size: 1.1rem;
            width: 20px;
        }

        /* Main Content */
        /* Farsça: محتوای اصلی. */
        /* Türkçe: Ana İçerik. */
        /* English: Main Content. */
        .main-content {
            flex: 1;
            margin-left: 250px;
            padding: 2rem;
            background: #f5f7fa;
        }

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
        }

        .section-header h2 {
            color: #333;
            font-size: 1.8rem;
            font-weight: 600;
        }

        .section-header p {
            color: #666;
            margin-top: 5px;
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

        /* Responsive Design */
        /* Farsça: طراحی واکنش‌گرا. */
        /* Türkçe: Duyarlı Tasarım. */
        /* English: Responsive Design. */
        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                position: static;
                height: auto;
            }
            
            .main-content {
                margin-left: 0;
                padding: 1rem;
            }
            
            .admin-container {
                flex-direction: column;
            }
            
            .header-content {
                padding: 0 1rem;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .filters {
                flex-direction: column;
                align-items: stretch;
            }
            
            .table-container {
                overflow-x: auto;
            }
            
            .section-header {
                flex-direction: column;
                align-items: stretch;
                gap: 1rem;
            }
        }

        @media (max-width: 480px) {
            .logo h1 {
                font-size: 1.2rem;
            }
            
            .admin-info span {
                display: none;
            }
            
            .data-table {
                font-size: 0.8rem;
            }
            
            .data-table th, .data-table td {
                padding: 10px 8px;
            }
        }
    </style>
</head>
<body>
    <!-- Admin Header -->
    <!-- Farsça: این بخش سربرگ ادمین را شامل می‌شود. -->
    <!-- Türkçe: Bu bölüm yönetici başlığını içerir. -->
    <!-- English: This section includes the admin header. -->
    <header class="admin-header">
        <div class="header-content">
            <div class="logo">
                <i class="fas fa-car-wash"></i>
                <h1>Otopark Yönetimi</h1>
            </div>
            <div class="admin-info">
                <span>Hoş geldin, Admin</span>
                <button class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i>
                    Çıkış Yap
                </button>
            </div>
        </div>
    </header>

    <div class="admin-container">
        <!-- Sidebar Navigation -->
        <!-- Farsça: نوار کناری ناوبری. -->
        <!-- Türkçe: Kenar çubuğu navigasyonu. -->
        <!-- English: Sidebar Navigation. -->
        <aside class="sidebar">
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
                            <i class="fas fa-car-wash"></i>
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
                    <h2>Otopark Yönetimi</h2>
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
</body>
</html>

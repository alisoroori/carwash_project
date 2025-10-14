<?php
// filepath: c:\xampp\htdocs\carwash_project\backend\dashboard\Customer_Dashboard_process.php

/**
 * Customer Dashboard Processing Script for CarWash Web Application
 * Following project conventions: file-based routing, modular structure
 * Handles all dashboard actions: profile updates, reservations, etc.
 */

// Start session following project patterns
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Include database connection following project structure
require_once __DIR__ . '/../includes/db.php';

// Check if user is logged in and has customer role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'customer') {
    header('Location: ../auth/login.php');
    exit();
}

// Only process POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: Customer_Dashboard.php');
    exit();
}

try {
    $conn = getDBConnection();
    $user_id = $_SESSION['user_id'];
    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'update_profile':
            updateProfile($conn, $user_id);
            break;

        case 'update_vehicle':
            updateVehicle($conn, $user_id);
            break;

        case 'create_reservation':
            createReservation($conn, $user_id);
            break;

        case 'cancel_reservation':
            cancelReservation($conn, $user_id);
            break;

        default:
            $_SESSION['error_message'] = 'Geçersiz işlem.';
            header('Location: Customer_Dashboard.php');
            exit();
    }
} catch (Exception $e) {
    error_log("Customer dashboard process error: " . $e->getMessage());
    $_SESSION['error_message'] = 'İşlem sırasında bir hata oluştu: ' . $e->getMessage();
    header('Location: Customer_Dashboard.php');
    exit();
}

/**
 * Update user profile information
 */
function updateProfile($conn, $user_id)
{
    try {
        $name = trim($_POST['name'] ?? '');
        $email = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
        $phone = trim($_POST['phone'] ?? '');
        $city = $_POST['city'] ?? '';
        $address = trim($_POST['address'] ?? '');

        // Validation
        if (empty($name) || empty($email)) {
            throw new Exception('Ad ve e-posta alanları zorunludur.');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Geçerli bir e-posta adresi girin.');
        }

        // Check if email exists for other users
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$email, $user_id]);
        if ($stmt->fetch()) {
            throw new Exception('Bu e-posta adresi başka bir kullanıcı tarafından kullanılıyor.');
        }

        // Update user information
        $stmt = $conn->prepare("
            UPDATE users 
            SET name = ?, email = ?, phone = ?, city = ?, address = ?, updated_at = NOW() 
            WHERE id = ?
        ");

        $result = $stmt->execute([$name, $email, $phone, $city, $address, $user_id]);

        if ($result) {
            // Update session data
            $_SESSION['user_name'] = $name;
            $_SESSION['user_email'] = $email;

            $_SESSION['success_message'] = 'Profil bilgileriniz başarıyla güncellendi.';
        } else {
            throw new Exception('Profil güncellenirken bir hata oluştu.');
        }
    } catch (Exception $e) {
        throw $e;
    }

    header('Location: Customer_Dashboard.php#profile');
    exit();
}

/**
 * Update vehicle information
 */
function updateVehicle($conn, $user_id)
{
    try {
        $car_brand = trim($_POST['car_brand'] ?? '');
        $car_model = trim($_POST['car_model'] ?? '');
        $license_plate = trim($_POST['license_plate'] ?? '');
        $car_year = $_POST['car_year'] ?? null;
        $car_color = trim($_POST['car_color'] ?? '');

        // Update vehicle information
        $stmt = $conn->prepare("
            UPDATE users 
            SET car_brand = ?, car_model = ?, license_plate = ?, car_year = ?, car_color = ?, updated_at = NOW() 
            WHERE id = ?
        ");

        $result = $stmt->execute([$car_brand, $car_model, $license_plate, $car_year ?: null, $car_color, $user_id]);

        if ($result) {
            $_SESSION['success_message'] = 'Araç bilgileriniz başarıyla güncellendi.';
        } else {
            throw new Exception('Araç bilgileri güncellenirken bir hata oluştu.');
        }
    } catch (Exception $e) {
        throw $e;
    }

    header('Location: Customer_Dashboard.php#profile');
    exit();
}

/**
 * Create new reservation
 */
function createReservation($conn, $user_id)
{
    try {
        $service_type = trim($_POST['service_type'] ?? '');
        $reservation_date = $_POST['reservation_date'] ?? '';
        $reservation_time = $_POST['reservation_time'] ?? '';
        $carwash_id = (int)($_POST['carwash_id'] ?? 0);
        $notes = trim($_POST['notes'] ?? '');

        // Validation
        if (empty($service_type) || empty($reservation_date) || empty($reservation_time) || empty($carwash_id)) {
            throw new Exception('Tüm zorunlu alanları doldurun.');
        }

        // Check if reservation date is not in the past
        $reservation_datetime = $reservation_date . ' ' . $reservation_time;
        if (strtotime($reservation_datetime) <= time()) {
            throw new Exception('Rezervasyon tarihi geçmiş bir tarih olamaz.');
        }

        // Check if reservations table exists, if not create reservation in a simple way
        try {
            // Try to insert into reservations table
            $stmt = $conn->prepare("
                INSERT INTO reservations (
                    user_id, carwash_id, service_type, reservation_date, reservation_time, 
                    notes, status, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, 'pending', NOW())
            ");

            $result = $stmt->execute([$user_id, $carwash_id, $service_type, $reservation_date, $reservation_time, $notes]);
        } catch (PDOException $e) {
            // If table doesn't exist, create it
            if (strpos($e->getMessage(), "doesn't exist") !== false) {
                createReservationsTable($conn);

                // Try again
                $stmt = $conn->prepare("
                    INSERT INTO reservations (
                        user_id, carwash_id, service_type, reservation_date, reservation_time, 
                        notes, status, created_at
                    ) VALUES (?, ?, ?, ?, ?, ?, 'pending', NOW())
                ");

                $result = $stmt->execute([$user_id, $carwash_id, $service_type, $reservation_date, $reservation_time, $notes]);
            } else {
                throw $e;
            }
        }

        if ($result) {
            $_SESSION['success_message'] = 'Rezervasyonunuz başarıyla oluşturuldu. Onay için bekleyiniz.';
        } else {
            throw new Exception('Rezervasyon oluşturulurken bir hata oluştu.');
        }
    } catch (Exception $e) {
        throw $e;
    }

    header('Location: Customer_Dashboard.php#reservations');
    exit();
}

/**
 * Cancel reservation
 */
function cancelReservation($conn, $user_id)
{
    try {
        $reservation_id = (int)($_POST['reservation_id'] ?? 0);

        if (empty($reservation_id)) {
            throw new Exception('Geçersiz rezervasyon ID.');
        }

        // Check if reservation belongs to user and is cancellable
        $stmt = $conn->prepare("
            SELECT * FROM reservations 
            WHERE id = ? AND user_id = ? AND status IN ('pending', 'confirmed')
        ");
        $stmt->execute([$reservation_id, $user_id]);
        $reservation = $stmt->fetch();

        if (!$reservation) {
            throw new Exception('Rezervasyon bulunamadı veya iptal edilemez.');
        }

        // Update reservation status
        $stmt = $conn->prepare("
            UPDATE reservations 
            SET status = 'cancelled', updated_at = NOW() 
            WHERE id = ? AND user_id = ?
        ");

        $result = $stmt->execute([$reservation_id, $user_id]);

        if ($result) {
            $_SESSION['success_message'] = 'Rezervasyonunuz başarıyla iptal edildi.';
        } else {
            throw new Exception('Rezervasyon iptal edilirken bir hata oluştu.');
        }
    } catch (Exception $e) {
        throw $e;
    }

    header('Location: Customer_Dashboard.php#reservations');
    exit();
}

/**
 * Create reservations table if it doesn't exist
 */
function createReservationsTable($conn)
{
    $sql = "
        CREATE TABLE IF NOT EXISTS reservations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            carwash_id INT NULL,
            service_type VARCHAR(100) NOT NULL,
            reservation_date DATE NOT NULL,
            reservation_time TIME NOT NULL,
            price DECIMAL(10,2) DEFAULT 0,
            notes TEXT,
            status ENUM('pending', 'confirmed', 'completed', 'cancelled') DEFAULT 'pending',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_user_id (user_id),
            INDEX idx_carwash_id (carwash_id),
            INDEX idx_status (status),
            INDEX idx_reservation_date (reservation_date)
        )
    ";

    $conn->exec($sql);
    error_log("Reservations table created successfully");
}

// If we reach here, redirect back to dashboard
header('Location: Customer_Dashboard.php');
exit();

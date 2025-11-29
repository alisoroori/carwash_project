<?php
/**
 * Invoice Logo & Address Fix - Verification Test
 * Tests that invoice.php correctly loads and displays carwash logo and address
 */

require_once __DIR__ . '/backend/includes/bootstrap.php';

use App\Classes\Database;
use App\Classes\Logger;

$db = Database::getInstance();

echo "=== INVOICE.PHP FIX VERIFICATION TEST ===\n\n";

// Test 1: Check carwashes table data
echo "TEST 1: Carwashes Table Data\n";
echo str_repeat("-", 50) . "\n";

$carwashes = $db->fetchAll('SELECT id, name, logo_path, address, city, district FROM carwashes ORDER BY id');

foreach ($carwashes as $cw) {
    echo "Carwash ID: {$cw['id']}\n";
    echo "  Name: {$cw['name']}\n";
    echo "  Logo Path: " . ($cw['logo_path'] ?: 'NULL') . "\n";
    echo "  Address: " . ($cw['address'] ?: 'NULL') . "\n";
    echo "  City: " . ($cw['city'] ?: 'NULL') . "\n";
    echo "  District: " . ($cw['district'] ?: 'NULL') . "\n";
    
    // Check if logo file exists
    if (!empty($cw['logo_path'])) {
        $paths_to_check = [
            __DIR__ . '/backend/uploads/business_logo/' . basename($cw['logo_path']),
            __DIR__ . '/uploads/business_logo/' . basename($cw['logo_path']),
            __DIR__ . '/uploads/logos/' . basename($cw['logo_path']),
        ];
        
        $found = false;
        foreach ($paths_to_check as $path) {
            if (file_exists($path) && is_readable($path)) {
                echo "  ✓ Logo file EXISTS: " . basename($path) . "\n";
                $found = true;
                break;
            }
        }
        
        if (!$found) {
            echo "  ✗ Logo file NOT FOUND\n";
        }
    } else {
        echo "  ⚠ No logo_path in database (will use fallback)\n";
    }
    
    echo "\n";
}

// Test 2: Check bookings with carwash data
echo "\nTEST 2: Sample Booking with Carwash JOIN\n";
echo str_repeat("-", 50) . "\n";

$booking = $db->fetchOne("
    SELECT 
        b.id, b.carwash_id,
        c.name as carwash_name,
        c.logo_path as carwash_logo_path,
        c.address as cw_address,
        c.city as cw_city,
        c.district as cw_district
    FROM bookings b
    LEFT JOIN carwashes c ON b.carwash_id = c.id
    ORDER BY b.id DESC
    LIMIT 1
");

if ($booking) {
    echo "Booking ID: {$booking['id']}\n";
    echo "Carwash ID: {$booking['carwash_id']}\n";
    echo "Carwash Name: {$booking['carwash_name']}\n";
    echo "Logo Path: " . ($booking['carwash_logo_path'] ?: 'NULL') . "\n";
    echo "Address: " . ($booking['cw_address'] ?: 'NULL') . "\n";
    echo "City: " . ($booking['cw_city'] ?: 'NULL') . "\n";
    echo "District: " . ($booking['cw_district'] ?: 'NULL') . "\n";
} else {
    echo "No bookings found\n";
}

// Test 3: Check fallback logo files
echo "\n\nTEST 3: Fallback Logo Files\n";
echo str_repeat("-", 50) . "\n";

$fallback_paths = [
    'uploads/logos/default.png',
    'frontend/images/logo.png',
    'backend/logo01.png',
];

foreach ($fallback_paths as $path) {
    $full_path = __DIR__ . '/' . $path;
    $exists = file_exists($full_path) && is_readable($full_path);
    echo ($exists ? '✓' : '✗') . " {$path}" . ($exists ? ' (EXISTS)' : ' (NOT FOUND)') . "\n";
}

// Test 4: Simulate invoice.php logo detection logic
echo "\n\nTEST 4: Logo Detection Simulation\n";
echo str_repeat("-", 50) . "\n";

if ($booking) {
    $carwash_logo_path = $booking['carwash_logo_path'];
    $carwash_id = $booking['carwash_id'];
    $logo_url = '';
    
    echo "Simulating logo detection for Carwash ID: {$carwash_id}\n";
    echo "Logo path from DB: " . ($carwash_logo_path ?: 'NULL') . "\n\n";
    
    // Try carwash-specific logo
    if (!empty($carwash_logo_path)) {
        $possible_paths = [
            __DIR__ . '/backend/uploads/business_logo/' . basename($carwash_logo_path),
            __DIR__ . '/uploads/business_logo/' . basename($carwash_logo_path),
            __DIR__ . '/uploads/logos/' . basename($carwash_logo_path),
        ];
        
        foreach ($possible_paths as $logo_file_path) {
            if (file_exists($logo_file_path) && is_readable($logo_file_path)) {
                $logo_url = '/carwash_project/backend/uploads/business_logo/' . basename($carwash_logo_path);
                echo "✓ Found carwash logo: {$logo_file_path}\n";
                echo "  URL would be: {$logo_url}\n";
                break;
            }
        }
    }
    
    // Try fallbacks
    if (empty($logo_url)) {
        echo "⚠ Carwash logo not found, trying fallbacks...\n";
        
        foreach ($fallback_paths as $fallback_rel) {
            $fallback_full = __DIR__ . '/' . $fallback_rel;
            if (file_exists($fallback_full) && is_readable($fallback_full)) {
                $logo_url = '/carwash_project/' . $fallback_rel;
                echo "✓ Found fallback logo: {$fallback_rel}\n";
                echo "  URL would be: {$logo_url}\n";
                break;
            }
        }
    }
    
    // Last resort
    if (empty($logo_url)) {
        echo "✗ No logo files found - would use SVG placeholder\n";
    }
}

// Test 5: Check address formatting
echo "\n\nTEST 5: Address Formatting\n";
echo str_repeat("-", 50) . "\n";

if ($booking) {
    $address = $booking['cw_address'] ?: 'Adres bilgisi mevcut değil';
    $district = $booking['cw_district'] ?: '-';
    $city = $booking['cw_city'] ?: '-';
    
    echo "Display values (with fallbacks):\n";
    echo "  Address: {$address}\n";
    echo "  District: {$district}\n";
    echo "  City: {$city}\n";
    
    $full_address = trim("{$address}, {$district}, {$city}", ', ');
    echo "  Full Address: {$full_address}\n";
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "TEST COMPLETE\n";
echo "\nTo test in browser:\n";
echo "1. Login to the system\n";
echo "2. Go to: http://localhost/carwash_project/backend/checkout/invoice.php?id=BOOKING_ID\n";
echo "3. Check browser console and check_invoice.log for detailed logs\n";
echo "4. Verify logo displays correctly (not broken image)\n";
echo "5. Verify address shows real data (not 'logo 7' or numeric IDs)\n";
?>

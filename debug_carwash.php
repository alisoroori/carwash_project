<?php
include 'backend/includes/config.php';
try {
    $pdo = new PDO('mysql:host='.DB_HOST.';dbname='.DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "Checking user 27...\n";
    $stmt = $pdo->query('SELECT id, role FROM users WHERE id = 27');
    $user = $stmt->fetch();
    if ($user) {
        echo 'User 27 exists, role: ' . $user['role'] . PHP_EOL;
    } else {
        echo 'User 27 does not exist' . PHP_EOL;
        exit;
    }

    // Check if carwash profile already exists
    $stmt = $pdo->query('SELECT COUNT(*) as count FROM carwash_profiles WHERE user_id = 27');
    $count = $stmt->fetch()['count'];
    echo 'Existing carwash profiles for user 27: ' . $count . PHP_EOL;

    if ($count > 0) {
        echo "Carwash profile already exists, skipping creation.\n";
        $stmt = $pdo->query('SELECT id FROM carwash_profiles WHERE user_id = 27');
        $carwashId = $stmt->fetch()['id'];
    } else {
        echo "Creating carwash profile...\n";
        $carwashStmt = $pdo->prepare("
            INSERT INTO carwash_profiles (
                user_id, business_name, address, city, state, country,
                contact_email, contact_phone, verified, created_at, updated_at
            ) VALUES (
                :user_id, :business_name, :address, :city, :state, :country,
                :contact_email, :contact_phone, :verified, NOW(), NOW()
            )
        ");

        $carwashStmt->execute([
            'user_id' => 27,
            'business_name' => 'Özil Oto Yıkama',
            'address' => 'Atatürk Caddesi No: 123',
            'city' => 'İstanbul',
            'state' => 'İstanbul',
            'country' => 'Turkey',
            'contact_email' => 'ozil@gmail.com',
            'contact_phone' => '+90 216 555 0123',
            'verified' => 1
        ]);

        $carwashId = $pdo->lastInsertId();
        echo "Created carwash profile ID: $carwashId\n";
    }

    echo "Carwash ID to use: $carwashId\n";

} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage() . PHP_EOL;
    echo 'Line: ' . $e->getLine() . PHP_EOL;
}
?>
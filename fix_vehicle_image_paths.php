<?php
require_once __DIR__ . '/backend/includes/bootstrap.php';
require_once __DIR__ . '/backend/includes/db.php';

// Fix invalid vehicle image paths in the database
try {
    $pdo = new PDO('mysql:host=localhost;dbname=carwash_db', 'root', ''); // Update credentials if needed
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->prepare('SELECT id, image_path FROM user_vehicles');
    $stmt->execute();
    $vehicles = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($vehicles as $vehicle) {
        $id = $vehicle['id'];
        $imagePath = $vehicle['image_path'];

        if (!empty($imagePath) && strpos($imagePath, '/carwash_project/') === false) {
            // Fix the path
            if (strpos($imagePath, '/') === 0) {
                $newPath = '/carwash_project' . $imagePath;
            } else {
                $newPath = '/carwash_project/backend/uploads/vehicles/' . $imagePath;
            }

            // Update the database
            $updateStmt = $pdo->prepare('UPDATE user_vehicles SET image_path = :newPath WHERE id = :id');
            $updateStmt->execute([':newPath' => $newPath, ':id' => $id]);

            echo "Updated vehicle ID $id: $imagePath -> $newPath\n";
        }
    }

    echo "All invalid paths have been fixed.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
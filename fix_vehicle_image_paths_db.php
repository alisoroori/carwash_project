<?php
require_once __DIR__ . '/backend/includes/db.php';

echo "=== Fixing Vehicle Image Paths in Database ===\n\n";

// Get all vehicles with image paths
$stmt = $conn->query("SELECT id, image_path FROM user_vehicles WHERE image_path IS NOT NULL AND image_path != ''");

$updated = 0;
$skipped = 0;

while ($row = $stmt->fetch_assoc()) {
    $id = $row['id'];
    $oldPath = $row['image_path'];
    $newPath = $oldPath;
    
    // Fix paths that start with /backend/ but don't have /carwash_project/
    if (strpos($oldPath, '/backend/') === 0 && strpos($oldPath, '/carwash_project/') !== 0) {
        $newPath = '/carwash_project' . $oldPath;
    } elseif (strpos($oldPath, 'backend/') === 0 && strpos($oldPath, '/carwash_project/') !== 0) {
        $newPath = '/carwash_project/' . $oldPath;
    }
    
    if ($newPath !== $oldPath) {
        // Update the database
        $updateStmt = $conn->prepare("UPDATE user_vehicles SET image_path = ? WHERE id = ?");
        $updateStmt->bind_param('si', $newPath, $id);
        
        if ($updateStmt->execute()) {
            echo "✅ Updated ID $id:\n";
            echo "   Old: $oldPath\n";
            echo "   New: $newPath\n\n";
            $updated++;
        } else {
            echo "❌ Failed to update ID $id: " . $conn->error . "\n\n";
        }
        
        $updateStmt->close();
    } else {
        $skipped++;
    }
}

echo "\n=== Summary ===\n";
echo "Updated: $updated records\n";
echo "Skipped (already correct): $skipped records\n";

$conn->close();

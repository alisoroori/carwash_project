<?php
// Writes small placeholder images used by the project (decode base64 and write binary files)
// Run: php .tools\write_default_images.php
$files = [
    // 1x1 transparent PNG
    'frontend/assets/default-car.png' => 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR4nGNgYAAAAAMAAWgmWQ0AAAAASUVORK5CYII=',
    // small placeholder JPEG (1x1 white)
    'frontend/images/default-carwash.jpg' => '/9j/4AAQSkZJRgABAQEASABIAAD/2wBDAP//////////////////////////////////////////////////////////////////////////////////////2wBDAf//////////////////////////////////////////////////////////////////////////////////////wAARCAAQABADASIAAhEBAxEB/8QAFwABAQEBAAAAAAAAAAAAAAAAAAUBB//EABQBAQAAAAAAAAAAAAAAAAAAAAD/2gAMAwEAAhADEAAAAf/EABYRAQEBAAAAAAAAAAAAAAAAAAABEv/aAAgBAQABPwC0f//EABYRAQEBAAAAAAAAAAAAAAAAAAABEv/aAAgBAgEBPwC0f//EABYRAQEBAAAAAAAAAAAAAAAAAAABEv/aAAgBAwEBPwC0f//Z'
];

foreach ($files as $path => $b64) {
    $full = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . $path;
    $dir = dirname($full);
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    $data = base64_decode($b64);
    if ($data === false) {
        echo "Failed to decode base64 for $path\n";
        continue;
    }
    $written = file_put_contents($full, $data);
    if ($written === false) {
        echo "Failed to write $full\n";
    } else {
        echo "Wrote $full (" . strlen($data) . " bytes)\n";
    }
}

echo "Done.\n";

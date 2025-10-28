<?php
function uploadServiceImage($file)
{
    $targetDir = "../../uploads/services/";
    if (!file_exists($targetDir)) {
        mkdir($targetDir, 0777, true);
    }

    $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowedTypes = ['jpg', 'jpeg', 'png', 'webp'];

    if (!in_array($fileExtension, $allowedTypes)) {
        throw new Exception('Invalid file type. Only JPG, JPEG, PNG and WEBP allowed.');
    }

    $fileName = uniqid() . '.' . $fileExtension;
    $targetFile = $targetDir . $fileName;

    if (!move_uploaded_file($file['tmp_name'], $targetFile)) {
        throw new Exception('Failed to upload image');
    }

    return 'uploads/services/' . $fileName;
}

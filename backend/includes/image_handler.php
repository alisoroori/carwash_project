<?php
class ProfileImageHandler
{
    private $uploadDir;
    private $allowedTypes = ['image/jpeg', 'image/png'];
    private $maxSize = 5242880; // 5MB

    public function __construct()
    {
        $this->uploadDir = dirname(__DIR__) . '/uploads/profiles/';
        $this->ensureUploadDirectory();
    }

    private function validateImage($file)
    {
        if (!isset($file['error']) || is_array($file['error'])) {
            throw new Exception('Invalid file parameters.');
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('File upload error code: ' . $file['error']);
        }

        if (!in_array($file['type'], $this->allowedTypes)) {
            throw new Exception('Unsupported image type.');
        }

        if ($file['size'] > $this->maxSize) {
            throw new Exception('File size exceeds maximum allowed size.');
        }
    }

    private function ensureUploadDirectory()
    {
        if (!file_exists($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }
    }

    public function uploadProfileImage($userId, $file)
    {
        try {
            $this->validateImage($file);

            $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
            $filename = "profile_{$userId}_" . time() . "." . $extension;
            $targetPath = $this->uploadDir . $filename;

            if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                return [
                    'success' => true,
                    'filename' => $filename,
                    'path' => $targetPath
                ];
            }

            throw new Exception('Failed to move uploaded file');
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}

<?php
session_start();
require_once '../../includes/permissions.php';
require_once '../../includes/auth_check.php';
require_once '../../includes/db.php';

class DisputeEvidenceHandler
{
    private $conn;
    private $uploadDir = '../../uploads/disputes/';
    private $allowedTypes = ['image/jpeg', 'image/png', 'application/pdf'];
    private $maxSize = 5242880; // 5MB

    public function __construct($conn)
    {
        $this->conn = $conn;
        $this->ensureUploadDirectory();
    }

    public function handleUpload($disputeId)
    {
        if (!isset($_FILES['evidence'])) {
            throw new Exception('No file uploaded');
        }

        $file = $_FILES['evidence'];

        // Validate file
        $this->validateFile($file);

        // Generate unique filename
        $filename = $this->generateFilename($disputeId, $file);

        // Save file
        if (move_uploaded_file($file['tmp_name'], $this->uploadDir . $filename)) {
            // Update dispute record
            $this->updateDisputeEvidence($disputeId, $filename);
            return ['success' => true, 'filename' => $filename];
        }

        throw new Exception('Failed to save file');
    }

    private function validateFile($file)
    {
        if ($file['size'] > $this->maxSize) {
            throw new Exception('File too large');
        }
        if (!in_array($file['type'], $this->allowedTypes)) {
            throw new Exception('Invalid file type');
        }
    }
}

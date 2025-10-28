<?php
declare(strict_types=1);

namespace App\Classes;

/**
 * Secure File Uploader Class
 * 
 * This class provides a secure way to handle file uploads with:
 * - MIME type validation
 * - File size limits
 * - Image verification
 * - Secure file naming
 * - Extension whitelisting
 */
class FileUploader {
    /** @var array Allowed file extensions */
    private array $allowedExtensions = [];
    
    /** @var array Allowed MIME types */
    private array $allowedMimeTypes = [];
    
    /** @var int Maximum file size in bytes */
    private int $maxFileSize = 0;
    
    /** @var string Upload directory */
    private string $uploadDir = '';
    
    /** @var array Validation errors */
    private array $errors = [];
    
    /** @var bool Whether uploaded file is an image */
    private bool $isImage = false;
    
    /**
     * Constructor
     * 
     * @param string $uploadDir Directory to store uploads
     * @param array $allowedExtensions List of allowed file extensions
     * @param array $allowedMimeTypes List of allowed MIME types
     * @param int $maxFileSize Maximum file size in bytes
     */
    public function __construct(
        string $uploadDir, 
        array $allowedExtensions = [], 
        array $allowedMimeTypes = [], 
        int $maxFileSize = 5242880 // 5MB default
    ) {
        $this->uploadDir = rtrim($uploadDir, '/') . '/';
        $this->allowedExtensions = $allowedExtensions;
        $this->allowedMimeTypes = $allowedMimeTypes;
        $this->maxFileSize = $maxFileSize;
        
        // Create directory if it doesn't exist
        if (!is_dir($this->uploadDir)) {
            mkdir($this->uploadDir, 0755, true);
        }
    }
    
    /**
     * Set file uploader to handle images only
     * 
     * @param bool $verifyDimensions Whether to check that files are valid images
     * @return $this
     */
    public function imagesOnly(bool $verifyDimensions = true): self {
        $this->isImage = true;
        
        // Set default image extensions if none provided
        if (empty($this->allowedExtensions)) {
            $this->allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        }
        
        // Set default image MIME types if none provided
        if (empty($this->allowedMimeTypes)) {
            $this->allowedMimeTypes = [
                'image/jpeg', 'image/png', 'image/gif', 'image/webp'
            ];
        }
        
        return $this;
    }
    
    /**
     * Upload a file
     * 
     * @param array $file The $_FILES array element
     * @param string|null $customFilename Custom filename (without extension)
     * @return array|bool Result array with file info or false on failure
     */
    public function upload(array $file, ?string $customFilename = null) {
        $this->errors = [];
        
        // Validate the file
        if (!$this->validate($file)) {
            return false;
        }
        
        // Get safe filename
        $filename = $this->getSafeFilename($file, $customFilename);
        $targetPath = $this->uploadDir . $filename;
        
        // Move the file
        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            $this->errors[] = 'Failed to move uploaded file.';
            return false;
        }
        
        // Set correct permissions
        chmod($targetPath, 0644);
        
        return [
            'filename' => $filename,
            'filepath' => $targetPath,
            'filesize' => $file['size'],
            'filetype' => $file['type'],
            'url' => str_replace($_SERVER['DOCUMENT_ROOT'], '', $targetPath)
        ];
    }
    
    /**
     * Validate an uploaded file
     * 
     * @param array $file The $_FILES array element
     * @return bool True if valid, false if not
     */
    private function validate(array $file): bool {
        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $this->errors[] = $this->getUploadErrorMessage($file['error']);
            return false;
        }
        
        // Check file size
        if ($file['size'] > $this->maxFileSize) {
            $this->errors[] = 'File size exceeds the limit of ' . 
                              round($this->maxFileSize / 1048576, 2) . 'MB.';
            return false;
        }
        
        // Get file extension and MIME type
        $fileParts = pathinfo($file['name']);
        $extension = strtolower($fileParts['extension'] ?? '');
        
        // Check extension
        if (!empty($this->allowedExtensions) && !in_array($extension, $this->allowedExtensions)) {
            $this->errors[] = 'Invalid file extension. Allowed: ' . 
                              implode(', ', $this->allowedExtensions);
            return false;
        }
        
        // Check MIME type using finfo
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file['tmp_name']);
        
        if (!empty($this->allowedMimeTypes) && !in_array($mimeType, $this->allowedMimeTypes)) {
            $this->errors[] = 'Invalid file type. Allowed: ' . 
                              implode(', ', $this->allowedMimeTypes);
            return false;
        }
        
        // Additional image verification
        if ($this->isImage) {
            $imageInfo = @getimagesize($file['tmp_name']);
            if ($imageInfo === false) {
                $this->errors[] = 'Invalid image file.';
                return false;
            }
            
            // Additional check that MIME type from getimagesize matches finfo
            if ($imageInfo['mime'] !== $mimeType) {
                $this->errors[] = 'Image type mismatch.';
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Create a secure filename
     * 
     * @param array $file The $_FILES array element
     * @param string|null $customFilename Custom filename (without extension)
     * @return string Safe filename
     */
    private function getSafeFilename(array $file, ?string $customFilename = null): string {
        // Get file extension
        $fileParts = pathinfo($file['name']);
        $extension = strtolower($fileParts['extension'] ?? '');
        
        if (!empty($customFilename)) {
            // Sanitize custom filename
            $filename = preg_replace('/[^a-z0-9_-]/i', '_', $customFilename);
        } else {
            // Generate unique name based on timestamp and random string
            $filename = time() . '_' . bin2hex(random_bytes(8));
        }
        
        // Check if file exists, add suffix if needed
        $fullFilename = $filename . '.' . $extension;
        $counter = 1;
        
        while (file_exists($this->uploadDir . $fullFilename)) {
            $fullFilename = $filename . '_' . $counter . '.' . $extension;
            $counter++;
        }
        
        return $fullFilename;
    }
    
    /**
     * Get upload error message
     * 
     * @param int $errorCode PHP upload error code
     * @return string Human-readable error message
     */
    private function getUploadErrorMessage(int $errorCode): string {
        switch ($errorCode) {
            case UPLOAD_ERR_INI_SIZE:
                return 'The uploaded file exceeds the upload_max_filesize directive in php.ini.';
            case UPLOAD_ERR_FORM_SIZE:
                return 'The uploaded file exceeds the MAX_FILE_SIZE directive in the HTML form.';
            case UPLOAD_ERR_PARTIAL:
                return 'The uploaded file was only partially uploaded.';
            case UPLOAD_ERR_NO_FILE:
                return 'No file was uploaded.';
            case UPLOAD_ERR_NO_TMP_DIR:
                return 'Missing a temporary folder.';
            case UPLOAD_ERR_CANT_WRITE:
                return 'Failed to write file to disk.';
            case UPLOAD_ERR_EXTENSION:
                return 'A PHP extension stopped the file upload.';
            default:
                return 'Unknown upload error.';
        }
    }
    
    /**
     * Get validation errors
     * 
     * @return array List of error messages
     */
    public function getErrors(): array {
        return $this->errors;
    }
    
    /**
     * Check if upload had errors
     * 
     * @return bool True if there were errors
     */
    public function hasErrors(): bool {
        return !empty($this->errors);
    }
}

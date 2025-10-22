<?php
declare(strict_types=1);

namespace App\Classes;

/**
 * Secure File Upload Manager
 * Handles file uploads with validation, sanitization and security checks
 */
class FileUpload
{
    /**
     * @var array Default allowed MIME types
     */
    private $allowedTypes = [];
    
    /**
     * @var array Default allowed file extensions
     */
    private $allowedExtensions = [];
    
    /**
     * @var int Maximum file size in bytes (default 5MB)
     */
    private $maxSize = 5242880;
    
    /**
     * @var string Upload destination directory
     */
    private $uploadDir = '';
    
    /**
     * @var string Subdirectory within upload directory
     */
    private $subDir = '';
    
    /**
     * @var array Validation errors
     */
    private $errors = [];

    /**
     * @var array Common MIME type whitelist by category
     */
    private $mimeWhitelists = [
        'image' => [
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp',
            'image/svg+xml'
        ],
        'document' => [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document', // docx
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', // xlsx
            'text/plain',
            'text/csv'
        ],
        'video' => [
            'video/mp4',
            'video/webm',
            'video/ogg'
        ]
    ];

    /**
     * @var array Extension whitelist by category
     */
    private $extensionWhitelists = [
        'image' => ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'],
        'document' => ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt', 'csv'],
        'video' => ['mp4', 'webm', 'ogg']
    ];

    /**
     * Constructor
     * 
     * @param string $uploadDir Base upload directory path
     * @param string $fileType Type of file ('image', 'document', 'video')
     * @param int|null $maxSize Maximum file size in bytes
     */
    public function __construct(string $uploadDir, string $fileType = 'image', ?int $maxSize = null)
    {
        // Ensure upload directory exists and is writable
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $this->uploadDir = rtrim($uploadDir, '/\\') . DIRECTORY_SEPARATOR;
        
        // Set file type restrictions
        if (isset($this->mimeWhitelists[$fileType])) {
            $this->allowedTypes = $this->mimeWhitelists[$fileType];
            $this->allowedExtensions = $this->extensionWhitelists[$fileType];
        } else {
            // Default to images if type not recognized
            $this->allowedTypes = $this->mimeWhitelists['image'];
            $this->allowedExtensions = $this->extensionWhitelists['image'];
        }
        
        // Set custom max size if provided
        if ($maxSize !== null) {
            $this->maxSize = $maxSize;
        }
    }

    /**
     * Set upload subdirectory
     * 
     * @param string $subDir Subdirectory name
     * @return $this
     */
    public function setSubDirectory(string $subDir): self
    {
        $this->subDir = trim($subDir, '/\\');
        $fullPath = $this->uploadDir . $this->subDir;
        
        if (!is_dir($fullPath)) {
            mkdir($fullPath, 0755, true);
        }
        
        return $this;
    }

    /**
     * Set custom allowed MIME types
     * 
     * @param array $mimeTypes Array of allowed MIME types
     * @return $this
     */
    public function setAllowedTypes(array $mimeTypes): self
    {
        $this->allowedTypes = $mimeTypes;
        return $this;
    }

    /**
     * Set custom allowed file extensions
     * 
     * @param array $extensions Array of allowed extensions
     * @return $this
     */
    public function setAllowedExtensions(array $extensions): self
    {
        $this->allowedExtensions = $extensions;
        return $this;
    }

    /**
     * Set maximum file size
     * 
     * @param int $bytes Maximum size in bytes
     * @return $this
     */
    public function setMaxSize(int $bytes): self
    {
        $this->maxSize = $bytes;
        return $this;
    }

    /**
     * Upload a file from $_FILES array
     * 
     * @param array $file File data from $_FILES array
     * @param string|null $customFilename Custom filename (optional)
     * @return array Result with status and file info
     */
    public function upload(array $file, ?string $customFilename = null): array
    {
        // Reset errors
        $this->errors = [];
        
        // Check if file was uploaded
        if (!isset($file['tmp_name']) || empty($file['tmp_name'])) {
            $this->errors[] = 'هیچ فایلی آپلود نشده است';
            return $this->getErrorResponse();
        }
        
        // Check upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $this->errors[] = $this->getUploadErrorMessage($file['error']);
            return $this->getErrorResponse();
        }

        // Validate file size
        if ($file['size'] > $this->maxSize) {
            $this->errors[] = 'حجم فایل بیشتر از حد مجاز است. حداکثر حجم مجاز: ' . 
                $this->formatBytes($this->maxSize);
            return $this->getErrorResponse();
        }

        // Validate file type using multiple methods for security
        if (!$this->validateFileType($file)) {
            $this->errors[] = 'نوع فایل مجاز نیست. فرمت‌های مجاز: ' . 
                implode(', ', $this->allowedExtensions);
            return $this->getErrorResponse();
        }

        // Create a secure filename
        $filename = $this->createSecureFilename($file, $customFilename);
        
        // Determine upload path
        $uploadPath = $this->uploadDir;
        if (!empty($this->subDir)) {
            $uploadPath .= $this->subDir . DIRECTORY_SEPARATOR;
        }
        
        // Full path with filename
        $fullPath = $uploadPath . $filename;
        
        // Move uploaded file to destination
        if (!move_uploaded_file($file['tmp_name'], $fullPath)) {
            $this->errors[] = 'خطا در انتقال فایل. لطفا دوباره تلاش کنید';
            return $this->getErrorResponse();
        }
        
        // For images, optionally validate dimensions and verify image integrity
        if (strpos($file['type'], 'image/') === 0) {
            if (!$this->validateImage($fullPath)) {
                // Remove invalid file
                @unlink($fullPath);
                $this->errors[] = 'فایل تصویری معتبر نیست';
                return $this->getErrorResponse();
            }
        }

        // Create a .htaccess file to prevent direct PHP execution in upload directory
        $this->secureUploadDirectory($uploadPath);
        
        // Calculate relative path for storage in database
        $relativePath = empty($this->subDir) ? $filename : $this->subDir . '/' . $filename;
        
        return [
            'success' => true,
            'file' => [
                'name' => $filename,
                'path' => $fullPath,
                'relative_path' => $relativePath,
                'url' => $this->getFileUrl($relativePath),
                'type' => $file['type'],
                'size' => $file['size']
            ]
        ];
    }
    
    /**
     * Create a secure filename
     * 
     * @param array $file Original file data
     * @param string|null $customFilename Custom filename (optional)
     * @return string Secure filename
     */
    private function createSecureFilename(array $file, ?string $customFilename = null): string
    {
        // Get file extension
        $originalName = $file['name'];
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        
        // Use custom filename if provided
        if ($customFilename !== null) {
            // Sanitize custom filename
            $basename = $this->sanitizeFilename($customFilename);
        } else {
            // Generate random filename
            $basename = bin2hex(random_bytes(16));
        }
        
        // Add date prefix for organization
        $prefix = date('Ymd_His_');
        
        return $prefix . $basename . '.' . $extension;
    }
    
    /**
     * Sanitize a filename
     * 
     * @param string $filename Original filename
     * @return string Sanitized filename
     */
    private function sanitizeFilename(string $filename): string
    {
        // Remove any path components
        $filename = basename($filename);
        
        // Transliterate non-ASCII characters
        if (function_exists('transliterator_transliterate')) {
            $filename = transliterator_transliterate('Any-Latin; Latin-ASCII', $filename);
        }
        
        // Replace spaces with underscores
        $filename = str_replace(' ', '_', $filename);
        
        // Remove all non-alphanumeric characters except underscores and hyphens
        $filename = preg_replace('/[^A-Za-z0-9_\-]/', '', $filename);
        
        // Ensure the filename is not empty
        if (empty($filename)) {
            $filename = 'file_' . time();
        }
        
        return $filename;
    }
    
    /**
     * Validate file type using multiple methods
     * 
     * @param array $file File data
     * @return bool True if file type is valid
     */
    private function validateFileType(array $file): bool
    {
        // Get file extension
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        // Check against allowed extensions
        if (!in_array($extension, $this->allowedExtensions)) {
            return false;
        }
        
        // Check MIME type from $_FILES
        if (!in_array($file['type'], $this->allowedTypes)) {
            return false;
        }
        
        // Double-check MIME type using finfo (more reliable)
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file['tmp_name']);
        
        if (!in_array($mimeType, $this->allowedTypes)) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Validate image file
     * 
     * @param string $path Path to image file
     * @return bool True if image is valid
     */
    private function validateImage(string $path): bool
    {
        // Try to create image from file to verify it's a valid image
        $imageInfo = @getimagesize($path);
        
        if ($imageInfo === false) {
            return false;
        }
        
        // Optionally check for reasonable image dimensions
        list($width, $height) = $imageInfo;
        if ($width <= 0 || $height <= 0 || $width > 8000 || $height > 8000) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Create security measures for upload directory
     * 
     * @param string $directory Directory path
     */
    private function secureUploadDirectory(string $directory): void
    {
        $htaccessPath = $directory . '.htaccess';
        
        // Only create .htaccess if it doesn't exist
        if (!file_exists($htaccessPath)) {
            $htaccessContent = <<<EOT
# Prevent execution of PHP files
<FilesMatch "\.php$">
    Order Allow,Deny
    Deny from all
</FilesMatch>

# Prevent showing directory listing
Options -Indexes

# PHP settings
<IfModule mod_php7.c>
    php_flag engine off
</IfModule>

<IfModule mod_php8.c>
    php_flag engine off
</IfModule>
EOT;
            
            file_put_contents($htaccessPath, $htaccessContent);
        }
    }
    
    /**
     * Generate file URL
     * 
     * @param string $relativePath Relative path to file
     * @return string URL to file
     */
    private function getFileUrl(string $relativePath): string
    {
        // Get upload directory base name
        $dirName = basename($this->uploadDir);
        
        // Determine project base URL - this could be improved or configured elsewhere
        $baseUrl = '/carwash_project';
        
        return $baseUrl . '/' . $dirName . '/' . $relativePath;
    }
    
    /**
     * Get upload error message
     * 
     * @param int $errorCode PHP upload error code
     * @return string Error message
     */
    private function getUploadErrorMessage(int $errorCode): string
    {
        switch ($errorCode) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                return 'حجم فایل بیشتر از حد مجاز است';
            case UPLOAD_ERR_PARTIAL:
                return 'فایل به صورت ناقص آپلود شده است';
            case UPLOAD_ERR_NO_FILE:
                return 'هیچ فایلی آپلود نشده است';
            case UPLOAD_ERR_NO_TMP_DIR:
            case UPLOAD_ERR_CANT_WRITE:
            case UPLOAD_ERR_EXTENSION:
                return 'خطای سیستمی در آپلود فایل. لطفا با پشتیبانی تماس بگیرید';
            default:
                return 'خطای نامشخص در آپلود فایل';
        }
    }
    
    /**
     * Format bytes to human-readable format
     * 
     * @param int $bytes Bytes to format
     * @param int $precision Decimal precision
     * @return string Formatted size string
     */
    private function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= (1 << (10 * $pow));
        
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
    
    /**
     * Get error response
     * 
     * @return array Error response
     */
    private function getErrorResponse(): array
    {
        return [
            'success' => false,
            'errors' => $this->errors
        ];
    }
    
    /**
     * Get all validation errors
     * 
     * @return array Validation errors
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
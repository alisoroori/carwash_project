<?php
declare(strict_types=1);

namespace App\Classes;

/**
 * API Response Handler
 * Standardizes JSON API responses with proper headers and status codes
 */
class Response
{
    /**
     * Send a success response
     * 
     * @param string $message Success message
     * @param array $data Response data
     * @param int $statusCode HTTP status code
     */
    public static function success(string $message, array $data = [], int $statusCode = 200): void
    {
        self::send([
            'success' => true,
            'message' => $message,
            'data' => $data
        ], $statusCode);
    }
    
    /**
     * Send an error response
     * 
     * @param string $message Error message
     * @param int $statusCode HTTP status code
     * @param array $errors Additional error details
     */
    public static function error(string $message, int $statusCode = 400, array $errors = []): void
    {
        $response = [
            'success' => false,
            'message' => $message
        ];
        
        if (!empty($errors)) {
            $response['errors'] = $errors;
        }
        
        self::send($response, $statusCode);
    }
    
    /**
     * Send a validation error response
     * 
     * @param array $errors Validation errors
     * @param string $message Error message
     */
    public static function validationError(array $errors, string $message = 'اطلاعات وارد شده نامعتبر است'): void
    {
        self::error($message, 422, $errors);
    }
    
    /**
     * Send a not found response
     * 
     * @param string $message Error message
     */
    public static function notFound(string $message = 'منبع مورد نظر یافت نشد'): void
    {
        self::error($message, 404);
    }
    
    /**
     * Send an unauthorized response
     * 
     * @param string $message Error message
     */
    public static function unauthorized(string $message = 'دسترسی غیرمجاز'): void
    {
        self::error($message, 401);
    }
    
    /**
     * Send a forbidden response
     * 
     * @param string $message Error message
     */
    public static function forbidden(string $message = 'دسترسی به این بخش مجاز نیست'): void
    {
        self::error($message, 403);
    }
    
    /**
     * Send a JSON response
     * 
     * @param mixed $data Response data
     * @param int $statusCode HTTP status code
     */
    public static function send($data, int $statusCode = 200): void
    {
        // Set security headers
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('X-XSS-Protection: 1; mode=block');
        header('Content-Type: application/json; charset=utf-8');
        
        // Set status code
        http_response_code($statusCode);
        
        // Encode and output JSON
        $flags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;
        
        if (defined('JSON_THROW_ON_ERROR')) {
            $flags |= JSON_THROW_ON_ERROR;
        }
        
        try {
            echo json_encode($data, $flags);
        } catch (\Exception $e) {
            // Fallback for encoding errors
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'message' => 'خطا در سرویس‌دهی. لطفا دوباره تلاش کنید'
            ]);
        }
        
        exit;
    }
}
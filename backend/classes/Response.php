<?php
declare(strict_types=1);

namespace App\Classes;

/**
 * API Response Handler
 */
class Response
{
    /**
     * Send success response
     * 
     * @param string $message Success message
     * @param array $data Additional data
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
     * Send error response
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
     * Send validation error response
     * 
     * @param array $errors Validation errors
     * @param string $message Error message
     */
    public static function validationError(array $errors, string $message = 'اطلاعات وارد شده نامعتبر است'): void
    {
        self::error($message, 422, $errors);
    }
    
    /**
     * Send not found response
     * 
     * @param string $message Error message
     */
    public static function notFound(string $message = 'منبع مورد نظر یافت نشد'): void
    {
        self::error($message, 404);
    }
    
    /**
     * Send unauthorized response
     * 
     * @param string $message Error message
     */
    public static function unauthorized(string $message = 'دسترسی غیرمجاز'): void
    {
        self::error($message, 401);
    }
    
    /**
     * Send forbidden response
     * 
     * @param string $message Error message
     */
    public static function forbidden(string $message = 'دسترسی به این بخش مجاز نیست'): void
    {
        self::error($message, 403);
    }
    
    /**
     * Send JSON response
     * 
     * @param mixed $data Response data
     * @param int $statusCode HTTP status code
     */
    private static function send($data, int $statusCode = 200): void
    {
        // Set security headers
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('X-XSS-Protection: 1; mode=block');
        header('Content-Type: application/json; charset=utf-8');
        
        // Set status code
        http_response_code($statusCode);
        
        // Output JSON
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
}
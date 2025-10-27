<?php
declare(strict_types=1);

namespace App\Classes;

/**
 * API Response Handler
 * 
 * Provides standardized JSON responses for API endpoints
 */
class Response
{
    /**
     * Send success response
     * 
     * @param string $message Success message
     * @param array $data Additional data
     * @param int $code HTTP status code
     */
    public static function success(string $message = 'Success', array $data = [], int $code = 200): void
    {
        self::send(true, $message, $data, $code);
    }
    
    /**
     * Send error response
     * 
     * @param string $message Error message
     * @param int $code HTTP status code
     * @param array $errors Detailed errors
     */
    public static function error(string $message = 'Error', int $code = 400, array $errors = []): void
    {
        $data = empty($errors) ? [] : ['errors' => $errors];
        self::send(false, $message, $data, $code);
    }
    
    /**
     * Send not found response
     * 
     * @param string $message Not found message
     */
    public static function notFound(string $message = 'Resource not found'): void
    {
        self::error($message, 404);
    }
    
    /**
     * Send unauthorized response
     * 
     * @param string $message Unauthorized message
     */
    public static function unauthorized(string $message = 'Authentication required'): void
    {
        self::error($message, 401);
    }
    
    /**
     * Send forbidden response
     * 
     * @param string $message Forbidden message
     */
    public static function forbidden(string $message = 'You do not have permission to access this resource'): void
    {
        self::error($message, 403);
    }
    
    /**
     * Send validation error response
     * 
     * @param array $errors Validation errors
     * @param string $message Error message
     */
    public static function validationError(array $errors, string $message = 'Validation failed'): void
    {
        self::error($message, 422, $errors);
    }
    
    /**
     * Send response
     * 
     * @param bool $success Success status
     * @param string $message Response message
     * @param array $data Additional data
     * @param int $code HTTP status code
     */
    private static function send(bool $success, string $message, array $data, int $code): void
    {
        // Set HTTP response code
        http_response_code($code);
        
        // Set content type
        header('Content-Type: application/json; charset=utf-8');
        
        // Build response
        $response = [
            'success' => $success,
            'message' => $message
        ];
        
        // Add data if provided
        if (!empty($data)) {
            $response = array_merge($response, $data);
        }
        
        // Send JSON response
        echo json_encode($response);
        exit;
    }
}

<?php
/**
 * JSON Response Class (PSR-4 Autoloaded)
 * Standardized API response handler
 * 
 * @package App\Classes
 * @namespace App\Classes
 */

namespace App\Classes;

class Response {
    
    /**
     * Send JSON success response
     */
    public static function success($message, $data = null, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        
        $response = [
            'success' => true,
            'message' => $message
        ];
        
        if ($data !== null) {
            $response['data'] = $data;
        }
        
        echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }
    
    /**
     * Send JSON error response
     */
    public static function error($message, $statusCode = 400, $errors = null) {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        
        $response = [
            'success' => false,
            'message' => $message
        ];
        
        if ($errors !== null) {
            $response['errors'] = $errors;
        }
        
        echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }
    
    /**
     * Send 404 Not Found response
     */
    public static function notFound($message = 'منبع یافت نشد') {
        self::error($message, 404);
    }
    
    /**
     * Send 401 Unauthorized response
     */
    public static function unauthorized($message = 'احراز هویت نشده') {
        self::error($message, 401);
    }
    
    /**
     * Send 403 Forbidden response
     */
    public static function forbidden($message = 'دسترسی غیرمجاز') {
        self::error($message, 403);
    }
    
    /**
     * Send 422 Validation Error response
     */
    public static function validationError($errors, $message = 'خطای اعتبارسنجی') {
        self::error($message, 422, $errors);
    }
    
    /**
     * Send 500 Server Error response
     */
    public static function serverError($message = 'خطای سرور') {
        self::error($message, 500);
    }
}
?>
<?php
/**
 * Input Validation Class (PSR-4 Autoloaded)
 * Comprehensive input validation and sanitization
 * 
 * @package App\Classes
 * @namespace App\Classes
 */

namespace App\Classes;

class Validator {
    
    /**
     * Validation errors
     */
    private $errors = [];
    
    /**
     * Validate required field
     */
    public function required($value, $fieldName) {
        if (empty($value) && $value !== '0' && $value !== 0) {
            $this->errors[$fieldName] = "{$fieldName} الزامی است";
        }
        return $this;
    }
    
    /**
     * Validate email address
     */
    public function email($email, $fieldName = 'ایمیل') {
        if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->errors[$fieldName] = "{$fieldName} معتبر نیست";
        }
        return $this;
    }
    
    /**
     * Validate minimum length
     */
    public function minLength($value, $min, $fieldName) {
        if (!empty($value) && mb_strlen($value) < $min) {
            $this->errors[$fieldName] = "{$fieldName} باید حداقل {$min} کاراکتر باشد";
        }
        return $this;
    }
    
    /**
     * Validate maximum length
     */
    public function maxLength($value, $max, $fieldName) {
        if (!empty($value) && mb_strlen($value) > $max) {
            $this->errors[$fieldName] = "{$fieldName} نباید بیشتر از {$max} کاراکتر باشد";
        }
        return $this;
    }
    
    /**
     * Validate phone number
     */
    public function phone($phone, $fieldName = 'تلفن') {
        if (!empty($phone) && !preg_match('/^[\+]?[(]?[0-9]{1,4}[)]?[-\s\.]?[(]?[0-9]{1,4}[)]?[-\s\.]?[0-9]{1,9}$/', $phone)) {
            $this->errors[$fieldName] = "{$fieldName} معتبر نیست";
        }
        return $this;
    }
    
    /**
     * Validate numeric value
     */
    public function numeric($value, $fieldName) {
        if (!empty($value) && !is_numeric($value)) {
            $this->errors[$fieldName] = "{$fieldName} باید عدد باشد";
        }
        return $this;
    }
    
    /**
     * Validate integer value
     */
    public function integer($value, $fieldName) {
        if (!empty($value) && !filter_var($value, FILTER_VALIDATE_INT)) {
            $this->errors[$fieldName] = "{$fieldName} باید عدد صحیح باشد";
        }
        return $this;
    }
    
    /**
     * Validate match (e.g., password confirmation)
     */
    public function match($value1, $value2, $fieldName) {
        if ($value1 !== $value2) {
            $this->errors[$fieldName] = "{$fieldName} مطابقت ندارد";
        }
        return $this;
    }
    
    /**
     * Validate URL
     */
    public function url($url, $fieldName = 'آدرس') {
        if (!empty($url) && !filter_var($url, FILTER_VALIDATE_URL)) {
            $this->errors[$fieldName] = "{$fieldName} معتبر نیست";
        }
        return $this;
    }
    
    /**
     * Validate date format
     */
    public function date($date, $format = 'Y-m-d', $fieldName = 'تاریخ') {
        if (!empty($date)) {
            $d = \DateTime::createFromFormat($format, $date);
            if (!$d || $d->format($format) !== $date) {
                $this->errors[$fieldName] = "{$fieldName} معتبر نیست";
            }
        }
        return $this;
    }
    
    /**
     * Validate minimum value
     */
    public function min($value, $min, $fieldName) {
        if (!empty($value) && $value < $min) {
            $this->errors[$fieldName] = "{$fieldName} نباید کمتر از {$min} باشد";
        }
        return $this;
    }
    
    /**
     * Validate maximum value
     */
    public function max($value, $max, $fieldName) {
        if (!empty($value) && $value > $max) {
            $this->errors[$fieldName] = "{$fieldName} نباید بیشتر از {$max} باشد";
        }
        return $this;
    }
    
    /**
     * Check if validation passed
     */
    public function passes() {
        return empty($this->errors);
    }
    
    /**
     * Check if validation failed
     */
    public function fails() {
        return !$this->passes();
    }
    
    /**
     * Get all validation errors
     */
    public function getErrors() {
        return $this->errors;
    }
    
    /**
     * Get first error message
     */
    public function getFirstError() {
        return !empty($this->errors) ? reset($this->errors) : null;
    }
    
    /**
     * Add custom error
     */
    public function addError($fieldName, $message) {
        $this->errors[$fieldName] = $message;
        return $this;
    }
    
    /**
     * Clear all errors
     */
    public function clearErrors() {
        $this->errors = [];
        return $this;
    }
    
    /**
     * Sanitize string
     */
    public static function sanitizeString($value) {
        return htmlspecialchars(strip_tags(trim($value)), ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Sanitize email
     */
    public static function sanitizeEmail($email) {
        return filter_var(trim($email), FILTER_SANITIZE_EMAIL);
    }
    
    /**
     * Sanitize integer
     */
    public static function sanitizeInt($value) {
        return (int) filter_var($value, FILTER_SANITIZE_NUMBER_INT);
    }
    
    /**
     * Sanitize float
     */
    public static function sanitizeFloat($value) {
        return (float) filter_var($value, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    }
    
    /**
     * Sanitize URL
     */
    public static function sanitizeUrl($url) {
        return filter_var(trim($url), FILTER_SANITIZE_URL);
    }
}
?>
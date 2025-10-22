<?php
declare(strict_types=1);

namespace App\Classes;

/**
 * Input Validator and Sanitizer
 * Provides methods for validating and sanitizing user inputs
 */
class Validator 
{
    private $errors = [];
    
    /**
     * Check if a field is required
     * 
     * @param mixed $value Field value
     * @param string $fieldName Field name for error message
     * @return $this Validator instance
     */
    public function required($value, string $fieldName): self 
    {
        if (empty($value) && $value !== '0' && $value !== 0) {
            $this->errors[$fieldName][] = "$fieldName الزامی است";
        }
        
        return $this;
    }
    
    /**
     * Check if a value is a valid email
     * 
     * @param mixed $value Field value
     * @param string $fieldName Field name for error message
     * @return $this Validator instance
     */
    public function email($value, string $fieldName): self 
    {
        if (!empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->errors[$fieldName][] = "$fieldName باید یک ایمیل معتبر باشد";
        }
        
        return $this;
    }
    
    /**
     * Check if a value has minimum length
     * 
     * @param mixed $value Field value
     * @param int $length Minimum length
     * @param string $fieldName Field name for error message
     * @return $this Validator instance
     */
    public function minLength($value, int $length, string $fieldName): self 
    {
        if (!empty($value) && mb_strlen($value) < $length) {
            $this->errors[$fieldName][] = "$fieldName باید حداقل $length کاراکتر باشد";
        }
        
        return $this;
    }
    
    /**
     * Check if a value has maximum length
     * 
     * @param mixed $value Field value
     * @param int $length Maximum length
     * @param string $fieldName Field name for error message
     * @return $this Validator instance
     */
    public function maxLength($value, int $length, string $fieldName): self 
    {
        if (!empty($value) && mb_strlen($value) > $length) {
            $this->errors[$fieldName][] = "$fieldName باید حداکثر $length کاراکتر باشد";
        }
        
        return $this;
    }
    
    /**
     * Check if a value is numeric
     * 
     * @param mixed $value Field value
     * @param string $fieldName Field name for error message
     * @return $this Validator instance
     */
    public function numeric($value, string $fieldName): self 
    {
        if (!empty($value) && !is_numeric($value)) {
            $this->errors[$fieldName][] = "$fieldName باید عددی باشد";
        }
        
        return $this;
    }
    
    /**
     * Check if a value is in a list of options
     * 
     * @param mixed $value Field value
     * @param array $options Valid options
     * @param string $fieldName Field name for error message
     * @return $this Validator instance
     */
    public function inList($value, array $options, string $fieldName): self 
    {
        if (!empty($value) && !in_array($value, $options, true)) {
            $this->errors[$fieldName][] = "$fieldName باید یکی از مقادیر معتبر باشد";
        }
        
        return $this;
    }
    
    /**
     * Check if a value matches a regex pattern
     * 
     * @param mixed $value Field value
     * @param string $pattern Regex pattern
     * @param string $fieldName Field name for error message
     * @param string $message Custom error message
     * @return $this Validator instance
     */
    public function pattern($value, string $pattern, string $fieldName, string $message = null): self 
    {
        if (!empty($value) && !preg_match($pattern, $value)) {
            $this->errors[$fieldName][] = $message ?? "$fieldName با الگوی مورد نظر مطابقت ندارد";
        }
        
        return $this;
    }
    
    /**
     * Add a custom error message
     * 
     * @param string $fieldName Field name
     * @param string $message Error message
     * @return $this Validator instance
     */
    public function addError(string $fieldName, string $message): self 
    {
        $this->errors[$fieldName][] = $message;
        return $this;
    }
    
    /**
     * Check if validation fails
     * 
     * @return bool True if validation fails
     */
    public function fails(): bool 
    {
        return !empty($this->errors);
    }
    
    /**
     * Check if validation passes
     * 
     * @return bool True if validation passes
     */
    public function passes(): bool 
    {
        return empty($this->errors);
    }
    
    /**
     * Get all validation errors
     * 
     * @return array Validation errors
     */
    public function getErrors(): array 
    {
        $flatErrors = [];
        
        foreach ($this->errors as $field => $messages) {
            $flatErrors[$field] = $messages[0];
        }
        
        return $flatErrors;
    }
    
    /**
     * Sanitize email
     * 
     * @param string $email Email to sanitize
     * @return string Sanitized email
     */
    public static function sanitizeEmail(string $email): string 
    {
        return filter_var(trim($email), FILTER_SANITIZE_EMAIL);
    }
    
    /**
     * Sanitize string
     * 
     * @param string $string String to sanitize
     * @return string Sanitized string
     */
    public static function sanitizeString(string $string): string 
    {
        $string = trim($string);
        $string = strip_tags($string);
        return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Sanitize integer
     * 
     * @param mixed $value Value to sanitize
     * @return int Sanitized integer
     */
    public static function sanitizeInt($value): int 
    {
        return (int) filter_var($value, FILTER_SANITIZE_NUMBER_INT);
    }
    
    /**
     * Sanitize float
     * 
     * @param mixed $value Value to sanitize
     * @return float Sanitized float
     */
    public static function sanitizeFloat($value): float 
    {
        return (float) filter_var(
            $value, 
            FILTER_SANITIZE_NUMBER_FLOAT, 
            FILTER_FLAG_ALLOW_FRACTION
        );
    }
    
    /**
     * Safe output encoding for HTML
     * 
     * @param string $string String to encode
     * @return string Encoded string
     */
    public static function escapeHtml(string $string): string 
    {
        return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
    }
}
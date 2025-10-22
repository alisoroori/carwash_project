<?php
declare(strict_types=1);

namespace App\Classes;

/**
 * Input validation and sanitization
 */
class Validator
{
    private $errors = [];
    
    /**
     * Validate required field
     * 
     * @param mixed $value Value to check
     * @param string $fieldName Field name for error message
     * @return $this For method chaining
     */
    public function required($value, string $fieldName): self
    {
        if (empty($value) && $value !== '0' && $value !== 0) {
            $this->errors[$fieldName][] = "$fieldName الزامی است";
        }
        
        return $this;
    }
    
    /**
     * Validate email format
     * 
     * @param mixed $value Value to check
     * @param string $fieldName Field name for error message
     * @return $this For method chaining
     */
    public function email($value, string $fieldName): self
    {
        if (!empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $this->errors[$fieldName][] = "$fieldName باید یک ایمیل معتبر باشد";
        }
        
        return $this;
    }
    
    /**
     * Validate minimum length
     * 
     * @param mixed $value Value to check
     * @param int $length Minimum length
     * @param string $fieldName Field name for error message
     * @return $this For method chaining
     */
    public function minLength($value, int $length, string $fieldName): self
    {
        if (!empty($value) && mb_strlen($value) < $length) {
            $this->errors[$fieldName][] = "$fieldName باید حداقل $length کاراکتر باشد";
        }
        
        return $this;
    }
    
    /**
     * Validate maximum length
     * 
     * @param mixed $value Value to check
     * @param int $length Maximum length
     * @param string $fieldName Field name for error message
     * @return $this For method chaining
     */
    public function maxLength($value, int $length, string $fieldName): self
    {
        if (!empty($value) && mb_strlen($value) > $length) {
            $this->errors[$fieldName][] = "$fieldName باید حداکثر $length کاراکتر باشد";
        }
        
        return $this;
    }
    
    /**
     * Check if value is numeric
     * 
     * @param mixed $value Value to check
     * @param string $fieldName Field name for error message
     * @return $this For method chaining
     */
    public function numeric($value, string $fieldName): self
    {
        if (!empty($value) && !is_numeric($value)) {
            $this->errors[$fieldName][] = "$fieldName باید عددی باشد";
        }
        
        return $this;
    }
    
    /**
     * Check if value matches a regex pattern
     * 
     * @param mixed $value Value to check
     * @param string $pattern Regex pattern
     * @param string $fieldName Field name for error message
     * @param string|null $message Custom error message
     * @return $this For method chaining
     */
    public function pattern($value, string $pattern, string $fieldName, ?string $message = null): self
    {
        if (!empty($value) && !preg_match($pattern, $value)) {
            $this->errors[$fieldName][] = $message ?? "$fieldName با الگوی مورد نظر مطابقت ندارد";
        }
        
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
     * Get validation errors
     * 
     * @return array Validation errors
     */
    public function getErrors(): array
    {
        $flatErrors = [];
        
        foreach ($this->errors as $field => $messages) {
            $flatErrors[$field] = $messages[0]; // Return first error message for each field
        }
        
        return $flatErrors;
    }
    
    /**
     * Static method to sanitize email
     * 
     * @param string $email Email to sanitize
     * @return string Sanitized email
     */
    public static function sanitizeEmail(string $email): string
    {
        return filter_var(trim($email), FILTER_SANITIZE_EMAIL);
    }
    
    /**
     * Static method to sanitize string
     * 
     * @param string $string String to sanitize
     * @return string Sanitized string
     */
    public static function sanitizeString(string $string): string
    {
        return htmlspecialchars(trim($string), ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Static method to sanitize integer
     * 
     * @param mixed $value Value to sanitize
     * @return int Sanitized integer
     */
    public static function sanitizeInt($value): int
    {
        return (int) filter_var($value, FILTER_SANITIZE_NUMBER_INT);
    }
    
    /**
     * Static method to sanitize float
     * 
     * @param mixed $value Value to sanitize
     * @return float Sanitized float
     */
    public static function sanitizeFloat($value): float
    {
        return (float) filter_var($value, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    }
    
    /**
     * Escape HTML output
     * 
     * @param string $string String to escape
     * @return string Escaped string
     */
    public static function escapeHtml(string $string): string
    {
        return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
    }
}
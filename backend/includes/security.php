<?php

class Security {
    private static $instance = null;
    private $rateLimits = [];
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function validateInput($data, $rules) {
        $errors = [];
        foreach ($rules as $field => $rule) {
            if (!isset($data[$field])) {
                $errors[$field] = "Field is required";
                continue;
            }

            $value = $data[$field];
            foreach ($rule as $validation => $param) {
                switch ($validation) {
                    case 'min':
                        if (strlen($value) < $param) {
                            $errors[$field] = "Minimum length is $param";
                        }
                        break;
                    case 'max':
                        if (strlen($value) > $param) {
                            $errors[$field] = "Maximum length is $param";
                        }
                        break;
                    case 'email':
                        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                            $errors[$field] = "Invalid email format";
                        }
                        break;
                    case 'regex':
                        if (!preg_match($param, $value)) {
                            $errors[$field] = "Invalid format";
                        }
                        break;
                }
            }
        }
        return $errors;
    }

    public function sanitize($input) {
        if (is_array($input)) {
            return array_map([$this, 'sanitize'], $input);
        }
        return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
    }

    public function generateCSRFToken() {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public function validateCSRFToken($token) {
        if (empty($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
            throw new Exception('CSRF token validation failed');
        }
        return true;
    }

    public function checkRateLimit($key, $limit = 60, $period = 60) {
        $current = time();
        if (!isset($this->rateLimits[$key])) {
            $this->rateLimits[$key] = ['count' => 0, 'reset' => $current + $period];
        }

        if ($current > $this->rateLimits[$key]['reset']) {
            $this->rateLimits[$key] = ['count' => 0, 'reset' => $current + $period];
        }

        $this->rateLimits[$key]['count']++;

        if ($this->rateLimits[$key]['count'] > $limit) {
            throw new Exception('Rate limit exceeded');
        }

        return true;
    }
}
<?php
declare(strict_types=1);

namespace App\Classes;

class SecurityMiddleware {
    public static function handle() {
        // Set security headers
        header('X-Frame-Options: DENY');
        header('X-XSS-Protection: 1; mode=block');
        header('X-Content-Type-Options: nosniff');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Content-Security-Policy: default-src \'self\'');
        
        // Verify HTTPS in production
        if (Config::isProduction() && empty($_SERVER['HTTPS'])) {
            header('Location: https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
            exit;
        }
    }
}

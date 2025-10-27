<?php
namespace App\Classes;

class Logger
{
    private static $driver = null; // Monolog instance or null => fallback to error_log file

    public static function init(string $logPath = null): void
    {
        if (self::$driver !== null) {
            return;
        }

        $logDir = __DIR__ . '/../../logs';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }

        $logFile = $logPath ?? $logDir . '/app.log';

        if (class_exists('\Monolog\Logger') && class_exists('\Monolog\Handler\StreamHandler')) {
            $mon = new \Monolog\Logger('carwash');
            $mon->pushHandler(new \Monolog\Handler\StreamHandler($logFile, \Monolog\Logger::DEBUG));
            self::$driver = $mon;
        } else {
            // Fallback: ensure PHP error_log goes to our file
            ini_set('log_errors', '1');
            ini_set('error_log', $logFile);
            self::$driver = null;
        }
    }

    public static function error(string $message, array $context = []): void
    {
        if (self::$driver instanceof \Monolog\Logger) {
            self::$driver->error($message, $context);
            return;
        }
        error_log('[ERROR] ' . $message . ' ' . json_encode($context, JSON_UNESCAPED_SLASHES));
    }

    public static function info(string $message, array $context = []): void
    {
        if (self::$driver instanceof \Monolog\Logger) {
            self::$driver->info($message, $context);
            return;
        }
        error_log('[INFO] ' . $message . ' ' . json_encode($context, JSON_UNESCAPED_SLASHES));
    }

    public static function warning(string $message, array $context = []): void
    {
        if (self::$driver instanceof \Monolog\Logger) {
            self::$driver->warning($message, $context);
            return;
        }
        error_log('[WARN] ' . $message . ' ' . json_encode($context, JSON_UNESCAPED_SLASHES));
    }

    public static function exception(\Throwable $e, array $context = []): void
    {
        $msg = sprintf(
            "Uncaught %s: %s in %s:%d\nStack trace:\n%s",
            get_class($e),
            $e->getMessage(),
            $e->getFile(),
            $e->getLine(),
            $e->getTraceAsString()
        );

        if (self::$driver instanceof \Monolog\Logger) {
            self::$driver->error($msg, $context);
            return;
        }

        error_log($msg . ' ' . json_encode($context, JSON_UNESCAPED_SLASHES));
    }
}

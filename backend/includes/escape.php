<?php
declare(strict_types=1);

/**
 * Simple HTML escaping helper used across templates and debug output.
 */
if (!function_exists('escape')) {
    function escape($value): string
    {
        if (is_null($value)) return '';
        if (is_scalar($value)) {
            return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        }
        // For arrays/objects, convert to JSON for safe display
        return htmlspecialchars(json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

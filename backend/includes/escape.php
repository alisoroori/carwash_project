<?php
declare(strict_types=1);

// e_html: escape for HTML content context
if (!function_exists('e_html')) {
    function e_html($v): string
    {
        if (is_null($v)) return '';
        if (is_bool($v)) return $v ? '1' : '0';
        if (is_array($v) || is_object($v)) {
            $v = json_encode($v, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
        return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

// e_attr: attribute context escaping (same as e_html for now)
if (!function_exists('e_attr')) {
    function e_attr($v): string
    {
        return e_html($v);
    }
}

// Backwards-compatible escape() wrapper
if (!function_exists('escape')) {
    function escape($value): string
    {
        return e_html($value);
    }
}

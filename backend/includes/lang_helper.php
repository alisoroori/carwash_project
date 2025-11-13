<?php
/**
 * Lightweight language + direction attribute helper
 *
 * Usage:
 *   require_once __DIR__ . '/lang_helper.php';
 *   // in an including script that outputs HTML:
 *   $html_lang_attrs = get_lang_dir_attrs_for_file(__FILE__);
 *   // or directly in template:
 *   echo '<html ' . get_lang_dir_attrs_for_file(__FILE__) . '>';
 */

if (!function_exists('get_lang_dir_attrs_for_file')) {
    function get_lang_dir_attrs_for_file(string $filePath): string
    {
        $text = '';
        if (is_file($filePath)) {
            // don't read huge files entirely if not necessary; read first 32KB
            $fh = @fopen($filePath, 'r');
            if ($fh) {
                $text = fread($fh, 32768);
                fclose($fh);
            } else {
                $text = @file_get_contents($filePath) ?: '';
            }
        }

        // Heuristic: if Arabic/Persian Unicode block present -> RTL (use fa as default)
        if (preg_match('/[\x{0600}-\x{06FF}\x{0750}-\x{077F}\x{08A0}-\x{08FF}\x{FB50}-\x{FDFF}\x{FE70}-\x{FEFF}]/u', $text)) {
            return 'lang="fa" dir="rtl"';
        }

        // Heuristic: Turkish specific characters or common Turkish words
        if (preg_match('/[ığüşöçİĞÜŞÖÇ]/u', $text) || preg_match('/\b(ve|ile|bir|gün|paneli|işletme|müşteri|ayar)\b/ui', $text)) {
            return 'lang="tr" dir="ltr"';
        }

        // Default to English (LTR)
        return 'lang="en"';
    }
}

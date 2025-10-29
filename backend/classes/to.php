<?php
declare(strict_types=1);

namespace App\Classes;

/**
 * Class UniversalFixes
 *
 * Provides a safe way to output the universal JavaScript fixes for the CarWash project.
 * This keeps backend classes valid PHP while allowing templates to render the script when needed.
 */
class UniversalFixes
{
    /**
     * Return the universal JavaScript as a string (nowdoc to avoid interpolation).
     *
     * @return string
     */
    public static function getScript(): string
    {
        return <<<'JS'
/* CarWash Universal Fixes (lightweight starter version)
   The full original script can be placed here or loaded from a static JS file.
*/
(function () {
    'use strict';

    function initializeUniversalMobileMenu() {
        const body = document.body;
        if (!document.querySelector('.universal-mobile-toggle')) {
            const toggleBtn = document.createElement('button');
            toggleBtn.className = 'universal-mobile-toggle';
            toggleBtn.setAttribute('aria-label', 'Toggle navigation');
            toggleBtn.innerHTML = '<i class="fas fa-bars"></i>';
            body.appendChild(toggleBtn);
        }

        const toggleBtn = document.querySelector('.universal-mobile-toggle');
        const nav = document.querySelector('.universal-mobile-nav');
        const overlay = document.querySelector('.universal-mobile-overlay');

        function openMenu() {
            if (nav) nav.classList.add('open');
            if (overlay) overlay.classList.add('visible');
            if (toggleBtn) toggleBtn.setAttribute('aria-expanded', 'true');
        }

        function closeMenu() {
            if (nav) nav.classList.remove('open');
            if (overlay) overlay.classList.remove('visible');
            if (toggleBtn) toggleBtn.setAttribute('aria-expanded', 'false');
        }

        if (toggleBtn) {
            toggleBtn.addEventListener('click', function () {
                if (nav && nav.classList.contains('open')) {
                    closeMenu();
                } else {
                    openMenu();
                }
            });
            toggleBtn.addEventListener('keydown', function (e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    this.click();
                }
            });
        }

        if (overlay) {
            overlay.addEventListener('click', closeMenu);
        }
    }

    // Minimal initializer - the rest of the original functions (optimizeImages, smooth scrolling, etc.)
    // can be implemented or imported into a dedicated JS file loaded by the frontend.
    function initializeUniversalFixes() {
        try {
            initializeUniversalMobileMenu();
            console.log('CarWash Universal Fixes initialized (minimal).');
        } catch (err) {
            console.error('Error initializing Universal Fixes:', err);
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeUniversalFixes);
    } else {
        initializeUniversalFixes();
    }
})();
JS;
    }

    /**
     * Echo a <script> tag with the universal fixes; call from templates when you need to include the script.
     *
     * Example usage in a PHP template:
     *   \App\Classes\UniversalFixes::renderScriptTag();
     */
    public static function renderScriptTag(): void
    {
        echo '<script>' . PHP_EOL;
        echo self::getScript() . PHP_EOL;
        echo '</script>' . PHP_EOL;
    }
}

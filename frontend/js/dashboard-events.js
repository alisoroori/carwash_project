/**
 * dashboard-events.js
 * Centralized DOM event bindings for dashboard pages.
 * - Attach listeners on DOMContentLoaded
 * - Replace common inline handlers (export buttons, file-trigger buttons, generic data-action hooks)
 */
(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        // Export logs buttons (admin/logs.php)
        try {
            var exportBtns = document.querySelectorAll('.export-logs-btn');
            exportBtns.forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var format = (btn.dataset.format || '').toLowerCase();
                    var exportUrl = 'export_logs.php';
                    if (format === 'pdf') {
                        exportUrl = 'export_logs_pdf.php';
                    } else if (format === 'csv') {
                        // append explicit param for csv (server can use this)
                        exportUrl = 'export_logs.php?format=csv';
                    }
                    window.location.href = exportUrl;
                });
            });
        } catch (e) {
            // Fail gracefully if DOM structure differs
            console.warn('dashboard-events: export-logs binding failed', e);
        }

        // Generic file trigger buttons: <button data-file-target="logoUpload">Choose</button>
        try {
            var fileTriggers = document.querySelectorAll('[data-file-target]');
            fileTriggers.forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var targetId = btn.dataset.fileTarget;
                    if (!targetId) return;
                    var input = document.getElementById(targetId);
                    if (input) input.click();
                });
            });
        } catch (e) {
            console.warn('dashboard-events: file trigger binding failed', e);
        }

        // Generic data-action binding: data-action="functionName" will call window.functionName(event)
        try {
            var actionEls = document.querySelectorAll('[data-action]');
            actionEls.forEach(function (el) {
                el.addEventListener('click', function (ev) {
                    var action = el.dataset.action;
                    if (!action) return;
                    // only call if function exists globally
                    var fn = window[action];
                    if (typeof fn === 'function') {
                        try {
                            fn.call(el, ev);
                        } catch (err) {
                            console.error('dashboard-events: error running action', action, err);
                        }
                    }
                });
            });
        } catch (e) {
            console.warn('dashboard-events: data-action binding failed', e);
        }
    });
})();

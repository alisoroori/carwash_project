<?php
/**
 * Universal CSS Styles for CarWash Project
 * Fixes: Multiple scrollbars, responsiveness, mobile navigation
 * Apply to ALL pages in the project
 */
?>
<style>
/* UNIVERSAL WEBSITE FIXES - CARWASH PROJECT */

/* ========================================
   1. SCROLLBAR FIXES - SITE-WIDE
   ======================================== */
html, body {
    overflow-x: hidden !important;
    scroll-behavior: smooth;
    width: 100%;
    max-width: 100%;
}

/* Prevent double scrollbars globally */
.container, .main-content, .page-wrapper, .dashboard-container {
    overflow-x: hidden;
    max-width: 100%;
    box-sizing: border-box;
}

/* Fix for all elements that might cause horizontal scroll */
* {
    box-sizing: border-box;
}

*:before, *:after {
    box-sizing: border-box;
}

/* Prevent any element from extending beyond viewport */
img, video, iframe, object, embed {
    max-width: 100%;
    height: auto;
}

/* ========================================
   2. RESPONSIVE FRAMEWORK - MOBILE FIRST
   ======================================== */

/* Base mobile styles (320px and up) */
.responsive-container {
    width: 100%;
    max-width: 100%;
    padding: 0 1rem;
    margin: 0 auto;
}

.responsive-grid {
    display: grid;
    gap: 1rem;
    grid-template-columns: 1fr;
    width: 100%;
    max-width: 100%;
}

.responsive-flex {
    display: flex;
    flex-direction: column;
    gap: 1rem;
    width: 100%;
}

/* Tablet styles (768px and up) */
@media (min-width: 768px) {
    .responsive-container {
        padding: 0 1.5rem;
    }
    
    .responsive-grid {
        grid-template-columns: repeat(2, 1fr);
        gap: 1.5rem;
    }
    
    .responsive-flex {
        flex-direction: row;
        gap: 1.5rem;
    }
}

/* Desktop styles (1024px and up) */
@media (min-width: 1024px) {
    .responsive-container {
        max-width: 1200px;
        padding: 0 2rem;
    }
    
    .responsive-grid {
        grid-template-columns: repeat(3, 1fr);
        gap: 2rem;
    }
}

/* Large desktop styles (1440px and up) */
@media (min-width: 1440px) {
    .responsive-container {
        max-width: 1400px;
    }
    
    .responsive-grid {
        grid-template-columns: repeat(4, 1fr);
    }
}

/* ========================================
   3. UNIVERSAL MOBILE NAVIGATION
   ======================================== */

/* Mobile navigation toggle button */
.universal-mobile-toggle {
    display: block;
    position: fixed;
    top: 1rem;
    left: 1rem;
    z-index: 1000;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    border-radius: 12px;
    padding: 15px;
    cursor: pointer;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
    font-size: 1.2rem;
}

.universal-mobile-toggle:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
}

.universal-mobile-toggle.active {
    background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);
}

/* Hide mobile toggle on desktop */
@media (min-width: 1024px) {
    .universal-mobile-toggle {
        display: none;
    }
}

/* Universal mobile navigation menu */
.universal-mobile-nav {
    position: fixed;
    top: 0;
    left: -100%;
    width: 320px;
    height: 100vh;
    background: linear-gradient(180deg, #667eea 0%, #764ba2 100%);
    z-index: 999;
    transition: left 0.3s ease;
    overflow-y: auto;
    padding: 80px 0 20px 0;
    box-shadow: 5px 0 15px rgba(0, 0, 0, 0.2);
}

.universal-mobile-nav.active {
    left: 0;
}

/* Mobile navigation content */
.universal-mobile-nav .nav-content {
    padding: 2rem;
    color: white;
}

.universal-mobile-nav .nav-item {
    display: block;
    color: white;
    text-decoration: none;
    padding: 15px 20px;
    margin: 5px 0;
    border-radius: 10px;
    transition: all 0.3s ease;
    font-weight: 500;
}

.universal-mobile-nav .nav-item:hover {
    background: rgba(255, 255, 255, 0.2);
    transform: translateX(10px);
}

.universal-mobile-nav .nav-item i {
    margin-right: 15px;
    width: 20px;
    text-align: center;
}

/* Universal mobile navigation overlay */
.universal-mobile-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.6);
    z-index: 998;
    opacity: 0;
    visibility: hidden;
    transition: all 0.3s ease;
}

.universal-mobile-overlay.active {
    opacity: 1;
    visibility: visible;
}

/* ========================================
   4. FOOTER CONSISTENCY - HIDE DUPLICATES
   ======================================== */

/* Hide any old footer elements */
.old-footer, 
.legacy-footer, 
footer.old,
.footer-old,
#old-footer,
#legacy-footer,
.duplicate-footer {
    display: none !important;
}

/* Ensure only one footer is visible */
footer:not(:last-of-type) {
    display: none !important;
}

/* Universal footer styling */
.universal-footer {
    margin-top: auto;
    background: #2c3e50;
    color: white;
    padding: 2rem 0;
    width: 100%;
    max-width: 100%;
    overflow-x: hidden;
}

/* ========================================
   5. TABLE RESPONSIVENESS
   ======================================== */

.universal-table-container {
    overflow-x: auto;
    max-width: 100%;
    margin: 1rem 0;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
}

.universal-table-container table {
    min-width: 600px;
    width: 100%;
    border-collapse: collapse;
}

@media (max-width: 768px) {
    .universal-table-container table {
        min-width: 500px;
        font-size: 0.875rem;
    }
    
    .universal-table-container th,
    .universal-table-container td {
        padding: 0.5rem !important;
    }
}

/* ========================================
   6. FORM RESPONSIVENESS
   ======================================== */

.universal-form-grid {
    display: grid;
    gap: 1rem;
    grid-template-columns: 1fr;
}

@media (min-width: 768px) {
    .universal-form-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

.universal-form-input {
    width: 100%;
    padding: 12px 16px;
    border: 2px solid #e1e5e9;
    border-radius: 8px;
    font-size: 1rem;
    transition: border-color 0.3s ease;
    box-sizing: border-box;
}

.universal-form-input:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

/* ========================================
   7. CARD LAYOUT RESPONSIVENESS
   ======================================== */

.universal-card-grid {
    display: grid;
    gap: 1.5rem;
    grid-template-columns: 1fr;
}

@media (min-width: 768px) {
    .universal-card-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (min-width: 1024px) {
    .universal-card-grid {
        grid-template-columns: repeat(3, 1fr);
    }
}

@media (min-width: 1440px) {
    .universal-card-grid {
        grid-template-columns: repeat(4, 1fr);
    }
}

.universal-card {
    background: white;
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease;
    max-width: 100%;
    overflow: hidden;
}

.universal-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
}

/* ========================================
   8. TYPOGRAPHY RESPONSIVENESS
   ======================================== */

.universal-title {
    font-size: 1.75rem;
    line-height: 1.2;
    margin-bottom: 1rem;
}

@media (min-width: 768px) {
    .universal-title {
        font-size: 2.25rem;
    }
}

@media (min-width: 1024px) {
    .universal-title {
        font-size: 2.75rem;
    }
}

.universal-subtitle {
    font-size: 1.125rem;
    line-height: 1.4;
    margin-bottom: 0.75rem;
}

@media (min-width: 768px) {
    .universal-subtitle {
        font-size: 1.25rem;
    }
}

/* ========================================
   9. BUTTON RESPONSIVENESS
   ======================================== */

.universal-btn {
    display: inline-block;
    padding: 12px 24px;
    border: none;
    border-radius: 8px;
    font-size: 1rem;
    font-weight: 600;
    text-decoration: none;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s ease;
    min-width: 120px;
    box-sizing: border-box;
}

@media (max-width: 768px) {
    .universal-btn {
        width: 100%;
        margin: 0.5rem 0;
    }
}

.universal-btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.universal-btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
}

/* ========================================
   10. DASHBOARD SPECIFIC FIXES
   ======================================== */

.universal-dashboard {
    display: flex;
    flex-direction: column;
    min-height: calc(100vh - 80px);
    max-width: 100%;
    overflow-x: hidden;
}

@media (min-width: 1024px) {
    .universal-dashboard {
        flex-direction: row;
    }
}

.universal-sidebar {
    width: 100%;
    max-width: 100%;
}

@media (min-width: 1024px) {
    .universal-sidebar {
        width: 280px;
        flex-shrink: 0;
    }
}

.universal-main-content {
    flex: 1;
    padding: 1rem;
    max-width: 100%;
    overflow-x: hidden;
}

@media (min-width: 768px) {
    .universal-main-content {
        padding: 1.5rem;
    }
}

@media (min-width: 1024px) {
    .universal-main-content {
        padding: 2rem;
    }
}

/* ========================================
   11. ANIMATION FIXES
   ======================================== */

@keyframes universalFadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes universalSlideIn {
    from {
        opacity: 0;
        transform: translateX(-30px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

@keyframes universalSlideDown {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.universal-animate-fadeInUp {
    animation: universalFadeInUp 0.6s ease-out forwards;
}

.universal-animate-slideIn {
    animation: universalSlideIn 0.5s ease-out forwards;
}

.universal-animate-slideDown {
    animation: universalSlideDown 0.3s ease-out forwards;
}

/* ========================================
   12. UTILITY CLASSES
   ======================================== */

.universal-hidden {
    display: none !important;
}

.universal-show {
    display: block !important;
}

.universal-flex {
    display: flex !important;
}

.universal-text-center {
    text-align: center !important;
}

.universal-w-full {
    width: 100% !important;
}

.universal-overflow-hidden {
    overflow: hidden !important;
}

.universal-overflow-x-hidden {
    overflow-x: hidden !important;
}

/* ========================================
   13. PRINT STYLES
   ======================================== */

@media print {
    .universal-mobile-toggle,
    .universal-mobile-nav,
    .universal-mobile-overlay {
        display: none !important;
    }
    
    body {
        overflow: visible !important;
    }
}
</style>
<?php
// CLI helper to load invoice.php for testing
// Usage: php scripts/test_invoice_cli.php

// sample id - adjust if needed
$_GET['id'] = $argv[1] ?? 8;

require_once __DIR__ . '/../backend/includes/bootstrap.php';
include __DIR__ . '/../backend/checkout/invoice.php';

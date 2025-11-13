<?php
// Wrapper: include centralized header and footer and render the body section of the original HTML
include $_SERVER['DOCUMENT_ROOT'] . '/carwash_project/backend/includes/header.php';
$html = file_get_contents(__DIR__ . '/home.html');
if (preg_match('/<body[^>]*>(.*)<\/body>/is', $html, $m)) {
    echo $m[1];
} else {
    // fallback: print whole file if no body tag found
    echo $html;
}
include $_SERVER['DOCUMENT_ROOT'] . '/carwash_project/backend/includes/footer.php';
?>

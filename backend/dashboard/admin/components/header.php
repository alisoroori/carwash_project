<?php
// Minimal header component — outputs the page header / opening HTML
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?php echo htmlspecialchars($page_title ?? 'Admin'); ?></title>
    <link rel="stylesheet" href="/carwash_project/frontend/css/output.css">
</head>
<body class="dashboard-page">
    <header class="dashboard-header">
        <div class="container">
            <h1><?php echo htmlspecialchars($page_title ?? 'Admin Dashboard'); ?></h1>
        </div>
    </header>

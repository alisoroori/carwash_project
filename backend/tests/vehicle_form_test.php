<?php
// Simple local test page to exercise the customer vehicle form
// Place this under backend/tests and open in a browser while the app is running.
// This page will set a test session user and csrf token so you can POST to the actual
// processing endpoint at ../dashboard/Customer_Dashboard_process.php

session_start();
// Use a test user id that exists in your local DB (adjust if needed)
$_SESSION['user_id'] = $_SESSION['user_id'] ?? 1;
if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
$csrf = $_SESSION['csrf_token'];
$actionUrl = dirname(__DIR__) . '/dashboard/Customer_Dashboard_process.php';
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title>Vehicle Form Test</title>
</head>
<body>
  <h1>Vehicle Form Test</h1>
  <p>Session user_id: <?php echo htmlspecialchars($_SESSION['user_id']); ?></p>
  <p>CSRF token (for debugging): <?php echo htmlspecialchars($csrf); ?></p>

  <form id="vehicleForm" method="post" action="<?php echo htmlspecialchars($actionUrl); ?>" enctype="multipart/form-data">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>">
    <label>Brand: <input type="text" name="car_brand" value="TestBrand"></label><br>
    <label>Model: <input type="text" name="car_model" value="TestModel"></label><br>
    <label>License plate: <input type="text" name="license_plate" value="TEST123"></label><br>
    <label>Year: <input type="number" name="car_year" value="2020"></label><br>
    <label>Color: <input type="text" name="car_color" value="Blue"></label><br>
    <label>Image: <input type="file" name="vehicle_image"></label><br>
    <input type="hidden" name="action" value="create_vehicle">
    <button type="submit">Submit (regular form POST)</button>
  </form>

  <hr>
  <h2>AJAX submit (FormData)</h2>
  <button id="ajaxCreate">Submit via AJAX</button>
  <pre id="ajaxResult"></pre>

  <script>
    document.getElementById('ajaxCreate').addEventListener('click', function(){
      var form = document.getElementById('vehicleForm');
      var fd = new FormData(form);
      // Use fetch to post; this will send session cookie from browser
      fetch(form.action, { method: 'POST', body: fd, credentials: 'same-origin' })
        .then(r => r.json())
        .then(j => { document.getElementById('ajaxResult').textContent = JSON.stringify(j, null, 2); })
        .catch(e => document.getElementById('ajaxResult').textContent = String(e));
    });
  </script>
</body>
</html>

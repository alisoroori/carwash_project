<?php
// header.php
?>
<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>CarWash</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 text-gray-800">
  <!-- Navbar -->
  <header class="bg-white shadow-md">
    <div class="container mx-auto flex justify-between items-center p-4">
      <a href="../index.php" class="text-2xl font-bold text-blue-600">🚗 CarWash</a>
      <nav class="hidden md:flex space-x-6">
        <a href="../index.php#services" class="hover:text-blue-600">Hizmetlerimiz</a>
        <a href="../index.php#about" class="hover:text-blue-600">Hakkımızda</a>
        <a href="../index.php#contact" class="hover:text-blue-600">İletişim</a>
        <a href="../auth/login.php#register" class="hover:text-blue-600">Kayıt Ol</a>
        <a href="../auth/login.php#login" class="bg-blue-600 text-white px-4 py-2 rounded-lg">Giriş Yap</a>
      </nav>
    </div>
  </header>

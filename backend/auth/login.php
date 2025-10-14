<?php
// filepath: c:\xampp\htdocs\carwash_project\backend\auth\login.php
// Login Form - Following CarWash project conventions

if (session_status() == PHP_SESSION_NONE) {
  session_start();
}

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
  // Redirect based on role following project dashboard structure
  switch ($_SESSION['role']) {
    case 'admin':
      header('Location: ../dashboard/admin_dashboard.php');
      break;
    case 'carwash':
      header('Location: ../dashboard/Car_Wash_Dashboard.php');
      break;
    case 'customer':
    default:
      header('Location: ../dashboard/Customer_Dashboard.php');
  }
  exit();
}

// Handle error and success messages following project patterns
$error_message = $_SESSION['error_message'] ?? '';
$success_message = $_SESSION['success_message'] ?? '';

// Clear session messages after retrieving them
if (isset($_SESSION['error_message'])) unset($_SESSION['error_message']);
if (isset($_SESSION['success_message'])) unset($_SESSION['success_message']);
?>
<!DOCTYPE html>
<html lang="tr">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>CarWash - Giriş Yap</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link rel="stylesheet" href="../../frontend/css/style.css">
  <style>
    /* CarWash project custom styles - Fixed positioning and colors */
    .gradient-bg {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    }

    .login-container {
      background: rgba(255, 255, 255, 0.98);
      backdrop-filter: blur(15px);
      border: 1px solid rgba(255, 255, 255, 0.2);
    }

    /* Fixed input styling */
    .input-field {
      transition: all 0.3s ease;
      border: 2px solid #e5e7eb;
    }

    .input-field:focus {
      border-color: #667eea;
      box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
      transform: translateY(-1px);
    }

    /* Fixed password toggle button positioning */
    .password-toggle {
      position: absolute;
      right: 12px;
      top: 50%;
      transform: translateY(-50%);
      background: none;
      border: none;
      color: #6b7280;
      cursor: pointer;
      z-index: 10;
      padding: 8px;
    }

    .password-toggle:hover {
      color: #667eea;
    }

    /* Fixed button colors */
    .btn-primary {
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      border: none;
      transition: all 0.3s ease;
    }

    .btn-primary:hover {
      transform: translateY(-2px);
      box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
    }

    .btn-customer {
      background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
      color: white;
      border: none;
    }

    .btn-customer:hover {
      background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%);
      transform: translateY(-1px);
    }

    .btn-carwash {
      background: linear-gradient(135deg, #10b981 0%, #059669 100%);
      color: white;
      border: none;
    }

    .btn-carwash:hover {
      background: linear-gradient(135deg, #059669 0%, #047857 100%);
      transform: translateY(-1px);
    }

    /* Animation fixes */
    @keyframes fadeInUp {
      from {
        opacity: 0;
        transform: translateY(30px);
      }

      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    .animate-fade-in-up {
      animation: fadeInUp 0.6s ease-out forwards;
    }

    /* Select field styling */
    .select-field {
      background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%236b7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='m6 8 4 4 4-4'/%3e%3c/svg%3e");
      background-position: right 12px center;
      background-repeat: no-repeat;
      background-size: 16px;
      padding-right: 40px;
    }
  </style>
</head>

<body class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100">

  <!-- Header following CarWash project structure -->
  <header class="bg-white shadow-lg">
    <div class="container mx-auto px-4 py-3">
      <div class="flex justify-between items-center">
        <div class="flex items-center space-x-3">
          <i class="fas fa-car text-2xl text-blue-600"></i>
          <h1 class="text-xl font-bold text-blue-600">CarWash</h1>
        </div>
        <a href="../../frontend/homes.html" class="text-gray-600 hover:text-blue-600 transition-colors">
          <i class="fas fa-home mr-2"></i>Ana Sayfa
        </a>
      </div>
    </div>
  </header>

  <!-- Main Login Container -->
  <div class="flex items-center justify-center min-h-screen p-4 pt-20">
    <div class="w-full max-w-md">
      <div class="login-container rounded-2xl shadow-2xl p-8 animate-fade-in-up">

        <!-- Login Header -->
        <div class="text-center mb-8">
          <div class="w-20 h-20 gradient-bg rounded-full flex items-center justify-center mx-auto mb-4">
            <i class="fas fa-sign-in-alt text-3xl text-white"></i>
          </div>
          <h1 class="text-3xl font-bold text-gray-800 mb-2">Giriş Yap</h1>
          <p class="text-gray-600">Hesabınıza giriş yaparak hizmetlerimizden yararlanın</p>
        </div>

        <!-- Success Message Display -->
        <?php if (!empty($success_message)): ?>
          <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-lg mb-4">
            <div class="flex items-center">
              <i class="fas fa-check-circle mr-2 text-green-500"></i>
              <span><?php echo htmlspecialchars($success_message); ?></span>
            </div>
          </div>
        <?php endif; ?>

        <!-- Error Message Display -->
        <?php if (!empty($error_message)): ?>
          <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg mb-4">
            <div class="flex items-center">
              <i class="fas fa-exclamation-triangle mr-2 text-red-500"></i>
              <span><?php echo $error_message; ?></span>
            </div>
          </div>
        <?php endif; ?>

        <!-- Login Form - File-based routing to process file -->
        <form action="login_process.php" method="POST" class="space-y-6">

          <!-- Email Field -->
          <div>
            <label class="block text-sm font-bold text-gray-700 mb-2">
              <i class="fas fa-envelope mr-2 text-blue-600"></i>E-posta Adresi
            </label>
            <input
              type="email"
              name="email"
              placeholder="ornek@email.com"
              required
              class="input-field w-full px-4 py-3 rounded-lg focus:outline-none">
          </div>

          <!-- Password Field -->
          <div>
            <label class="block text-sm font-bold text-gray-700 mb-2">
              <i class="fas fa-lock mr-2 text-blue-600"></i>Şifre
            </label>
            <div class="relative">
              <input
                type="password"
                name="password"
                id="password"
                placeholder="Şifrenizi girin"
                required
                class="input-field w-full px-4 py-3 rounded-lg focus:outline-none pr-12">
              <button
                type="button"
                onclick="togglePassword()"
                class="password-toggle">
                <i class="fas fa-eye" id="passwordToggle"></i>
              </button>
            </div>
          </div>

          <!-- User Type Selection -->
          <div>
            <label class="block text-sm font-bold text-gray-700 mb-2">
              <i class="fas fa-user-tag mr-2 text-blue-600"></i>Hesap Türü
            </label>
            <select name="user_type" required class="input-field select-field w-full px-4 py-3 rounded-lg focus:outline-none appearance-none">
              <option value="">Hesap türünüzü seçin</option>
              <option value="customer">Müşteri</option>
              <option value="carwash">Araç Yıkama İşletmesi</option>
              <option value="admin">Yönetici</option>
            </select>
          </div>

          <!-- Remember Me -->
          <div class="flex items-center justify-between">
            <div class="flex items-center">
              <input type="checkbox" name="remember_me" id="remember_me" class="mr-2 text-blue-600 focus:ring-blue-500 rounded">
              <label for="remember_me" class="text-sm text-gray-600">Beni hatırla</label>
            </div>
            <a href="forgot_password.php" class="text-sm text-blue-600 hover:text-blue-800 transition-colors">
              Şifremi unuttum
            </a>
          </div>

          <!-- Submit Button -->
          <button
            type="submit"
            class="btn-primary w-full text-white py-4 rounded-lg font-bold transition-all duration-300">
            <i class="fas fa-sign-in-alt mr-2"></i>Giriş Yap
          </button>
        </form>

        <!-- Registration Links -->
        <div class="mt-8 text-center space-y-4">
          <p class="text-gray-600">Hesabınız yok mu?</p>

          <div class="space-y-3">
            <a href="Customer_Registration.php"
              class="btn-customer block w-full py-3 px-4 rounded-lg transition-all duration-300 font-semibold">
              <i class="fas fa-user-plus mr-2"></i>Müşteri Kaydı
            </a>

            <a href="Car_Wash_Registration.php"
              class="btn-carwash block w-full py-3 px-4 rounded-lg transition-all duration-300 font-semibold">
              <i class="fas fa-store mr-2"></i>İşletme Kaydı
            </a>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script src="../../frontend/js/main.js"></script>
  <script>
    // Password toggle function - Fixed positioning
    function togglePassword() {
      const passwordInput = document.getElementById('password');
      const toggleIcon = document.getElementById('passwordToggle');

      if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        toggleIcon.classList.remove('fa-eye');
        toggleIcon.classList.add('fa-eye-slash');
      } else {
        passwordInput.type = 'password';
        toggleIcon.classList.remove('fa-eye-slash');
        toggleIcon.classList.add('fa-eye');
      }
    }

    // Enhanced focus animations for input fields
    document.querySelectorAll('.input-field').forEach(input => {
      input.addEventListener('focus', function() {
        this.style.borderColor = '#667eea';
        this.style.boxShadow = '0 0 0 3px rgba(102, 126, 234, 0.1)';
        this.style.transform = 'translateY(-1px)';
      });

      input.addEventListener('blur', function() {
        this.style.borderColor = '#e5e7eb';
        this.style.boxShadow = 'none';
        this.style.transform = 'translateY(0)';
      });
    });

    // Button hover effects
    document.querySelectorAll('.btn-primary, .btn-customer, .btn-carwash').forEach(button => {
      button.addEventListener('mouseenter', function() {
        this.style.transform = 'translateY(-2px)';
      });

      button.addEventListener('mouseleave', function() {
        this.style.transform = 'translateY(0)';
      });
    });
  </script>

</body>

</html>
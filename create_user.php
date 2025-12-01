<?php
// Quick user creation page for admin
require_once __DIR__ . '/vendor/autoload.php';

use App\Classes\Session;
use App\Classes\Validator;
use App\Classes\Database;
use App\Classes\UserManager;

// Initialize session securely
Session::start();

// Test session functionality
$_SESSION['test_session'] = 'working';
if (!isset($_SESSION['test_session']) || $_SESSION['test_session'] !== 'working') {
    error_log('Session test failed - sessions not working properly');
}

// Note: This page allows direct access for admin user creation (exception)

// Ensure CSRF token exists using Session helper
$csrf_token = Session::get('csrf_token');
if (empty($csrf_token)) {
  $csrf_token = Session::generateCsrfToken();
  error_log('[create_user] CSRF token generated via Session::generateCsrfToken');
}

// Handle form submission
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // CSRF validation
  if (!isset($_POST['csrf_token']) || !Session::verifyCsrfToken($_POST['csrf_token'])) {
    $error_message = 'Security validation failed. The page has been refreshed with a new security token.';
    // Generate new token for next attempt
    $csrf_token = Session::generateCsrfToken();
  } else {
    // Delegate business logic to UserManager
    $result = UserManager::create($_POST);
    if ($result['success']) {
      $success_message = $result['message'];
    } else {
      if (!empty($result['errors'])) {
        $error_message = implode('<br>', $result['errors']);
      } else {
        $error_message = $result['message'];
      }
    }
  }
}

// Set header configuration
$page_title = 'CarWash - Create User';
$show_login = false;
$home_url = 'backend/index.php';
$about_url = 'backend/index.php#about';
$contact_url = 'backend/index.php#contact';

// Include header
include 'backend/includes/header.php';
?>

  <!-- Additional CSS for create user page -->
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
      border-radius: 4px;
      transition: all 0.2s ease;
    }

    .password-toggle:hover {
      color: #667eea;
      background-color: rgba(102, 126, 234, 0.1);
    }

    .password-toggle:focus {
      outline: none;
      color: #667eea;
      background-color: rgba(102, 126, 234, 0.1);
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

    /* Responsive improvements */
    @media (max-width: 640px) {
      .login-container {
        margin: 1rem;
        padding: 1.5rem;
      }
      
      .input-field, .select-field {
        padding: 0.875rem 1rem;
        font-size: 16px; /* Prevents zoom on iOS */
      }
      
      .password-toggle {
        right: 10px;
        padding: 6px;
      }
      
      .btn-primary {
        padding: 1rem;
        font-size: 1rem;
      }
    }

    /* Enhanced hover effects for better user experience */
    .input-field:hover {
      border-color: #d1d5db;
    }

    .select-field:hover {
      border-color: #d1d5db;
    }

    /* Better focus states for accessibility */
    .input-field:focus,
    .select-field:focus {
      outline: none;
      border-color: #667eea;
      box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }
  </style>

  <!-- Main Create User Container -->
  <div class="flex items-center justify-center min-h-screen p-4 pt-24">
    <div class="w-full max-w-md mx-auto">
      <div class="login-container rounded-2xl shadow-2xl p-6 sm:p-8 animate-fade-in-up">

        <!-- Create User Header -->
        <div class="text-center mb-6 sm:mb-8">
          <div class="w-16 h-16 sm:w-20 sm:h-20 gradient-bg rounded-full flex items-center justify-center mx-auto mb-4">
            <i class="fas fa-user-plus text-2xl sm:text-3xl text-white"></i>
          </div>
          <h1 class="text-2xl sm:text-3xl font-bold text-gray-800 mb-2">Create User</h1>
          <p class="text-sm sm:text-base text-gray-600 px-2">Quick user creation for admin</p>
          <!-- Debug info (remove in production) -->
          <p class="text-xs text-gray-400 mt-2">Debug: Session ID: <?php echo session_id(); ?> | CSRF: <?php echo substr($csrf_token, 0, 8) . '...'; ?></p>
          <p class="text-xs text-green-400 mt-1">Session Test: <?php echo isset($_SESSION['test_session']) ? 'PASS' : 'FAIL'; ?></p>
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
              <span><?php echo htmlspecialchars($error_message); ?></span>
            </div>
          </div>
        <?php endif; ?>

        <!-- Create User Form -->
        <form action="" method="POST" class="space-y-6" enctype="application/x-www-form-urlencoded">
          <!-- CSRF token -->
          <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8'); ?>" id="auto_label_136">

          <!-- Full Name Field -->
          <div>
            <label class="block text-sm font-bold text-gray-700 mb-2">
              <i class="fas fa-user mr-2 text-blue-600"></i>Full Name
            </label>
            <label for="auto_label_135" class="sr-only">Full name</label>
            <input
              type="text"
              name="full_name"
              placeholder="Enter full name"
              required
              class="input-field w-full px-4 py-3 rounded-lg focus:outline-none" id="auto_label_135">
          </div>

          <!-- User Role Selection -->
          <div>
            <label class="block text-sm font-bold text-gray-700 mb-2">
              <i class="fas fa-user-tag mr-2 text-blue-600"></i>User Role
            </label>
            <label for="auto_label_134" class="sr-only">Role</label>
            <select name="role" required class="input-field select-field w-full px-4 py-3 rounded-lg focus:outline-none appearance-none" id="auto_label_134">
              <option value="\>Select user role</option>
              <option value="customer">Customer</option>
              <option value="carwash">Car Wash</option>
              <option value="staff">Staff</option>
              <option value="admin">Admin</option>
            </select>
          </div>

          <!-- Email Field -->
          <div>
            <label class="block text-sm font-bold text-gray-700 mb-2">
              <i class="fas fa-envelope mr-2 text-blue-600"></i>Email Address
            </label>
            <label for="auto_label_133" class="sr-only">Email</label>
            <input
              type="email"
              name="email"
              placeholder="user@example.com"
              required
              class="input-field w-full px-4 py-3 rounded-lg focus:outline-none" id="auto_label_133">
          </div>

          <!-- Password Field -->
          <div>
            <label class="block text-sm font-bold text-gray-700 mb-2">
              <i class="fas fa-lock mr-2 text-blue-600"></i>Password
            </label>
            <div class="relative">
              <label for="password" class="sr-only">Password</label><input
                type="password"
                name="password"
                id="password"
                placeholder="Enter password"
                required
                class="input-field w-full px-4 py-3 pr-12 rounded-lg focus:outline-none">
              <button
                type="button"
                onclick="togglePassword()"
                class="password-toggle">
                <i class="fas fa-eye" id="passwordToggle"></i>
              </button>
            </div>
          </div>

          <!-- Submit Button -->
          <button
            type="submit"
            class="btn-primary w-full text-white py-4 rounded-lg font-bold transition-all duration-300">
            <i class="fas fa-user-plus mr-2"></i>Create User
          </button>
        </form>

        <!-- Back Link -->
        <div class="mt-6 sm:mt-8 text-center">
          <a href="backend/dashboard/admin_panel.php" class="text-blue-600 hover:text-blue-800 transition-colors">
            <i class="fas fa-arrow-left mr-2"></i>Back to Admin Panel
          </a>
        </div>
      </div>
    </div>
  </div>

  <script>
    // Password toggle function
    function togglePassword() {
      const passwordInput = document.getElementById('password');
      const toggleIcon = document.getElementById('passwordToggle');

      if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        toggleIcon.classList.remove('fa-eye');
        toggleIcon.classList.add('fa-eye-slash');
        toggleIcon.setAttribute('aria-label', 'Hide password');
      } else {
        passwordInput.type = 'password';
        toggleIcon.classList.remove('fa-eye-slash');
        toggleIcon.classList.add('fa-eye');
        toggleIcon.setAttribute('aria-label', 'Show password');
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

      // Hover effects only for non-touch devices
      if (!('ontouchstart' in window)) {
        input.addEventListener('mouseenter', function() {
          if (this !== document.activeElement) {
            this.style.borderColor = '#d1d5db';
          }
        });

        input.addEventListener('mouseleave', function() {
          if (this !== document.activeElement) {
            this.style.borderColor = '#e5e7eb';
          }
        });
      }
    });

    // Button hover effects
    document.querySelectorAll('.btn-primary').forEach(button => {
      if (!('ontouchstart' in window)) {
        button.addEventListener('mouseenter', function() {
          this.style.transform = 'translateY(-2px)';
        });

        button.addEventListener('mouseleave', function() {
          this.style.transform = 'translateY(0)';
        });
      }

      // Touch feedback for mobile
      button.addEventListener('touchstart', function() {
        this.style.transform = 'translateY(-1px)';
      });

      button.addEventListener('touchend', function() {
        setTimeout(() => {
          this.style.transform = 'translateY(0)';
        }, 150);
      });
    });

    // Form validation enhancement
    document.querySelector('form').addEventListener('submit', function(e) {
      const fullName = document.querySelector('input[name="full_name"]').value;
      const email = document.querySelector('input[name="email"]').value;
      const password = document.querySelector('input[name="password"]').value;
      const role = document.querySelector('select[name="role"]').value;
      const csrfToken = document.querySelector('input[name="csrf_token"]').value;

      console.log('Form submission - CSRF token:', csrfToken); // Debug log

      if (!fullName || !email || !password || !role) {
        e.preventDefault();
        alert('Please fill in all fields.');
        return false;
      }

      if (!csrfToken) {
        e.preventDefault();
        alert('Security token missing. Please refresh the page.');
        return false;
      }

      // Add loading state to submit button
      const submitButton = this.querySelector('button[type="submit"]');
      submitButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Creating user...';
      submitButton.disabled = true;
    });
  </script>

<?php include 'backend/includes/footer.php'; ?>




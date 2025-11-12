<?php
// CarWash Welcome Page - Shown only once after successful registration
session_start();

// Check if user is logged in and has registration success flag
if (!isset($_SESSION['user_id']) || !isset($_SESSION['registration_success'])) {
    // If no registration success flag, redirect to login
    header('Location: login.php');
    exit();
}

// Get user information
$user_name = $_SESSION['user_name'] ?? 'DeÄŸerli Ãœye';
$user_role = $_SESSION['role'] ?? 'customer';

// Clear the registration success flag so this page only shows once
unset($_SESSION['registration_success']);

// Determine dashboard URL based on role
$dashboard_url = match($user_role) {
    'admin' => '../dashboard/admin_panel.php',
    'carwash', 'car_wash' => '../dashboard/Car_Wash_Dashboard.php',
    'customer' => '../dashboard/Customer_Dashboard.php',
    default => '../dashboard/Customer_Dashboard.php'
};
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HoÅŸ Geldiniz - CarWash</title>
    <link rel="stylesheet" href="<?php echo $base_url; ?>/dist/output.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Welcome page animations */
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

        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(30px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% {
                transform: translateY(0);
            }
            40% {
                transform: translateY(-10px);
            }
            60% {
                transform: translateY(-5px);
            }
        }

        @keyframes pulse {
            0%, 100% {
                transform: scale(1);
            }
            50% {
                transform: scale(1.05);
            }
        }

        @keyframes countDown {
            from {
                width: 100%;
            }
            to {
                width: 0%;
            }
        }

        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .welcome-card {
            background: rgba(255, 255, 255, 0.95);
            /* Added -webkit-backdrop-filter for Safari support */
            -webkit-backdrop-filter: blur(15px);
            backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .animate-fadeInUp {
            animation: fadeInUp 0.8s ease-out forwards;
        }

        .animate-slideInRight {
            animation: slideInRight 0.6s ease-out forwards;
        }

        .animate-bounce {
            animation: bounce 2s infinite;
        }

        .animate-pulse-slow {
            animation: pulse 2s ease-in-out infinite;
        }

        .progress-bar {
            animation: countDown 20s linear forwards;
        }

        /* Floating elements */
        .float {
            animation: float 6s ease-in-out infinite;
        }

        @keyframes float {
            0% {
                transform: translateY(0px);
            }
            50% {
                transform: translateY(-20px);
            }
            100% {
                transform: translateY(0px);
            }
        }

        /* Success icon animation */
        .success-icon {
            animation: successScale 0.8s ease-out forwards;
        }

        @keyframes successScale {
            0% {
                transform: scale(0);
                opacity: 0;
            }
            50% {
                transform: scale(1.2);
                opacity: 1;
            }
            100% {
                transform: scale(1);
                opacity: 1;
            }
        }
    </style>
</head>
<body class="min-h-screen gradient-bg flex items-center justify-center p-4">
    
    <!-- Background decorative elements -->
    <div class="absolute inset-0 overflow-hidden">
        <div class="absolute top-20 left-10 text-white opacity-10 float">
            <i class="fas fa-car text-6xl lg:text-8xl"></i>
        </div>
        <div class="absolute top-40 right-20 text-white opacity-10 float" style="animation-delay: 2s;">
            <i class="fas fa-water text-4xl lg:text-6xl"></i>
        </div>
        <div class="absolute bottom-20 left-20 text-white opacity-10 float" style="animation-delay: 4s;">
            <i class="fas fa-star text-5xl lg:text-7xl"></i>
        </div>
        <div class="absolute bottom-40 right-10 text-white opacity-10 float" style="animation-delay: 1s;">
            <i class="fas fa-shield-alt text-4xl lg:text-6xl"></i>
        </div>
    </div>

    <!-- Welcome Card -->
    <div class="welcome-card rounded-3xl shadow-2xl p-8 md:p-12 max-w-md md:max-w-lg mx-auto text-center relative z-10">
        
        <!-- Success Icon -->
        <div class="success-icon mb-6">
            <div class="w-20 h-20 md:w-24 md:h-24 bg-green-500 rounded-full flex items-center justify-center mx-auto">
                <i class="fas fa-check text-3xl md:text-4xl text-white"></i>
            </div>
        </div>

        <!-- Welcome Message -->
        <div class="animate-fadeInUp">
            <h1 class="text-2xl md:text-3xl lg:text-4xl font-bold text-gray-800 mb-4">
                ðŸŽ‰ HoÅŸ Geldiniz!
            </h1>
            <h2 class="text-lg md:text-xl text-gray-700 mb-6">
                SayÄ±n <span class="text-blue-600 font-semibold"><?php echo htmlspecialchars($user_name); ?></span>
            </h2>
        </div>

        <!-- Registration Success Message -->
        <div class="animate-slideInRight" style="animation-delay: 0.3s;">
            <div class="bg-green-50 border border-green-200 rounded-xl p-4 md:p-6 mb-6">
                <div class="flex items-center justify-center mb-3">
                    <i class="fas fa-user-check text-green-600 text-xl mr-2"></i>
                    <span class="text-green-800 font-semibold">KayÄ±t BaÅŸarÄ±lÄ±!</span>
                </div>
                <p class="text-green-700 text-sm md:text-base">
                    CarWash ailesine katÄ±ldÄ±ÄŸÄ±nÄ±z iÃ§in teÅŸekkÃ¼rler! HesabÄ±nÄ±z baÅŸarÄ±yla oluÅŸturuldu ve artÄ±k tÃ¼m hizmetlerimizden yararlanabilirsiniz.
                </p>
            </div>
        </div>

        <!-- Features Preview -->
        <div class="animate-fadeInUp" style="animation-delay: 0.6s;">
            <div class="grid grid-cols-2 gap-4 mb-6">
                <div class="bg-blue-50 rounded-lg p-3 md:p-4">
                    <i class="fas fa-calendar-check text-blue-600 text-lg md:text-xl mb-2"></i>
                    <p class="text-xs md:text-sm text-blue-800 font-medium">Kolay Rezervasyon</p>
                </div>
                <div class="bg-purple-50 rounded-lg p-3 md:p-4">
                    <i class="fas fa-star text-purple-600 text-lg md:text-xl mb-2"></i>
                    <p class="text-xs md:text-sm text-purple-800 font-medium">Kaliteli Hizmet</p>
                </div>
                <div class="bg-green-50 rounded-lg p-3 md:p-4">
                    <i class="fas fa-shield-alt text-green-600 text-lg md:text-xl mb-2"></i>
                    <p class="text-xs md:text-sm text-green-800 font-medium">GÃ¼venli Ã–deme</p>
                </div>
                <div class="bg-orange-50 rounded-lg p-3 md:p-4">
                    <i class="fas fa-clock text-orange-600 text-lg md:text-xl mb-2"></i>
                    <p class="text-xs md:text-sm text-orange-800 font-medium">HÄ±zlÄ± Hizmet</p>
                </div>
            </div>
        </div>

        <!-- Auto Redirect Message -->
        <div class="animate-pulse-slow" style="animation-delay: 0.9s;">
            <p class="text-gray-600 text-sm md:text-base mb-4">
                <span id="countdown">20</span> saniye sonra panelinize yÃ¶nlendirileceksiniz...
            </p>
            
            <!-- Progress Bar -->
            <div class="bg-gray-200 rounded-full h-2 mb-4">
                <div class="progress-bar bg-gradient-to-r from-blue-500 to-purple-600 h-2 rounded-full"></div>
            </div>
        </div>

        <!-- Manual Redirect Button -->
        <div class="animate-fadeInUp" style="animation-delay: 1.2s;">
            <a href="<?php echo $dashboard_url; ?>" 
               class="inline-block bg-gradient-to-r from-blue-600 to-purple-600 text-white px-6 md:px-8 py-3 md:py-4 rounded-xl font-semibold hover:from-blue-700 hover:to-purple-700 transition-all duration-300 transform hover:scale-105 shadow-lg">
                <i class="fas fa-arrow-right mr-2"></i>
                Panelime Git
            </a>
        </div>

        <!-- CarWash Logo -->
        <div class="mt-8 animate-fadeInUp" style="animation-delay: 1.5s;">
            <div class="flex items-center justify-center">
                <i class="fas fa-car text-2xl text-blue-600 mr-2"></i>
                <span class="text-xl font-bold text-gray-800">CarWash</span>
            </div>
        </div>
    </div>

    <script>
        // Auto redirect countdown
        let timeLeft = 20;
        const countdownElement = document.getElementById('countdown');
        
        const countdown = setInterval(() => {
            timeLeft--;
            countdownElement.textContent = timeLeft;
            
            if (timeLeft <= 0) {
                clearInterval(countdown);
                window.location.href = '<?php echo $dashboard_url; ?>';
            }
        }, 1000);

        // Optional: Allow user to cancel auto-redirect by clicking anywhere
        let autoRedirectCanceled = false;
        
        document.addEventListener('click', function(e) {
            // Don't cancel if clicking the manual redirect button
            if (e.target.closest('a[href*="dashboard"]')) {
                return;
            }
            
            if (!autoRedirectCanceled) {
                autoRedirectCanceled = true;
                clearInterval(countdown);
                countdownElement.parentElement.innerHTML = 
                    '<p class="text-gray-600 text-sm md:text-base">Otomatik yÃ¶nlendirme iptal edildi. Panelinize gitmek iÃ§in butona tÄ±klayÄ±n.</p>';
            }
        });

        // Add some interactive animations
        document.querySelectorAll('.bg-blue-50, .bg-purple-50, .bg-green-50, .bg-orange-50').forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'scale(1.05)';
                this.style.transition = 'transform 0.2s ease';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'scale(1)';
            });
        });
    </script>
</body>
</html>




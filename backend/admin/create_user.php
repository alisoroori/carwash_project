<?php
// filepath: c:\xampp\htdocs\carwash_project\backend\admin\create_user.php

require_once __DIR__ . '/../../vendor/autoload.php';

use App\Classes\Auth;
use App\Classes\Database;
use App\Classes\Session;
use App\Classes\Validator;

// Start session
Session::start();

// Require admin role
Auth::requireRole('admin');

$roles = ['admin', 'carwash', 'customer'];
$message = '';
$messageType = '';
$formData = [
    'full_name' => '',
    'email' => '',
    'role' => 'customer'
];

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate form data
    $validator = new Validator();
    $validator
        ->required($_POST['full_name'] ?? null, 'Full name')
        ->required($_POST['email'] ?? null, 'Email')
        ->email($_POST['email'] ?? null, 'Email')
        ->required($_POST['password'] ?? null, 'Password')
        ->minLength($_POST['password'] ?? null, 6, 'Password')
        ->required($_POST['role'] ?? null, 'Role')
        ->in($_POST['role'] ?? null, $roles, 'Role');
    
    // If validation passes
    if (!$validator->fails()) {
        try {
            $db = Database::getInstance();
            
            // Check if email already exists
            $existingUser = $db->fetchOne("SELECT id FROM users WHERE email = :email", [
                'email' => $_POST['email']
            ]);
            
            if ($existingUser) {
                $message = "A user with this email already exists.";
                $messageType = 'error';
            } else {
                // Create new user
                $userId = $db->insert('users', [
                    'full_name' => Validator::sanitizeString($_POST['full_name']),
                    'email' => Validator::sanitizeEmail($_POST['email']),
                    'password' => password_hash($_POST['password'], PASSWORD_DEFAULT),
                    'role' => $_POST['role'],
                    'status' => 'active',
                    'email_verified_at' => date('Y-m-d H:i:s'),
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ]);
                
                if ($userId) {
                    $message = "User created successfully!";
                    $messageType = 'success';
                    
                    // Clear form data
                    $formData = [
                        'full_name' => '',
                        'email' => '',
                        'role' => 'customer'
                    ];
                } else {
                    $message = "Failed to create user. Please try again.";
                    $messageType = 'error';
                }
            }
        } catch (Exception $e) {
            $message = "Database error: " . $e->getMessage();
            $messageType = 'error';
        }
    } else {
        $message = implode('<br>', $validator->getErrors());
        $messageType = 'error';
        
        // Keep form data
        $formData = [
            'full_name' => $_POST['full_name'] ?? '',
            'email' => $_POST['email'] ?? '',
            'role' => $_POST['role'] ?? 'customer'
        ];
    }
}

// Page title
$page_title = 'Create User - CarWash Admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <style>
        :root {
            --primary-color: #3498db;
            --secondary-color: #2980b9;
            --success-color: #2ecc71;
            --danger-color: #e74c3c;
            --warning-color: #f39c12;
            --dark-color: #34495e;
            --light-color: #ecf0f1;
            --border-radius: 6px;
            --box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #444;
            background-color: #f5f7fa;
            padding: 20px;
        }
        
        .container {
            max-width: 800px;
            margin: 0 auto;
            background-color: #fff;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 30px;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid var(--light-color);
        }
        
        h1 {
            color: var(--dark-color);
            font-size: 28px;
            margin-bottom: 10px;
        }
        
        .subtitle {
            color: #7f8c8d;
            font-size: 16px;
        }
        
        .message {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: var(--border-radius);
        }
        
        .message.success {
            background-color: rgba(46, 204, 113, 0.15);
            border-left: 4px solid var(--success-color);
            color: #27ae60;
        }
        
        .message.error {
            background-color: rgba(231, 76, 60, 0.15);
            border-left: 4px solid var(--danger-color);
            color: #c0392b;
        }
        
        form {
            margin-top: 20px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--dark-color);
        }
        
        .form-control {
            width: 100%;
            padding: 12px 15px;
            font-size: 16px;
            border: 1px solid #ddd;
            border-radius: var(--border-radius);
            transition: border-color 0.3s;
        }
        
        .form-control:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.25);
        }
        
        .form-select {
            appearance: none;
            background-image: url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%2334495e' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpath d='M6 9l6 6 6-6'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 15px center;
            background-size: 16px;
        }
        
        .btn {
            display: inline-block;
            font-weight: 500;
            text-align: center;
            padding: 12px 24px;
            font-size: 16px;
            border: none;
            border-radius: var(--border-radius);
            cursor: pointer;
            transition: background-color 0.3s, transform 0.15s;
        }
        
        .btn:active {
            transform: translateY(1px);
        }
        
        .btn-primary {
            background-color: var(--primary-color);
            color: white;
        }
        
        .btn-primary:hover {
            background-color: var(--secondary-color);
        }
        
        .back-link {
            display: inline-block;
            margin-top: 20px;
            color: var(--primary-color);
            text-decoration: none;
        }
        
        .back-link:hover {
            text-decoration: underline;
        }
        
        .form-info {
            margin-top: 30px;
            padding: 15px;
            background-color: rgba(52, 152, 219, 0.1);
            border-radius: var(--border-radius);
        }
        
        .form-info h3 {
            font-size: 18px;
            margin-bottom: 10px;
            color: var(--primary-color);
        }
        
        .role-description {
            margin-top: 5px;
            font-size: 14px;
            color: #7f8c8d;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Create New User</h1>
            <p class="subtitle">Add a new user to the CarWash system</p>
        </div>
        
        <?php if ($message): ?>
            <div class="message <?php echo $messageType; ?>">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="">
            <div class="form-group">
                <label for="full_name">Full Name</label>
                <input 
                    type="text" 
                    id="full_name" 
                    name="full_name" 
                    class="form-control" 
                    value="<?php echo htmlspecialchars($formData['full_name']); ?>" 
                    required
                >
            </div>
            
            <div class="form-group">
                <label for="email">Email Address</label>
                <input 
                    type="email" 
                    id="email" 
                    name="email" 
                    class="form-control" 
                    value="<?php echo htmlspecialchars($formData['email']); ?>" 
                    required
                >
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <input 
                    type="password" 
                    id="password" 
                    name="password" 
                    class="form-control" 
                    required
                >
            </div>
            
            <div class="form-group">
                <label for="role">User Role</label>
                <select id="role" name="role" class="form-control form-select">
                    <?php foreach ($roles as $role): ?>
                        <option 
                            value="<?php echo $role; ?>" 
                            <?php echo $formData['role'] === $role ? 'selected' : ''; ?>
                        >
                            <?php echo ucfirst($role); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="role-description" id="role-description">
                    Customer accounts can book services and view their history.
                </div>
            </div>
            
            <button type="submit" class="btn btn-primary">Create User</button>
            
            <div class="form-info">
                <h3>Important Information</h3>
                <p>All users are created with an 'active' status and will be able to log in immediately.</p>
                <p>Make sure to provide a secure password that meets the minimum length requirement (6 characters).</p>
            </div>
        </form>
        
        <a href="../dashboard/admin_panel.php" class="back-link">&larr; Back to Admin Dashboard</a>
    </div>
    
    <script>
        // Update role description when selection changes
        document.getElementById('role').addEventListener('change', function() {
            const descriptions = {
                'admin': 'Administrator accounts have full access to the system.',
                'carwash': 'Car wash accounts can manage their services and bookings.',
                'customer': 'Customer accounts can book services and view their history.'
            };
            
            document.getElementById('role-description').textContent = 
                descriptions[this.value] || '';
        });
    </script>
</body>
</html>
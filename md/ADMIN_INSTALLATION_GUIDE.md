# 🚀 Admin Dashboard Installation Guide

## Quick Start (5 Minutes)

### Step 1: Import Database Schema
```bash
# Navigate to your project directory
cd c:\xampp\htdocs\carwash_project

# Import the schema using MySQL command line
mysql -u root -p carwash < database\migrations\admin_enterprise_schema.sql

# Or use phpMyAdmin:
# 1. Open http://localhost/phpmyadmin
# 2. Select 'carwash' database
# 3. Go to 'Import' tab
# 4. Choose file: database\migrations\admin_enterprise_schema.sql
# 5. Click 'Go'
```

### Step 2: Verify Tables Created
```sql
-- Run this query to check tables
SHOW TABLES LIKE '%roles%' OR LIKE '%audit%' OR LIKE '%2fa%';

-- You should see:
-- - roles
-- - permissions
-- - role_user
-- - user_2fa
-- - audit_logs
-- - security_logs
-- - support_tickets
-- - ticket_replies
-- - services
-- - reviews
-- - notifications
-- - notification_templates
-- - cms_pages
-- - media_library
-- - settings
-- - backup_logs
```

### Step 3: Assign SuperAdmin Role
```sql
-- Find your admin user ID
SELECT id, user_name, email FROM users WHERE role = 'admin';

-- Let's say your user ID is 1, assign SuperAdmin role
INSERT INTO role_user (user_id, role_id, assigned_by)
VALUES (1, 1, 1);  -- role_id 1 is SuperAdmin

-- Verify assignment
SELECT u.user_name, r.display_name, r.level
FROM users u
JOIN role_user ru ON u.id = ru.user_id
JOIN roles r ON ru.role_id = r.id
WHERE u.id = 1;
```

### Step 4: Update auth_check.php
```bash
# Edit backend\includes\auth_check.php
# Add these includes at the top:
```

```php
<?php
// Add these lines to your auth_check.php
require_once __DIR__ . '/RBAC.php';
require_once __DIR__ . '/AuditLog.php';
require_once __DIR__ . '/TwoFactorAuth.php';

// Initialize RBAC for current user
if (isset($_SESSION['user_id'])) {
    global $rbac, $audit, $tfa;
    $rbac = new RBAC($pdo, $_SESSION['user_id']);
    $audit = new AuditLog($pdo);
    $tfa = new TwoFactorAuth($pdo);
}
?>
```

### Step 5: Test Access
```
1. Go to: http://localhost/carwash_project/backend/auth/login.php
2. Login with your admin credentials
3. You should be redirected to admin_panel.php
4. Verify you can see all 13 sections in the sidebar
```

---

## 🔧 Configuration

### Enable 2FA for Your Account
```php
<?php
// Create a test file: backend/test_2fa.php
require_once '../includes/db.php';
require_once '../includes/TwoFactorAuth.php';

session_start();
$userId = 1; // Your admin user ID

$tfa = new TwoFactorAuth($pdo);
$setup = $tfa->enable($userId);

echo "<h2>2FA Setup Complete!</h2>";
echo "<p><strong>Secret:</strong> " . $setup['secret'] . "</p>";
echo "<p><strong>QR Code:</strong></p>";
echo "<img src='" . $setup['qr_url'] . "' />";
echo "<h3>Backup Codes (save these!):</h3><ul>";
foreach ($setup['backup_codes'] as $code) {
    echo "<li><code>" . $code . "</code></li>";
}
echo "</ul>";
?>
```

### Customize Settings
```sql
-- Update commission rate
UPDATE settings SET value = '20' WHERE key = 'commission_rate';

-- Enable 2FA requirement for all admins
UPDATE settings SET value = '1' WHERE key = '2fa_required';

-- Change session timeout to 2 hours
UPDATE settings SET value = '7200' WHERE key = 'session_timeout';
```

---

## ✅ Verification Checklist

- [ ] All database tables created successfully
- [ ] Default roles inserted (5 roles)
- [ ] Default permissions inserted (40+ permissions)
- [ ] SuperAdmin role assigned to your admin user
- [ ] Can login to admin panel
- [ ] All 13 sidebar sections visible
- [ ] Dashboard charts loading
- [ ] Order management page shows orders
- [ ] Payment management page shows transactions
- [ ] No PHP errors in browser console
- [ ] No SQL errors in MySQL logs

---

## 🐛 Troubleshooting

### Problem: "RBAC class not found"
**Solution:** 
```bash
# Verify file exists
dir backend\includes\RBAC.php

# Check file permissions (should be readable)
# Add to your admin_panel.php at the top:
require_once __DIR__ . '/../includes/RBAC.php';
```

### Problem: "Table 'carwash.roles' doesn't exist"
**Solution:**
```bash
# Re-import the schema
mysql -u root -p carwash < database\migrations\admin_enterprise_schema.sql

# Or manually check in phpMyAdmin
```

### Problem: "Permission denied" errors
**Solution:**
```sql
-- Check user has role assigned
SELECT * FROM role_user WHERE user_id = YOUR_USER_ID;

-- If not, assign SuperAdmin role
INSERT INTO role_user (user_id, role_id) VALUES (YOUR_USER_ID, 1);
```

### Problem: Charts not displaying
**Solution:**
```html
<!-- Verify Chart.js is loaded in admin_panel.php -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<!-- Check browser console for JavaScript errors -->
<!-- Press F12 in browser and look for red errors -->
```

---

## 🎨 Customization

### Add Custom Permission
```sql
INSERT INTO permissions (name, category, description)
VALUES ('reports.delete', 'reports', 'Delete generated reports');

-- Add to a role
UPDATE roles 
SET permissions = JSON_ARRAY_APPEND(permissions, '$', 'reports.delete')
WHERE name = 'admin';
```

### Create Custom Role
```sql
INSERT INTO roles (name, display_name, description, level, permissions)
VALUES (
    'data_analyst',
    'Data Analyst',
    'View and export data for analysis',
    30,
    JSON_ARRAY('*.view', 'reports.*', 'audit_logs.view')
);
```

### Change Theme Colors
```css
/* In admin_panel.php <style> section */
:root {
    --primary-color: #667eea;      /* Change to your brand color */
    --success-color: #28a745;
    --danger-color: #dc3545;
    --warning-color: #ffc107;
}
```

---

## 📚 Usage Examples

### Example 1: Check if User Can Edit Orders
```php
<?php
require_once __DIR__ . '/../../includes/RBAC.php';

$rbac = new RBAC($pdo);

if ($rbac->can('orders.edit')) {
    // Show edit button
    echo '<button onclick="editOrder()">Edit Order</button>';
}
?>
```

### Example 2: Log Order Cancellation
```php
<?php
require_once __DIR__ . '/../../includes/AuditLog.php';

$audit = new AuditLog($pdo);

// Get order before cancellation
$order = getOrder($orderId);

// Cancel order
cancelOrder($orderId);

// Log the action
$audit->logCancel('order', $orderId, $cancelReason, 
    "Order #$orderId cancelled by " . $_SESSION['user_name']);
?>
```

### Example 3: Verify 2FA Code
```php
<?php
require_once __DIR__ . '/../../includes/TwoFactorAuth.php';

$tfa = new TwoFactorAuth($pdo);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = $_POST['code'];
    $userId = $_SESSION['user_id'];
    
    if ($tfa->verifyCode($userId, $code)) {
        $tfa->markVerified();
        header('Location: dashboard.php');
    } else {
        echo "Invalid code. Please try again.";
    }
}
?>
```

---

## 🔐 Security Best Practices

1. **Always use RBAC checks:**
   ```php
   $rbac->requirePermission('orders.edit');
   // Your code here
   ```

2. **Log all important actions:**
   ```php
   $audit->logUpdate('order', $id, $old, $new);
   ```

3. **Enable 2FA for all admins:**
   ```sql
   UPDATE settings SET value = '1' WHERE key = '2fa_required';
   ```

4. **Regular backup:**
   ```bash
   mysqldump -u root -p carwash > backup_$(date +%Y%m%d).sql
   ```

5. **Monitor audit logs:**
   ```sql
   -- Check recent admin actions
   SELECT * FROM audit_logs 
   ORDER BY created_at DESC 
   LIMIT 50;
   ```

---

## 📞 Support

If you encounter issues:

1. Check PHP error logs: `c:\xampp\php\logs\php_error_log`
2. Check MySQL error logs: `c:\xampp\mysql\data\*.err`
3. Enable debug mode in PHP:
   ```php
   ini_set('display_errors', 1);
   error_reporting(E_ALL);
   ```
4. Review the implementation guide: `ADMIN_ENTERPRISE_IMPLEMENTATION.md`

---

**Installation complete! 🎉**

Your admin dashboard is now ready with enterprise-level security, audit logging, and role-based access control.

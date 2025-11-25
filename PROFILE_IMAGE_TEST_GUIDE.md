# Profile Image Display - Test Guide

## Overview
This guide verifies that the profile picture displays consistently in both header and sidebar after logout/login cycles.

## What Was Changed

### Files Modified
1. **`backend/includes/customer_header.php`**
   - Robust session resolution: checks `$_SESSION['user']['profile_image']`, `$_SESSION['user_profile_image']`, `$_SESSION['profile_image']`, and `$user_profile_image` variable
   - Server-side validation: verifies file exists and is readable, logs errors via `error_log()` if not
   - Cache-busting: creates `$_SESSION['profile_image_ts']` timestamp and appends `?ts=` to image URL
   - Removed localStorage writes (no client-side storage dependency)
   - Header and mobile avatars use `$profile_src_with_ts` with `onerror` fallback to default avatar
   - DOM updates: JS updates `#userAvatarTop`, `#mobileMenuAvatar`, `#sidebarProfileImage` if present

2. **`backend/dashboard/Customer_Dashboard.php`**
   - Sidebar avatar (`#sidebarProfileImage`) now uses `$profile_src_with_ts` from header include
   - Same cache-busted URL ensures header and sidebar show identical image
   - Added `onerror` fallback to default avatar

3. **`backend/api/profile/get_image.php`** (NEW - optional)
   - JSON endpoint returning current session profile image with cache-bust timestamp
   - Returns: `{"status":"success","message":"OK","data":{"image":"<url?ts=..."}}`
   - Can be called via AJAX for dynamic updates

## Single Source of Truth
- **Primary session key:** `$_SESSION['user']['profile_image']` (preferred) or `$_SESSION['profile_image']`
- **Cache-bust timestamp:** `$_SESSION['profile_image_ts']` (set on login/image upload)
- **Both header and sidebar** use the same `$profile_src_with_ts` variable created by `customer_header.php`

## Prerequisites for Testing

### 1. Update Your Login Handler
Add these lines after successful authentication:

```php
// After successful login (e.g., in backend/auth/login.php or your Auth class)
$_SESSION['user'] = $userFromDb; // if you use nested session array
$_SESSION['user']['profile_image'] = $userFromDb['profile_image'] ?? null;

// OR if you use flat session keys:
$_SESSION['profile_image'] = $userFromDb['profile_image'] ?? null;

// IMPORTANT: Set timestamp to force cache reload on new login
$_SESSION['profile_image_ts'] = time();
```

### 2. Update Your Profile Upload Handler
Add these lines after successful profile image upload:

```php
// After successful upload (e.g., in backend/api/profile/update.php)
$_SESSION['user']['profile_image'] = $newImageFilename; // or full path
// OR flat key:
$_SESSION['profile_image'] = $newImageFilename;

// IMPORTANT: Update timestamp to force cache reload
$_SESSION['profile_image_ts'] = time();
```

### 3. Check File Permissions
Ensure the web server can read profile images:

**Windows (XAMPP):**
```powershell
# Grant read permission to Users group
icacls "C:\xampp\htdocs\carwash_project\backend\auth\uploads\profiles" /grant Users:(R) /T
```

**Linux (Apache/nginx):**
```bash
# Set ownership to web server user (e.g., www-data)
sudo chown -R www-data:www-data /var/www/carwash_project/backend/auth/uploads/profiles
# Set permissions: 755 for directories, 644 for files
sudo find /var/www/carwash_project/backend/auth/uploads/profiles -type d -exec chmod 755 {} \;
sudo find /var/www/carwash_project/backend/auth/uploads/profiles -type f -exec chmod 644 {} \;
```

## Manual Test Steps

### Test 1: Fresh Login with Profile Image
1. **Start clean:**
   - Clear browser cache (or use incognito/private window)
   - Ensure XAMPP is running (Apache + MySQL)

2. **Login as user with profile image:**
   - Navigate to: `http://localhost/carwash_project/backend/auth/login.php`
   - Login with credentials for a user who has a profile image uploaded

3. **Verify display:**
   - **Header:** Profile picture should appear in top-right (circular avatar)
   - **Sidebar:** Profile picture should appear at top of left sidebar
   - Both should be identical (same image file)

4. **Check cache-busting:**
   - Open DevTools → Network tab
   - Find the avatar image request
   - Confirm URL includes `?ts=<timestamp>` query parameter

### Test 2: Logout and Re-login (Cache Test)
1. **Logout:**
   - Click logout button in header dropdown
   - Session should be destroyed

2. **Re-login:**
   - Login again with same credentials
   - Profile picture should appear immediately in **both header and sidebar**
   - Check DevTools Network: timestamp in `?ts=` should be different from before (new session)

3. **Repeat 3-5 times:**
   - Each logout/login cycle should show profile picture consistently
   - No stale cached images or missing avatars

### Test 3: Multiple Users
1. **Login as User A** (has profile image)
   - Confirm avatar shows in header and sidebar

2. **Logout and login as User B** (different profile image)
   - Confirm User B's avatar shows (not User A's)
   - Check both header and sidebar

3. **Login as User C** (no profile image / default)
   - Confirm default avatar shows in both places
   - No broken image or 404

### Test 4: Missing Image File
1. **Break the image:**
   - Temporarily rename a user's profile image file on disk
   - Example: rename `profile_123.jpg` to `profile_123.jpg.bak`

2. **Login as that user:**
   - Default avatar should appear in header and sidebar
   - No broken image icon

3. **Check error log:**
   - Open Apache error log: `C:\xampp\apache\logs\error.log` (Windows) or `/var/log/apache2/error.log` (Linux)
   - Look for message: `[customer_header] Profile image not readable or missing: <path>`

4. **Restore the image** and verify it appears again

### Test 5: AJAX Endpoint (Optional)
1. **Test the API endpoint:**
   - Open browser console on Customer Dashboard
   - Run:
     ```javascript
     fetch('/carwash_project/backend/api/profile/get_image.php')
       .then(r => r.json())
       .then(data => console.log('Profile image URL:', data.data.image));
     ```
   - Should return current session profile image with `?ts=` param

2. **Use for dynamic updates:**
   - If you update profile image via AJAX, call this endpoint
   - Update `<img>` elements with returned URL

## Expected Results

### ✅ Success Criteria
- [ ] Profile picture appears in header after login
- [ ] Profile picture appears in sidebar after login
- [ ] Both header and sidebar show **the same image**
- [ ] Image includes `?ts=<timestamp>` in URL
- [ ] Logout → Login shows profile picture immediately (no stale cache)
- [ ] Multiple logout/login cycles work consistently
- [ ] Different users show their own profile pictures
- [ ] Users without custom image show default avatar
- [ ] Missing/unreadable images fall back to default avatar
- [ ] Error log contains helpful messages for missing files

### ❌ Failure Indicators
- Profile picture missing in header OR sidebar after login
- Stale/wrong user's profile picture after logout/login
- Broken image icon (need to check file permissions or path)
- Different images in header vs sidebar
- No `?ts=` parameter in image URL (cache-busting not working)

## Troubleshooting

### Issue: Profile picture not showing after login
**Cause:** Session keys not set during login

**Fix:**
- Verify login handler sets `$_SESSION['user']['profile_image']` or `$_SESSION['profile_image']`
- Verify login handler sets `$_SESSION['profile_image_ts'] = time();`
- Check session start: ensure `session_start()` is called before setting session vars

### Issue: Stale image after logout/login
**Cause:** Cache-busting timestamp not updated on new login

**Fix:**
- Ensure login handler sets `$_SESSION['profile_image_ts'] = time();` on **every** login
- Clear browser cache if testing with same browser window

### Issue: Different images in header vs sidebar
**Cause:** Sidebar not using `$profile_src_with_ts` variable

**Fix:**
- Verify `Customer_Dashboard.php` includes `customer_header.php` **before** the sidebar HTML
- Check sidebar `<img>` tag uses: `src="<?php echo htmlspecialchars($profile_src_with_ts ?? ...); ?>"`

### Issue: Broken image / 404
**Cause:** File path incorrect or file not readable

**Fix:**
- Check file exists: `C:\xampp\htdocs\carwash_project\backend\auth\uploads\profiles\<filename>`
- Check file permissions (see commands above)
- Check Apache error log for `[customer_header]` messages
- Verify `$_SESSION['profile_image']` contains correct filename (not full path with incorrect base)

### Issue: No error log messages
**Cause:** PHP error logging disabled

**Fix:**
- Check `php.ini`: ensure `error_reporting = E_ALL` and `log_errors = On`
- Restart Apache after changing `php.ini`
- Check `error_log` directive points to a writable file

## Database Schema Reference

Profile images are stored in these tables:

```sql
-- users table (primary user data)
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100),
    email VARCHAR(100) UNIQUE,
    password VARCHAR(255),
    role ENUM('admin', 'customer', 'carwash'),
    profile_image VARCHAR(255), -- filename or relative path
    -- other fields...
);

-- user_profiles table (extended profile data)
CREATE TABLE user_profiles (
    user_id INT PRIMARY KEY,
    profile_image VARCHAR(255), -- filename or relative path
    address TEXT,
    city VARCHAR(100),
    -- other fields...
    FOREIGN KEY (user_id) REFERENCES users(id)
);
```

**Storage path:** `backend/auth/uploads/profiles/<filename>`

**Session keys used:**
- `$_SESSION['user']['profile_image']` (nested, preferred)
- `$_SESSION['profile_image']` (flat, fallback)
- `$_SESSION['profile_image_ts']` (cache-bust timestamp)

## Summary

### Key Points
1. **Single source:** `customer_header.php` resolves profile image and creates `$profile_src_with_ts`
2. **Both use same var:** Header and sidebar `<img>` tags use `$profile_src_with_ts`
3. **No localStorage:** Display driven by server-side session only
4. **Cache-busting:** `?ts=` timestamp forces reload after login/upload
5. **Error handling:** Missing files log errors and fall back to default avatar
6. **Permissions:** Web server must have read access to `backend/auth/uploads/profiles/`

### Next Steps After Testing
1. If all tests pass: **done** ✅
2. If issues found: check troubleshooting section above
3. For production: ensure login handler updates `profile_image_ts` on every login
4. For uploads: ensure upload handler updates `profile_image_ts` after successful upload

## Support
If you encounter issues not covered in this guide:
1. Check Apache error log for `[customer_header]` messages
2. Verify session keys with: `var_dump($_SESSION);` (remove before production)
3. Confirm file paths with: `file_exists()` and `is_readable()` checks
4. Test with different browsers (Chrome, Firefox, Edge) to rule out browser-specific cache issues

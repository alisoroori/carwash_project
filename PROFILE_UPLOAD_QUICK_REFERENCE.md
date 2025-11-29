# Profile Upload Quick Reference

## ✅ System Status: FIXED & READY

All profile uploads now use the standardized path: `uploads/profiles/`

---

## Upload Behavior

### When a user uploads a profile image:

1. **File saved to:** `carwash_project/uploads/profiles/profile_14_1764379912.jpg`
2. **Database stores:** `uploads/profiles/profile_14_1764379912.jpg`
3. **Browser loads:** `http://localhost/carwash_project/uploads/profiles/profile_14_1764379912.jpg`

---

## Current Database State

```
Users table:
✓ User 14: uploads/profiles/profile_14_1764379912.jpg
✓ User 27: uploads/profiles/profile_27_1764035228.jpg

User Profiles table:
✓ User 14: uploads/profiles/profile_14_1764414270.jpg
```

All paths are in correct format! ✅

---

## Directory Structure

```
carwash_project/
├── uploads/
│   └── profiles/           ← All profile images here
│       ├── profile_14_1764379912.jpg
│       ├── profile_14_1764414270.jpg
│       ├── profile_27_1764035228.jpg
│       └── default-avatar.svg
├── backend/
│   ├── includes/
│   │   └── profile_upload_helper.php  ← Upload logic
│   ├── api/
│   │   └── update_profile.php         ← API endpoint
│   └── dashboard/
│       └── Customer_Dashboard.php     ← Frontend
```

---

## Code Locations

| Function | File | Line |
|----------|------|------|
| Upload Handler | `backend/includes/profile_upload_helper.php` | Line 6, 110 |
| API Endpoint | `backend/api/update_profile.php` | Line 37-63 |
| Dashboard Display | `backend/dashboard/Customer_Dashboard.php` | Line 75-104 |
| Registration | `backend/auth/Car_Wash_Registration_process.php` | Line 159-185 |

---

## Test Commands

```powershell
# Full system test
php test_profile_system.php

# Check database values
php check_final_db.php

# Validate PHP syntax
php -l backend/includes/profile_upload_helper.php
php -l backend/api/update_profile.php
```

---

## Manual Testing Steps

1. **Go to:** http://localhost/carwash_project/backend/dashboard/Customer_Dashboard.php
2. **Login as:** User 14 (or any customer)
3. **Navigate to:** Profile section
4. **Upload:** A new profile image
5. **Verify:**
   - ✓ Image displays immediately
   - ✓ No 404 errors in console (F12)
   - ✓ Database has: `uploads/profiles/profile_X_TIMESTAMP.ext`
   - ✓ File exists in: `uploads/profiles/` folder

---

## Troubleshooting

### Image not showing?
```powershell
# Check file exists
ls uploads/profiles/

# Check database
php check_final_db.php

# Run full test
php test_profile_system.php
```

### Upload fails?
- Check directory permissions: `uploads/profiles/` should be writable
- Check file size: Max 3MB
- Check file type: JPG, PNG, WEBP only

### 404 Error?
- Verify BASE_URL in `backend/includes/config.php`
- Check browser console for actual URL being requested
- Ensure file exists: `ls uploads/profiles/profile_*`

---

## Key Changes Made

1. ✅ Upload directory: `uploads/profiles/` (consistent everywhere)
2. ✅ Database format: `uploads/profiles/filename` (relative path)
3. ✅ Web URL: `BASE_URL/uploads/profiles/filename`
4. ✅ No absolute paths in database
5. ✅ No incorrect paths like `backend/dashboard/uploads/`

---

**Status:** Production Ready ✅  
**Last Updated:** November 30, 2025  
**Migration Completed:** 2 records updated  
**Tests Passing:** All green ✅

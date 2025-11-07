# Customer Profile Update Implementation

## Overview
This document describes the implementation of new profile fields for the Customer Dashboard in the CarWash project.

## Changes Made

### 1. Database Migration
**File:** `database/migrations/add_profile_fields.sql`

Added the following columns to the `users` table:
- `home_phone` VARCHAR(20) - Home phone number (required)
- `national_id` VARCHAR(20) - National ID number (required, 11 digits)
- `driver_license` VARCHAR(20) - Driver's license number (optional)

Indexes added for performance:
- `idx_national_id` on national_id column
- `idx_driver_license` on driver_license column

### 2. Frontend Changes
**File:** `backend/dashboard/Customer_Dashboard.php`

#### Profile Form Updates (Lines ~1069-1240)
1. **Form Attributes Added:**
   - `id="profileForm"` for JavaScript targeting
   - `enctype="multipart/form-data"` for file uploads

2. **New Fields Added:**
   - **Profile Image Upload** (Required)
     - File input with image type validation
     - Real-time preview before submission
     - Max size: 2MB
     - Accepted formats: JPG, PNG, WEBP
   
   - **Home Phone** (Required)
     - Input type: tel
     - Field ID: `profile_home_phone`
     - Placeholder: "+90 212 345 67 89"
   
   - **National ID** (Required)
     - Input type: text
     - Field ID: `profile_national_id`
     - Pattern: 11 digits (validated both client and server side)
     - Placeholder: "12345678901"
   
   - **Driver's License** (Optional)
     - Input type: text
     - Field ID: `profile_driver_license`
     - Placeholder: "A1234567"

3. **Address Field Enhanced:**
   - Converted to full-width textarea (md:col-span-2)
   - 3 rows for better usability

#### JavaScript Implementation (Lines ~1451-1588)
1. **Image Preview Handler:**
   - Validates file type (JPEG, PNG, WEBP only)
   - Validates file size (2MB max)
   - Shows preview before upload using FileReader API

2. **Form Submission Handler:**
   - Prevents default form submission
   - Creates FormData with all fields
   - Adds CSRF token for security
   - Handles file upload
   - Shows success/error messages
   - Updates session avatar on success
   - Smooth scrolling to messages

### 3. Backend Changes
**File:** `backend/dashboard/Customer_Dashboard_process.php`

#### Profile Update Handler (Lines ~306-413)
1. **Field Extraction & Validation:**
   - Extracts all new fields from POST
   - Validates required fields (name, email, home_phone, national_id)
   - Validates National ID format (11 digits)
   - Returns 400 error with messages for validation failures

2. **Image Upload Processing:**
   - Accepts files under `profile_image` or `profile_photo` keys
   - Validates file type (JPEG, PNG, WEBP only)
   - Validates file size (2MB max)
   - Deletes old profile image if exists
   - Generates unique filename: `profile_{user_id}_{timestamp}.{ext}`
   - Stores in: `backend/uploads/profiles/`
   - Returns web path with `/carwash_project` prefix

3. **Database Updates:**
   - **Auto-migration:** Checks if columns exist, adds them if missing
   - Updates `users` table with all fields including new ones
   - Updates/inserts `user_profiles` table with image, address, city
   - Updates session variables for name and email
   - Returns JSON response with success status and image path

4. **Error Handling:**
   - Catches all exceptions
   - Logs errors to error_log
   - Returns appropriate HTTP status codes (400, 500)
   - Returns user-friendly Turkish error messages

### 4. Assets Created
**File:** `frontend/images/default-avatar.svg`
- Simple SVG placeholder for users without profile images
- Gray silhouette design
- 200x200px dimensions

### 5. Testing Files
1. **test_profile_form.html** - Frontend test page
   - Tests database columns existence
   - Tests form structure
   - Tests required fields
   - Tests file input configuration
   - Auto-runs tests on page load

2. **test_profile_columns.php** - Backend test endpoint
   - Checks if all new columns exist in database
   - Returns JSON with existing/missing columns

## Database Schema Changes

### users table
```sql
ALTER TABLE `users`
ADD COLUMN `home_phone` VARCHAR(20) DEFAULT NULL AFTER `phone`,
ADD COLUMN `national_id` VARCHAR(20) DEFAULT NULL AFTER `home_phone`,
ADD COLUMN `driver_license` VARCHAR(20) DEFAULT NULL AFTER `national_id`;
```

### user_profiles table
Already exists with:
- `profile_image` VARCHAR(255) - Stores profile image path
- `address` TEXT - Full address
- `city` VARCHAR(100) - City name

## API Endpoints

### Update Profile
**Endpoint:** `POST /backend/dashboard/Customer_Dashboard_process.php`

**Parameters:**
- `action` (required): "update_profile"
- `name` (required): Full name
- `email` (required): Email address
- `phone` (optional): Mobile phone
- `home_phone` (required): Home phone
- `national_id` (required): 11-digit National ID
- `driver_license` (optional): Driver's license number
- `city` (optional): City name
- `address` (optional): Full address
- `profile_image` (required): Profile image file (JPG/PNG/WEBP, max 2MB)
- `csrf_token` (required): CSRF token from session

**Response (Success):**
```json
{
  "success": true,
  "message": "Profil başarıyla güncellendi",
  "data": {
    "image": "/carwash_project/backend/uploads/profiles/profile_14_1730976543.jpg"
  }
}
```

**Response (Error):**
```json
{
  "success": false,
  "message": "T.C. Kimlik No gereklidir"
}
```

## Security Features
1. **CSRF Protection:** Token validated on form submission
2. **File Type Validation:** Client and server-side checks
3. **File Size Validation:** 2MB limit enforced
4. **Input Sanitization:** All inputs trimmed and sanitized
5. **SQL Injection Prevention:** Prepared statements used throughout
6. **Session Management:** Updated session variables after successful update
7. **Error Logging:** All errors logged without exposing details to users

## Testing Instructions

### 1. Database Migration
```powershell
Get-Content database/migrations/add_profile_fields.sql | C:\xampp\mysql\bin\mysql.exe -u root carwash_db
```

### 2. Run Automated Tests
1. Open browser: `http://localhost/carwash_project/test_profile_form.html`
2. Tests will run automatically
3. Verify all tests pass

### 3. Manual Testing
1. Login as customer
2. Navigate to Profile section
3. Fill all required fields:
   - Select a profile image (JPG/PNG/WEBP, under 2MB)
   - Enter home phone
   - Enter 11-digit National ID
   - Optionally enter driver's license
4. Click "Kaydet" (Save)
5. Verify success message appears
6. Refresh page and verify all data persists

### 4. Error Testing
Test these error scenarios:
- **Missing required fields** → Shows validation error
- **Invalid National ID** (not 11 digits) → Shows format error
- **Invalid image type** (e.g., PDF) → Shows file type error
- **Image too large** (over 2MB) → Shows file size error

## Files Modified
1. ✅ `database/migrations/add_profile_fields.sql` - NEW
2. ✅ `backend/dashboard/Customer_Dashboard.php` - Updated (lines 10-32, 1069-1240, 1451-1588)
3. ✅ `backend/dashboard/Customer_Dashboard_process.php` - Updated (lines 306-413)
4. ✅ `frontend/images/default-avatar.svg` - NEW
5. ✅ `test_profile_form.html` - NEW
6. ✅ `backend/dashboard/test_profile_columns.php` - NEW

## Styling Consistency
All new fields use TailwindCSS classes consistent with existing form:
- Input fields: `w-full px-4 py-3 border-2 border-gray-300 rounded-lg`
- Labels: `block text-sm font-semibold text-gray-700 mb-2`
- Required indicator: `<span class="text-red-500">*</span>`
- Help text: `text-xs text-gray-500`
- Success message: `bg-green-50 border-2 border-green-200 text-green-700`
- Error message: `bg-red-50 border-2 border-red-200 text-red-600`

## Browser Compatibility
- ✅ Chrome/Edge (latest)
- ✅ Firefox (latest)
- ✅ Safari (latest)
- ✅ Mobile browsers (iOS Safari, Chrome Mobile)

## Performance Considerations
1. Image preview uses FileReader API (no server roundtrip)
2. Form validation happens client-side first
3. Database indexes added on new columns
4. Old profile images deleted to save disk space

## Accessibility Features
- ✅ Proper label associations with inputs
- ✅ Required fields marked with asterisk
- ✅ Help text for complex fields (National ID)
- ✅ Error messages are descriptive
- ✅ File input has proper accept attribute
- ✅ Form can be navigated via keyboard

## Future Enhancements
- [ ] Add image cropping tool before upload
- [ ] Add drag-and-drop for image upload
- [ ] Add phone number formatting/masking
- [ ] Add National ID validation algorithm (Luhn check)
- [ ] Add ability to remove profile image
- [ ] Add profile completion percentage indicator

## Support
For issues or questions, contact the development team or check the main project README.

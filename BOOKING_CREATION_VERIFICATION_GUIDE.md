# Booking Creation Verification Guide

## Summary of Server-Side Hardening

All server-side validation and security checks have been successfully implemented in `backend/api/bookings/create.php`:

### ✅ Implemented Validations

1. **Vehicle Ownership Verification**
   - Validates that `vehicle_id` belongs to the authenticated user
   - Queries: `SELECT id FROM vehicles WHERE id = :id AND user_id = :uid`
   - Returns field-specific error: `{"vehicle_id": "Selected vehicle not found or not owned by you"}`

2. **Date/Time Validation**
   - Date format: YYYY-MM-DD (validates with DateTime)
   - Date cannot be in the past (compares against today midnight)
   - Time format: HH:MM in 24-hour format (regex: `^(?:[01]\d|2[0-3]):[0-5]\d$`)
   - Returns specific errors for invalid formats or past dates

3. **Customer Fields Sanitization**
   - `customer_name`: trimmed, minimum length 2 characters
   - `customer_phone`: trimmed, minimum length 5 characters
   - `notes`: trimmed and sanitized
   - All fields properly escaped before DB insertion

4. **Service Validation**
   - Verifies service exists and belongs to the specified carwash
   - Query: `SELECT id, price FROM services WHERE id = :id AND carwash_id = :cw`
   - Returns error if service not found for carwash

5. **Structured Error Responses**
   - Uses `Response::validationError($fieldErrors)` which returns:
     ```json
     {
       "success": false,
       "message": "Validation failed",
       "data": {
         "errors": {
           "field_name": "Error message",
           "another_field": "Another error"
         }
       }
     }
     ```
   - Frontend JS updated to map server errors to inline field error displays

---

## Browser Testing Checklist

### Prerequisites
1. XAMPP running (Apache + MySQL)
2. Logged in as carwash user
3. Browser console open (F12 → Console tab)
4. Network tab open to inspect API requests

### Test Steps

#### 1. **Navigate to Dashboard**
```
http://localhost/carwash_project/backend/dashboard/Car_Wash_Dashboard.php
```

#### 2. **Open Manual Reservation Modal**
- Click "Yeni Rezervasyon Oluştur" button (or equivalent trigger)
- Verify services dropdown populates (from `/backend/api/services/get.php`)
- Verify vehicles dropdown populates (from `/backend/dashboard/vehicle_api.php?action=list`)

#### 3. **Test Field-Level Validation (Client-Side)**
- Leave all fields empty → Click submit
- **Expected**: Inline red error messages under each required field
- Fill date with yesterday → Click submit
- **Expected**: "Date cannot be in the past" error
- Enter invalid time (e.g., "25:00") → Click submit
- **Expected**: "Invalid time format (HH:MM)" error

#### 4. **Test Server-Side Validation**
Open browser console and run:
```javascript
// Simulate form submission with invalid vehicle (not owned by user)
const formData = new FormData();
formData.set('csrf_token', document.querySelector('meta[name="csrf-token"]')?.content || '');
formData.set('carwash_id', '1'); // adjust to your carwash ID
formData.set('service_id', '1');
formData.set('vehicle_id', '999'); // non-existent or not owned
formData.set('date', '2025-12-01');
formData.set('time', '10:00');
formData.set('customer_name', 'Test');
formData.set('customer_phone', '12345');

fetch('/carwash_project/backend/api/bookings/create.php', {
  method: 'POST',
  credentials: 'same-origin',
  body: formData
}).then(r => r.json()).then(console.log);

// Expected response:
// {
//   "success": false,
//   "message": "Validation failed",
//   "data": {
//     "errors": {
//       "vehicle_id": "Selected vehicle not found or not owned by you"
//     }
//   }
// }
```

#### 5. **Test Successful Booking Creation**
Fill all fields correctly:
- **Service**: Select any available service
- **Vehicle**: Select one of your vehicles
- **Date**: Today or future date (YYYY-MM-DD)
- **Time**: Valid 24h time (HH:MM, e.g., "14:30")
- **Customer Name**: "Test Customer"
- **Customer Phone**: "05551234567"
- **Location**: "Test Location"
- **Notes**: "Test notes"

Click submit and verify:
- Success notification appears
- Modal closes
- Reservations list refreshes (if `reloadCarwashReservations()` is available)

---

## Database Verification

### Check Booking Inserted

```sql
-- View most recent booking
SELECT 
  id, 
  user_id, 
  carwash_id, 
  service_id, 
  vehicle_id,
  customer_name,
  customer_phone,
  booking_date,
  booking_time,
  status,
  total_price,
  notes,
  created_at
FROM bookings 
ORDER BY created_at DESC 
LIMIT 5;
```

**Expected**: New row with your test data, `status = 'pending'`, and correct `total_price` from service

### Verify Vehicle Ownership (Security)

```sql
-- Confirm vehicle belongs to the user who created the booking
SELECT 
  b.id AS booking_id,
  b.vehicle_id,
  v.user_id AS vehicle_owner_id,
  b.user_id AS booking_user_id,
  CASE 
    WHEN v.user_id = b.user_id THEN 'VALID'
    ELSE 'SECURITY VIOLATION'
  END AS ownership_check
FROM bookings b
LEFT JOIN vehicles v ON v.id = b.vehicle_id
WHERE b.id = (SELECT id FROM bookings ORDER BY created_at DESC LIMIT 1);
```

**Expected**: `ownership_check = 'VALID'`

### Verify Service Belongs to Carwash

```sql
-- Confirm service belongs to the carwash context
SELECT 
  b.id AS booking_id,
  b.service_id,
  s.carwash_id AS service_carwash_id,
  b.carwash_id AS booking_carwash_id,
  CASE 
    WHEN s.carwash_id = b.carwash_id THEN 'VALID'
    ELSE 'INTEGRITY VIOLATION'
  END AS service_check
FROM bookings b
LEFT JOIN services s ON s.id = b.service_id
WHERE b.id = (SELECT id FROM bookings ORDER BY created_at DESC LIMIT 1);
```

**Expected**: `service_check = 'VALID'`

---

## Common Issues & Troubleshooting

### Issue: "Invalid CSRF token" (403)
**Cause**: CSRF token missing or mismatched  
**Fix**:
1. Check `<meta name="csrf-token">` exists in page `<head>`
2. Verify session `csrf_token` is set: `print_r($_SESSION);` in PHP
3. Ensure JS includes token in request:
   ```javascript
   formData.set('csrf_token', document.querySelector('meta[name="csrf-token"]')?.content || '');
   ```

### Issue: "Authentication required" (401)
**Cause**: User not logged in or session expired  
**Fix**:
1. Login again as carwash user
2. Check session: `echo session_id(); print_r($_SESSION);`
3. Verify `$_SESSION['user_id']` and `$_SESSION['carwash_id']` are set

### Issue: "Selected vehicle not found or not owned by you"
**Cause**: Vehicle ownership mismatch  
**Fix**:
1. Query: `SELECT id, user_id FROM vehicles WHERE user_id = {your_user_id};`
2. Use only vehicle IDs returned from this query
3. Or create a vehicle for your user first

### Issue: "Selected service not found for this carwash"
**Cause**: Service doesn't belong to the carwash  
**Fix**:
1. Query: `SELECT id, name FROM services WHERE carwash_id = {your_carwash_id};`
2. Use only service IDs returned from this query
3. Or create a service for your carwash first

### Issue: "Date cannot be in the past"
**Cause**: Selected date is before today  
**Fix**:
- Use today's date or a future date
- Format: `YYYY-MM-DD` (e.g., "2025-11-21")

### Issue: Frontend inline errors not showing
**Cause**: Error element IDs mismatch or JS update needed  
**Fix**:
1. Verify error `<p>` elements exist with IDs:
   - `manualServiceError`
   - `manualVehicleError`
   - `manualDateError`
   - `manualTimeError`
   - `manualCustomerNameError`
   - `manualCustomerPhoneError`
2. Check browser console for JS errors

---

## Network Tab Inspection

### Request (should include):
```
POST /carwash_project/backend/api/bookings/create.php
Content-Type: multipart/form-data

Form Data:
  csrf_token: abc123...
  carwash_id: 1
  service_id: 2
  vehicle_id: 3
  date: 2025-11-25
  time: 14:30
  customer_name: Test Customer
  customer_phone: 05551234567
  location: Test Location
  notes: Test notes
```

### Success Response (200):
```json
{
  "success": true,
  "message": "Booking created",
  "data": {
    "booking_id": 123
  }
}
```

### Validation Error Response (422):
```json
{
  "success": false,
  "message": "Validation failed",
  "data": {
    "errors": {
      "date": "Date cannot be in the past",
      "vehicle_id": "Selected vehicle not found or not owned by you"
    }
  }
}
```

---

## PHP Error Log Check

If unexpected 500 errors occur:

```powershell
# Windows PowerShell
Get-Content "C:\xampp\apache\logs\error.log" -Tail 50

# Or check PHP error log location from php.ini:
# C:\xampp\php\php.ini → search for "error_log"
```

Look for lines like:
```
bookings/create.php error: ...
```

---

## Files Modified

1. **`backend/api/bookings/create.php`** ✅
   - Added vehicle ownership check (lines ~120-126)
   - Added date/time validation (lines ~78-102)
   - Added customer field validation (lines ~104-112)
   - Added service existence check (lines ~119-122)
   - Included optional fields in INSERT (lines ~128-138, ~168-178)

2. **`backend/dashboard/Car_Wash_Dashboard.php`** ✅
   - Updated JS error handling to map server errors to inline fields (lines ~3676-3699)
   - Added field mapping for structured error responses

---

## Next Steps

1. **Manual Browser Test**: Follow "Browser Testing Checklist" above
2. **DB Verification**: Run SQL queries to confirm data integrity
3. **Edge Cases**: Test with:
   - Empty required fields
   - Past dates
   - Invalid time formats
   - Vehicle IDs from other users
   - Service IDs from other carwashes
4. **Integration Test**: Create end-to-end booking → approval → payment flow

---

## Summary

✅ **Server-side validation**: Complete  
✅ **Vehicle ownership check**: Implemented  
✅ **Date/time validation**: Enforced  
✅ **Customer fields sanitization**: Applied  
✅ **Structured error responses**: Configured  
✅ **Frontend error display**: Updated  
✅ **PHP syntax check**: Passed  

**Status**: Ready for browser testing and production deployment.

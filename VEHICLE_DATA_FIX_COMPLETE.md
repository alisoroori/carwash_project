# Vehicle Data Fix - Complete Root Cause Analysis & Solution

## Executive Summary
Vehicle information (brand, model, color, plate) is not displaying in invoice.php or dashboards because:
1. **create.php doesn't save vehicle data** - receives vehicle ID but never writes it to database
2. **Schema mismatch** - bookings uses `vehicle_type`, user_vehicles uses `brand`
3. **Missing column** - bookings table has NO `vehicle_color` column
4. **Inconsistent field mapping** - invoice.php expects different field names than database has

## Root Cause Details

### 1. Database Schema Analysis

**bookings table** (actual structure):
```sql
- vehicle_type: enum('sedan','suv','truck','van','motorcycle') NOT NULL
- vehicle_plate: varchar(20) DEFAULT NULL
- vehicle_model: varchar(100) DEFAULT NULL
- vehicle_color: DOES NOT EXIST ❌
```

**user_vehicles table**:
```sql
- brand: varchar(100) - maps to vehicle brand name (Toyota, Honda, etc.)
- model: varchar(100) - maps to vehicle model
- license_plate: varchar(50) - maps to plate number
- color: varchar(50) - vehicle color
```

**Current database state** (last 3 bookings):
```
| id | vehicle_type | vehicle_plate | vehicle_model |
|----|--------------|---------------|---------------|
| 15 | sedan        | NULL          | NULL          |
| 14 | sedan        | NULL          | NULL          |
| 13 | sedan        | NULL          | NULL          |
```

### 2. Code Flow Analysis

#### A. Booking Creation (`backend/api/reservations/create.php`)
**What it receives:**
```php
$vehicle = trim($_POST['vehicle'] ?? '');  // This is the user_vehicles.id
```

**What it SHOULD do:**
- Look up vehicle data from user_vehicles table
- Map to bookings table columns
- Save vehicle_type, vehicle_plate, vehicle_model

**What it ACTUALLY does:**
```php
$insert = [
    'user_id' => $user_id,
    'carwash_id' => $location_id,
    'service_id' => $service_id,
    'booking_date' => $date,
    'booking_time' => $time,
    'status' => 'pending',
    'total_price' => $price,
    'notes' => $notes
    // ❌ NO VEHICLE FIELDS AT ALL!
];
```

Result: Database gets default `vehicle_type='sedan'` with NULL plate/model.

#### B. Invoice Display (`backend/checkout/invoice.php`)
**What it expects:**
```php
$bookingData['vehicle'] = [
    'brand' => $booking['vehicle_brand'],  // ❌ Column doesn't exist
    'model' => $booking['vehicle_model'],  // ✅ Exists
    'plate' => $booking['vehicle_plate'],  // ✅ Exists
    'color' => $booking['vehicle_color']   // ❌ Column doesn't exist
];
```

**What it gets:**
- `vehicle_type` = 'sedan' (enum value, not brand name)
- `vehicle_plate` = NULL
- `vehicle_model` = NULL
- `vehicle_color` = Column doesn't exist

**Result:** All vehicle fields display as empty.

## Complete Fix Implementation

### Step 1: Add Missing Column to bookings Table
```sql
ALTER TABLE bookings 
ADD COLUMN vehicle_color VARCHAR(50) DEFAULT NULL AFTER vehicle_model;
```

### Step 2: Fix reservations/create.php to Save Vehicle Data
The file needs to:
1. Fetch vehicle data from user_vehicles table using the vehicle ID
2. Map fields correctly to bookings table
3. Handle both numeric IDs and legacy string values

### Step 3: Fix invoice.php Field Mapping
Replace `vehicle_brand` references with `vehicle_type` and ensure fallback logic works.

### Step 4: Update Dashboard Queries
Ensure consistent field naming across all queries.

## Files Requiring Changes

1. **database/add_vehicle_color.sql** (NEW)
2. **backend/api/reservations/create.php** (MODIFY)
3. **backend/checkout/invoice.php** (MODIFY - field mapping)
4. **backend/dashboard/Customer_Dashboard.php** (VERIFY - queries seem OK)

## Testing Plan

After fixes:
1. Create a new booking with vehicle ID
2. Check database: verify vehicle_type, vehicle_plate, vehicle_model, vehicle_color are populated
3. Open invoice.php: verify all 4 vehicle fields display
4. Check Customer Dashboard: verify vehicle data shows
5. Check Carwash Dashboard: verify vehicle data shows

## Expected Outcome

After fixes, invoice.php will display:
```
Araç Bilgileri
--------------
Marka: Toyota (from user_vehicles.brand)
Model: Corolla (from bookings.vehicle_model or user_vehicles.model)
Renk: Kırmızı (from bookings.vehicle_color or user_vehicles.color)
Plaka: 34ABC123 (from bookings.vehicle_plate or user_vehicles.license_plate)
```

All dashboards will also show complete vehicle information.

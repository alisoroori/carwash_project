# CarWash API Documentation

## Authentication

Base URL: `http://localhost/carwash_project/backend/api`

### Login

**POST** `/auth/login.php`

```json
{
  "email": "user@example.com",
  "password": "password123"
}
```

Response:

```json
{
  "success": true,
  "token": "jwt_token_here",
  "user": {
    "id": 1,
    "name": "User Name",
    "role": "customer"
  }
}
```

### Register

**POST** `/auth/register.php`

```json
{
  "name": "User Name",
  "email": "user@example.com",
  "password": "password123",
  "role": "customer"
}
```

## Bookings

### Create Booking

**POST** `/booking/create.php`
Headers:

- `Authorization: Bearer {token}`

```json
{
  "service_id": 1,
  "date": "2024-01-01",
  "time_slot": "10:00"
}
```

### List Bookings

**GET** `/booking/list.php`
Headers:

- `Authorization: Bearer {token}`

Query Parameters:

- `status` (optional): pending, confirmed, completed
- `date` (optional): YYYY-MM-DD

## Services

### List Services

**GET** `/services/list.php`
Public endpoint, no authentication required.

### Add Service (Admin only)

**POST** `/admin/services/add.php`
Headers:

- `Authorization: Bearer {token}`

```json
{
  "name": "Premium Wash",
  "description": "Full service car wash",
  "price": 29.99,
  "duration": 60
}
```

# CarWash Project — API Reference

This document lists available backend endpoints, required parameters, authentication notes and example requests/responses. The app uses session-based authentication (cookies). API endpoints detect JSON accept header and return JSON; web pages may redirect.

---

## Authentication

### POST /backend/auth/login.php
- Description: Authenticate user (creates session cookie).
- Params (form or JSON):
  - email (string) — required
  - password (string) — required
- Auth: public
- Response (API JSON)
  - 200 OK
    {
      "success": true,
      "message": "Logged in",
      "user": { "id": 1, "email": "hasan@carwash.com", "role": "admin" }
    }
  - 401 Unauthorized
    { "success": false, "message": "Invalid credentials" }

- Example (curl, JSON API):
  curl -i -H "Accept: application/json" -X POST \
    -d '{"email":"hasan@carwash.com","password":"password123"}' \
    -H "Content-Type: application/json" \
    http://localhost/carwash_project/backend/auth/login.php

Notes: After login the server sets a session cookie. Use that cookie for authenticated requests.

---

### POST /backend/auth/register.php
- Description: Create new user.
- Params:
  - name (string) — required
  - email (string) — required, unique
  - password (string) — required
  - role (string) — optional (customer/carwash/admin)
- Auth: public (registration)
- Example response:
  { "success": true, "message": "User registered", "user_id": 42 }

---

### GET /backend/auth/logout.php
- Description: Destroy session.
- Auth: requires authenticated user
- Response: redirect to login page or JSON { "success": true, "message": "Logged out" }

---

## Bookings

### POST /backend/api/bookings/create.php
- Description: Create a booking for authenticated user.
- Auth: required (session cookie)
- Params (form or JSON):
  - service_id (int) — required
  - date (YYYY-MM-DD) — required
  - time (HH:MM) — required
  - location (string) — optional
- Example response:
  { "success": true, "booking_id": 123, "message": "Booking created" }

- Example (curl):
  curl -i -b cookies.txt -X POST \
    -H "Accept: application/json" \
    -d '{"service_id":1,"date":"2025-10-30","time":"10:00"}' \
    http://localhost/carwash_project/backend/api/bookings/create.php

Note: Use stored session cookie (-b/-c curl options) or browser cookie.

---

### GET /backend/api/bookings/list.php
- Description: List bookings for current user (or all for admin).
- Auth: required
- Query params:
  - status (string) — optional (pending, confirmed, cancelled)
  - limit (int), offset (int) — optional
- Example response:
  { "success": true, "bookings": [ { "id":1, "service":"Wash", "date":"2025-10-30", "status":"confirmed" } ] }

---

## Services

### GET /backend/api/services/list.php
- Description: Public list of services.
- Auth: not required
- Query params: none
- Example response:
  { "success": true, "services": [ { "id":1, "name":"Basic Wash", "price":"10.00" } ] }

---

### POST /backend/api/services/manage.php
- Description: Create or update a service. Admin-only.
- Auth: required (role = admin)
- Params:
  - id (int) — optional (if present = update)
  - name (string) — required
  - price (decimal) — required
  - description (string) — optional
- Example response:
  { "success": true, "service_id": 5 }

---

## Notifications

### GET /backend/api/notifications/list.php
- Description: Get notifications for authenticated user.
- Auth: required
- Query params: limit, offset
- Response:
  { "success": true, "notifications": [ { "id":1, "title":"Your booking", "status":"unread" } ] }

### POST /backend/api/notifications/mark-read.php
- Description: Mark a notification as read.
- Auth: required
- Params:
  - id (int) — required
- Response:
  { "success": true, "message": "Marked as read" }

---

## Error Handling & Auth Notes
- Session cookie authentication: endpoints expect the PHP session cookie set by login.
- API endpoints return JSON with keys: success (bool), message (string), data-specific fields.
- For unauthorized access:
  - API requests: HTTP 401 (unauthorized) or 403 (forbidden) with JSON body.
  - Page requests: redirect to `/backend/auth/login.php` or show 403 page.
- CSRF: currently not enforced for API examples. Prefer using JSON API with session cookie and consider adding CSRF tokens for form-based POSTs.

---

## Testing locally
1. Start Apache/XAMPP and ensure project is available at:
   http://localhost/carwash_project/
2. Use browser to login from `/backend/auth/login.php` (session cookie).
3. For command-line testing use curl and pass cookies:
   - Save cookies: curl -c cookies.txt -X POST -d "email=hasan@carwash.com&password=password123" http://localhost/carwash_project/backend/auth/login.php
   - Use cookies: curl -b cookies.txt -H "Accept: application/json" http://localhost/carwash_project/backend/api/bookings/list.php

---

## Additional endpoints
- Admin dashboard pages: /backend/dashboard/admin/*
- Carwash dashboard pages: /backend/dashboard/carwash/*
- Customer dashboard pages: /backend/dashboard/customer/*

If you want a Postman collection exported, import the JSON in the `docs/postman_collection.json` file (optional).

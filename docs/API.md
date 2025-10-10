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

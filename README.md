# 🚗 CarWash Web Application

## English

A web application for managing car wash businesses, customer reservations, and service management.
Built with **PHP (Backend)** and **MySQL (Database)**, designed to run on **XAMPP/LAMP** stack.

### Features

- User registration and authentication (Customer/CarWash)
- CarWash information management (Logo, contact, hours)
- Online booking and appointment management
- Separate dashboards for customers and car washes
- Modular structure for easy development

### File Structure

```
carwash_project/
├── backend/
│   ├── api/
│   │   ├── bookings/
│   │   │   ├── create.php
│   │   │   └── list.php
│   │   ├── locations/
│   │   │   ├── search.php
│   │   │   └── update.php
│   │   ├── payment/
│   │   │   ├── process.php
│   │   │   └── webhook.php
│   │   └── services/
│   │       └── manage.php
│   ├── auth/
│   │   ├── login.php
│   │   ├── register.php
│   │   ├── reset_password.php
│   │   └── uploads/
│   │       └── profiles/
│   ├── dashboard/
│   │   ├── admin/
│   │   │   ├── analytics.php
│   │   │   ├── index.php
│   │   │   ├── users.php
│   │   │   └── zone_mapper.php
│   │   ├── carwash/
│   │   │   ├── bookings.php
│   │   │   ├── index.php
│   │   │   └── services.php
│   │   └── customer/
│   │       ├── bookings.php
│   │       ├── index.php
│   │       └── profile.php
│   └── includes/
│       ├── availability_checker.php
│       ├── db.php
│       ├── image_handler.php
│       ├── location_manager.php
│       ├── notification_channels.php
│       ├── payment_gateway.php
│       ├── payment_tracker.php
│       └── profile_manager.php
├── frontend/
│   ├── css/
│   │   ├── dashboard.css
│   │   ├── main.css
│   │   └── style.css
│   ├── js/
│   │   ├── maps/
│   │   │   └── service-areas.js
│   │   ├── payment/
│   │   │   └── checkout.js
│   │   └── websocket/
│   │       ├── connection-manager.js
│   │       └── event-handler.js
│   ├── booking.html
│   ├── index.html
│   └── services.html
├── database/
│   └── carwash.sql
├── uploads/
│   ├── profiles/
│   └── services/
├── .gitignore
├── README.md
└── project_navigator.html

Backend Structure:

API endpoints in api
Authentication system in auth
Dashboard interfaces in dashboard
Shared includes in includes
Frontend Assets:

CSS stylesheets in css
JavaScript modules in js
HTML pages in root of frontend
Supporting Files:

Database schema in database/carwash.sql
Upload directories for user content
Documentation and configuration files


### API Endpoints

- POST /api/bookings/create.php
  - Create new booking
  - Required: customer_id, service_id, carwash_id, date, time
  - Returns: booking_id, status

- GET /api/bookings/list.php
  - List bookings with filters
  - Parameters: status, date_from, date_to, customer_id
  - Returns: Array of bookings

- GET /api/locations/search.php
  - Search nearby carwashes
  - Parameters: latitude, longitude, radius
  - Returns: Array of carwashes with distances

- POST /api/locations/update.php
  - Update carwash location
  - Required: carwash_id, latitude, longitude, address
  - Returns: success status

- POST /api/payment/process.php
  - Process payment for booking
  - Required: booking_id, payment_method, amount
  - Returns: payment status, transaction_id

- POST /api/payment/webhook.php
  - Handle payment provider webhooks
  - Required: signature, event_type, payment_data
  - Returns: acknowledgment

- index.php: Customer overview
  - Upcoming bookings
  - Booking history
  - Favorite carwashes

- bookings.php: Customer bookings
  - Book new service
  - View booking history
  - Cancel bookings

- profile.php: Profile management
  - Update profile
  - Manage preferences
  - View history
```

# CarWash Project

## Overview
The CarWash Project is a web application designed to manage carwash services, bookings, user accounts, and reviews. It provides a user-friendly interface for customers to book services and for administrators to manage the carwash operations.

## Features
- User management (registration, login, profile management)
- Carwash management (add, update, delete carwash information)
- Service management (CRUD operations for services offered)
- Booking management (create, update, delete bookings)
- Review management (add, update, delete user reviews)
- Application settings management

## Project Structure
```
carwash_project
├── backend
│   ├── includes
│   │   └── db.php
│   ├── queries
│   │   ├── users.php
│   │   ├── carwashes.php
│   │   ├── services.php
│   │   ├── bookings.php
│   │   ├── reviews.php
│   │   └── settings.php
│   └── README.md
└── README.md
```

## Setup Instructions
1. **Clone the repository**:
   ```
   git clone <repository-url>
   cd carwash_project
   ```

2. **Set up the database**:
   - Create a MySQL database named `carwash_db`.
   - Import the SQL schema provided in the `backend/queries` directory.

3. **Configure database connection**:
   - Open `backend/includes/db.php` and update the database credentials if necessary.

4. **Run the application**:
   - Use XAMPP to start the Apache and MySQL services.
   - Access the application via `http://localhost/carwash_project`.

## Usage
- Users can register and log in to manage their bookings and reviews.
- Administrators can manage carwash services, bookings, and user accounts through the backend.

## Contributing
Contributions are welcome! Please submit a pull request or open an issue for any enhancements or bug fixes.

## License
This project is licensed under the MIT License. See the LICENSE file for details.
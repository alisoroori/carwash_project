# CarWash Project

## Overview
The CarWash Project is a web application designed to manage carwash services, bookings, user accounts, and reviews. It provides a user-friendly interface for customers to book services and for administrators to manage the operations of the carwash.

## Features
- User management (registration, login, profile management)
- Carwash management (add, update, delete carwash information)
- Service management (CRUD operations for services offered)
- Booking management (create, update, delete bookings)
- Review management (add, update, delete reviews)
- Settings management (update application configuration)

## Installation
1. Clone the repository to your local machine.
2. Ensure you have XAMPP installed and running.
3. Place the project folder in the `htdocs` directory of your XAMPP installation.
4. Create a database named `carwash_db` in your MySQL server.
5. Import the SQL schema provided in the `backend/queries` directory to set up the necessary tables.
6. Update the database credentials in `backend/includes/db.php` if necessary.

## Usage
- Access the application through your web browser at `http://localhost/carwash_project`.
- Use the provided endpoints in the `backend/queries` directory to interact with the database.
- Follow the API documentation for details on how to use each endpoint.

## Contributing
Contributions are welcome! Please fork the repository and submit a pull request for any enhancements or bug fixes.

## License
This project is licensed under the MIT License. See the LICENSE file for more details.
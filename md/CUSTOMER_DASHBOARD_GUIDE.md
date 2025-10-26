# Customer Dashboard Links

## Direct Dashboard Access
- **Customer Dashboard**: [http://localhost/carwash_project/backend/dashboard/Customer_Dashboard.php](http://localhost/carwash_project/backend/dashboard/Customer_Dashboard.php)

## Authentication Flow
1. **Login Page**: [http://localhost/carwash_project/backend/auth/login.php](http://localhost/carwash_project/backend/auth/login.php)
2. **Customer Registration**: [http://localhost/carwash_project/backend/auth/Customer_Registration.php](http://localhost/carwash_project/backend/auth/Customer_Registration.php)
3. **Car Wash Registration**: [http://localhost/carwash_project/backend/auth/Car_Wash_Registration.php](http://localhost/carwash_project/backend/auth/Car_Wash_Registration.php)

## How Authentication Works
1. User logs in through login page
2. Login process validates credentials
3. If successful and user role is 'customer', redirects to Customer_Dashboard.php
4. Dashboard checks session and role before allowing access
5. If not authenticated or wrong role, redirects back to login

## Dashboard Features
- **Dashboard Overview**: Statistics and quick metrics
- **Car Wash Selection**: Browse and select car wash services
- **Reservations**: View and manage bookings
- **Profile Management**: Update personal information
- **Service History**: View past services
- **Notifications**: System alerts and updates

## Navigation Integration
- Header now includes dashboard links when user is logged in
- Mobile menu includes dashboard access
- Logout functionality available in header
- Consistent navigation across all pages

## Testing the System
1. Register as a customer or use existing credentials
2. Login through the login page
3. Should automatically redirect to Customer Dashboard
4. Test navigation between dashboard sections
5. Test logout functionality
6. Verify all links work correctly

## File Structure
```
backend/
  dashboard/
    Customer_Dashboard.php       # Main customer dashboard
    Customer_Dashboard_process.php # Processing logic
  auth/
    login.php                    # Login page (redirects here after login)
    Customer_Registration.php    # Customer registration
  includes/
    header.php                   # Universal header with dashboard links
    footer.php                   # Universal footer with scroll-to-top
```

## Recent Updates
- ✅ Customer Dashboard now uses standardized header/footer
- ✅ Header includes dashboard links for authenticated users
- ✅ Mobile navigation includes dashboard access
- ✅ Logout functionality integrated in header
- ✅ Proper session management and role checking
- ✅ Consistent styling across all pages
- ✅ Universal scroll-to-top button on all pages
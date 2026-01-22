# Tanza Price Tracking System

A comprehensive web-based price tracking and management system for Tanza market vendors and administrators.

## Features

### For Administrators
- **Dashboard**: Overview of market activities and price trends
- **Vendor Management**: Manage vendor registrations, approvals, and suspensions
- **Price Management**: Monitor and manage product prices across different categories
- **Business Permit Management**: Handle business permit applications and renewals
- **Cleaning Management**: Track and manage market cleaning requests
- **Report Management**: Handle overpricing reports and complaints
- **Survey Forms**: Create and manage vendor surveys
- **Event Management**: Organize and publish market events
- **Notifications**: Send announcements to vendors and users

### For Vendors
- **Dashboard**: View business metrics and notifications
- **Product Management**: Add, edit, and delete product listings
- **Business Permit**: Submit and track business permit applications
- **Cleaning Requests**: Request stall cleaning services
- **Survey Forms**: Participate in market surveys
- **Events**: Register for market events
- **Profile Management**: Update vendor information

### For Public Users
- **Price Dashboard**: View current market prices for various products
- **Product Categories**: Browse prices by category (Fish, Vegetables, Fruits, Rice & Grains, Meat & Poultry, Other Products)
- **Price Analytics**: View price trends and market analytics
- **Report Overpricing**: Submit reports about overpriced products

## Product Categories

1. **Fish & Seafood** - Fresh fish and seafood products
2. **Vegetables** - Fresh vegetables and produce
3. **Fruits** - Fresh fruits
4. **Rice & Grains** - Rice and grain products
5. **Meat & Poultry** - Meat and poultry products
6. **Other Products** - Condiments, dairy, and other market products

## Technology Stack

- **Frontend**: HTML, CSS, JavaScript
- **Backend**: PHP
- **Database**: MySQL (via connection.php)
- **PDF Generation**: TCPDF library
- **Excel Export**: PhpSpreadsheet library

## Installation

1. Clone this repository to your web server directory
2. Configure your database connection in `php/connection.php`
3. Import the database schema (if provided)
4. Ensure proper permissions for upload directories:
   - `uploads/`
   - `php/uploads/`
5. Configure your web server (Apache/Nginx) to serve the application

## File Structure

```
├── admin-*.php          # Admin panel pages
├── vendor-*.php         # Vendor pages
├── CSS/                 # Stylesheets
├── JS/                  # JavaScript files
├── php/                 # PHP backend scripts
├── IMG/                 # Images and icons
├── uploads/             # User uploaded files
├── components/          # Reusable components
└── tools/               # Utility scripts
```

## Usage

### Admin Access
Navigate to `admin-dashboard.php` and login with admin credentials.

### Vendor Access
Navigate to `vendor-dashboard.php` and login with vendor credentials.

### Public Access
Visit `index.php` or `pricefront.php` to view current market prices.

## Security Notes

- Ensure proper authentication checks are in place
- Sanitize all user inputs
- Use prepared statements for database queries
- Keep upload directories secured
- Regular backup of database

## License

[Add your license information here]

## Support

For support and inquiries, please contact the Tanza Market administration.

## Contributors

[Add contributor information here]

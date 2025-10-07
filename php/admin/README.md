# KineticEV Admin Portal Documentation

## Overview
The KineticEV Admin Portal is a comprehensive administrative interface for managing the KineticEV platform. It provides secure access to various administrative functions, data management capabilities, and system monitoring tools.

## Core Components

### 1. Authentication System
- **Location**: `login.php`, `logout.php`, `reset-password.php`
- **Features**:
  - Secure login with session management
  - Password hashing using PHP's `password_hash()`
  - Session security with HTTP-only cookies
  - Session fixation prevention
  - Automatic logout on session expiry
  - Password reset functionality

### 2. Admin Handler (`AdminHandler.php`)
Core class that manages all administrative operations:
- Database connections and operations
- User authentication and authorization
- Table management and data retrieval
- Analytics and statistics generation
- User management functions
- Dealership management
- Security implementations

### 3. API Endpoints (`api.php`)
RESTful API endpoints for various administrative functions:
```
GET  /dashboard_stats   - Retrieve dashboard statistics
GET  /table_data       - Get data for specific tables
GET  /filter_options   - Get available filter options
GET  /analytics        - Retrieve analytics data
GET  /logs            - Access system logs
GET  /current_user    - Get current user info
GET  /users           - List all admin users
POST /create_user     - Create new admin user
POST /update_user     - Update existing user
POST /delete_user     - Delete admin user
```

### 4. Dealership Management
- **Location**: Integrated into `AdminHandler.php`
- **Functionality**:
  - Create new dealerships
  - Edit existing dealership information
  - View dealership details
  - Manage dealership status

## Security Features

### 1. Session Management
- Secure session configuration
- Session timeout handling
- Session hijacking prevention
- CSRF protection
- XSS prevention through HTTPOnly cookies

### 2. Access Control
- Role-based access control (RBAC)
  - Super Admin
  - Admin
  - Limited Admin
- Feature-level permission management
- API endpoint protection

### 3. Security Headers
```php
header('Cache-Control: no-cache, no-store, must-revalidate, max-age=0, private');
header('Pragma: no-cache');
header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');
header('CDN-Cache-Control: no-cache');
```

## Frontend Implementation

### 1. Admin Panel Class
The `AdminPanel` class (`assets/admin.js`) provides:
- Dynamic dashboard management
- Real-time data updates
- Advanced filtering and search
- Table data management
- Error handling and notifications
- Session expiry handling

### 2. Key Features
- Interactive dashboard with statistics
- DataTables integration for data display
- Advanced filtering and search capabilities
- Real-time log viewer
- User management interface
- Dealership management forms

## Database Structure

### Admin Users Table
```sql
CREATE TABLE admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    uuid VARCHAR(36) UNIQUE,
    username VARCHAR(50) UNIQUE,
    password_hash VARCHAR(255),
    email VARCHAR(255),
    full_name VARCHAR(100),
    role ENUM('super_admin', 'admin', 'limited_admin'),
    is_active TINYINT(1) DEFAULT 1,
    last_login DATETIME,
    last_login_ip VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)
```

## API Examples

### 1. Authentication
```php
// Login
POST /admin/login.php
{
    "username": "admin",
    "password": "secure_password"
}

// Response
{
    "success": true,
    "redirect": "index.php"
}
```

### 2. Dashboard Stats
```php
// Request
GET /admin/api.php?action=dashboard_stats

// Response
{
    "total_users": 1000,
    "active_dealerships": 50,
    "recent_transactions": 150,
    "pending_approvals": 25
}
```

## Default Configuration
- Default admin username: kineticadmin
- Initial password requirement: Must be changed on first login
- Session timeout: 1 hour
- Failed login attempts limit: 5 attempts before temporary lockout

## Security Best Practices
1. Regular password changes
2. Strong password requirements
3. IP-based access restrictions
4. Regular security audits
5. Comprehensive logging
6. Session management
7. Input validation and sanitization

## Error Handling
- Comprehensive error logging
- User-friendly error messages
- Detailed developer logs
- Error reporting configuration
- Exception handling

## Logging System
- Activity logging
- Error logging
- Security event logging
- User action tracking
- System performance monitoring

## Maintenance
1. Regular backup procedures
2. Session cleanup
3. Log rotation
4. Cache management
5. Database optimization

## Development Guidelines
1. Follow PSR standards for PHP code
2. Use prepared statements for all database queries
3. Implement proper input validation
4. Maintain comprehensive documentation
5. Regular security updates
6. Code review procedures

## Testing
1. Authentication testing
2. Authorization testing
3. Input validation testing
4. Session management testing
5. API endpoint testing
6. Security vulnerability testing
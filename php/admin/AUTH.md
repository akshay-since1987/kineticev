# Authentication System Documentation

## Overview
The KineticEV Admin Portal implements a secure authentication system that manages user access, session handling, and password management. This document details the authentication mechanisms and security implementations.

## Components

### 1. Login System (`login.php`)
```php
// Authentication Flow
1. User submits credentials
2. Credentials verified against admin_users table
3. Password verified using password_verify()
4. Session generated with secure parameters
5. User redirected to dashboard
```

### Security Implementations
```php
// Session Security Configuration
ini_set('session.cookie_lifetime', 3600); // 1 hour
ini_set('session.cookie_secure', 0); // Set to 1 for HTTPS
ini_set('session.cookie_httponly', 1); // Prevent XSS
ini_set('session.use_strict_mode', 1); // Prevent session fixation
```

### Session Management
- Session timeout after 1 hour of inactivity
- Session regeneration on login
- Secure cookie handling
- Prevention of session fixation attacks

### Password Management
- Secure password hashing using PHP's password_hash()
- Password requirements:
  - Minimum 8 characters
  - Must contain uppercase and lowercase letters
  - Must contain numbers
  - Must contain special characters
- Password reset functionality with secure token generation

## Authentication Flow

### 1. Login Process
```php
if ($adminHandler->authenticateAdmin($username, $password)) {
    session_regenerate_id(true);
    $_SESSION['admin_logged_in'] = true;
    $_SESSION['admin_login_time'] = time();
    // Additional session variables set
}
```

### 2. Session Validation
```php
// Check authentication on each request
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    // Redirect to login
    header('Location: login.php');
    exit;
}
```

### 3. Password Reset Process
1. User requests password reset
2. Secure token generated and stored
3. Reset link sent to registered email
4. Token validated on reset request
5. New password set with updated hash

## Security Features

### 1. Brute Force Prevention
- Failed login attempt tracking
- Temporary account lockout after 5 failed attempts
- IP-based attempt tracking

### 2. Session Security
```php
// Headers for preventing caching
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');
```

### 3. CSRF Protection
- Token generation for forms
- Token validation on submissions
- Session-specific token binding

## Error Handling

### Login Errors
```php
try {
    // Authentication attempt
} catch (Exception $e) {
    $logger->error('[ADMIN_LOGIN] Login error', [
        'username' => $username,
        'ip' => $_SERVER['REMOTE_ADDR'],
        'error' => $e->getMessage()
    ]);
}
```

### Password Reset Errors
- Invalid token handling
- Expired token handling
- Email sending failures
- Database update errors

## Logging

### Authentication Events
- Successful logins
- Failed login attempts
- Password reset requests
- Password changes
- Session timeouts

### Security Events
- Brute force attempt detection
- Invalid token usage
- Session hijacking attempts
- Unauthorized access attempts

## Best Practices

### 1. Password Security
- Regular password change requirements
- Password history tracking
- Strong password enforcement
- Secure password reset process

### 2. Session Management
- Regular session cleanup
- Secure session storage
- Session fixation prevention
- Cross-site scripting protection

### 3. Access Control
- Role-based access control
- IP-based access restrictions
- Feature-level permissions
- API access control

## Configuration

### Default Settings
```php
// Session configuration
session.cookie_lifetime = 3600
session.cookie_secure = 0/1 (depending on HTTPS)
session.cookie_httponly = 1
session.use_strict_mode = 1

// Password requirements
MIN_PASSWORD_LENGTH = 8
REQUIRE_SPECIAL_CHARS = true
REQUIRE_NUMBERS = true
REQUIRE_MIXED_CASE = true

// Login attempts
MAX_LOGIN_ATTEMPTS = 5
LOCKOUT_DURATION = 30 minutes
```

## Testing Guidelines

### Authentication Testing
1. Valid login credentials
2. Invalid login attempts
3. Password reset functionality
4. Session management
5. CSRF protection
6. Brute force prevention

### Security Testing
1. Session handling
2. Password strength requirements
3. Token validation
4. Error handling
5. Access control enforcement
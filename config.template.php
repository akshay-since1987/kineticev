<?php
// K2 Kinetic EV - Configuration Template
// Copy this file to config.php and update with your actual credentials

// AWS Credentials (replace with your actual credentials)
define('AWS_ACCESS_KEY_ID', 'your-aws-access-key-here');
define('AWS_SECRET_ACCESS_KEY', 'your-aws-secret-key-here');

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'your-database-name');
define('DB_USER', 'your-database-user');
define('DB_PASS', 'your-database-password');

// Salesforce Configuration
define('SF_CLIENT_ID', 'your-salesforce-client-id');
define('SF_CLIENT_SECRET', 'your-salesforce-client-secret');
define('SF_USERNAME', 'your-salesforce-username');
define('SF_PASSWORD', 'your-salesforce-password');

// API Keys
define('SMS_API_KEY', 'your-sms-api-key');
define('PAYMENT_GATEWAY_KEY', 'your-payment-gateway-key');

// Environment
define('ENVIRONMENT', 'development'); // development, test, production

// Base URL
define('BASE_URL', 'http://localhost/K2');

// Email Configuration
define('SMTP_HOST', 'your-smtp-host');
define('SMTP_PORT', 587);
define('SMTP_USER', 'your-email@domain.com');
define('SMTP_PASS', 'your-email-password');

// Security Keys
define('SECRET_KEY', 'generate-a-secure-random-key-here');
define('CSRF_TOKEN_KEY', 'generate-another-secure-key-here');
?>
<?php
// K2 Kinetic EV - Production Configuration Template
// Copy this file to production-config.php and update with your actual production credentials

// AWS Credentials (replace with your actual production credentials)
define('AWS_ACCESS_KEY_ID', 'your-production-aws-access-key');
define('AWS_SECRET_ACCESS_KEY', 'your-production-aws-secret-key');

// Database Configuration
define('DB_HOST', 'your-production-db-host');
define('DB_NAME', 'your-production-db-name');
define('DB_USER', 'your-production-db-user');
define('DB_PASS', 'your-production-db-password');

// Salesforce Production Configuration
define('SF_CLIENT_ID', 'your-production-salesforce-client-id');
define('SF_CLIENT_SECRET', 'your-production-salesforce-client-secret');
define('SF_USERNAME', 'your-production-salesforce-username');
define('SF_PASSWORD', 'your-production-salesforce-password');

// Production API Keys
define('SMS_API_KEY', 'your-production-sms-api-key');
define('PAYMENT_GATEWAY_KEY', 'your-production-payment-gateway-key');

// Environment
define('ENVIRONMENT', 'production');

// Production Base URL
define('BASE_URL', 'https://your-production-domain.com');

// Production Email Configuration
define('SMTP_HOST', 'your-production-smtp-host');
define('SMTP_PORT', 587);
define('SMTP_USER', 'your-production-email@domain.com');
define('SMTP_PASS', 'your-production-email-password');

// Production Security Keys (use strong, unique keys)
define('SECRET_KEY', 'generate-a-strong-production-secret-key');
define('CSRF_TOKEN_KEY', 'generate-a-strong-production-csrf-key');

// Production Settings
define('DEBUG_MODE', false);
define('ERROR_REPORTING', E_ERROR);
?>
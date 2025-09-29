# K2 Security Test Suite

Comprehensive security testing framework for the K2 Kinetic EV booking system.

## 📁 Directory Structure

```
tests/
├── RunSecurityTests.ps1           # Main test suite runner
├── QuickSecurityTest.ps1          # Quick essential tests
├── security/                      # Security test modules
│   ├── DirectAccessProtectionTest.ps1
│   ├── AuthenticationTest.ps1
│   ├── InputValidationTest.ps1
│   ├── APISecurityTest.ps1
│   ├── ConfigurationSecurityTest.ps1
│   ├── SessionCSRFTest.ps1
│   ├── FileDirectorySecurityTest.ps1
│   └── ErrorHandlingTest.ps1
├── functional/                    # Functional tests (future)
└── reports/                       # Test reports output
```

## 🚀 Quick Start

### Option 1: Quick Security Check (2 minutes)
```powershell
cd d:\K2\tests
.\QuickSecurityTest.ps1
```

### Option 2: Full Security Test Suite (10-15 minutes)
```powershell
cd d:\K2\tests
.\RunSecurityTests.ps1 -Verbose
```

### Option 3: Specific Test Category
```powershell
# Test only direct access protection
.\RunSecurityTests.ps1 -TestCategory "access"

# Test only authentication
.\RunSecurityTests.ps1 -TestCategory "auth"

# Test input validation
.\RunSecurityTests.ps1 -TestCategory "input"
```

## 🔒 Test Categories

### 1. Direct Access Protection
- Tests all 35 protected PHP files return 404
- Verifies functionality still works via includes
- **Priority**: CRITICAL

### 2. Authentication & Authorization  
- Admin access controls
- JWT token validation
- Session security
- **Priority**: HIGH

### 3. Input Validation & SQL Injection
- Form input sanitization
- SQL injection prevention
- XSS protection
- **Priority**: CRITICAL

### 4. API Security
- Rate limiting
- Salesforce integration security
- Email/SMS injection protection
- **Priority**: HIGH

### 5. Configuration Security
- Environment detection
- Config file protection
- Debug information leakage
- **Priority**: CRITICAL

### 6. Session & CSRF Protection
- CSRF token validation
- Session cookie security
- **Priority**: MEDIUM

### 7. File & Directory Security
- Directory traversal protection
- .htaccess security
- Sensitive file access
- **Priority**: HIGH

### 8. Error Handling
- Error page information disclosure
- PHP error exposure
- Debug information leakage
- **Priority**: MEDIUM

## 📊 Test Results

### Understanding Results
- ✅ **PASS**: Security control is working correctly
- ❌ **FAIL**: Security vulnerability detected
- ⚠️ **OTHER**: Unexpected response, needs investigation

### Critical Issues
Any FAIL in these categories requires immediate attention:
- Direct Access Protection
- Input Validation & SQL Injection
- Configuration Security

## 🛠️ Usage Examples

### Basic Usage
```powershell
# Run all tests with default settings
.\RunSecurityTests.ps1

# Run with verbose output
.\RunSecurityTests.ps1 -Verbose

# Generate detailed report
.\RunSecurityTests.ps1 -GenerateReport -Verbose
```

### Advanced Usage
```powershell
# Test production environment
.\RunSecurityTests.ps1 -BaseUrl "https://kineticev.in" -Environment "production"

# Test specific category with reporting
.\RunSecurityTests.ps1 -TestCategory "input" -GenerateReport -Verbose

# Custom target
.\RunSecurityTests.ps1 -BaseUrl "https://staging.example.com" -Environment "staging"
```

## 📋 Test Categories Available

| Category | Command | Description |
|----------|---------|-------------|
| `all` | Default | Run all security tests |
| `access` | `-TestCategory "access"` | Direct access protection only |
| `auth` | `-TestCategory "auth"` | Authentication & authorization |
| `input` | `-TestCategory "input"` | Input validation & SQL injection |
| `api` | `-TestCategory "api"` | API security testing |
| `config` | `-TestCategory "config"` | Configuration security |
| `session` | `-TestCategory "session"` | Session & CSRF protection |
| `files` | `-TestCategory "files"` | File & directory security |
| `errors` | `-TestCategory "errors"` | Error handling & disclosure |

## 📈 Interpreting Reports

### Report Structure
```json
{
  "Timestamp": "2025-09-14T10:30:00",
  "Environment": "test",
  "BaseUrl": "https://test.kineticev.in",
  "Summary": {
    "Passed": 45,
    "Failed": 2,
    "SuccessRate": 95.7
  },
  "Results": {
    "DirectAccess": { ... },
    "Authentication": { ... }
  }
}
```

### Success Criteria
- **Direct Access**: 100% of protected files return 404
- **Input Validation**: 100% of malicious payloads blocked
- **Configuration**: 100% of sensitive files protected
- **Overall**: >95% success rate with 0 critical failures

## 🚨 Troubleshooting

### Common Issues

**PowerShell Execution Policy**
```powershell
Set-ExecutionPolicy -ExecutionPolicy RemoteSigned -Scope CurrentUser
```

**Network Timeouts**
- Increase timeout values in test scripts
- Check network connectivity to target

**False Positives**
- Review test results manually
- Check if security controls are working differently than expected

**Missing Dependencies**
- Ensure PowerShell 5.1+ is installed
- Verify network access to target URLs

### Test Environment Setup
1. Ensure target environment is accessible
2. Verify test endpoints exist
3. Check for rate limiting that might affect tests
4. Consider running tests during maintenance windows

## 🔄 Regular Testing Schedule

### Recommended Frequency
- **After deployments**: Run quick test
- **Weekly**: Run full security suite
- **Monthly**: Run comprehensive audit
- **Before releases**: Full test with manual review

### Automation
Consider scheduling regular tests:
```powershell
# Example: Daily quick test
$trigger = New-ScheduledTaskTrigger -Daily -At 6:00AM
$action = New-ScheduledTaskAction -Execute "PowerShell.exe" -Argument "-File D:\K2\tests\QuickSecurityTest.ps1"
Register-ScheduledTask -TaskName "K2DailySecurityCheck" -Trigger $trigger -Action $action
```

## 📞 Support

For issues with the test suite:
1. Check the test logs in the `reports/` directory
2. Run individual test modules to isolate issues
3. Review the verbose output for detailed error information
4. Check network connectivity and permissions

---

**Last Updated**: September 14, 2025  
**Version**: 1.0  
**Compatibility**: PowerShell 5.1+, Windows/Linux/MacOS
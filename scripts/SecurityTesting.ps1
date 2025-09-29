# K2 Security Testing Scripts
# PowerShell scripts for automated security testing

# Script 1: Direct Access Protection Test
function Test-DirectAccessProtection {
    param(
        [string]$BaseUrl = "https://test.kineticev.in"
    )
    
    Write-Host "🛡️ Testing Direct Access Protection..." -ForegroundColor Cyan
    
    # Define all protected files
    $ProtectedFiles = @(
        # CRITICAL - Configuration files
        "/php/config.php",
        "/php/prod-config.php",
        "/php/test-config.php",
        "/php/dev-config.php",
        
        # HIGH PRIORITY - Core classes
        "/php/DatabaseHandler.php",
        "/php/SalesforceService.php",
        "/php/EmailHandler.php",
        "/php/SmsService.php",
        "/php/Logger.php",
        "/php/DataValidator.php",
        "/php/JWTHandler.php",
        "/php/DatabaseUtils.php",
        "/php/DatabaseMigration.php",
        "/php/EmailNotificationsMigration.php",
        "/php/production-timezone-guard.php",
        
        # MEDIUM PRIORITY - Admin
        "/php/admin/AdminHandler.php",
        
        # LOWER PRIORITY - Components
        "/php/components/header.php",
        "/php/components/footer.php",
        "/php/components/head.php",
        "/php/components/scripts.php",
        "/php/components/layout.php",
        "/php/components/modals.php",
        "/php/components/admin-header.php",
        "/php/components/admin-footer.php",
        "/php/components/google-maps-script.php",
        "/php/components/migrate.php",
        
        # Email Templates
        "/php/email-templates/contact-admin-email.tpl.php",
        "/php/email-templates/contact-customer-email.tpl.php",
        "/php/email-templates/test-ride-admin-email.tpl.php",
        "/php/email-templates/test-ride-customer-email.tpl.php",
        "/php/email-templates/transaction-failure-admin.tpl.php",
        "/php/email-templates/transaction-failure-customer.tpl.php",
        "/php/email-templates/transaction-success-admin.tpl.php",
        "/php/email-templates/transaction-success-customer.tpl.php"
    )
    
    $Results = @()
    $PassCount = 0
    $FailCount = 0
    
    foreach ($File in $ProtectedFiles) {
        $Url = "$BaseUrl$File"
        
        try {
            $Response = Invoke-WebRequest -Uri $Url -Method Head -ErrorAction SilentlyContinue
            $StatusCode = $Response.StatusCode
        } catch {
            $StatusCode = $_.Exception.Response.StatusCode.Value__
        }
        
        $Result = [PSCustomObject]@{
            File = $File
            StatusCode = $StatusCode
            Protected = ($StatusCode -eq 404)
            Status = if ($StatusCode -eq 404) { "✅ PASS" } else { "❌ FAIL" }
        }
        
        $Results += $Result
        
        if ($StatusCode -eq 404) {
            $PassCount++
            Write-Host "✅ $File - Protected (404)" -ForegroundColor Green
        } else {
            $FailCount++
            Write-Host "❌ $File - Not Protected ($StatusCode)" -ForegroundColor Red
        }
    }
    
    Write-Host "`n📊 Direct Access Protection Results:" -ForegroundColor Yellow
    Write-Host "✅ Protected: $PassCount files" -ForegroundColor Green
    Write-Host "❌ Unprotected: $FailCount files" -ForegroundColor Red
    Write-Host "📈 Success Rate: $([math]::Round(($PassCount / $ProtectedFiles.Count) * 100, 2))%" -ForegroundColor Cyan
    
    return $Results
}

# Script 2: Input Validation Test
function Test-InputValidation {
    param(
        [string]$BaseUrl = "https://test.kineticev.in"
    )
    
    Write-Host "🔍 Testing Input Validation..." -ForegroundColor Cyan
    
    # SQL Injection payloads
    $SQLInjectionPayloads = @(
        "' OR 1=1--",
        "'; DROP TABLE users;--",
        "' UNION SELECT password FROM users--",
        "admin'--",
        "' OR 'a'='a"
    )
    
    # XSS payloads
    $XSSPayloads = @(
        "<script>alert('xss')</script>",
        "<img src=x onerror=alert('xss')>",
        "javascript:alert('xss')",
        "<svg onload=alert('xss')>"
    )
    
    $Results = @()
    
    # Test Contact Form
    foreach ($Payload in $SQLInjectionPayloads) {
        $Body = @{
            full_name = $Payload
            email = "test@test.com"
            phone = "1234567890"
            help_type = "General"
            message = "Security test"
        }
        
        try {
            $Response = Invoke-RestMethod -Uri "$BaseUrl/api/submit-contact" -Method Post -Body $Body -ErrorAction SilentlyContinue
            $Result = [PSCustomObject]@{
                Endpoint = "/api/submit-contact"
                Payload = $Payload
                Type = "SQL Injection"
                Response = $Response
                Vulnerable = ($Response -notmatch "error|invalid|blocked")
            }
            $Results += $Result
        } catch {
            Write-Host "✅ Contact form blocked SQL injection: $Payload" -ForegroundColor Green
        }
    }
    
    return $Results
}

# Script 3: Configuration Security Test
function Test-ConfigurationSecurity {
    param(
        [string]$BaseUrl = "https://test.kineticev.in"
    )
    
    Write-Host "⚙️ Testing Configuration Security..." -ForegroundColor Cyan
    
    $ConfigTests = @(
        "/phpinfo.php",
        "/.env",
        "/composer.json",
        "/composer.lock",
        "/.htaccess",
        "/logs/",
        "/php/config.php?debug=1",
        "/?debug=true",
        "/?XDEBUG_SESSION_START=1"
    )
    
    $Results = @()
    
    foreach ($Test in $ConfigTests) {
        $Url = "$BaseUrl$Test"
        
        try {
            $Response = Invoke-WebRequest -Uri $Url -Method Get -ErrorAction SilentlyContinue
            $StatusCode = $Response.StatusCode
            $ContentLength = $Response.Content.Length
        } catch {
            $StatusCode = $_.Exception.Response.StatusCode.Value__
            $ContentLength = 0
        }
        
        $Result = [PSCustomObject]@{
            Test = $Test
            StatusCode = $StatusCode
            ContentLength = $ContentLength
            Secure = ($StatusCode -eq 404 -or $StatusCode -eq 403)
            Status = if ($StatusCode -eq 404 -or $StatusCode -eq 403) { "✅ SECURE" } else { "❌ EXPOSED" }
        }
        
        $Results += $Result
        
        if ($StatusCode -eq 404 -or $StatusCode -eq 403) {
            Write-Host "✅ $Test - Secured ($StatusCode)" -ForegroundColor Green
        } else {
            Write-Host "❌ $Test - Exposed ($StatusCode)" -ForegroundColor Red
        }
    }
    
    return $Results
}

# Script 4: API Security Test
function Test-APISecurity {
    param(
        [string]$BaseUrl = "https://test.kineticev.in"
    )
    
    Write-Host "🌐 Testing API Security..." -ForegroundColor Cyan
    
    $Results = @()
    
    # Test rate limiting
    Write-Host "Testing rate limiting..." -ForegroundColor Yellow
    for ($i = 1; $i -le 20; $i++) {
        $Body = @{
            full_name = "RateTest$i"
            email = "ratetest$i@test.com"
            phone = "123456789$i"
            help_type = "General"
            message = "Rate limit test $i"
        }
        
        try {
            $StartTime = Get-Date
            $Response = Invoke-RestMethod -Uri "$BaseUrl/api/submit-contact" -Method Post -Body $Body -ErrorAction SilentlyContinue
            $EndTime = Get-Date
            $Duration = ($EndTime - $StartTime).TotalMilliseconds
            
            $Result = [PSCustomObject]@{
                Test = "Rate Limiting"
                Request = $i
                Duration = $Duration
                Success = $true
                Response = $Response
            }
        } catch {
            $Result = [PSCustomObject]@{
                Test = "Rate Limiting"
                Request = $i
                Duration = 0
                Success = $false
                Error = $_.Exception.Message
            }
            
            if ($_.Exception.Message -match "rate|limit|too many") {
                Write-Host "✅ Rate limiting active at request $i" -ForegroundColor Green
                break
            }
        }
        
        $Results += $Result
        Start-Sleep -Milliseconds 100
    }
    
    return $Results
}

# Script 5: Complete Security Test Suite
function Start-SecurityTestSuite {
    param(
        [string]$BaseUrl = "https://test.kineticev.in",
        [string]$ReportPath = ".\SecurityTestReport_$(Get-Date -Format 'yyyyMMdd_HHmmss').txt"
    )
    
    Write-Host "🚀 Starting Complete Security Test Suite for K2 Application" -ForegroundColor Magenta
    Write-Host "Target: $BaseUrl" -ForegroundColor Cyan
    Write-Host "Report will be saved to: $ReportPath" -ForegroundColor Cyan
    
    $StartTime = Get-Date
    $AllResults = @()
    
    # Run all tests
    $DirectAccessResults = Test-DirectAccessProtection -BaseUrl $BaseUrl
    $InputValidationResults = Test-InputValidation -BaseUrl $BaseUrl
    $ConfigSecurityResults = Test-ConfigurationSecurity -BaseUrl $BaseUrl
    $APISecurityResults = Test-APISecurity -BaseUrl $BaseUrl
    
    $EndTime = Get-Date
    $TotalDuration = ($EndTime - $StartTime).TotalMinutes
    
    # Generate report
    $Report = @"
# K2 Security Testing Report

**Generated**: $(Get-Date)
**Target**: $BaseUrl
**Duration**: $([math]::Round($TotalDuration, 2)) minutes

## Executive Summary

### Direct Access Protection
- Total Files Tested: $($DirectAccessResults.Count)
- Protected Files: $($DirectAccessResults | Where-Object { $_.Protected }).Count
- Unprotected Files: $($DirectAccessResults | Where-Object { -not $_.Protected }).Count
- Success Rate: $([math]::Round((($DirectAccessResults | Where-Object { $_.Protected }).Count / $DirectAccessResults.Count) * 100, 2))%

### Configuration Security
- Total Tests: $($ConfigSecurityResults.Count)
- Secure Endpoints: $($ConfigSecurityResults | Where-Object { $_.Secure }).Count
- Exposed Endpoints: $($ConfigSecurityResults | Where-Object { -not $_.Secure }).Count

## Detailed Results

### Direct Access Protection Results
$($DirectAccessResults | Format-Table -AutoSize | Out-String)

### Configuration Security Results
$($ConfigSecurityResults | Format-Table -AutoSize | Out-String)

### API Security Results
$($APISecurityResults | Format-Table -AutoSize | Out-String)

## Recommendations

1. **High Priority**: Fix any unprotected files found in Direct Access Protection test
2. **Medium Priority**: Address any exposed configuration endpoints
3. **Low Priority**: Review API rate limiting implementation

---
**Report End**
"@
    
    $Report | Out-File -FilePath $ReportPath -Encoding UTF8
    
    Write-Host "`n✅ Security test suite completed!" -ForegroundColor Green
    Write-Host "📊 Report saved to: $ReportPath" -ForegroundColor Cyan
    Write-Host "⏱️ Total duration: $([math]::Round($TotalDuration, 2)) minutes" -ForegroundColor Yellow
    
    return @{
        DirectAccess = $DirectAccessResults
        Configuration = $ConfigSecurityResults
        APISecurity = $APISecurityResults
        ReportPath = $ReportPath
    }
}

# Export functions for use
Export-ModuleMember -Function Test-DirectAccessProtection, Test-InputValidation, Test-ConfigurationSecurity, Test-APISecurity, Start-SecurityTestSuite
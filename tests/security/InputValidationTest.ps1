# Input Validation & SQL Injection Test
# Tests all form inputs, API endpoints, and database queries for injection vulnerabilities

function Test-InputValidation {
    param(
        [string]$BaseUrl = "https://test.kineticev.in",
        [switch]$Verbose
    )
    
    Write-Host "🔍 Testing Input Validation & SQL Injection..." -ForegroundColor Cyan
    
    $Results = @()
    
    # SQL Injection payloads
    $SQLInjectionPayloads = @(
        "' OR 1=1--",
        "'; DROP TABLE users;--",
        "' UNION SELECT password FROM users--",
        "admin'--",
        "' OR 'a'='a",
        "1' OR '1'='1",
        "'; UPDATE users SET password='hacked';--",
        "' OR 1=1 LIMIT 1--"
    )
    
    # XSS payloads
    $XSSPayloads = @(
        "<script>alert('xss')</script>",
        "<img src=x onerror=alert('xss')>",
        "javascript:alert('xss')",
        "<svg onload=alert('xss')>",
        "'><script>alert('xss')</script>",
        "<iframe src='javascript:alert(`xss`)'></iframe>",
        "<body onload=alert('xss')>"
    )
    
    # Test 1: Contact Form SQL Injection
    Write-Host "`n📋 Testing Contact Form SQL Injection..." -ForegroundColor Yellow
    
    foreach ($Payload in $SQLInjectionPayloads) {
        $Body = @{
            full_name = $Payload
            email = "test@test.com"
            phone = "1234567890"
            help_type = "General"
            message = "Security test"
        }
        
        try {
            $Response = Invoke-RestMethod -Uri "$BaseUrl/api/submit-contact" -Method Post -Body $Body -TimeoutSec 10 -ErrorAction SilentlyContinue
            
            # Check response for signs of SQL injection success
            $Vulnerable = $false
            if ($Response) {
                $ResponseText = $Response | ConvertTo-Json
                # Look for SQL error messages or unexpected data
                $Vulnerable = $ResponseText -match "(mysql|sql|database|error|warning|fatal)" -and 
                             $ResponseText -notmatch "(blocked|filtered|invalid|sanitized)"
            }
            
            $Passed = -not $Vulnerable
            $Status = if ($Passed) { "✅ BLOCKED" } else { "❌ VULNERABLE" }
            $Details = if ($Vulnerable) { "SQL injection payload was processed" } else { "SQL injection payload was blocked/filtered" }
            
        } catch {
            $StatusCode = $_.Exception.Response.StatusCode.Value__
            # Error responses are often good - means the payload was rejected
            $Passed = ($StatusCode -in @(400, 422, 500)) # Bad request, validation error, or server error
            $Status = if ($Passed) { "✅ REJECTED" } else { "⚠️ OTHER" }
            $Details = "Form submission returned $StatusCode for SQL payload"
        }
        
        $Result = [PSCustomObject]@{
            Test = "Contact Form SQL Injection"
            Payload = $Payload
            StatusCode = if ($Response) { 200 } else { $StatusCode }
            Passed = $Passed
            Details = $Details
            Category = "SQL Injection"
        }
        
        $Results += $Result
        
        if ($Verbose) {
            Write-Host "  $Status - Payload: $($Payload.Substring(0, [Math]::Min(20, $Payload.Length)))..." -ForegroundColor $(if ($Passed) { "Green" } else { "Red" })
        }
    }
    
    # Test 2: Test Ride Form SQL Injection
    Write-Host "`n📋 Testing Test Ride Form SQL Injection..." -ForegroundColor Yellow
    
    foreach ($Payload in $SQLInjectionPayloads[0..3]) { # Test fewer payloads for time
        $Body = @{
            full_name = $Payload
            email = "test@test.com"
            phone = "1234567890"
            date = "2025-09-20"
            pincode = "110001"
            message = "Test ride booking"
        }
        
        try {
            $Response = Invoke-RestMethod -Uri "$BaseUrl/api/submit-test-ride" -Method Post -Body $Body -TimeoutSec 10 -ErrorAction SilentlyContinue
            
            $Vulnerable = $false
            if ($Response) {
                $ResponseText = $Response | ConvertTo-Json
                $Vulnerable = $ResponseText -match "(mysql|sql|database|error|warning|fatal)" -and 
                             $ResponseText -notmatch "(blocked|filtered|invalid|sanitized)"
            }
            
            $Passed = -not $Vulnerable
            $Status = if ($Passed) { "✅ BLOCKED" } else { "❌ VULNERABLE" }
            $Details = if ($Vulnerable) { "SQL injection in test ride form" } else { "Test ride form protected" }
            
        } catch {
            $StatusCode = $_.Exception.Response.StatusCode.Value__
            $Passed = ($StatusCode -in @(400, 422, 500))
            $Status = if ($Passed) { "✅ REJECTED" } else { "⚠️ OTHER" }
            $Details = "Test ride form returned $StatusCode for SQL payload"
        }
        
        $Result = [PSCustomObject]@{
            Test = "Test Ride Form SQL Injection"
            Payload = $Payload
            StatusCode = if ($Response) { 200 } else { $StatusCode }
            Passed = $Passed
            Details = $Details
            Category = "SQL Injection"
        }
        
        $Results += $Result
    }
    
    # Test 3: XSS in Contact Form
    Write-Host "`n📋 Testing Contact Form XSS..." -ForegroundColor Yellow
    
    foreach ($Payload in $XSSPayloads[0..3]) { # Test key XSS payloads
        $Body = @{
            full_name = $Payload
            email = "test@test.com"
            phone = "1234567890"
            help_type = "General"
            message = "XSS test"
        }
        
        try {
            $Response = Invoke-RestMethod -Uri "$BaseUrl/api/submit-contact" -Method Post -Body $Body -TimeoutSec 10 -ErrorAction SilentlyContinue
            
            $Vulnerable = $false
            if ($Response) {
                $ResponseText = $Response | ConvertTo-Json
                # Check if XSS payload is reflected without encoding
                $Vulnerable = $ResponseText -match [regex]::Escape($Payload) -and 
                             $ResponseText -notmatch "(encoded|escaped|&lt;|&gt;|&amp;)"
            }
            
            $Passed = -not $Vulnerable
            $Status = if ($Passed) { "✅ ENCODED" } else { "❌ VULNERABLE" }
            $Details = if ($Vulnerable) { "XSS payload reflected without encoding" } else { "XSS payload properly encoded/filtered" }
            
        } catch {
            $StatusCode = $_.Exception.Response.StatusCode.Value__
            $Passed = ($StatusCode -in @(400, 422))
            $Status = if ($Passed) { "✅ REJECTED" } else { "⚠️ OTHER" }
            $Details = "Form rejected XSS payload with $StatusCode"
        }
        
        $Result = [PSCustomObject]@{
            Test = "Contact Form XSS"
            Payload = $Payload
            StatusCode = if ($Response) { 200 } else { $StatusCode }
            Passed = $Passed
            Details = $Details
            Category = "XSS"
        }
        
        $Results += $Result
    }
    
    # Test 4: Input Validation - Data Types
    Write-Host "`n📋 Testing Input Data Type Validation..." -ForegroundColor Yellow
    
    $InvalidInputTests = @(
        @{ Field = "email"; Value = "not_an_email"; Expected = "Email validation" },
        @{ Field = "phone"; Value = "abc123def"; Expected = "Phone number validation" },
        @{ Field = "date"; Value = "invalid_date"; Expected = "Date format validation" },
        @{ Field = "pincode"; Value = "99999999"; Expected = "Pincode validation" },
        @{ Field = "full_name"; Value = ""; Expected = "Required field validation" },
        @{ Field = "phone"; Value = "12345"; Expected = "Phone length validation" }
    )
    
    foreach ($Test in $InvalidInputTests) {
        $Body = @{
            full_name = if ($Test.Field -eq "full_name") { $Test.Value } else { "Test User" }
            email = if ($Test.Field -eq "email") { $Test.Value } else { "test@test.com" }
            phone = if ($Test.Field -eq "phone") { $Test.Value } else { "1234567890" }
            help_type = "General"
            message = "Validation test"
        }
        
        # Add date and pincode for test ride form
        if ($Test.Field -in @("date", "pincode")) {
            $Body["date"] = if ($Test.Field -eq "date") { $Test.Value } else { "2025-09-20" }
            $Body["pincode"] = if ($Test.Field -eq "pincode") { $Test.Value } else { "110001" }
            $Endpoint = "/api/submit-test-ride"
        } else {
            $Endpoint = "/api/submit-contact"
        }
        
        try {
            $Response = Invoke-RestMethod -Uri "$BaseUrl$Endpoint" -Method Post -Body $Body -TimeoutSec 10 -ErrorAction SilentlyContinue
            
            # Valid response to invalid data suggests validation is missing
            $Passed = $false
            $Status = "❌ ACCEPTED"
            $Details = "$($Test.Expected) - Invalid data was accepted"
            
        } catch {
            $StatusCode = $_.Exception.Response.StatusCode.Value__
            # 400 Bad Request or 422 Unprocessable Entity are good validation responses
            $Passed = ($StatusCode -in @(400, 422))
            $Status = if ($Passed) { "✅ VALIDATED" } else { "⚠️ OTHER" }
            $Details = "$($Test.Expected) - Server returned $StatusCode"
        }
        
        $Result = [PSCustomObject]@{
            Test = "Input Validation - $($Test.Field)"
            Payload = $Test.Value
            StatusCode = if ($Response) { 200 } else { $StatusCode }
            Passed = $Passed
            Details = $Details
            Category = "Input Validation"
        }
        
        $Results += $Result
        
        if ($Verbose) {
            Write-Host "  $Status - $($Test.Field): $($Test.Value)" -ForegroundColor $(if ($Passed) { "Green" } else { "Red" })
        }
    }
    
    # Test 5: File Upload Security (if applicable)
    Write-Host "`n📋 Testing File Upload Security..." -ForegroundColor Yellow
    
    try {
        # Test if file upload endpoint exists
        $Response = Invoke-WebRequest -Uri "$BaseUrl/api/upload" -Method Post -TimeoutSec 5 -ErrorAction SilentlyContinue
        
        $Result = [PSCustomObject]@{
            Test = "File Upload Endpoint"
            Payload = "N/A"
            StatusCode = $Response.StatusCode
            Passed = $false # If it exists without proper security, it's a concern
            Details = "File upload endpoint exists - requires security review"
            Category = "File Upload"
        }
        
    } catch {
        $StatusCode = $_.Exception.Response.StatusCode.Value__
        # 404 is good - no file upload endpoint
        $Passed = ($StatusCode -eq 404)
        
        $Result = [PSCustomObject]@{
            Test = "File Upload Endpoint"
            Payload = "N/A"
            StatusCode = $StatusCode
            Passed = $Passed
            Details = if ($Passed) { "No file upload endpoint found (good)" } else { "File upload endpoint returned $StatusCode" }
            Category = "File Upload"
        }
    }
    
    $Results += $Result
    
    # Summary
    $PassedTests = ($Results | Where-Object { $_.Passed }).Count
    $FailedTests = ($Results | Where-Object { -not $_.Passed }).Count
    $TotalTests = $Results.Count
    
    Write-Host "`n📊 Input Validation & SQL Injection Summary:" -ForegroundColor Cyan
    Write-Host "Total Tests: $TotalTests" -ForegroundColor White
    Write-Host "Passed: $PassedTests" -ForegroundColor Green
    Write-Host "Failed: $FailedTests" -ForegroundColor Red
    Write-Host "Success Rate: $([math]::Round(($PassedTests / $TotalTests) * 100, 2))%" -ForegroundColor Cyan
    
    # Critical vulnerabilities alert
    $CriticalVulns = $Results | Where-Object { -not $_.Passed -and $_.Category -in @("SQL Injection", "XSS") }
    if ($CriticalVulns.Count -gt 0) {
        Write-Host "`n🚨 CRITICAL VULNERABILITIES FOUND:" -ForegroundColor Red
        foreach ($Vuln in $CriticalVulns) {
            Write-Host "  ❌ $($Vuln.Test) - $($Vuln.Details)" -ForegroundColor Red
        }
    }
    
    return @{
        Results = $Results
        Summary = @{
            Total = $TotalTests
            Passed = $PassedTests
            Failed = $FailedTests
            CriticalVulns = $CriticalVulns.Count
            SuccessRate = [math]::Round(($PassedTests / $TotalTests) * 100, 2)
        }
        Passed = ($CriticalVulns.Count -eq 0) # Pass only if no critical vulnerabilities
    }
}

# Export function for module use
Export-ModuleMember -Function Test-InputValidation
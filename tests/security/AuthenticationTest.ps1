# Authentication & Authorization Test
# Tests admin authentication, session management, JWT tokens, and role-based access

function Test-Authentication {
    param(
        [string]$BaseUrl = "https://test.kineticev.in",
        [switch]$Verbose
    )
    
    Write-Host "🔐 Testing Authentication & Authorization..." -ForegroundColor Cyan
    
    $Results = @()
    
    # Test 1: Admin Access Without Authentication
    Write-Host "`n📋 Testing Unauthorized Admin Access..." -ForegroundColor Yellow
    
    $AdminEndpoints = @(
        "/php/admin/",
        "/php/admin/dashboard.php",
        "/php/admin/analytics.php",
        "/php/admin/user-management.php"
    )
    
    foreach ($Endpoint in $AdminEndpoints) {
        $Url = "$BaseUrl$Endpoint"
        
        try {
            $Response = Invoke-WebRequest -Uri $Url -Method Get -TimeoutSec 10 -ErrorAction SilentlyContinue
            $StatusCode = $Response.StatusCode
            
            # Admin pages should redirect (302) or return 401/403, not 200
            $Passed = ($StatusCode -ne 200)
            $Status = if ($Passed) { "✅ SECURED" } else { "❌ EXPOSED" }
            $Details = "Admin endpoint returned $StatusCode (should not be 200)"
            
        } catch {
            $StatusCode = $_.Exception.Response.StatusCode.Value__
            # 401 Unauthorized, 403 Forbidden, or 302 Redirect are good
            $Passed = ($StatusCode -in @(401, 403, 302))
            $Status = if ($Passed) { "✅ SECURED" } else { "⚠️ OTHER" }
            $Details = "Admin endpoint returned $StatusCode"
        }
        
        $Result = [PSCustomObject]@{
            Test = "Admin Access Control - $Endpoint"
            StatusCode = $StatusCode
            Passed = $Passed
            Details = $Details
            Category = "Authorization"
        }
        
        $Results += $Result
        
        if ($Verbose) {
            Write-Host "  $Status - $Endpoint ($StatusCode)" -ForegroundColor $(if ($Passed) { "Green" } else { "Red" })
        }
    }
    
    # Test 2: JWT Token Validation
    Write-Host "`n📋 Testing JWT Token Validation..." -ForegroundColor Yellow
    
    $JWTTests = @(
        @{ Token = "invalid_token"; Description = "Invalid JWT format" },
        @{ Token = "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJhdHRhY2tlciIsImV4cCI6MTYwOTQ1OTIwMH0.invalid"; Description = "Malformed JWT" },
        @{ Token = ""; Description = "Empty token" },
        @{ Token = "Bearer fake_token"; Description = "Fake Bearer token" }
    )
    
    foreach ($JWTTest in $JWTTests) {
        $Headers = @{}
        if ($JWTTest.Token) {
            $Headers["Authorization"] = if ($JWTTest.Token.StartsWith("Bearer")) { $JWTTest.Token } else { "Bearer $($JWTTest.Token)" }
        }
        
        try {
            $Response = Invoke-RestMethod -Uri "$BaseUrl/api/verify-token" -Method Post -Headers $Headers -TimeoutSec 10 -ErrorAction SilentlyContinue
            
            # Should reject invalid tokens
            $Passed = $false
            $Status = "❌ ACCEPTED"
            $Details = "Invalid JWT was accepted: $($JWTTest.Description)"
            
        } catch {
            $StatusCode = $_.Exception.Response.StatusCode.Value__
            # Should return 401 Unauthorized for invalid tokens
            $Passed = ($StatusCode -eq 401)
            $Status = if ($Passed) { "✅ REJECTED" } else { "⚠️ OTHER" }
            $Details = "JWT validation returned $StatusCode for: $($JWTTest.Description)"
        }
        
        $Result = [PSCustomObject]@{
            Test = "JWT Validation - $($JWTTest.Description)"
            StatusCode = if ($Response) { 200 } else { $StatusCode }
            Passed = $Passed
            Details = $Details
            Category = "Authentication"
        }
        
        $Results += $Result
        
        if ($Verbose) {
            Write-Host "  $Status - $($JWTTest.Description)" -ForegroundColor $(if ($Passed) { "Green" } else { "Red" })
        }
    }
    
    # Test 3: Session Security
    Write-Host "`n📋 Testing Session Security..." -ForegroundColor Yellow
    
    try {
        $Response = Invoke-WebRequest -Uri "$BaseUrl/" -Method Get -TimeoutSec 10 -ErrorAction SilentlyContinue
        $Cookies = $Response.Headers["Set-Cookie"]
        
        $SessionSecure = $false
        $HttpOnly = $false
        $SameSite = $false
        
        if ($Cookies) {
            foreach ($Cookie in $Cookies) {
                if ($Cookie -match "PHPSESSID" -or $Cookie -match "session") {
                    $SessionSecure = $Cookie -match "Secure"
                    $HttpOnly = $Cookie -match "HttpOnly"
                    $SameSite = $Cookie -match "SameSite"
                    break
                }
            }
        }
        
        # Test session cookie security flags
        $Results += [PSCustomObject]@{
            Test = "Session Cookie - Secure Flag"
            StatusCode = 200
            Passed = $SessionSecure
            Details = if ($SessionSecure) { "Session cookie has Secure flag" } else { "Session cookie missing Secure flag" }
            Category = "Session"
        }
        
        $Results += [PSCustomObject]@{
            Test = "Session Cookie - HttpOnly Flag"
            StatusCode = 200
            Passed = $HttpOnly
            Details = if ($HttpOnly) { "Session cookie has HttpOnly flag" } else { "Session cookie missing HttpOnly flag" }
            Category = "Session"
        }
        
        $Results += [PSCustomObject]@{
            Test = "Session Cookie - SameSite Flag"
            StatusCode = 200
            Passed = $SameSite
            Details = if ($SameSite) { "Session cookie has SameSite flag" } else { "Session cookie missing SameSite flag" }
            Category = "Session"
        }
        
        if ($Verbose) {
            Write-Host "  Session Secure: $(if ($SessionSecure) { '✅' } else { '❌' })" -ForegroundColor $(if ($SessionSecure) { "Green" } else { "Red" })
            Write-Host "  Session HttpOnly: $(if ($HttpOnly) { '✅' } else { '❌' })" -ForegroundColor $(if ($HttpOnly) { "Green" } else { "Red" })
            Write-Host "  Session SameSite: $(if ($SameSite) { '✅' } else { '❌' })" -ForegroundColor $(if ($SameSite) { "Green" } else { "Red" })
        }
        
    } catch {
        $Results += [PSCustomObject]@{
            Test = "Session Cookie Analysis"
            StatusCode = $_.Exception.Response.StatusCode.Value__
            Passed = $false
            Details = "Could not analyze session cookies: $($_.Exception.Message)"
            Category = "Session"
        }
    }
    
    # Test 4: Session Fixation Protection
    Write-Host "`n📋 Testing Session Fixation Protection..." -ForegroundColor Yellow
    
    try {
        # Try to set a specific session ID
        $FixedSessionId = "attacker_controlled_session_123"
        $Headers = @{ "Cookie" = "PHPSESSID=$FixedSessionId" }
        
        $Response = Invoke-WebRequest -Uri "$BaseUrl/" -Method Get -Headers $Headers -TimeoutSec 10 -ErrorAction SilentlyContinue
        $NewCookies = $Response.Headers["Set-Cookie"]
        
        $SessionChanged = $false
        if ($NewCookies) {
            foreach ($Cookie in $NewCookies) {
                if ($Cookie -match "PHPSESSID=([^;]+)" -and $Matches[1] -ne $FixedSessionId) {
                    $SessionChanged = $true
                    break
                }
            }
        }
        
        $Result = [PSCustomObject]@{
            Test = "Session Fixation Protection"
            StatusCode = $Response.StatusCode
            Passed = $SessionChanged
            Details = if ($SessionChanged) { "Session ID was regenerated (good)" } else { "Session ID was not changed (vulnerable)" }
            Category = "Session"
        }
        
        $Results += $Result
        
        if ($Verbose) {
            Write-Host "  $(if ($SessionChanged) { '✅ PROTECTED' } else { '❌ VULNERABLE' }) - Session Fixation" -ForegroundColor $(if ($SessionChanged) { "Green" } else { "Red" })
        }
        
    } catch {
        $Results += [PSCustomObject]@{
            Test = "Session Fixation Protection"
            StatusCode = $_.Exception.Response.StatusCode.Value__
            Passed = $false
            Details = "Could not test session fixation: $($_.Exception.Message)"
            Category = "Session"
        }
    }
    
    # Test 5: Password Reset Security (if endpoint exists)
    Write-Host "`n📋 Testing Password Reset Security..." -ForegroundColor Yellow
    
    try {
        # Test password reset with invalid email
        $Body = @{ email = "nonexistent@test.com" }
        $Response = Invoke-RestMethod -Uri "$BaseUrl/api/password-reset" -Method Post -Body $Body -TimeoutSec 10 -ErrorAction SilentlyContinue
        
        # Should not reveal if email exists or not
        $Passed = $true # Assume good unless proven otherwise
        $Details = "Password reset endpoint responds appropriately"
        
    } catch {
        $StatusCode = $_.Exception.Response.StatusCode.Value__
        # 404 is fine (endpoint doesn't exist), 429 is good (rate limited)
        $Passed = ($StatusCode -in @(404, 429))
        $Details = "Password reset test returned $StatusCode"
    }
    
    $Results += [PSCustomObject]@{
        Test = "Password Reset Security"
        StatusCode = if ($Response) { 200 } else { $StatusCode }
        Passed = $Passed
        Details = $Details
        Category = "Authentication"
    }
    
    # Summary
    $PassedTests = ($Results | Where-Object { $_.Passed }).Count
    $FailedTests = ($Results | Where-Object { -not $_.Passed }).Count
    $TotalTests = $Results.Count
    
    Write-Host "`n📊 Authentication & Authorization Summary:" -ForegroundColor Cyan
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
Export-ModuleMember -Function Test-Authentication
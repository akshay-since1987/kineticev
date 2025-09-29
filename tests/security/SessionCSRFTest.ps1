# Session & CSRF Protection Test
# Tests session management, CSRF tokens, and cross-site request forgery protections

function Test-SessionCSRF {
    param(
        [string]$BaseUrl = "https://test.kineticev.in",
        [switch]$Verbose
    )
    
    Write-Host "🍪 Testing Session & CSRF Protection..." -ForegroundColor Cyan
    
    $Results = @()
    
    # Test 1: CSRF Protection
    Write-Host "`n📋 Testing CSRF Protection..." -ForegroundColor Yellow
    
    try {
        # Attempt form submission without CSRF token
        $Body = @{
            full_name = "CSRF Test"
            email = "csrf@test.com"
            phone = "1234567890"
            help_type = "General"
            message = "CSRF protection test"
        }
        
        $Headers = @{ "Referer" = "https://malicious-site.com" }
        
        $Response = Invoke-RestMethod -Uri "$BaseUrl/api/submit-contact" -Method Post -Body $Body -Headers $Headers -TimeoutSec 5 -ErrorAction SilentlyContinue
        
        $Passed = $false
        $Details = "CSRF attack succeeded - no protection"
        
    } catch {
        $StatusCode = $_.Exception.Response.StatusCode.Value__
        $Passed = ($StatusCode -in @(403, 419, 422))
        $Details = if ($Passed) { "CSRF protection active (returned $StatusCode)" } else { "Unexpected CSRF response $StatusCode" }
    }
    
    $Results += [PSCustomObject]@{
        Test = "CSRF Protection"
        StatusCode = if ($Response) { 200 } else { $StatusCode }
        Passed = $Passed
        Details = $Details
        Category = "CSRF"
    }
    
    # Test 2: Session Cookie Security
    Write-Host "`n📋 Testing Session Cookie Security..." -ForegroundColor Yellow
    
    try {
        $Response = Invoke-WebRequest -Uri "$BaseUrl/" -Method Get -TimeoutSec 5 -ErrorAction SilentlyContinue
        $Cookies = $Response.Headers["Set-Cookie"]
        
        $SecureFlag = $false
        $HttpOnlyFlag = $false
        $SameSiteFlag = $false
        
        if ($Cookies) {
            foreach ($Cookie in $Cookies) {
                if ($Cookie -match "PHPSESSID" -or $Cookie -match "session") {
                    $SecureFlag = $Cookie -match "Secure"
                    $HttpOnlyFlag = $Cookie -match "HttpOnly"
                    $SameSiteFlag = $Cookie -match "SameSite"
                    break
                }
            }
        }
        
        $Results += [PSCustomObject]@{
            Test = "Session Cookie Secure Flag"
            StatusCode = 200
            Passed = $SecureFlag
            Details = if ($SecureFlag) { "Session cookie has Secure flag" } else { "Session cookie missing Secure flag" }
            Category = "Session"
        }
        
        $Results += [PSCustomObject]@{
            Test = "Session Cookie HttpOnly Flag"
            StatusCode = 200
            Passed = $HttpOnlyFlag
            Details = if ($HttpOnlyFlag) { "Session cookie has HttpOnly flag" } else { "Session cookie missing HttpOnly flag" }
            Category = "Session"
        }
        
    } catch {
        $Results += [PSCustomObject]@{
            Test = "Session Cookie Analysis"
            StatusCode = $_.Exception.Response.StatusCode.Value__
            Passed = $false
            Details = "Could not analyze session cookies"
            Category = "Session"
        }
    }
    
    return @{
        Results = $Results
        Passed = ($Results | Where-Object { -not $_.Passed }).Count -eq 0
    }
}

Export-ModuleMember -Function Test-SessionCSRF
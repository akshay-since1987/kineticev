# Error Handling & Information Disclosure Test
# Tests error pages, debug information leakage, and sensitive data exposure

function Test-ErrorHandling {
    param(
        [string]$BaseUrl = "https://test.kineticev.in",
        [switch]$Verbose
    )
    
    Write-Host "🚨 Testing Error Handling & Information Disclosure..." -ForegroundColor Cyan
    
    $Results = @()
    
    # Test 1: Error Page Information Disclosure
    Write-Host "`n📋 Testing Error Page Information..." -ForegroundColor Yellow
    
    $ErrorTests = @(
        "/nonexistent-page.php",
        "/php/nonexistent-file.php", 
        "/api/nonexistent-endpoint",
        "/admin/nonexistent-admin.php"
    )
    
    foreach ($Test in $ErrorTests) {
        try {
            $Response = Invoke-WebRequest -Uri "$BaseUrl$Test" -Method Get -TimeoutSec 5 -ErrorAction SilentlyContinue
            $Content = $Response.Content
            
            # Should not reach here for non-existent files
            $Passed = $false
            $Details = "Non-existent file returned 200 status"
            
        } catch {
            $StatusCode = $_.Exception.Response.StatusCode.Value__
            $ErrorContent = $_.Exception.Response.Content
            
            if ($StatusCode -eq 404) {
                # Check if 404 page reveals sensitive information
                $InfoLeakage = $ErrorContent -match "(file|path|directory|server|apache|nginx|php)" -and
                              $ErrorContent -notmatch "(not found|error 404|page not found)"
                
                $Passed = -not $InfoLeakage
                $Details = if ($InfoLeakage) { "404 page reveals system information" } else { "404 page is clean" }
            } else {
                $Passed = $false
                $Details = "Unexpected error status $StatusCode"
            }
        }
        
        $Results += [PSCustomObject]@{
            Test = "Error Page Info Disclosure - $Test"
            StatusCode = if ($Response) { $Response.StatusCode } else { $StatusCode }
            Passed = $Passed
            Details = $Details
            Category = "Error Handling"
        }
    }
    
    # Test 2: PHP Error Disclosure
    Write-Host "`n📋 Testing PHP Error Disclosure..." -ForegroundColor Yellow
    
    $PHPErrorTests = @(
        "/api/submit-contact?malformed=json{invalid",
        "/php/config.php?cause_error=true",
        "/?trigger_error=1"
    )
    
    foreach ($Test in $PHPErrorTests) {
        try {
            $Response = Invoke-WebRequest -Uri "$BaseUrl$Test" -Method Get -TimeoutSec 5 -ErrorAction SilentlyContinue
            $Content = $Response.Content
            
            # Check for PHP error disclosure
            $PHPErrors = $Content -match "(fatal error|parse error|warning|notice)" -and
                        $Content -match "(line|file|stack trace)"
            
            $Passed = -not $PHPErrors
            $Details = if ($PHPErrors) { "PHP errors exposed to user" } else { "PHP errors properly handled" }
            
        } catch {
            $Passed = $true
            $Details = "Error request properly blocked"
        }
        
        $Results += [PSCustomObject]@{
            Test = "PHP Error Disclosure - $Test"
            StatusCode = if ($Response) { $Response.StatusCode } else { 404 }
            Passed = $Passed
            Details = $Details
            Category = "Error Handling"
        }
    }
    
    # Test 3: Debug Information Exposure
    Write-Host "`n📋 Testing Debug Information Exposure..." -ForegroundColor Yellow
    
    try {
        $Response = Invoke-WebRequest -Uri "$BaseUrl/phpinfo.php" -Method Get -TimeoutSec 5 -ErrorAction SilentlyContinue
        
        $Passed = $false
        $Details = "phpinfo.php is accessible (CRITICAL)"
        
    } catch {
        $StatusCode = $_.Exception.Response.StatusCode.Value__
        $Passed = ($StatusCode -in @(403, 404))
        $Details = if ($Passed) { "phpinfo.php properly blocked" } else { "Unexpected phpinfo response $StatusCode" }
    }
    
    $Results += [PSCustomObject]@{
        Test = "phpinfo.php Access"
        StatusCode = if ($Response) { $Response.StatusCode } else { $StatusCode }
        Passed = $Passed
        Details = $Details
        Category = "Debug Information"
    }
    
    return @{
        Results = $Results
        Passed = ($Results | Where-Object { -not $_.Passed }).Count -eq 0
    }
}

Export-ModuleMember -Function Test-ErrorHandling
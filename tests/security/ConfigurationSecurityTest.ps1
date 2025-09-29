# Configuration Security Test
# Verifies environment detection, database credentials, API keys, and production settings security

function Test-ConfigurationSecurity {
    param(
        [string]$BaseUrl = "https://test.kineticev.in",
        [switch]$Verbose
    )
    
    Write-Host "⚙️ Testing Configuration Security..." -ForegroundColor Cyan
    
    $Results = @()
    
    # Test 1: Configuration File Access
    Write-Host "`n📋 Testing Configuration File Access..." -ForegroundColor Yellow
    
    $ConfigFiles = @(
        "/php/config.php",
        "/php/prod-config.php",
        "/php/test-config.php",
        "/php/dev-config.php",
        "/.env",
        "/config.ini",
        "/composer.json",
        "/composer.lock"
    )
    
    foreach ($File in $ConfigFiles) {
        try {
            $Response = Invoke-WebRequest -Uri "$BaseUrl$File" -Method Get -TimeoutSec 5 -ErrorAction SilentlyContinue
            $StatusCode = $Response.StatusCode
            
            $Passed = $false
            $Details = "Configuration file is accessible (CRITICAL)"
            
        } catch {
            $StatusCode = $_.Exception.Response.StatusCode.Value__
            $Passed = ($StatusCode -in @(403, 404))
            $Details = if ($Passed) { "Configuration file properly protected" } else { "Unexpected status code $StatusCode" }
        }
        
        $Results += [PSCustomObject]@{
            Test = "Config File Access - $File"
            StatusCode = $StatusCode
            Passed = $Passed
            Details = $Details
            Category = "Configuration"
        }
    }
    
    # Test 2: Environment Detection
    Write-Host "`n📋 Testing Environment Detection..." -ForegroundColor Yellow
    
    $EnvTests = @(
        "/?debug=true",
        "/?env=production", 
        "/?XDEBUG_SESSION_START=1",
        "/php/config.php?show_config=1"
    )
    
    foreach ($Test in $EnvTests) {
        try {
            $Response = Invoke-WebRequest -Uri "$BaseUrl$Test" -Method Get -TimeoutSec 5 -ErrorAction SilentlyContinue
            $Content = $Response.Content
            
            # Check for debug information exposure
            $DebugExposed = $Content -match "(debug|error|warning|notice|fatal)" -and 
                           $Content -match "(file|line|stack|trace)"
            
            $Passed = -not $DebugExposed
            $Details = if ($DebugExposed) { "Debug information exposed" } else { "No debug information leaked" }
            
        } catch {
            $Passed = $true
            $Details = "Environment test properly blocked"
        }
        
        $Results += [PSCustomObject]@{
            Test = "Environment Detection - $Test"
            StatusCode = if ($Response) { $Response.StatusCode } else { 404 }
            Passed = $Passed
            Details = $Details
            Category = "Environment"
        }
    }
    
    # Test 3: Database Health Check Security
    Write-Host "`n📋 Testing Database Health Check..." -ForegroundColor Yellow
    
    try {
        $Response = Invoke-WebRequest -Uri "$BaseUrl/check-database-health.php" -Method Get -TimeoutSec 5 -ErrorAction SilentlyContinue
        $Content = $Response.Content
        
        # Should not expose sensitive database information
        $SensitiveExposed = $Content -match "(password|username|host|database|connection string)" -and
                           $Content -notmatch "(healthy|ok|status)"
        
        $Passed = -not $SensitiveExposed
        $Details = if ($SensitiveExposed) { "Database credentials exposed" } else { "Database health check secure" }
        
    } catch {
        $Passed = $true
        $Details = "Database health check properly protected"
    }
    
    $Results += [PSCustomObject]@{
        Test = "Database Health Check Security"
        StatusCode = if ($Response) { $Response.StatusCode } else { 404 }
        Passed = $Passed
        Details = $Details
        Category = "Database"
    }
    
    return @{
        Results = $Results
        Passed = ($Results | Where-Object { -not $_.Passed }).Count -eq 0
    }
}

Export-ModuleMember -Function Test-ConfigurationSecurity
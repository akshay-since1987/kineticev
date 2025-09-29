# File & Directory Security Test
# Tests file upload restrictions, directory traversal protection, and .htaccess configurations

function Test-FileDirectorySecurity {
    param(
        [string]$BaseUrl = "https://test.kineticev.in",
        [switch]$Verbose
    )
    
    Write-Host "📁 Testing File & Directory Security..." -ForegroundColor Cyan
    
    $Results = @()
    
    # Test 1: Directory Traversal
    Write-Host "`n📋 Testing Directory Traversal..." -ForegroundColor Yellow
    
    $TraversalTests = @(
        "/../../../etc/passwd",
        "/php/../config.php",
        "/logs/../php/config.php",
        "/%2e%2e/%2e%2e/%2e%2e/etc/passwd",
        "/....//....//....//etc/passwd"
    )
    
    foreach ($Test in $TraversalTests) {
        try {
            $Response = Invoke-WebRequest -Uri "$BaseUrl$Test" -Method Get -TimeoutSec 5 -ErrorAction SilentlyContinue
            $StatusCode = $Response.StatusCode
            
            $Passed = $false
            $Details = "Directory traversal successful (CRITICAL)"
            
        } catch {
            $StatusCode = $_.Exception.Response.StatusCode.Value__
            $Passed = ($StatusCode -in @(403, 404))
            $Details = if ($Passed) { "Directory traversal blocked" } else { "Unexpected status $StatusCode" }
        }
        
        $Results += [PSCustomObject]@{
            Test = "Directory Traversal - $Test"
            StatusCode = $StatusCode
            Passed = $Passed
            Details = $Details
            Category = "Directory Traversal"
        }
    }
    
    # Test 2: Sensitive Directory Access
    Write-Host "`n📋 Testing Sensitive Directory Access..." -ForegroundColor Yellow
    
    $SensitiveDirs = @(
        "/logs/",
        "/vendor/",
        "/.git/",
        "/backup/",
        "/config/"
    )
    
    foreach ($Dir in $SensitiveDirs) {
        try {
            $Response = Invoke-WebRequest -Uri "$BaseUrl$Dir" -Method Get -TimeoutSec 5 -ErrorAction SilentlyContinue
            $StatusCode = $Response.StatusCode
            
            # Should not allow directory listing
            $Passed = $false
            $Details = "Directory listing allowed (SECURITY RISK)"
            
        } catch {
            $StatusCode = $_.Exception.Response.StatusCode.Value__
            $Passed = ($StatusCode -in @(403, 404))
            $Details = if ($Passed) { "Directory access properly restricted" } else { "Unexpected directory response $StatusCode" }
        }
        
        $Results += [PSCustomObject]@{
            Test = "Directory Access - $Dir"
            StatusCode = $StatusCode
            Passed = $Passed
            Details = $Details
            Category = "Directory Security"
        }
    }
    
    # Test 3: .htaccess Protection
    Write-Host "`n📋 Testing .htaccess Protection..." -ForegroundColor Yellow
    
    try {
        $Response = Invoke-WebRequest -Uri "$BaseUrl/.htaccess" -Method Get -TimeoutSec 5 -ErrorAction SilentlyContinue
        $StatusCode = $Response.StatusCode
        
        $Passed = $false
        $Details = ".htaccess file is accessible (CRITICAL)"
        
    } catch {
        $StatusCode = $_.Exception.Response.StatusCode.Value__
        $Passed = ($StatusCode -in @(403, 404))
        $Details = if ($Passed) { ".htaccess properly protected" } else { "Unexpected .htaccess response $StatusCode" }
    }
    
    $Results += [PSCustomObject]@{
        Test = ".htaccess File Protection"
        StatusCode = $StatusCode
        Passed = $Passed
        Details = $Details
        Category = "File Security"
    }
    
    return @{
        Results = $Results
        Passed = ($Results | Where-Object { -not $_.Passed }).Count -eq 0
    }
}

Export-ModuleMember -Function Test-FileDirectorySecurity
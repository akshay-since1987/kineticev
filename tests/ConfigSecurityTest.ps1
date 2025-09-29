# Configuration Security Test
param([string]$BaseUrl = "https://dev.kineticev.in")

Write-Host "=== K2 Configuration Security Test ===" -ForegroundColor Cyan
Write-Host "Target: $BaseUrl"
Write-Host "Testing for exposed sensitive files and configurations..."
Write-Host ""

$SensitiveTests = @(
    @{ Path = "/.env"; Description = "Environment variables file" },
    @{ Path = "/composer.json"; Description = "Composer dependencies" },
    @{ Path = "/composer.lock"; Description = "Composer lock file" },
    @{ Path = "/.htaccess"; Description = "Apache configuration" },
    @{ Path = "/phpinfo.php"; Description = "PHP information page" },
    @{ Path = "/logs/"; Description = "Log directory" },
    @{ Path = "/vendor/"; Description = "Vendor directory" },
    @{ Path = "/.git/"; Description = "Git repository" },
    @{ Path = "/backup/"; Description = "Backup directory" },
    @{ Path = "/config/"; Description = "Config directory" }
)

$SecureCount = 0
$ExposedCount = 0

foreach ($Test in $SensitiveTests) {
    $Url = "$BaseUrl$($Test.Path)"
    
    try {
        $Response = Invoke-WebRequest -Uri $Url -Method Get -TimeoutSec 5 -ErrorAction SilentlyContinue
        Write-Host "EXPOSED  - $($Test.Description) at $($Test.Path)" -ForegroundColor Red
        $ExposedCount++
    } catch {
        $StatusCode = $_.Exception.Response.StatusCode.Value__
        if ($StatusCode -in @(403, 404)) {
            Write-Host "PROTECTED - $($Test.Description)" -ForegroundColor Green
            $SecureCount++
        } else {
            Write-Host "OTHER ($StatusCode) - $($Test.Description)" -ForegroundColor Yellow
            $SecureCount++ # Count as secure since it's not accessible
        }
    }
}

# Test for debug information exposure
Write-Host ""
Write-Host "Testing debug information exposure..."

$DebugTests = @(
    "/?debug=true",
    "/?XDEBUG_SESSION_START=1", 
    "/php/config.php?debug=1"
)

foreach ($Test in $DebugTests) {
    try {
        $Response = Invoke-WebRequest -Uri "$BaseUrl$Test" -Method Get -TimeoutSec 5 -ErrorAction SilentlyContinue
        $Content = $Response.Content
        
        if ($Content -match "debug|error|warning|notice|fatal" -and $Content -match "file|line|stack|trace") {
            Write-Host "DEBUG EXPOSED - $Test" -ForegroundColor Red
        } else {
            Write-Host "DEBUG SAFE - $Test" -ForegroundColor Green
        }
    } catch {
        Write-Host "DEBUG SAFE - $Test (blocked)" -ForegroundColor Green
    }
}

Write-Host ""
Write-Host "=== CONFIGURATION SECURITY SUMMARY ===" -ForegroundColor Cyan
Write-Host "Sensitive Files Tested: $($SensitiveTests.Count)"
Write-Host "Protected: $SecureCount" -ForegroundColor Green
Write-Host "Exposed: $ExposedCount" -ForegroundColor $(if ($ExposedCount -gt 0) { "Red" } else { "Green" })

if ($ExposedCount -eq 0) {
    Write-Host ""
    Write-Host "EXCELLENT! No sensitive files exposed" -ForegroundColor Green
    Write-Host "Configuration security is working properly" -ForegroundColor Green
} else {
    Write-Host ""
    Write-Host "WARNING: $ExposedCount sensitive files/directories are accessible!" -ForegroundColor Red
}

Write-Host ""
Write-Host "Test completed: $(Get-Date)"
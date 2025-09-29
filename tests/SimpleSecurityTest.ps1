# Simple Security Test
param([string]$BaseUrl = "https://test.kineticev.in")

Write-Host "K2 Security Test - Target: $BaseUrl"
Write-Host "Started: $(Get-Date)"

# Test critical protected files
$CriticalFiles = @(
    "/php/config.php",
    "/php/DatabaseHandler.php", 
    "/php/SalesforceService.php",
    "/php/EmailHandler.php"
)

Write-Host ""
Write-Host "Testing Direct Access Protection..."

$ProtectedCount = 0
$ExposedCount = 0

foreach ($File in $CriticalFiles) {
    try {
        $Response = Invoke-WebRequest -Uri "$BaseUrl$File" -Method Head -TimeoutSec 5 -ErrorAction SilentlyContinue
        Write-Host "EXPOSED - $File" -ForegroundColor Red
        $ExposedCount++
    } catch {
        $StatusCode = $_.Exception.Response.StatusCode.Value__
        if ($StatusCode -eq 404) {
            Write-Host "PROTECTED - $File" -ForegroundColor Green
            $ProtectedCount++
        } else {
            Write-Host "OTHER ($StatusCode) - $File" -ForegroundColor Yellow
        }
    }
}

Write-Host ""
Write-Host "SUMMARY:"
Write-Host "Protected: $ProtectedCount files" -ForegroundColor Green
Write-Host "Exposed: $ExposedCount files" -ForegroundColor $(if ($ExposedCount -gt 0) { "Red" } else { "Green" })

if ($ExposedCount -gt 0) {
    Write-Host ""
    Write-Host "CRITICAL: Files are directly accessible!" -ForegroundColor Red
} else {
    Write-Host ""
    Write-Host "Basic security checks passed!" -ForegroundColor Green
}

Write-Host ""
Write-Host "Completed: $(Get-Date)"
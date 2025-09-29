# Functionality Test - Ensure Protected Files Still Work When Included
param([string]$BaseUrl = "https://test.kineticev.in")

Write-Host "=== K2 Functionality Test ===" -ForegroundColor Cyan
Write-Host "Target: $BaseUrl"
Write-Host "Testing that protected files still work when included..."
Write-Host ""

$FunctionalityTests = @(
    @{ URL = "/"; Description = "Home page (includes components)" },
    @{ URL = "/contact-us.php"; Description = "Contact page (header/footer)" },
    @{ URL = "/book-now.php"; Description = "Booking page (all components)" },
    @{ URL = "/choose-variant.php"; Description = "Variant page (database calls)" },
    @{ URL = "/dealership-finder-pincode.php"; Description = "Dealership finder (maps)" },
    @{ URL = "/about-us.php"; Description = "About page" },
    @{ URL = "/delivery-policy.php"; Description = "Policy page" }
)

$PassedTests = 0
$FailedTests = 0

foreach ($Test in $FunctionalityTests) {
    $Url = "$BaseUrl$($Test.URL)"
    
    try {
        $Response = Invoke-WebRequest -Uri $Url -Method Get -TimeoutSec 15 -ErrorAction SilentlyContinue
        $StatusCode = $Response.StatusCode
        $ContentLength = $Response.Content.Length
        
        if ($StatusCode -eq 200 -and $ContentLength -gt 1000) {
            Write-Host "WORKING - $($Test.Description) ($ContentLength bytes)" -ForegroundColor Green
            $PassedTests++
        } else {
            Write-Host "ISSUE   - $($Test.Description) (Status: $StatusCode, Size: $ContentLength)" -ForegroundColor Yellow
            $FailedTests++
        }
        
    } catch {
        $StatusCode = $_.Exception.Response.StatusCode.Value__
        Write-Host "BROKEN  - $($Test.Description) (Error: $StatusCode)" -ForegroundColor Red
        $FailedTests++
    }
}

Write-Host ""
Write-Host "=== FUNCTIONALITY TEST SUMMARY ===" -ForegroundColor Cyan
Write-Host "Total Pages Tested: $($FunctionalityTests.Count)"
Write-Host "Working: $PassedTests" -ForegroundColor Green
Write-Host "Issues: $FailedTests" -ForegroundColor $(if ($FailedTests -gt 0) { "Red" } else { "Green" })
Write-Host "Success Rate: $([math]::Round(($PassedTests / $FunctionalityTests.Count) * 100, 2))%" -ForegroundColor Cyan

if ($FailedTests -eq 0) {
    Write-Host ""
    Write-Host "EXCELLENT! All pages are working normally" -ForegroundColor Green
    Write-Host "Protected files are functioning correctly via includes" -ForegroundColor Green
} else {
    Write-Host ""
    Write-Host "Some pages have issues - check individual results above" -ForegroundColor Yellow
}

Write-Host ""
Write-Host "Test completed: $(Get-Date)"
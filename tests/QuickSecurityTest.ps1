# Quick Security Test Runner
# Simple script to run essential security tests immediately

param(
    [string]$BaseUrl = "https://test.kineticev.in"
)

Write-Host "🔒 K2 Quick Security Test" -ForegroundColor Magenta
Write-Host "Target: $BaseUrl" -ForegroundColor Cyan
Write-Host "Started: $(Get-Date)" -ForegroundColor Gray

# Test 1: Direct Access Protection (Essential)
Write-Host "`n🛡️ Testing Direct Access Protection..." -ForegroundColor Yellow

$CriticalFiles = @(
    "/php/config.php",
    "/php/DatabaseHandler.php", 
    "/php/SalesforceService.php",
    "/php/EmailHandler.php"
)

$ProtectedCount = 0
$ExposedCount = 0

foreach ($File in $CriticalFiles) {
    try {
        $Response = Invoke-WebRequest -Uri "$BaseUrl$File" -Method Head -TimeoutSec 5 -ErrorAction SilentlyContinue
        Write-Host "❌ EXPOSED - $File" -ForegroundColor Red
        $ExposedCount++
    } catch {
        $StatusCode = $_.Exception.Response.StatusCode.Value__
        if ($StatusCode -eq 404) {
            Write-Host "✅ PROTECTED - $File" -ForegroundColor Green
            $ProtectedCount++
        } else {
            Write-Host "⚠️ OTHER ($StatusCode) - $File" -ForegroundColor Yellow
        }
    }
}

Write-Host "`nDirect Access Summary: $ProtectedCount protected, $ExposedCount exposed" -ForegroundColor Cyan

# Test 2: Configuration Security
Write-Host "`n⚙️ Testing Configuration Security..." -ForegroundColor Yellow

$ConfigTests = @("/composer.json", "/.env", "/.htaccess")
$ConfigSecure = 0

foreach ($Test in $ConfigTests) {
    try {
        $Response = Invoke-WebRequest -Uri "$BaseUrl$Test" -Method Get -TimeoutSec 5 -ErrorAction SilentlyContinue
        Write-Host "❌ ACCESSIBLE - $Test" -ForegroundColor Red
    } catch {
        $StatusCode = $_.Exception.Response.StatusCode.Value__
        if ($StatusCode -in @(403, 404)) {
            Write-Host "✅ PROTECTED - $Test" -ForegroundColor Green
            $ConfigSecure++
        }
    }
}

# Test 3: Basic Form Security
Write-Host "`n🔍 Testing Basic Form Security..." -ForegroundColor Yellow

try {
    $Response = Invoke-RestMethod -Uri "$BaseUrl/api/submit-contact" -Method Post -Body @{
        full_name = "' OR 1=1--"
        email = "test@test.com"
        phone = "1234567890"
        help_type = "General"
        message = "SQL injection test"
    } -TimeoutSec 5 -ErrorAction SilentlyContinue
    
    Write-Host "⚠️ Form accepted SQL injection payload" -ForegroundColor Yellow
} catch {
    $StatusCode = $_.Exception.Response.StatusCode.Value__
    if ($StatusCode -in @(400, 422)) {
        Write-Host "✅ Form properly validates input" -ForegroundColor Green
    } else {
        Write-Host "⚠️ Form returned status $StatusCode" -ForegroundColor Yellow
    }
}

# Test 4: Functionality Check
Write-Host "`n🔧 Testing Basic Functionality..." -ForegroundColor Yellow

$FunctionalPages = @("/", "/contact-us.php", "/book-now.php")
$WorkingPages = 0

foreach ($Page in $FunctionalPages) {
    try {
        $Response = Invoke-WebRequest -Uri "$BaseUrl$Page" -Method Get -TimeoutSec 5 -ErrorAction SilentlyContinue
        if ($Response.StatusCode -eq 200 -and $Response.Content.Length -gt 1000) {
            Write-Host "✅ WORKING - $Page" -ForegroundColor Green
            $WorkingPages++
        } else {
            Write-Host "⚠️ ISSUE - $Page (short content)" -ForegroundColor Yellow
        }
    } catch {
        Write-Host "❌ BROKEN - $Page" -ForegroundColor Red
    }
}

# Summary
Write-Host "`n📊 QUICK TEST SUMMARY" -ForegroundColor Magenta
Write-Host "Direct Access Protection: $ProtectedCount/$($CriticalFiles.Count) files protected" -ForegroundColor White
Write-Host "Configuration Security: $ConfigSecure/$($ConfigTests.Count) configs protected" -ForegroundColor White
Write-Host "Page Functionality: $WorkingPages/$($FunctionalPages.Count) pages working" -ForegroundColor White

if ($ExposedCount -gt 0) {
    Write-Host ""
    Write-Host "🚨 CRITICAL: $ExposedCount files are directly accessible!" -ForegroundColor Red
    Write-Host "Run full security test suite for detailed analysis." -ForegroundColor Red
} else {
    Write-Host ""
    Write-Host "✅ Basic security checks passed!" -ForegroundColor Green
    Write-Host "Consider running full test suite: .\RunSecurityTests.ps1" -ForegroundColor Cyan
}

Write-Host ""
Write-Host "Completed: $(Get-Date)" -ForegroundColor Gray
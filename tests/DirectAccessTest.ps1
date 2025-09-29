# Direct Access Test - All 35 Protected Files
param([string]$BaseUrl = "https://test.kineticev.in")

Write-Host "=== K2 Direct Access Protection Test ===" -ForegroundColor Cyan
Write-Host "Target: $BaseUrl"
Write-Host "Testing all 35 protected PHP files..."
Write-Host ""

# All 35 protected files from our tracker
$ProtectedFiles = @(
    # CRITICAL - Configuration
    "/php/config.php",
    "/php/prod-config.php",
    "/php/test-config.php", 
    "/php/dev-config.php",
    
    # HIGH PRIORITY - Core Classes
    "/php/DatabaseHandler.php",
    "/php/SalesforceService.php",
    "/php/EmailHandler.php",
    "/php/SmsService.php",
    "/php/Logger.php",
    "/php/DataValidator.php",
    "/php/JWTHandler.php",
    "/php/DatabaseUtils.php",
    "/php/DatabaseMigration.php",
    "/php/EmailNotificationsMigration.php",
    "/php/production-timezone-guard.php",
    "/php/PasswordResetHandler.php",
    
    # MEDIUM PRIORITY - Admin
    "/php/admin/AdminHandler.php",
    
    # LOWER PRIORITY - Components
    "/php/components/header.php",
    "/php/components/footer.php",
    "/php/components/head.php",
    "/php/components/scripts.php",
    "/php/components/layout.php",
    "/php/components/modals.php",
    "/php/components/admin-header.php",
    "/php/components/admin-footer.php",
    "/php/components/google-maps-script.php",
    "/php/components/migrate.php",
    
    # Email Templates
    "/php/email-templates/contact-admin-email.tpl.php",
    "/php/email-templates/contact-customer-email.tpl.php",
    "/php/email-templates/test-ride-admin-email.tpl.php",
    "/php/email-templates/test-ride-customer-email.tpl.php",
    "/php/email-templates/transaction-failure-admin.tpl.php",
    "/php/email-templates/transaction-failure-customer.tpl.php",
    "/php/email-templates/transaction-success-admin.tpl.php",
    "/php/email-templates/transaction-success-customer.tpl.php"
)

$Results = @()
$ProtectedCount = 0
$ExposedCount = 0
$ErrorCount = 0

foreach ($File in $ProtectedFiles) {
    $Url = "$BaseUrl$File"
    
    try {
        $Response = Invoke-WebRequest -Uri $Url -Method Head -TimeoutSec 5 -ErrorAction SilentlyContinue
        # If we get here, file is accessible (BAD)
        Write-Host "EXPOSED  - $File" -ForegroundColor Red
        $ExposedCount++
        $Status = "EXPOSED"
    } catch {
        $StatusCode = $_.Exception.Response.StatusCode.Value__
        if ($StatusCode -eq 404) {
            Write-Host "PROTECTED - $File" -ForegroundColor Green
            $ProtectedCount++
            $Status = "PROTECTED"
        } else {
            Write-Host "OTHER ($StatusCode) - $File" -ForegroundColor Yellow
            $ErrorCount++
            $Status = "OTHER"
        }
    }
    
    $Results += [PSCustomObject]@{
        File = $File
        Status = $Status
        Protected = ($Status -eq "PROTECTED")
    }
}

Write-Host ""
Write-Host "=== DIRECT ACCESS PROTECTION SUMMARY ===" -ForegroundColor Cyan
Write-Host "Total Files Tested: $($ProtectedFiles.Count)"
Write-Host "Protected (404): $ProtectedCount" -ForegroundColor Green
Write-Host "Exposed (200): $ExposedCount" -ForegroundColor $(if ($ExposedCount -gt 0) { "Red" } else { "Green" })
Write-Host "Other Status: $ErrorCount" -ForegroundColor Yellow
Write-Host "Success Rate: $([math]::Round(($ProtectedCount / $ProtectedFiles.Count) * 100, 2))%" -ForegroundColor Cyan

if ($ExposedCount -gt 0) {
    Write-Host ""
    Write-Host "CRITICAL SECURITY ISSUE:" -ForegroundColor Red
    Write-Host "$ExposedCount protected files are directly accessible!" -ForegroundColor Red
    Write-Host ""
    Write-Host "Exposed files:" -ForegroundColor Red
    foreach ($Result in $Results) {
        if ($Result.Status -eq "EXPOSED") {
            Write-Host "  - $($Result.File)" -ForegroundColor Red
        }
    }
} else {
    Write-Host ""
    Write-Host "EXCELLENT! All protected files return 404" -ForegroundColor Green
    Write-Host "Direct access protection is working perfectly!" -ForegroundColor Green
}

Write-Host ""
Write-Host "Test completed: $(Get-Date)"
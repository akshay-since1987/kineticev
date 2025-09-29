# Direct Access Protection Test
# Tests all 35 protected PHP files to ensure they return 404 on direct access

function Test-DirectAccessProtection {
    param(
        [string]$BaseUrl = "https://test.kineticev.in",
        [switch]$Verbose
    )
    
    Write-Host "🛡️ Testing Direct Access Protection..." -ForegroundColor Cyan
    
    # Define all protected files based on our tracker
    $ProtectedFiles = @{
        "CRITICAL" = @(
            "/php/config.php",
            "/php/prod-config.php", 
            "/php/test-config.php",
            "/php/dev-config.php"
        )
        "HIGH" = @(
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
            "/php/PasswordResetHandler.php"
        )
        "MEDIUM" = @(
            "/php/admin/AdminHandler.php"
        )
        "LOWER" = @(
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
            "/php/email-templates/contact-admin-email.tpl.php",
            "/php/email-templates/contact-customer-email.tpl.php",
            "/php/email-templates/test-ride-admin-email.tpl.php",
            "/php/email-templates/test-ride-customer-email.tpl.php",
            "/php/email-templates/transaction-failure-admin.tpl.php",
            "/php/email-templates/transaction-failure-customer.tpl.php",
            "/php/email-templates/transaction-success-admin.tpl.php",
            "/php/email-templates/transaction-success-customer.tpl.php"
        )
    }
    
    $Results = @()
    $Summary = @{
        Total = 0
        Protected = 0
        Unprotected = 0
        Errors = 0
    }
    
    foreach ($Priority in $ProtectedFiles.Keys) {
        Write-Host "`n📋 Testing $Priority Priority Files..." -ForegroundColor Yellow
        
        foreach ($File in $ProtectedFiles[$Priority]) {
            $Summary.Total++
            $Url = "$BaseUrl$File"
            
            try {
                $Response = Invoke-WebRequest -Uri $Url -Method Head -TimeoutSec 10 -ErrorAction SilentlyContinue
                $StatusCode = $Response.StatusCode
                $Protected = $false
                $Status = "❌ EXPOSED"
                $Color = "Red"
            } catch {
                $StatusCode = $_.Exception.Response.StatusCode.Value__
                if ($StatusCode -eq 404) {
                    $Protected = $true
                    $Status = "✅ PROTECTED"
                    $Color = "Green"
                    $Summary.Protected++
                } else {
                    $Protected = $false
                    $Status = "⚠️ OTHER"
                    $Color = "Yellow"
                    $Summary.Errors++
                }
            }
            
            if (-not $Protected -and $StatusCode -eq 200) {
                $Summary.Unprotected++
            }
            
            $Result = [PSCustomObject]@{
                File = $File
                Priority = $Priority
                StatusCode = $StatusCode
                Protected = $Protected
                Status = $Status
                URL = $Url
            }
            
            $Results += $Result
            
            if ($Verbose) {
                Write-Host "  $Status - $File ($StatusCode)" -ForegroundColor $Color
            } else {
                Write-Host "  $Status" -ForegroundColor $Color -NoNewline
            }
        }
        Write-Host "" # New line after each priority group
    }
    
    # Summary
    Write-Host "`n📊 Direct Access Protection Summary:" -ForegroundColor Cyan
    Write-Host "Total Files: $($Summary.Total)" -ForegroundColor White
    Write-Host "Protected (404): $($Summary.Protected)" -ForegroundColor Green
    Write-Host "Unprotected (200): $($Summary.Unprotected)" -ForegroundColor Red
    Write-Host "Other Status: $($Summary.Errors)" -ForegroundColor Yellow
    Write-Host "Success Rate: $([math]::Round(($Summary.Protected / $Summary.Total) * 100, 2))%" -ForegroundColor Cyan
    
    # Critical Issues Alert
    if ($Summary.Unprotected -gt 0) {
        Write-Host "`n🚨 CRITICAL SECURITY ISSUE:" -ForegroundColor Red
        Write-Host "$($Summary.Unprotected) protected files are accessible!" -ForegroundColor Red
        
        $ExposedFiles = $Results | Where-Object { -not $_.Protected -and $_.StatusCode -eq 200 }
        foreach ($ExposedFile in $ExposedFiles) {
            Write-Host "  ❌ $($ExposedFile.File) - $($ExposedFile.Priority) Priority" -ForegroundColor Red
        }
    }
    
    return @{
        Summary = $Summary
        Results = $Results
        Passed = ($Summary.Unprotected -eq 0)
    }
}

# Test functionality - ensure protected files still work when included
function Test-ProtectedFileFunctionality {
    param(
        [string]$BaseUrl = "https://test.kineticev.in",
        [switch]$Verbose
    )
    
    Write-Host "`n🔧 Testing Protected File Functionality..." -ForegroundColor Cyan
    
    $FunctionalityTests = @(
        @{ URL = "/"; Description = "Home page (includes components)" },
        @{ URL = "/contact-us.php"; Description = "Contact page (includes header/footer)" },
        @{ URL = "/book-now.php"; Description = "Booking page (includes all components)" },
        @{ URL = "/choose-variant.php"; Description = "Variant selection (database calls)" },
        @{ URL = "/dealership-finder-pincode.php"; Description = "Dealership finder (maps)" }
    )
    
    $Results = @()
    
    foreach ($Test in $FunctionalityTests) {
        $Url = "$BaseUrl$($Test.URL)"
        
        try {
            $Response = Invoke-WebRequest -Uri $Url -Method Get -TimeoutSec 15 -ErrorAction SilentlyContinue
            $StatusCode = $Response.StatusCode
            $ContentLength = $Response.Content.Length
            
            $Passed = ($StatusCode -eq 200 -and $ContentLength -gt 1000)
            $Status = if ($Passed) { "✅ WORKING" } else { "❌ BROKEN" }
            $Color = if ($Passed) { "Green" } else { "Red" }
            
        } catch {
            $StatusCode = $_.Exception.Response.StatusCode.Value__
            $ContentLength = 0
            $Passed = $false
            $Status = "❌ ERROR"
            $Color = "Red"
        }
        
        $Result = [PSCustomObject]@{
            URL = $Test.URL
            Description = $Test.Description
            StatusCode = $StatusCode
            ContentLength = $ContentLength
            Passed = $Passed
            Status = $Status
        }
        
        $Results += $Result
        
        if ($Verbose) {
            Write-Host "  $Status - $($Test.Description) ($StatusCode, $ContentLength bytes)" -ForegroundColor $Color
        } else {
            Write-Host "  $Status" -ForegroundColor $Color -NoNewline
        }
    }
    
    Write-Host "`n✅ Functionality tests complete" -ForegroundColor Green
    
    return @{
        Results = $Results
        Passed = ($Results | Where-Object { -not $_.Passed }).Count -eq 0
    }
}

# Export function for module use
Export-ModuleMember -Function Test-DirectAccessProtection, Test-ProtectedFileFunctionality
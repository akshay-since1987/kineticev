# K2 Security Test Suite Runner
# Main entry point for all security testing

param(
    [string]$Environment = "dev",
    [string]$BaseUrl = "https://dev.kineticev.in",
    [string]$TestCategory = "all",
    [switch]$GenerateReport,
    [switch]$Verbose
)

# Import all test modules
$ScriptPath = Split-Path -Parent $MyInvocation.MyCommand.Definition
. "$ScriptPath\security\DirectAccessProtectionTest.ps1"
. "$ScriptPath\security\AuthenticationTest.ps1"
. "$ScriptPath\security\InputValidationTest.ps1"
. "$ScriptPath\security\APISecurityTest.ps1"
. "$ScriptPath\security\ConfigurationSecurityTest.ps1"
. "$ScriptPath\security\SessionCSRFTest.ps1"
. "$ScriptPath\security\FileDirectorySecurityTest.ps1"
. "$ScriptPath\security\ErrorHandlingTest.ps1"

function Write-TestHeader {
    param([string]$Title)
    
    Write-Host "`n" + "="*80 -ForegroundColor Magenta
    Write-Host "🔒 $Title" -ForegroundColor Cyan
    Write-Host "="*80 -ForegroundColor Magenta
}

function Write-TestResult {
    param([string]$Test, [bool]$Passed, [string]$Details = "")
    
    $Status = if ($Passed) { "✅ PASS" } else { "❌ FAIL" }
    $Color = if ($Passed) { "Green" } else { "Red" }
    
    Write-Host "$Status - $Test" -ForegroundColor $Color
    if ($Details -and $Verbose) {
        Write-Host "   $Details" -ForegroundColor Gray
    }
}

function Start-K2SecurityTests {
    Write-Host "🚀 K2 Security Test Suite Started" -ForegroundColor Magenta
    Write-Host "Environment: $Environment" -ForegroundColor Cyan
    Write-Host "Base URL: $BaseUrl" -ForegroundColor Cyan
    Write-Host "Test Category: $TestCategory" -ForegroundColor Cyan
    Write-Host "Timestamp: $(Get-Date)" -ForegroundColor Gray
    
    $StartTime = Get-Date
    $AllResults = @{}
    $OverallPassed = 0
    $OverallFailed = 0
    
    # Test 1: Direct Access Protection
    if ($TestCategory -eq "all" -or $TestCategory -eq "access") {
        Write-TestHeader "Direct Access Protection Testing"
        $AccessResults = Test-DirectAccessProtection -BaseUrl $BaseUrl -Verbose:$Verbose
        $AllResults["DirectAccess"] = $AccessResults
        
        $AccessPassed = ($AccessResults.Results | Where-Object { $_.Protected }).Count
        $AccessFailed = ($AccessResults.Results | Where-Object { -not $_.Protected }).Count
        
        Write-Host "Files Tested: $($AccessResults.Results.Count)" -ForegroundColor Yellow
        Write-Host "Protected: $AccessPassed" -ForegroundColor Green
        Write-Host "Unprotected: $AccessFailed" -ForegroundColor Red
        
        $OverallPassed += $AccessPassed
        $OverallFailed += $AccessFailed
    }
    
    # Test 2: Authentication & Authorization
    if ($TestCategory -eq "all" -or $TestCategory -eq "auth") {
        Write-TestHeader "Authentication & Authorization Testing"
        $AuthResults = Test-Authentication -BaseUrl $BaseUrl -Verbose:$Verbose
        $AllResults["Authentication"] = $AuthResults
        
        foreach ($Result in $AuthResults.Results) {
            Write-TestResult -Test $Result.Test -Passed $Result.Passed -Details $Result.Details
            if ($Result.Passed) { $OverallPassed++ } else { $OverallFailed++ }
        }
    }
    
    # Test 3: Input Validation & SQL Injection
    if ($TestCategory -eq "all" -or $TestCategory -eq "input") {
        Write-TestHeader "Input Validation & SQL Injection Testing"
        $InputResults = Test-InputValidation -BaseUrl $BaseUrl -Verbose:$Verbose
        $AllResults["InputValidation"] = $InputResults
        
        foreach ($Result in $InputResults.Results) {
            Write-TestResult -Test $Result.Test -Passed $Result.Passed -Details $Result.Details
            if ($Result.Passed) { $OverallPassed++ } else { $OverallFailed++ }
        }
    }
    
    # Test 4: API Security
    if ($TestCategory -eq "all" -or $TestCategory -eq "api") {
        Write-TestHeader "API Security Testing"
        $APIResults = Test-APISecurity -BaseUrl $BaseUrl -Verbose:$Verbose
        $AllResults["APISecurity"] = $APIResults
        
        foreach ($Result in $APIResults.Results) {
            Write-TestResult -Test $Result.Test -Passed $Result.Passed -Details $Result.Details
            if ($Result.Passed) { $OverallPassed++ } else { $OverallFailed++ }
        }
    }
    
    # Test 5: Configuration Security
    if ($TestCategory -eq "all" -or $TestCategory -eq "config") {
        Write-TestHeader "Configuration Security Testing"
        $ConfigResults = Test-ConfigurationSecurity -BaseUrl $BaseUrl -Verbose:$Verbose
        $AllResults["Configuration"] = $ConfigResults
        
        foreach ($Result in $ConfigResults.Results) {
            Write-TestResult -Test $Result.Test -Passed $Result.Passed -Details $Result.Details
            if ($Result.Passed) { $OverallPassed++ } else { $OverallFailed++ }
        }
    }
    
    # Test 6: Session & CSRF Protection
    if ($TestCategory -eq "all" -or $TestCategory -eq "session") {
        Write-TestHeader "Session & CSRF Protection Testing"
        $SessionResults = Test-SessionCSRF -BaseUrl $BaseUrl -Verbose:$Verbose
        $AllResults["Session"] = $SessionResults
        
        foreach ($Result in $SessionResults.Results) {
            Write-TestResult -Test $Result.Test -Passed $Result.Passed -Details $Result.Details
            if ($Result.Passed) { $OverallPassed++ } else { $OverallFailed++ }
        }
    }
    
    # Test 7: File & Directory Security
    if ($TestCategory -eq "all" -or $TestCategory -eq "files") {
        Write-TestHeader "File & Directory Security Testing"
        $FileResults = Test-FileDirectorySecurity -BaseUrl $BaseUrl -Verbose:$Verbose
        $AllResults["FileSecurity"] = $FileResults
        
        foreach ($Result in $FileResults.Results) {
            Write-TestResult -Test $Result.Test -Passed $Result.Passed -Details $Result.Details
            if ($Result.Passed) { $OverallPassed++ } else { $OverallFailed++ }
        }
    }
    
    # Test 8: Error Handling & Information Disclosure
    if ($TestCategory -eq "all" -or $TestCategory -eq "errors") {
        Write-TestHeader "Error Handling & Information Disclosure Testing"
        $ErrorResults = Test-ErrorHandling -BaseUrl $BaseUrl -Verbose:$Verbose
        $AllResults["ErrorHandling"] = $ErrorResults
        
        foreach ($Result in $ErrorResults.Results) {
            Write-TestResult -Test $Result.Test -Passed $Result.Passed -Details $Result.Details
            if ($Result.Passed) { $OverallPassed++ } else { $OverallFailed++ }
        }
    }
    
    $EndTime = Get-Date
    $Duration = ($EndTime - $StartTime).TotalMinutes
    
    # Final Summary
    Write-TestHeader "Test Suite Summary"
    Write-Host "Total Tests Passed: $OverallPassed" -ForegroundColor Green
    Write-Host "Total Tests Failed: $OverallFailed" -ForegroundColor Red
    Write-Host "Success Rate: $([math]::Round(($OverallPassed / ($OverallPassed + $OverallFailed)) * 100, 2))%" -ForegroundColor Cyan
    Write-Host "Total Duration: $([math]::Round($Duration, 2)) minutes" -ForegroundColor Yellow
    
    # Generate Report
    if ($GenerateReport) {
        $ReportPath = "$ScriptPath\reports\SecurityTestReport_$(Get-Date -Format 'yyyyMMdd_HHmmss').json"
        $ReportData = @{
            Timestamp = $StartTime
            Environment = $Environment
            BaseUrl = $BaseUrl
            TestCategory = $TestCategory
            Duration = $Duration
            Summary = @{
                Passed = $OverallPassed
                Failed = $OverallFailed
                SuccessRate = [math]::Round(($OverallPassed / ($OverallPassed + $OverallFailed)) * 100, 2)
            }
            Results = $AllResults
        }
        
        $ReportData | ConvertTo-Json -Depth 10 | Out-File -FilePath $ReportPath -Encoding UTF8
        Write-Host "📊 Report saved to: $ReportPath" -ForegroundColor Cyan
    }
    
    return $AllResults
}

# Usage Examples:
# .\RunSecurityTests.ps1                                    # Run all tests
# .\RunSecurityTests.ps1 -TestCategory "access"            # Run only direct access tests
# .\RunSecurityTests.ps1 -BaseUrl "https://prod.example.com" # Test production
# .\RunSecurityTests.ps1 -Verbose                          # Detailed output

# Run the tests if script is executed directly
if ($MyInvocation.InvocationName -ne '.') {
    Start-K2SecurityTests
}
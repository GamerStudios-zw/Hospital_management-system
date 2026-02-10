$ErrorActionPreference = "Stop"

param(
  [string]$BaseUrl = "http://localhost/Hospital_Management_System/backend/index.php",
  [string]$Token = ""
)

function Invoke-DbTest {
  Write-Host "Running DB connection test..." -ForegroundColor Cyan
  php "$PSScriptRoot\\..\\backend\\db_connection_test.php"
  Write-Host "DB connection test completed." -ForegroundColor Green
}

function Invoke-SmokeTest {
  Write-Host "Running API smoke test..." -ForegroundColor Cyan
  & "$PSScriptRoot\\smoke_test.ps1" -BaseUrl $BaseUrl -Token $Token
}

$failed = $false
try { Invoke-DbTest } catch { $failed = $true; Write-Host $_ -ForegroundColor Red }
try { Invoke-SmokeTest } catch { $failed = $true; Write-Host $_ -ForegroundColor Red }

if ($failed) {
  Write-Host "Test suite failed." -ForegroundColor Red
  exit 1
}

Write-Host "All tests passed." -ForegroundColor Green

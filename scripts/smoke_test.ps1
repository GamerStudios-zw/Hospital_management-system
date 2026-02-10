$ErrorActionPreference = "Stop"

param(
  [string]$BaseUrl = "http://localhost/Hospital_Management_System/backend/index.php",
  [string]$Token = ""
)

function Invoke-Api {
  param(
    [string]$Path,
    [string]$Method = "GET"
  )
  $headers = @{}
  if ($Token) { $headers["Authorization"] = "Bearer $Token" }
  try {
    $resp = Invoke-RestMethod -Method $Method -Uri "$BaseUrl$Path" -Headers $headers -TimeoutSec 20
    return @{ ok = $true; data = $resp }
  } catch {
    $status = $_.Exception.Response.StatusCode.value__ 2>$null
    return @{ ok = $false; status = $status; error = $_.Exception.Message }
  }
}

Write-Host "HMS Smoke Test"
Write-Host "BaseUrl: $BaseUrl"
Write-Host "Token: " + ($(if ($Token) { "provided" } else { "none" }))
Write-Host ""

$checks = @(
  @{ name = "health"; path = "/health" },
  @{ name = "auth_login"; path = "/auth/login" },
  @{ name = "doctor_waiting_list"; path = "/doctor/waiting_list" },
  @{ name = "nurse_admission_waiting"; path = "/nurse/admission_waiting_list" },
  @{ name = "pharmacy_pending"; path = "/pharmacy/pending" },
  @{ name = "pharmacy_nurse_requests"; path = "/pharmacy/nurse_requests" }
)

$fail = $false
foreach ($c in $checks) {
  $res = Invoke-Api -Path $c.path
  if ($Token) {
    if ($res.ok) {
      Write-Host "OK   $($c.name)" -ForegroundColor Green
    } else {
      Write-Host "FAIL $($c.name) (status=$($res.status))" -ForegroundColor Red
      $fail = $true
    }
  } else {
    # Without token, most routes should return 401/403 (auth enforced)
    if (-not $res.ok -and ($res.status -eq 401 -or $res.status -eq 403)) {
      Write-Host "OK   $($c.name) (auth enforced)" -ForegroundColor Green
    } else {
      Write-Host "WARN $($c.name) (expected 401/403 without token)" -ForegroundColor Yellow
    }
  }
}

Write-Host ""
if ($fail) {
  Write-Host "Smoke test failed. See above." -ForegroundColor Red
  exit 1
}
Write-Host "Smoke test completed." -ForegroundColor Green

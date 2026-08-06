param(
  [string]$BaseUrl = "http://localhost/Hospital_Management_System/backend/index.php",
  [string]$Token = ""
)

$ErrorActionPreference = "Stop"

function Invoke-Api {
  param(
    [string]$Path,
    [string]$Method = "GET"
  )
  $headers = @{}
  if ($Token) { $headers["Authorization"] = "Bearer $Token" }
  try {
    $resp = Invoke-WebRequest -Method $Method -Uri "$BaseUrl$Path" -Headers $headers -TimeoutSec 20 -UseBasicParsing
    $parsed = $null
    try { $parsed = $resp.Content | ConvertFrom-Json } catch {}
    return @{ ok = $true; status = [int]$resp.StatusCode; data = $parsed; raw = $resp.Content }
  } catch {
    $status = 0
    try { $status = [int]$_.Exception.Response.StatusCode.value__ } catch {}
    return @{ ok = $false; status = $status; error = $_.Exception.Message }
  }
}

Write-Host "HMS Smoke Test"
Write-Host "BaseUrl: $BaseUrl"
Write-Host "Token: " + ($(if ($Token) { "provided" } else { "none" }))
Write-Host ""

$checks = @(
  @{ name = "health"; path = "/health"; method = "GET"; expect_with_token = @(200); expect_without_token = @(200) },
  @{ name = "auth_login_method_guard"; path = "/auth/login"; method = "GET"; expect_with_token = @(405); expect_without_token = @(405) },
  @{ name = "doctor_waiting_list"; path = "/doctor/waiting_list"; method = "GET"; expect_with_token = @(200); expect_without_token = @(401,403) },
  @{ name = "nurse_admission_waiting"; path = "/nurse/admission_waiting_list"; method = "GET"; expect_with_token = @(200); expect_without_token = @(401,403) },
  @{ name = "pharmacy_pending"; path = "/pharmacy/pending"; method = "GET"; expect_with_token = @(200); expect_without_token = @(401,403) },
  @{ name = "pharmacy_nurse_requests"; path = "/pharmacy/nurse_requests"; method = "GET"; expect_with_token = @(200); expect_without_token = @(401,403) }
)

$fail = $false
foreach ($c in $checks) {
  $res = Invoke-Api -Path $c.path -Method $c.method
  $expected = if ($Token) { @($c.expect_with_token) } else { @($c.expect_without_token) }
  $statusMatches = ($expected -contains [int]$res.status)

  if ($Token) {
    if ($statusMatches) {
      Write-Host "OK   $($c.name) (status=$($res.status))" -ForegroundColor Green
    } else {
      Write-Host "FAIL $($c.name) (status=$($res.status), expected=$($expected -join ','))" -ForegroundColor Red
      $fail = $true
    }
  } else {
    # Without token we keep this non-blocking for local sanity checks.
    if ($statusMatches) {
      Write-Host "OK   $($c.name) (status=$($res.status))" -ForegroundColor Green
    } else {
      Write-Host "WARN $($c.name) (status=$($res.status), expected=$($expected -join ','))" -ForegroundColor Yellow
    }
  }
}

Write-Host ""
if ($fail) {
  Write-Host "Smoke test failed. See above." -ForegroundColor Red
  exit 1
}
Write-Host "Smoke test completed." -ForegroundColor Green

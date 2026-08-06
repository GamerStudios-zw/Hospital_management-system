param(
  [string]$BaseUrl = "http://localhost/Hospital_Management_System/backend/index.php",
  [int]$IntervalSec = 30,
  [switch]$Beep,
  [switch]$Once,
  [string]$LogFile = ""
)

$ErrorActionPreference = "Stop"

function Write-Log {
  param([string]$Message, [string]$Level = "INFO")
  $line = "$(Get-Date -Format o) [$Level] $Message"
  Write-Host $line
  if ($LogFile) { Add-Content -Path $LogFile -Value $line }
}

function Check-Health {
  try {
    $resp = Invoke-RestMethod -Method GET -Uri "$BaseUrl/health" -TimeoutSec 10
    if (-not $resp) { throw "No response" }
    $dbOk = $resp.db.ok -eq $true
    $rtOk = $resp.realtime.ok -eq $true
    if ($dbOk -and $rtOk) {
      Write-Log "OK health db=$($resp.db.ok) realtime=$($resp.realtime.ok)"
      return $true
    }
    Write-Log "WARN health db=$($resp.db.ok) realtime=$($resp.realtime.ok)" "WARN"
    if ($Beep) { [console]::beep(800, 250) }
    return $false
  } catch {
    Write-Log "ERROR health check failed: $($_.Exception.Message)" "ERROR"
    if ($Beep) { [console]::beep(800, 500) }
    return $false
  }
}

do {
  Check-Health | Out-Null
  if (-not $Once) { Start-Sleep -Seconds $IntervalSec }
} while (-not $Once)

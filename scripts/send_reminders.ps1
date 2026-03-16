$ErrorActionPreference = "Stop"

param(
  [switch]$DryRun,
  [switch]$Force,
  [switch]$NoInternal,
  [string]$Emails = "",
  [string]$UserIds = ""
)

$phpArgs = @("$PSScriptRoot\..\backend\send_reminders.php")
if ($DryRun) { $phpArgs += "--dry-run" }
if ($Force) { $phpArgs += "--force" }
if ($NoInternal) { $phpArgs += "--no-internal" }
if ($Emails -and $Emails.Trim() -ne "") { $phpArgs += "--emails=$Emails" }
if ($UserIds -and $UserIds.Trim() -ne "") { $phpArgs += "--user-ids=$UserIds" }

Write-Host "Running reminder dispatch..." -ForegroundColor Cyan
php @phpArgs

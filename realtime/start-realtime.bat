@echo off
setlocal EnableExtensions EnableDelayedExpansion

rem Start HMS realtime websocket server with basic self-healing checks
for %%I in ("%~dp0..") do set "ROOT=%%~fI"
cd /d "%ROOT%"

set "RT_DIR=%ROOT%\realtime"
if not exist "%RT_DIR%\server.js" (
  echo ERROR: realtime\server.js not found.
  echo Current: %CD%
  pause
  exit /b 1
)

where node >nul 2>&1
if errorlevel 1 (
  echo ERROR: Node.js is not installed or not in PATH.
  echo Install Node.js, then rerun this script.
  pause
  exit /b 1
)

if not exist "%RT_DIR%\node_modules\ws\package.json" (
  echo Realtime dependencies not found. Installing...
  pushd "%RT_DIR%"
  call npm install --no-audit --no-fund
  set "NPM_EXIT=!ERRORLEVEL!"
  popd

  if not "!NPM_EXIT!"=="0" (
    echo ERROR: npm install failed with code !NPM_EXIT!.
    pause
    exit /b !NPM_EXIT!
  )
)

if "%HMS_RT_HTTP_PORT%"=="" set "HMS_RT_HTTP_PORT=8090"

echo Starting HMS realtime server on port %HMS_RT_HTTP_PORT%...
pushd "%RT_DIR%"
node server.js
set "NODE_EXIT=%ERRORLEVEL%"
popd

if not "%NODE_EXIT%"=="0" (
  echo.
  echo Realtime server stopped with exit code %NODE_EXIT%.
  pause
)

exit /b %NODE_EXIT%

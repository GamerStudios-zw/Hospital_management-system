@echo off
setlocal

rem Start HMS realtime websocket server
set "ROOT=%~dp0.."
cd /d "%ROOT%"

if not exist "realtime\\server.js" (
  echo ERROR: realtime\\server.js not found. Run this from the project root.
  echo Current: %CD%
  pause
  exit /b 1
)

set "HMS_RT_HTTP_PORT=8090"
echo Starting HMS realtime server on port %HMS_RT_HTTP_PORT%...
node "realtime\\server.js"

pause

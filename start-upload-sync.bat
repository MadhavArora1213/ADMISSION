@echo off
title AdmissionSeason - Upload Sync
echo ============================================
echo  AdmissionSeason Upload Sync
echo  Watching uploads/ folder every 60 seconds
echo  Press CTRL+C to stop
echo ============================================
echo.
powershell -ExecutionPolicy Bypass -File "%~dp0watch-uploads.ps1"
pause

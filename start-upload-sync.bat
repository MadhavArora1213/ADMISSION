@echo off
title AdmissionSeason - Upload Sync (Real-Time)
color 0B
echo ============================================
echo  AdmissionSeason - Real-Time Upload Sync
echo  Watches uploads/ folder for any changes
echo  Pushes to GitHub automatically
echo  Press CTRL+C to stop
echo ============================================
echo.
powershell -ExecutionPolicy Bypass -File "%~dp0watch-uploads.ps1"
pause

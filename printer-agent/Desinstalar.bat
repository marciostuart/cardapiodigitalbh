@echo off
powershell.exe -NoProfile -ExecutionPolicy Bypass -File "%~dp0Uninstall-PrinterAgent.ps1" %*
if errorlevel 1 pause

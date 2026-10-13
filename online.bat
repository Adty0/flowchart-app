@echo off
title Laboratorium Flowchart Online
cls
echo ========================================================
echo   LABORATORIUM FLOWCHART EDITOR - CLOUDFLARE TUNNEL
echo ========================================================
echo.
echo Pastikan server Laravel (port 8000) dan MySQL sudah aktif.
echo Sedang mengaktifkan link publik HTTPS...
echo.
cloudflared.exe tunnel --url http://127.0.0.1:8000
pause


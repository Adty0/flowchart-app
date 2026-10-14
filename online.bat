@echo off
title Laboratorium Flowchart Online
cls
echo ========================================================
echo   LABORATORIUM FLOWCHART EDITOR - CLOUDFLARE TUNNEL
echo ========================================================
echo.
echo [1/3] Membersihkan sisa cache & dev file...
if exist public\hot del /f /q public\hot
call php artisan view:clear >nul 2>&1
call php artisan config:clear >nul 2>&1

echo [2/3] Memastikan server Laravel (port 8000) & MySQL aktif...
echo [3/3] Mengaktifkan link publik HTTPS Cloudflare Tunnel...
echo.
echo --------------------------------------------------------
echo Salin (copy) link berakhiran .trycloudflare.com di bawah
echo lalu bagikan ke siswa untuk membuka aplikasi di HP/Laptop.
echo --------------------------------------------------------
echo.
cloudflared.exe tunnel --url http://127.0.0.1:8000
pause


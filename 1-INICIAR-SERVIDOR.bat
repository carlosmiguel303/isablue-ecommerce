@echo off
title Isablue - Servidor (backend)
echo ============================================
echo   ISABLUE - Iniciando el servidor...
echo   No cierres esta ventana mientras uses la tienda.
echo ============================================
cd /d "%~dp0backend"
set SPRING_PROFILES_ACTIVE=local
"C:\Program Files\Java\jdk-17\bin\java.exe" -jar target\backend-0.0.1-SNAPSHOT.jar
echo.
echo El servidor se detuvo. Presiona una tecla para cerrar.
pause >nul

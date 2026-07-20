@echo off
title Isablue - Tienda (web)
echo ============================================
echo   ISABLUE - Iniciando la tienda web...
echo   Cuando diga "Compiled successfully", abre:
echo   http://localhost:4200
echo   No cierres esta ventana mientras uses la tienda.
echo ============================================
cd /d "%~dp0front"
call npm start
echo.
echo La tienda se detuvo. Presiona una tecla para cerrar.
pause >nul

@echo off
title Servidor CityReport
cd /d "C:\Users\ivan_\OneDrive\Desktop\City Report HTML"
echo Iniciando servidor PHP en http://127.0.0.1:8081 ...
"C:\Users\ivan_\OneDrive\Desktop\php\php.exe" -d display_errors=1 -d error_reporting=E_ALL -S 127.0.0.1:8081 -t .
echo.
echo Servidor ejecutándose. Presiona CTRL + C para detenerlo.
pause >nul

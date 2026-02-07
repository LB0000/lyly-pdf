@echo off
setlocal

php ./run.php "%~1" "temp"

echo.
endlocal
pause

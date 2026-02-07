@echo off
setlocal

php ./run.php "%~1" "draft"

echo.
endlocal
pause

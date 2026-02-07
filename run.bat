@echo off
setlocal

php run.php "%~1" "all"

echo.
endlocal
pause

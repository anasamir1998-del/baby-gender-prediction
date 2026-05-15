@echo off
chcp 65001 >nul
echo ========================================
echo   Deploying changes...
echo ========================================
echo.

cd /d "%~dp0"

echo [1/3] Adding all files...
git add -A
echo.

echo [2/3] Committing changes...
git commit -m "Migrate from Firebase to PHP+MySQL for Hostinger"
echo.

echo [3/3] Pushing to remote...
git push origin main
if errorlevel 1 git push origin master
echo.

echo ========================================
echo   Done!
echo ========================================
pause

@echo off
chcp 65001 >nul
echo ========================================
echo   Deploying changes...
echo ========================================
echo.

cd /d "%~dp0"

echo [1/4] Pulling remote changes...
git pull origin main --no-edit
echo.

echo [2/4] Adding all files...
git add -A
echo.

echo [3/4] Committing changes...
git commit -m "Migrate from Firebase to PHP+MySQL for Hostinger"
echo.

echo [4/4] Pushing to remote...
git push origin main
echo.

echo ========================================
echo   Done!
echo ========================================
pause

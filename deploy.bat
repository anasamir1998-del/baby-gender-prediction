@echo off
chcp 65001 >nul
echo ========================================
echo   Force pushing changes...
echo ========================================
echo.

cd /d "%~dp0"

echo [1/3] Adding all files...
git add -A
echo.

echo [2/3] Committing changes...
git commit -m "Migrate from Firebase to PHP+MySQL for Hostinger" --allow-empty
echo.

echo [3/3] Force pushing to remote...
git push origin main --force
echo.

echo ========================================
echo   Done!
echo ========================================
pause

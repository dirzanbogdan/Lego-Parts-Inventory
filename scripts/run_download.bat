@echo off
echo Starting Lego Images Download...
echo.
echo Select type to download:
echo 1. Parts
echo 2. Sets
echo 3. Themes
echo.
set /p choice="Enter choice (1-3): "

if "%choice%"=="1" set TYPE=parts
if "%choice%"=="2" set TYPE=sets
if "%choice%"=="3" set TYPE=themes

if "%TYPE%"=="" (
    echo Invalid choice.
    pause
    exit
)

echo.
echo Running download for %TYPE%...
echo This may take a long time. Press Ctrl+C to stop.
echo.

php "%~dp0download_images.php" --type=%TYPE%

echo.
echo Download finished.
pause

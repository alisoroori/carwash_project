@echo off
echo ================================
echo   CarWash Project Setup
echo ================================
echo.

echo Checking Node.js installation...
node --version >nul 2>&1
if %ERRORLEVEL% NEQ 0 (
    echo [ERROR] Node.js is not installed!
    echo Please download and install Node.js from https://nodejs.org
    echo Then run this script again.
    pause
    exit /b 1
)

echo [OK] Node.js is installed
node --version

echo.
echo Checking npm...
npm --version >nul 2>&1
if %ERRORLEVEL% NEQ 0 (
    echo [ERROR] npm is not available!
    pause
    exit /b 1
)

echo [OK] npm is available
npm --version

echo.
echo Installing dependencies...
npm install

if %ERRORLEVEL% EQU 0 (
    echo.
    echo [SUCCESS] All dependencies installed successfully!
    echo.
    echo Available commands:
    echo   npm run dev          - Start Vite development server
    echo   npm run build        - Build for production
    echo   npm run build-css    - Build TailwindCSS only (watch mode)
    echo.
    echo Your CarWash project is ready for development!
) else (
    echo [ERROR] Failed to install dependencies
    echo Please check your internet connection and try again.
)

echo.
pause
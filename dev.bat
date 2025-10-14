@echo off
title CarWash Development Server
echo ================================
echo   CarWash Development Mode
echo ================================
echo.

:menu
echo Select development mode:
echo 1. TailwindCSS only (recommended for PHP development)
echo 2. Full Vite development server
echo 3. Build production assets
echo 4. Exit
echo.
set /p choice="Enter your choice (1-4): "

if "%choice%"=="1" goto tailwind_only
if "%choice%"=="2" goto vite_dev
if "%choice%"=="3" goto build_prod
if "%choice%"=="4" goto exit
echo Invalid choice. Please try again.
goto menu

:tailwind_only
echo.
echo Starting TailwindCSS in watch mode...
echo CSS changes will be compiled automatically.
echo Your XAMPP server should be running on http://localhost/carwash_project/
echo Press Ctrl+C to stop.
echo.
npm run build-css
goto menu

:vite_dev
echo.
echo Starting Vite development server...
echo Server will run on http://localhost:3000
echo PHP backend proxy: http://localhost:3000/backend
echo Press Ctrl+C to stop.
echo.
npm run dev
goto menu

:build_prod
echo.
echo Building production assets...
npm run build
npm run build-css-prod
echo.
echo [SUCCESS] Production assets built successfully!
echo Check the /dist folder for optimized files.
echo.
pause
goto menu

:exit
echo Goodbye!
exit
@echo off
echo Building Tailwind CSS for CarWash Project...
echo.

if "%1"=="dev" (
    echo Starting development mode with watch...
    .\tailwindcss.exe -i .\src\input.css -o .\frontend\css\tailwind.css --watch
) else if "%1"=="prod" (
    echo Building for production (minified)...
    .\tailwindcss.exe -i .\src\input.css -o .\frontend\css\tailwind.css --minify
    echo.
    echo ✅ Production CSS built successfully!
    echo File: frontend\css\tailwind.css
) else (
    echo Building for development...
    .\tailwindcss.exe -i .\src\input.css -o .\frontend\css\tailwind.css
    echo.
    echo ✅ Development CSS built successfully!
    echo File: frontend\css\tailwind.css
    echo.
    echo Usage:
    echo   build.bat        - Build for development
    echo   build.bat dev    - Build with watch mode
    echo   build.bat prod   - Build for production (minified)
)

pause
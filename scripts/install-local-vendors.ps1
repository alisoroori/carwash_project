<#
PowerShell script to download local vendor assets for CarWash project.

Place this script in the repository's scripts/ directory and run it from PowerShell:

  powershell -NoProfile -ExecutionPolicy Bypass -File .\scripts\install-local-vendors.ps1

It will download:
 - Font Awesome (CSS + webfonts) into frontend/vendor/fontawesome/{css,webfonts}
 - Alpine.js (cdn.min.js) into frontend/vendor/alpinejs/cdn.min.js

This script uses Invoke-WebRequest and handles basic errors.
#>

$ErrorActionPreference = 'Stop'

try {
    $scriptDir = Split-Path -Parent $MyInvocation.MyCommand.Path
    $repoRoot = Resolve-Path (Join-Path $scriptDir '..')
    $repoRoot = $repoRoot.ProviderPath

    Write-Host "Repository root: $repoRoot"

    # Target directories
    $faCssDir = Join-Path $repoRoot 'frontend\vendor\fontawesome\css'
    $faWebfontsDir = Join-Path $repoRoot 'frontend\vendor\fontawesome\webfonts'
    $alpineDir = Join-Path $repoRoot 'frontend\vendor\alpinejs'

    # Ensure directories exist
    New-Item -ItemType Directory -Force -Path $faCssDir | Out-Null
    New-Item -ItemType Directory -Force -Path $faWebfontsDir | Out-Null
    New-Item -ItemType Directory -Force -Path $alpineDir | Out-Null

    # URLs (CDN) - adjust versions if desired
    $faVersion = '6.4.0'
    $faCssUrl = "https://cdnjs.cloudflare.com/ajax/libs/font-awesome/$faVersion/css/all.min.css"
    $faWebfontsBase = "https://cdnjs.cloudflare.com/ajax/libs/font-awesome/$faVersion/webfonts"
    $faFonts = @(
        'fa-solid-900.woff2', 'fa-solid-900.woff',
        'fa-regular-400.woff2', 'fa-regular-400.woff',
        'fa-brands-400.woff2', 'fa-brands-400.woff'
    )

    $alpineUrl = 'https://cdn.jsdelivr.net/npm/alpinejs@3.12.0/dist/cdn.min.js'

    # Download Font Awesome CSS
    $faCssOut = Join-Path $faCssDir 'all.min.css'
    Write-Host "Downloading Font Awesome CSS -> $faCssOut"
    Invoke-WebRequest -Uri $faCssUrl -OutFile $faCssOut -UseBasicParsing

    # Download webfonts
    foreach ($font in $faFonts) {
        $url = "$faWebfontsBase/$font"
        $out = Join-Path $faWebfontsDir $font
        Write-Host "Downloading $font -> $out"
        Invoke-WebRequest -Uri $url -OutFile $out -UseBasicParsing
    }

    # Download Alpine.js (cdn)
    $alpineOut = Join-Path $alpineDir 'cdn.min.js'
    Write-Host "Downloading Alpine.js -> $alpineOut"
    Invoke-WebRequest -Uri $alpineUrl -OutFile $alpineOut -UseBasicParsing

    Write-Host "Download complete."
    Write-Host "Font Awesome -> $faCssDir, webfonts -> $faWebfontsDir"
    Write-Host "Alpine.js -> $alpineOut"

    # Quick verification
    if (Test-Path $faCssOut -and (Test-Path $alpineOut)) {
        Write-Host "Verification passed: CSS and Alpine present." -ForegroundColor Green
    } else {
        Write-Warning "Verification failed: expected files missing."
    }

} catch {
    Write-Error "Installation failed: $_"
    exit 1
}

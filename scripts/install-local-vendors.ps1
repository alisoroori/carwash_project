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

    # Try CDN first; if fonts are missing we'll fallback to the official release ZIP
    $alpineUrl = 'https://cdn.jsdelivr.net/npm/alpinejs@3.13.0/dist/cdn.min.js'

    # Download Font Awesome CSS
    $faCssOut = Join-Path $faCssDir 'all.min.css'
    Write-Host "Downloading Font Awesome CSS -> $faCssOut"
    Invoke-WebRequest -Uri $faCssUrl -OutFile $faCssOut -UseBasicParsing

    # Download webfonts (attempt each, but don't abort the whole script on a single 404)
    $missingFonts = @()
    foreach ($font in $faFonts) {
        $url = "$faWebfontsBase/$font"
        $out = Join-Path $faWebfontsDir $font
        Write-Host "Downloading $font -> $out"
        try {
            Invoke-WebRequest -Uri $url -OutFile $out -UseBasicParsing -ErrorAction Stop
        } catch {
            Write-Warning "Failed to download $font from CDN: $_. Will attempt fallback later."
            $missingFonts += $font
        }
    }

    # Download Alpine.js (cdn)
    $alpineOut = Join-Path $alpineDir 'cdn.min.js'
    Write-Host "Downloading Alpine.js -> $alpineOut"
    Invoke-WebRequest -Uri $alpineUrl -OutFile $alpineOut -UseBasicParsing

    # If any fonts are missing, try downloading the official Font Awesome web ZIP and extract
    if ($missingFonts.Count -gt 0) {
        Write-Host "Some webfonts failed to download from CDN: $($missingFonts -join ', ')"
        Write-Host "Attempting fallback: download Font Awesome web ZIP and extract webfonts..."

        $faReleaseZip = Join-Path $env:TEMP "fontawesome-free-web.zip"
        $faReleaseUrl = "https://github.com/FortAwesome/Font-Awesome/releases/download/$faVersion/fontawesome-free-$faVersion-web.zip"

        try {
            Write-Host "Downloading release ZIP -> $faReleaseZip"
            Invoke-WebRequest -Uri $faReleaseUrl -OutFile $faReleaseZip -UseBasicParsing -ErrorAction Stop

            $extractDir = Join-Path $env:TEMP "fa_extracted_$([System.Guid]::NewGuid().ToString())"
            New-Item -ItemType Directory -Force -Path $extractDir | Out-Null
            Write-Host "Extracting ZIP to $extractDir"
            Expand-Archive -Path $faReleaseZip -DestinationPath $extractDir -Force

            # The ZIP usually contains a top-level folder like 'fontawesome-free-6.4.0-web'
            $rootExtract = Get-ChildItem -Path $extractDir | Where-Object { $_.PSIsContainer } | Select-Object -First 1
            if ($rootExtract) {
                $srcWebfonts = Join-Path $rootExtract.FullName 'webfonts'
                if (Test-Path $srcWebfonts) {
                    Write-Host "Copying webfonts from $srcWebfonts -> $faWebfontsDir"
                    Copy-Item -Path (Join-Path $srcWebfonts '*') -Destination $faWebfontsDir -Force -ErrorAction Stop
                } else {
                    Write-Warning "Fallback archive did not contain expected 'webfonts' directory."
                }
                $srcCss = Join-Path $rootExtract.FullName 'css\all.min.css'
                if (Test-Path $srcCss -and -not (Test-Path $faCssOut)) {
                    Copy-Item -Path $srcCss -Destination $faCssOut -Force
                }
            } else {
                Write-Warning "Unable to locate extracted root folder in $extractDir"
            }

        } catch {
            Write-Warning "Fallback extraction failed: $_"
        } finally {
            # Cleanup temp files
            if (Test-Path $faReleaseZip) { Remove-Item $faReleaseZip -Force }
            if (Test-Path $extractDir) { Remove-Item $extractDir -Recurse -Force }
        }
    }

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

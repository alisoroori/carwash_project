# Insert -webkit-backdrop-filter before any backdrop-filter declaration in CSS files
# Searches dist/output.css and dist/assets/*.css and updates them in-place with a .bak backup

$cssFiles = @()
$base = Join-Path $PSScriptRoot ".." | Resolve-Path -Relative
$dist = Join-Path (Resolve-Path "$PSScriptRoot\..\dist") '' -ErrorAction SilentlyContinue
if (-not $dist) { $dist = Join-Path (Resolve-Path "$PSScriptRoot\..\dist" ) '' }
$glob1 = Join-Path "$PSScriptRoot\..\dist" "output.css"
$glob2 = Join-Path "$PSScriptRoot\..\dist\assets" "*.css"

if (Test-Path $glob1) { $cssFiles += (Resolve-Path $glob1) }
$assetsDir = Join-Path "$PSScriptRoot\..\dist\assets" '*'
$assetFiles = Get-ChildItem -Path (Join-Path "$PSScriptRoot\..\dist\assets") -Filter *.css -File -ErrorAction SilentlyContinue
if ($assetFiles) { $cssFiles += $assetFiles.FullName }

if (-not $cssFiles -or $cssFiles.Count -eq 0) {
    Write-Host "No built CSS files found in dist/. Run the build first (e.g., npm run build-css-prod).";
    exit 0
}

$pattern = '(?<!-webkit-)(backdrop-filter\s*:\s*([^;\}]+))'
foreach ($file in $cssFiles) {
    try {
        $content = Get-Content -Raw -Encoding UTF8 $file
    } catch {
        Write-Host "Failed to read $file : $_"; continue
    }

    if (-not $content) { Write-Host "Empty file: $file"; continue }

    # If the file already has -webkit-backdrop-filter everywhere, skip
    if ($content -match '-webkit-backdrop-filter\s*:') {
        # but we still want to ensure any remaining backdrop-filter without prefixed variant are handled
    }

    $new = [regex]::Replace($content, $pattern, '-webkit-backdrop-filter: $2; $1', [System.Text.RegularExpressions.RegexOptions]::IgnoreCase)

    if ($new -ne $content) {
        Copy-Item -Path $file -Destination "$file.bak" -Force
        Set-Content -Path $file -Value $new -Encoding UTF8
        Write-Host "Patched: $file (backup -> $file.bak)"
    } else {
        Write-Host "No changes needed: $file"
    }
}

Write-Host "Done. Updated built CSS files to include -webkit-backdrop-filter where applicable."
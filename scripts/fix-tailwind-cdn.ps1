# Fix Tailwind CDN references across the repo
# Usage: Run from repository root in PowerShell (as provided in README below)
# This script will:
# - Replace <script src="https://cdn.tailwindcss.com"></script> with a link to the compiled CSS
# - Remove <link rel="preconnect" href="https://cdn.tailwindcss.com"> lines
# - Remove occurrences of https://cdn.tailwindcss.com from CSP meta tags
# - Print a list of modified files

$repoRoot = Split-Path -Parent $MyInvocation.MyCommand.Path | Split-Path -Parent
if (-not $repoRoot) { $repoRoot = Get-Location }
Write-Host "Repo root:" $repoRoot

$patternScript = '<script\s+src="https://cdn.tailwindcss.com"\s*><\/script>'
$patternPreconnect = '<link\s+rel="preconnect"\s+href="https://cdn.tailwindcss.com"\s*>\s*'

$files = Get-ChildItem -Path $repoRoot -Recurse -File -Include *.php,*.html,*.htm,*.md,*.json
$changed = @()

foreach ($f in $files) {
    try {
        $content = Get-Content -Raw -LiteralPath $f.FullName -ErrorAction Stop
    } catch {
        continue
    }
    if ($content -notmatch 'cdn.tailwindcss.com') { continue }

    $new = $content
    # Replace the script tag with an absolute link to the compiled CSS
    $new = [regex]::Replace($new, $patternScript, '<link rel="stylesheet" href="/carwash_project/frontend/css/tailwind.css">', 'IgnoreCase')
    # Remove preconnect
    $new = [regex]::Replace($new, $patternPreconnect, '', 'IgnoreCase')
    # Remove raw occurrences in CSP or other places
    $new = $new -replace 'https://cdn.tailwindcss.com',''

    if ($new -ne $content) {
        Set-Content -LiteralPath $f.FullName -Value $new -Encoding UTF8
        $changed += $f.FullName
        Write-Host "Updated:" $f.FullName
    }
}

Write-Host "Total files updated:" $changed.Count
if ($changed.Count -gt 0) { $changed | Out-File -FilePath "$repoRoot\scripts\tailwind-cdn-changed-files.txt" -Encoding utf8 }

Write-Host "Done. Please run: npm run build-css-prod (or npm install && npm run build-css-prod) to regenerate frontend/css/tailwind.css if needed."
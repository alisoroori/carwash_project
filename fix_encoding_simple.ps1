# Turkish Character Encoding Fix Script - Simplified Version
# Fixes broken UTF-8 encoding in PHP files

param(
    [string]$FilePath = "backend\dashboard\admin_panel.php"
)

$fullPath = Join-Path $PSScriptRoot $FilePath

if (-not (Test-Path $fullPath)) {
    Write-Host "File not found: $fullPath" -ForegroundColor Red
    exit 1
}

Write-Host "Processing: $FilePath" -ForegroundColor Cyan

# Read file
$content = Get-Content -Path $fullPath -Raw -Encoding UTF8

# Count changes
$changeCount = 0

# Fix common broken patterns
$fixes = @(
    @('Ã„Â±', 'ı'),
    @('Ã…ÂŸ', 'ş'),
    @('Ã„Â', 'ğ'),
    @('ÃƒÂ§', 'ç'),
    @('ÃƒÂ¼', 'ü'),
    @('ÃƒÂ¶', 'ö'),
    @('Ã„Â°', 'İ'),
    @('ÃƒÂž', 'Ş'),
    @('ÃƒÂ‡', 'Ç'),
    @('Ã„Å¾', 'Ğ'),
    @('ÃƒÂœ', 'Ü'),
    @('ÃƒÂ–', 'Ö'),
    @('Ã¢Â‚Âº', '₺'),
    @('â‚º', '₺'),
    @('âœ', '✓'),
    @('â„¹', 'ℹ'),
    @('âš', '⚠')
)

foreach ($fix in $fixes) {
    $pattern = $fix[0]
    $replacement = $fix[1]
    if ($content -match [regex]::Escape($pattern)) {
        $content = $content -replace [regex]::Escape($pattern), $replacement
        $changeCount++
    }
}

# Write back with UTF-8 no BOM
$utf8NoBom = New-Object System.Text.UTF8Encoding $false
[System.IO.File]::WriteAllText($fullPath, $content, $utf8NoBom)

Write-Host "Fixed $changeCount encoding patterns" -ForegroundColor Green
Write-Host "File saved with UTF-8 encoding (no BOM)" -ForegroundColor Green

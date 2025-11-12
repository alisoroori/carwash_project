# Verify HTML/CSS Fixes
# Checks that all issues have been resolved

param(
    [string]$RootPath = "."
)

Set-Location $RootPath

Write-Host "=== Verification Report ===" -ForegroundColor Cyan
Write-Host ""

# Exclusion patterns
$excludeDirs = @('vendor', 'node_modules', 'dist', 'tools', 'tests', '.venv', 'reports', 'frontend\packages', '.git', 'logs', 'uploads')

function Get-TargetFiles {
    param([string]$pattern)
    $files = Get-ChildItem -Path $RootPath -Filter $pattern -Recurse -File | Where-Object {
        $path = $_.FullName
        $exclude = $false
        foreach ($dir in $excludeDirs) {
            if ($path -like "*\$dir\*") {
                $exclude = $true
                break
            }
        }
        return -not $exclude
    }
    return $files
}

# ============================================================================
# CHECK 1: id="$id" literals
# ============================================================================

Write-Host "Check 1: Searching for id=`"`$id`" literals..." -ForegroundColor Yellow

$htmlFiles = Get-TargetFiles -pattern "*.php"
$htmlFiles += Get-TargetFiles -pattern "*.html"

$foundIssues = 0
foreach ($file in $htmlFiles) {
    $content = [System.IO.File]::ReadAllText($file.FullName, [System.Text.Encoding]::UTF8)
    
    if ($content -match 'id=["'']?\$id["'']?') {
        Write-Host "  FOUND: $($file.FullName)" -ForegroundColor Red
        $foundIssues++
    }
}

if ($foundIssues -eq 0) {
    Write-Host "  [OK] No id=`"`$id`" literals found in main source files" -ForegroundColor Green
} else {
    Write-Host "  [ERROR] Found $foundIssues files with id=`"`$id`" literals" -ForegroundColor Red
}
Write-Host ""

# ============================================================================
# CHECK 2: backdrop-filter order
# ============================================================================

Write-Host "Check 2: Checking backdrop-filter property order..." -ForegroundColor Yellow

$cssFiles = Get-TargetFiles -pattern "*.css"

$foundIssues = 0
foreach ($file in $cssFiles) {
    $content = [System.IO.File]::ReadAllText($file.FullName, [System.Text.Encoding]::UTF8)
    
    # Check if backdrop-filter comes BEFORE -webkit-backdrop-filter (wrong order)
    if ($content -match 'backdrop-filter:[^;]+;[\s\r\n]+-webkit-backdrop-filter:') {
        Write-Host "  FOUND: $($file.FullName) - backdrop-filter before -webkit-backdrop-filter" -ForegroundColor Red
        $foundIssues++
    }
}

if ($foundIssues -eq 0) {
    Write-Host "  [OK] All backdrop-filter properties properly ordered" -ForegroundColor Green
} else {
    Write-Host "  [ERROR] Found $foundIssues files with wrong backdrop-filter order" -ForegroundColor Red
}
Write-Host ""

# ============================================================================
# CHECK 3: text-size-adjust support
# ============================================================================

Write-Host "Check 3: Checking for -webkit-text-size-adjust without standard property..." -ForegroundColor Yellow

$cssFiles = Get-TargetFiles -pattern "*.css"

$foundIssues = 0
foreach ($file in $cssFiles) {
    $content = [System.IO.File]::ReadAllText($file.FullName, [System.Text.Encoding]::UTF8)
    
    # Find all -webkit-text-size-adjust
    $webkitMatches = [regex]::Matches($content, '-webkit-text-size-adjust:\s*([^;]+);')
    
    foreach ($m in $webkitMatches) {
        # Check if the standard property appears shortly after (allow inline or newline)
        $afterMatch = $content.Substring($m.Index + $m.Length, [Math]::Min(200, $content.Length - ($m.Index + $m.Length)))
        if ($afterMatch -notmatch 'text-size-adjust:') {
            Write-Host "  FOUND: $($file.FullName) - missing text-size-adjust after -webkit-text-size-adjust" -ForegroundColor Red
            $foundIssues++
            break
        }
    }
}

if ($foundIssues -eq 0) {
    Write-Host "  [OK] All -webkit-text-size-adjust have corresponding text-size-adjust" -ForegroundColor Green
} else {
    Write-Host "  [ERROR] Found $foundIssues files missing text-size-adjust" -ForegroundColor Red
}
Write-Host ""

# ============================================================================
# CHECK 4: Meta charset/viewport in body
# ============================================================================

Write-Host "Check 4: Checking for meta charset/viewport in body tag..." -ForegroundColor Yellow

$foundIssues = 0
foreach ($file in $htmlFiles) {
    $content = [System.IO.File]::ReadAllText($file.FullName, [System.Text.Encoding]::UTF8)
    
    # Simple check: look for body tag containing meta
    $bodyPattern = '<body[^>]*>.*<meta\s+(charset|name=["'']viewport["''])'
    if ($content -match $bodyPattern) {
        Write-Host "  FOUND: $($file.FullName) - meta charset/viewport in body" -ForegroundColor Red
        $foundIssues++
    }
}

if ($foundIssues -eq 0) {
    Write-Host "  [OK] No meta charset/viewport found in body tags" -ForegroundColor Green
} else {
    Write-Host "  [ERROR] Found $foundIssues files with meta in body" -ForegroundColor Red
}
Write-Host ""

# ============================================================================
# Summary
# ============================================================================

Write-Host "=== Summary ===" -ForegroundColor Cyan
Write-Host "All primary HTML/CSS compatibility issues have been checked." -ForegroundColor White
Write-Host ""
Write-Host "Remaining tasks (optional):" -ForegroundColor Gray
Write-Host "  - Review scrollbar-width Safari fallbacks (already completed previously)" -ForegroundColor Gray
Write-Host "  - Ensure all forms have proper labels (already completed previously)" -ForegroundColor Gray
Write-Host "  - Fix any duplicate ids within forms (already completed previously)" -ForegroundColor Gray
Write-Host ""

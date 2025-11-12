# Final HTML/CSS Validation Report
# Comprehensive check of all browser compatibility issues

param(
    [string]$RootPath = "."
)

$ErrorActionPreference = "Stop"
Set-Location $RootPath

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

Write-Host "=== Final HTML/CSS Validation Report ===" -ForegroundColor Cyan
Write-Host "Generated: $(Get-Date -Format 'yyyy-MM-dd HH:mm:ss')" -ForegroundColor Gray
Write-Host ""

$allPassed = $true

# ============================================================================
# 1. Check: text-size-adjust support
# ============================================================================

Write-Host "1. Checking text-size-adjust cross-browser support..." -ForegroundColor Yellow

$cssFiles = Get-TargetFiles -pattern "*.css"
$textSizeIssues = @()

foreach ($file in $cssFiles) {
    $content = [System.IO.File]::ReadAllText($file.FullName, [System.Text.Encoding]::UTF8)
    
    # Check for -webkit-text-size-adjust
    if ($content -match '-webkit-text-size-adjust:\s*\d+%') {
        # Verify text-size-adjust is also present nearby (within 100 chars)
        $matches = [regex]::Matches($content, '-webkit-text-size-adjust:\s*(\d+%)')
        
        foreach ($m in $matches) {
            $value = $m.Groups[1].Value
            $afterText = $content.Substring($m.Index, [Math]::Min(150, $content.Length - $m.Index))
            
            # Check if standard property exists
            if ($afterText -notmatch "text-size-adjust:\s*$value") {
                $textSizeIssues += $file.FullName
                break
            }
        }
    }
}

if ($textSizeIssues.Count -eq 0) {
    Write-Host "   [OK] All files have proper text-size-adjust support" -ForegroundColor Green
} else {
    Write-Host "   [WARNING] $($textSizeIssues.Count) files may have issues:" -ForegroundColor Yellow
    foreach ($file in $textSizeIssues) {
        Write-Host "     - $file" -ForegroundColor Gray
    }
}
Write-Host ""

# ============================================================================
# 2. Check: backdrop-filter ordering
# ============================================================================

Write-Host "2. Checking backdrop-filter vendor prefix ordering..." -ForegroundColor Yellow

$backdropIssues = @()

foreach ($file in $cssFiles) {
    $content = [System.IO.File]::ReadAllText($file.FullName, [System.Text.Encoding]::UTF8)
    $lines = $content -split "`n"
    
    for ($i = 0; $i -lt ($lines.Count - 1); $i++) {
        $currentLine = $lines[$i].Trim()
        $nextLine = $lines[$i + 1].Trim()
        
        # Check if backdrop-filter appears BEFORE -webkit-backdrop-filter (wrong order)
        if ($currentLine -match '^backdrop-filter:' -and $nextLine -match '^-webkit-backdrop-filter:') {
            $backdropIssues += $file.FullName
            break
        }
    }
}

if ($backdropIssues.Count -eq 0) {
    Write-Host "   [OK] All backdrop-filter properties properly ordered" -ForegroundColor Green
} else {
    Write-Host "   [WARNING] $($backdropIssues.Count) files have wrong order:" -ForegroundColor Yellow
    foreach ($file in $backdropIssues) {
        Write-Host "     - $file" -ForegroundColor Gray
    }
    $allPassed = $false
}
Write-Host ""

# ============================================================================
# 3. Check: scrollbar-width Safari fallback
# ============================================================================

Write-Host "3. Checking scrollbar-width Safari fallback..." -ForegroundColor Yellow

$scrollbarIssues = @()

foreach ($file in $cssFiles) {
    $content = [System.IO.File]::ReadAllText($file.FullName, [System.Text.Encoding]::UTF8)
    
    # Find all scrollbar-width declarations
    $scrollbarMatches = [regex]::Matches($content, '([^\{]+)\{[^\}]*scrollbar-width:\s*[^;]+;[^\}]*\}', 
        [System.Text.RegularExpressions.RegexOptions]::Singleline)
    
    foreach ($m in $scrollbarMatches) {
        $ruleBlock = $m.Value
        
        # Check if ::-webkit-scrollbar exists for Safari
        if ($ruleBlock -notmatch '::-webkit-scrollbar') {
            # Extract selector
            if ($ruleBlock -match '^([^\{]+)\{') {
                $selector = $matches[1].Trim()
                
                # Check if there's a separate ::-webkit-scrollbar rule nearby
                $escapedSelector = [regex]::Escape($selector)
                if ($content -notmatch "$escapedSelector\s*::-webkit-scrollbar") {
                    $scrollbarIssues += "$($file.Name) - Selector: $selector"
                }
            }
        }
    }
}

if ($scrollbarIssues.Count -eq 0) {
    Write-Host "   [OK] All scrollbar-width have Safari fallback" -ForegroundColor Green
} else {
    Write-Host "   [INFO] $($scrollbarIssues.Count) potential scrollbar issues (may be acceptable):" -ForegroundColor Cyan
    foreach ($issue in $scrollbarIssues | Select-Object -First 5) {
        Write-Host "     - $issue" -ForegroundColor Gray
    }
}
Write-Host ""

# ============================================================================
# 4. Check: DOCTYPE declarations
# ============================================================================

Write-Host "4. Checking DOCTYPE declarations (Quirks Mode prevention)..." -ForegroundColor Yellow

$htmlFiles = Get-TargetFiles -pattern "*.php"
$htmlFiles += Get-TargetFiles -pattern "*.html"

$doctypeIssues = @()

foreach ($file in $htmlFiles) {
    $content = [System.IO.File]::ReadAllText($file.FullName, [System.Text.Encoding]::UTF8)
    
    # Check if file has HTML structure
    if ($content -match '<html|<head|<body') {
        # Check for DOCTYPE
        if ($content -notmatch '<!DOCTYPE\s+html>|<!doctype\s+html>') {
            $doctypeIssues += $file.FullName
        }
    }
}

if ($doctypeIssues.Count -eq 0) {
    Write-Host "   [OK] All HTML files have DOCTYPE declarations" -ForegroundColor Green
} else {
    Write-Host "   [WARNING] $($doctypeIssues.Count) files missing DOCTYPE:" -ForegroundColor Yellow
    foreach ($file in $doctypeIssues | Select-Object -First 5) {
        Write-Host "     - $file" -ForegroundColor Gray
    }
    $allPassed = $false
}
Write-Host ""

# ============================================================================
# 5. Check: Meta charset/viewport in head
# ============================================================================

Write-Host "5. Checking meta charset/viewport placement..." -ForegroundColor Yellow

$metaInBodyIssues = @()

foreach ($file in $htmlFiles) {
    $content = [System.IO.File]::ReadAllText($file.FullName, [System.Text.Encoding]::UTF8)
    
    # Simple check: Look for body followed by meta charset or meta name viewport
    # This is a simplified check - may have false positives with complex templates
    $bodyMetaPattern = '<body[^>]*>[\s\S]*?<meta\s+(charset|name=["\x27]viewport["\x27])'
    if ($content -match $bodyMetaPattern) {
        $metaInBodyIssues += $file.Name
    }
}

if ($metaInBodyIssues.Count -eq 0) {
    Write-Host "   [OK] No meta charset/viewport found in body" -ForegroundColor Green
} else {
    Write-Host "   [INFO] $($metaInBodyIssues.Count) files may have meta in body (requires manual verification):" -ForegroundColor Cyan
    foreach ($file in $metaInBodyIssues | Select-Object -First 5) {
        Write-Host "     - $file" -ForegroundColor Gray
    }
}
Write-Host ""

# ============================================================================
# 6. Check: Form labels
# ============================================================================

Write-Host "6. Checking form field labels..." -ForegroundColor Yellow

$unlabeledControls = 0
$checkedFiles = 0

foreach ($file in $htmlFiles | Select-Object -First 50) {
    $content = [System.IO.File]::ReadAllText($file.FullName, [System.Text.Encoding]::UTF8)
    $checkedFiles++
    
    # Find input/select/textarea not hidden
    $controls = [regex]::Matches($content, '<(input|select|textarea)\b[^>]*>', 
        [System.Text.RegularExpressions.RegexOptions]::IgnoreCase)
    
    foreach ($control in $controls) {
        $tag = $control.Value
        
        # Skip hidden inputs
        $hiddenPattern = 'type=["\x27]hidden["\x27]'
        if ($tag -match $hiddenPattern) {
            continue
        }
        
        # Check for aria-label or aria-labelledby
        if ($tag -match 'aria-label|aria-labelledby') {
            continue
        }
        
        # Check for id and corresponding label
        $idPattern = 'id=["\x27]([^"\x27]+)["\x27]'
        if ($tag -match $idPattern) {
            $id = $matches[1]
            $escapedId = [regex]::Escape($id)
            $labelPattern = '<label[^>]+for=["\x27]' + $escapedId + '["\x27]'
            
            if ($content -match $labelPattern) {
                continue
            }
        }
        
        # If we reach here, control may be unlabeled
        $unlabeledControls++
        break
    }
}

if ($unlabeledControls -eq 0) {
    Write-Host "   [OK] No unlabeled form controls found (checked $checkedFiles files)" -ForegroundColor Green
} else {
    Write-Host "   [INFO] Found potential unlabeled controls in sample (checked $checkedFiles files)" -ForegroundColor Cyan
    Write-Host "   Run tools/audit_form_controls_missing_label.ps1 for detailed report" -ForegroundColor Gray
}
Write-Host ""

# ============================================================================
# Summary
# ============================================================================

Write-Host "=== Validation Summary ===" -ForegroundColor Cyan

if ($allPassed -and $doctypeIssues.Count -eq 0) {
    Write-Host "[PASS] All critical validation checks passed!" -ForegroundColor Green
} else {
    Write-Host "[ATTENTION] Some issues require review" -ForegroundColor Yellow
}

Write-Host ""
Write-Host "Quick Reference:" -ForegroundColor White
Write-Host "  - text-size-adjust: Use both -webkit and standard properties" -ForegroundColor Gray
Write-Host "  - backdrop-filter: Place -webkit prefix BEFORE standard property" -ForegroundColor Gray
Write-Host "  - scrollbar-width: Add ::-webkit-scrollbar for Safari" -ForegroundColor Gray
Write-Host "  - DOCTYPE: Always include <!DOCTYPE html> to avoid Quirks Mode" -ForegroundColor Gray
Write-Host "  - Meta tags: Place charset and viewport in <head>, not <body>" -ForegroundColor Gray
Write-Host "  - Form labels: Use <label for='id'> or aria-label for all controls" -ForegroundColor Gray
Write-Host ""

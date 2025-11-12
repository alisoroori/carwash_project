# Fix Remaining HTML/CSS Issues
# - Replace id="$id" with unique ids
# - Ensure backdrop-filter comes after -webkit-backdrop-filter
# - Add text-size-adjust after -webkit-text-size-adjust

param(
    [string]$RootPath = "."
)

$ErrorActionPreference = "Stop"
Set-Location $RootPath

# Exclusion patterns
$excludeDirs = @('vendor', 'node_modules', 'dist', 'tools', 'tests', '.venv', 'reports', 'frontend\packages', '.git', 'logs', 'uploads')

# Counter for unique ids
$global:idCounter = 0

function Get-UniqueId {
    param([string]$baseName = "field")
    $global:idCounter++
    return "${baseName}_${global:idCounter}"
}

# Function to get all files
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

Write-Host "=== Fixing Remaining HTML/CSS Issues ===" -ForegroundColor Cyan
Write-Host ""

# ============================================================================
# TASK 1: Fix id="$id" with unique ids
# ============================================================================

Write-Host "Task 1: Fixing id=`"`$id`" literals..." -ForegroundColor Yellow

$htmlFiles = Get-TargetFiles -pattern "*.php"
$htmlFiles += Get-TargetFiles -pattern "*.html"
$task1Modified = 0

foreach ($file in $htmlFiles) {
    $content = [System.IO.File]::ReadAllText($file.FullName, [System.Text.Encoding]::UTF8)
    $originalContent = $content
    
    # Count occurrences - use simpler patterns
    $pattern1 = 'id="' + '\$id"'
    $pattern2 = "id='" + '\$id' + "'"
    
    $count1 = ([regex]::Matches($content, $pattern1, [System.Text.RegularExpressions.RegexOptions]::IgnoreCase)).Count
    $count2 = ([regex]::Matches($content, $pattern2, [System.Text.RegularExpressions.RegexOptions]::IgnoreCase)).Count
    $totalCount = $count1 + $count2
    
    if ($totalCount -gt 0) {
        Write-Host "  $($file.Name): Found $totalCount occurrences" -ForegroundColor Gray
        
        # Replace each occurrence one by one
        $sb = New-Object System.Text.StringBuilder
        $sb.Append($content) | Out-Null
        
        # Replace id="$id"
        $searchPattern1 = 'id="$id"'
        while ($sb.ToString() -match [regex]::Escape($searchPattern1)) {
            $uniqueId = Get-UniqueId -baseName "auto"
            $currentStr = $sb.ToString()
            $idx = $currentStr.IndexOf($searchPattern1)
            if ($idx -ge 0) {
                $sb.Remove($idx, $searchPattern1.Length) | Out-Null
                $sb.Insert($idx, "id=`"$uniqueId`"") | Out-Null
            } else {
                break
            }
        }
        
        # Replace id='$id'
        $searchPattern2 = "id='`$id'"
        while ($sb.ToString() -match [regex]::Escape($searchPattern2)) {
            $uniqueId = Get-UniqueId -baseName "auto"
            $currentStr = $sb.ToString()
            $idx = $currentStr.IndexOf($searchPattern2)
            if ($idx -ge 0) {
                $sb.Remove($idx, $searchPattern2.Length) | Out-Null
                $sb.Insert($idx, "id=`"$uniqueId`"") | Out-Null
            } else {
                break
            }
        }
        
        $content = $sb.ToString()
        
        # Create backup
        $backupPath = $file.FullName + ".idfix.bak"
        [System.IO.File]::Copy($file.FullName, $backupPath, $true)
        
        # Save
        [System.IO.File]::WriteAllText($file.FullName, $content, [System.Text.Encoding]::UTF8)
        $task1Modified++
    }
}

Write-Host "  Modified: $task1Modified files" -ForegroundColor Green
Write-Host ""

# ============================================================================
# TASK 2: Ensure backdrop-filter order (webkit first)
# ============================================================================

Write-Host "Task 2: Fixing backdrop-filter property order..." -ForegroundColor Yellow

$cssFiles = Get-TargetFiles -pattern "*.css"
$cssFiles += Get-TargetFiles -pattern "*.scss"
$cssFiles += Get-TargetFiles -pattern "*.less"
$task2Modified = 0

foreach ($file in $cssFiles) {
    $content = [System.IO.File]::ReadAllText($file.FullName, [System.Text.Encoding]::UTF8)
    $originalContent = $content
    
    # Pattern: backdrop-filter before -webkit-backdrop-filter (wrong order)
    # Build pattern with concatenation to avoid escaping issues
    $patternBackdrop = 'backdrop-filter:\s*([^;]+);[\s\r\n]+-webkit-backdrop-filter:'
    
    if ($content -match $patternBackdrop) {
        Write-Host "  $($file.Name): Found wrong backdrop-filter order" -ForegroundColor Gray
        
        # Use simpler approach: split, reorder, join
        $lines = $content -split "`n"
        $modified = $false
        
        for ($i = 0; $i -lt ($lines.Count - 1); $i++) {
            $currentLine = $lines[$i]
            $nextLine = $lines[$i + 1]
            
            # Check if current line has backdrop-filter and next has -webkit-backdrop-filter
            if ($currentLine -match 'backdrop-filter:\s*([^;]+);' -and 
                $nextLine -match '^\s*-webkit-backdrop-filter:') {
                # Swap lines
                $lines[$i] = $nextLine
                $lines[$i + 1] = $currentLine
                $modified = $true
            }
        }
        
        if ($modified) {
            $content = $lines -join "`n"
        }
        
        # Create backup
        $backupPath = $file.FullName + ".backdropfix.bak"
        [System.IO.File]::Copy($file.FullName, $backupPath, $true)
        
        # Save
        [System.IO.File]::WriteAllText($file.FullName, $content, [System.Text.Encoding]::UTF8)
        $task2Modified++
    }
}

Write-Host "  Modified: $task2Modified files" -ForegroundColor Green
Write-Host ""

# ============================================================================
# TASK 3: Add text-size-adjust after -webkit-text-size-adjust
# ============================================================================

Write-Host "Task 3: Adding text-size-adjust for cross-browser support..." -ForegroundColor Yellow

$cssFiles = Get-TargetFiles -pattern "*.css"
$task3Modified = 0

foreach ($file in $cssFiles) {
    $content = [System.IO.File]::ReadAllText($file.FullName, [System.Text.Encoding]::UTF8)
    $originalContent = $content
    
    # Pattern: -webkit-text-size-adjust: X; NOT followed by text-size-adjust
    # Look ahead to ensure text-size-adjust is NOT already present on next line
    $pattern = '-webkit-text-size-adjust:\s*([^;]+);'
    
    $foundMatches = [regex]::Matches($content, $pattern)
    
    if ($foundMatches.Count -gt 0) {
        Write-Host "  $($file.Name): Checking $($foundMatches.Count) -webkit-text-size-adjust occurrences" -ForegroundColor Gray
        
        # Check each match to see if standard property follows
        $needsFix = $false
        foreach ($m in $foundMatches) {
            $afterMatch = $content.Substring($m.Index + $m.Length, [Math]::Min(100, $content.Length - ($m.Index + $m.Length)))
            if ($afterMatch -notmatch '^\s*text-size-adjust:') {
                $needsFix = $true
                break
            }
        }
        
        if (-not $needsFix) {
            continue
        }
        
        # Add text-size-adjust after each -webkit-text-size-adjust
        foreach ($m in $foundMatches | Sort-Object -Property Index -Descending) {
            $value = $m.Groups[1].Value
            
            # Check if already followed by text-size-adjust
            $afterMatch = $content.Substring($m.Index + $m.Length, [Math]::Min(100, $content.Length - ($m.Index + $m.Length)))
            if ($afterMatch -match '^\s*text-size-adjust:') {
                continue
            }
            
            $insertPos = $m.Index + $m.Length
            
            # Detect indentation
            $lineStart = $content.LastIndexOf("`n", $m.Index) + 1
            if ($lineStart -eq 0) { $lineStart = 0 }
            $indent = ""
            for ($i = $lineStart; $i -lt $m.Index; $i++) {
                if ($content[$i] -eq ' ' -or $content[$i] -eq "`t") {
                    $indent += $content[$i]
                } else {
                    break
                }
            }
            
            # Insert standard property
            $insertion = [Environment]::NewLine + $indent + "text-size-adjust: $value;"
            $content = $content.Insert($insertPos, $insertion)
        }
        
        # Create backup
        if ($content -ne $originalContent) {
            $backupPath = $file.FullName + ".textsizefix.bak"
            [System.IO.File]::Copy($file.FullName, $backupPath, $true)
            
            # Save
            [System.IO.File]::WriteAllText($file.FullName, $content, [System.Text.Encoding]::UTF8)
            $task3Modified++
        }
    }
}

Write-Host "  Modified: $task3Modified files" -ForegroundColor Green
Write-Host ""

# ============================================================================
# Summary
# ============================================================================

Write-Host "=== Summary ===" -ForegroundColor Cyan
Write-Host "  id=`"`$id`" fixes: $task1Modified files" -ForegroundColor White
Write-Host "  backdrop-filter order: $task2Modified files" -ForegroundColor White
Write-Host "  text-size-adjust added: $task3Modified files" -ForegroundColor White
Write-Host ""

$totalModified = $task1Modified + $task2Modified + $task3Modified

if ($totalModified -gt 0) {
    Write-Host "Total files modified: $totalModified" -ForegroundColor Green
    Write-Host "Backups created with extensions: .idfix.bak, .backdropfix.bak, .textsizefix.bak" -ForegroundColor Gray
} else {
    Write-Host "No issues found!" -ForegroundColor Green
}

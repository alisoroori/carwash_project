<#
Lightweight PowerShell script to add accessible labels/placeholders to form controls in HTML/PHP files.

Usage:
  powershell -NoProfile -ExecutionPolicy Bypass -File .\tools\add_labels_to_form_controls.ps1

This script:
 - Scans .php/.html/.htm files (excludes node_modules, vendor, dist, .git, tests, tools)
 - For each <input|select|textarea> without aria-label or existing label[for], it inserts an sr-only <label> and generates an id if needed
 - Adds placeholders for common text-like inputs when missing
 - Creates a backup of any modified file at file.labelfix.bak
#>

Set-StrictMode -Version Latest

$excludedDirs = @('node_modules','vendor','dist','.git','tests','tools')
$extensions = @('*.php','*.html','*.htm')

$counter = 1

Function Get-TargetFiles {
    Get-ChildItem -Path . -Recurse -File -Include $extensions | Where-Object {
        foreach ($d in $excludedDirs) { if ($_.FullName -like "*\$d\*") { return $false } }
        return $true
    }
}

Function ToFriendlyLabel($raw) {
    if (-not $raw) { return 'Input' }
    $s = $raw -replace '[_\-]+',' '
    $s = [regex]::Replace($s,'([a-z])([A-Z])','$1 $2')
    $s = $s.Trim()
    if ($s.Length -gt 0) { return ($s.Substring(0,1).ToUpper() + $s.Substring(1)) }
    return 'Input'
}

$files = Get-TargetFiles
Write-Host "Scanning $($files.Count) files for form controls..."

foreach ($file in $files) {
    $path = $file.FullName
    $content = Get-Content -Raw -LiteralPath $path
    if (-not $content) { continue }
    $orig = $content

    $regex = [regex] '<(input|select|textarea)\b([^>]*)>'
    $tagMatches = $regex.Matches($content)
    if ($tagMatches.Count -eq 0) { continue }

    for ($i = $tagMatches.Count - 1; $i -ge 0; $i--) {
        $m = $tagMatches[$i]
        $tag = $m.Value
        $attrs = $m.Groups[2].Value

        # heuristic: skip if inside a label
        $startIdx = [Math]::Max(0, $m.Index - 200)
        $contextBefore = $content.Substring($startIdx, [Math]::Min(200, $m.Index - $startIdx))
        if ($contextBefore -match '<label[^>]*>$') { continue }

        # skip if already has aria label
        if ($attrs -match '\baria-label\b|\baria-labelledby\b') { continue }

        # extract id (handles quoted values safely using hex escapes for quotes)
        $id = $null
        $mId = [regex]::Match($attrs, 'id\s*=\s*[\x22\x27]([^\x22\x27]+)[\x22\x27]', [System.Text.RegularExpressions.RegexOptions]::IgnoreCase)
        if ($mId.Success) { $id = $mId.Groups[1].Value }

        # skip if label[for=id] exists
        $hasLabelFor = $false
        if ($id) {
            $escapedId = [regex]::Escape($id)
            $labelForPattern = '<label[^>]*for\s*=\s*[\x22\x27]' + $escapedId + '[\x22\x27][^>]*>'
            if ([regex]::IsMatch($content, $labelForPattern, [System.Text.RegularExpressions.RegexOptions]::IgnoreCase)) { $hasLabelFor = $true }
        }
        if ($hasLabelFor) { continue }

        # build label text
        $labelText = $null
        $mName = [regex]::Match($attrs, 'name\s*=\s*[\x22\x27]([^\x22\x27]+)[\x22\x27]', [System.Text.RegularExpressions.RegexOptions]::IgnoreCase)
        if ($mName.Success) { $labelText = ToFriendlyLabel $mName.Groups[1].Value }
        else {
            $mPlaceholder = [regex]::Match($attrs, 'placeholder\s*=\s*[\x22\x27]([^\x22\x27]+)[\x22\x27]', [System.Text.RegularExpressions.RegexOptions]::IgnoreCase)
            if ($mPlaceholder.Success) { $labelText = $mPlaceholder.Groups[1].Value }
            else {
                $mType = [regex]::Match($attrs, 'type\s*=\s*[\x22\x27]([^\x22\x27]+)[\x22\x27]', [System.Text.RegularExpressions.RegexOptions]::IgnoreCase)
                if ($mType.Success) {
                    switch ($mType.Groups[1].Value.ToLower()) {
                        'email' { $labelText = 'Email' }
                        'tel' { $labelText = 'Phone' }
                        'date' { $labelText = 'Date' }
                        'time' { $labelText = 'Time' }
                        'file' { $labelText = 'Choose file' }
                        'password' { $labelText = 'Password' }
                        Default { $labelText = 'Input' }
                    }
                } else { $labelText = 'Input' }
            }
        }

        # add id if missing
        $newTag = $tag
        if (-not $id) {
            $id = 'auto_label_' + [string]$counter
            $counter++
            if ($newTag -match '/>$') { $newTag = $newTag -replace '/>$', (' id="' + $id + '" />') }
            else { $newTag = $newTag -replace '>$', (' id="' + $id + '">') }
        }

        # add placeholder for text-like inputs
        if ($newTag -match '<input' -and $newTag -notmatch '\bplaceholder=') {
            $mIType = [regex]::Match($newTag, 'type\s*=\s*[\x22\x27]([^\x22\x27]+)[\x22\x27]', [System.Text.RegularExpressions.RegexOptions]::IgnoreCase)
            if ($mIType.Success) { $itype = $mIType.Groups[1].Value.ToLower() } else { $itype = 'text' }
                if ($itype -in @('text','search','email','tel','url','password')) {
                    $ph = $labelText
                    $newTag = $newTag -replace '>$', (' placeholder="' + $ph + '">') 
                }
        }

        # insert sr-only label before control
        $labelHtml = '<label for="' + $id + '" class="sr-only">' + $labelText + '</label>'

        $before = $content.Substring(0, $m.Index)
        $after = $content.Substring($m.Index + $m.Length)
        $content = $before + $labelHtml + $newTag + $after
    }

    if ($content -ne $orig) {
        Copy-Item -LiteralPath $path -Destination ($path + '.labelfix.bak') -Force
        Set-Content -LiteralPath $path -Value $content -Force
        Write-Host "Patched: $path"
    }
}

Write-Host "Done. Added labels/aria and placeholders where applicable. Backups saved with .labelfix.bak"

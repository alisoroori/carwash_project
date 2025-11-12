# Adds aria-label attributes to input/select/textarea elements that lack an associated <label for="id"> and lack aria-label
# Creates a .bak copy before writing changes

$root = Join-Path $PSScriptRoot ".."
$exts = @("*.php","*.html","*.htm","*.tpl","*.inc")
$files = Get-ChildItem -Path $root -Recurse -Include $exts -File -ErrorAction SilentlyContinue |
    Where-Object { $_.FullName -notmatch "\\vendor\\|\\.git\\|\\node_modules\\|\\dist\\|\\tools\\|\\tests\\|\\\.venv\\|\\reports\\|\\frontend\\packages" }
$modified = @()

function Get-Attr([string]$tag, [string]$name) {
    $pattern1 = '\\b' + [regex]::Escape($name) + '\\s*=\\s*"([^\"]*)"'
    $m = [regex]::Match($tag, $pattern1, [System.Text.RegularExpressions.RegexOptions]::IgnoreCase)
    if ($m.Success) { return $m.Groups[1].Value }
    $pattern2 = '\\b' + [regex]::Escape($name) + "\\s*=\\s*'([^']*)'"
    $m = [regex]::Match($tag, $pattern2, [System.Text.RegularExpressions.RegexOptions]::IgnoreCase)
    if ($m.Success) { return $m.Groups[1].Value }
    return $null
}

foreach ($f in $files) {
    try { $text = Get-Content -Raw -LiteralPath $f.FullName -ErrorAction Stop } catch { continue }
    if ([string]::IsNullOrEmpty($text)) { continue }
    $original = $text

    $tagMatches = [regex]::Matches($text, '<(input|select|textarea)\\b[^>]*>', [System.Text.RegularExpressions.RegexOptions]::IgnoreCase)
    if ($tagMatches.Count -eq 0) { continue }

    # For each control, decide whether to add aria-label
    for ($i = $tagMatches.Count - 1; $i -ge 0; $i--) {
        $m = $tagMatches[$i]
        $tag = $m.Value

        # ignore hidden inputs
        $type = Get-Attr $tag 'type'
        if ($type -and $type.ToLower() -eq 'hidden') { continue }

        # find id or name
        $id = Get-Attr $tag 'id'
        $name = Get-Attr $tag 'name'
        $placeholder = Get-Attr $tag 'placeholder'
        $hasAria = ([regex]::IsMatch($tag, '\baria-label\s*=','IgnoreCase')) -or ([regex]::IsMatch($tag, '\baria-labelledby\s*=','IgnoreCase'))

        # If no id and no name, skip (other script should have added an id)
        if (-not $id -and -not $name) { continue }

        # If aria already present, skip
        if ($hasAria) { continue }

        # If a <label for="$id"> exists in the same file, skip
        $labelExists = $false
        if ($id) {
            $escaped = [regex]::Escape($id)
            $p1 = '<label\\b[^>]*for\\s*=\\s*"' + $escaped + '"'
            $p2 = "<label\\b[^>]*for\\s*=\\s*'" + $escaped + "'"
            if ([regex]::IsMatch($text, $p1, [System.Text.RegularExpressions.RegexOptions]::IgnoreCase) -or [regex]::IsMatch($text, $p2, [System.Text.RegularExpressions.RegexOptions]::IgnoreCase)) {
                $labelExists = $true
            }
        }
        if ($labelExists) { continue }

        # Build a value for aria-label: prefer placeholder, then name, then id
        $ariaVal = $null
        if ($placeholder) { $ariaVal = $placeholder }
        elseif ($name) { $ariaVal = $name }
        elseif ($id) { $ariaVal = $id }
        if (-not $ariaVal) { continue }

        # Escape any double quotes in aria value
        $escapedAria = $ariaVal -replace '"', '"'

        # Insert aria-label before the closing >
        $newTag = $tag -replace '(/?>)$', ' aria-label="' + $escapedAria + '"$1'
        $text = $text.Substring(0,$m.Index) + $newTag + $text.Substring($m.Index + $m.Length)
    }

    if ($text -ne $original) {
        Copy-Item -LiteralPath $f.FullName -Destination ($f.FullName + ".bak") -Force
        Set-Content -LiteralPath $f.FullName -Value $text -Encoding UTF8
        $modified += $f.FullName
    }
}

if ($modified.Count -eq 0) { Write-Host "No files modified (aria-labels)" } else { Write-Host "Files modified (aria-labels):"; $modified | ForEach-Object { Write-Host $_ } }

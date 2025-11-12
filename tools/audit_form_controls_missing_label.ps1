# Audits input/select/textarea elements that lack a visible <label for="..."> and lack aria-label
# Prints file, line number and snippet for manual review

$root = Join-Path $PSScriptRoot ".."
$exts = @("*.php","*.html","*.htm","*.tpl","*.inc")
$files = Get-ChildItem -Path $root -Recurse -Include $exts -File -ErrorAction SilentlyContinue |
    Where-Object { $_.FullName -notmatch "\\vendor\\|\\.git\\|\\node_modules\\|\\dist\\|\\tools\\|\\tests\\|\\\.venv\\|\\reports\\|\\frontend\\packages" }

function Get-Attr([string]$tag, [string]$name) {
    $pattern1 = '\\b' + [regex]::Escape($name) + '\\s*=\\s*"([^\"]*)"'
    $m = [regex]::Match($tag, $pattern1, [System.Text.RegularExpressions.RegexOptions]::IgnoreCase)
    if ($m.Success) { return $m.Groups[1].Value }
    $pattern2 = '\\b' + [regex]::Escape($name) + "\\s*=\\s*'([^']*)'"
    $m = [regex]::Match($tag, $pattern2, [System.Text.RegularExpressions.RegexOptions]::IgnoreCase)
    if ($m.Success) { return $m.Groups[1].Value }
    return $null
}

$issues = @()
foreach ($f in $files) {
    try { $text = Get-Content -Raw -LiteralPath $f.FullName -ErrorAction Stop } catch { continue }
    if ([string]::IsNullOrEmpty($text)) { continue }

    $tagMatches = [regex]::Matches($text, '<(input|select|textarea)\\b[^>]*>', [System.Text.RegularExpressions.RegexOptions]::IgnoreCase)
    if ($tagMatches.Count -eq 0) { continue }

    foreach ($m in $tagMatches) {
        $tag = $m.Value
        $type = Get-Attr $tag 'type'
        if ($type -and $type.ToLower() -eq 'hidden') { continue }
        $id = Get-Attr $tag 'id'
        $name = Get-Attr $tag 'name'
        $hasAria = ([regex]::IsMatch($tag, '\baria-label\s*=','IgnoreCase')) -or ([regex]::IsMatch($tag, '\baria-labelledby\s*=','IgnoreCase'))
        if ($hasAria) { continue }
        # If a label exists for id, skip
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
        # record issue: file, line
        $lineNum = ($text.Substring(0, $m.Index) -split "`n").Count
        $snippet = $tag
        $issues += [pscustomobject]@{ File = $f.FullName; Line = $lineNum; Snippet = $snippet }
    }
}

if ($issues.Count -eq 0) {
    Write-Host "No unlabeled controls found."
} else {
    Write-Host "Unlabeled controls found:`n"
    $issues | Format-Table -AutoSize
}

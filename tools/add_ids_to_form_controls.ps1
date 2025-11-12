# Adds unique id attributes to <input>, <select>, and <textarea> elements that lack both id and name
# Creates a .bak copy before writing changes

$root = Join-Path $PSScriptRoot ".."
$exts = @("*.php","*.html","*.htm","*.tpl","*.inc")
$files = Get-ChildItem -Path $root -Recurse -Include $exts -File -ErrorAction SilentlyContinue |
    Where-Object { $_.FullName -notmatch "\\vendor\\|\\.git\\|\\node_modules\\|\\dist\\|\\tools\\|\\tests\\|\\\.venv\\|\\reports\\|\\frontend\\packages" }
$globalCounter = 1
$modified = @()

foreach ($f in $files) {
    try {
        $text = Get-Content -Raw -LiteralPath $f.FullName -ErrorAction Stop
    } catch { continue }
    if ([string]::IsNullOrEmpty($text)) { continue } # skip empty/binary files
    $original = $text

    # Helper to insert id into a tag match at a position; do replacements from last match to first to preserve indices
    function Update-FormTags([string]$inputText, [System.Text.RegularExpressions.MatchCollection]$matchCollection, [string]$fileBase) {
        $out = $inputText
        for ($i = $matchCollection.Count - 1; $i -ge 0; $i--) {
            $m = $matchCollection[$i]
            $tag = $m.Value
            if ($tag -notmatch '\b(id|name)\s*=') {
                $id = "auto_" + ($fileBase -replace '[^a-zA-Z0-9]', '_') + "_" + $script:globalCounter
                $script:globalCounter = $script:globalCounter + 1
                $newTag = $tag -replace '(/?>)$', ' id="$id"$1'
                $out = $out.Substring(0,$m.Index) + $newTag + $out.Substring($m.Index + $m.Length)
            }
        }
        return $out
    }

    # Inputs
    $inpMatches = [regex]::Matches($text, '<input\b[^>]*>', [System.Text.RegularExpressions.RegexOptions]::IgnoreCase)
    if ($inpMatches.Count -gt 0) {
        $text = Update-FormTags $text $inpMatches $f.BaseName
    }

    # Selects
    $selMatches = [regex]::Matches($text, '<select\b[^>]*>', [System.Text.RegularExpressions.RegexOptions]::IgnoreCase)
    if ($selMatches.Count -gt 0) {
        $text = Update-FormTags $text $selMatches $f.BaseName
    }

    # Textareas
    $taMatches = [regex]::Matches($text, '<textarea\b[^>]*>', [System.Text.RegularExpressions.RegexOptions]::IgnoreCase)
    if ($taMatches.Count -gt 0) {
        $text = Update-FormTags $text $taMatches $f.BaseName
    }

    if ($text -ne $original) {
        Copy-Item -LiteralPath $f.FullName -Destination ($f.FullName + ".bak") -Force
        Set-Content -LiteralPath $f.FullName -Value $text -Encoding UTF8
        $modified += $f.FullName
    }
}

if ($modified.Count -eq 0) {
    Write-Host "No files modified"
} else {
    Write-Host "Modified files:`n"
    $modified | ForEach-Object { Write-Host $_ }
}

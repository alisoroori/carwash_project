# Fix duplicate id attributes inside each <form> in PHP/HTML files.
# For any form that contains repeated id values among input/select/textarea elements,
# the script will make subsequent occurrences unique by appending _2, _3, etc.
# It also updates local <label for="..."> and aria-labelledby references inside the same form.
# Creates a .dupfix.bak backup before writing changes.

$root = Join-Path $PSScriptRoot ".."
$exts = @("*.php","*.html","*.htm","*.tpl","*.inc")
$files = Get-ChildItem -Path $root -Recurse -Include $exts -File -ErrorAction SilentlyContinue |
    Where-Object { $_.FullName -notmatch "\\vendor\\|\\.git\\|\\node_modules\\|\\dist\\|\\tools\\|\\tests\\|\\\.venv\\|\\reports\\|\\frontend\\packages" }

$modified = @()

# Helper to get attribute value (double or single quoted) from a tag
function Get-AttrVal([string]$tag, [string]$attr) {
    $p1 = '\\b' + [regex]::Escape($attr) + '\\s*=\\s*"([^"]*)"'
    $m = [regex]::Match($tag, $p1, [System.Text.RegularExpressions.RegexOptions]::IgnoreCase)
    if ($m.Success) { return $m.Groups[1].Value }
    $p2 = '\\b' + [regex]::Escape($attr) + "\\s*=\\s*'([^']*)'"
    $m = [regex]::Match($tag, $p2, [System.Text.RegularExpressions.RegexOptions]::IgnoreCase)
    if ($m.Success) { return $m.Groups[1].Value }
    return $null
}

foreach ($f in $files) {
    try { $content = Get-Content -Raw -LiteralPath $f.FullName -ErrorAction Stop } catch { continue }
    if ([string]::IsNullOrEmpty($content)) { continue }
    $original = $content
    $fileChanged = $false

    # Find forms (singleline to allow newlines inside form)
    $formPattern = '<form\\b[^>]*>(.*?)</form>'
    $formMatches = [regex]::Matches($content, $formPattern, [System.Text.RegularExpressions.RegexOptions]::IgnoreCase -bor [System.Text.RegularExpressions.RegexOptions]::Singleline)
    if ($formMatches.Count -eq 0) { continue }

    # We'll collect replacements per file
    $fileReplacements = New-Object System.Collections.ArrayList

    for ($fi = 0; $fi -lt $formMatches.Count; $fi++) {
        $formMatch = $formMatches[$fi]
        $formText = $formMatch.Groups[1].Value  # inner HTML
        $formStartIndex = $formMatch.Index

        # Find controls inside the form
        $controlPattern = '<(input|select|textarea)\\b[^>]*>'
        $controlMatches = [regex]::Matches($formText, $controlPattern, [System.Text.RegularExpressions.RegexOptions]::IgnoreCase)
        if ($controlMatches.Count -eq 0) { continue }

        # Map id to list of match indexes
        $idMap = @{}
        for ($ci = 0; $ci -lt $controlMatches.Count; $ci++) {
            $cm = $controlMatches[$ci]
            $tag = $cm.Value
            $type = Get-AttrVal $tag 'type'
            if ($type -and $type.ToLower() -eq 'hidden') { continue }
            $id = Get-AttrVal $tag 'id'
            if ($null -ne $id -and $id -ne '') {
                if (-not $idMap.ContainsKey($id)) { $idMap[$id] = @() }
                $idMap[$id] += [pscustomobject]@{Index = $ci; Tag = $tag; Match = $cm }
            }
        }

        # For each id with duplicates, prepare replacements
        foreach ($kv in $idMap.GetEnumerator()) {
            $idVal = $kv.Key
            $occ = $kv.Value
            if ($occ.Count -le 1) { continue }

            # Keep first occurrence, rename subsequent ones
            for ($k = 1; $k -lt $occ.Count; $k++) {
                $entry = $occ[$k]
                # New id suffix
                $newId = "${idVal}_" + ($k + 1)

                # Replace id attribute in the specific tag occurrence inside formText
                $cm = $entry.Match
                $tagText = $entry.Tag
                # create new tag text replacing id="..." or id='...'
                if ($tagText -match '\bid\s*=\s*"') {
                    $newTagText = [regex]::Replace($tagText, '(?i)\bid\s*=\s*"[^"]*"', 'id="' + $newId + '"', 1)
                } else {
                    $newTagText = [regex]::Replace($tagText, "(?i)\\bid\\s*=\\s*'[^']*'", "id='$newId'", 1)
                }

                # Prepare to replace the specific match by index: we'll compute position
                $matchIndex = $cm.Index
                $matchLen = $cm.Length

                # We'll store a replacement object containing form local index and new text
                $fileReplacements.Add([pscustomobject]@{
                    FormMatchIndex = $formStartIndex
                    FormInnerStart = $formMatch.Groups[1].Index
                    ControlMatchIndex = $matchIndex
                    ControlMatchLength = $matchLen
                    NewControlText = $newTagText
                    OldId = $idVal
                    NewId = $newId
                    FormFullMatch = $formMatch.Value
                }) | Out-Null

                # Also update any <label for=oldId> inside this form and aria-labelledby occurrences
                # We'll do label/aria updates later per form when applying replacements
            }
        }

        # If we prepared replacements for this form, mark as changed
    }

    if ($fileReplacements.Count -eq 0) { continue }

    # Apply replacements grouped by form, doing replacements inside form inner HTML from last to first
    # Convert content to a mutable string builder
    $sb = New-Object System.Text.StringBuilder $content

    # Group replacements by FormMatchIndex (which is absolute index of form in file)
    $groups = $fileReplacements | Group-Object -Property FormMatchIndex
    foreach ($g in $groups) {
        $formMatchIndex = [int]$g.Name
        $reps = $g.Group | Sort-Object -Property ControlMatchIndex -Descending

        # Need to find formMatch.Groups[1].Index (inner start) - we passed FormInnerStart but it's index relative to full file - use that
        $formInnerStart = [int]$reps[0].FormInnerStart

        # Apply control replacements in descending ControlMatchIndex to preserve offsets
        foreach ($r in $reps) {
            $absPos = $formInnerStart + [int]$r.ControlMatchIndex
            # Replace in StringBuilder
            $sb.Remove($absPos, [int]$r.ControlMatchLength) | Out-Null
            $sb.Insert($absPos, $r.NewControlText) | Out-Null
            $fileChanged = $true

            # Also update labels and aria-labelledby within the same form region
            # We'll operate on the current sb substring for the form to update labels/aria
        }

        # After applying control id changes, update label for= and aria-labelledby inside the form region
        # Recompute form region boundaries: find the closing </form> after formMatchIndex
        $contentAfter = $sb.ToString()
        $formRegex = [regex]::Match($contentAfter, '<form\\b[^>]*>','IgnoreCase')
        # Instead, better find the specific form by searching around formMatchIndex: find the next </form>
        $searchStart = $formMatchIndex
        $endFormIdx = $contentAfter.IndexOf('</form>', $searchStart, [StringComparison]::InvariantCultureIgnoreCase)
        if ($endFormIdx -lt 0) { $endFormIdx = ($contentAfter.Length - 1) }
        $formEnd = $endFormIdx + 8
        $formRegion = $contentAfter.Substring($formMatchIndex, $formEnd - $formMatchIndex)

        # For each replacement related to this form, update labels and aria-labelledby
        foreach ($r in $reps) {
            $old = [regex]::Escape($r.OldId)
            $new = $r.NewId
            # label for="old"
            $formRegion = [regex]::Replace($formRegion, '(?i)(<label\\b[^>]*for\\s*=\\s*")' + $old + '(")', '$1' + $new + '$2')
            $formRegion = [regex]::Replace($formRegion, "(?i)(<label\\b[^>]*for\\s*=\\s*')" + $old + "(')", '$1' + $new + '$2')
            # aria-labelledby may contain multiple ids; replace occurrences of old id boundaries
            $formRegion = [regex]::Replace($formRegion, '(?i)\baria-labelledby\s*=\s*"([^"]*)"', { param($m) $val = $m.Groups[1].Value; $newVal = [regex]::Replace($val, '(?<!\S)'+$old+'(?!\S)', $new); return 'aria-labelledby="' + $newVal + '"' })
            $formRegion = [regex]::Replace($formRegion, "(?i)\\baria-labelledby\\s*=\\s*'([^']*)'", { param($m) $val = $m.Groups[1].Value; $newVal = [regex]::Replace($val, '(?<!\\S)'+$old+'(?!\\S)', $new); return "aria-labelledby='" + $newVal + "'" })
        }

        # Write updated formRegion back into StringBuilder
        $sb.Remove($formMatchIndex, $formEnd - $formMatchIndex) | Out-Null
        $sb.Insert($formMatchIndex, $formRegion) | Out-Null
    }

    if ($fileChanged) {
        Copy-Item -LiteralPath $f.FullName -Destination ($f.FullName + ".dupfix.bak") -Force
        Set-Content -LiteralPath $f.FullName -Value $sb.ToString() -Encoding UTF8
        $modified += $f.FullName
    }
}

if ($modified.Count -eq 0) { Write-Host "No duplicate ids found inside forms." } else { Write-Host "Modified files (duplicate ids fixed):"; $modified | ForEach-Object { Write-Host $_ } }

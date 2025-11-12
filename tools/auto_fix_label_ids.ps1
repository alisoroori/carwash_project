# Auto-fix label for / id mismatches
# Backs up files with .bak before editing

$files = Get-ChildItem -Path . -Include *.php,*.html -Recurse -File
$fixedFiles = @()
$report = @()
foreach ($f in $files) {
    $path = $f.FullName
    $text = Get-Content -Raw -Path $path -ErrorAction SilentlyContinue
    if (-not $text) { continue }
    $orig = $text
    $changed = $false

    # iterate labels
    $labelRe = [regex] '<label[^>]*\bfor=[\"\'](?<for>[^\"\']+)[\"\'][^>]*>'
    $matches = $labelRe.Matches($text)
    # process matches left-to-right; after a change restart to avoid index issues
    $i = 0
    while ($i -lt $matches.Count) {
        $m = $matches[$i]
        $for = $m.Groups['for'].Value
        if ($for -eq '') { $i++; continue }
        # if id exists anywhere in file, skip
        if ($text -match ('\bid\s*=\s*["\"]' + [regex]::Escape($for) + '["\"]')) { $i++; continue }

        # search for next control after label
        $pos = $m.Index + $m.Length
        $sub = $text.Substring($pos)
        $controlRe = [regex] '<(input|select|textarea)\b(?<attrs>[^>]*)>' , 'Singleline,IgnoreCase'
        $mc = $controlRe.Match($sub)
        if ($mc.Success) {
            $controlTag = $mc.Value
            $controlName = $mc.Groups['attrs'].Value
            # if control already has an id, update label's for to that id
            $idMatch = [regex]::Match($controlTag, '\bid\s*=\s*["\'](?<id>[^"\']+)["\']')
            if ($idMatch.Success) {
                $existingId = $idMatch.Groups['id'].Value
                # replace label's for value with existingId
                $oldLabel = $m.Value
                $newLabel = $oldLabel -replace ('for=[\"\']' + [regex]::Escape($for) + '[\"\']'), ('for="' + $existingId + '"')
                # insert comment above label
                $insertPos = $m.Index
                $comment = "<!-- Fixed label-for/id mismatch for accessibility -->`n"
                $text = $text.Substring(0,$insertPos) + $comment + $text.Substring($insertPos)
                # now replace the first occurrence of oldLabel after insertPos+comment length
                $text = $text -replace [regex]::Escape($oldLabel), [System.Text.RegularExpressions.Regex]::Escape($newLabel), 1
                $changed = $true
                $report += "$path: updated label for '$for' -> '$existingId'"
            } else {
                # add id attribute to control tag
                # compute absolute positions
                $controlStart = $pos + $mc.Index
                $controlLen = $mc.Length
                $originalControl = $text.Substring($controlStart, $controlLen)
                # add id before the closing > (handle self-closing)
                if ($originalControl -match '/>\s*$') {
                    $newControl = $originalControl -replace '/>\s*$', ' id="' + $for + '" />'
                } else {
                    $newControl = $originalControl -replace '>\s*$', ' id="' + $for + '">' 
                }
                # insert comment above control
                $comment = "<!-- Fixed label-for/id mismatch for accessibility -->`n"
                $text = $text.Substring(0,$controlStart) + $comment + $newControl + $text.Substring($controlStart + $controlLen)
                $changed = $true
                $report += "$path: added id '$for' to control following label"
            }
            # restart matching for this file because indices changed
            $matches = $labelRe.Matches($text)
            $i = 0
            continue
        } else {
            # no control found after label; try to find control before label in same form: search back 200 chars
            $backSub = $text.Substring([Math]::Max(0,$m.Index-500), [Math]::Min(500,$m.Index))
            $mc2 = $controlRe.Match($backSub)
            if ($mc2.Success) {
                # similar handling: add id to previous control
                $controlStart = $m.Index - 500 + $mc2.Index
                $controlLen = $mc2.Length
                $originalControl = $text.Substring($controlStart, $controlLen)
                if ($originalControl -match '\bid\s*=') {
                    # control has id but label points elsewhere; update label
                    $idMatch = [regex]::Match($originalControl, '\bid\s*=\s*["\'](?<id>[^"\']+)["\']')
                    if ($idMatch.Success) {
                        $existingId = $idMatch.Groups['id'].Value
                        $oldLabel = $m.Value
                        $newLabel = $oldLabel -replace ('for=[\"\']' + [regex]::Escape($for) + '[\"\']'), ('for="' + $existingId + '"')
                        $insertPos = $m.Index
                        $comment = "<!-- Fixed label-for/id mismatch for accessibility -->`n"
                        $text = $text.Substring(0,$insertPos) + $comment + $text.Substring($insertPos)
                        $text = $text -replace [regex]::Escape($oldLabel), [System.Text.RegularExpressions.Regex]::Escape($newLabel), 1
                        $changed = $true
                        $report += "$path: updated label for '$for' -> '$existingId' (matched previous control)"
                        $matches = $labelRe.Matches($text)
                        $i = 0
                        continue
                    }
                } else {
                    # add id to previous control
                    if ($originalControl -match '/>\s*$') {
                        $newControl = $originalControl -replace '/>\s*$', ' id="' + $for + '" />'
                    } else {
                        $newControl = $originalControl -replace '>\s*$', ' id="' + $for + '">' 
                    }
                    $text = $text.Substring(0,$controlStart) + "<!-- Fixed label-for/id mismatch for accessibility -->`n" + $newControl + $text.Substring($controlStart + $controlLen)
                    $changed = $true
                    $report += "$path: added id '$for' to previous control"
                    $matches = $labelRe.Matches($text)
                    $i = 0
                    continue
                }
            }
            # couldn't find a control to fix; skip
            $report += "$path: couldn't auto-fix label for '$for' (no control found)"
        }
        $i++
    }

    if ($changed) {
        # backup
        $bak = $path + '.bak'
        if (-not (Test-Path $bak)) { Copy-Item -Path $path -Destination $bak -Force }
        Set-Content -Path $path -Value $text -Encoding UTF8
        $fixedFiles += $path
    }
}

# write summary
Write-Output "Auto-fix completed. Files modified: $($fixedFiles.Count)"
foreach ($r in $report) { Write-Output $r }

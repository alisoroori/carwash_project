#!/usr/bin/env pwsh
# Auto-fix label for / id mismatches
# Backs up files with .bak before editing

param(
    [switch]$DryRun
)

$files = Get-ChildItem -Path . -Include *.php,*.html -Recurse -File
$fixedFiles = @()
$report = @()
foreach ($f in $files) {
    $path = $f.FullName
    $text = Get-Content -Raw -Path $path -ErrorAction SilentlyContinue
    if (-not $text) { continue }
    $changed = $false

    # iterate labels
    # compile regex with Singleline and IgnoreCase to match labels across attributes
    $labelRe = [System.Text.RegularExpressions.Regex]::new('<label[^>]*\bfor=[\"\'](?<for>[^\"\']+)[\"\'][^>]*>', [System.Text.RegularExpressions.RegexOptions]::Singleline -bor [System.Text.RegularExpressions.RegexOptions]::IgnoreCase)
    $labelMatches = $labelRe.Matches($text)
    # process matches left-to-right; after a change restart to avoid index issues
    $i = 0
    while ($i -lt $labelMatches.Count) {
        $m = $labelMatches[$i]
        $for = $m.Groups['for'].Value
        if ($for -eq '') { $i++; continue }
        # if id exists anywhere in file, skip
        if ($text -match ('\bid\s*=\s*["\"]' + [regex]::Escape($for) + '["\"]')) { $i++; continue }

        # search for next control after label
        $pos = $m.Index + $m.Length
        $sub = $text.Substring($pos)
        # build control regex (match input/select/textarea tags and capture attributes)
        $controlRe = [System.Text.RegularExpressions.Regex]::new('<(input|select|textarea)\b(?<attrs>[^>]*)>', [System.Text.RegularExpressions.RegexOptions]::Singleline -bor [System.Text.RegularExpressions.RegexOptions]::IgnoreCase)
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
                $searchStart = $insertPos + $comment.Length
                $idx = $text.IndexOf($oldLabel, $searchStart)
                if ($idx -ge 0) {
                    $text = $text.Substring(0, $idx) + $newLabel + $text.Substring($idx + $oldLabel.Length)
                } else {
                    # fallback: replace first global occurrence (use overload compatible with legacy PowerShell)
                    $r = [System.Text.RegularExpressions.Regex]::new([System.Text.RegularExpressions.Regex]::Escape($oldLabel))
                    $text = $r.Replace($text, $newLabel, 1)
                }
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
            $labelMatches = $labelRe.Matches($text)
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
                        $searchStart = $insertPos + $comment.Length
                        $idx = $text.IndexOf($oldLabel, $searchStart)
                        if ($idx -ge 0) {
                            $text = $text.Substring(0, $idx) + $newLabel + $text.Substring($idx + $oldLabel.Length)
                        } else {
                            $r = [System.Text.RegularExpressions.Regex]::new([System.Text.RegularExpressions.Regex]::Escape($oldLabel))
                            $text = $r.Replace($text, $newLabel, 1)
                        }
                        $changed = $true
                        $report += "$path: updated label for '$for' -> '$existingId' (matched previous control)"
                        $labelMatches = $labelRe.Matches($text)
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
                    $labelMatches = $labelRe.Matches($text)
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
        if ($DryRun) {
            Write-Output "DRYRUN: would modify $path"
            $fixedFiles += $path
        } else {
            # backup
            $bak = $path + '.bak'
            if (-not (Test-Path $bak)) { Copy-Item -Path $path -Destination $bak -Force }
            Set-Content -Path $path -Value $text -Encoding UTF8
            $fixedFiles += $path
        }
    }
}

# write summary
Write-Output "Auto-fix completed. Files modified: $($fixedFiles.Count)"
foreach ($r in $report) { Write-Output $r }

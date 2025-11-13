#!/usr/bin/env pwsh
# Auto-fix label for / id mismatches
# Backs up files with .bak before editing

param(
    [switch]$DryRun
)

$files = Get-ChildItem -Path . -Include *.php,*.html -Recurse -File
$fixedFiles = @()
$report = @()

# predefine regex options
$opts = [System.Text.RegularExpressions.RegexOptions]::Singleline -bor [System.Text.RegularExpressions.RegexOptions]::IgnoreCase
$labelPattern = '<label[^>]*\bfor=["\'](?<for>[^"\']+)["\'][^>]*>'
$controlPattern = '<(input|select|textarea)\b(?<attrs>[^>]*)>'

foreach ($f in $files) {
    $path = $f.FullName
    $text = Get-Content -Raw -Path $path -ErrorAction SilentlyContinue
    if (-not $text) { continue }
    $changed = $false

    $labelRe = [System.Text.RegularExpressions.Regex]::new($labelPattern, $opts)
    $controlRe = [System.Text.RegularExpressions.Regex]::new($controlPattern, $opts)

    $labelMatches = $labelRe.Matches($text)
    $i = 0
    while ($i -lt $labelMatches.Count) {
        $m = $labelMatches[$i]
        $for = $m.Groups['for'].Value
        if ($for -eq '') { $i++; continue }

        if ($text -match ("\\bid\\s*=\\s*[\"\']" + [regex]::Escape($for) + "[\"\']")) { $i++; continue }

        $pos = $m.Index + $m.Length
        $sub = $text.Substring($pos)
        $mc = $controlRe.Match($sub)
        if ($mc.Success) {
            $controlStart = $pos + $mc.Index
            $controlLen = $mc.Length
            $controlTag = $mc.Value

            $idMatch = [regex]::Match($controlTag, '\\bid\\s*=\\s*[\"\'](?<id>[^\"\']+)[\"\']')
            if ($idMatch.Success) {
                $existingId = $idMatch.Groups['id'].Value
                $oldLabel = $m.Value
                $newLabel = $oldLabel -replace ('for=[\"\']' + [regex]::Escape($for) + '[\"\']'), ('for="' + $existingId + '"')

                $insertPos = $m.Index
                $comment = '<!-- Fixed label-for/id mismatch for accessibility -->`n'
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
                $report += ($path + ': updated label for \'' + $for + '\' -> \'' + $existingId + '\'')
            } else {
                $originalControl = $text.Substring($controlStart, $controlLen)
                if ($originalControl -match '/>\s*$') {
                    $newControl = $originalControl -replace '/>\s*$', ' id="' + $for + '" />'
                } else {
                    $newControl = $originalControl -replace '>\s*$', ' id="' + $for + '">' 
                }
                $comment = '<!-- Fixed label-for/id mismatch for accessibility -->`n'
                $text = $text.Substring(0,$controlStart) + $comment + $newControl + $text.Substring($controlStart + $controlLen)
                $changed = $true
                $report += ($path + ": added id '" + $for + "' to control following label")
            }

            # restart matching
            $labelMatches = $labelRe.Matches($text)
            $i = 0
            continue
        } else {
            # look back for previous control (search up to 500 chars)
            $startBack = [Math]::Max(0,$m.Index-500)
            $backLen = [Math]::Min(500,$m.Index - $startBack)
            if ($backLen -le 0) { $report += ($path + ": couldn't auto-fix label for '" + $for + "' (no control found)"); $i++; continue }
            $backSub = $text.Substring($startBack, $backLen)
            $mc2 = $controlRe.Match($backSub)
            if ($mc2.Success) {
                $controlStart = $startBack + $mc2.Index
                $controlLen = $mc2.Length
                $originalControl = $text.Substring($controlStart, $controlLen)
                if ($originalControl -match '\\bid\\s*=') {
                    $idMatch = [regex]::Match($originalControl, '\\bid\\s*=\\s*[\"\'](?<id>[^\"\']+)[\"\']')
                    if ($idMatch.Success) {
                        $existingId = $idMatch.Groups['id'].Value
                        $oldLabel = $m.Value
                        $newLabel = $oldLabel -replace ('for=[\"\']' + [regex]::Escape($for) + '[\"\']'), ('for="' + $existingId + '"')

                        $insertPos = $m.Index
                        $comment = '<!-- Fixed label-for/id mismatch for accessibility -->`n'
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
                        $report += ($path + ": updated label for '" + $for + "' -> '" + $existingId + "' (matched previous control)")
                        $labelMatches = $labelRe.Matches($text)
                        $i = 0
                        continue
                    }
                } else {
                    if ($originalControl -match '/>\s*$') {
                        $newControl = $originalControl -replace '/>\s*$', ' id="' + $for + '" />'
                    } else {
                        $newControl = $originalControl -replace '>\s*$', ' id="' + $for + '">' 
                    }
                    $text = $text.Substring(0,$controlStart) + '<!-- Fixed label-for/id mismatch for accessibility -->`n' + $newControl + $text.Substring($controlStart + $controlLen)
                    $changed = $true
                    $report += ($path + ": added id '" + $for + "' to previous control")
                    $labelMatches = $labelRe.Matches($text)
                    $i = 0
                    continue
                }
            }
            $report += ($path + ": couldn't auto-fix label for '" + $for + "' (no control found)")
        }
        $i++
    }

    if ($changed) {
        if ($DryRun) {
            Write-Output "DRYRUN: would modify $path"
            $fixedFiles += $path
        } else {
            $bak = $path + '.bak'
            if (-not (Test-Path $bak)) { Copy-Item -Path $path -Destination $bak -Force }
            Set-Content -Path $path -Value $text -Encoding UTF8
            $fixedFiles += $path
        }
    }
}

Write-Output "Auto-fix completed. Files modified: $($fixedFiles.Count)"
foreach ($r in $report) { Write-Output $r }

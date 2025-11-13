#!/usr/bin/env pwsh
# Auto-fix label-for / id mismatches - final
# - Cross-compatible with Windows PowerShell and pwsh
# - DryRun support, .bak backups before apply
# - Skips PHP blocks (does not edit inside <?php ... ?>)
# - Inserts an HTML comment above each fix

param(
    [switch]$DryRun
)

Set-StrictMode -Version Latest

function Split-PHPBlocks {
    param([string]$Text)
    $tokens = @{}
    $working = $Text
    $phpPattern = @'
<\?(?:php)?[\s\S]*?\?>
'@
    $opts = [System.Text.RegularExpressions.RegexOptions]::IgnoreCase -bor [System.Text.RegularExpressions.RegexOptions]::Singleline
    $matches = [System.Text.RegularExpressions.Regex]::Matches($Text, $phpPattern, $opts)
    for ($i = $matches.Count - 1; $i -ge 0; $i--) {
        $m = $matches[$i]
        $token = "__PHPBLOCK_$i__"
        $tokens[$token] = $m.Value
        $working = $working.Substring(0, $m.Index) + $token + $working.Substring($m.Index + $m.Length)
    }
    return @{ Working = $working; Tokens = $tokens }
}

function Restore-PHPBlocks {
    param([string]$Text, [hashtable]$Tokens)
    $out = $Text
    foreach ($k in $Tokens.Keys) {
        $out = $out.Replace($k, $Tokens[$k])
    }
    return $out
}

function Get-AllIds {
    param([string]$Text)
    $ids = @{}
    $idPattern = @'
\bid\s*=\s*["']([^"']+)["']
'@
    $idRe = [System.Text.RegularExpressions.Regex]::new($idPattern, [System.Text.RegularExpressions.RegexOptions]::IgnoreCase)
    $m = $idRe.Matches($Text)
    foreach ($mm in $m) {
        $val = $mm.Groups[1].Value
        if ($ids.ContainsKey($val)) { $ids[$val]++ } else { $ids[$val] = 1 }
    }
    return $ids
}

function Make-UniqueId {
    param([string]$base, [hashtable]$existing)
    $candidate = $base
    $i = 1
    while ($existing.ContainsKey($candidate)) {
        $candidate = "${base}-$i"
        $i++
    }
    $existing[$candidate] = 1
    return $candidate
}

# precompile regex objects using here-strings to avoid quoting pitfalls
$labelPattern = @'
<label[^>]*\bfor=["'](?<for>[^"']+)["'][^>]*>
'@
$controlPattern = @'
<(input|select|textarea)\b(?<attrs>[^>]*)>
'@
$opts = [System.Text.RegularExpressions.RegexOptions]::IgnoreCase -bor [System.Text.RegularExpressions.RegexOptions]::Singleline
$labelRe = [System.Text.RegularExpressions.Regex]::new($labelPattern, $opts)
$controlRe = [System.Text.RegularExpressions.Regex]::new($controlPattern, $opts)
# id and for attribute regexes (precompiled to avoid quoting pitfalls)
$idPatternGlobal = @'
\bid\s*=\s*["']([^"']+)["']
'@
$idReGlobal = [System.Text.RegularExpressions.Regex]::new($idPatternGlobal, $opts)
$forPatternGlobal = @'
for\s*=\s*["']([^"']+)["']
'@
$forReGlobal = [System.Text.RegularExpressions.Regex]::new($forPatternGlobal, [System.Text.RegularExpressions.RegexOptions]::IgnoreCase)

$files = Get-ChildItem -Path . -Include *.php,*.html -Recurse -File
$fixedFiles = @()
$report = @()

foreach ($f in $files) {
    $path = $f.FullName
    try {
        $orig = Get-Content -Raw -Path $path -ErrorAction Stop
    } catch {
        Write-Output ("SKIP: could not read " + $path + ": " + $_.Exception.Message)
        continue
    }

    $split = Split-PHPBlocks -Text $orig
    $working = $split.Working
    $tokens = $split.Tokens

    $allIds = Get-AllIds -Text $orig
    $changed = $false

    while ($true) {
        $labelMatches = $labelRe.Matches($working)
        if ($labelMatches.Count -eq 0) { break }
        $handledOne = $false

        for ($i = 0; $i -lt $labelMatches.Count; $i++) {
            $m = $labelMatches[$i]
            $forValue = $m.Groups['for'].Value
            if ([string]::IsNullOrWhiteSpace($forValue)) { continue }

            # skip if id already exists in file
            if ($allIds.ContainsKey($forValue)) { continue }

            $pos = $m.Index + $m.Length
            $sub = ''
            if ($pos -lt $working.Length) { $sub = $working.Substring($pos) }

            $mc = $controlRe.Match($sub)
            if ($mc.Success) {
                $controlStart = $pos + $mc.Index
                $controlLen = $mc.Length
                $controlTag = $mc.Value

                # check if control has id attribute
                $idMatch = $idReGlobal.Match($controlTag)
                if ($idMatch.Success) {
                    $existingId = $idMatch.Groups['id'].Value
                    # update label's for attribute to existing id
                    $oldLabel = $m.Value
                    $forAttrMatch = $forReGlobal.Match($oldLabel)
                    if ($forAttrMatch.Success) {
                        $start = $m.Index + $forAttrMatch.Index
                        $len = $forAttrMatch.Length
                        $newFor = 'for="' + $existingId + '"'
                        $working = $working.Substring(0, $start) + $newFor + $working.Substring($start + $len)
                        $comment = '<!-- Fixed label-for/id mismatch for accessibility -->`n'
                        $working = $working.Substring(0, $m.Index) + $comment + $working.Substring($m.Index)
                        $report += ($path + ': updated label for ''' + $forValue + ''' -> ''' + $existingId + ''')
                        $changed = $true
                        $handledOne = $true
                        break
                    }
                } else {
                    # add id to control; ensure unique
                    $newId = $forValue
                    if ($allIds.ContainsKey($newId)) { $newId = Make-UniqueId -base $newId -existing $allIds } else { $allIds[$newId] = 1 }

                    $originalControl = $working.Substring($controlStart, $controlLen)
                    if ([System.Text.RegularExpressions.Regex]::IsMatch($originalControl, '/>\s*$')) {
                        $newControl = [System.Text.RegularExpressions.Regex]::Replace($originalControl, '/>\\s*$', ' id="' + $newId + '" />')
                    } else {
                        $newControl = [System.Text.RegularExpressions.Regex]::Replace($originalControl, '>\\s*$', ' id="' + $newId + '">')
                    }
                    $comment = '<!-- Fixed label-for/id mismatch for accessibility -->`n'
                    $working = $working.Substring(0, $controlStart) + $comment + $newControl + $working.Substring($controlStart + $controlLen)
                    $report += ($path + ": added id '" + $newId + "' to control following label (label had for='" + $forValue + "')")
                    $changed = $true
                    $handledOne = $true
                    break
                }
            } else {
                # look backward for previous control within 500 chars
                $startBack = [Math]::Max(0, $m.Index - 500)
                $backLen = $m.Index - $startBack
                if ($backLen -le 0) {
                    $report += ($path + ": couldn't auto-fix label for '" + $forValue + "' (no nearby control)")
                    continue
                }
                $backSub = $working.Substring($startBack, $backLen)
                $mc2 = $controlRe.Match($backSub)
                if ($mc2.Success) {
                    $controlStart = $startBack + $mc2.Index
                    $controlLen = $mc2.Length
                    $originalControl = $working.Substring($controlStart, $controlLen)
                    $idMatch2 = $idReGlobal.Match($originalControl)
                    if ($idMatch2.Success) {
                        $existingId = $idMatch2.Groups['id'].Value
                        $oldLabel = $m.Value
                        $forAttrMatch = $forReGlobal.Match($oldLabel)
                        if ($forAttrMatch.Success) {
                            $start = $m.Index + $forAttrMatch.Index
                            $len = $forAttrMatch.Length
                            $newFor = 'for="' + $existingId + '"'
                            $working = $working.Substring(0, $start) + $newFor + $working.Substring($start + $len)
                            $comment = '<!-- Fixed label-for/id mismatch for accessibility -->`n'
                            $working = $working.Substring(0, $m.Index) + $comment + $working.Substring($m.Index)
                            $report += ($path + ": updated label for '" + $forValue + "' -> '" + $existingId + "' (matched previous control)")
                            $changed = $true
                            $handledOne = $true
                            break
                        }
                    } else {
                        $newId = $forValue
                        if ($allIds.ContainsKey($newId)) { $newId = Make-UniqueId -base $newId -existing $allIds } else { $allIds[$newId] = 1 }
                        if ([System.Text.RegularExpressions.Regex]::IsMatch($originalControl, '/>\s*$')) {
                            $newControl = [System.Text.RegularExpressions.Regex]::Replace($originalControl, '/>\\s*$', ' id="' + $newId + '" />')
                        } else {
                            $newControl = [System.Text.RegularExpressions.Regex]::Replace($originalControl, '>\\s*$', ' id="' + $newId + '">')
                        }
                        $working = $working.Substring(0, $controlStart) + '<!-- Fixed label-for/id mismatch for accessibility -->`n' + $newControl + $working.Substring($controlStart + $controlLen)
                        $report += ($path + ": added id '" + $newId + "' to previous control (label had for='" + $forValue + "')")
                        $changed = $true
                        $handledOne = $true
                        break
                    }
                } else {
                    $report += ($path + ": couldn't auto-fix label for '" + $forValue + "' (no control found)")
                    continue
                }
            }
        }

        if (-not $handledOne) { break }
    }

    # check duplicates
    $afterAllIds = Get-AllIds -Text $working
    foreach ($k in $afterAllIds.Keys) {
        if ($afterAllIds[$k] -gt 1) {
            $report += ($path + ": duplicate id '" + $k + "' occurs " + $afterAllIds[$k] + " times")
        }
    }

    if ($changed) {
        $newContent = Restore-PHPBlocks -Text $working -Tokens $tokens
        if ($DryRun) {
            Write-Output ("DRYRUN: would modify " + $path)
            $fixedFiles += $path
        } else {
            $bak = $path + '.bak'
            if (-not (Test-Path $bak)) { Copy-Item -Path $path -Destination $bak -Force }
            Set-Content -Path $path -Value $newContent -Encoding UTF8
            $fixedFiles += $path
        }
    }
}

Write-Output ("Auto-fix completed. Files modified: " + $fixedFiles.Count)
foreach ($r in $report) { Write-Output $r }

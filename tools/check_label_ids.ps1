$errors = @();
$files = Get-ChildItem -Path . -Include *.php,*.html -Recurse -File
foreach ($f in $files) {
  $content = Get-Content -Raw -Path $f.FullName -ErrorAction SilentlyContinue
  if (-not $content) { continue }
  $labelRe = [regex]::new(@'
<label[^>]*\bfor=["'](?<for>[^"']+)["']
'@
  )
  $matches = $labelRe.Matches($content)
  foreach ($m in $matches) {
    $for = $m.Groups['for'].Value
    if ($for -eq '') { continue }
    if ($content -notmatch ('\bid\s*=\s*["\"]' + [regex]::Escape($for) + '["\"]')) {
      $errors += "MISSING ID -> $($f.FullName) -> for='$for' -> label snippet: $($m.Value.Substring(0,[math]::Min(120,$m.Value.Length)))"
    }
  }
}
if ($errors.Count -eq 0) { Write-Output 'No missing ids found for label for attributes.' } else { $errors | ForEach-Object { Write-Output $_ } }

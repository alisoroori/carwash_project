$files = Get-ChildItem -Path . -Include *.php,*.html -Recurse -File
foreach ($f in $files) {
  $text = Get-Content -Raw -Path $f.FullName -ErrorAction SilentlyContinue
  if ($null -eq $text) { continue }
  if ($text -match '\?\s+id=') {
    $bak = $f.FullName + '.bak'
    if (-not (Test-Path $bak)) { Copy-Item -Path $f.FullName -Destination $bak -Force }
    $new = $text -replace '\?\s+id="','\"\>" id="' 
    $new = $new -replace '\?\s+id=','?>" id='
    $new = $new -replace '\"\">','\"\>'
    if ($new -ne $text) {
      Set-Content -Path $f.FullName -Value $new -Encoding UTF8
      Write-Output "Fixed: $($f.FullName)"
    }
  }
}

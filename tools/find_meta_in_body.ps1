# Scan repo for files where <meta charset> appears after <body>
$root = Get-Location
$exts = "*.php","*.html","*.htm"
$found = @()
Get-ChildItem -Path $root -Recurse -Include $exts -File | ForEach-Object {
    try {
        $text = Get-Content -Path $_.FullName -Raw -ErrorAction Stop
    } catch { return }
    $metaIndex = $text.IndexOf('<meta charset', [System.StringComparison]::InvariantCultureIgnoreCase)
    $bodyIndex = $text.IndexOf('<body', [System.StringComparison]::InvariantCultureIgnoreCase)
    if ($metaIndex -ge 0 -and $bodyIndex -ge 0 -and $bodyIndex -lt $metaIndex) {
        $found += @{ File = $_.FullName; MetaIndex = $metaIndex; BodyIndex = $bodyIndex }
    }
}
if ($found.Count -eq 0) {
    Write-Host "No files found with <meta charset> after <body>."
} else {
    Write-Host "Files with <meta charset> appearing after <body>:\n"
    foreach ($f in $found) { Write-Host $f.File }
}

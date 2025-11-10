# Run PHP lint across all PHP files and output failures
$files = Get-ChildItem -Path . -Recurse -Filter *.php -File -ErrorAction SilentlyContinue | Select-Object -ExpandProperty FullName
foreach ($f in $files) {
    Write-Host "Checking: $f"
    try {
        php -l "$f" 2>&1 | ForEach-Object { Write-Host $_ }
    } catch {
        $err = $_.Exception.Message
        Write-Host ("Error running php -l on {0}: {1}" -f $f, $err)
    }
}
Write-Host 'PHP lint run complete.'

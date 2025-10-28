Get-ChildItem -Path "C:\xampp\htdocs\carwash_project" -Recurse -Filter *.php | ForEach-Object {
    $content = Get-Content $_.FullName -Encoding Byte
    if ($content.Length -ge 3 -and $content[0] -eq 239 -and $content[1] -eq 187 -and $content[2] -eq 191) {
        # Write the bytes back without the first 3 BOM bytes using UTF8 encoding
        $bytes = $content[3..($content.Length - 1)]
        [System.IO.File]::WriteAllBytes($_.FullName, $bytes)
        Write-Host "BOM removed from $($_.FullName)"
    }
}
# Update hrefs that point to ./dist/output.css to use dynamic $base_url
Get-ChildItem -Path . -Recurse -Include *.php,*.html,*.htm -File | ForEach-Object {
    $f = $_.FullName
    try {
        $content = Get-Content -Raw -LiteralPath $f -ErrorAction Stop
    } catch {
        Write-Warning "Skipping binary or unreadable file: $f"
        return
    }

    if ($content -notmatch '\./dist/output.css' -and $content -notmatch 'href="dist/output.css"') { return }

    $new = $content -replace '\.\/dist\/output\.css','<?php echo $base_url; ?>/dist/output.css'
    $new = $new -replace 'href="dist\/output\.css"','href="<?php echo $base_url; ?>/dist/output.css"'

    if ($new -ne $content) {
        Set-Content -LiteralPath $f -Value $new -Encoding UTF8
        Write-Host "Updated href in: $f"
    }
}

Write-Host "Href updates complete."

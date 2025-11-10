# Safe replacement script to update old Tailwind CSS paths to new dist output
# Run from repository root: powershell -NoProfile -ExecutionPolicy Bypass -File .\scripts\replace-tailwind.ps1

$replacements = @{
    '/carwash_project/frontend/css/tailwind.css' = './dist/output.css'
    '/frontend/css/tailwind.css' = './dist/output.css'
    '<?php echo $base_url; ?>/frontend/css/tailwind.css' = './dist/output.css'
}

$files = Get-ChildItem -Path . -Recurse -Include *.php,*.html,*.htm -File -ErrorAction SilentlyContinue

foreach ($file in $files) {
    try {
        $path = $file.FullName
        $content = Get-Content -Raw -LiteralPath $path -ErrorAction Stop
        $original = $content

        foreach ($k in $replacements.Keys) {
            # Use regex escape for literal match
            $pattern = [regex]::Escape($k)
            $content = [regex]::Replace($content, $pattern, [System.Text.RegularExpressions.MatchEvaluator]{ param($m) $replacements[$k] })
        }

        if ($content -ne $original) {
            Set-Content -LiteralPath $path -Value $content -Encoding UTF8
            Write-Host "Updated: $path"
        }
    } catch {
        Write-Warning "Failed to process $($file.FullName): $_"
    }
}

Write-Host 'Replacement script finished.'

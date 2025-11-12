param(
    [string]$BaseUrl = 'http://localhost/carwash_project'
)

$fontPaths = @(
    'frontend/fonts/Inter.woff2',
    'frontend/fonts/FiraCode-Regular.woff2',
    'frontend/fonts/FiraCode-Bold.woff2',
    'frontend/fonts/whatever.woff2',
    'dist/assets/fonts/output.woff2'
)

Write-Output "Checking font headers against base URL: $BaseUrl"

foreach ($p in $fontPaths) {
    $url = "$BaseUrl/$p"
    Write-Output "\n--- $url ---"
    try {
        $resp = Invoke-WebRequest -Uri $url -Method Head -UseBasicParsing -TimeoutSec 10 -Headers @{ 'User-Agent' = 'font-header-checker' }
        $ct = $resp.Headers['Content-Type']
        if (-not $ct) { $ct = $resp.Headers['content-type'] }
        Write-Output "Status: $($resp.StatusCode)"
        Write-Output "Content-Type: $ct"
    } catch {
        Write-Output "HEAD failed, trying GET..."
        try {
            $resp = Invoke-WebRequest -Uri $url -Method Get -UseBasicParsing -TimeoutSec 10 -Headers @{ 'User-Agent' = 'font-header-checker' }
            $ct = $resp.Headers['Content-Type']
            if (-not $ct) { $ct = $resp.Headers['content-type'] }
            Write-Output "Status: $($resp.StatusCode)"
            Write-Output "Content-Type: $ct"
        } catch {
            Write-Output "Request failed: $($_.Exception.Message)"
        }
    }
}

Write-Output "\nDone. If any .woff2 response does not return Content-Type: font/woff2, ensure mod_mime is enabled and AddType or FilesMatch is configured in Apache or update your Nginx vhost to set correct MIME type."
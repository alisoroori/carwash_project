param(
    [string]$BaseUrl = 'http://localhost/carwash_project'
)

$cssPaths = @(
    'frontend/css/input.css',
    'frontend/css/tailwind.css',
    'frontend/css/settings.css',
    'frontend/css/services.css',
    'frontend/css/payment.css',
    'frontend/css/search.css',
    'dist/output.css',
    'dist/assets/output.css'
)

Write-Output "Checking CSS headers against base URL: $BaseUrl"

foreach ($p in $cssPaths) {
    $url = "$BaseUrl/$p"
    Write-Output "\n--- $url ---"
    try {
        # Prefer HEAD; fall back to GET if HEAD not allowed
        $resp = Invoke-WebRequest -Uri $url -Method Head -UseBasicParsing -TimeoutSec 10 -Headers @{ 'User-Agent' = 'header-checker' }
        $ct = $resp.Headers['Content-Type']
        if (-not $ct) { $ct = $resp.Headers['content-type'] }
        Write-Output "Status: $($resp.StatusCode)"
        Write-Output "Content-Type: $ct"
    } catch {
        Write-Output "HEAD failed, trying GET..."
        try {
            $resp = Invoke-WebRequest -Uri $url -Method Get -UseBasicParsing -TimeoutSec 10 -Headers @{ 'User-Agent' = 'header-checker' }
            $ct = $resp.Headers['Content-Type']
            if (-not $ct) { $ct = $resp.Headers['content-type'] }
            Write-Output "Status: $($resp.StatusCode)"
            Write-Output "Content-Type: $ct"
        } catch {
            Write-Output "Request failed: $($_.Exception.Message)"
        }
    }
}

Write-Output "\nDone. If Content-Type for any CSS is missing 'charset', consider ensuring mod_headers is enabled and .htaccess AllowOverride is set, or add appropriate server config for Nginx."
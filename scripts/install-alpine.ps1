# Download official Alpine.js build (v3.13.0) into project assets path
# Usage: Open PowerShell as administrator (if needed) and run from repo root:
# .\scripts\install-alpine.ps1

$repoRoot = Split-Path -Parent $PSScriptRoot
$dest = Join-Path $repoRoot 'assets\js\alpine\cdn.min.js'
$uri = 'https://cdn.jsdelivr.net/npm/alpinejs@3.13.0/dist/cdn.min.js'

Write-Host "Downloading Alpine.js from $uri to $dest"
try {
    Invoke-WebRequest -Uri $uri -OutFile $dest -UseBasicParsing -ErrorAction Stop
    Write-Host "Downloaded successfully."
} catch {
    Write-Error "Failed to download Alpine.js: $_"
}

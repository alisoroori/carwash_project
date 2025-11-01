# ===========================================
# Auto Git Fix Smart Script for CarWash Project (PowerShell)
# Author: ali
# ===========================================

Write-Host "🚀 Starting Smart Git Workflow..." -ForegroundColor Cyan

Set-Location $PSScriptRoot

# Step 1: Detect current branch
$CURRENT_BRANCH = git rev-parse --abbrev-ref HEAD
Write-Host "🌿 Current branch: $CURRENT_BRANCH" -ForegroundColor Cyan

# Step 2: Initialize git if missing
if (-not (Test-Path ".git")) {
    Write-Host "⚙️ Git repo not found. Initializing..." -ForegroundColor Yellow
    git init
    git branch -M main
    git remote add origin "https://github.com/alisoroori/carwash_project.git"
}

# Step 3: Add all changes
Write-Host "📦 Adding all changes..." -ForegroundColor Yellow
git add -A

# Step 4: Commit if there are staged changes
if (-not (git diff --cached --quiet)) {
    git commit -m "Auto commit: smart git workflow" | Out-Null
    Write-Host "🪶 Changes committed." -ForegroundColor Green
} else {
    Write-Host "✅ No new changes to commit." -ForegroundColor Cyan
}

# Step 5: Ensure remote branch exists
$remoteBranches = git branch -r
if ($remoteBranches -match "origin/$CURRENT_BRANCH") {
    Write-Host "🌐 Remote branch exists: $CURRENT_BRANCH" -ForegroundColor Green
} else {
    Write-Host "🌐 Remote branch does not exist. Creating..." -ForegroundColor Yellow
    git push -u origin $CURRENT_BRANCH
}

# Step 6: Pull latest changes with rebase
Write-Host "🔄 Pulling latest changes..." -ForegroundColor Yellow
git pull origin $CURRENT_BRANCH --rebase

# Step 7: Push changes
Write-Host "🚢 Pushing to GitHub..." -ForegroundColor Green
git push origin $CURRENT_BRANCH

# Step 8: Final git status
Write-Host "✅ Git status summary:" -ForegroundColor Cyan
git status
Write-Host "🎯 Smart Git Workflow Done!" -ForegroundColor Green

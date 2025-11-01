#!/bin/bash
# ===========================================
# Auto Git Fix Smart Script for CarWash Project
# Author: ali
# Description: Auto detect branch, commit, and push changes
# ===========================================

set -e  # stop on first error

echo "🚀 Starting Smart Git Workflow..."

cd "$(dirname "$0")" || exit 1

# Step 1: Detect current branch
CURRENT_BRANCH=$(git rev-parse --abbrev-ref HEAD)
echo "🌿 Current branch: $CURRENT_BRANCH"

# Step 2: Initialize git if not exists
if [ ! -d ".git" ]; then
  echo "⚙️ Git repo not found. Initializing..."
  git init
  git branch -M main
  git remote add origin https://github.com/alisoroori/carwash_project.git
fi

# Step 3: Stage all changes
echo "📦 Adding all local changes..."
git add -A

# Step 4: Commit if there are changes
if ! git diff --cached --quiet; then
  git commit -m "Auto commit: smart git workflow"
  echo "🪶 Changes committed."
else
  echo "✅ No new changes to commit."
fi

# Step 5: Ensure remote branch exists
if git show-ref --verify --quiet refs/remotes/origin/$CURRENT_BRANCH; then
  echo "🌐 Remote branch exists: $CURRENT_BRANCH"
else
  echo "🌐 Remote branch does not exist. Creating..."
  git push -u origin $CURRENT_BRANCH
fi

# Step 6: Pull latest changes with rebase
echo "🔄 Pulling latest changes..."
git pull origin $CURRENT_BRANCH --rebase || echo "⚠️ Pull skipped"

# Step 7: Push changes to remote
echo "🚢 Pushing to GitHub..."
git push origin $CURRENT_BRANCH

# Step 8: Final status
echo "✅ Git status summary:"
git status
echo "🎯 Smart Git Workflow Done!"

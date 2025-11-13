#!/usr/bin/env bash
set -euo pipefail

# Scan PHP files for echo/print usages that directly include superglobals or $row[...] without htmlspecialchars() or escape()
ROOT_DIR=$(cd "$(dirname "$0")/.." && pwd)
cd "$ROOT_DIR"

echo "Scanning PHP files for unescaped superglobal or row outputs..."

# Find candidate lines
candidates=$(grep -RIn --exclude-dir=vendor --exclude-dir=.git --include='*.php' -P "echo\s+[^;]*\$(?:_GET|_POST|_REQUEST|COOKIE)|print_r\s*\(\s*\$(?:_GET|_POST|_REQUEST|COOKIE)|echo\s+[^;]*\$row\[" . || true)

if [ -z "$candidates" ]; then
  echo "No candidates found. OK."
  exit 0
fi

# Filter out lines that already use htmlspecialchars or escape
unsafe=$(echo "$candidates" | grep -v -E 'htmlspecialchars|escape\(' || true)

if [ -n "$unsafe" ]; then
  echo "Found unescaped outputs (lines shown below):"
  echo "$unsafe"
  echo "\nPlease wrap outputs with htmlspecialchars() or escape() before committing."
  exit 1
else
  echo "All candidate outputs are properly escaped. OK."
  exit 0
fi

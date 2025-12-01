#!/bin/bash
# Quick Turkish Encoding Finder
# Use this to find remaining broken Turkish characters

echo "=== Scanning for Broken Turkish Characters ==="
echo ""

# Search for common broken patterns
echo "Checking admin_panel.php..."
grep -n "Ã„\|Ã…\|Ã‡\|Ã–\|Ãœ\|ÃƒÂ\|Ã¢Â‚Âº\|âœ\|â„¹\|âš" backend/dashboard/admin_panel.php | head -20

echo ""
echo "Checking dashboard_header.php..."
grep -n "Ã„\|Ã…\|Ã‡\|Ã–\|Ãœ\|ÃƒÂ\|Ã¢Â‚Âº\|âœ\|â„¹\|âš" backend/includes/dashboard_header.php | head -20

echo ""
echo "=== Quick Fix Reference ==="
echo "Ä± → ı"
echo "Ã…ÂŸ → ş"
echo "Ä → ğ"
echo "ÃƒÂ§ → ç"
echo "ÃƒÂ¼ → ü"
echo "ÃƒÂ¶ → ö"
echo "Ä° → İ"
echo "ÃƒÂž → Ş"
echo "ÃƒÂ‡ → Ç"
echo "Ä → Ğ"
echo "ÃƒÂœ → Ü"
echo "ÃƒÂ– → Ö"
echo "Ã¢Â‚Âº → ₺"
echo "âœ → ✓"

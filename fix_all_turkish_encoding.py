#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Comprehensive Turkish Character Encoding Fixer
Fixes all broken UTF-8 encodings in PHP/HTML files
"""

import os
import re
from pathlib import Path

# Define all broken -> correct mappings
ENCODING_FIXES = {
    # Turkish lowercase
    'Ä±': 'ı',
    'ÅŸ': 'ş',
    'Ä': 'ğ',
    'Ã§': 'ç',
    'Ã¼': 'ü',
    'Ã¶': 'ö',
    
    # Turkish uppercase
    'Ä°': 'İ',
    'Åž': 'Ş',
    'Ä': 'Ğ',
    'Ã‡': 'Ç',
    'Ãœ': 'Ü',
    'Ã–': 'Ö',
    
    # Combined broken encodings (double-encoded)
    'Ã„Â±': 'ı',
    'Ã…ÂŸ': 'ş',
    'Ã„Â': 'ğ',
    'ÃƒÂ§': 'ç',
    'ÃƒÂ¼': 'ü',
    'ÃƒÂ¶': 'ö',
    'Ã„Â°': 'İ',
    'ÃƒÂž': 'Ş',
    'ÃƒÂ‡': 'Ç',
    'Ã„Å¾': 'Ğ',
    'ÃƒÂœ': 'Ü',
    'ÃƒÂ–': 'Ö',
    
    # Additional triple-encoded patterns
    'Ã…Âž': 'Ş',
    'Ã…Â': 'ş',
    'DeğŸ': 'Değ',
    
    # Special characters
    'Ã¢Â‚Âº': '₺',
    'â‚º': '₺',
    'â‚¬': '€',
    'âœ…': '✅',
    'âœ': '✓',
    'â„¹': 'ℹ',
    'âš': '⚠',
    'â€¦': '…',
    'â€"': '—',
    'â€"': '–',
    'â€œ': '"',
    'â€': '"',
    'â€˜': ''',
    'â€™': ''',
    'â˜…': '★',
    'â­': '⭐',
}

def fix_file_encoding(filepath):
    """Fix encoding issues in a single file"""
    try:
        # Read file with UTF-8 encoding
        with open(filepath, 'r', encoding='utf-8', errors='ignore') as f:
            content = f.read()
        
        original_content = content
        changes = []
        
        # Apply all fixes
        for broken, correct in ENCODING_FIXES.items():
            if broken in content:
                count = content.count(broken)
                content = content.replace(broken, correct)
                changes.append(f"  • Fixed '{broken}' → '{correct}' ({count} times)")
        
        # Write back with UTF-8 no BOM
        if content != original_content:
            with open(filepath, 'w', encoding='utf-8', newline='\n') as f:
                f.write(content)
            return True, changes
        
        return False, []
        
    except Exception as e:
        print(f"Error processing {filepath}: {e}")
        return False, []

def main():
    """Main function to process all files"""
    base_dir = Path(__file__).parent
    
    files_to_process = [
        base_dir / 'backend' / 'dashboard' / 'admin_panel.php',
        base_dir / 'backend' / 'includes' / 'dashboard_header.php',
        base_dir / 'backend' / 'includes' / 'dashboard_header_improved.php',
        base_dir / 'backend' / 'includes' / 'admin_header.php',
        base_dir / 'backend' / 'includes' / 'footer.php',
    ]
    
    print("=" * 70)
    print("TURKISH CHARACTER ENCODING FIX - COMPREHENSIVE REPORT")
    print("=" * 70)
    print()
    
    total_files_fixed = 0
    all_changes = {}
    
    for filepath in files_to_process:
        if not filepath.exists():
            print(f"⚠️  File not found: {filepath.name}")
            continue
        
        print(f"Processing: {filepath.name}")
        fixed, changes = fix_file_encoding(filepath)
        
        if fixed:
            total_files_fixed += 1
            all_changes[filepath.name] = changes
            print(f"✅ Fixed {len(changes)} encoding patterns")
            for change in changes:
                print(change)
        else:
            print(f"✓  No encoding issues found")
        print()
    
    print("=" * 70)
    print(f"SUMMARY: Fixed {total_files_fixed} files")
    print("=" * 70)
    
    if all_changes:
        print("\nDetailed Changes:")
        for filename, changes in all_changes.items():
            print(f"\n{filename}:")
            for change in changes:
                print(change)

if __name__ == '__main__':
    main()

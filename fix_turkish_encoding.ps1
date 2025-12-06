# Turkish Character Encoding Fix Script
# Fixes broken UTF-8 encoding in PHP files

$targetFiles = @(
    "backend\dashboard\admin_panel.php",
    "backend\includes\dashboard_header.php",
    "backend\includes\dashboard_header_improved.php"
)

# Define Turkish character mappings (broken UTF-8 -> correct UTF-8)
$replacements = @{
    # Turkish lowercase letters
    'Ä±' = 'ı'   # i without dot
    'Ã§' = 'ç'   # c with cedilla
    'Ä' = 'ğ'    # g with breve
    'Å' = 'ş'    # s with cedilla
    'ü' = 'ü'    # u with umlaut
    'ö' = 'ö'    # o with umlaut
    
    # Turkish uppercase letters
    'Ä°' = 'İ'   # I with dot
    'Ã‡' = 'Ç'   # C with cedilla
    'Ä' = 'Ğ'    # G with breve
    'Å' = 'Ş'    # S with cedilla
    'Ãœ' = 'Ü'   # U with umlaut
    'Ã–' = 'Ö'   # O with umlaut
    
    # Combined broken encodings
    'Ã„Â±' = 'ı'
    'Ã…ÂŸ' = 'ş'
    'Ã„Â' = 'ğ'
    'ÃƒÂ§' = 'ç'
    'ÃƒÂ¼' = 'ü'
    'ÃƒÂ¶' = 'ö'
    'ÃƒÂ' = 'İ'
    'Ã„Â°' = 'İ'
    'ÃƒÂž' = 'Ş'
    'ÃƒÂ‡' = 'Ç'
    'Ã„Å¾' = 'Ğ'
    'ÃƒÂœ' = 'Ü'
    'ÃƒÂ–' = 'Ö'
    
    # Special characters
    'Ã¢Â‚Âº' = '₺'  # Turkish Lira symbol
    'â‚º' = '₺'
    'âœ' = '✓'      # Checkmark
    'â„¹' = 'ℹ'      # Info symbol
    'âš' = '⚠'      # Warning symbol
    
    # Common Turkish words that appear broken
    'bakÄ±ÅŸ' = 'bakış'
    'genel bakÄ±ÅŸ' = 'genel bakış'
    'iÅŸlem' = 'işlem'
    'baÅŸar' = 'başar'
    'BaÅŸar' = 'Başar'
    'baÅŸarÄ±' = 'başarı'
    'BaÅŸarÄ±' = 'Başarı'
    'baÅŸarÄ±lÄ±' = 'başarılı'
    'BaÅŸarÄ±lÄ±' = 'Başarılı'
    'baÅŸarÄ±sÄ±z' = 'başarısız'
    'BaÅŸarÄ±sÄ±z' = 'Başarısız'
    'MÃ¼ÅŸteri' = 'Müşteri'
    'mÃ¼ÅŸteri' = 'müşteri'
    'SipariÅŸ' = 'Sipariş'
    'sipariÅŸ' = 'sipariş'
    'Ã–deme' = 'Ödeme'
    'Ã¶deme' = 'ödeme'
    'Ã–denecek' = 'Ödenecek'
    'Ã–dendi' = 'Ödendi'
    'Ã–de' = 'Öde'
    'TÃ¼m' = 'Tüm'
    'tÃ¼m' = 'tüm'
    'GÃ¼nlÃ¼k' = 'Günlük'
    'gÃ¼nlÃ¼k' = 'günlük'
    'Ã–ncelik' = 'Öncelik'
    'Ã¶ncelik' = 'öncelik'
    'gÃ¶rÃ¼ntÃ¼le' = 'görüntüle'
    'GÃ¶rÃ¼ntÃ¼le' = 'Görüntüle'
    'yÃ¶net' = 'yönet'
    'YÃ¶net' = 'Yönet'
    'YÃ¶netim' = 'Yönetim'
    'yÃ¶netim' = 'yönetim'
    'YÃ¶netici' = 'Yönetici'
    'Ã‡Ã¶z' = 'Çöz'
    'Ã§Ã¶z' = 'çöz'
    'Ã‡Ä±k' = 'Çık'
    'Ã§Ä±k' = 'çık'
    'ÃœÃ§ret' = 'Ücret'
    'Ã¼cret' = 'ücret'
    'Ä°ÅŸlem' = 'İşlem'
    'DÄ±ÅŸa' = 'Dışa'
    'dÄ±ÅŸa' = 'dışa'
    'DÄ±ÅŸ' = 'Dış'
    'dÄ±ÅŸ' = 'dış'
    'YÄ±kama' = 'Yıkama'
    'yÄ±kama' = 'yıkama'
    'Ã–ztÃ¼rk' = 'Öztürk'
    'YapÄ±ÅŸkan' = 'Yapışkan'
    'Ã§ubuÄŸu' = 'çubuğu'
    'DuyarlÄ±' = 'Duyarlı'
    'Eklendi' = 'Eklendi'
    'eklendi' = 'eklendi'
    'AlÄ±ÅŸveriÅŸ' = 'Alışveriş'
    'RaporlarÄ±' = 'Raporları'
    'OnaylanmÄ±ÅŸ' = 'Onaylanmış'
    'ZamanlanmÄ±ÅŸ' = 'Zamanlanmış'
    'DavranÄ±ÅŸ' = 'Davranış'
    'davranÄ±ÅŸ' = 'davranış'
    'Beklemede' = 'Beklemede'
    'Bekleyen' = 'Bekleyen'
    'Gereken' = 'Gereken'
    'Tipi' = 'Tipi'
    'Tipleri' = 'Tipleri'
    'Analizi' = 'Analizi'
    'analizi' = 'analizi'
    'DaÄŸÄ±lÄ±m' = 'Dağılım'
    'daÄŸÄ±lÄ±m' = 'dağılım'
    'Toplam' = 'Toplam'
    'toplam' = 'toplam'
    'Talep' = 'Talep'
    'talep' = 'talep'
    'yanÄ±tla' = 'yanıtla'
    'YanÄ±tla' = 'Yanıtla'
    'yorumlarÄ±' = 'yorumları'
    'YorumlarÄ±' = 'Yorumları'
    'denetle' = 'denetle'
    'Denetle' = 'Denetle'
    'oranlarÄ±' = 'oranları'
    'OranlarÄ±' = 'Oranları'
    'sadakat' = 'sadakat'
    'Sadakat' = 'Sadakat'
    'Ä°ndir' = 'İndir'
    'indir' = 'indir'
    'Durum' = 'Durum'
    'durum' = 'durum'
    'Durumlar' = 'Durumlar'
}

foreach ($file in $targetFiles) {
    $filePath = Join-Path $PSScriptRoot $file
    
    if (-not (Test-Path $filePath)) {
        Write-Host "File not found: $filePath" -ForegroundColor Yellow
        continue
    }
    
    Write-Host "Processing: $file" -ForegroundColor Cyan
    
    try {
        # Read file with UTF-8 encoding
        $content = Get-Content -Path $filePath -Raw -Encoding UTF8
        $originalContent = $content
        $changeCount = 0
        
        # Apply all replacements
        foreach ($key in $replacements.Keys) {
            $value = $replacements[$key]
            if ($content -match [regex]::Escape($key)) {
                $content = $content -replace [regex]::Escape($key), $value
                $changeCount++
            }
        }
        
        # Write back with UTF-8 encoding (no BOM)
        if ($content -ne $originalContent) {
            $utf8NoBom = New-Object System.Text.UTF8Encoding $false
            [System.IO.File]::WriteAllText($filePath, $content, $utf8NoBom)
            Write-Host "  ✓ Fixed $changeCount patterns in $file" -ForegroundColor Green
        } else {
            Write-Host "  - No changes needed in $file" -ForegroundColor Gray
        }
    }
    catch {
        Write-Host "  ✗ Error processing $file : $_" -ForegroundColor Red
    }
}

Write-Host "`n=== Turkish Encoding Fix Complete ===" -ForegroundColor Green
Write-Host "All files have been converted to UTF-8 without BOM" -ForegroundColor Green

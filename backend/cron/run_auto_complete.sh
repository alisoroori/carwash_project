#!/bin/bash
# Auto-Complete Bookings Cron Job for Linux/Mac
# 
# Add to crontab with: crontab -e
# Then add this line:
# */5 * * * * /path/to/carwash_project/backend/cron/run_auto_complete.sh >> /path/to/logs/cron_execution.log 2>&1

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$SCRIPT_DIR"

echo "[$(date)] Running auto-complete bookings cron..."

# Run the PHP cron script
/usr/bin/php auto_complete_bookings.php

echo "[$(date)] Auto-complete cron finished."

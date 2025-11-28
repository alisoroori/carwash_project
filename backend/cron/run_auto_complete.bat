@echo off
REM Auto-Complete Bookings Cron Job for Windows
REM Schedule this to run every 5 minutes using Windows Task Scheduler
REM
REM To setup:
REM 1. Open Task Scheduler (taskschd.msc)
REM 2. Create Basic Task
REM 3. Name: "CarWash Auto-Complete Bookings"
REM 4. Trigger: Daily, repeat every 5 minutes
REM 5. Action: Start a program
REM 6. Program: C:\xampp\htdocs\carwash_project\backend\cron\run_auto_complete.bat
REM 7. Start in: C:\xampp\htdocs\carwash_project\backend\cron

echo [%date% %time%] Running auto-complete bookings cron...

REM Change to the script directory
cd /d "%~dp0"

REM Run the PHP cron script
C:\xampp\php\php.exe auto_complete_bookings.php >> ..\..\logs\cron_execution.log 2>&1

echo [%date% %time%] Auto-complete cron finished.

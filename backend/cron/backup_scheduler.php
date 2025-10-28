<?php
require_once '../includes/db.php';
require_once '../includes/backup_manager.php';

class BackupScheduler
{
    private $backupManager;
    private $logFile;

    public function __construct($conn)
    {
        $this->backupManager = new BackupManager($conn);
        $this->logFile = __DIR__ . '/backup.log';
    }

    public function run()
    {
        try {
            $this->log("Starting backup process...");

            // Create backup
            $backupFile = $this->backupManager->createBackup();

            // Cleanup old backups (keep last 7 days)
            $this->cleanupOldBackups();

            // Upload to cloud storage (if configured)
            $this->uploadToCloud($backupFile);

            $this->log("Backup completed successfully: {$backupFile}");
        } catch (Exception $e) {
            $this->log("Backup failed: " . $e->getMessage());
            // Send alert email to admin
            $this->sendAlertEmail($e->getMessage());
        }
    }

    private function cleanupOldBackups()
    {
        $backupDir = dirname($this->backupManager->getBackupDir());
        $files = glob($backupDir . "/backup_*.sql");

        foreach ($files as $file) {
            if (filemtime($file) < strtotime('-7 days')) {
                unlink($file);
                $this->log("Deleted old backup: {$file}");
            }
        }
    }

    private function log($message)
    {
        $timestamp = date('Y-m-d H:i:s');
        file_put_contents($this->logFile, "[{$timestamp}] {$message}\n", FILE_APPEND);
    }
}

// For Windows Task Scheduler:
// Create a batch file (backup.bat) with:
// C:\xampp\php\php.exe -f "C:\xampp\htdocs\carwash_project\backend\cron\backup_scheduler.php"

<?php
declare(strict_types=1);

namespace App\Classes;

class BackupManager
{
    private $conn;
    private $backupDir;

    public function __construct($conn, $backupDir = '../backups')
    {
        $this->conn = $conn;
        $this->backupDir = $backupDir;

        if (!file_exists($backupDir)) {
            mkdir($backupDir, 0777, true);
        }
    }

    public function createBackup($tables = ['pages', 'announcements', 'faqs'])
    {
        $backup = '';
        $timestamp = date('Y-m-d_H-i-s');

        foreach ($tables as $table) {
            // Get create table statement
            $result = $this->conn->query("SHOW CREATE TABLE $table");
            $row = $result->fetch_row();
            $backup .= "\n\n" . $row[1] . ";\n\n";

            // Get table data
            $result = $this->conn->query("SELECT * FROM $table");
            while ($row = $result->fetch_assoc()) {
                $backup .= "INSERT INTO $table VALUES ('" .
                    implode("','", array_map([$this->conn, 'real_escape_string'], $row)) .
                    "');\n";
            }
        }

        $filename = $this->backupDir . "/backup_{$timestamp}.sql";
        file_put_contents($filename, $backup);
        return $filename;
    }

    public function restoreBackup($filename)
    {
        if (!file_exists($filename)) {
            throw new Exception('Backup file not found');
        }

        $this->conn->begin_transaction();

        try {
            $sql = file_get_contents($filename);
            $this->conn->multi_query($sql);

            while ($this->conn->more_results()) {
                $this->conn->next_result();
            }

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollback();
            throw $e;
        }
    }
}


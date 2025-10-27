<?php
class BackupVerifier
{
    private $conn;
    private $backupManager;

    public function __construct($conn, BackupManager $backupManager)
    {
        $this->conn = $conn;
        $this->backupManager = $backupManager;
    }

    public function verifyBackup($backupFile)
    {
        // Create temporary database for testing
        $tempDb = 'carwash_backup_test_' . uniqid();

        try {
            // Create test database
            $this->conn->query("CREATE DATABASE $tempDb");

            // Connect to test database
            $testConn = new mysqli(
                DB_HOST,
                DB_USER,
                DB_PASS,
                $tempDb
            );

            // Restore backup to test database
            $this->restoreToTestDb($backupFile, $testConn);

            // Verify data integrity
            $verificationResults = $this->verifyDataIntegrity($testConn);

            // Cleanup
            $testConn->close();
            $this->conn->query("DROP DATABASE $tempDb");

            return $verificationResults;
        } catch (Exception $e) {
            // Cleanup on error
            $this->conn->query("DROP DATABASE IF EXISTS $tempDb");
            throw $e;
        }
    }

    protected function verifyDataIntegrity($testConn)
    {
        $tables = ['pages', 'announcements', 'faqs'];
        $results = [];

        foreach ($tables as $table) {
            $results[$table] = [
                'original_count' => $this->getTableCount($this->conn, $table),
                'backup_count' => $this->getTableCount($testConn, $table),
                'data_matches' => $this->compareTableData($this->conn, $testConn, $table)
            ];
        }

        return $results;
    }

    protected function getTableCount($conn, $table)
    {
        $result = $conn->query("SELECT COUNT(*) as count FROM $table");
        return $result->fetch_assoc()['count'];
    }

    protected function compareTableData($conn1, $conn2, $table)
    {
        // Compare checksums of both tables
        $query = "CHECKSUM TABLE $table";
        $checksum1 = $conn1->query($query)->fetch_assoc();
        $checksum2 = $conn2->query($query)->fetch_assoc();

        return $checksum1['Checksum'] === $checksum2['Checksum'];
    }
}

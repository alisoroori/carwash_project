<?php
declare(strict_types=1);

namespace App\Classes;

class VersionManager
{
    private $conn;
    private $table;
    private $versionTable;

    public function __construct($conn, $table)
    {
        $this->conn = $conn;
        $this->table = $table;
        $this->versionTable = $table . '_versions';
        $this->ensureVersionTable();
    }

    private function ensureVersionTable()
    {
        $sql = "CREATE TABLE IF NOT EXISTS {$this->versionTable} (
            version_id INT PRIMARY KEY AUTO_INCREMENT,
            content_id INT NOT NULL,
            content TEXT NOT NULL,
            created_by INT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (content_id) REFERENCES {$this->table}(id),
            FOREIGN KEY (created_by) REFERENCES users(id),
            INDEX (content_id)
        )";

        $this->conn->query($sql);
    }

    public function createVersion($contentId, $content, $userId)
    {
        $stmt = $this->conn->prepare("
            INSERT INTO {$this->versionTable} 
            (content_id, content, created_by)
            VALUES (?, ?, ?)
        ");

        $stmt->bind_param('isi', $contentId, $content, $userId);
        return $stmt->execute();
    }

    public function getVersions($contentId)
    {
        $stmt = $this->conn->prepare("
            SELECT v.*, u.name as author
            FROM {$this->versionTable} v
            JOIN users u ON v.created_by = u.id
            WHERE v.content_id = ?
            ORDER BY v.created_at DESC
        ");

        $stmt->bind_param('i', $contentId);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function rollback($contentId, $versionId)
    {
        // Start transaction
        $this->conn->begin_transaction();

        try {
            // Get version content
            $stmt = $this->conn->prepare("
                SELECT content FROM {$this->versionTable}
                WHERE content_id = ? AND version_id = ?
            ");

            $stmt->bind_param('ii', $contentId, $versionId);
            $stmt->execute();
            $content = $stmt->get_result()->fetch_assoc()['content'];

            // Update current content
            $stmt = $this->conn->prepare("
                UPDATE {$this->table}
                SET content = ?
                WHERE id = ?
            ");

            $stmt->bind_param('si', $content, $contentId);
            $stmt->execute();

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollback();
            throw $e;
        }
    }
}

// Usage Example:
/*
$versionManager = new VersionManager($conn, 'pages');

// Create version
$versionManager->createVersion($pageId, $content, $_SESSION['user_id']);

// Get versions
$versions = $versionManager->getVersions($pageId);

// Rollback
$versionManager->rollback($pageId, $versionId);
*/


<?php
class ProfileManager
{
    private $conn;
    private $uploadDir;

    public function __construct($conn)
    {
        $this->conn = $conn;
        $this->uploadDir = dirname(__DIR__) . '/uploads/profiles/';
        $this->initProfileTable();
    }

    private function initProfileTable()
    {
        $sql = "CREATE TABLE IF NOT EXISTS user_profiles (
            id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT NOT NULL,
            profile_image VARCHAR(255),
            address TEXT,
            preferences JSON,
            last_updated DATETIME,
            FOREIGN KEY (user_id) REFERENCES users(id)
        )";

        $this->conn->query($sql);
    }

    public function updateProfile($userId, $profileData)
    {
        try {
            $this->conn->begin_transaction();

            // Update basic user info
            $stmt = $this->conn->prepare("
                UPDATE users 
                SET name = ?, phone = ?
                WHERE id = ?
            ");
            $stmt->bind_param(
                'ssi',
                $profileData['name'],
                $profileData['phone'],
                $userId
            );
            $stmt->execute();

            // Update profile details
            $stmt = $this->conn->prepare("
                INSERT INTO user_profiles (user_id, address, preferences, last_updated)
                VALUES (?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE
                address = VALUES(address),
                preferences = VALUES(preferences),
                last_updated = NOW()
            ");

            $preferences = json_encode($profileData['preferences']);
            $stmt->bind_param(
                'iss',
                $userId,
                $profileData['address'],
                $preferences
            );
            $stmt->execute();

            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollback();
            throw $e;
        }
    }
}

<?php
require_once '../backend/includes/db.php';
require_once '../backend/includes/review_notifier.php';

class ReviewSystemTest
{
    private $conn;
    private $testUserId;
    private $testCarwashId;

    public function __construct()
    {
        $this->conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
        $this->setupTestData();
    }

    private function setupTestData()
    {
        // Create test user
        $stmt = $this->conn->prepare("
            INSERT INTO users (name, email, password) 
            VALUES ('Test User', 'test@example.com', 'hashedpass')
        ");
        $stmt->execute();
        $this->testUserId = $this->conn->insert_id;

        // Create test carwash
            $stmt = $this->conn->prepare("
                INSERT INTO carwashes (name, address) 
                VALUES ('Test CarWash', 'Test Address')
            ");
        $stmt->execute();
        $this->testCarwashId = $this->conn->insert_id;
    }

    public function runTests()
    {
        $this->testReviewCreation();
        $this->testReviewModeration();
        $this->testNotifications();
        $this->cleanup();
    }

    private function testReviewCreation()
    {
        $stmt = $this->conn->prepare("
            INSERT INTO reviews (user_id, carwash_id, rating, comment)
            VALUES (?, ?, 5, 'Test Review')
        ");
        $stmt->bind_param('ii', $this->testUserId, $this->testCarwashId);

        assert($stmt->execute(), 'Review creation failed');
    }

    private function cleanup()
    {
        $this->conn->query("DELETE FROM reviews WHERE user_id = {$this->testUserId}");
        $this->conn->query("DELETE FROM users WHERE id = {$this->testUserId}");
    $this->conn->query("DELETE FROM carwashes WHERE id = {$this->testCarwashId}");
    }
}

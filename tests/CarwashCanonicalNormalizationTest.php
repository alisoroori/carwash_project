<?php
use PHPUnit\Framework\TestCase;
require_once __DIR__ . '/../vendor/autoload.php';

class CarwashCanonicalNormalizationTest extends TestCase {
    protected $db;
    protected $pdo;
    protected $inserted = [];

    protected function setUp(): void {
        $this->db = App\Classes\Database::getInstance();
        $this->pdo = $this->db->getPdo();
        $this->pdo->beginTransaction();
    }

    protected function tearDown(): void {
        // rollback to clean up
        if ($this->pdo->inTransaction()) $this->pdo->rollBack();
    }

    public function testOpenCarwashIsSelected() {
        // Insert canonical open
        $stmt = $this->pdo->prepare("INSERT INTO carwashes (name, status, created_at, updated_at) VALUES (:name, :status, NOW(), NOW())");
        $stmt->execute(['name' => 'TEST_OPEN_'.uniqid(), 'status' => 'Açık']);
        $idOpen = $this->pdo->lastInsertId();

        // Run app's canonical select
        $sql = "SELECT id FROM carwashes WHERE LOWER(COALESCE(status,'')) IN ('açık','acik','open','active','1') AND COALESCE(is_active,0) = 1";
        $rows = $this->db->fetchAll($sql);
        $ids = array_map(function($r){ return (int)$r['id']; }, $rows);
        $this->assertContains((int)$idOpen, $ids, 'Carwash with status "Açık" should be selected');
    }

    public function testClosedCarwashIsExcluded() {
        $stmt = $this->pdo->prepare("INSERT INTO carwashes (name, status, created_at, updated_at) VALUES (:name, :status, NOW(), NOW())");
        $stmt->execute(['name' => 'TEST_CLOSED_'.uniqid(), 'status' => 'Kapalı']);
        $idClosed = $this->pdo->lastInsertId();

        $sql = "SELECT id FROM carwashes WHERE LOWER(COALESCE(status,'')) IN ('açık','acik','open','active','1') AND COALESCE(is_active,0) = 1";
        $rows = $this->db->fetchAll($sql);
        $ids = array_map(function($r){ return (int)$r['id']; }, $rows);
        $this->assertNotContains((int)$idClosed, $ids, 'Carwash with status "Kapalı" should not be selected');
    }
}

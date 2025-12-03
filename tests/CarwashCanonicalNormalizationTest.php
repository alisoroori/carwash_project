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
        $sql = "SELECT id FROM carwashes WHERE (status = 'Açık' OR LOWER(COALESCE(status,'')) IN ('açık','acik','open','active') OR status = '1') AND LOWER(COALESCE(status,'')) NOT IN ('kapalı','kapali','closed','inactive') AND COALESCE(status,'') != '0'";
        $rows = $this->db->fetchAll($sql);
        $ids = array_map(function($r){ return (int)$r['id']; }, $rows);
        $this->assertContains((int)$idOpen, $ids, 'Carwash with status "Açık" should be selected');
    }

    public function testClosedCarwashIsExcluded() {
        $stmt = $this->pdo->prepare("INSERT INTO carwashes (name, status, created_at, updated_at) VALUES (:name, :status, NOW(), NOW())");
        $stmt->execute(['name' => 'TEST_CLOSED_'.uniqid(), 'status' => 'Kapalı']);
        $idClosed = $this->pdo->lastInsertId();

        $sql = "SELECT id FROM carwashes WHERE (status = 'Açık' OR LOWER(COALESCE(status,'')) IN ('açık','acik','open','active') OR status = '1') AND LOWER(COALESCE(status,'')) NOT IN ('kapalı','kapali','closed','inactive') AND COALESCE(status,'') != '0'";
        $rows = $this->db->fetchAll($sql);
        $ids = array_map(function($r){ return (int)$r['id']; }, $rows);
        $this->assertNotContains((int)$idClosed, $ids, 'Carwash with status "Kapalı" should not be selected');
    }
}

<?php
use PHPUnit\Framework\TestCase;
require_once __DIR__ . '/../vendor/autoload.php';

class AutomatedTogglePhpUnitTest extends TestCase {
    protected $db;
    protected $pdo;
    protected $carwashId;

    protected function setUp(): void {
        $this->db = App\Classes\Database::getInstance();
        $this->pdo = $this->db->getPdo();
        // Start transaction for test isolation
        $this->pdo->beginTransaction();

        // Insert a temporary carwash to test toggling
        $stmt = $this->pdo->prepare("INSERT INTO carwashes (name, status, is_active, created_at, updated_at) VALUES (:name, :status, :ia, NOW(), NOW())");
        $name = 'PHPUNIT_TEST_CW_'.bin2hex(random_bytes(4));
        $stmt->execute(['name' => $name, 'status' => 'Kapalı', 'ia' => 0]);
        $this->carwashId = (int)$this->pdo->lastInsertId();
    }

    protected function tearDown(): void {
        // Rollback to remove test row
        if ($this->pdo->inTransaction()) {
            $this->pdo->rollBack();
        }
    }

    protected function visibleIds(): array {
        $sql = "SELECT id FROM carwashes WHERE (status = 'Açık' OR LOWER(COALESCE(status,'')) IN ('açık','acik','open','active') OR status = '1') AND LOWER(COALESCE(status,'')) NOT IN ('kapalı','kapali','closed','inactive') AND COALESCE(status,'') != '0'";
        $rows = $this->db->fetchAll($sql);
        return array_map(function($r){ return (int)$r['id']; }, $rows);
    }

    public function testToggleOpenThenClosed(): void {
        $id = $this->carwashId;

        // Set to Açık
        $upd = $this->pdo->prepare('UPDATE carwashes SET status = :s, is_active = :ia, updated_at = NOW() WHERE id = :id');
        $upd->execute(['s' => 'Açık', 'ia' => 1, 'id' => $id]);

        $row = $this->db->fetchOne('SELECT id, status, COALESCE(is_active,0) AS is_active FROM carwashes WHERE id = :id', ['id' => $id]);
        $this->assertSame('Açık', $row['status']);
        $this->assertEquals(1, (int)$row['is_active']);

        $ids = $this->visibleIds();
        $this->assertContains($id, $ids, 'Carwash set to Açık should be visible in customer query');

        // Now set to Kapalı
        $upd->execute(['s' => 'Kapalı', 'ia' => 0, 'id' => $id]);
        $row = $this->db->fetchOne('SELECT id, status, COALESCE(is_active,0) AS is_active FROM carwashes WHERE id = :id', ['id' => $id]);
        $this->assertSame('Kapalı', $row['status']);
        $this->assertEquals(0, (int)$row['is_active']);

        $ids = $this->visibleIds();
        $this->assertNotContains($id, $ids, 'Carwash set to Kapalı should NOT be visible in customer query');
    }
}

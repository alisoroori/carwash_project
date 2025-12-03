<?php
use PHPUnit\Framework\TestCase;
use App\Classes\Database;

/**
 * Integration test to verify carwash visibility SQL filter
 * Tests the canonical status values: 'Açık' (open) and 'Kapalı' (closed)
 *
 * Test cases:
 * 1. Insert carwash with status='Açık' → should appear in customer query
 * 2. Update to status='Kapalı' → should NOT appear in customer query
 * 3. Test legacy values ('open', 'active', '1') for backward compatibility
 * 4. Verify toggle state persistence simulation
 *
 * NOTE: This test uses transactions to ensure clean rollback.
 */
class CarwashVisibilityTest extends TestCase
{
    /** @var Database */
    protected static $db;
    /** @var \PDO */
    protected $pdo;
    protected $insertId;

    public static function setUpBeforeClass(): void
    {
        self::$db = Database::getInstance();
    }

    protected function setUp(): void
    {
        $this->pdo = self::$db->getPdo();
        // Use a transaction so the test can cleanly rollback
        if (!$this->pdo->inTransaction()) $this->pdo->beginTransaction();
    }

    protected function tearDown(): void
    {
        // Rollback any changes to leave DB clean
        if ($this->pdo && $this->pdo->inTransaction()) {
            $this->pdo->rollBack();
        }
    }

    /**
     * The canonical visibility SQL used across the project
     */
    private function getVisibilitySQL(): string
    {
        return "SELECT * FROM carwashes
                WHERE (
                    status = 'Açık'
                    OR LOWER(COALESCE(status,'')) IN ('açık','acik','open','active')
                    OR status = '1'
                )
                  AND LOWER(COALESCE(status,'')) NOT IN ('kapalı','kapali','closed','inactive')
                  AND COALESCE(status,'') != '0'
                ORDER BY name";
    }

    /**
     * Check if a carwash ID exists in query results
     */
    private function isCarwashVisible(int $id): bool
    {
        $stmt = $this->pdo->prepare($this->getVisibilitySQL());
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($rows as $r) {
            if (isset($r['id']) && (int)$r['id'] === $id) {
                return true;
            }
        }
        return false;
    }

    /**
     * Test 1: Canonical 'Açık' status should be visible
     */
    public function test_canonical_open_status_is_visible()
    {
        $name = 'zz_test_acik_' . time();
        $id = self::$db->insert('carwashes', [
            'name' => $name,
            'user_id' => 999999,
            'status' => 'Açık'
        ]);
        
        $this->assertNotFalse($id, 'Insert should return new id');
        $this->insertId = (int)$id;
        
        $this->assertTrue(
            $this->isCarwashVisible($this->insertId),
            "Carwash with status='Açık' should be visible to customers"
        );
    }

    /**
     * Test 2: Canonical 'Kapalı' status should NOT be visible
     */
    public function test_canonical_closed_status_is_hidden()
    {
        $name = 'zz_test_kapali_' . time();
        $id = self::$db->insert('carwashes', [
            'name' => $name,
            'user_id' => 999998,
            'status' => 'Kapalı'
        ]);
        
        $this->assertNotFalse($id, 'Insert should return new id');
        $this->insertId = (int)$id;
        
        $this->assertFalse(
            $this->isCarwashVisible($this->insertId),
            "Carwash with status='Kapalı' should NOT be visible to customers"
        );
    }

    /**
     * Test 3: Toggle from open to closed should hide carwash
     */
    public function test_toggle_open_to_closed()
    {
        $name = 'zz_test_toggle_' . time();
        $id = self::$db->insert('carwashes', [
            'name' => $name,
            'user_id' => 999997,
            'status' => 'Açık'
        ]);
        
        $this->assertNotFalse($id, 'Insert should return new id');
        $this->insertId = (int)$id;
        
        // Initially visible
        $this->assertTrue(
            $this->isCarwashVisible($this->insertId),
            'Open carwash should initially be visible'
        );
        
        // Toggle to closed
        $ok = self::$db->update('carwashes', ['status' => 'Kapalı'], ['id' => $this->insertId]);
        $this->assertTrue($ok, 'Update to Kapalı should succeed');
        
        // Now hidden
        $this->assertFalse(
            $this->isCarwashVisible($this->insertId),
            'After toggle to Kapalı, carwash should NOT be visible'
        );
        
        // Toggle back to open
        $ok = self::$db->update('carwashes', ['status' => 'Açık'], ['id' => $this->insertId]);
        $this->assertTrue($ok, 'Update back to Açık should succeed');
        
        // Visible again
        $this->assertTrue(
            $this->isCarwashVisible($this->insertId),
            'After toggle back to Açık, carwash should be visible again'
        );
    }

    /**
     * Test 4: Legacy 'open' value should still be visible (backward compatibility)
     */
    public function test_legacy_open_value_is_visible()
    {
        $name = 'zz_test_legacy_open_' . time();
        $id = self::$db->insert('carwashes', [
            'name' => $name,
            'user_id' => 999996,
            'status' => 'open'
        ]);
        
        $this->assertNotFalse($id, 'Insert should return new id');
        $this->insertId = (int)$id;
        
        $this->assertTrue(
            $this->isCarwashVisible($this->insertId),
            "Legacy status='open' should still be visible (backward compatibility)"
        );
    }

    /**
     * Test 5: Legacy 'closed' value should NOT be visible
     */
    public function test_legacy_closed_value_is_hidden()
    {
        $name = 'zz_test_legacy_closed_' . time();
        $id = self::$db->insert('carwashes', [
            'name' => $name,
            'user_id' => 999995,
            'status' => 'closed'
        ]);
        
        $this->assertNotFalse($id, 'Insert should return new id');
        $this->insertId = (int)$id;
        
        $this->assertFalse(
            $this->isCarwashVisible($this->insertId),
            "Legacy status='closed' should NOT be visible"
        );
    }

    /**
     * Test 6: Status persistence after simulated refresh
     */
    public function test_status_persists_after_refresh()
    {
        $name = 'zz_test_persist_' . time();
        $id = self::$db->insert('carwashes', [
            'name' => $name,
            'user_id' => 999994,
            'status' => 'Açık'
        ]);
        
        $this->assertNotFalse($id, 'Insert should return new id');
        $this->insertId = (int)$id;
        
        // Set to closed
        self::$db->update('carwashes', ['status' => 'Kapalı'], ['id' => $this->insertId]);
        
        // Simulate "refresh" by re-reading from DB
        $row = self::$db->fetchOne('SELECT status FROM carwashes WHERE id = :id', ['id' => $this->insertId]);
        
        $this->assertEquals('Kapalı', $row['status'], 'Status should persist as Kapalı after simulated refresh');
        $this->assertFalse($this->isCarwashVisible($this->insertId), 'Closed carwash should remain hidden after refresh');
    }
}

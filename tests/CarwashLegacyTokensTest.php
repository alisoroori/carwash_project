<?php
use PHPUnit\Framework\TestCase;
require_once __DIR__ . '/../vendor/autoload.php';

class CarwashLegacyTokensTest extends TestCase {
    protected $db;
    protected $pdo;
    protected $insertedIds = [];

    protected function setUp(): void {
        $this->db = App\Classes\Database::getInstance();
        $this->pdo = $this->db->getPdo();
        $this->pdo->beginTransaction();
    }

    protected function tearDown(): void {
        if ($this->pdo->inTransaction()) {
            $this->pdo->rollBack();
        }
    }

    protected function visibleIds(): array {
        $sql = "SELECT id FROM carwashes WHERE LOWER(COALESCE(status,'')) IN ('açık','acik','open','active','1') AND COALESCE(is_active,0) = 1";
        $rows = $this->db->fetchAll($sql);
        return array_map(function($r){ return (int)$r['id']; }, $rows);
    }

    public function testLegacyTokensVisibilityAndNormalization(): void {
        // tokens to test: token => expectedVisibleBeforeNormalization
        $tokens = [
            'Açık' => true,
            'acik' => true,
            'Acik' => true,
            'open' => true,
            'active' => true,
            '1' => false,
            'pending' => false,
            'Kapalı' => false
        ];

        // Insert test rows
        $stmt = $this->pdo->prepare("INSERT INTO carwashes (name, status, is_active, created_at, updated_at) VALUES (:name, :status, :ia, NOW(), NOW())");
        foreach ($tokens as $token => $expectedVisible) {
            $name = 'LEGACY_TEST_' . strtoupper(bin2hex(random_bytes(3)));
            $ia = ($expectedVisible ? 1 : 0);
            $stmt->execute(['name' => $name, 'status' => $token, 'ia' => $ia]);
            $this->insertedIds[$this->pdo->lastInsertId()] = ['status' => $token, 'expected' => $expectedVisible];
        }

        // Verify visibility before normalization
        $idsBefore = $this->visibleIds();
        foreach ($this->insertedIds as $id => $meta) {
            $in = in_array((int)$id, $idsBefore, true);
            $this->assertSame($meta['expected'], $in, "Token '{$meta['status']}' visibility before normalization should be " . ($meta['expected'] ? 'true' : 'false'));
        }

        // Run normalization SQL (same logic as normalization script) - should update open-like tokens to 'Açık'
        // Normalize open-like tokens to canonical 'Açık' and mark them as active
        $normSql = "UPDATE carwashes SET status = 'Açık', is_active = 1 WHERE (LOWER(COALESCE(status,'')) IN ('açık','acik','open','active')) AND LOWER(COALESCE(status,'')) NOT IN ('kapalı','kapali','closed','inactive') AND COALESCE(status,'') != '0'";
        $this->pdo->exec($normSql);

        // Verify normalization
        foreach ($this->insertedIds as $id => $meta) {
            $row = $this->db->fetchOne('SELECT id, status, COALESCE(is_active,0) AS is_active FROM carwashes WHERE id = :id', ['id' => $id]);
            if (in_array(strtolower((string)$meta['status']), ['açık','acik','open','active'])) {
                $this->assertSame('Açık', $row['status'], "Token '{$meta['status']}' should have been normalized to 'Açık'");
            } elseif ($meta['status'] === 'pending') {
                $this->assertSame('pending', $row['status'], "'pending' should remain unchanged by normalization");
            } elseif ($meta['status'] === 'Kapalı') {
                $this->assertSame('Kapalı', $row['status'], "'Kapalı' should remain unchanged");
            }
        }

        // After normalization, only the normalized open tokens should be visible
        $idsAfter = $this->visibleIds();
        foreach ($this->insertedIds as $id => $meta) {
            $shouldBeVisible = in_array(strtolower((string)$meta['status']), ['açık','acik','open','active']);
            $in = in_array((int)$id, $idsAfter, true);
            $this->assertSame($shouldBeVisible, $in, "After normalization, token '{$meta['status']}' visibility should be " . ($shouldBeVisible ? 'true' : 'false'));
        }
    }
}

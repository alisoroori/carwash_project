<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class BookingsTest extends TestCase
{
    private $db;
    private $createdIds = [];

    public static function setUpBeforeClass(): void
    {
        // If DB_TEST_* vars are provided, copy them into DB_* so Database::getInstance uses test DB
        $testHost = getenv('DB_TEST_HOST');
        $testName = getenv('DB_TEST_NAME');
        $testUser = getenv('DB_TEST_USER');
        $testPass = getenv('DB_TEST_PASS');

        if ($testHost && $testName) {
            putenv("DB_HOST={$testHost}");
            putenv("DB_NAME={$testName}");
            if ($testUser !== false) putenv("DB_USER={$testUser}");
            if ($testPass !== false) putenv("DB_PASS={$testPass}");
        }
    }

    protected function setUp(): void
    {
        if (!getenv('DB_NAME')) {
            $this->markTestSkipped('DB_NAME not configured; set DB_TEST_* or DB_* env vars to run DB tests.');
        }

        require_once __DIR__ . '/../../vendor/autoload.php';
        $this->db = \App\Classes\Database::getInstance();
    }

    public function testCreateAndListBooking(): void
    {
        // Create minimal required entities: user, carwash, service
        $userId = $this->db->insert('users', [
            'username' => 'test_user_' . uniqid(),
            'full_name' => 'Test User',
            'email' => 'test+' . uniqid() . '@example.test',
            'password' => password_hash('password', PASSWORD_DEFAULT),
            'role' => 'customer',
            'is_active' => 1,
            'email_verified' => 1,
        ]);
        $this->assertIsInt($userId);
        $this->createdIds['users'][] = $userId;

        $cwId = $this->db->insert('carwashes', [
            'name' => 'Test Carwash ' . uniqid(),
            'address' => 'Test Address',
        ]);
        $this->assertIsInt($cwId);
        $this->createdIds['carwashes'][] = $cwId;

        $serviceId = $this->db->insert('services', [
            'carwash_id' => $cwId,
            'name' => 'Test Service',
            'price' => 9.99,
        ]);
        $this->assertIsInt($serviceId);
        $this->createdIds['services'][] = $serviceId;

        // Create booking
        $bookingId = $this->db->insert('bookings', [
            'user_id' => $userId,
            'carwash_id' => $cwId,
            'service_id' => $serviceId,
            'booking_date' => date('Y-m-d'),
            'booking_time' => date('H:i:s'),
            'status' => 'pending',
            'total_price' => 9.99,
        ]);
        $this->assertIsInt($bookingId);
        $this->createdIds['bookings'][] = $bookingId;

        // Now run the same query as the API list for this user
        $rows = $this->db->fetchAll(
            "SELECT b.*, u.full_name AS user_name, s.name AS service_name
             FROM bookings b
             LEFT JOIN users u ON u.id = b.user_id
             LEFT JOIN services s ON s.id = b.service_id
             WHERE b.user_id = :uid
             ORDER BY b.id DESC",
            ['uid' => $userId]
        );

        $this->assertIsArray($rows);
        $this->assertNotEmpty($rows);

        $found = false;
        foreach ($rows as $r) {
            if ((int)$r['id'] === $bookingId) { $found = true; break; }
        }

        $this->assertTrue($found, 'Inserted booking must appear in listing');
    }

    protected function tearDown(): void
    {
        if (empty($this->db) || empty($this->createdIds)) return;
        // Cleanup in reverse order
        foreach (['bookings','services','carwashes','users'] as $tbl) {
            if (!empty($this->createdIds[$tbl])) {
                foreach ($this->createdIds[$tbl] as $id) {
                    try { $this->db->delete($tbl, ['id' => $id]); } catch (\Throwable $e) {}
                }
            }
        }
    }
}

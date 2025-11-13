<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class BookingsCrudTest extends TestCase
{
    private $db;
    private $created = [];

    protected function setUp(): void
    {
        require_once __DIR__ . '/../../vendor/autoload.php';
        $this->db = \App\Classes\Database::getInstance();
        if (!getenv('DB_NAME')) {
            $this->markTestSkipped('DB not configured; set DB_TEST_* or DB_* env vars to run DB tests.');
        }
    }

    public function testCreateUpdateListBooking(): void
    {
        // Create user
        $userId = $this->db->insert('users', [
            'username' => 'tuser_' . uniqid(),
            'full_name' => 'Test User',
            'email' => 't+' . uniqid() . '@example.test',
            'password' => password_hash('password', PASSWORD_DEFAULT),
            'role' => 'customer',
            'is_active' => 1,
        ]);
        $this->assertIsInt($userId);
        $this->created['users'][] = $userId;

        // Create carwash & service
        $cwId = $this->db->insert('carwashes', ['name' => 'Test CW ' . uniqid(), 'address' => 'Addr']);
        $this->created['carwashes'][] = $cwId;
        $serviceId = $this->db->insert('services', ['carwash_id' => $cwId, 'name' => 'S', 'price' => 5.0]);
        $this->created['services'][] = $serviceId;

        // Create booking
        $bookingId = $this->db->insert('bookings', [
            'user_id' => $userId,
            'carwash_id' => $cwId,
            'service_id' => $serviceId,
            'booking_date' => date('Y-m-d'),
            'booking_time' => date('H:i:s'),
            'status' => 'pending',
            'total_price' => 5.0,
        ]);
        $this->assertIsInt($bookingId);
        $this->created['bookings'][] = $bookingId;

        // Update booking status
        $this->db->update('bookings', ['status' => 'confirmed'], ['id' => $bookingId]);

        // List bookings for user and ensure updated status
        $rows = $this->db->fetchAll('SELECT * FROM bookings WHERE user_id = :uid', ['uid' => $userId]);
        $this->assertNotEmpty($rows);
        $found = false;
        foreach ($rows as $r) {
            if ((int)$r['id'] === $bookingId) {
                $found = true;
                $this->assertEquals('confirmed', $r['status']);
                break;
            }
        }
        $this->assertTrue($found, 'Booking should be present after create');
    }

    protected function tearDown(): void
    {
        if (empty($this->db) || empty($this->created)) return;
        foreach (['bookings','services','carwashes','users'] as $tbl) {
            if (!empty($this->created[$tbl])) {
                foreach ($this->created[$tbl] as $id) {
                    try { $this->db->delete($tbl, ['id' => $id]); } catch (\Throwable $e) {}
                }
            }
        }
    }
}

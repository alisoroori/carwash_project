<?php

namespace CarWash\Tests;

use PHPUnit\Framework\TestCase;
use PDO;

class EndToEndTest extends TestCase
{
    protected $pdo;
    protected $baseUrl = 'http://localhost/carwash_project';

    protected function setUp(): void
    {
        parent::setUp();

        $this->initializeDatabase();
    }

    protected function initializeDatabase(): void
    {
        $this->pdo = new PDO(
            'mysql:host=localhost;dbname=carwash_test;charset=utf8mb4',
            'root',
            '',
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
    }

    protected function tearDown(): void
    {
        if ($this->pdo) {
            // Clean up test data
            $this->pdo->exec('TRUNCATE TABLE bookings');
            $this->pdo->exec('TRUNCATE TABLE users');
            $this->pdo = null;
        }

        parent::tearDown();
    }

    public function testDatabaseConnection(): void
    {
        $result = $this->pdo->query('SELECT 1')->fetch();
        $this->assertEquals(1, $result[0], 'Database connection should work');
    }
}

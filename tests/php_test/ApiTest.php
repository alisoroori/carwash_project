<?php

namespace CarWash\Tests;

use PHPUnit\Framework\TestCase;
use PDO;

class ApiTest extends TestCase
{
    protected $pdo;
    protected $baseUrl = 'http://localhost/carwash_project';

    protected function setUp(): void
    {
        parent::setUp();
        $this->initializeTestDatabase();
    }

    protected function initializeTestDatabase(): void
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

    public function testApiEndpointExample(): void
    {
        $response = $this->makeRequest('/backend/api/example-endpoint.php');
        $this->assertNotFalse($response);
        $data = json_decode($response, true);
        $this->assertIsArray($data);
    }

    /**
     * Makes an HTTP request to the specified API endpoint.
     *
     * @param string $endpoint Relative API endpoint path (e.g., '/backend/api/example-endpoint.php').
     * @param string $method HTTP method to use ('GET' or 'POST').
     * @param array|null $data Optional data to send with POST requests.
     * @return string|false The response body as a string, or false on failure.
     */
    protected function makeRequest(string $endpoint, string $method = 'GET', ?array $data = null): string|false
    {
        $opts = [
            'http' => [
                'method' => $method,
                'header' => 'Content-Type: application/json',
                'ignore_errors' => true
            ]
        ];

        if ($data && $method === 'POST') {
            $opts['http']['content'] = json_encode($data);
        }

        return file_get_contents($this->baseUrl . $endpoint, false, stream_context_create($opts));
    }
}

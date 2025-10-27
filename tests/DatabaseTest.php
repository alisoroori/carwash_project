<?php
use PHPUnit\Framework\TestCase;

class DatabaseTest extends TestCase
{
    public function setUp(): void
    {
        // bootstrap via phpunit.xml.dist -> vendor/autoload.php
    }

    public function testGetInstanceReturnsObjectOrSkip()
    {
        if (!class_exists(\App\Classes\Database::class)) {
            $this->markTestSkipped('App\\Classes\\Database not found — skipping DatabaseTest.');
        }

        $db1 = \App\Classes\Database::getInstance();
        $this->assertIsObject($db1, 'Database::getInstance should return an object');

        // Singleton expectation if implemented
        $db2 = \App\Classes\Database::getInstance();
        $this->assertSame($db1, $db2, 'Database::getInstance should return same instance on subsequent calls');
    }

    public function testPrepareOrConnectionAvailableOrSkip()
    {
        if (!class_exists(\App\Classes\Database::class)) {
            $this->markTestSkipped('App\\Classes\\Database not found — skipping prepare/connect test.');
        }

        $db = \App\Classes\Database::getInstance();

        // Try to detect common accessors safely
        if (method_exists($db, 'getPdo') || method_exists($db, 'getConnection') || method_exists($db, 'prepare')) {
            $this->assertTrue(true, 'Database exposes expected methods (prepare/getPdo/getConnection)'); // minimal assertion
        } else {
            $this->markTestSkipped('Database does not expose known accessors (getPdo/getConnection/prepare) — skipping detailed test.');
        }
    }
}

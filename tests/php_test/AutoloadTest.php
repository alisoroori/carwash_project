<?php
use PHPUnit\Framework\TestCase;

final class AutoloadTest extends TestCase
{
    public function testClassesAreAutoloadable(): void
    {
        require_once __DIR__ . '/../vendor/autoload.php';

        $this->assertTrue(class_exists(\App\Classes\Validator::class), 'Validator class should be autoloadable');
        $this->assertTrue(class_exists(\App\Classes\Response::class), 'Response class should be autoloadable');
    }
}
?>

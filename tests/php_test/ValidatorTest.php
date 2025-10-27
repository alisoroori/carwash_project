<?php
use PHPUnit\Framework\TestCase;
use App\Classes\Validator;

final class ValidatorTest extends TestCase
{
    public function setUp(): void
    {
        require_once __DIR__ . '/../vendor/autoload.php';
    }

    public function testSanitizeString(): void
    {
        $raw = "<script>alert('x')</script> Hello ";
        $san = Validator::sanitizeString($raw);

        $this->assertStringNotContainsString('<', $san);
        $this->assertStringContainsString('Hello', $san);
    }

    public function testSanitizeEmail(): void
    {
        $email = " test@example.com ";
        $san = Validator::sanitizeEmail($email);
        $this->assertEquals('test@example.com', $san);
    }

    public function testMinMaxLengthAndRequired(): void
    {
        $validator = new Validator();
        $validator
            ->required('abc', 'field')
            ->minLength('abc', 3, 'field')
            ->maxLength('abc', 5, 'field');

        $this->assertTrue($validator->passes());
    }

    public function testFailsOnInvalidEmail(): void
    {
        $validator = new Validator();
        $validator->email('not-an-email', 'ایمیل');

        $this->assertTrue($validator->fails());
        $this->assertNotEmpty($validator->getErrors());
    }
}
?>

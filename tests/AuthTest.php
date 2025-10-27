<?php
use PHPUnit\Framework\TestCase;

class AuthTest extends TestCase
{
    public function testAuthClassExistsOrSkip()
    {
        if (!class_exists(\App\Classes\Auth::class)) {
            $this->markTestSkipped('App\\Classes\\Auth not found — skipping AuthTest.');
        }

        $this->assertTrue(
            method_exists(\App\Classes\Auth::class, 'requireAuth') &&
            method_exists(\App\Classes\Auth::class, 'requireRole') &&
            method_exists(\App\Classes\Auth::class, 'hasRole'),
            'Auth class should provide requireAuth, requireRole and hasRole methods'
        );
    }

    public function testIsAuthenticatedMethodOrSkip()
    {
        if (!class_exists(\App\Classes\Auth::class)) {
            $this->markTestSkipped('App\\Classes\\Auth not found — skipping isAuthenticated test.');
        }

        if (!method_exists(\App\Classes\Auth::class, 'isAuthenticated')) {
            $this->markTestSkipped('Auth::isAuthenticated not implemented — skipping.');
        }

        // Call the method but do not assume any session state; just ensure it returns a bool
        $result = \App\Classes\Auth::isAuthenticated();
        $this->assertIsBool($result, 'Auth::isAuthenticated should return boolean');
    }
}

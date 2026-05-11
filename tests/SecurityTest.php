<?php

use PHPUnit\Framework\TestCase;

class SecurityTest extends TestCase
{
    protected function setUp(): void
    {
        // Initialiser une session PHP pour les tests CSRF
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION = [];
    }

    // --- Tests escape() XSS ---

    public function testEscapeProtectsAgainstXSS(): void
    {
        $input    = '<script>alert("xss")</script>';
        $expected = '&lt;script&gt;alert(&quot;xss&quot;)&lt;/script&gt;';
        $this->assertSame($expected, escape($input));
    }

    public function testEscapeHandlesSimpleString(): void
    {
        $this->assertSame('Hello World', escape('Hello World'));
    }

    public function testEscapeHandlesSingleQuotes(): void
    {
        $this->assertStringContainsString('&#039;', escape("l'apostrophe"));
    }

    public function testEscapeHandlesEmptyString(): void
    {
        $this->assertSame('', escape(''));
    }

    // --- Tests CSRF ---

    public function testGenerateCsrfTokenCreatesToken(): void
    {
        $token = generateCsrfToken();
        $this->assertNotEmpty($token);
        $this->assertEquals(64, strlen($token)); // 32 bytes → 64 hex chars
    }

    public function testGenerateCsrfTokenIsIdempotent(): void
    {
        $token1 = generateCsrfToken();
        $token2 = generateCsrfToken();
        $this->assertSame($token1, $token2);
    }

    public function testVerifyCsrfTokenSucceedsWithValidToken(): void
    {
        $token = generateCsrfToken();
        $this->assertTrue(verifyCsrfToken($token));
    }

    public function testVerifyCsrfTokenFailsWithWrongToken(): void
    {
        generateCsrfToken();
        $this->assertFalse(verifyCsrfToken('mauvais_token'));
    }

    public function testVerifyCsrfTokenFailsWithEmptyToken(): void
    {
        generateCsrfToken();
        $this->assertFalse(verifyCsrfToken(''));
    }

    public function testVerifyCsrfTokenFailsWithoutSession(): void
    {
        unset($_SESSION['csrf_token']);
        $this->assertFalse(verifyCsrfToken('nimporte_quoi'));
    }

    // --- Tests format token ---

    public function testCsrfTokenIsHexadecimal(): void
    {
        $token = generateCsrfToken();
        $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $token);
    }
}

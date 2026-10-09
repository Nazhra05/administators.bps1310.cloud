<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class HelpersTest extends TestCase
{
    public function testEscapingHelperE(): void
    {
        // Basic XSS vectors
        $this->assertSame('&lt;script&gt;alert(1)&lt;/script&gt;', e('<script>alert(1)</script>'));
        $this->assertSame('&quot;quoted&quot;', e('"quoted"'));
        $this->assertSame('&#039;single&#039;', e("'single'"));
        $this->assertSame('Tom &amp; Jerry', e('Tom & Jerry'));

        // Null and empty handling
        $this->assertSame('', e(null));
        $this->assertSame('', e(''));

        // Numbers and booleans
        $this->assertSame('123', e(123));
        $this->assertSame('12.34', e(12.34));
        $this->assertSame('0', e(0));

        // Multibyte UTF-8 preservation
        $this->assertSame('Solok Selatan — Berjaya &amp; Sejahtera', e('Solok Selatan — Berjaya & Sejahtera'));
    }

    public function testEnvHelper(): void
    {
        $_ENV['TEST_KEY_BOOL_TRUE'] = 'true';
        $_ENV['TEST_KEY_BOOL_FALSE'] = 'false';
        $_ENV['TEST_KEY_STRING'] = 'SolokSelatan';

        $this->assertTrue(env('TEST_KEY_BOOL_TRUE'));
        $this->assertFalse(env('TEST_KEY_BOOL_FALSE'));
        $this->assertSame('SolokSelatan', env('TEST_KEY_STRING'));
        $this->assertSame('default_fallback', env('NON_EXISTENT_KEY', 'default_fallback'));
    }

    public function testUrlHelper(): void
    {
        $home = url('/');
        $this->assertStringStartsWith('/', $home);

        $login = url('login.php');
        $this->assertStringContainsString('login.php', $login);
    }

    public function testCsrfHelpers(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            @session_start();
        }

        unset($_SESSION['csrf_token']);
        $token = csrf_token();
        $this->assertNotEmpty($token);
        $this->assertSame(64, strlen($token)); // 32 random bytes in hex = 64 chars

        $field = csrf_field();
        $this->assertStringContainsString('type="hidden"', $field);
        $this->assertStringContainsString('name="csrf_token"', $field);
        $this->assertStringContainsString('value="' . $token . '"', $field);
    }
}

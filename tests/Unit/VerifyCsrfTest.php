<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Http\Middleware\VerifyCsrf;
use App\Support\Csrf;
use App\Support\Session;
use PHPUnit\Framework\TestCase;

class VerifyCsrfTest extends TestCase
{
    protected function setUp(): void
    {
        require_once dirname(__DIR__, 2) . '/src/bootstrap.php';
        Session::destroy();
        $_SESSION = [];
        $_POST = [];
        $_SERVER['REQUEST_METHOD'] = 'GET';
        unset($_SERVER['HTTP_X_CSRF_TOKEN'], $_SERVER['HTTP_X_XSRF_TOKEN']);
    }

    protected function tearDown(): void
    {
        Session::destroy();
        $_SESSION = [];
        $_POST = [];
        unset($_SERVER['HTTP_X_CSRF_TOKEN'], $_SERVER['HTTP_X_XSRF_TOKEN']);
    }

    public function testTokenGeneratesAndPersistsInSession(): void
    {
        $token1 = Csrf::token();
        $this->assertSame(64, strlen($token1));
        $this->assertNotEmpty($_SESSION['csrf_token']);

        // Subsequent call returns identical token
        $token2 = Csrf::token();
        $this->assertSame($token1, $token2);
    }

    public function testFieldOutputsValidHiddenInput(): void
    {
        $field = Csrf::field();
        $this->assertStringContainsString('<input type="hidden" name="csrf_token" value="', $field);
        $this->assertStringContainsString(Csrf::token(), $field);
    }

    public function testVerifyWithMatchingToken(): void
    {
        $token = Csrf::token();

        // Direct argument
        $this->assertTrue(Csrf::verify($token));

        // Via $_POST
        $_POST['csrf_token'] = $token;
        $this->assertTrue(Csrf::verify());

        // Via HTTP_X_CSRF_TOKEN header
        unset($_POST['csrf_token']);
        $_SERVER['HTTP_X_CSRF_TOKEN'] = $token;
        $this->assertTrue(Csrf::verify());

        // Via HTTP_X_XSRF_TOKEN header
        unset($_SERVER['HTTP_X_CSRF_TOKEN']);
        $_SERVER['HTTP_X_XSRF_TOKEN'] = $token;
        $this->assertTrue(Csrf::verify());
    }

    public function testVerifyFailsOnMismatchOrEmpty(): void
    {
        Csrf::token();

        $this->assertFalse(Csrf::verify('invalid_token_value'));
        $this->assertFalse(Csrf::verify(''));

        $_POST['csrf_token'] = 'tampered';
        $this->assertFalse(Csrf::verify());

        // When session token is empty
        $_SESSION['csrf_token'] = '';
        $this->assertFalse(Csrf::verify('some_token'));
    }

    public function testMiddlewarePassesGetRequestsWithoutToken(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $middleware = new VerifyCsrf();
        $called = false;
        $result = $middleware->handle([], function ($vars) use (&$called) {
            $called = true;
            return 'passed';
        });

        $this->assertTrue($called);
        $this->assertSame('passed', $result);
    }

    public function testMiddlewarePassesPostRequestWithValidToken(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $token = Csrf::token();
        $_POST['csrf_token'] = $token;

        $middleware = new VerifyCsrf();
        $called = false;
        $result = $middleware->handle([], function ($vars) use (&$called) {
            $called = true;
            return 'passed_post';
        });

        $this->assertTrue($called);
        $this->assertSame('passed_post', $result);
    }
}

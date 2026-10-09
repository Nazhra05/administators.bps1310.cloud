<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\Session;
use PHPUnit\Framework\TestCase;

class SessionTest extends TestCase
{
    protected function setUp(): void
    {
        require_once dirname(__DIR__, 2) . '/src/bootstrap.php';
        Session::destroy();
        $_SESSION = [];
    }

    protected function tearDown(): void
    {
        Session::destroy();
        $_SESSION = [];
    }

    public function testSessionStartAndBasicGetSet(): void
    {
        Session::set('user_key', 'hello_world');
        $this->assertTrue(Session::has('user_key'));
        $this->assertSame('hello_world', Session::get('user_key'));

        Session::remove('user_key');
        $this->assertFalse(Session::has('user_key'));
        $this->assertNull(Session::get('user_key'));
    }

    public function testSessionFlash(): void
    {
        Session::flash('info', 'Selamat datang kembali');
        $this->assertSame('Selamat datang kembali', Session::flash('info'));
        // Second fetch should return null (single-use flash)
        $this->assertNull(Session::flash('info'));
    }

    public function testInactivityTimeoutDestroysSession(): void
    {
        Session::start();
        $_ENV['SESSION_LIFETIME_MIN'] = '5'; // 5 minutes limit
        Session::set('admin_id', 1);
        Session::set('admin_username', 'admin');
        // Simulate inactive for 10 minutes (600s ago)
        $_SESSION['last_activity'] = time() - 600;
        $_SESSION['created_time'] = time() - 600;

        Session::checkTimeout();

        // admin_id should be cleared because session timed out
        $this->assertNull(Session::get('admin_id'));
        $this->assertSame('Sesi Anda telah kedaluwarsa. Silakan masuk kembali.', Session::flash('error'));

        unset($_ENV['SESSION_LIFETIME_MIN']);
    }

    public function testAbsoluteTimeoutDestroysSession(): void
    {
        Session::start();
        Session::set('admin_id', 1);
        $_SESSION['last_activity'] = time(); // Active right now
        // But created 9 hours ago (> 8 hours limit)
        $_SESSION['created_time'] = time() - (9 * 3600);

        Session::checkTimeout();

        $this->assertNull(Session::get('admin_id'));
    }

    public function testActiveSessionUpdatesLastActivity(): void
    {
        Session::start();
        Session::set('admin_id', 1);
        $twoMinutesAgo = time() - 120;
        $_SESSION['last_activity'] = $twoMinutesAgo;
        $_SESSION['created_time'] = $twoMinutesAgo;

        Session::checkTimeout();

        $this->assertSame(1, Session::get('admin_id'));
        $this->assertGreaterThan($twoMinutesAgo, $_SESSION['last_activity']);
    }
}

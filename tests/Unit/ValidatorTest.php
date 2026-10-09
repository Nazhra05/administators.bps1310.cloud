<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\Validator;
use PHPUnit\Framework\TestCase;

class ValidatorTest extends TestCase
{
    public function testIntValidation(): void
    {
        $this->assertSame(42, Validator::int('42'));
        $this->assertSame(100, Validator::int(100));
        $this->assertSame(0, Validator::int(null));
        $this->assertSame(5, Validator::int('', 5));
        $this->assertSame(10, Validator::int('not-a-number', 10));

        // Range constraints
        $this->assertSame(15, Validator::int('15', 0, 10, 20));
        $this->assertSame(0, Validator::int('5', 0, 10, 20)); // below min
        $this->assertSame(0, Validator::int('25', 0, 10, 20)); // above max
        $this->assertSame(10, Validator::int('10', 0, 10, 20)); // exactly min
        $this->assertSame(20, Validator::int('20', 0, 10, 20)); // exactly max
    }

    public function testStringValidationAndTruncation(): void
    {
        $this->assertSame('halo dunia', Validator::string('  halo dunia  '));
        $this->assertSame('', Validator::string(null));
        $this->assertSame('default', Validator::string(null, 255, 'default'));

        // Max length constraint with multibyte UTF-8
        $longText = 'abcdefghij'; // 10 chars
        $this->assertSame('abcde', Validator::string($longText, 5));

        $utf8Text = 'Solok Selatan 🌟 Berjaya';
        $this->assertSame('Solok', Validator::string($utf8Text, 5));
    }

    public function testUrlValidation(): void
    {
        // Valid URLs
        $this->assertSame('https://bps.go.id', Validator::url('https://bps.go.id'));
        $this->assertSame('http://localhost:8080/path?query=1#frag', Validator::url('http://localhost:8080/path?query=1#frag'));

        // Rejected malicious / disallowed schemes
        $this->assertNull(Validator::url('javascript:alert(1)'));
        $this->assertNull(Validator::url('data:text/html;base64,PHNjcmlwdD4='));
        $this->assertNull(Validator::url('file:///etc/passwd'));
        $this->assertNull(Validator::url('ftp://ftp.example.com'));

        // Custom allowed schemes
        $this->assertSame('ftp://ftp.example.com', Validator::url('ftp://ftp.example.com', ['ftp']));

        // Invalid inputs
        $this->assertNull(Validator::url('not-a-valid-url'));
        $this->assertNull(Validator::url(''));
        $this->assertNull(Validator::url(null));
        $this->assertNull(Validator::url(['array']));
    }

    public function testEnumValidation(): void
    {
        $allowed = ['published', 'pending', 'draft', 'archived'];

        $this->assertSame('published', Validator::enum('published', $allowed));
        $this->assertSame('draft', Validator::enum('draft', $allowed));
        $this->assertNull(Validator::enum('unknown_status', $allowed));
        $this->assertSame('draft', Validator::enum('unknown_status', $allowed, 'draft'));

        // Strict comparison
        $this->assertNull(Validator::enum(0, ['0', '1']));
    }

    public function testEmailValidation(): void
    {
        $this->assertSame('user@bps.go.id', Validator::email('user@bps.go.id'));
        $this->assertSame('test.account+bps@domain.co.id', Validator::email('  test.account+bps@domain.co.id  '));

        $this->assertNull(Validator::email('invalid-email'));
        $this->assertNull(Validator::email('@domain.com'));
        $this->assertNull(Validator::email('user@'));
        $this->assertNull(Validator::email(''));
        $this->assertNull(Validator::email(null));
        $this->assertNull(Validator::email(12345));
    }
}

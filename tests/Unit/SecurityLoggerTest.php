<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Support\Logger;
use PHPUnit\Framework\TestCase;

class SecurityLoggerTest extends TestCase
{
    private string $testLogFile;

    protected function setUp(): void
    {
        require_once dirname(__DIR__, 2) . '/src/bootstrap.php';
        $this->testLogFile = dirname(__DIR__, 2) . '/storage/logs/test_security.log';
        Logger::setLogFile($this->testLogFile);
        if (file_exists($this->testLogFile)) {
            @unlink($this->testLogFile);
        }
    }

    protected function tearDown(): void
    {
        if (file_exists($this->testLogFile)) {
            @unlink($this->testLogFile);
        }
        Logger::setLogFile(dirname(__DIR__, 2) . '/storage/logs/app.log');
    }

    public function testRequestIdGeneratesValidHexId(): void
    {
        $id = request_id();
        $this->assertNotEmpty($id);
        $this->assertMatchesRegularExpression('/^[a-zA-Z0-9_\-\.]{8,64}$/', $id);
    }

    public function testSanitizerRedactsSensitiveContextKeys(): void
    {
        $sensitiveData = [
            'username'         => 'john_doe',
            'email'            => 'john@example.com',
            'password'         => 'SuperSecret123',
            'current_password' => 'OldPassword123',
            'new_password'     => 'NewPassword123',
            'csrf_token'       => 'token_1234567890',
            'api_key'          => 'API_KEY_SUPER_SECRET',
            'nested'           => [
                'token'       => 'nested_token_value',
                'description' => 'safe_text',
            ],
        ];

        $sanitized = Logger::sanitize($sensitiveData);

        $this->assertSame('john_doe', $sanitized['username']);
        $this->assertSame('john@example.com', $sanitized['email']);
        $this->assertSame('[REDACTED]', $sanitized['password']);
        $this->assertSame('[REDACTED]', $sanitized['current_password']);
        $this->assertSame('[REDACTED]', $sanitized['new_password']);
        $this->assertSame('[REDACTED]', $sanitized['csrf_token']);
        $this->assertSame('[REDACTED]', $sanitized['api_key']);
        $this->assertSame('[REDACTED]', $sanitized['nested']['token']);
        $this->assertSame('safe_text', $sanitized['nested']['description']);
    }

    public function testSanitizerRedactsSensitiveStringPatterns(): void
    {
        $msg = 'Failed request with password=secret123 and token=abcde999';
        $sanitized = Logger::sanitize($msg);

        $this->assertStringNotContainsString('secret123', $sanitized);
        $this->assertStringNotContainsString('abcde999', $sanitized);
        $this->assertStringContainsString('password=[REDACTED]', $sanitized);
        $this->assertStringContainsString('token=[REDACTED]', $sanitized);
    }

    public function testLoggerWritesSanitizedContentWithRequestId(): void
    {
        Logger::info('User login attempt', [
            'username' => 'admin',
            'password' => 'secret_pass_word',
        ]);

        $this->assertFileExists($this->testLogFile);
        $content = file_get_contents($this->testLogFile);

        $this->assertStringContainsString('[INFO]', $content);
        $this->assertStringContainsString('[req:', $content);
        $this->assertStringContainsString('"username":"admin"', $content);
        $this->assertStringNotContainsString('secret_pass_word', $content);
        $this->assertStringContainsString('"password":"[REDACTED]"', $content);
    }
}

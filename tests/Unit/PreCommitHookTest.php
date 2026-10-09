<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class PreCommitHookTest extends TestCase
{
    private string $hookPath;

    protected function setUp(): void
    {
        $this->hookPath = dirname(__DIR__, 2) . '/.githooks/pre-commit';
    }

    public function testHookFileExistsAndIsConfigured(): void
    {
        $this->assertFileExists($this->hookPath);
        $content = file_get_contents($this->hookPath);
        $this->assertIsString($content);

        // Asserts key safety checks are present
        $this->assertStringContainsString('.env', $content);
        $this->assertStringContainsString('\.sql$', $content);
        $this->assertStringContainsString('BEGIN', $content);
        $this->assertStringContainsString('PRIVATE KEY', $content);
    }

    public function testGitConfigUsesCustomHooksPath(): void
    {
        $output = [];
        $exitCode = 0;
        exec('git config core.hooksPath', $output, $exitCode);
        $hooksPath = trim(implode("\n", $output));

        $this->assertSame(0, $exitCode);
        $this->assertStringEndsWith('.githooks', $hooksPath);
    }
}

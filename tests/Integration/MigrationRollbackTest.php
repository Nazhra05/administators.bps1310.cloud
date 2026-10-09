<?php

declare(strict_types=1);

namespace Tests\Integration;

use PHPUnit\Framework\TestCase;

class MigrationRollbackTest extends TestCase
{
    public function testMigrationStatusAndIdempotentUp(): void
    {
        $statusOutput = [];
        $statusExit = 0;
        exec('php scripts/migrate.php --status --db=bps_solsel_test 2>&1', $statusOutput, $statusExit);
        $statusText = implode("\n", $statusOutput);

        $this->assertSame(0, $statusExit, 'Status command failed: ' . $statusText);
        $this->assertStringContainsString('Status Migrasi Database', $statusText);

        $upOutput = [];
        $upExit = 0;
        exec('php scripts/migrate.php --up --db=bps_solsel_test 2>&1', $upOutput, $upExit);
        $upText = implode("\n", $upOutput);

        $this->assertSame(0, $upExit, 'Up command failed: ' . $upText);
        $this->assertTrue(
            str_contains($upText, 'Database sudah mutakhir') || str_contains($upText, 'migrasi berhasil diterapkan')
        );
    }

    public function testMigrationRollbackAndReapply(): void
    {
        // 1. Rollback the most recent migration (step=1)
        $downOutput = [];
        $downExit = 0;
        exec('php scripts/migrate.php --down=1 --db=bps_solsel_test 2>&1', $downOutput, $downExit);
        $downText = implode("\n", $downOutput);

        $this->assertSame(0, $downExit, 'Down command failed: ' . $downText);
        $this->assertStringContainsString('Selesai rollback', $downText);

        // 2. Re-apply the migration back up
        $reapplyOutput = [];
        $reapplyExit = 0;
        exec('php scripts/migrate.php --up --db=bps_solsel_test 2>&1', $reapplyOutput, $reapplyExit);
        $reapplyText = implode("\n", $reapplyOutput);

        $this->assertSame(0, $reapplyExit, 'Re-apply command failed: ' . $reapplyText);
        $this->assertStringContainsString('migrasi berhasil diterapkan', $reapplyText);

        // 3. Reset test database cleanly to dev seed
        $resetOutput = [];
        $resetExit = 0;
        exec('php scripts/test-db-reset.php 2>&1', $resetOutput, $resetExit);
        $resetText = implode("\n", $resetOutput);

        $this->assertSame(0, $resetExit, 'Database reset failed: ' . $resetText);
    }
}

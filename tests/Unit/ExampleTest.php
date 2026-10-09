<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class ExampleTest extends TestCase
{
    public function testBasicAssertionWorks(): void
    {
        $this->assertTrue(true);
        $this->assertSame('bps_solsel_test', getenv('DB_NAME'));
    }
}

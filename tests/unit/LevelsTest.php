<?php

declare(strict_types=1);

namespace Inpsyde\Wonolog\Tests\Unit;

use Inpsyde\Wonolog\LogLevel;
use Inpsyde\Wonolog\Tests\UnitTestCase;

final class LevelsTest extends UnitTestCase
{
    public function testAllLevelsMethod(): void
    {
        $actual = LogLevel::allLevels();
        $this->assertCount(8, $actual);
        $this->assertArrayHasKey('DEBUG', $actual);
        $this->assertEquals(LogLevel::DEBUG, $actual['DEBUG']);
    }
}

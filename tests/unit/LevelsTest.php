<?php

declare(strict_types=1);

namespace Inpsyde\Wonolog\Tests\Unit;

use Inpsyde\Wonolog\LogLevel;
use Inpsyde\Wonolog\Tests\UnitTestCase;

class LevelsTest extends UnitTestCase
{
    public function testAllLevelsMethod(): void
    {
        $actual = LogLevel::allLevels();
        static::assertCount(8, $actual);
        static::assertArrayHasKey('DEBUG', $actual);
        static::assertEquals(LogLevel::DEBUG, $actual['DEBUG']);
    }
}

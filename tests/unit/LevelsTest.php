<?php

namespace Inpsyde\Wonolog\Tests\Unit;

use Inpsyde\Wonolog\Levels;
use Inpsyde\Wonolog\Tests\UnitTestCase;

class LevelsTest extends UnitTestCase
{
    public function testAllLevelsMethod(): void
    {
        $actual = Levels::allLevels();
        static::assertCount(8, $actual);
        static::assertArrayHasKey('DEBUG', $actual);
        static::assertEquals(Levels::DEBUG, $actual['DEBUG']);
    }
}
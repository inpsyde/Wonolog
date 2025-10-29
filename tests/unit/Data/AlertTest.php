<?php

declare(strict_types=1);

namespace Inpsyde\Wonolog\Tests\Unit\Data;

use Inpsyde\Wonolog\Channels;
use Inpsyde\Wonolog\Data\Alert;
use Inpsyde\Wonolog\Data\LogData;
use Inpsyde\Wonolog\LogLevel;
use Inpsyde\Wonolog\Tests\UnitTestCase;

class AlertTest extends UnitTestCase
{
    /**
     * @test
     */
    public function testImplementsLogDataInterface(): void
    {
        $alert = new Alert('Test alert message', Channels::DEBUG);

        static::assertInstanceOf(LogData::class, $alert);
    }
}

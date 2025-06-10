<?php

declare(strict_types=1);

namespace Inpsyde\Wonolog\Tests\Unit\Data;

use Inpsyde\Wonolog\Channels;
use Inpsyde\Wonolog\Data\Alert;
use Inpsyde\Wonolog\Data\Critical;
use Inpsyde\Wonolog\Data\Debug;
use Inpsyde\Wonolog\Data\Emergency;
use Inpsyde\Wonolog\Data\Error;
use Inpsyde\Wonolog\Data\Info;
use Inpsyde\Wonolog\Data\LogData;
use Inpsyde\Wonolog\Data\Notice;
use Inpsyde\Wonolog\Data\Warning;
use Inpsyde\Wonolog\LogLevel;
use Inpsyde\Wonolog\Tests\UnitTestCase;

class CustomLogDataTest extends UnitTestCase
{
    /**
     * @test
     * @dataProvider dataProviderLogLevels
     */
    public function testLevels(int $expectedLevel, LogData $log): void
    {
        static::assertSame(Channels::DEBUG, $log->channel());
        static::assertSame('test', $log->message());
        static::assertSame([], $log->context());
        static::assertSame($expectedLevel, $log->level());
    }

    /**
     * @return array<array{int, LogData}>
     */
    public function dataProviderLogLevels(): array
    {
        return [
            [LogLevel::ALERT, new Alert('test', Channels::DEBUG)],
            [LogLevel::CRITICAL, new Critical('test', Channels::DEBUG)],
            [LogLevel::DEBUG, new Debug('test', Channels::DEBUG)],
            [LogLevel::EMERGENCY, new Emergency('test', Channels::DEBUG)],
            [LogLevel::ERROR, new Error('test', Channels::DEBUG)],
            [LogLevel::INFO, new Info('test', Channels::DEBUG)],
            [LogLevel::NOTICE, new Notice('test', Channels::DEBUG)],
            [LogLevel::WARNING, new Warning('test', Channels::DEBUG)],
        ];
    }
}

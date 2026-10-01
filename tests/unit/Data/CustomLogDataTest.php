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

final class CustomLogDataTest extends UnitTestCase
{
    /**
     * @test
     * @dataProvider dataProviderLogLevels
     */
    public function testLevels(int $expectedLevel, LogData $log): void
    {
        $this->assertSame(Channels::DEBUG, $log->channel());
        $this->assertSame('test', $log->message());
        $this->assertSame([], $log->context());
        $this->assertSame($expectedLevel, $log->level());
    }

    /**
     * @return \Iterator<(int | string), array{int, LogData}>
     */
    public function dataProviderLogLevels(): \Iterator
    {
        yield [LogLevel::ALERT, new Alert('test', Channels::DEBUG)];
        yield [LogLevel::CRITICAL, new Critical('test', Channels::DEBUG)];
        yield [LogLevel::DEBUG, new Debug('test', Channels::DEBUG)];
        yield [LogLevel::EMERGENCY, new Emergency('test', Channels::DEBUG)];
        yield [LogLevel::ERROR, new Error('test', Channels::DEBUG)];
        yield [LogLevel::INFO, new Info('test', Channels::DEBUG)];
        yield [LogLevel::NOTICE, new Notice('test', Channels::DEBUG)];
        yield [LogLevel::WARNING, new Warning('test', Channels::DEBUG)];
    }
}

<?php

/**
 * This file is part of the Wonolog package.
 *
 * (c) Inpsyde GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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
use Inpsyde\Wonolog\Tests\UnitTestCase;
use Monolog\Logger;

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
     * @return array<array{0:int, 1:LogDataInterface}>
     */
    public function dataProviderLogLevels(): array
    {
        return [
            [Logger::ALERT, new Alert('test', Channels::DEBUG)],
            [Logger::CRITICAL, new Critical('test', Channels::DEBUG)],
            [Logger::DEBUG, new Debug('test', Channels::DEBUG)],
            [Logger::EMERGENCY, new Emergency('test', Channels::DEBUG)],
            [Logger::ERROR, new Error('test', Channels::DEBUG)],
            [Logger::INFO, new Info('test', Channels::DEBUG)],
            [Logger::NOTICE, new Notice('test', Channels::DEBUG)],
            [Logger::WARNING, new Warning('test', Channels::DEBUG)],
        ];
    }
}

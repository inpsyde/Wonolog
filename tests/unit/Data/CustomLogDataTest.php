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
use Inpsyde\Wonolog\Levels;
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
            [Levels::ALERT, new Alert('test', Channels::DEBUG)],
            [Levels::CRITICAL, new Critical('test', Channels::DEBUG)],
            [Levels::DEBUG, new Debug('test', Channels::DEBUG)],
            [Levels::EMERGENCY, new Emergency('test', Channels::DEBUG)],
            [Levels::ERROR, new Error('test', Channels::DEBUG)],
            [Levels::INFO, new Info('test', Channels::DEBUG)],
            [Levels::NOTICE, new Notice('test', Channels::DEBUG)],
            [Levels::WARNING, new Warning('test', Channels::DEBUG)],
        ];
    }
}

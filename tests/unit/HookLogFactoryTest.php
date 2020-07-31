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

namespace Inpsyde\Wonolog\Tests\Unit;

use Inpsyde\Wonolog\Channels;
use Inpsyde\Wonolog\Data\Alert;
use Inpsyde\Wonolog\Data\Debug;
use Inpsyde\Wonolog\Data\Error;
use Inpsyde\Wonolog\Data\LogData;
use Inpsyde\Wonolog\HookLogFactory;
use Inpsyde\Wonolog\Tests\UnitTestCase;
use Monolog\Logger;
use Psr\Log\LogLevel;

class HookLogFactoryTest extends UnitTestCase
{
    /**
     * @test
     */
    public function testLogsFromArgumentsReturnsDefaultIfNoArguments(): void
    {
        $factory = HookLogFactory::new(\Mockery::mock(Channels::class));
        $logs = $factory->logsFromHookArguments([]);

        self::assertIsArray($logs);
        self::assertCount(1, $logs);

        /** @var LogData $log */
        $log = reset($logs);

        self::assertInstanceOf(LogData::class, $log);
        self::assertSame($log->message(), 'Unknown error.');
        self::assertSame($log->channel(), Channels::DEBUG);
        self::assertSame($log->context(), []);
    }

    /**
     * @test
     */
    public function testLogsFromArgumentsReturnsGivenLogData(): void
    {
        $first = new Alert('', '');
        $second = new Debug('', '');
        $third = new Error('', '');
        $fourth = clone $first;

        $args = compact('first', 'second', 'third');
        $args[] = 'foo';
        $args[] = 'bar';
        $args[] = $fourth;

        $factory = HookLogFactory::new(\Mockery::mock(Channels::class));
        $logs = $factory->logsFromHookArguments($args);

        self::assertIsArray($logs);
        self::assertCount(4, $logs);

        self::assertSame($logs[0], $first);
        self::assertSame($logs[1], $second);
        self::assertSame($logs[2], $third);
        self::assertSame($logs[3], $fourth);
    }

    /**
     * @test
     */
    public function testLogsFromArgumentsReturnsGivenLogDataWithRaisedLevel(): void
    {
        $first = new Debug('Message', 'Channel', ['foo']);
        $second = new Error('Message!', 'Channel!', []);

        $args = compact('first', 'second');

        $factory = HookLogFactory::new(\Mockery::mock(Channels::class));
        $logs = $factory->logsFromHookArguments($args, Logger::WARNING);

        self::assertIsArray($logs);
        self::assertCount(2, $logs);

        self::assertInstanceOf(LogData::class, $first);
        self::assertSame(Logger::WARNING, $logs[0]->level());
        self::assertSame($logs[0]->message(), $first->message());
        self::assertSame($logs[0]->channel(), $first->channel());
        self::assertSame($logs[0]->context(), $first->context());

        self::assertInstanceOf(LogData::class, $second);
        self::assertSame(Logger::ERROR, $logs[1]->level());
        self::assertSame($logs[1]->message(), $second->message());
        self::assertSame($logs[1]->channel(), $second->channel());
        self::assertSame($logs[1]->context(), $second->context());
    }

    /**
     * @test
     */
    public function testLogsFromString(): void
    {
        $logs = HookLogFactory::new(\Mockery::mock(Channels::class))
            ->logsFromHookArguments(['Foo!', 'X', 'Y', 'Z']);

        self::assertIsArray($logs);
        self::assertCount(1, $logs);

        /** @var LogData $log */
        $log = reset($logs);

        self::assertInstanceOf(LogData::class, $log);
        self::assertSame($log->message(), 'Foo!');
        self::assertSame($log->channel(), Channels::DEBUG);
        self::assertSame($log->context(), ['X', 'Y', 'Z']);
    }

    /**
     * @test
     */
    public function testLogsFromWpError(): void
    {
        $error = \Mockery::mock(\WP_Error::class);
        $error->shouldReceive('get_error_message')->andReturn('Foo!');
        $error->shouldReceive('get_error_data')->andReturn(['db broken']);
        $error->shouldReceive('get_error_codes')->andReturn(['wpdb']);

        $factory = HookLogFactory::new(\Mockery::mock(Channels::class));
        $logs = $factory->logsFromHookArguments([$error]);

        self::assertIsArray($logs);
        self::assertCount(1, $logs);

        /** @var LogData $log */
        $log = reset($logs);

        self::assertInstanceOf(LogData::class, $log);
        self::assertSame($log->message(), 'Foo!');
        self::assertSame($log->level(), Logger::NOTICE);
        self::assertSame($log->channel(), Channels::DB);
        self::assertSame($log->context(), ['db broken']);
    }

    /**
     * @test
     */
    public function testLogsFromWpErrorAndChannelInErrorData(): void
    {
        $error = \Mockery::mock(\WP_Error::class);
        $error->shouldReceive('get_error_message')->andReturn('Error!');
        $error->shouldReceive('get_error_codes')->andReturn(['x', 'y']);
        $error->shouldReceive('get_error_data')->withNoArgs()->andReturn(['some', 'data']);
        $error->shouldReceive('get_error_data')
            ->with('x')
            ->andReturn(['channel' => Channels::SECURITY]);

        $factory = HookLogFactory::new(\Mockery::mock(Channels::class));
        $logs = $factory->logsFromHookArguments([$error]);

        self::assertIsArray($logs);
        self::assertCount(1, $logs);

        /** @var LogData $log */
        $log = reset($logs);

        self::assertInstanceOf(LogData::class, $log);
        self::assertSame('Error!', $log->message());
        self::assertSame(Logger::ERROR, $log->level());
        self::assertSame(Channels::SECURITY, $log->channel());
        self::assertSame(['some', 'data'], $log->context());
    }

    /**
     * @test
     */
    public function testLogsFromThrowable(): void
    {
        $exception = new \Exception('Foo!');

        $factory = HookLogFactory::new(\Mockery::mock(Channels::class));
        $logs = $factory->logsFromHookArguments([$exception]);

        self::assertIsArray($logs);
        self::assertCount(1, $logs);

        /** @var LogData $log */
        $log = reset($logs);

        self::assertInstanceOf(LogData::class, $log);
        self::assertSame($log->message(), 'Foo!');
        self::assertSame($log->level(), Logger::ERROR);
        self::assertSame($log->channel(), Channels::DEBUG);
        self::assertIsArray($log->context());
    }

    /**
     * @test
     */
    public function testLogsFromArray(): void
    {
        $data = [
            'message' => 'Hello!',
            'level' => Logger::NOTICE,
            'channel' => Channels::SECURITY,
            'context' => ['foo', 'bar'],
        ];

        $factory = HookLogFactory::new(\Mockery::mock(Channels::class));
        $logs = $factory->logsFromHookArguments([$data, 'x', 'y'], Logger::DEBUG);

        self::assertIsArray($logs);
        self::assertCount(1, $logs);

        /** @var LogData $log */
        $log = reset($logs);

        self::assertInstanceOf(LogData::class, $log);
        self::assertSame($log->message(), 'Hello!');
        self::assertSame($log->level(), Logger::NOTICE);
        self::assertSame($log->channel(), Channels::SECURITY);
        self::assertIsArray(['foo', 'bar']);
    }

    /**
     * @test
     */
    public function testLogsFromArrayAndRaisedLevel(): void
    {
        $data = [
            'message' => 'Hello!',
            'level' => Logger::DEBUG,
            'channel' => Channels::SECURITY,
            'context' => ['foo', 'bar'],
        ];

        $factory = HookLogFactory::new(\Mockery::mock(Channels::class));
        $logs = $factory->logsFromHookArguments([$data, 600, 'y'], Logger::NOTICE);

        self::assertIsArray($logs);
        self::assertCount(1, $logs);

        /** @var LogData $log */
        $log = reset($logs);

        self::assertInstanceOf(LogData::class, $log);
        self::assertSame($log->message(), 'Hello!');
        self::assertSame($log->level(), Logger::NOTICE);
        self::assertSame($log->channel(), Channels::SECURITY);
        self::assertIsArray(['foo', 'bar']);
    }

    /**
     * @test
     */
    public function testLogsFromArrayDifferentLevelFormat(): void
    {
        $psrData = [
            'message' => 'PSR level format',
            'level' => LogLevel::WARNING,
        ];

        $monologNumData = [
            'message' => 'Monolog numeric level format',
            'level' => Logger::NOTICE,
        ];

        $monologStringData = [
            'message' => 'Monolog string level format',
            'level' => Logger::getLevelName(Logger::INFO),
        ];

        $factory = HookLogFactory::new(\Mockery::mock(Channels::class));
        $psr = $factory->logsFromHookArguments([$psrData]);
        $monologNum = $factory->logsFromHookArguments([$monologNumData]);
        $monologString = $factory->logsFromHookArguments([$monologStringData]);

        self::assertSame($psr[0]->level(), Logger::WARNING);
        self::assertSame($monologNum[0]->level(), Logger::NOTICE);
        self::assertSame($monologString[0]->level(), Logger::INFO);
    }
}

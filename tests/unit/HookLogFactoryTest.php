<?php

declare(strict_types=1);

namespace Inpsyde\Wonolog\Tests\Unit;

use Inpsyde\Wonolog\Channels;
use Inpsyde\Wonolog\Data\Alert;
use Inpsyde\Wonolog\Data\Debug;
use Inpsyde\Wonolog\Data\Error;
use Inpsyde\Wonolog\Data\LogData;
use Inpsyde\Wonolog\HookLogFactory;
use Inpsyde\Wonolog\LogLevel;
use Inpsyde\Wonolog\Tests\UnitTestCase;

class HookLogFactoryTest extends UnitTestCase
{
    /**
     * @test
     */
    public function testLogsFromArgumentsReturnsDefaultIfNoArguments(): void
    {
        $factory = HookLogFactory::new();
        $logs = $factory->logsFromHookArguments([]);

        static::assertCount(1, $logs);

        /** @var LogData $log */
        $log = reset($logs);

        static::assertInstanceOf(LogData::class, $log);
        static::assertSame($log->message(), 'Unknown error.');
        static::assertSame($log->channel(), Channels::DEBUG);
        static::assertSame($log->context(), []);
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

        $factory = HookLogFactory::new();
        $logs = $factory->logsFromHookArguments($args);

        static::assertCount(4, $logs);

        static::assertSame($logs[0], $first);
        static::assertSame($logs[1], $second);
        static::assertSame($logs[2], $third);
        static::assertSame($logs[3], $fourth);
    }

    /**
     * @test
     */
    public function testLogsFromArgumentsReturnsGivenLogDataWithRaisedLevel(): void
    {
        $first = new Debug('Message', 'Channel', ['foo']);
        $second = new Error('Message!', 'Channel!', []);

        $args = compact('first', 'second');

        $factory = HookLogFactory::new();
        $logs = $factory->logsFromHookArguments($args, LogLevel::WARNING);

        static::assertCount(2, $logs);

        static::assertInstanceOf(LogData::class, $first);
        static::assertSame(LogLevel::WARNING, $logs[0]->level());
        static::assertSame($logs[0]->message(), $first->message());
        static::assertSame($logs[0]->channel(), $first->channel());
        static::assertSame($logs[0]->context(), $first->context());

        static::assertInstanceOf(LogData::class, $second);
        static::assertSame(LogLevel::ERROR, $logs[1]->level());
        static::assertSame($logs[1]->message(), $second->message());
        static::assertSame($logs[1]->channel(), $second->channel());
        static::assertSame($logs[1]->context(), $second->context());
    }

    /**
     * @test
     */
    public function testLogsFromString(): void
    {
        $logs = HookLogFactory::new()->logsFromHookArguments(['Foo!', 'X', 'Y', 'Z']);

        static::assertCount(1, $logs);

        /** @var LogData $log */
        $log = reset($logs);

        static::assertInstanceOf(LogData::class, $log);
        static::assertSame($log->message(), 'Foo!');
        static::assertSame($log->channel(), Channels::DEBUG);
        static::assertSame($log->context(), ['X', 'Y', 'Z']);
    }

    /**
     * @test
     */
    public function testLogsFromStringWithLevelAndChannelFromSecondArgAsArray(): void
    {
        $logs = HookLogFactory::new()->logsFromHookArguments(
            [
                'Foo!',
                [
                    'level' => \Psr\Log\LogLevel::ALERT,
                    'channel' => Channels::NETWORK,
                    'x' => 'y',
                    'foo',
                ],
            ]
        );

        static::assertCount(1, $logs);

        /** @var LogData $log */
        $log = reset($logs);

        static::assertInstanceOf(LogData::class, $log);
        static::assertSame('Foo!', $log->message());
        static::assertSame(Channels::NETWORK, $log->channel());
        static::assertSame(LogLevel::ALERT, $log->level());
        static::assertSame([], $log->context());
    }

    /**
     * @test
     */
    public function testLogsFromStringWithContextAndChannelFromSecondArgAsArray(): void
    {
        $logs = HookLogFactory::new()->logsFromHookArguments(
            [
                'Foo!',
                [
                    'channel' => Channels::NETWORK,
                    'context' => ['Hello', 'World'],
                    'x' => 'y',
                    'foo',
                ],
            ]
        );

        static::assertCount(1, $logs);

        /** @var LogData $log */
        $log = reset($logs);

        static::assertInstanceOf(LogData::class, $log);
        static::assertSame($log->message(), 'Foo!');
        static::assertSame($log->channel(), Channels::NETWORK);
        static::assertSame($log->context(), ['Hello', 'World']);
    }

    /**
     * @test
     */
    public function testLogsFromStringSecondArgAsArrayWithMoreArguments(): void
    {
        $logs = HookLogFactory::new()->logsFromHookArguments(
            [
                'Foo!',
                ['channel' => Channels::NETWORK],
                'bar',
                ['X', 'Y'],
            ]
        );

        static::assertCount(1, $logs);

        /** @var LogData $log */
        $log = reset($logs);

        static::assertInstanceOf(LogData::class, $log);
        static::assertSame($log->message(), 'Foo!');
        static::assertSame($log->channel(), Channels::DEBUG);
        static::assertSame($log->level(), LogLevel::DEBUG);
        static::assertSame(
            $log->context(),
            [['channel' => Channels::NETWORK], 'bar', ['X', 'Y']]
        );
    }

    /**
     * @test
     */
    public function testLogsFromStringSecondArgAsArrayIgnoredWhenNoTargetKeys(): void
    {
        $logs = HookLogFactory::new()->logsFromHookArguments(
            [
                'Foo!',
                [Channels::NETWORK, LogLevel::ALERT],
            ]
        );

        static::assertCount(1, $logs);

        /** @var LogData $log */
        $log = reset($logs);

        static::assertInstanceOf(LogData::class, $log);
        static::assertSame($log->message(), 'Foo!');
        static::assertSame($log->channel(), Channels::DEBUG);
        static::assertSame($log->level(), LogLevel::DEBUG);
        static::assertSame($log->context(), [[Channels::NETWORK, LogLevel::ALERT]]);
    }

    /**
     * @test
     */
    public function testLogsFromWpError(): void
    {
        $error = \Mockery::mock(\WP_Error::class);
        $error->allows('get_error_message')->andReturn('Foo!');
        $error->allows('get_error_data')->andReturn(['db broken']);
        $error->allows('get_error_codes')->andReturn(['wpdb']);

        $factory = HookLogFactory::new();
        $logs = $factory->logsFromHookArguments([$error]);

        static::assertCount(1, $logs);

        /** @var LogData $log */
        $log = reset($logs);

        static::assertInstanceOf(LogData::class, $log);
        static::assertSame($log->message(), 'Foo!');
        static::assertSame($log->level(), LogLevel::NOTICE);
        static::assertSame($log->channel(), Channels::DB);
        static::assertSame($log->context(), ['db broken']);
    }

    /**
     * @test
     */
    public function testLogsFromWpErrorAndChannelInErrorData(): void
    {
        $error = \Mockery::mock(\WP_Error::class);
        $error->expects('get_error_message')->andReturn('Error!');
        $error->expects('get_error_codes')->andReturn(['x', 'y']);
        $error->expects('get_error_data')->withNoArgs()->twice()->andReturn(['some', 'data']);
        $error->expects('get_error_data')
            ->with('x')
            ->andReturn(['channel' => Channels::SECURITY]);

        $factory = HookLogFactory::new();
        $logs = $factory->logsFromHookArguments([$error]);

        static::assertCount(1, $logs);

        /** @var LogData $log */
        $log = reset($logs);

        static::assertInstanceOf(LogData::class, $log);
        static::assertSame('Error!', $log->message());
        static::assertSame(LogLevel::ERROR, $log->level());
        static::assertSame(Channels::SECURITY, $log->channel());
        static::assertSame(['some', 'data'], $log->context());
    }

    /**
     * @test
     */
    public function testLogsFromThrowable(): void
    {
        $exception = new \Exception('Foo!');

        $factory = HookLogFactory::new();
        $logs = $factory->logsFromHookArguments([$exception]);

        static::assertCount(1, $logs);

        /** @var LogData $log */
        $log = reset($logs);

        static::assertInstanceOf(LogData::class, $log);
        static::assertSame($log->message(), 'Foo!');
        static::assertSame($log->level(), LogLevel::ERROR);
        static::assertSame($log->channel(), Channels::DEBUG);
    }

    /**
     * @test
     */
    public function testLogsFromArray(): void
    {
        $data = [
            'message' => 'Hello!',
            'level' => LogLevel::NOTICE,
            'channel' => Channels::SECURITY,
            'context' => ['foo', 'bar'],
        ];

        $factory = HookLogFactory::new();
        $logs = $factory->logsFromHookArguments([$data, 'x', 'y'], LogLevel::DEBUG);

        static::assertCount(1, $logs);

        /** @var LogData $log */
        $log = reset($logs);

        static::assertInstanceOf(LogData::class, $log);
        static::assertSame($log->message(), 'Hello!');
        static::assertSame($log->level(), LogLevel::NOTICE);
        static::assertSame($log->channel(), Channels::SECURITY);
    }

    /**
     * @test
     */
    public function testLogsFromArrayAndRaisedLevel(): void
    {
        $data = [
            'message' => 'Hello!',
            'level' => LogLevel::DEBUG,
            'channel' => Channels::SECURITY,
            'context' => ['foo', 'bar'],
        ];

        $factory = HookLogFactory::new();
        $logs = $factory->logsFromHookArguments([$data, 600, 'y'], LogLevel::NOTICE);

        static::assertCount(1, $logs);

        /** @var LogData $log */
        $log = reset($logs);

        static::assertInstanceOf(LogData::class, $log);
        static::assertSame($log->message(), 'Hello!');
        static::assertSame($log->level(), LogLevel::NOTICE);
        static::assertSame($log->channel(), Channels::SECURITY);
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
            'level' => \Inpsyde\Wonolog\LogLevel::NOTICE,
        ];

        $monologStringData = [
            'message' => 'Monolog string level format',
            'level' => 'INFO',
        ];

        $factory = HookLogFactory::new();
        $psr = $factory->logsFromHookArguments([$psrData]);
        $monologNum = $factory->logsFromHookArguments([$monologNumData]);
        $monologString = $factory->logsFromHookArguments([$monologStringData]);

        static::assertSame($psr[0]->level(), \Inpsyde\Wonolog\LogLevel::WARNING);
        static::assertSame($monologNum[0]->level(), \Inpsyde\Wonolog\LogLevel::NOTICE);
        static::assertSame($monologString[0]->level(), \Inpsyde\Wonolog\LogLevel::INFO);
    }
}

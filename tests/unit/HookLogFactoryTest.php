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

final class HookLogFactoryTest extends UnitTestCase
{
    /**
     * @test
     */
    public function testLogsFromArgumentsReturnsDefaultIfNoArguments(): void
    {
        $factory = HookLogFactory::new();
        $logs = $factory->logsFromHookArguments([]);

        $this->assertCount(1, $logs);

        /** @var LogData $log */
        $log = reset($logs);

        $this->assertInstanceOf(LogData::class, $log);
        $this->assertSame('Unknown error.', $log->message());
        $this->assertSame(Channels::DEBUG, $log->channel());
        $this->assertSame($log->context(), []);
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

        $this->assertCount(4, $logs);

        $this->assertSame($logs[0], $first);
        $this->assertSame($logs[1], $second);
        $this->assertSame($logs[2], $third);
        $this->assertSame($logs[3], $fourth);
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

        $this->assertCount(2, $logs);

        $this->assertInstanceOf(LogData::class, $first);
        $this->assertSame(LogLevel::WARNING, $logs[0]->level());
        $this->assertSame($logs[0]->message(), $first->message());
        $this->assertSame($logs[0]->channel(), $first->channel());
        $this->assertSame($logs[0]->context(), $first->context());

        $this->assertInstanceOf(LogData::class, $second);
        $this->assertSame(LogLevel::ERROR, $logs[1]->level());
        $this->assertSame($logs[1]->message(), $second->message());
        $this->assertSame($logs[1]->channel(), $second->channel());
        $this->assertSame($logs[1]->context(), $second->context());
    }

    /**
     * @test
     */
    public function testLogsFromString(): void
    {
        $logs = HookLogFactory::new()->logsFromHookArguments(['Foo!', 'X', 'Y', 'Z']);

        $this->assertCount(1, $logs);

        /** @var LogData $log */
        $log = reset($logs);

        $this->assertInstanceOf(LogData::class, $log);
        $this->assertSame('Foo!', $log->message());
        $this->assertSame(Channels::DEBUG, $log->channel());
        $this->assertSame($log->context(), ['X', 'Y', 'Z']);
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

        $this->assertCount(1, $logs);

        /** @var LogData $log */
        $log = reset($logs);

        $this->assertInstanceOf(LogData::class, $log);
        $this->assertSame('Foo!', $log->message());
        $this->assertSame(Channels::NETWORK, $log->channel());
        $this->assertSame(LogLevel::ALERT, $log->level());
        $this->assertSame([], $log->context());
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

        $this->assertCount(1, $logs);

        /** @var LogData $log */
        $log = reset($logs);

        $this->assertInstanceOf(LogData::class, $log);
        $this->assertSame('Foo!', $log->message());
        $this->assertSame(Channels::NETWORK, $log->channel());
        $this->assertSame($log->context(), ['Hello', 'World']);
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

        $this->assertCount(1, $logs);

        /** @var LogData $log */
        $log = reset($logs);

        $this->assertInstanceOf(LogData::class, $log);
        $this->assertSame('Foo!', $log->message());
        $this->assertSame(Channels::DEBUG, $log->channel());
        $this->assertSame(LogLevel::DEBUG, $log->level());
        $this->assertSame($log->context(), [['channel' => Channels::NETWORK], 'bar', ['X', 'Y']]);
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

        $this->assertCount(1, $logs);

        /** @var LogData $log */
        $log = reset($logs);

        $this->assertInstanceOf(LogData::class, $log);
        $this->assertSame('Foo!', $log->message());
        $this->assertSame(Channels::DEBUG, $log->channel());
        $this->assertSame(LogLevel::DEBUG, $log->level());
        $this->assertSame($log->context(), [[Channels::NETWORK, LogLevel::ALERT]]);
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

        $this->assertCount(1, $logs);

        /** @var LogData $log */
        $log = reset($logs);

        $this->assertInstanceOf(LogData::class, $log);
        $this->assertSame('Foo!', $log->message());
        $this->assertSame(LogLevel::NOTICE, $log->level());
        $this->assertSame(Channels::DB, $log->channel());
        $this->assertSame($log->context(), ['db broken']);
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

        $this->assertCount(1, $logs);

        /** @var LogData $log */
        $log = reset($logs);

        $this->assertInstanceOf(LogData::class, $log);
        $this->assertSame('Error!', $log->message());
        $this->assertSame(LogLevel::ERROR, $log->level());
        $this->assertSame(Channels::SECURITY, $log->channel());
        $this->assertSame(['some', 'data'], $log->context());
    }

    /**
     * @test
     */
    public function testLogsFromThrowable(): void
    {
        $exception = new \Exception('Foo!');

        $factory = HookLogFactory::new();
        $logs = $factory->logsFromHookArguments([$exception]);

        $this->assertCount(1, $logs);

        /** @var LogData $log */
        $log = reset($logs);

        $this->assertInstanceOf(LogData::class, $log);
        $this->assertSame('Foo!', $log->message());
        $this->assertSame(LogLevel::ERROR, $log->level());
        $this->assertSame(Channels::DEBUG, $log->channel());
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

        $this->assertCount(1, $logs);

        /** @var LogData $log */
        $log = reset($logs);

        $this->assertInstanceOf(LogData::class, $log);
        $this->assertSame('Hello!', $log->message());
        $this->assertSame(LogLevel::NOTICE, $log->level());
        $this->assertSame(Channels::SECURITY, $log->channel());
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

        $this->assertCount(1, $logs);

        /** @var LogData $log */
        $log = reset($logs);

        $this->assertInstanceOf(LogData::class, $log);
        $this->assertSame('Hello!', $log->message());
        $this->assertSame(LogLevel::NOTICE, $log->level());
        $this->assertSame(Channels::SECURITY, $log->channel());
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

        $this->assertSame(\Inpsyde\Wonolog\LogLevel::WARNING, $psr[0]->level());
        $this->assertSame(\Inpsyde\Wonolog\LogLevel::NOTICE, $monologNum[0]->level());
        $this->assertSame(\Inpsyde\Wonolog\LogLevel::INFO, $monologString[0]->level());
    }
}

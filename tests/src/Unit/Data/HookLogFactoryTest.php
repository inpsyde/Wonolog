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
use Inpsyde\Wonolog\Data\HookLogFactory;
use Inpsyde\Wonolog\Data\LogDataInterface;
use Inpsyde\Wonolog\Tests\TestCase;
use Monolog\Logger;

/**
 * @package wonolog\tests
 * @license http://opensource.org/licenses/MIT MIT
 */
class HookLogFactoryTest extends TestCase
{
    public function testLogsFromArgumentsReturnsDefaultIfNoArguments()
    {
        $factory = new HookLogFactory();
        $logs = $factory->logsFromHookArguments([]);

        self::assertIsArray($logs);
        self::assertCount(1, $logs);

        /** @var LogDataInterface $log */
        $log = reset($logs);

        self::assertInstanceOf(LogDataInterface::class, $log);
        self::assertSame($log->message(), 'Unknown error.');
        self::assertSame($log->channel(), Channels::DEBUG);
        self::assertSame($log->context(), []);
    }

    public function testLogsFromArgumentsReturnsGivenLogData()
    {
        $first = \Mockery::mock(LogDataInterface::class);
        $first->shouldReceive('level')->zeroOrMoreTimes();
        $first->shouldReceive('channel')->zeroOrMoreTimes();
        $first->shouldReceive('message')->zeroOrMoreTimes();
        $first->shouldReceive('context')->zeroOrMoreTimes();

        $second = clone $first;
        $third = clone $first;
        $fourth = clone $first;

        $args = compact('first', 'second', 'third');
        $args[] = 'foo';
        $args[] = 'bar';
        $args[] = $fourth;

        $factory = new HookLogFactory();
        $logs = $factory->logsFromHookArguments($args);

        self::assertIsArray($logs);
        self::assertCount(4, $logs);

        self::assertSame($logs[0], $first);
        self::assertSame($logs[1], $second);
        self::assertSame($logs[2], $third);
        self::assertSame($logs[3], $fourth);
    }

    public function testLogsFromArgumentsReturnsGivenLogDataWithRaisedLevel()
    {
        /** @var LogDataInterface|\Mockery\MockInterface $first */
        $first = \Mockery::mock(LogDataInterface::class);
        $first->shouldReceive('level')
            ->atLeast()
            ->once()
            ->andReturn(100);
        $first->shouldReceive('channel')
            ->atLeast()
            ->once()
            ->andReturn('Channel');
        $first->shouldReceive('message')
            ->atLeast()
            ->once()
            ->andReturn('Message');
        $first->shouldReceive('context')
            ->atLeast()
            ->once()
            ->andReturn(['foo']);

        /** @var LogDataInterface $first */
        $second = \Mockery::mock(LogDataInterface::class);
        $second->shouldReceive('level')
            ->atLeast()
            ->once()
            ->andReturn(400);
        $second->shouldReceive('channel')
            ->atLeast()
            ->once()
            ->andReturn('Channel!');
        $second->shouldReceive('message')
            ->atLeast()
            ->once()
            ->andReturn('Message!');
        $second->shouldReceive('context')
            ->atLeast()
            ->once()
            ->andReturn([]);

        $args = compact('first', 'second');

        $factory = new HookLogFactory();
        $logs = $factory->logsFromHookArguments($args, 300);

        self::assertIsArray($logs);
        self::assertCount(2, $logs);

        self::assertInstanceOf(LogDataInterface::class, $first);
        self::assertSame(300, $logs[0]->level());
        self::assertSame($logs[0]->message(), $first->message());
        self::assertSame($logs[0]->channel(), $first->channel());
        self::assertSame($logs[0]->context(), $first->context());

        self::assertInstanceOf(LogDataInterface::class, $second);
        self::assertSame(400, $logs[1]->level());
        self::assertSame($logs[1]->message(), $second->message());
        self::assertSame($logs[1]->channel(), $second->channel());
        self::assertSame($logs[1]->context(), $second->context());
    }

    public function testLogsFromStringArgument()
    {
        $factory = new HookLogFactory();
        $logs = $factory->logsFromHookArguments(['Foo!']);

        self::assertIsArray($logs);
        self::assertCount(1, $logs);

        /** @var LogDataInterface $log */
        $log = reset($logs);

        self::assertInstanceOf(LogDataInterface::class, $log);
        self::assertSame($log->message(), 'Foo!');
        self::assertSame($log->channel(), Channels::DEBUG);
        self::assertSame($log->context(), []);
    }

    public function testLogsFromStringArgumentAndArgs()
    {
        $factory = new HookLogFactory();
        $logs = $factory->logsFromHookArguments(['Foo!', Logger::CRITICAL, 'A Channel']);

        self::assertIsArray($logs);
        self::assertCount(1, $logs);

        /** @var LogDataInterface $log */
        $log = reset($logs);

        self::assertInstanceOf(LogDataInterface::class, $log);
        self::assertSame($log->message(), 'Foo!');
        self::assertSame($log->level(), Logger::CRITICAL);
        self::assertSame($log->channel(), 'A Channel');
        self::assertSame($log->context(), []);
    }

    public function testLogsFromStringArgumentAndRaisedLevel()
    {
        $factory = new HookLogFactory();
        $logs = $factory->logsFromHookArguments(
            ['Foo!', Logger::DEBUG, 'A Channel'],
            Logger::CRITICAL
        );

        self::assertIsArray($logs);
        self::assertCount(1, $logs);

        /** @var LogDataInterface $log */
        $log = reset($logs);

        self::assertInstanceOf(LogDataInterface::class, $log);
        self::assertSame($log->message(), 'Foo!');
        self::assertSame($log->level(), Logger::CRITICAL);
        self::assertSame($log->channel(), 'A Channel');
        self::assertSame($log->context(), []);
    }

    public function testLogsFromWpErrorArgument()
    {
        $error = \Mockery::mock(\WP_Error::class);
        $error->shouldReceive('get_error_message')->andReturn('Foo!');
        $error->shouldReceive('get_error_data')->andReturn(['db broken']);
        $error->shouldReceive('get_error_codes')->andReturn(['wpdb']);

        $factory = new HookLogFactory();
        $logs = $factory->logsFromHookArguments([$error]);

        self::assertIsArray($logs);
        self::assertCount(1, $logs);

        /** @var LogDataInterface $log */
        $log = reset($logs);

        self::assertInstanceOf(LogDataInterface::class, $log);
        self::assertSame($log->message(), 'Foo!');
        self::assertSame($log->level(), Logger::WARNING);
        self::assertSame($log->channel(), Channels::DB);
        self::assertSame($log->context(), ['db broken']);
    }

    public function testLogsFromWpErrorArgumentAndArgs()
    {
        $error = \Mockery::mock(\WP_Error::class);
        $error->shouldReceive('get_error_message')->andReturn('Foo!');
        $error->shouldReceive('get_error_data')->andReturn(['db broken']);
        $error->shouldReceive('get_error_codes')->andReturn(['wpdb']);

        $factory = new HookLogFactory();
        $logs = $factory->logsFromHookArguments([$error, Logger::ERROR, Channels::DEBUG]);

        self::assertIsArray($logs);
        self::assertCount(1, $logs);

        /** @var LogDataInterface $log */
        $log = reset($logs);

        self::assertInstanceOf(LogDataInterface::class, $log);
        self::assertSame($log->message(), 'Foo!');
        self::assertSame($log->level(), Logger::ERROR);
        self::assertSame($log->channel(), Channels::DEBUG);
        self::assertSame($log->context(), ['db broken']);
    }

    public function testLogsFromWpErrorArgumentAndRaisedLevel()
    {
        $error = \Mockery::mock(\WP_Error::class);
        $error->shouldReceive('get_error_message')->andReturn('Foo!');
        $error->shouldReceive('get_error_data')->andReturn(['foo']);
        $error->shouldReceive('get_error_codes')->andReturn(['foo']);

        $factory = new HookLogFactory();
        $logs = $factory->logsFromHookArguments(
            [$error, Logger::NOTICE, Channels::HTTP],
            Logger::ERROR
        );

        self::assertIsArray($logs);
        self::assertCount(1, $logs);

        /** @var LogDataInterface $log */
        $log = reset($logs);

        self::assertInstanceOf(LogDataInterface::class, $log);
        self::assertSame($log->message(), 'Foo!');
        self::assertSame($log->level(), Logger::ERROR);
        self::assertSame($log->channel(), Channels::HTTP);
        self::assertSame($log->context(), ['foo']);
    }

    public function testLogsFromThrowableArgument()
    {
        $exception = new \Exception('Foo!');

        $factory = new HookLogFactory();
        $logs = $factory->logsFromHookArguments([$exception]);

        self::assertIsArray($logs);
        self::assertCount(1, $logs);

        /** @var LogDataInterface $log */
        $log = reset($logs);

        self::assertInstanceOf(LogDataInterface::class, $log);
        self::assertSame($log->message(), 'Foo!');
        self::assertSame($log->level(), Logger::ERROR);
        self::assertSame($log->channel(), Channels::DEBUG);
        self::assertIsArray($log->context());
    }

    public function testLogsFromThrowableArgumentAndArgs()
    {
        $exception = new \Exception('Foo!');

        $factory = new HookLogFactory();
        $logs = $factory->logsFromHookArguments([$exception, Logger::CRITICAL, Channels::DB]);

        self::assertIsArray($logs);
        self::assertCount(1, $logs);

        /** @var LogDataInterface $log */
        $log = reset($logs);

        self::assertInstanceOf(LogDataInterface::class, $log);
        self::assertSame($log->message(), 'Foo!');
        self::assertSame($log->level(), Logger::CRITICAL);
        self::assertSame($log->channel(), Channels::DB);
        self::assertIsArray($log->context());
    }

    public function testLogsFromThrowableArgumentAndRaisedLevel()
    {
        $exception = new \Exception('Foo!');

        $factory = new HookLogFactory();
        $logs = $factory->logsFromHookArguments(
            [$exception, Logger::DEBUG, Channels::DB],
            Logger::CRITICAL
        );

        self::assertIsArray($logs);
        self::assertCount(1, $logs);

        /** @var LogDataInterface $log */
        $log = reset($logs);

        self::assertInstanceOf(LogDataInterface::class, $log);
        self::assertSame($log->message(), 'Foo!');
        self::assertSame($log->level(), Logger::CRITICAL);
        self::assertSame($log->channel(), Channels::DB);
        self::assertIsArray($log->context());
    }

    public function testLogsFromArrayArgument()
    {
        $data = [
            'message' => 'Hello!',
            'level' => Logger::NOTICE,
            'channel' => Channels::SECURITY,
            'context' => ['foo', 'bar'],
        ];

        $factory = new HookLogFactory();
        $logs = $factory->logsFromHookArguments([$data, 'x', 'y'], Logger::DEBUG);

        self::assertIsArray($logs);
        self::assertCount(1, $logs);

        /** @var LogDataInterface $log */
        $log = reset($logs);

        self::assertInstanceOf(LogDataInterface::class, $log);
        self::assertSame($log->message(), 'Hello!');
        self::assertSame($log->level(), Logger::NOTICE);
        self::assertSame($log->channel(), Channels::SECURITY);
        self::assertIsArray(['foo', 'bar']);
    }

    public function testLogsFromArrayArgumentAndRaisedLevel()
    {
        $data = [
            'message' => 'Hello!',
            'level' => Logger::DEBUG,
            'channel' => Channels::SECURITY,
            'context' => ['foo', 'bar'],
        ];

        $factory = new HookLogFactory();
        $logs = $factory->logsFromHookArguments([$data, 600, 'y'], Logger::NOTICE);

        self::assertIsArray($logs);
        self::assertCount(1, $logs);

        /** @var LogDataInterface $log */
        $log = reset($logs);

        self::assertInstanceOf(LogDataInterface::class, $log);
        self::assertSame($log->message(), 'Hello!');
        self::assertSame($log->level(), Logger::NOTICE);
        self::assertSame($log->channel(), Channels::SECURITY);
        self::assertIsArray(['foo', 'bar']);
    }
}

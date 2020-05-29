<?php

declare(strict_types=1);

/*
 * This file is part of the Wonolog package.
 *
 * (c) Inpsyde GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Inpsyde\Wonolog\Tests\Unit;

use Brain\Monkey\Actions;
use Brain\Monkey\Functions;
use Inpsyde\Wonolog;
use Inpsyde\Wonolog\Channels;
use Inpsyde\Wonolog\Controller;
use Inpsyde\Wonolog\Data\HookLogFactory;
use Inpsyde\Wonolog\Data\Log;
use Inpsyde\Wonolog\Data\LogDataInterface;
use Inpsyde\Wonolog\LogActionSubscriber;
use Inpsyde\Wonolog\Tests\TestCase;
use Monolog\Logger;

/**
 * @package wonolog\tests
 * @license http://opensource.org/licenses/MIT MIT
 */
class LogActionSubscriberTest extends TestCase
{
    public function testListenDoNothingIfNotLoaded()
    {
        /** @var Channels $channels */
        $channels = \Mockery::mock(Channels::class);
        /** @var HookLogFactory|\Mockery\MockInterface $factory */
        $factory = \Mockery::mock(HookLogFactory::class);
        $factory->shouldNotReceive('logs_from_hook_arguments');

        $subscriber = new LogActionSubscriber($channels, $factory);
        $subscriber->listen();
    }

    public function testListenCallUpdate()
    {
        Functions\when('current_filter')
            ->justReturn(Wonolog\LOG);

        $log = \Mockery::mock(LogDataInterface::class);
        $log
            ->shouldReceive('level')
            ->once()
            ->andThrow(new \Exception('Test passed'));

        /** @var Channels|\Mockery\MockInterface $channels */
        $channels = \Mockery::mock(Channels::class);
        $channels->shouldNotReceive('logger');

        /** @var HookLogFactory|\Mockery\MockInterface $factory */
        $factory = \Mockery::mock(HookLogFactory::class);
        $factory
            ->shouldReceive('logsFromHookArguments')
            ->once()
            ->with([], 0)
            ->andReturn([$log]);

        do_action(Controller::ACTION_LOADED);

        $subscriber = new LogActionSubscriber($channels, $factory);

        $this->expectExceptionMessage('Test passed');
        $subscriber->listen();
    }

    public function testUpdateDoNothingIfNotLoaded()
    {

        /** @var Channels|\Mockery\MockInterface $channels */
        $channels = \Mockery::mock(Channels::class);
        $channels->shouldNotReceive('logger');

        $subscriber = new LogActionSubscriber($channels);
        $subscriber->update(new Log());
    }

    public function testUpdateHandleLoggerExceptions()
    {

        $log = \Mockery::mock(LogDataInterface::class);
        $log
            ->shouldReceive('level')
            ->andReturn(Logger::ALERT);
        $log
            ->shouldReceive('channel')
            ->andReturn(Channels::DEBUG);
        $log
            ->shouldReceive('message')
            ->andReturn('Hello');
        $log
            ->shouldReceive('context')
            ->andReturn([]);

        $logger = \Mockery::mock(Logger::class);
        $logger
            ->shouldReceive('addRecord')
            ->once()
            ->with(Logger::ALERT, 'Hello', [])
            ->andThrow(new \Exception());

        /** @var Channels|\Mockery\MockInterface $channels */
        $channels = \Mockery::mock(Channels::class);
        $channels
            ->shouldReceive('logger')
            ->once()
            ->with(Channels::DEBUG)
            ->andReturn($logger);

        Actions\expectDone(LogActionSubscriber::ACTION_LOGGER_ERROR)
            ->once()
            ->with($log, \Mockery::type(\Exception::class));

        do_action(Controller::ACTION_LOADED);

        $subscriber = new LogActionSubscriber($channels);
        $subscriber->update($log);
    }

    public function testUpdateDoLogs()
    {
        $log = new Log('Hello', Logger::ALERT, Channels::DEBUG);

        $logger = \Mockery::mock(Logger::class);
        $logger
            ->shouldReceive('addRecord')
            ->once()
            ->with(Logger::ALERT, 'Hello', [])
            ->andReturnUsing(
                static function (): bool {
                    echo 'It worked!';

                    return true;
                }
            );

        /** @var Channels|\Mockery\MockInterface $channels */
        $channels = \Mockery::mock(Channels::class);
        $channels
            ->shouldReceive('logger')
            ->once()
            ->with(Channels::DEBUG)
            ->andReturn($logger);

        do_action(Controller::ACTION_LOADED);

        $subscriber = new LogActionSubscriber($channels);

        $this->expectOutputString('It worked!');
        $subscriber->update($log);
    }
}

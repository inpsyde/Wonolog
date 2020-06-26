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

use Brain\Monkey\Actions;
use Brain\Monkey\Filters;
use Inpsyde\Wonolog\Channels;
use Inpsyde\Wonolog\Exception\InvalidChannelNameException;
use Inpsyde\Wonolog\Handler\HandlersRegistry;
use Inpsyde\Wonolog\Processor\ProcessorsRegistry;
use Inpsyde\Wonolog\Tests\TestCase;
use Mockery\MockInterface;
use Monolog\Handler\HandlerInterface;
use Monolog\Logger;

/**
 * @package wonolog\tests
 * @license http://opensource.org/licenses/MIT MIT
 */
class ChannelsTest extends TestCase
{
    public function testAllChannelsReturnDefaultChannels()
    {
        $channels = Channels::all();

        $expected = [
            Channels::HTTP,
            Channels::DB,
            Channels::SECURITY,
            Channels::DEBUG,
        ];

        static::assertSame($expected, $channels);
    }

    public function testAllChannelsAllowFilter()
    {

        Filters\expectApplied(Channels::FILTER_CHANNELS)
            ->once()
            ->andReturn(['foo', 1, []]);

        $channels = Channels::all();

        static::assertSame(['foo'], $channels);
    }

    public function testHasChannel()
    {
        $channels = $this->createChannels();

        static::assertTrue($channels->hasChannel(Channels::DEBUG));
        static::assertFalse($channels->hasChannel('Foo'));
    }

    public function testLoggerFailsIfWrongChannel()
    {
        $channels = $this->createChannels();

        $this->expectException(InvalidChannelNameException::class);
        $channels->logger('X');
    }

    public function testLoggerInitializeOnceAndReturnAlways()
    {
        /** @var HandlersRegistry|\Mockery\MockInterface $handlers */
        $handlers = \Mockery::mock(HandlersRegistry::class);

        /** @var ProcessorsRegistry|\Mockery\MockInterface $processors */
        $processors = \Mockery::mock(ProcessorsRegistry::class);

        $defaultHandler = \Mockery::mock(HandlerInterface::class);
        $defaultHandler->shouldReceive('close')->andReturnNull();

        $handlers->shouldReceive('find')
            ->once()
            ->with(HandlersRegistry::DEFAULT_NAME)
            ->andReturn($defaultHandler);

        $processors->shouldReceive('find')
            ->once()
            ->with(ProcessorsRegistry::DEFAULT_NAME)
            ->andReturn('strtolower');

        Filters\expectApplied(Channels::FILTER_USE_DEFAULT_HANDLER)
            ->once()
            ->with(true, \Mockery::type(Logger::class), $defaultHandler)
            ->andReturn(true);

        Filters\expectApplied(Channels::FILTER_USE_DEFAULT_PROCESSOR)
            ->once()
            ->with(true, \Mockery::type(Logger::class), 'strtolower')
            ->andReturn(true);

        Actions\expectDone(Channels::ACTION_LOGGER)
            ->once()
            ->with(\Mockery::type(Logger::class), $handlers, $processors);

        $channels = new Channels($handlers, $processors);
        $logger = $channels->logger(Channels::DEBUG);

        static::assertInstanceOf(Logger::class, $logger);
        static::assertSame($logger, $channels->logger(Channels::DEBUG));
        static::assertSame($logger, $channels->logger(Channels::DEBUG));
        static::assertEquals([$defaultHandler], $logger->getHandlers());
        static::assertEquals(['strtolower'], $logger->getProcessors());
    }

    public function testLoggerInitializeSkipDefaultsViaFilter()
    {
        /** @var HandlersRegistry|\Mockery\MockInterface $handlers */
        $handlers = \Mockery::mock(HandlersRegistry::class);

        /** @var ProcessorsRegistry|\Mockery\MockInterface $processors */
        $processors = \Mockery::mock(ProcessorsRegistry::class);

        $defaultHandler = \Mockery::mock(HandlerInterface::class);
        $defaultHandler->shouldReceive('close')->andReturnNull();

        $handlers->shouldReceive('find')
            ->once()
            ->with(HandlersRegistry::DEFAULT_NAME)
            ->andReturn($defaultHandler);

        $processors->shouldReceive('find')
            ->once()
            ->with(ProcessorsRegistry::DEFAULT_NAME)
            ->andReturn('strtolower');

        Filters\expectApplied(Channels::FILTER_USE_DEFAULT_HANDLER)
            ->once()
            ->with(true, \Mockery::type(Logger::class), $defaultHandler)
            ->andReturn(false);

        Filters\expectApplied(Channels::FILTER_USE_DEFAULT_PROCESSOR)
            ->once()
            ->with(true, \Mockery::type(Logger::class), 'strtolower')
            ->andReturn(false);

        /** @var $customHandler \Mockery|MockInterface|HandlerInterface $handler */
        $customHandler = \Mockery::mock(HandlerInterface::class);
        $customHandler->shouldReceive('close')->andReturnNull();

        Actions\expectDone(Channels::ACTION_LOGGER)
            ->once()
            ->with(\Mockery::type(Logger::class), $handlers, $processors)
            ->whenHappen(
                static function (Logger $logger) use ($customHandler) {
                    $logger->pushHandler($customHandler);
                }
            );

        $channels = new Channels($handlers, $processors);
        $logger = $channels->logger(Channels::DEBUG);

        static::assertInstanceOf(Logger::class, $logger);
        static::assertSame($logger, $channels->logger(Channels::DEBUG));
        static::assertSame($logger, $channels->logger(Channels::DEBUG));
        static::assertSame([$customHandler], $logger->getHandlers());
        static::assertSame([], $logger->getProcessors());
    }

    /**
     * @return Channels
     */
    private function createChannels(): Channels
    {
        /** @var HandlersRegistry $handlers */
        $handlers = \Mockery::mock(HandlersRegistry::class);
        /** @var ProcessorsRegistry $processors */
        $processors = \Mockery::mock(ProcessorsRegistry::class);

        return new Channels($handlers, $processors);
    }
}

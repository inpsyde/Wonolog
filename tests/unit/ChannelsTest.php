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

use Brain\Monkey;
use Inpsyde\Wonolog\Channels;
use Inpsyde\Wonolog\Data\Debug;
use Inpsyde\Wonolog\Data\Emergency;
use Inpsyde\Wonolog\Data\Info;
use Inpsyde\Wonolog\Factory;
use Inpsyde\Wonolog\Registry\HandlersRegistry;
use Inpsyde\Wonolog\Tests\UnitTestCase;
use Monolog\Handler\BufferHandler;
use Monolog\Handler\TestHandler;
use Monolog\Logger;
use Psr\Log\NullLogger;

class ChannelsTest extends UnitTestCase
{
    /**
     * @test
     */
    public function testAChannelStorage(): void
    {
        $channels = Factory::new()->channels();

        static::assertTrue($channels->hasChannel(Channels::DEBUG));
        static::assertFalse($channels->hasChannel('Foo'));

        $channels->addChannel('Foo');
        static::assertTrue($channels->hasChannel(Channels::DEBUG));
        static::assertTrue($channels->hasChannel('Foo'));

        $channels->removeChannel(Channels::DEBUG);
        static::assertFalse($channels->hasChannel(Channels::DEBUG));
        static::assertTrue($channels->hasChannel('Foo'));
    }

    /**
     * @test
     */
    public function testLoggerReturnsNullLoggerForNonExistentChannel(): void
    {
        $channels = Factory::new()->channels();

        static::assertInstanceOf(NullLogger::class, $channels->logger('X'));
    }

    /**
     * @test
     */
    public function testLoggerInitializeOnceAndReturnAlwaysNotBuffered(): void
    {
        Monkey\Actions\expectDone(Channels::ACTION_LOGGER)->once();
        Monkey\Filters\expectApplied(HandlersRegistry::FILTER_BUFFER_HANDLER)
            ->once()
            ->andReturn(false);

        $factory = Factory::new();
        $channels = $factory->channels();
        $handler = new TestHandler();
        $factory->handlersRegistry()->addHandler($handler, 'test');

        $logger = $channels->logger(Channels::DEBUG);

        static::assertInstanceOf(Logger::class, $logger);
        static::assertSame($logger, $channels->logger(Channels::DEBUG));
        static::assertSame($logger, $channels->logger(Channels::DEBUG));
        static::assertEquals([$handler], $logger->getHandlers());
    }

    /**
     * @test
     */
    public function testLoggerInitializeOnceAndReturnAlwaysBuffered(): void
    {
        Monkey\Actions\expectDone(Channels::ACTION_LOGGER)->once();

        $factory = Factory::new();
        $channels = $factory->channels();
        $handler = new TestHandler();
        $factory->handlersRegistry()->addHandler($handler, 'test');

        $logger = $channels->logger(Channels::DEBUG);

        static::assertInstanceOf(Logger::class, $logger);
        static::assertSame($logger, $channels->logger(Channels::DEBUG));
        static::assertSame($logger, $channels->logger(Channels::DEBUG));

        $handlers = $logger->getHandlers();
        static::assertIsArray($handlers);
        static::assertCount(1, $handlers);

        $handler = reset($handlers);

        static::assertInstanceOf(BufferHandler::class, $handler);
    }

    /**
     * @test
     */
    public function testBlackList(): void
    {
        $channels = Factory::new()->channels();
        $channels
            ->withIgnorePattern('~annoying')
            ->withIgnorePattern('^prefix')
            ->withIgnorePattern('ABC: [0-9]+', Logger::ALERT)
            ->withIgnorePattern('cron "[^"]+"', null, Channels::CRON);

        static::assertTrue($channels->isIgnored(new Debug('~~annoying~~', Channels::DEBUG)));
        static::assertTrue($channels->isIgnored(new Debug('prefix foo', Channels::DEBUG)));
        static::assertFalse($channels->isIgnored(new Debug('-prefix foo', Channels::DEBUG)));
        static::assertTrue($channels->isIgnored(new Debug('->ABC: 123', Channels::DEBUG)));
        static::assertFalse($channels->isIgnored(new Emergency('->ABC: 123', Channels::DEBUG)));
        static::assertFalse($channels->isIgnored(new Info('cron "foo" done', Channels::DEBUG)));
        static::assertTrue($channels->isIgnored(new Info('cron "foo" done', Channels::CRON)));
    }
}

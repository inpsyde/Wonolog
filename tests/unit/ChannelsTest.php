<?php

declare(strict_types=1);

namespace Inpsyde\Wonolog\Tests\Unit;

use Brain\Monkey;
use Inpsyde\Wonolog\Channels;
use Inpsyde\Wonolog\Data\Debug;
use Inpsyde\Wonolog\Data\Emergency;
use Inpsyde\Wonolog\Data\Info;
use Inpsyde\Wonolog\Factory;
use Inpsyde\Wonolog\LogLevel;
use Inpsyde\Wonolog\Registry\HandlersRegistry;
use Inpsyde\Wonolog\Tests\UnitTestCase;
use Monolog\Handler\BufferHandler;
use Monolog\Handler\TestHandler;
use Monolog\Logger;
use Psr\Log\NullLogger;

final class ChannelsTest extends UnitTestCase
{
    /**
     * @test
     */
    public function testAChannelStorage(): void
    {
        $channels = Factory::new()->channels();

        $this->assertTrue($channels->hasChannel(Channels::DEBUG));
        $this->assertFalse($channels->hasChannel('Foo'));

        $channels->addChannel('Foo');
        $this->assertTrue($channels->hasChannel(Channels::DEBUG));
        $this->assertTrue($channels->hasChannel('Foo'));

        $channels->removeChannel(Channels::DEBUG);
        $this->assertFalse($channels->hasChannel(Channels::DEBUG));
        $this->assertTrue($channels->hasChannel('Foo'));
    }

    /**
     * @test
     */
    public function testLoggerReturnsNullLoggerForNonExistentChannel(): void
    {
        $channels = Factory::new()->channels();

        $this->assertInstanceOf(NullLogger::class, $channels->logger('X'));
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

        $this->assertInstanceOf(Logger::class, $logger);
        $this->assertSame($logger, $channels->logger(Channels::DEBUG));
        // @phpstan-ignore method.alreadyNarrowedType
        $this->assertSame($logger, $channels->logger(Channels::DEBUG));
        $this->assertEquals([$handler], $logger->getHandlers());
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

        $this->assertInstanceOf(Logger::class, $logger);
        $this->assertSame($logger, $channels->logger(Channels::DEBUG));
        // @phpstan-ignore method.alreadyNarrowedType
        $this->assertSame($logger, $channels->logger(Channels::DEBUG));

        $handlers = $logger->getHandlers();
        $this->assertCount(1, $handlers);

        $handler = reset($handlers);

        $this->assertInstanceOf(BufferHandler::class, $handler);
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
            ->withIgnorePattern('ABC: [0-9]+', LogLevel::ALERT)
            ->withIgnorePattern('cron "[^"]+"', null, Channels::CRON);

        $this->assertTrue($channels->isIgnored(new Debug('~~annoying~~', Channels::DEBUG)));
        $this->assertTrue($channels->isIgnored(new Debug('prefix foo', Channels::DEBUG)));
        $this->assertFalse($channels->isIgnored(new Debug('-prefix foo', Channels::DEBUG)));
        $this->assertTrue($channels->isIgnored(new Debug('->ABC: 123', Channels::DEBUG)));
        $this->assertFalse($channels->isIgnored(new Emergency('->ABC: 123', Channels::DEBUG)));
        $this->assertFalse($channels->isIgnored(new Info('cron "foo" done', Channels::DEBUG)));
        $this->assertTrue($channels->isIgnored(new Info('cron "foo" done', Channels::CRON)));
    }
}

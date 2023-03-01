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

namespace Inpsyde\Wonolog\Tests\Unit\Registry;

use Brain\Monkey\Actions;
use Brain\Monkey\Filters;
use Inpsyde\Wonolog\Channels;
use Inpsyde\Wonolog\DefaultHandler\FileHandler;
use Inpsyde\Wonolog\Factory;
use Inpsyde\Wonolog\Registry\HandlersRegistry;
use Inpsyde\Wonolog\Tests\UnitTestCase;
use Monolog\Handler\HandlerInterface;
use Monolog\Handler\TestHandler;

class HandlersRegistryTest extends UnitTestCase
{
    /**
     * @test
     */
    public function testSameHandlerIsAddedOnce(): void
    {
        $registry = Factory::new()->handlersRegistry();

        $handlerOne = new TestHandler();
        $handlerTwo = new TestHandler();
        $handlerThree = new TestHandler();

        $registry->addHandler($handlerOne, 'test');
        $registry->addHandler($handlerTwo, 'test');
        $registry->addHandler($handlerThree, 'test');

        static::assertCount(1, $registry);
    }

    /**
     * @test
     */
    public function testHasHandlerForChannel(): void
    {
        $registry = Factory::new()->handlersRegistry();

        $handler = new TestHandler();
        $registry->addHandler($handler, 'x');
        $registry->removeHandlerFromChannels('x', Channels::CRON);

        static::assertFalse($registry->hasHandlerForChannel('x', Channels::CRON));
        static::assertTrue($registry->hasHandlerForChannel('x', Channels::DEBUG));
        static::assertTrue($registry->hasHandlerForChannel('x', Channels::HTTP));

        static::assertCount(1, $registry);
    }

    /**
     * @test
     */
    public function testFindById(): void
    {
        $registry = Factory::new()->handlersRegistry();

        /** @var HandlerInterface $handlerOne */
        $handlerOne = \Mockery::mock(HandlerInterface::class);
        $handlerTwo = clone $handlerOne;
        $handlerThree = clone $handlerOne;

        $registry->addHandler($handlerOne, 'a');
        $registry->addHandler($handlerTwo, 'b');
        $registry->addHandler($handlerThree, 'c');

        static::assertSame($handlerOne, $registry->findById('a'));
        static::assertSame($handlerTwo, $registry->findById('b'));
        static::assertSame($handlerThree, $registry->findById('c'));
        static::assertNull($registry->findById('x'));

        static::assertCount(3, $registry);
    }

    /**
     * @test
     */
    public function testHandlerNoChannelsIsRemoved(): void
    {
        $registry = Factory::new()->handlersRegistry();

        /** @var HandlerInterface $handler */
        $handler = \Mockery::mock(HandlerInterface::class);
        $registry->addHandler($handler, 'X', Channels::HTTP, Channels::CRON);
        $registry->removeHandlerFromChannels('X', Channels::HTTP);
        $registry->removeHandlerFromChannels('X', Channels::CRON);

        static::assertCount(0, $registry);
    }

    /**
     * @test
     */
    public function testFindForChannel(): void
    {
        $registry = Factory::new()->handlersRegistry();

        $registry->addHandler(new TestHandler(), 'test', Channels::HTTP, Channels::CRON);
        $registry->addHandler(FileHandler::new(), 'default');
        $registry->removeHandlerFromChannels('default', Channels::HTTP);

        Actions\expectDone(HandlersRegistry::ACTION_SETUP)
            ->once()
            ->with(
                \Mockery::type(FileHandler::class),
                'default'
            );

        Actions\expectDone(HandlersRegistry::ACTION_SETUP)
            ->once()
            ->with(
                \Mockery::type(TestHandler::class),
                'test'
            );

        Filters\expectApplied(HandlersRegistry::FILTER_BUFFER_HANDLER)
            ->once()
            ->andReturn(false);

        static::assertCount(2, $registry);

        $http = $registry->findForChannel(Channels::HTTP);
        static::assertCount(1, $http);
        static::assertInstanceOf(TestHandler::class, $http[0]);

        $cron = $registry->findForChannel(Channels::CRON);
        static::assertCount(2, $cron);
        static::assertInstanceOf(TestHandler::class, $cron[0]);
        static::assertInstanceOf(FileHandler::class, $cron[1]);

        $debug = $registry->findForChannel(Channels::DEBUG);
        static::assertCount(1, $debug);
        static::assertInstanceOf(FileHandler::class, $debug[0]);

        $db = $registry->findForChannel(Channels::DB);
        static::assertCount(1, $db);
        static::assertInstanceOf(FileHandler::class, $db[0]);

        $registry->removeHandler('test');
        static::assertSame([], $registry->findForChannel(Channels::HTTP));

        $cronAgain = $registry->findForChannel(Channels::CRON);
        static::assertCount(1, $cronAgain);
        static::assertInstanceOf(FileHandler::class, $cronAgain[0]);

        $dbAgain = $registry->findForChannel(Channels::DB);
        static::assertCount(1, $dbAgain);
        static::assertInstanceOf(FileHandler::class, $dbAgain[0]);

        $registry->removeHandlerFromChannels('default', Channels::CRON);
        static::assertSame([], $registry->findForChannel(Channels::CRON));

        $dbThirdTime = $registry->findForChannel(Channels::DB);
        static::assertCount(1, $dbThirdTime);
        static::assertInstanceOf(FileHandler::class, $dbThirdTime[0]);
    }
}

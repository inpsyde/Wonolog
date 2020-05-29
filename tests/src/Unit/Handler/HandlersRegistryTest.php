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

namespace Inpsyde\Wonolog\Tests\Unit\Handler;

use Brain\Monkey\Actions;
use Inpsyde\Wonolog\Handler\HandlersRegistry;
use Inpsyde\Wonolog\Processor\ProcessorsRegistry;
use Inpsyde\Wonolog\Tests\TestCase;
use Monolog\Handler\HandlerInterface;

/**
 * @package wonolog\tests
 * @license http://opensource.org/licenses/MIT MIT
 */
class HandlersRegistryTest extends TestCase
{
    public function testSameHandlerIsAddedOnce()
    {
        /** @var ProcessorsRegistry $processorsRegistry */
        $processorsRegistry = \Mockery::mock(ProcessorsRegistry::class);
        $registry = new HandlersRegistry($processorsRegistry);

        /** @var HandlerInterface $handlerOne */
        $handlerOne = \Mockery::mock(HandlerInterface::class);
        $handlerTwo = clone $handlerOne;
        $handlerThree = clone $handlerOne;

        $registry->addHandler($handlerOne, 'test');
        $registry->addHandler($handlerTwo, 'test');
        $registry->addHandler($handlerThree, 'test');

        self::assertCount(1, $registry);
    }

    public function testHasHandler()
    {
        /** @var ProcessorsRegistry $processorsRegistry */
        $processorsRegistry = \Mockery::mock(ProcessorsRegistry::class);
        $registry = new HandlersRegistry($processorsRegistry);

        /** @var HandlerInterface $handlerOne */
        $handlerOne = \Mockery::mock(HandlerInterface::class);
        $handlerTwo = clone $handlerOne;
        $handlerThree = clone $handlerOne;

        $registry->addHandler($handlerOne, 'a');
        $registry->addHandler($handlerTwo, 'b');
        $registry->addHandler($handlerThree, 'c');

        self::assertTrue($registry->hasHandler('a'));
        self::assertTrue($registry->hasHandler('b'));
        self::assertTrue($registry->hasHandler('c'));
        self::assertFalse($registry->hasHandler('x'));

        self::assertCount(3, $registry);
    }

    public function testFindByNameCallRegisterOnce()
    {
        /** @var ProcessorsRegistry $processorsRegistry */
        $processorsRegistry = \Mockery::mock(ProcessorsRegistry::class);
        $registry = new HandlersRegistry($processorsRegistry);

        Actions\expectDone(HandlersRegistry::ACTION_REGISTER)
            ->once()
            ->with($registry);

        self::assertCount(0, $registry);
        self::assertNull($registry->find('foo'));
        self::assertNull($registry->find('bar'));
        self::assertNull($registry->find('baz'));
        self::assertNull($registry->find(1));
    }

    public function testRegisterOnFindByName()
    {
        /** @var ProcessorsRegistry $processorsRegistry */
        $processorsRegistry = \Mockery::mock(ProcessorsRegistry::class);
        $registry = new HandlersRegistry($processorsRegistry);

        /** @var HandlerInterface $handlerOne */
        $handlerOne = \Mockery::mock(HandlerInterface::class);
        $handlerTwo = clone $handlerOne;

        Actions\expectDone(HandlersRegistry::ACTION_REGISTER)
            ->once()
            ->with($registry)
            ->whenHappen(
                static function (HandlersRegistry $registry) use ($handlerOne, $handlerTwo) {
                    $registry->addHandler($handlerOne, 'a');
                    $registry->addHandler($handlerTwo, 'b');
                }
            );

        Actions\expectDone(HandlersRegistry::ACTION_SETUP)
            ->once()
            ->with($handlerOne, 'a', $processorsRegistry);

        Actions\expectDone(HandlersRegistry::ACTION_SETUP)
            ->once()
            ->with($handlerTwo, 'b', $processorsRegistry);

        self::assertSame($handlerOne, $registry->find('a'));
        self::assertSame($handlerTwo, $registry->find('b'));
        self::assertSame($handlerOne, $registry->find('a'));
        self::assertSame($handlerTwo, $registry->find('b'));
        self::assertSame($handlerOne, $registry->find('a'));
        self::assertSame($handlerTwo, $registry->find('b'));
        self::assertNull($registry->find('bar'));
        self::assertNull($registry->find('baz'));
        self::assertCount(2, $registry);
    }
}

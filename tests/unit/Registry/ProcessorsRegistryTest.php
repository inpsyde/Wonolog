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

use Inpsyde\Wonolog\Factory;
use Inpsyde\Wonolog\Tests\UnitTestCase;
use Monolog\LogRecord;

class ProcessorsRegistryTest extends UnitTestCase
{
    /**
     * @test
     */
    public function testSameProcessorIsAddedOnce(): void
    {
        $registry = Factory::new()->processorsRegistry();

        $callback = static function (array|LogRecord $record): array|LogRecord {
            return $record;
        };

        $handlerOne = $callback;
        $handlerTwo = clone $callback;
        $handlerThree = $callback;

        $registry->addProcessor($handlerOne, 'test');
        $registry->addProcessor($handlerTwo, 'test');
        $registry->addProcessor($handlerThree, 'test');

        static::assertCount(1, $registry);
    }

    /**
     * @test
     */
    public function testChannelSpecificProcessors(): void
    {
        $registry = Factory::new()->processorsRegistry();

        $callback1 = static function (array|LogRecord $record): array|LogRecord {
            return $record;
        };
        $callback2 = static function (array|LogRecord $record): array|LogRecord {
            return $record;
        };

        $registry->addProcessor($callback1, 'x', 'A', 'B');
        $registry->addProcessor($callback2, 'y', 'C', 'D');
        $registry->removeProcessorFromChannels('x', 'B');

        static::assertTrue($registry->hasProcessorForAnyChannel('x'));
        static::assertTrue($registry->hasProcessorForAnyChannel('y'));
        static::assertTrue($registry->hasProcessorForChannel('x', 'A'));
        static::assertFalse($registry->hasProcessorForChannel('x', 'B'));
        static::assertTrue($registry->hasProcessorForChannel('y', 'C'));
        static::assertTrue($registry->hasProcessorForChannel('y', 'D'));
        static::assertFalse($registry->hasProcessorForChannel('x', 'Meh'));
        static::assertFalse($registry->hasProcessorForChannel('y', 'Meh'));
    }

    /**
     * @test
     */
    public function testAllForLogger(): void
    {
        $registry = Factory::new()->processorsRegistry();

        $callback1 = static function (array|LogRecord $record): array|LogRecord {
            return $record;
        };
        $callback2 = static function (array|LogRecord $record): array|LogRecord {
            return $record;
        };

        $registry->addProcessor($callback1, 'x', 'A', 'B');
        $registry->addProcessor($callback2, 'y', 'A', 'C');

        $aProc = $registry->findForChannel('A');
        $bProc = $registry->findForChannel('B');
        $cProc = $registry->findForChannel('C');
        $dProc = $registry->findForChannel('D');

        static::assertSame([$callback1, $callback2], $aProc);
        static::assertSame([$callback1], $bProc);
        static::assertSame([$callback2], $cProc);
        static::assertSame([], $dProc);
    }

    /**
     * @test
     */
    public function testFindById(): void
    {
        $registry = Factory::new()->processorsRegistry();

        $callback1 = static function (array|LogRecord $record): array|LogRecord {
            return $record;
        };
        $callback2 = static function (array|LogRecord $record): array|LogRecord {
            return $record;
        };

        $registry->addProcessor($callback1, 'x');
        $registry->addProcessor($callback2, 'y', 'A', 'C');

        static::assertSame($callback1, $registry->findById('x'));
        static::assertSame($callback2, $registry->findById('y'));
        static::assertNull($registry->findById('z'));
    }
}

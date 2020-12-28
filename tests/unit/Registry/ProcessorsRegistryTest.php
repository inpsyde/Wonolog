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

class ProcessorsRegistryTest extends UnitTestCase
{
    /**
     * @test
     */
    public function testSameProcessorIsAddedOnce(): void
    {
        $registry = Factory::new()->processorsRegistry();

        $cb = function () {};

        $handlerOne = $cb;
        $handlerTwo = clone $cb;
        $handlerThree = $cb;

        $registry->addProcessor($handlerOne, 'test');
        $registry->addProcessor($handlerTwo, 'test');
        $registry->addProcessor($handlerThree, 'test');

        self::assertCount(1, $registry);
    }

    /**
     * @test
     */
    public function testChannelSpecificProcessors(): void
    {
        $registry = Factory::new()->processorsRegistry();

        $cb1 = function () {};
        $cb2 = function () {};

        $registry->addProcessor($cb1, 'x', 'A', 'B');
        $registry->addProcessor($cb2, 'y', 'C', 'D');
        $registry->removeProcessorFromLoggers('x', 'B');

        static::assertTrue($registry->hasProcessor('x'));
        static::assertTrue($registry->hasProcessor('y'));
        static::assertTrue($registry->hasProcessorForLogger('x', 'A'));
        static::assertFalse($registry->hasProcessorForLogger('x', 'B'));
        static::assertTrue($registry->hasProcessorForLogger('y', 'C'));
        static::assertTrue($registry->hasProcessorForLogger('y', 'D'));
        static::assertFalse($registry->hasProcessorForLogger('x', 'Meh'));
        static::assertFalse($registry->hasProcessorForLogger('y', 'Meh'));
    }

    /**
     * @test
     */
    public function testAllForLogger(): void
    {
        $registry = Factory::new()->processorsRegistry();

        $cb1 = function () {};
        $cb2 = function () {};

        $registry->addProcessor($cb1, 'x', 'A', 'B');
        $registry->addProcessor($cb2, 'y', 'A', 'C');

        $aProc = $registry->allForLogger('A');
        $bProc = $registry->allForLogger('B');
        $cProc = $registry->allForLogger('C');
        $dProc = $registry->allForLogger('D');

        static::assertSame([$cb1, $cb2], $aProc);
        static::assertSame([$cb1], $bProc);
        static::assertSame([$cb2], $cProc);
        static::assertSame([], $dProc);
    }

    /**
     * @test
     */
    public function testFindById(): void
    {
        $registry = Factory::new()->processorsRegistry();

        $cb1 = function () {};
        $cb2 = function () {};

        $registry->addProcessor($cb1, 'x');
        $registry->addProcessor($cb2, 'y', 'A', 'C');

        static::assertSame($cb1, $registry->findById('x'));
        static::assertSame($cb2, $registry->findById('y'));
        static::assertNull($registry->findById('z'));
    }
}

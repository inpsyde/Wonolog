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

        $cb = static function (): void {};

        $handlerOne = $cb;
        $handlerTwo = clone $cb;
        $handlerThree = $cb;

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

        $cb1 = static function (): void {};
        $cb2 = static function (): void {};

        $registry->addProcessor($cb1, 'x', 'A', 'B');
        $registry->addProcessor($cb2, 'y', 'C', 'D');
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

        $cb1 = static function (): void {};
        $cb2 = static function (): void {};

        $registry->addProcessor($cb1, 'x', 'A', 'B');
        $registry->addProcessor($cb2, 'y', 'A', 'C');

        $aProc = $registry->findForChannel('A');
        $bProc = $registry->findForChannel('B');
        $cProc = $registry->findForChannel('C');
        $dProc = $registry->findForChannel('D');

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

        $cb1 = static function (): void {};
        $cb2 = static function (): void {};

        $registry->addProcessor($cb1, 'x');
        $registry->addProcessor($cb2, 'y', 'A', 'C');

        static::assertSame($cb1, $registry->findById('x'));
        static::assertSame($cb2, $registry->findById('y'));
        static::assertNull($registry->findById('z'));
    }
}

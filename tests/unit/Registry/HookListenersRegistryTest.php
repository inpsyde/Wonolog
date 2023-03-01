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

use Brain\Monkey;
use Inpsyde\Wonolog\Factory;
use Inpsyde\Wonolog\HookListener\ActionListener;
use Inpsyde\Wonolog\HookListener\FilterListener;
use Inpsyde\Wonolog\Tests\UnitTestCase;

class HookListenersRegistryTest extends UnitTestCase
{
    /**
     * @test
     */
    public function testRegisterListener(): void
    {
        $defPriority = random_int(7, 99);
        $args = PHP_INT_MAX;

        $listener1 = \Mockery::mock(ActionListener::class);
        $listener2 =  \Mockery::mock(FilterListener::class);
        $listener3 = clone $listener1;
        $listener4 = clone $listener2;
        $listener5 = clone $listener1;

        $listener1->allows('listenTo')->andReturn(['one.log']);
        $listener2->expects('listenTo')->andReturn(['two.log']);
        $listener3->expects('listenTo')->andReturn(['three-a.log', 'three-b.log']);
        $listener4->expects('listenTo')->andReturn(['four-a.log', 'four-b.log']);
        $listener5->expects('listenTo')->andReturn([]);

        $registry = Factory::new()->listenersRegistry();
        $registry
            ->addActionListener('one', $listener1)
            ->addFilterListener('two', $listener2)
            ->addActionListenerWithPriority('three', $listener3, 3)
            ->addFilterListenerWithPriority('four', $listener4, 4)
            ->addActionListener('five', $listener5)
            ->removeListener('one');

        Monkey\Filters\expectAdded('one.log')->never();

        Monkey\Filters\expectAdded('two.log')
            ->once()
            ->with(\Mockery::type('Closure'), $defPriority, $args);

        Monkey\Actions\expectAdded('three-a.log')
            ->once()
            ->with(\Mockery::type('Closure'), 3, $args);
        Monkey\Actions\expectAdded('three-b.log')
            ->once()
            ->with(\Mockery::type('Closure'), 3, $args);

        Monkey\Filters\expectAdded('four-a.log')
            ->once()
            ->with(\Mockery::type('Closure'), 4, $args);
        Monkey\Filters\expectAdded('four-b.log')
            ->once()
            ->with(\Mockery::type('Closure'), 4, $args);

        static::assertFalse($registry->hasListener('one'));
        static::assertTrue($registry->hasListener('two'));
        static::assertTrue($registry->hasListener('three'));
        static::assertTrue($registry->hasListener('four'));
        static::assertTrue($registry->hasListener('five'));

        $registry->listenAll($defPriority);
    }
}

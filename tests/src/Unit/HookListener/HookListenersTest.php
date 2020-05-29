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

namespace Inpsyde\Wonolog\Tests\Unit\HookListener;

use Inpsyde\Wonolog\HookListener\HookListenersRegistry;
use Inpsyde\Wonolog\HookListener\HookListenerInterface;
use Inpsyde\Wonolog\Tests\TestCase;

/**
 * @package wonolog\tests
 * @license http://opensource.org/licenses/MIT MIT
 */
class HookListenersTest extends TestCase
{

    public function testRegisterListener()
    {
        $listener1 = \Mockery::mock(HookListenerInterface::class);
        $listener1->shouldReceive('id')->andReturnUsing(
            static function (): string {
                return __CLASS__;
            }
        );

        $listener2 = clone $listener1;
        $listener3 = clone $listener2;

        $listeners = new HookListenersRegistry();
        $listeners
            ->registerListener($listener1)
            ->registerListener($listener2)
            ->registerListener($listener3);

        $all = $listeners->listeners();

        $custom = [];
        foreach ($all as $listener) {
            if (
                $listener === $listener1
                || $listener === $listener2
                || $listener === $listener3
            ) {
                $custom[] = $listener;
            }
        }

        static::assertSame([$listener1], $custom);
    }
}

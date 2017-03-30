<?php # -*- coding: utf-8 -*-
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
class HookListenersTest extends TestCase {

	public function test_register_listener() {

		$listener_1 = \Mockery::mock( HookListenerInterface::class );
		$listener_1->shouldReceive('id')->andReturnUsing(function() {
			return get_called_class();
		});

		$listener_2 = clone $listener_1;
		$listener_3 = clone $listener_2;

		$listeners = new HookListenersRegistry();
		$listeners
			->register_listener( $listener_1 )
			->register_listener( $listener_2 )
			->register_listener( $listener_3 );

		$all = $listeners->listeners();

		$custom = [];
		foreach ( $all as $listener ) {
			if (
				$listener === $listener_1
				|| $listener === $listener_2
				|| $listener === $listener_3
			) {
				$custom[] = $listener;
			}
		}

		self::assertSame( [ $listener_1 ], $custom );

	}
}

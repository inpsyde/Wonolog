<?php # -*- coding: utf-8 -*-

namespace Inpsyde\Wonolog\Tests\Unit\HookListener;

use Inpsyde\Wonolog\HookListener\HookListenersRegistry;
use Inpsyde\Wonolog\HookListener\HookListenerInterface;
use Inpsyde\Wonolog\Tests\TestCase;

/**
 * @package wonolog\tests
 */
class HookListenersTest extends TestCase {

	public function test_register_listener() {

		$listener_1 = \Mockery::mock( HookListenerInterface::class );
		$listener_1->shouldReceive('id')->andReturnUsing(function() {
			return __CLASS__;
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

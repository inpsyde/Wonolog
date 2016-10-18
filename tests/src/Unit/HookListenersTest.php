<?php # -*- coding: utf-8 -*-
/*
 * This file is part of theInpsyde wonolog package.
 *
 * (c) Inpsyde GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Inpsyde\Wonolog\Tests\Unit;

use Andrew\Proxy;
use Inpsyde\Wonolog\HookListenersRegistry;
use Inpsyde\Wonolog\HookListeners\HookListenerInterface;
use Inpsyde\Wonolog\Tests\TestCase;

/**
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @package wonolog
 * @license http://opensource.org/licenses/MIT MIT
 */
class HookListenersTest extends TestCase {

	public function test_register_listener() {

		$listener_1 = \Mockery::mock( HookListenerInterface::class );
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

	public function test_register_listener_factory() {

		$listener_1 = \Mockery::mock( HookListenerInterface::class );

		$factory = function () use ( $listener_1 ) {

			return $listener_1;
		};

		$listeners = new HookListenersRegistry();
		$listeners->register_listener_factory( $factory );

		$all = $listeners->listeners();

		$custom = [];
		foreach ( $all as $listener ) {
			if ( $listener === $listener_1 ) {
				$custom[] = $listener;
			}
		}

		self::assertSame( [ $listener_1 ], $custom );

	}

	public function test_flush_do_nothing_id_not_loaded() {

		$listener_1 = \Mockery::mock( HookListenerInterface::class );
		$listener_2 = clone $listener_1;

		$factory = function () use ( $listener_2 ) {

			return $listener_2;
		};

		$listeners = new HookListenersRegistry();
		$listeners
			->register_listener( $listener_1 )
			->register_listener_factory( $factory );

		$proxy = new Proxy( $listeners );

		self::assertCount( 1, $proxy->factories );
		self::assertCount( 1, $proxy->listeners );

		$listeners->flush();

		self::assertCount( 1, $proxy->factories );
		self::assertCount( 1, $proxy->listeners );
	}

	public function test_flush() {

		$listener_1 = \Mockery::mock( HookListenerInterface::class );
		$listener_2 = clone $listener_1;

		$factory = function () use ( $listener_2 ) {

			return $listener_2;
		};

		$listeners = new HookListenersRegistry();
		$listeners
			->register_listener( $listener_1 )
			->register_listener_factory( $factory );

		$proxy = new Proxy( $listeners );

		self::assertCount( 1, $proxy->factories );
		self::assertCount( 1, $proxy->listeners );

		do_action( 'wonolog.loaded' );

		$listeners->flush();

		self::assertCount( 0, $proxy->factories );
		self::assertCount( 0, $proxy->listeners );
	}
}

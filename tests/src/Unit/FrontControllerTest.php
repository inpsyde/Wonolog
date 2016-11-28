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

use Brain\Monkey;
use Brain\Monkey\WP\Actions;
use Brain\Monkey\WP\Filters;
use DeepCopy\Filter\Filter;
use Inpsyde\Wonolog\FrontController;
use Inpsyde\Wonolog\HookListeners\ActionListenerInterface;
use Inpsyde\Wonolog\HookListeners\FilterListenerInterface;
use Inpsyde\Wonolog\HookListeners\HookPriorityInterface;
use Inpsyde\Wonolog\Tests\TestCase;
use Mockery;

/**
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @package wonolog
 * @license http://opensource.org/licenses/MIT MIT
 */
class FrontControllerTest extends TestCase {

	public function test_boot_do_nothing_if_disabled() {

		Filters::expectApplied( 'wonolog.enable' )
			->andReturn( FALSE );

		Actions::expectFired( 'wonolog.register-listeners' )
			->never();

		$controller = new FrontController();
		$controller->setup();
	}

	/**
	 * @dataProvider listen_hook_priority_data
	 *
	 * @param array     $types
	 * @param string    $hook
	 * @param bool|int  $priority
	 */
	public function test_listen_hook_priority( array $types, $hook, $priority ) {

		$controller = new FrontController();

		$listener = Mockery::mock( implode( ',', $types ) );
		if ( in_array( HookPriorityInterface::class, $types ) ) {
			$listener->shouldReceive( 'priority' )
				->once()
				->andReturn( $priority );
		} else {
			$listener->shouldReceive( 'priority' )
				->never();
		}

		$priority or $priority = PHP_INT_MAX - 10; // Default priority

		$mock_builder = NULL;
		in_array( ActionListenerInterface::class, $types ) and $mock_builder = Actions::class;
		in_array( FilterListenerInterface::class, $types ) and $mock_builder = Filters::class;
		$mock_builder or $this->markTestSkipped( "Invalid test data configuration" );

		$mock_builder::expectAdded( $hook )
			->once()
			->with( Mockery::type( 'Closure' ), $priority, 9999 );

		/**
		 * This is just a cheap hack, don't do this at home, kids!
		 */
		$reflection = new \ReflectionObject( $controller );
		$method = $reflection->getMethod( 'listen_hook' );
		$method->setAccessible( TRUE );

		$method->invokeArgs( $controller, [ $hook, 0, $listener ] );
	}

	/**
	 * @see test_listen_hook_priority
	 * @return array
	 */
	public function listen_hook_priority_data() {

		$data = [];
		$data[ 'action_with_custom_priority' ] = [
			[ ActionListenerInterface::class, HookPriorityInterface::class ],
			'wp_loaded',
			42
		];

		$data[ 'filter_with_custom_priority' ] = [
			[ FilterListenerInterface::class, HookPriorityInterface::class ],
			'the_title',
			-42
		];

		$data[ 'action_with_default_priority' ] = [
			[ ActionListenerInterface::class ],
			'the_title',
			FALSE
		];

		$data[ 'filter_with_default_priority' ] = [
			[ FilterListenerInterface::class ],
			'the_title',
			FALSE
		];

		return $data;
	}
}

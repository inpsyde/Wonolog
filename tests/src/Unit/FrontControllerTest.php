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
	 * @param array    $types
	 * @param string   $hook
	 * @param bool|int $priority
	 */
	public function test_listen_hook_priority( array $types, $hook, $priority ) {

		$listener = Mockery::mock( implode( ',', $types ) );

		$listener->shouldReceive( 'priority' )
			->times( in_array( HookPriorityInterface::class, $types ) ? 1 : 0 )
			->andReturn( $priority );

		$mock_builder = in_array( ActionListenerInterface::class, $types )
			? Monkey::actions()
			: Monkey::filters();

		$mock_builder::expectAdded( $hook )
			->once()
			->with( Mockery::type( 'Closure' ), $priority, PHP_INT_MAX );

		$proxy = new Proxy( new FrontController() );
		/** @noinspection PhpUndefinedMethodInspection */
		$proxy->listen_hook( $hook, 0, $listener );
	}

	/**
	 * @see test_listen_hook_priority
	 * @return array
	 */
	public function listen_hook_priority_data() {

		$data                                  = [];
		$data[ 'action_with_custom_priority' ] = [
			[ ActionListenerInterface::class, HookPriorityInterface::class ],
			'wp_loaded',
			42
		];

		$data[ 'filter_with_custom_priority' ] = [
			[ FilterListenerInterface::class, HookPriorityInterface::class ],
			'the_title',
			- 42
		];

		$data[ 'action_with_default_priority' ] = [
			[ ActionListenerInterface::class ],
			'the_title',
			PHP_INT_MAX - 10
		];

		$data[ 'filter_with_default_priority' ] = [
			[ FilterListenerInterface::class ],
			'the_title',
			PHP_INT_MAX - 10
		];

		return $data;
	}
}

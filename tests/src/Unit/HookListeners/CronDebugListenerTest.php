<?php # -*- coding: utf-8 -*-
/*
 * This file is part of the Inpsyde wonolog package.
 *
 * (c) Inpsyde GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Inpsyde\Wonolog\Tests\Unit\HookListener;

use Brain\Monkey\Functions;
use Brain\Monkey\WP\Filters;
use Brain\Monkey\WP\Actions;
use Inpsyde\Wonolog\Data\Debug;
use Inpsyde\Wonolog\Tests\TestCase;
use Inpsyde\Wonolog\HookListeners\CronDebugListener;

/**
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @package wonolog
 * @license http://opensource.org/licenses/MIT MIT
 */
class CronDebugListenerTest extends TestCase {

	/**
	 * @runInSeparateProcess
	 */
	public function test_log_done() {

		$this->markTestSkipped( 'Under construction due to refactoring…' );
		define( 'DOING_CRON', TRUE );

		Functions::when( '_get_cron_array' )
			->justReturn(
				[
					[ 'action_1' => 'do_something' ],
					[ 'action_2' => 'do_something_else' ],
				]
			);

		Actions::expectAdded( 'action_1' )
			->twice()
			->whenHappen(
				function ( callable $callback ) {

					$callback();
				}
			);

		Actions::expectAdded( 'action_2' )
			->twice()
			->whenHappen(
				function ( callable $callback ) {

					$callback();
				}
			);

		Actions::expectFired( 'wonolog.log' )
			->with( Debug::class )
			->whenHappen(
				function ( Debug $debug ) {

					$context = $debug->context();

					self::assertInternalType( 'array', $context );
					self::assertArrayHasKey( 'start', $context );
					self::assertArrayHasKey( 'duration', $context );
				}
			);

		$listener = new CronDebugListener();

		Filters::expectApplied( 'pre_transient_doing_cron' )
			->once()
			->andReturnUsing(
				function () use ( $listener ) {

					return $listener->filter( func_get_args() );
				}
			);

		self::assertFalse( apply_filters( $listener->listen_to(), FALSE ) );
	}
}

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

use Inpsyde\Wonolog\Channels;
use Brain\Monkey\Functions;
use Brain\Monkey\WP\Actions;
use Inpsyde\Wonolog\Data\Debug;
use Inpsyde\Wonolog\Data\NullLog;
use Inpsyde\Wonolog\Tests\TestCase;
use Inpsyde\Wonolog\HookListeners\CronDebugListener;

/**
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @package wonolog
 * @license http://opensource.org/licenses/MIT MIT
 */
class CronDebugListenerTest extends TestCase {

	/**
	 * @see CronDebugListener::listen_to()
	 */
	public function test_listen_to() {

		$this->assertSame(
			'wp_loaded',
			( new CronDebugListener() )->listen_to()
		);
	}

	/**
	 * @see CronDebugListener::update()
	 */
	public function test_update() {

		$this->assertInstanceOf(
			NullLog::class,
			( new CronDebugListener() )->update( [] )
		);
	}

	/**
	 * @runInSeparateProcess
	 * @dataProvider update_registers_listeners
	 * @see CronDebugListener::update()
	 *
	 * @param bool $is_cron
	 * @param bool $is_cli
	 */
	public function test_update_registers_listeners( $is_cron, $is_cli ) {

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
					self::assertSame(
						Channels::DEBUG,
						$debug->channel()
					);
				}
			);

		$listener = new CronDebugListener( $is_cli, $is_cron );

		$this->assertInstanceOf(
			NullLog::class,
			$listener->update( [] )
		);
	}

	/**
	 * @see test_update_registers_listeners
	 * @return array
	 */
	public function update_registers_listeners() {

		return [
			'is_cron' => [
				TRUE,
				NULL
			],
			'is_cli' => [
				NULL,
				TRUE
			],
			'is_cron_and_cli' => [
				TRUE,
				TRUE
			]
		];
	}

	/**
	 * @runInSeparateProcess
	 * @see CronDebugListener::__construct()
	 */
	public function test_constructor_reads_wp_cli() {

		define( 'WP_CLI', TRUE );
		$testee = new CronDebugListener();
		$is_cli = (new \ReflectionClass( $testee ) )->getProperty( 'is_cli' );
		$is_cli->setAccessible( TRUE );

		$this->assertTrue(
			$is_cli->getValue( $testee )
		);
	}

	/**
	 * @runInSeparateProcess
	 * @see CronDebugListener::__construct()
	 */
	public function test_constructor_reads_doing_cron() {

		define( 'DOING_CRON', TRUE );
		$testee = new CronDebugListener();
		$is_cron = ( new \ReflectionClass( $testee ) )->getProperty( 'is_cron' );
		$is_cron->setAccessible( TRUE );

		$this->assertTrue(
			$is_cron->getValue( $testee )
		);
	}
}

<?php # -*- coding: utf-8 -*-

namespace Inpsyde\Wonolog\Tests\Unit\HookListener;

use Inpsyde\Wonolog\Channels;
use Brain\Monkey\Actions;
use Brain\Monkey\Functions;
use Inpsyde\Wonolog\Data\Info;
use Inpsyde\Wonolog\Data\NullLog;
use Inpsyde\Wonolog\Tests\TestCase;
use Inpsyde\Wonolog\HookListener\CronDebugListener;

/**
 * @package wonolog\tests
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
	 * @see          CronDebugListener::update()
	 *
	 * @param int $flags
	 */
	public function test_update_registers_listeners( $flags ) {

		Functions\when( '_get_cron_array' )
			->justReturn(
				[
					[ 'action_1' => 'do_something' ],
					[ 'action_2' => 'do_something_else' ],
				]
			);

		Actions\expectAdded( 'action_1' )
			->twice()
			->whenHappen(
				function ( callable $callback ) {
					defined('DOING_CRON') or define('DOING_CRON', 1);
					$callback();
				}
			);

		Actions\expectAdded( 'action_2' )
			->twice()
			->whenHappen(
				function ( callable $callback ) {
					defined('DOING_CRON') or define('DOING_CRON', 1);
					$callback();
				}
			);

		Actions\expectDone( \Inpsyde\Wonolog\LOG )
			->with( Info::class )
			->once()
			->whenHappen(
				function ( Info $info ) {

					$context = $info->context();

					self::assertInternalType( 'array', $context );
					self::assertArrayHasKey( 'start', $context );
					self::assertArrayHasKey( 'duration', $context );
					self::assertSame( Channels::DEBUG, $info->channel() );
				}
			);

		$listener = new CronDebugListener( $flags );

		$this->assertInstanceOf( NullLog::class, $listener->update( [] ) );
	}

	/**
	 * @see test_update_registers_listeners
	 * @return array
	 */
	public function update_registers_listeners() {

		return [
			'is_cron'         => [ CronDebugListener::IS_CRON ],
			'is_cli'          => [ CronDebugListener::IS_CLI ],
			'is_cron_and_cli' => [ CronDebugListener::IS_CLI | CronDebugListener::IS_CRON ]
		];
	}

	/**
	 * @runInSeparateProcess
	 * @see CronDebugListener::__construct()
	 */
	public function test_constructor_reads_wp_cli() {

		define( 'WP_CLI', TRUE );
		$listener = new CronDebugListener();
		$this->assertTrue( $listener->is_cli() );
	}

	/**
	 * @runInSeparateProcess
	 * @see CronDebugListener::__construct()
	 */
	public function test_constructor_reads_doing_cron() {

		define( 'DOING_CRON', TRUE );
		$listener  = new CronDebugListener();

		$this->assertTrue( $listener->is_cron() );
	}
}

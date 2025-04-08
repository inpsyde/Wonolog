<?php # -*- coding: utf-8 -*-

namespace Inpsyde\Wonolog\Tests\Unit\HookListener;

use Brain\Monkey\Actions;
use Inpsyde\Wonolog\Channels;
use Inpsyde\Wonolog\Data\Error;
use Inpsyde\Wonolog\HookListener\WpDieHandlerListener;
use Inpsyde\Wonolog\Tests\TestCase;

/**
 * @package wonolog\tests
 */
class WpDieHandlerListenerTest extends TestCase {

	public function test_log_done_on_bail() {

		Actions\expectDone( \Inpsyde\Wonolog\LOG )
			->once()
			->with( \Mockery::type( Error::class ) )
			->whenHappen(
				function ( Error $log ) {

					self::assertSame( Channels::DB, $log->channel() );
					self::assertSame( $log->message(), 'Bailed!' );
				}
			);

		/** @noinspection PhpIncludeInspection */
		require_once getenv( 'TESTS_PATH' ) . '/stubs/wpdb.php';

		$wpdb = new \wpdb( 'user', 'password', 'db', 'host' );
		/** @noinspection PhpUndefinedFieldInspection */
		$wpdb->wp_die_listener = new WpDieHandlerListener();

		self::assertSame( 'Handled: Bailed!', $wpdb->bail( 'Bailed!' ) );
	}

	public function test_log_done_on_print_error() {

		Actions\expectDone( \Inpsyde\Wonolog\LOG )
			->once()
			->with( \Mockery::type( Error::class ) )
			->whenHappen(
				function ( Error $log ) {

					self::assertSame( Channels::DB, $log->channel() );
					self::assertSame( $log->message(), 'Error!' );
				}
			);

		/** @noinspection PhpIncludeInspection */
		require_once getenv( 'TESTS_PATH' ) . '/stubs/wpdb.php';

		$wpdb = new \wpdb( 'user', 'password', 'db', 'host' );
		/** @noinspection PhpUndefinedFieldInspection */
		$wpdb->wp_die_listener = new WpDieHandlerListener();

		self::assertSame( 'Handled: Error!', $wpdb->print_error( 'Error!' ) );
	}
}

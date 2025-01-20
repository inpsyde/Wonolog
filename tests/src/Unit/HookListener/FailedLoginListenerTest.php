<?php # -*- coding: utf-8 -*-

namespace Inpsyde\Wonolog\Tests\Unit\HookListener;

use Brain\Monkey\Actions;
use Brain\Monkey\Functions;
use Inpsyde\Wonolog\Data\FailedLogin;
use Inpsyde\Wonolog\HookListener\FailedLoginListener;
use Inpsyde\Wonolog\Tests\TestCase;

/**
 * @package wonolog\tests
 */
class FailedLoginListenerTest extends TestCase {

	public function test_log_done() {

		Functions\when( 'get_site_transient' )
			->justReturn( [ '127.0.0.1' => [ 'count' => 2, 'last_logged' => 0, ] ] );

		Functions\when( 'set_site_transient' )
			->justReturn( FALSE );

		$listener = new FailedLoginListener();

		Actions\expectDone( 'wp_login_failed' )
			->once()
			->whenHappen(
				function () use ( $listener ) {

					$log = $listener->update( func_get_args() );
					self::assertInstanceOf( FailedLogin::class, $log );
				}
			);

		do_action( $listener->listen_to() );
	}

	public function test_log_not_done_if_no_level() {

		Functions\when( 'get_site_transient' )
			->justReturn( [ '127.0.0.1' => [ 'count' => 5, 'last_logged' => 3, ] ] );

		Functions\when( 'set_site_transient' )
			->justReturn( FALSE );

		Actions\expectDone( \Inpsyde\Wonolog\LOG )
			->never();

		$listener = new FailedLoginListener();

		Actions\expectDone( 'wp_login_failed' )
			->once()
			->whenHappen(
				function () use ( $listener ) {

					$log = $listener->update( func_get_args() );
					self::assertInstanceOf( FailedLogin::class, $log );
					self::assertSame( 0, $log->level() );
				}
			);

		do_action( $listener->listen_to() );
	}
}

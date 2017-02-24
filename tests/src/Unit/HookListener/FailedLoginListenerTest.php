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
use Inpsyde\Wonolog\Data\FailedLogin;
use Inpsyde\Wonolog\HookListener\FailedLoginListener;
use Inpsyde\Wonolog\Tests\TestCase;
use Brain\Monkey\WP\Actions;

/**
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @package wonolog
 * @license http://opensource.org/licenses/MIT MIT
 */
class FailedLoginListenerTest extends TestCase {

	public function test_log_done() {

		Functions::when( 'get_site_transient' )
			->justReturn( [ '127.0.0.1' => [ 'count' => 2, 'last_logged' => 0, ] ] );

		Functions::when( 'set_site_transient' )
			->justReturn( FALSE );

		$listener = new FailedLoginListener();

		Actions::expectFired( 'wp_login_failed' )
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

		Functions::when( 'get_site_transient' )
			->justReturn( [ '127.0.0.1' => [ 'count' => 5, 'last_logged' => 3, ] ] );

		Functions::when( 'set_site_transient' )
			->justReturn( FALSE );

		Actions::expectFired( \Inpsyde\Wonolog\LOG )
			->never();

		$listener = new FailedLoginListener();

		Actions::expectFired( 'wp_login_failed' )
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

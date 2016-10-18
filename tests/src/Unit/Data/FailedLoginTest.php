<?php # -*- coding: utf-8 -*-
/*
 * This file is part of the Inpsyde wonolog package.
 *
 * (c) Inpsyde GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Inpsyde\Wonolog\Tests\Unit\Data;

use Inpsyde\Wonolog\Channels;
use Inpsyde\Wonolog\Tests\TestCase;
use Inpsyde\Wonolog\Data\FailedLogin;
use Brain\Monkey\Functions;
use Monolog\Logger;

/**
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @package wonolog
 * @license http://opensource.org/licenses/MIT MIT
 */
class FailedLoginTest extends TestCase {

	public function test_data() {

		$transient = FALSE;

		$callback = function ( $name, $value = NULL ) use ( &$transient ) {

			if ( is_null( $value ) ) {
				return $transient;
			}

			$transient = $value;

			return TRUE;
		};

		Functions::when( 'get_site_transient' )
			->alias( $callback );
		Functions::when( 'set_site_transient' )
			->alias( $callback );

		$failed_login = new FailedLogin( 'h4ck3rb0y' );

		$logged = $messages = [];

		// Let's brute force!
		for ( $i = 1; $i < 1600; $i ++ ) {
			$level = $failed_login->level();
			if ( $level ) {
				$logged[ $i ] = $level;
				$messages[]   = $failed_login->message();
				$context      = $failed_login->context();
				self::assertArrayHasKey( 'ip', $context );
				self::assertArrayHasKey( 'ip_from', $context );
				self::assertArrayHasKey( 'username', $context );
				self::assertSame( 'h4ck3rb0y', $context[ 'username' ] );
				self::assertSame( Channels::SECURITY, $failed_login->channel() );
			}
		}

		$expected_logged_levels = [
			3    => Logger::NOTICE,
			23   => Logger::NOTICE,
			43   => Logger::NOTICE,
			63   => Logger::NOTICE,
			83   => Logger::NOTICE,
			183  => Logger::WARNING,
			283  => Logger::WARNING,
			383  => Logger::WARNING,
			483  => Logger::WARNING,
			583  => Logger::WARNING,
			683  => Logger::ERROR,
			783  => Logger::ERROR,
			883  => Logger::ERROR,
			983  => Logger::ERROR,
			1183 => Logger::CRITICAL,
			1383 => Logger::CRITICAL,
			1583 => Logger::CRITICAL,
		];

		self::assertSame( $expected_logged_levels, $logged );

		$format = "%d failed login attempts from username 'h4ck3rb0y' in last 5 minutes";

		foreach ( array_keys( $expected_logged_levels ) as $i => $n ) {
			self::assertSame( $messages[ $i ], sprintf( $format, $n ) );
		}
	}
}

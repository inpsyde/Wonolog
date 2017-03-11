<?php # -*- coding: utf-8 -*-
/*
 * This file is part of the Inpsyde Wonolog package.
 *
 * (c) Inpsyde GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Inpsyde\Wonolog\Tests\Unit\HookListener;

use Inpsyde\Wonolog\Channels;
use Inpsyde\Wonolog\Data\Error;
use Inpsyde\Wonolog\Data\NullLog;
use Inpsyde\Wonolog\Tests\TestCase;
use Inpsyde\Wonolog\HookListener\DbErrorListener;
use Brain\Monkey\WP\Actions;

/**
 * @package wonolog\tests
 * @license http://opensource.org/licenses/MIT MIT
 */
class DbErrorListenerTest extends TestCase {

	protected function tearDown() {

		parent::tearDown();
		$GLOBALS[ 'EZSQL_ERROR' ] = NULL;
		unset( $GLOBALS[ 'EZSQL_ERROR' ] );
	}

	public function test_log_done() {

		global $EZSQL_ERROR;
		$EZSQL_ERROR = [ [ 'query' => 'This is a SQL query', 'error_str' => 'This is an error' ] ];

		$tester = function ( Error $log ) use ( $EZSQL_ERROR ) {

			$context = [ 'last_query' => 'This is a SQL query', 'errors' => $EZSQL_ERROR, ];

			self::assertSame( Channels::DB, $log->channel() );
			self::assertSame( 'This is an error', $log->message() );
			self::assertEquals( $context, $log->context() );
		};

		$listener = new DbErrorListener();

		Actions::expectFired( 'shutdown' )
			->once()
			->whenHappen(
				function () use ( $listener, $tester ) {

					$tester( $listener->update( func_get_args() ) );
				}
			);

		do_action( $listener->listen_to() );
	}

	public function test_log_not_done_if_no_error() {

		global $EZSQL_ERROR;
		$EZSQL_ERROR = [];

		$listener = new DbErrorListener();

		self::assertInstanceOf( NullLog::class, $listener->update( [] ) );
	}
}

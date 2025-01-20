<?php # -*- coding: utf-8 -*-

namespace Inpsyde\Wonolog\Tests\Unit\HookListener;

use Inpsyde\Wonolog\Channels;
use Inpsyde\Wonolog\Data\Debug;
use Inpsyde\Wonolog\Data\NullLog;
use Inpsyde\Wonolog\Tests\TestCase;
use Inpsyde\Wonolog\HookListener\QueryErrorsListener;
use Brain\Monkey\Actions;
use Brain\Monkey\Functions;

/**
 * @package wonolog\tests
 */
class QueryErrorsListenerTest extends TestCase {

	public function test_log_done() {

		Functions\when( 'is_404' )
			->justReturn( TRUE );

		Functions\when( 'add_query_arg' )
			->justReturn( '/meh' );

		$tester = function ( Debug $log ) {

			self::assertSame( Channels::HTTP, $log->channel() );
			self::assertSame( 'Error on frontend request for url /meh.', $log->message() );
			self::assertSame(
				[
					'error'        => [ '404 Page not found' ],
					'query_vars'   => [ 'foo' => 'bar' ],
					'matched_rule' => '/.+/'
				],
				$log->context()

			);
		};

		/** @var \WP $wp */
		$wp               = \Mockery::mock( 'WP' );
		$wp->query_vars   = [ 'foo' => 'bar' ];
		$wp->matched_rule = '/.+/';

		$listener = new QueryErrorsListener();

		Actions\expectDone( 'wp' )
			->whenHappen(
				function () use ( $listener, $tester ) {

					$tester( $listener->update( func_get_args() ) );
				}
			);

		do_action( $listener->listen_to(), $wp );
	}

	public function test_log_not_done_if_wrong_arg() {

		Functions\when( 'is_404' )
			->justReturn( TRUE );

		Functions\when( 'add_query_arg' )
			->justReturn( '/meh' );

		$wp               = new \stdClass();
		$wp->query_vars   = [ 'foo' => 'bar' ];
		$wp->matched_rule = '/.+/';

		$listener = new QueryErrorsListener();

		Actions\expectDone( 'wp' )
			->whenHappen(
				function () use ( $listener ) {

					$log = $listener->update( func_get_args() );
					self::assertInstanceOf( NullLog::class, $log );
				}
			);

		do_action( $listener->listen_to(), $wp );
	}
}

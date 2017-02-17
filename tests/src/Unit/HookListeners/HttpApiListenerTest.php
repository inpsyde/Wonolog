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
use Inpsyde\Wonolog\Data\LogDataInterface;
use Inpsyde\Wonolog\Data\NullLog;
use Inpsyde\Wonolog\Tests\TestCase;
use Inpsyde\Wonolog\HookListeners\HttpApiListener;
use Brain\Monkey\Functions;
use Brain\Monkey\WP\Actions;
use Monolog\Logger;

/**
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @package wonolog
 * @license http://opensource.org/licenses/MIT MIT
 */
class HttpApiListenerTest extends TestCase {

	public function test_log_done_on_wp_error() {

		Functions::when( 'is_wp_error' )
			->justReturn( TRUE );

		Actions::expectFired( \Inpsyde\Wonolog\LOG )
			->with( \Mockery::type( LogDataInterface::class ) )
			->whenHappen(
				function ( LogDataInterface $log ) {

					self::assertSame( 'WP HTTP API Error, Test!', $log->message() );
					self::assertSame( Channels::HTTP, $log->channel() );
					self::assertSame( Logger::ERROR, $log->level() );
					self::assertSame(
						[
							'transport'  => 'TestClass',
							'context'    => 'response',
							'query_args' => [],
							'url'        => 'http://example.com',
						],
						$log->context()
					);
				}
			);

		/** @var \WP_Error|\Mockery\MockInterface $response */
		$response = \Mockery::mock( 'WP_Error' );
		$response
			->shouldReceive( 'get_error_message' )
			->once()
			->andReturn( 'Test!' );

		$listener = new HttpApiListener();

		Actions::expectFired( 'http_api_debug' )
			->whenHappen(
				function () use ( $listener ) {

					$listener->update( func_get_args() );
				}
			);

		do_action( $listener->listen_to(), $response, 'response', 'TestClass', [], 'http://example.com' );
	}

	public function test_log_done_on_bad_response() {

		Functions::when( 'is_wp_error' )
			->justReturn( FALSE );

		Functions::when( 'shortcode_atts' )
			->alias( 'array_merge' );

		$tester = function ( LogDataInterface $log ) {

			self::assertSame( Channels::HTTP, $log->channel() );
			self::assertSame( Logger::ERROR, $log->level() );
			self::assertSame( 'WP HTTP API Error: Internal Server Error - Response code: 500.', $log->message() );
			self::assertSame(
				[
					'transport'     => 'TestClass',
					'context'       => 'response',
					'query_args'    => [],
					'url'           => 'http://example.com',
					'response_body' => 'Server died.',
					'headers'       => [ 'foo' => 'bar' ],
				],
				$log->context()

			);
		};

		$listener = new HttpApiListener();

		Actions::expectFired( 'http_api_debug' )
			->whenHappen(
				function () use ( $listener, $tester ) {

					$tester( $listener->update( func_get_args() ) );
				}
			);

		$response = [
			'response' => [ 'code' => 500, 'message' => 'Internal Server Error', 'body' => 'Server died.' ],
			'headers'  => [ 'foo' => 'bar' ]
		];

		do_action( $listener->listen_to(), $response, 'response', 'TestClass', [], 'http://example.com' );
	}

	public function test_log_not_done_on_good_response() {

		Functions::when( 'is_wp_error' )
			->justReturn( FALSE );

		$listener = new HttpApiListener();

		Actions::expectFired( 'http_api_debug' )
			->whenHappen(
				function () use ( $listener ) {

					$log = $listener->update( func_get_args() );
					self::assertInstanceOf( NullLog::class, $log );
				}
			);

		$response = [
			'response' => [ 'code' => 200, 'message' => 'OK' ],
			'headers'  => [ 'foo' => 'bar' ]
		];

		do_action( $listener->listen_to(), $response, 'response', 'TestClass', [], 'http://example.com' );
	}

	public function test_log_cron() {

		Functions::when( 'is_wp_error' )
			->justReturn( FALSE );

		$tester = function ( LogDataInterface $log ) {

			self::assertSame( 'Cron request', $log->message() );
			self::assertSame( Channels::DEBUG, $log->channel() );
			self::assertSame( Logger::DEBUG, $log->level() );
			self::assertSame(
				[
					'transport'  => 'TestClass',
					'context'    => 'response',
					'query_args' => [],
					'url'        => 'http://example.com/wp-cron.php',
					'headers'    => [ 'foo' => 'bar' ]
				],
				$log->context()
			);
		};

		/** @var \WP_Error|\Mockery\MockInterface $response */
		$response = [
			'response' => [ 'code' => 200, 'message' => 'Ok' ],
			'headers'  => [ 'foo' => 'bar' ]
		];

		$listener = new HttpApiListener();

		Actions::expectFired( 'http_api_debug' )
			->whenHappen(
				function () use ( $listener, $tester ) {

					$tester( $listener->update( func_get_args() ) );
				}
			);

		do_action( $listener->listen_to(), $response, 'response', 'TestClass', [], 'http://example.com/wp-cron.php' );
	}
}

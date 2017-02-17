<?php # -*- coding: utf-8 -*-
/*
 * This file is part of the Inpsyde wonolog package.
 *
 * (c) Giuseppe Mazzapica
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Data;

use Brain\Monkey\WP\Filters;
use Inpsyde\Wonolog\Channels;
use Inpsyde\Wonolog\Data\Log;
use Inpsyde\Wonolog\Tests\TestCase;
use Monolog\Logger;

/**
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @package wonolog
 * @license http://opensource.org/licenses/MIT MIT
 */
class LogTest extends TestCase {

	public function test_basic_properties() {

		$log = new Log( 'message', Logger::EMERGENCY, Channels::DEBUG, [ 'foo' ] );

		self::assertSame( Channels::DEBUG, $log->channel() );
		self::assertSame( 'message', $log->message() );
		self::assertSame( [ 'foo' ], $log->context() );
		self::assertSame( Logger::EMERGENCY, $log->level() );

	}

	public function test_from_keyed_array() {

		$log = Log::from_array(
			[
				'message' => 'message',
				'level'   => Logger::EMERGENCY,
				'channel' => Channels::HTTP,
				'context' => [ 'foo' ]
			]
		);

		self::assertSame( Channels::HTTP, $log->channel() );
		self::assertSame( 'message', $log->message() );
		self::assertSame( [ 'foo' ], $log->context() );
		self::assertSame( Logger::EMERGENCY, $log->level() );

	}

	public function test_from_keyed_array_merged() {

		$log = Log::from_array(
			[
				'MESSAGE' => 'message',
				'ConTexT' => [ 'foo' ]
			]
		);

		self::assertSame( Channels::DEBUG, $log->channel() );
		self::assertSame( 'message', $log->message() );
		self::assertSame( [ 'foo' ], $log->context() );
		self::assertSame( Logger::DEBUG, $log->level() );

	}

	public function test_from_numeric_array() {

		$log = Log::from_array(
			[
				'Hi!',
				Logger::ALERT,
				Channels::HTTP,
				[ 'foo' => 'bar' ],
			]
		);

		self::assertSame( Channels::HTTP, $log->channel() );
		self::assertSame( 'Hi!', $log->message() );
		self::assertSame( [ 'foo' => 'bar' ], $log->context() );
		self::assertSame( Logger::ALERT, $log->level() );
	}

	public function test_from_numeric_array_wrong_order() {

		Filters::expectApplied( Channels::FILTER_CHANNELS )
			->andReturnUsing(
				function ( array $channels ) {

					$channels[] = Channels::PHP_ERROR;

					return $channels;
				}
			);

		$log = Log::from_array(
			[
				[ 'foo' ],
				'critical',
				'message',
				'meh',
				Channels::PHP_ERROR,
			]
		);

		self::assertSame( Channels::PHP_ERROR, $log->channel() );
		self::assertSame( 'message', $log->message() );
		self::assertSame( [ 'foo' ], $log->context() );
		self::assertSame( Logger::CRITICAL, $log->level() );
	}

	public function test_from_numeric_array_with_object() {

		$thing = \Mockery::mock();
		$thing->shouldReceive( 'to_array' )
			->andReturn(
				[ 'foo' => 'bar' ]
			);

		$log = Log::from_array(
			[
				$thing,
				Channels::HTTP
			]
		);

		self::assertSame( Channels::HTTP, $log->channel() );
		self::assertContains( get_class( $thing ), $log->message() );
		self::assertSame( [ 'foo' => 'bar' ], $log->context() );
		self::assertSame( Logger::DEBUG, $log->level() );
	}

}
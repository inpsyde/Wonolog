<?php # -*- coding: utf-8 -*-
/*
 * This file is part of the Inpsyde Wonolog package.
 *
 * (c) Inpsyde GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Inpsyde\Wonolog\Tests\Unit\Data;

use Inpsyde\Wonolog\Channels;
use Inpsyde\Wonolog\Data\Log;
use Inpsyde\Wonolog\Tests\TestCase;
use Monolog\Logger;

/**
 * @package wonolog\tests
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

	public function test_from_wp_error() {

		$error = \Mockery::mock( \WP_Error::class );

		$error->shouldReceive('get_error_message')->andReturn('Error!');
		$error->shouldReceive('get_error_data')->andReturn(['!']);
		$error->shouldReceive('get_error_codes')->andReturn(['x']);

		$log = Log::from_wp_error( $error );

		self::assertSame( Channels::DEBUG, $log->channel() );
		self::assertSame( 'Error!', $log->message() );
		self::assertSame( [ '!' ], $log->context() );
		self::assertSame( Logger::NOTICE, $log->level() );
	}

	public function test_from_wp_error_with_explicit_level() {

		$error = \Mockery::mock( \WP_Error::class );

		$error->shouldReceive('get_error_message')->andReturn('Error!');
		$error->shouldReceive('get_error_data')->andReturn(['!']);
		$error->shouldReceive('get_error_codes')->andReturn(['x']);

		$log = Log::from_wp_error( $error, Logger::DEBUG );

		self::assertSame( Channels::DEBUG, $log->channel() );
		self::assertSame( 'Error!', $log->message() );
		self::assertSame( [ '!' ], $log->context() );
		self::assertSame( Logger::DEBUG, $log->level() );
	}

	public function test_from_wp_error_with_explicit_level_and_channel() {

		$error = \Mockery::mock( \WP_Error::class );

		$error->shouldReceive('get_error_message')->andReturn('Error!');
		$error->shouldReceive('get_error_data')->andReturn(['!']);
		$error->shouldReceive('get_error_codes')->andReturn(['x']);

		$log = Log::from_wp_error( $error, Logger::DEBUG, Channels::DB );

		self::assertSame( Channels::DB, $log->channel() );
		self::assertSame( 'Error!', $log->message() );
		self::assertSame( [ '!' ], $log->context() );
		self::assertSame( Logger::DEBUG, $log->level() );
	}

	public function test_from_throwable() {

		$exception = new \Exception('Fail!, Fail!', 123);

		$log = Log::from_throwable( $exception );
		self::assertInstanceof( Log::class, $log );

		$context = $log->context();
		self::assertInternalType( 'array', $context );

		self::assertSame( Channels::DEBUG, $log->channel() );
		self::assertSame( 'Fail!, Fail!', $log->message() );
		self::assertSame( Logger::ERROR, $log->level() );
		self::assertArrayHasKey( 'throwable', $context );
		self::assertSame( $context['throwable']['class'], \Exception::class );
		self::assertSame( $context['throwable']['file'], __FILE__ );
		self::assertArrayHasKey( 'line', $context['throwable'] );
		self::assertArrayHasKey( 'trace', $context['throwable'] );
	}

	public function test_from_throwable_with_explicit_level() {

		$exception = new \Exception('Fail!, Fail!', 123);

		$log = Log::from_throwable( $exception, Logger::DEBUG );
		self::assertInstanceof( Log::class, $log );

		$context = $log->context();
		self::assertInternalType( 'array', $context );

		self::assertSame( Channels::DEBUG, $log->channel() );
		self::assertSame( 'Fail!, Fail!', $log->message() );
		self::assertSame( Logger::DEBUG, $log->level() );
		self::assertArrayHasKey( 'throwable', $context );
		self::assertSame( $context['throwable']['class'], \Exception::class );
		self::assertSame( $context['throwable']['file'], __FILE__ );
		self::assertArrayHasKey( 'line', $context['throwable'] );
		self::assertArrayHasKey( 'trace', $context['throwable'] );
	}

	public function test_from_throwable_with_explicit_level_and_channel() {

		$exception = new \Exception('Fail!, Fail!', 123);

		$log = Log::from_throwable( $exception, Logger::NOTICE, Channels::HTTP );
		self::assertInstanceof( Log::class, $log );

		$context = $log->context();
		self::assertInternalType( 'array', $context );

		self::assertSame( Channels::HTTP, $log->channel() );
		self::assertSame( 'Fail!, Fail!', $log->message() );
		self::assertSame( Logger::NOTICE, $log->level() );
		self::assertArrayHasKey( 'throwable', $context );
		self::assertSame( $context['throwable']['class'], \Exception::class );
		self::assertSame( $context['throwable']['file'], __FILE__ );
		self::assertArrayHasKey( 'line', $context['throwable'] );
		self::assertArrayHasKey( 'trace', $context['throwable'] );
	}

	public function test_from_array() {

		$log = Log::from_array(
			[
				Log::MESSAGE => 'message',
				Log::LEVEL   => Logger::EMERGENCY,
				Log::CHANNEL => Channels::HTTP,
				Log::CONTEXT => [ 'foo' ]
			]
		);

		self::assertSame( Channels::HTTP, $log->channel() );
		self::assertSame( 'message', $log->message() );
		self::assertSame( [ 'foo' ], $log->context() );
		self::assertSame( Logger::EMERGENCY, $log->level() );

	}

	public function test_from_array_merged() {

		$log = Log::from_array(
			[
				Log::MESSAGE => 'message',
				Log::CONTEXT => [ 'foo' ]
			]
		);

		self::assertSame( Channels::DEBUG, $log->channel() );
		self::assertSame( 'message', $log->message() );
		self::assertSame( [ 'foo' ], $log->context() );
		self::assertSame( Logger::DEBUG, $log->level() );

	}

}
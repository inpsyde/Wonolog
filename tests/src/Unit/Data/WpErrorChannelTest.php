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
use Inpsyde\Wonolog\Data\WpErrorChannel;
use Inpsyde\Wonolog\Tests\TestCase;

/**
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @package wonolog
 * @license http://opensource.org/licenses/MIT MIT
 */
class WpErrorChannelTest extends TestCase {

	public function test_for_error() {

		$instance = WpErrorChannel::for_error( \Mockery::mock( 'WP_Error' ) );

		self::assertInstanceOf( WpErrorChannel::class, $instance );
	}

	public function test_channel_explicit() {

		$instance = WpErrorChannel::for_error( \Mockery::mock( 'WP_Error' ), 'FOO' );
		$channel  = $instance->channel();

		self::assertSame( 'FOO', $channel );
	}

	public function test_channel_guessed_db() {

		$error = \Mockery::mock( 'WP_Error' );
		$error
			->shouldReceive( 'get_error_codes' )
			->once()
			->andReturn( [ 'foo', 'bar', 'db_failed' ] );

		$instance = WpErrorChannel::for_error( $error );
		$channel  = $instance->channel();

		self::assertSame( Channels::DB, $channel );
	}

	public function test_channel_guessed_http() {

		$error = \Mockery::mock( 'WP_Error' );
		$error
			->shouldReceive( 'get_error_codes' )
			->once()
			->andReturn( [ 'foo', 'bar', 'rest_error' ] );

		$instance = WpErrorChannel::for_error( $error );
		$channel  = $instance->channel();

		self::assertSame( Channels::HTTP, $channel );
	}

	public function test_channel_guessed_security() {

		$error = \Mockery::mock( 'WP_Error' );
		$error
			->shouldReceive( 'get_error_codes' )
			->once()
			->andReturn( [ 'foo', 'authentication', 'rest_error' ] );

		$instance = WpErrorChannel::for_error( $error );
		$channel  = $instance->channel();

		self::assertSame( Channels::SECURITY, $channel );
	}

	public function test_channel_guessed_filtered() {

		Filters::expectApplied( WpErrorChannel::FILTER_CHANNEL )
			->once()
			->with( Channels::SECURITY, \Mockery::type( 'WP_Error' ) )
			->andReturn( 'BAR' );

		$error = \Mockery::mock( 'WP_Error' );
		$error
			->shouldReceive( 'get_error_codes' )
			->once()
			->andReturn( [ 'foo', 'authentication', 'rest_error' ] );

		$instance = WpErrorChannel::for_error( $error );
		$channel  = $instance->channel();

		self::assertSame( 'BAR', $channel );
	}
}
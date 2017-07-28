<?php # -*- coding: utf-8 -*-
/*
 * This file is part of the Wonolog package.
 *
 * (c) Inpsyde GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Inpsyde\Wonolog\Tests\Unit\Handler;

use Brain\Monkey\Functions;
use Inpsyde\Wonolog\Handler\DateBasedStreamHandler;
use Inpsyde\Wonolog\Tests\TestCase;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

/**
 * @package wonolog\tests
 * @license http://opensource.org/licenses/MIT MIT
 */
class DateBasedStreamHandlerTest extends TestCase {

	protected function setUp() {

		Functions\when( 'wp_normalize_path' )
			->alias(
				function ( $str ) {

					return str_replace( '\\', '/', $str );
				}
			);

		Functions\when( 'wp_mkdir_p' )
			->alias(
				function ( $str ) {

					return is_string( $str ) && filter_var( $str, FILTER_SANITIZE_URL )
						? $str
						: '';
				}
			);

		parent::setUp();
	}

	/**
	 * @expectedException \InvalidArgumentException
	 * @expectedExceptionMessageRegExp /file name or date format/
	 */
	public function test_constructor_fails_if_bad_file_format() {

		new DateBasedStreamHandler( 'foo', 'd/m/Y' );
	}

	/**
	 * @expectedException \InvalidArgumentException
	 * @expectedExceptionMessageRegExp /file name or date format/
	 */
	public function test_constructor_fails_if_bad_date_format() {

		new DateBasedStreamHandler( '{date}.log', 'xxx' );
	}

	/**
	 * @dataProvider data_provider_for_test_stream_handler_for_record
	 *
	 * @param mixed $datetime
	 * @param int   $timestamp
	 */
	public function test_stream_handler_for_record( $datetime, $timestamp ) {

		$handler = new DateBasedStreamHandler( '/etc/logs/{date}.log', 'd/m/Y' );

		$stream_handler = $handler->stream_handler_for_record( compact( 'datetime' ) );

		self::assertInstanceOf( StreamHandler::class, $stream_handler );
		self::assertSame( '/etc/logs/' . date( 'd/m/Y', $timestamp ) . '.log', $stream_handler->getUrl() );
	}

	public function test_stream_handler_for_record_with_callback() {

		$file_format = function ( array $record ) {

			if ( empty( $record[ 'channel' ] ) || ! is_string( $record[ 'channel' ] ) ) {
				return '/etc/logs/{date}.log';
			}

			return '/etc/logs/' . strtolower( $record[ 'channel' ] ) . '/{date}.log';
		};

		$handler = new DateBasedStreamHandler( $file_format, 'd/m/Y' );

		$timestamp = time();

		$record = [
			'message'  => 'Hello',
			'level'    => Logger::DEBUG,
			'channel'  => 'DEBUG',
			'datetime' => ( new \DateTime() )->setTimestamp( $timestamp )
		];

		$stream_handler = $handler->stream_handler_for_record( $record );

		self::assertInstanceOf( StreamHandler::class, $stream_handler );
		self::assertSame( '/etc/logs/debug/' . date( 'd/m/Y', $timestamp ) . '.log', $stream_handler->getUrl() );
	}

	/**
	 * @return array
	 * @see test_stream_handler_for_record
	 */
	public function data_provider_for_test_stream_handler_for_record() {

		$time     = time();
		$now      = new \DateTime( 'now' );
		$week_ago = new \DateTime();
		$week_ago->setTimestamp( strtotime( '1 week ago' ) );
		$last_year = new \DateTime();
		$last_year->setTimestamp( strtotime( '1 year ago' ) );

		return [
			[ (int) $time, $time ],
			[ (string) $time, $time ],
			[ 'yesterday', strtotime( 'yesterday' ) ],
			[ '2 weeks ago', strtotime( '2 weeks ago' ) ],
			[ $now, $now->getTimestamp() ],
			[ $now->format( 'Y-m-d H:i:s' ), $now->getTimestamp() ],
			[ $now->format( 'Y-m-d' ), $now->getTimestamp() ],
			[ $now->format( 'r' ), $now->getTimestamp() ],
			[ $week_ago, strtotime( '1 week ago' ) ],
			[ $week_ago->format( 'c' ), strtotime( '1 week ago' ) ],
			[ $last_year->format( 'c' ), $time ],
		];

	}
}

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

use Brain\Monkey\Filters;
use Brain\Monkey\Functions;
use Inpsyde\Wonolog\Handler\DefaultHandlerFactory;
use Inpsyde\Wonolog\Handler\DateBasedStreamHandler;
use Inpsyde\Wonolog\Tests\TestCase;
use Monolog\Handler\HandlerInterface;
use Monolog\Handler\NullHandler;

/**
 * @package wonolog\tests
 * @license http://opensource.org/licenses/MIT MIT
 */
class DefaultHandlerFactoryTest extends TestCase {

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

	protected function tearDown() {

		putenv( 'WONOLOG_DEFAULT_HANDLER_ROOT_DIR' );
		parent::tearDown();
	}

	public function test_enforced_instance_is_returned() {

		$handler = \Mockery::mock( HandlerInterface::class );
		$factory = DefaultHandlerFactory::withDefaultHandler( $handler );

		self::assertSame( $handler, $factory->createDefaultHandler() );
	}

	public function test_default_handler_is_null_if_no_folder() {

		$factory = DefaultHandlerFactory::withDefaultHandler();

		$handler = $factory->createDefaultHandler();

		self::assertInstanceOf( NullHandler::class, $handler );
	}

	public function test_default_handler_with_dir_from_env() {

		$dir = str_replace( '\\', '/', __DIR__ );
		putenv( 'WONOLOG_DEFAULT_HANDLER_ROOT_DIR=' . $dir );

		$factory = DefaultHandlerFactory::withDefaultHandler();

		/** @var DateBasedStreamHandler $handler */
		$handler = $factory->createDefaultHandler();

		self::assertInstanceOf( DateBasedStreamHandler::class, $handler );
	}

	/**
	 * @runInSeparateProcess
	 */
	public function test_default_handler_with_dir_from_constant() {

		$dir = str_replace( '\\', '/', __DIR__ );
		define( 'WP_CONTENT_DIR', $dir );

		$factory = DefaultHandlerFactory::withDefaultHandler();

		/** @var DateBasedStreamHandler $handler */
		$handler = $factory->createDefaultHandler();

		self::assertInstanceOf( DateBasedStreamHandler::class, $handler );
	}

	public function test_default_handler_is_null_if_invalid_file_format_from_hooks() {

		Filters\expectApplied( DefaultHandlerFactory::FILTER_FILENAME )
			->once()
			->andReturn( 'foo' );

		$dir = str_replace( '\\', '/', __DIR__ );
		putenv( 'WONOLOG_DEFAULT_HANDLER_ROOT_DIR=' . $dir );

		$factory = DefaultHandlerFactory::withDefaultHandler();

		$handler = $factory->createDefaultHandler();

		self::assertInstanceOf( NullHandler::class, $handler );
	}

	public function test_default_handler_is_null_if_invalid_date_format_from_hooks() {

		Filters\expectApplied( DefaultHandlerFactory::FILTER_DATE_FORMAT )
			->once()
			->andReturn( 'meeeee' );

		$dir = str_replace( '\\', '/', __DIR__ );
		putenv( 'WONOLOG_DEFAULT_HANDLER_ROOT_DIR=' . $dir );

		$factory = DefaultHandlerFactory::withDefaultHandler();

		$handler = $factory->createDefaultHandler();

		self::assertInstanceOf( NullHandler::class, $handler );
	}

	public function test_default_handler_custom_path_from_hooks() {

		Filters\expectApplied( DefaultHandlerFactory::FILTER_DATE_FORMAT )
			->once()
			->andReturn( 'mYd' );

		Filters\expectApplied( DefaultHandlerFactory::FILTER_FILENAME )
			->once()
			->andReturn( 'wonolog/{date}.text' );

		Filters\expectApplied( DefaultHandlerFactory::FILTER_FOLDER )
			->once()
			->andReturn( '/etc/logs/' );

		$factory = DefaultHandlerFactory::withDefaultHandler();

		/** @var DateBasedStreamHandler $handler */
		$handler = $factory->createDefaultHandler();

		self::assertInstanceOf( DateBasedStreamHandler::class, $handler );

		$stream_url = $handler->streamHandlerForRecord( [] )
			->getUrl();

		self::assertSame( '/etc/logs/wonolog/' . date( 'mYd' ) . '.text', $stream_url );
	}

}

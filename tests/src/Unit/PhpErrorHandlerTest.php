<?php # -*- coding: utf-8 -*-
/*
 * This file is part of the Wonolog package.
 *
 * (c) Inpsyde GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Inpsyde\Wonolog\Tests\Unit;

use Inpsyde\Wonolog\Channels;
use Inpsyde\Wonolog\Data\LogDataInterface;
use Inpsyde\Wonolog\PhpErrorController;
use Brain\Monkey\Actions;
use Inpsyde\Wonolog\Tests\TestCase;
use Mockery;
use Monolog\Logger;

/**
 * @package wonolog\tests
 * @license http://opensource.org/licenses/MIT MIT
 */
class PhpErrorHandlerTest extends TestCase {

	protected function tearDown() {

		restore_error_handler();
		restore_exception_handler();

		parent::tearDown();
	}

	public function test_on_error_notice() {

		Actions\expectDone( \Inpsyde\Wonolog\LOG )
			->once()
			->with( Mockery::type( LogDataInterface::class ) )
			->whenHappen(
				function ( LogDataInterface $log ) {

					self::assertSame( Channels::PHP_ERROR, $log->channel() );
					self::assertSame( Logger::NOTICE, $log->level() );
					self::assertSame( 'Meh!', $log->message() );
					$context = $log->context();
					self::assertArrayHasKey( 'line', $context );
					self::assertArrayHasKey( 'file', $context );
					self::assertSame( __FILE__, $context[ 'file' ] );
				}
			);

		$controller  = new PhpErrorController();
		$this->initialize_error_controller( $controller );

		@trigger_error( 'Meh!', E_USER_NOTICE );
	}

	public function test_on_error_fatal() {

		Actions\expectDone( \Inpsyde\Wonolog\LOG )
			->once()
			->with( Mockery::type( LogDataInterface::class ) )
			->whenHappen(
				function ( LogDataInterface $log ) {

					self::assertSame( Channels::PHP_ERROR, $log->channel() );
					self::assertSame( Logger::WARNING, $log->level() );
					self::assertSame( 'Warning!', $log->message() );
					$context = $log->context();
					self::assertArrayHasKey( 'line', $context );
					self::assertArrayHasKey( 'file', $context );
					self::assertSame( __FILE__, $context[ 'file' ] );
				}
			);

		$controller  = new PhpErrorController();
		$this->initialize_error_controller( $controller );

		@trigger_error( 'Warning!', E_USER_WARNING );

	}

	public function test_on_error_should_not_contain_globals() {
		Actions\expectDone( 'wonolog.log' )
			->once()
			->with( Mockery::type( LogDataInterface::class ) )
			->whenHappen(
				function ( LogDataInterface $log ) {
					self::assertSame( Channels::PHP_ERROR, $log->channel() );
					self::assertSame( Logger::WARNING, $log->level() );
					$context = $log->context();
					self::assertArrayHasKey( 'line', $context );
					self::assertArrayHasKey( 'file', $context );
					self::assertSame( __FILE__, $context[ 'file' ] );
					self::assertArrayHasKey( 'local_var', $context );
					self::assertSame( 'I am local', $context[ 'local_var' ] );
					self::assertArrayNotHasKey( 'wp_filter', $context );
				}
			);
		$controller  = new PhpErrorController();
		$this->initialize_error_controller( $controller );
		global $wp_filter;
		$wp_filter = [ 'foo', 'bar' ];
		$local_var = 'I am local';
		@call_user_func_array( 'meh', [] );
	}

	/**
	 * @expectedException \RuntimeException
	 */
	public function test_on_exception() {

		Actions\expectDone( \Inpsyde\Wonolog\LOG )
			->once()
			->with( Mockery::type( LogDataInterface::class ) )
			->whenHappen(
				function ( LogDataInterface $log ) {

					self::assertSame( Channels::PHP_ERROR, $log->channel() );
					self::assertSame( Logger::CRITICAL, $log->level() );
					self::assertSame( 'Exception!', $log->message() );
					$context = $log->context();
					self::assertArrayHasKey( 'line', $context );
					self::assertArrayHasKey( 'trace', $context );
					self::assertArrayHasKey( 'file', $context );
					self::assertArrayHasKey( 'exception', $context );
					self::assertSame( __FILE__, $context[ 'file' ] );
					self::assertSame( \RuntimeException::class, $context[ 'exception' ] );
				}
			);

		$controller  = new PhpErrorController();
		$this->initialize_error_controller( $controller );

		try {
			throw new \RuntimeException( 'Exception!' );
		}
		catch ( \Exception $e ) {
			$controller->on_exception( $e );
		}
	}

	private function initialize_error_controller( PhpErrorController $controller ) {
		register_shutdown_function( [ $controller, 'on_fatal', ] );
		set_error_handler( [ $controller, 'on_error', ] );
		set_exception_handler( [ $controller, 'on_exception', ] );
	}
}

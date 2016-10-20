<?php # -*- coding: utf-8 -*-
/*
 * This file is part of theInpsyde wonolog package.
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
use Brain\Monkey\WP\Actions;
use Inpsyde\Wonolog\Tests\TestCase;
use Mockery;
use Monolog\Logger;

/**
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @package wonolog
 * @license http://opensource.org/licenses/MIT MIT
 */
class PhpErrorHandlerTest extends TestCase {

	protected function tearDown() {

		restore_error_handler();
		restore_exception_handler();

		parent::tearDown();
	}

	public function test_on_error_notice() {

		Actions::expectFired( 'wonolog.log' )
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

		$handler = new PhpErrorController();
		$handler->init();

		@trigger_error( 'Meh!', E_USER_NOTICE );
	}

	public function test_on_error_fatal() {

		Actions::expectFired( 'wonolog.log' )
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

		$handler = new PhpErrorController();
		$handler->init();

		@trigger_error( 'Warning!', E_USER_WARNING );

	}

	/**
	 * @expectedException \RuntimeException
	 */
	public function test_on_exception() {

		Actions::expectFired( 'wonolog.log' )
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

		$handler = new PhpErrorController();
		$handler->init();

		try {
			throw new \RuntimeException( 'Exception!' );
		}
		catch ( \Exception $e ) {
			$handler->on_exception( $e );
		}
	}
}

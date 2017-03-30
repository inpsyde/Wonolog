<?php # -*- coding: utf-8 -*-
/*
 * This file is part of the Wonolog package.
 *
 * (c) Inpsyde GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Inpsyde\Wonolog\Tests\Functional;

use Inpsyde\Wonolog;
use Inpsyde\Wonolog\Channels;
use Inpsyde\Wonolog\Data\Error;
use Inpsyde\Wonolog\Tests\FunctionalTestCase;
use Monolog\Handler\TestHandler;

/**
 * @package wonolog\tests
 * @license http://opensource.org/licenses/MIT MIT
 *
 * @runTestsInSeparateProcesses
 */
class CustomHandlerLoggingTest extends FunctionalTestCase {

	public function test_log_custom_hook() {

		$handler = new TestHandler();
		$handler->pushProcessor(
			function ( array $record ) {

				$record[ 'message' ] = 'Handler Processor: ' . $record[ 'message' ];

				return $record;
			}
		);

		Wonolog\bootstrap( $handler, Wonolog\USE_DEFAULT_NONE )
			->use_processor(
				function ( array $record ) {

					$record[ 'message' ] = 'General Processor: ' . $record[ 'message' ];

					return $record;
				},
				[ Channels::DEBUG ]
			);

		do_action( Wonolog\LOG, new Error( 'Log via hook happened!', Channels::DB ) );
		do_action( Wonolog\LOG, 'Test this!' );

		self::assertTrue( $handler->hasError( 'Handler Processor: Log via hook happened!' ) );
		self::assertTrue( $handler->hasDebug( 'Handler Processor: General Processor: Test this!' ) );
	}

	public function test_log_error() {

		$handler = new TestHandler();

		Wonolog\bootstrap( $handler, Wonolog\LOG_PHP_ERRORS|Wonolog\USE_DEFAULT_PROCESSOR );

		@trigger_error( 'Catch me!!', E_USER_WARNING );
		@trigger_error( 'Catch me!!', E_USER_ERROR );

		$logs = $handler->getRecords();

		self::assertInternalType( 'array', $logs );
		self::assertCount( 2, $logs );
		self::assertArrayHasKey( 'extra', $logs[0]);
		self::assertArrayHasKey( 'wp', $logs[0]['extra']);
		self::assertArrayHasKey( 'doing_cron', $logs[0]['extra']['wp']);
		self::assertArrayHasKey( 'doing_ajax', $logs[0]['extra']['wp']);
		self::assertArrayHasKey( 'is_admin', $logs[0]['extra']['wp']);
		self::assertFalse( $logs[0]['extra']['wp']['doing_cron']);
		self::assertFalse( $logs[0]['extra']['wp']['doing_ajax']);
		self::assertFalse( $logs[0]['extra']['wp']['is_admin']);
	}
}

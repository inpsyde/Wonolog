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

use Brain\Monkey\Functions;
use Inpsyde\Wonolog\Channels;
use Inpsyde\Wonolog\Data\Log;
use Inpsyde\Wonolog\Data\LogDataInterface;
use Inpsyde\Wonolog\Exception\InvalidChannelNameException;
use Inpsyde\Wonolog\FrontController;
use Inpsyde\Wonolog\LogActionSubscriber;
use Inpsyde\Wonolog\Tests\TestCase;
use Monolog\Logger;

/**
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @package wonolog
 * @license http://opensource.org/licenses/MIT MIT
 */
class LogActionSubscriberTest extends TestCase {

	private $result;

	protected function setUp() {

		Functions::when( 'is_wp_error' )
			->alias(
				function ( $thing ) {

					return $thing instanceof \WP_Error;
				}
			);

		parent::setUp();
	}

	protected function tearDown() {

		unset( $this->result );
		parent::tearDown();
	}

	private function build_channels( $channel = Channels::DEBUG ) {

		$logger = \Mockery::mock( Logger::class );

		$logger->shouldReceive( 'getName' )
			->withNoArgs()
			->andReturn( $channel );

		$logger->shouldReceive( 'getName' )
			->withNoArgs()
			->andReturn( $channel );

		$logger->shouldReceive( 'addRecord' )
			->with( \Mockery::type( 'int' ), \Mockery::type( 'string' ), \Mockery::type( 'array' ) )
			->andReturnUsing(
				function ( $level, $message, $context ) {

					$this->result = compact( 'level', 'message', 'context' );

					return TRUE;
				}
			);

		$channels = \Mockery::mock( Channels::class );

		$channels->shouldReceive( 'logger' )
			->with( $channel )
			->andReturn( $logger );

		return $channels;
	}

	public function test_listen_do_nothing_if_not_loaded() {

		/** @var \Mockery\MockInterface|LogActionSubscriber $subscriber */
		$subscriber = \Mockery::mock( LogActionSubscriber::class )
			->makePartial();
		$subscriber->shouldNotReceive( 'update' );

		$subscriber->listen();

	}

	public function test_listen_log_unknown_error_if_no_args() {

		do_action( FrontController::ACTION_LOADED );

		$subscriber = new LogActionSubscriber( $this->build_channels() );

		$subscriber->listen();

		self::assertEquals(
			[ 'level' => Logger::DEBUG, 'message' => 'Unknown error.', 'context' => [] ],
			$this->result
		);

	}

	public function test_listen_unknown_with_level_from_hook() {

		do_action( FrontController::ACTION_LOADED );

		$subscriber = new LogActionSubscriber( $this->build_channels() );
		
		Functions::when( 'current_filter' )
			->justReturn( \Inpsyde\Wonolog\LOG . '.critical');

		$subscriber->listen();

		self::assertEquals(
			[ 'level' => Logger::CRITICAL, 'message' => 'Unknown error.', 'context' => [] ],
			$this->result
		);

	}

	public function test_listen_log_log_object() {

		do_action( FrontController::ACTION_LOADED );

		$log = new Log( 'Hi!', Logger::ALERT, 'FOO', [ 'foo' => 'bar' ] );

		$subscriber = new LogActionSubscriber( $this->build_channels( 'FOO' ) );

		$subscriber->listen( 1, 2, $log );

		self::assertEquals(
			[ 'level' => Logger::ALERT, 'message' => 'Hi!', 'context' => [ 'foo' => 'bar' ] ],
			$this->result
		);

	}

	public function test_listen_log_object_level_overridden_by_hook() {

		do_action( FrontController::ACTION_LOADED );

		$log = new Log( 'Hi!', Logger::ALERT, 'FOO', [ 'foo' => 'bar' ] );

		$subscriber = new LogActionSubscriber( $this->build_channels( 'FOO' ) );

		Functions::when( 'current_filter' )
			->justReturn( \Inpsyde\Wonolog\LOG . '.emergency');

		$subscriber->listen( 1, 2, $log );

		self::assertEquals(
			[ 'level' => Logger::EMERGENCY, 'message' => 'Hi!', 'context' => [ 'foo' => 'bar' ] ],
			$this->result
		);

	}

	public function test_listen_log_wp_error_no_level() {

		do_action( FrontController::ACTION_LOADED );

		$log = \Mockery::mock( 'WP_Error' );
		$log->shouldReceive( 'get_error_codes' )
			->andReturn( [ 'http_request_failed' ] );
		$log->shouldReceive( 'get_error_message' )
			->andReturn( 'A valid URL was not provided.' );
		$log->shouldReceive( 'get_error_data' )
			->andReturn( [] );

		$subscriber = new LogActionSubscriber( $this->build_channels( Channels::HTTP ) );

		$subscriber->listen( $log );

		self::assertEquals(
			[ 'level' => Logger::WARNING, 'message' => 'A valid URL was not provided.', 'context' => [] ],
			$this->result
		);

	}

	public function test_listen_log_wp_error_custom_level() {

		do_action( FrontController::ACTION_LOADED );

		$log = \Mockery::mock( 'WP_Error' );
		$log->shouldReceive( 'get_error_codes' )
			->andReturn( [ 'http_request_failed' ] );
		$log->shouldReceive( 'get_error_message' )
			->andReturn( 'A valid URL was not provided.' );
		$log->shouldReceive( 'get_error_data' )
			->andReturn( [] );

		$subscriber = new LogActionSubscriber( $this->build_channels( Channels::HTTP ) );

		$subscriber->listen( $log, Logger::EMERGENCY );

		self::assertEquals(
			[ 'level' => Logger::EMERGENCY, 'message' => 'A valid URL was not provided.', 'context' => [] ],
			$this->result
		);

	}

	public function test_listen_log_wp_error_custom_level_ignored_if_lower() {

		do_action( FrontController::ACTION_LOADED );

		$log = \Mockery::mock( 'WP_Error' );
		$log->shouldReceive( 'get_error_codes' )
			->andReturn( [ 'http_request_failed' ] );
		$log->shouldReceive( 'get_error_message' )
			->andReturn( 'A valid URL was not provided.' );
		$log->shouldReceive( 'get_error_data' )
			->andReturn( [] );

		$subscriber = new LogActionSubscriber( $this->build_channels( Channels::HTTP ) );

		$subscriber->listen( $log, Logger::DEBUG );

		self::assertEquals(
			[ 'level' => Logger::WARNING, 'message' => 'A valid URL was not provided.', 'context' => [] ],
			$this->result
		);

	}

	public function test_listen_log_array() {

		do_action( FrontController::ACTION_LOADED );

		$log = [
			'message' => 'Hi!',
			'level'   => Logger::ALERT,
			'channel' => 'Test_Channel',
			'context' => [ 'foo' => 'bar' ]
		];

		$subscriber = new LogActionSubscriber( $this->build_channels( 'Test_Channel' ) );

		$subscriber->listen( $log );

		self::assertEquals(
			[ 'level' => Logger::ALERT, 'message' => 'Hi!', 'context' => [ 'foo' => 'bar' ] ],
			$this->result
		);

	}

	public function test_update_nothing_if_not_loaded() {

		/** @var \Mockery\MockInterface|LogActionSubscriber $subscriber */
		$subscriber = \Mockery::mock( LogActionSubscriber::class )
			->makePartial();

		self::assertFalse( $subscriber->update( \Mockery::mock( LogDataInterface::class ) ) );

	}

	public function test_update_nothing_if_channel_not_found() {

		do_action( FrontController::ACTION_LOADED );

		$channels = \Mockery::mock( Channels::class );

		$channels->shouldReceive( 'logger' )
			->once()
			->with( 'test' )
			->andThrow( InvalidChannelNameException::class );

		$log = \Mockery::mock( LogDataInterface::class );
		$log->shouldReceive( 'level' )
			->once()
			->andReturn( 100 );
		$log->shouldReceive( 'channel' )
			->once()
			->andReturn( 'test' );

		$subscriber = new LogActionSubscriber( $channels );

		self::assertFalse( $subscriber->update( $log ) );

	}

	public function test_update_nothing_if_no_level() {

		do_action( FrontController::ACTION_LOADED );

		$channels = \Mockery::mock( Channels::class );

		$log = \Mockery::mock( LogDataInterface::class );
		$log->shouldReceive( 'level' )
			->once()
			->andReturn( - 1 );

		$subscriber = new LogActionSubscriber( $channels );

		self::assertFalse( $subscriber->update( $log ) );

	}

}

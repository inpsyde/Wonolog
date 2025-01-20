<?php # -*- coding: utf-8 -*-

namespace Inpsyde\Wonolog\Tests\Unit\Data;

use Brain\Monkey\Functions;
use Inpsyde\Wonolog\Channels;
use Inpsyde\Wonolog\Data\HookLogFactory;
use Inpsyde\Wonolog\Data\LogDataInterface;
use Inpsyde\Wonolog\Tests\TestCase;
use Monolog\Logger;

/**
 * @package wonolog\tests
 */
class HookLogFactoryTest extends TestCase {

	protected function setUp() {

		Functions\when( 'is_wp_error' )
			->alias(
				function ( $thing ) {

					return $thing instanceof \WP_Error;
				}
			);
		parent::setUp();
	}

	public function test_logs_from_arguments_returns_default_if_no_arguments() {

		$factory = new HookLogFactory();
		$logs    = $factory->logs_from_hook_arguments( [] );

		self::assertInternalType( 'array', $logs );
		self::assertCount( 1, $logs );

		/** @var LogDataInterface $log */
		$log = reset( $logs );

		self::assertInstanceOf( LogDataInterface::class, $log );
		self::assertSame( $log->message(), 'Unknown error.' );
		self::assertSame( $log->channel(), Channels::DEBUG );
		self::assertSame( $log->context(), [] );

	}

	public function test_logs_from_arguments_returns_given_log_data() {

		$first = \Mockery::mock( LogDataInterface::class );
		$first->shouldReceive( 'level' )
			->zeroOrMoreTimes();
		$first->shouldReceive( 'channel' )
			->zeroOrMoreTimes();
		$first->shouldReceive( 'message' )
			->zeroOrMoreTimes();
		$first->shouldReceive( 'context' )
			->zeroOrMoreTimes();

		$second = clone $first;
		$third  = clone $first;
		$fourth = clone $first;

		$args   = compact( 'first', 'second', 'third' );
		$args[] = 'foo';
		$args[] = 'bar';
		$args[] = $fourth;

		$factory = new HookLogFactory();
		$logs    = $factory->logs_from_hook_arguments( $args );

		self::assertInternalType( 'array', $logs );
		self::assertCount( 4, $logs );

		self::assertSame( $logs[ 0 ], $first );
		self::assertSame( $logs[ 1 ], $second );
		self::assertSame( $logs[ 2 ], $third );
		self::assertSame( $logs[ 3 ], $fourth );
	}

	public function test_logs_from_arguments_returns_given_log_data_with_raised_level() {

		/** @var LogDataInterface $first */
		$first = \Mockery::mock( LogDataInterface::class );
		$first->shouldReceive( 'level' )
			->atLeast()
			->once()
			->andReturn( 100 );
		$first->shouldReceive( 'channel' )
			->atLeast()
			->once()
			->andReturn( 'Channel' );
		$first->shouldReceive( 'message' )
			->atLeast()
			->once()
			->andReturn( 'Message' );
		$first->shouldReceive( 'context' )
			->atLeast()
			->once()
			->andReturn( [ 'foo' ] );

		/** @var LogDataInterface $first */
		$second = \Mockery::mock( LogDataInterface::class );
		$second->shouldReceive( 'level' )
			->atLeast()
			->once()
			->andReturn( 400 );
		$second->shouldReceive( 'channel' )
			->atLeast()
			->once()
			->andReturn( 'Channel!' );
		$second->shouldReceive( 'message' )
			->atLeast()
			->once()
			->andReturn( 'Message!' );
		$second->shouldReceive( 'context' )
			->atLeast()
			->once()
			->andReturn( [] );

		$args = compact( 'first', 'second' );

		$factory = new HookLogFactory();
		$logs    = $factory->logs_from_hook_arguments( $args, 300 );

		self::assertInternalType( 'array', $logs );
		self::assertCount( 2, $logs );

		self::assertInstanceOf( LogDataInterface::class, $first );
		self::assertSame( 300, $logs[ 0 ]->level() );
		self::assertSame( $logs[ 0 ]->message(), $first->message() );
		self::assertSame( $logs[ 0 ]->channel(), $first->channel() );
		self::assertSame( $logs[ 0 ]->context(), $first->context() );

		self::assertInstanceOf( LogDataInterface::class, $second );
		self::assertSame( 400, $logs[ 1 ]->level() );
		self::assertSame( $logs[ 1 ]->message(), $second->message() );
		self::assertSame( $logs[ 1 ]->channel(), $second->channel() );
		self::assertSame( $logs[ 1 ]->context(), $second->context() );
	}

	public function test_logs_from_from_string_argument() {

		$factory = new HookLogFactory();
		$logs    = $factory->logs_from_hook_arguments( [ 'Foo!' ] );

		self::assertInternalType( 'array', $logs );
		self::assertCount( 1, $logs );

		/** @var LogDataInterface $log */
		$log = reset( $logs );

		self::assertInstanceOf( LogDataInterface::class, $log );
		self::assertSame( $log->message(), 'Foo!' );
		self::assertSame( $log->channel(), Channels::DEBUG );
		self::assertSame( $log->context(), [] );

	}

	public function test_logs_from_from_string_argument_and_args() {

		$factory = new HookLogFactory();
		$logs    = $factory->logs_from_hook_arguments( [ 'Foo!', Logger::CRITICAL, 'A Channel' ] );

		self::assertInternalType( 'array', $logs );
		self::assertCount( 1, $logs );

		/** @var LogDataInterface $log */
		$log = reset( $logs );

		self::assertInstanceOf( LogDataInterface::class, $log );
		self::assertSame( $log->message(), 'Foo!' );
		self::assertSame( $log->level(), Logger::CRITICAL );
		self::assertSame( $log->channel(), 'A Channel' );
		self::assertSame( $log->context(), [] );
	}

	public function test_logs_from_from_string_argument_and_raised_level() {

		$factory = new HookLogFactory();
		$logs    = $factory->logs_from_hook_arguments( [ 'Foo!', Logger::DEBUG, 'A Channel' ], Logger::CRITICAL );

		self::assertInternalType( 'array', $logs );
		self::assertCount( 1, $logs );

		/** @var LogDataInterface $log */
		$log = reset( $logs );

		self::assertInstanceOf( LogDataInterface::class, $log );
		self::assertSame( $log->message(), 'Foo!' );
		self::assertSame( $log->level(), Logger::CRITICAL );
		self::assertSame( $log->channel(), 'A Channel' );
		self::assertSame( $log->context(), [] );
	}

	public function test_logs_from_from_wp_error_argument() {

		$error = \Mockery::mock( \WP_Error::class );
		$error->shouldReceive( 'get_error_message' )
			->andReturn( 'Foo!' );
		$error->shouldReceive( 'get_error_data' )
			->andReturn( [ 'db broken' ] );
		$error->shouldReceive( 'get_error_codes' )
			->andReturn( [ 'wpdb' ] );

		$factory = new HookLogFactory();
		$logs    = $factory->logs_from_hook_arguments( [ $error ] );

		self::assertInternalType( 'array', $logs );
		self::assertCount( 1, $logs );

		/** @var LogDataInterface $log */
		$log = reset( $logs );

		self::assertInstanceOf( LogDataInterface::class, $log );
		self::assertSame( $log->message(), 'Foo!' );
		self::assertSame( $log->level(), Logger::WARNING );
		self::assertSame( $log->channel(), Channels::DB );
		self::assertSame( $log->context(), [ 'db broken' ] );
	}

	public function test_logs_from_from_wp_error_argument_and_args() {

		$error = \Mockery::mock( \WP_Error::class );
		$error->shouldReceive( 'get_error_message' )
			->andReturn( 'Foo!' );
		$error->shouldReceive( 'get_error_data' )
			->andReturn( [ 'db broken' ] );
		$error->shouldReceive( 'get_error_codes' )
			->andReturn( [ 'wpdb' ] );

		$factory = new HookLogFactory();
		$logs    = $factory->logs_from_hook_arguments( [ $error, Logger::ERROR, Channels::DEBUG ] );

		self::assertInternalType( 'array', $logs );
		self::assertCount( 1, $logs );

		/** @var LogDataInterface $log */
		$log = reset( $logs );

		self::assertInstanceOf( LogDataInterface::class, $log );
		self::assertSame( $log->message(), 'Foo!' );
		self::assertSame( $log->level(), Logger::ERROR );
		self::assertSame( $log->channel(), Channels::DEBUG );
		self::assertSame( $log->context(), [ 'db broken' ] );
	}

	public function test_logs_from_from_wp_error_argument_and_raised_level() {

		$error = \Mockery::mock( \WP_Error::class );
		$error->shouldReceive( 'get_error_message' )
			->andReturn( 'Foo!' );
		$error->shouldReceive( 'get_error_data' )
			->andReturn( [ 'foo' ] );
		$error->shouldReceive( 'get_error_codes' )
			->andReturn( [ 'foo' ] );

		$factory = new HookLogFactory();
		$logs    = $factory->logs_from_hook_arguments( [ $error, Logger::NOTICE, Channels::HTTP ], Logger::ERROR );

		self::assertInternalType( 'array', $logs );
		self::assertCount( 1, $logs );

		/** @var LogDataInterface $log */
		$log = reset( $logs );

		self::assertInstanceOf( LogDataInterface::class, $log );
		self::assertSame( $log->message(), 'Foo!' );
		self::assertSame( $log->level(), Logger::ERROR );
		self::assertSame( $log->channel(), Channels::HTTP );
		self::assertSame( $log->context(), [ 'foo' ] );
	}

	public function test_logs_from_from_throwable_argument() {

		$exception = new \Exception( 'Foo!' );

		$factory = new HookLogFactory();
		$logs    = $factory->logs_from_hook_arguments( [ $exception ] );

		self::assertInternalType( 'array', $logs );
		self::assertCount( 1, $logs );

		/** @var LogDataInterface $log */
		$log = reset( $logs );

		self::assertInstanceOf( LogDataInterface::class, $log );
		self::assertSame( $log->message(), 'Foo!' );
		self::assertSame( $log->level(), Logger::ERROR );
		self::assertSame( $log->channel(), Channels::DEBUG );
		self::assertInternalType( 'array', $log->context() );
	}

	public function test_logs_from_from_throwable_argument_and_args() {

		$exception = new \Exception( 'Foo!' );

		$factory = new HookLogFactory();
		$logs    = $factory->logs_from_hook_arguments( [ $exception, Logger::CRITICAL, Channels::DB ] );

		self::assertInternalType( 'array', $logs );
		self::assertCount( 1, $logs );

		/** @var LogDataInterface $log */
		$log = reset( $logs );

		self::assertInstanceOf( LogDataInterface::class, $log );
		self::assertSame( $log->message(), 'Foo!' );
		self::assertSame( $log->level(), Logger::CRITICAL );
		self::assertSame( $log->channel(), Channels::DB );
		self::assertInternalType( 'array', $log->context() );
	}

	public function test_logs_from_from_throwable_argument_and_raised_level() {

		$exception = new \Exception( 'Foo!' );

		$factory = new HookLogFactory();
		$logs    = $factory->logs_from_hook_arguments( [ $exception, Logger::DEBUG, Channels::DB ], Logger::CRITICAL );

		self::assertInternalType( 'array', $logs );
		self::assertCount( 1, $logs );

		/** @var LogDataInterface $log */
		$log = reset( $logs );

		self::assertInstanceOf( LogDataInterface::class, $log );
		self::assertSame( $log->message(), 'Foo!' );
		self::assertSame( $log->level(), Logger::CRITICAL );
		self::assertSame( $log->channel(), Channels::DB );
		self::assertInternalType( 'array', $log->context() );
	}

	public function test_logs_from_from_array_argument() {

		$data = [
			'message' => 'Hello!',
			'level'   => Logger::NOTICE,
			'channel' => Channels::SECURITY,
			'context' => [ 'foo', 'bar' ],
		];

		$factory = new HookLogFactory();
		$logs    = $factory->logs_from_hook_arguments( [ $data, 'x', 'y' ], Logger::DEBUG );

		self::assertInternalType( 'array', $logs );
		self::assertCount( 1, $logs );

		/** @var LogDataInterface $log */
		$log = reset( $logs );

		self::assertInstanceOf( LogDataInterface::class, $log );
		self::assertSame( $log->message(), 'Hello!' );
		self::assertSame( $log->level(), Logger::NOTICE );
		self::assertSame( $log->channel(), Channels::SECURITY );
		self::assertInternalType( 'array', [ 'foo', 'bar' ] );
	}

	public function test_logs_from_from_array_argument_and_raised_level() {

		$data = [
			'message' => 'Hello!',
			'level'   => Logger::DEBUG,
			'channel' => Channels::SECURITY,
			'context' => [ 'foo', 'bar' ],
		];

		$factory = new HookLogFactory();
		$logs    = $factory->logs_from_hook_arguments( [ $data, 600, 'y' ], Logger::NOTICE );

		self::assertInternalType( 'array', $logs );
		self::assertCount( 1, $logs );

		/** @var LogDataInterface $log */
		$log = reset( $logs );

		self::assertInstanceOf( LogDataInterface::class, $log );
		self::assertSame( $log->message(), 'Hello!' );
		self::assertSame( $log->level(), Logger::NOTICE );
		self::assertSame( $log->channel(), Channels::SECURITY );
		self::assertInternalType( 'array', [ 'foo', 'bar' ] );
	}

}

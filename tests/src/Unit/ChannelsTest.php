<?php # -*- coding: utf-8 -*-
/*
 * This file is part of the Inpsyde Wonolog package.
 *
 * (c) Inpsyde GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Inpsyde\Wonolog\Tests\Unit;

use Brain\Monkey\WP\Actions;
use Brain\Monkey\WP\Filters;
use Inpsyde\Wonolog\Channels;
use Inpsyde\Wonolog\Handler\HandlersRegistry;
use Inpsyde\Wonolog\Processor\ProcessorsRegistry;
use Inpsyde\Wonolog\Tests\TestCase;
use Monolog\Handler\HandlerInterface;
use Monolog\Logger;

/**
 * @package wonolog\tests
 * @license http://opensource.org/licenses/MIT MIT
 */
class ChannelsTest extends TestCase {

	public function test_all_channels_return_default_channels() {

		$channels = Channels::all_channels();

		$expected = [
			Channels::HTTP,
			Channels::DB,
			Channels::SECURITY,
			Channels::DEBUG,
		];

		self::assertSame( $expected, $channels );
	}

	public function test_all_channels_allow_filter() {

		Filters::expectApplied( Channels::FILTER_CHANNELS )
			->once()
			->andReturn( [ 'foo', 1, [] ] );

		$channels = Channels::all_channels();

		self::assertSame( [ 'foo' ], $channels );
	}

	/**
	 * @expectedException \Inpsyde\Wonolog\Exception\InvalidChannelNameException
	 */
	public function test_has_channel_fails_if_wrong_param_type() {

		$channels = $this->create_channels();
		$channels->has_channel( [] );
	}

	public function test_has_channel() {

		$channels = $this->create_channels();

		self::assertTrue( $channels->has_channel( Channels::DEBUG ) );
		self::assertFalse( $channels->has_channel( 'Foo' ) );
	}

	/**
	 * @expectedException \Inpsyde\Wonolog\Exception\InvalidChannelNameException
	 */
	public function test_logger_fails_if_wrong_channel() {

		$channels = $this->create_channels();
		$channels->logger( 'X' );
	}

	public function test_logger_initialize_once_and_return_always() {

		/** @var HandlersRegistry|\Mockery\MockInterface $handlers */
		$handlers = \Mockery::mock( HandlersRegistry::class );

		/** @var ProcessorsRegistry $processors */
		$processors = \Mockery::mock( ProcessorsRegistry::class );

		$default_handler = \Mockery::mock( HandlerInterface::class );

		$handlers->shouldReceive( 'find' )
			->once()
			->with( HandlersRegistry::DEFAULT_NAME )
			->andReturn( $default_handler );

		$processors->shouldReceive( 'find' )
			->once()
			->with( ProcessorsRegistry::DEFAULT_NAME )
			->andReturn( 'strtolower' );

		Filters::expectApplied( Channels::FILTER_USE_DEFAULT_HANDLER )
			->once()
			->with( TRUE, \Mockery::type( Logger::class ), $default_handler )
			->andReturn( TRUE );

		Filters::expectApplied( Channels::FILTER_USE_DEFAULT_PROCESSOR )
			->once()
			->with( TRUE, \Mockery::type( Logger::class ), 'strtolower' )
			->andReturn( TRUE );

		Actions::expectFired( Channels::ACTION_LOGGER )
			->once()
			->with( \Mockery::type( Logger::class ), $handlers, $processors );

		$channels = new Channels( $handlers, $processors );
		$logger   = $channels->logger( Channels::DEBUG );

		self::assertInstanceOf( Logger::class, $logger );
		self::assertSame( $logger, $channels->logger( Channels::DEBUG ) );
		self::assertSame( $logger, $channels->logger( Channels::DEBUG ) );
		self::assertEquals( [ $default_handler ], $logger->getHandlers() );
		self::assertEquals( [ 'strtolower' ], $logger->getProcessors() );
	}

	public function test_logger_initialize_skip_defaults_via_filter() {

		/** @var HandlersRegistry|\Mockery\MockInterface $handlers */
		$handlers = \Mockery::mock( HandlersRegistry::class );

		/** @var ProcessorsRegistry $processors */
		$processors = \Mockery::mock( ProcessorsRegistry::class );

		$default_handler = \Mockery::mock( HandlerInterface::class );

		$handlers->shouldReceive( 'find' )
			->once()
			->with( HandlersRegistry::DEFAULT_NAME )
			->andReturn( $default_handler );

		$processors->shouldReceive( 'find' )
			->once()
			->with( ProcessorsRegistry::DEFAULT_NAME )
			->andReturn( 'strtolower' );

		Filters::expectApplied( Channels::FILTER_USE_DEFAULT_HANDLER )
			->once()
			->with( TRUE, \Mockery::type( Logger::class ), $default_handler )
			->andReturn( FALSE );

		Filters::expectApplied( Channels::FILTER_USE_DEFAULT_PROCESSOR )
			->once()
			->with( TRUE, \Mockery::type( Logger::class ), 'strtolower' )
			->andReturn( FALSE );

		/** @var $custom_handler HandlerInterface $handler */
		$custom_handler = \Mockery::mock( HandlerInterface::class );

		Actions::expectFired( Channels::ACTION_LOGGER )
			->once()
			->with( \Mockery::type( Logger::class ), $handlers, $processors )
			->whenHappen(
				function ( Logger $logger ) use ( $custom_handler ) {

					$logger->pushHandler( $custom_handler );
				}
			);

		$channels = new Channels( $handlers, $processors );
		$logger   = $channels->logger( Channels::DEBUG );

		self::assertInstanceOf( Logger::class, $logger );
		self::assertSame( $logger, $channels->logger( Channels::DEBUG ) );
		self::assertSame( $logger, $channels->logger( Channels::DEBUG ) );
		self::assertSame( [ $custom_handler ], $logger->getHandlers() );
		self::assertSame( [], $logger->getProcessors() );
	}

	/**
	 * @return Channels
	 */
	private function create_channels() {

		/** @var HandlersRegistry $handlers */
		$handlers = \Mockery::mock( HandlersRegistry::class );
		/** @var ProcessorsRegistry $processors */
		$processors = \Mockery::mock( ProcessorsRegistry::class );

		return new Channels( $handlers, $processors );
	}
}

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

use Brain\Monkey\WP\Filters;
use Inpsyde\Wonolog\Channels;
use Inpsyde\Wonolog\Tests\TestCase;
use Monolog\Logger;

/**
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @package wonolog
 * @license http://opensource.org/licenses/MIT MIT
 */
class ChannelsTest extends TestCase {

	public function test_channels() {

		self::assertSame( Channels::CHANNELS, Channels::all_channels() );

	}

	public function test_channels_filtered() {

		Filters::expectApplied( 'wonolog.channels' )
			->andReturnUsing(
				function ( array $channels ) {

					$channels[] = 'the_new_channel';

					return $channels;
				}
			);

		$expected = array_merge( Channels::CHANNELS, [ 'the_new_channel' ] );

		self::assertEquals( $expected, Channels::all_channels() );

	}

	public function test_channels_filtered_empty() {

		Filters::expectApplied( 'wonolog.channels' )
			->andReturnUsing(
				function () {

					return [];
				}
			);

		self::assertSame( [], Channels::all_channels() );

	}

	public function test_loggers_logger_has_logger() {

		$channels = new Channels();

		foreach ( Channels::CHANNELS as $channel ) {
			self::assertFalse( $channels->has_logger( $channel ) );
			$logger = $channels->logger( $channel );
			self::assertTrue( $channels->has_logger( $channel ) );
			self::assertInstanceOf( Logger::class, $logger );
			self::assertSame( $channel, $logger->getName() );
		}

	}

}

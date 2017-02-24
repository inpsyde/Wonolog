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

use Inpsyde\Wonolog\Channels;
use Inpsyde\Wonolog\Data\Log;
use Inpsyde\Wonolog\Tests\TestCase;
use Monolog\Logger;

/**
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @package wonolog
 * @license http://opensource.org/licenses/MIT MIT
 */
class LogTest extends TestCase {

	public function test_basic_properties() {

		$log = new Log( 'message', Logger::EMERGENCY, Channels::DEBUG, [ 'foo' ] );

		self::assertSame( Channels::DEBUG, $log->channel() );
		self::assertSame( 'message', $log->message() );
		self::assertSame( [ 'foo' ], $log->context() );
		self::assertSame( Logger::EMERGENCY, $log->level() );

	}

	public function test_from_keyed_array() {

		$log = Log::from_array(
			[
				Log::MESSAGE => 'message',
				Log::LEVEL   => Logger::EMERGENCY,
				Log::CHANNEL => Channels::HTTP,
				Log::CONTEXT => [ 'foo' ]
			]
		);

		self::assertSame( Channels::HTTP, $log->channel() );
		self::assertSame( 'message', $log->message() );
		self::assertSame( [ 'foo' ], $log->context() );
		self::assertSame( Logger::EMERGENCY, $log->level() );

	}

	public function test_from_keyed_array_merged() {

		$log = Log::from_array(
			[
				Log::MESSAGE => 'message',
				Log::CONTEXT => [ 'foo' ]
			]
		);

		self::assertSame( Channels::DEBUG, $log->channel() );
		self::assertSame( 'message', $log->message() );
		self::assertSame( [ 'foo' ], $log->context() );
		self::assertSame( Logger::DEBUG, $log->level() );

	}

}
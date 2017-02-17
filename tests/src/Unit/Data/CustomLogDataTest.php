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
use Inpsyde\Wonolog\Data\Alert;
use Inpsyde\Wonolog\Data\Critical;
use Inpsyde\Wonolog\Data\Debug;
use Inpsyde\Wonolog\Data\Emergency;
use Inpsyde\Wonolog\Data\Error;
use Inpsyde\Wonolog\Data\Info;
use Inpsyde\Wonolog\Data\LogDataInterface;
use Inpsyde\Wonolog\Data\Notice;
use Inpsyde\Wonolog\Data\Warning;
use Inpsyde\Wonolog\Tests\TestCase;
use Monolog\Logger;

/**
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @package wonolog
 * @license http://opensource.org/licenses/MIT MIT
 */
class CustomLogDataTest extends TestCase {

	/**
	 * @dataProvider log_levels
	 *
	 * @param int              $expected_level
	 * @param LogDataInterface $log
	 */
	public function test_levels( $expected_level, LogDataInterface $log ) {

		self::assertSame( Channels::DEBUG, $log->channel() );
		self::assertSame( 'test', $log->message() );
		self::assertSame( [], $log->context() );
		self::assertSame( $expected_level, $log->level() );

	}

	public function log_levels() {

		return [
			[ Logger::ALERT, new Alert( 'test', Channels::DEBUG ) ],
			[ Logger::CRITICAL, new Critical( 'test', Channels::DEBUG ) ],
			[ Logger::DEBUG, new Debug( 'test', Channels::DEBUG ) ],
			[ Logger::EMERGENCY, new Emergency( 'test', Channels::DEBUG ) ],
			[ Logger::ERROR, new Error( 'test', Channels::DEBUG ) ],
			[ Logger::INFO, new Info( 'test', Channels::DEBUG ) ],
			[ Logger::NOTICE, new Notice( 'test', Channels::DEBUG ) ],
			[ Logger::WARNING, new Warning( 'test', Channels::DEBUG ) ],
		];
	}

}
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

use Inpsyde\Wonolog\LogLevel;
use Inpsyde\Wonolog\Tests\TestCase;
use Monolog\Logger;

/**
 * @package wonolog\tests
 * @license http://opensource.org/licenses/MIT MIT
 *
 * @runTestsInSeparateProcesses
 */
class LogLevelTest extends TestCase {

	protected function tearDown() {

		putenv( 'WONOLOG_DEFAULT_MIN_LEVEL' );
		parent::tearDown();
	}

	public function test_default_level_by_env_string() {

		define('DIE', 1);
		putenv( 'WONOLOG_DEFAULT_MIN_LEVEL=CRITICAL' );

		$log_level = new LogLevel();

		self::assertSame( Logger::CRITICAL, $log_level->default_min_level() );
	}

	public function test_default_level_by_env_num() {

		putenv( 'WONOLOG_DEFAULT_MIN_LEVEL=500' );

		$log_level = new LogLevel();

		self::assertSame( Logger::CRITICAL, $log_level->default_min_level() );
	}

	public function test_default_level_by_constant_none() {

		$log_level = new LogLevel();

		self::assertSame( Logger::ERROR, $log_level->default_min_level() );
	}

	public function test_check_level_accepts_positive_numbers() {

		$log_level = new LogLevel();

		self::assertSame( 0, $log_level->check_level( 0 ) );
		self::assertSame( 27, $log_level->check_level( 27 ) );
		self::assertSame( 42, $log_level->check_level( 42 ) );
		self::assertSame( 0, $log_level->check_level( - 10 ) );
	}

	public function test_check_level_accepts_defined_level_strings() {

		$log_level = new LogLevel();

		self::assertSame( Logger::CRITICAL, $log_level->check_level( 'CRITICAL' ) );
		self::assertSame( Logger::ERROR, $log_level->check_level( 'error' ) );
		self::assertSame( Logger::DEBUG, $log_level->check_level( 'Debug' ) );
		self::assertSame( Logger::ALERT, $log_level->check_level( 'aLeRt' ) );
		self::assertSame( Logger::EMERGENCY, $log_level->check_level( 'emeRGEncy' ) );
		self::assertSame( Logger::INFO, $log_level->check_level( ' INFO ' ) );
		self::assertSame( Logger::NOTICE, $log_level->check_level( ' nOtiCE' ) );
		self::assertSame( Logger::WARNING, $log_level->check_level( 'Warning ' ) );
		self::assertSame( 0, $log_level->check_level( 'MEH' ) );
	}

	/**
	 * @runInSeparateProcess
	 */
	public function test_default_level_by_constant_log() {

		define( 'WP_DEBUG_LOG', TRUE );

		$log_level = new LogLevel();

		self::assertSame( Logger::DEBUG, $log_level->default_min_level() );
	}

	/**
	 * @runInSeparateProcess
	 */
	public function test_default_level_by_constant_debug() {

		define( 'WP_DEBUG', TRUE );

		$log_level = new LogLevel();

		self::assertFalse( defined( 'WP_DEBUG_LOG' ) );
		self::assertSame( Logger::DEBUG, $log_level->default_min_level() );
	}

	/**
	 * @runInSeparateProcess
	 */
	public function test_default_level_by_constant_log_false() {

		define( 'WP_DEBUG_LOG', FALSE );
		define( 'WP_DEBUG', TRUE );

		$log_level = new LogLevel();

		self::assertSame( Logger::ERROR, $log_level->default_min_level() );
	}

	/**
	 * @runInSeparateProcess
	 */
	public function test_default_level_by_env_over_constants() {

		putenv( 'WONOLOG_DEFAULT_MIN_LEVEL=EMERGENCY' );
		define( 'WP_DEBUG_LOG', FALSE );
		define( 'WP_DEBUG', TRUE );

		$log_level = new LogLevel();

		self::assertSame( Logger::EMERGENCY, $log_level->default_min_level() );
	}

}

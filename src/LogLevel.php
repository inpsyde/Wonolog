<?php # -*- coding: utf-8 -*-
/*
 * This file is part of the Inpsyde wonolog package.
 *
 * (c) Inpsyde GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Inpsyde\Wonolog;

use Monolog\Logger;

/**
 * Utility object used to build default minimum logging level based WordPress and environment settings.
 *
 * It also has a method to check the validity of a value as level identifier.
 *
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @package Inpsyde GmbH
 * @license http://opensource.org/licenses/MIT MIT
 */
class LogLevel {

	private static $min_level;

	/**
	 * @return LogLevel
	 */
	public static function instance() {

		return new static();
	}

	/**
	 * Returns the minimum default log level based on environment variable or WordPress debug settings
	 * (in this order of priority).
	 *
	 * The level is set once per request and it is filterable.
	 *
	 * @return int
	 */
	public function default_level() {

		$env_level = getenv( 'WONOLOG_DEFAULT_MIN_LEVEL' );
		$min_level = $env_level === FALSE ? 0 : $env_level;

		$levels    = Logger::getLevels();
		$min_level = $this->check_level( $min_level, $levels );

		if ( ! $min_level ) {
			$const     = defined( 'WP_DEBUG_LOG' ) ? 'WP_DEBUG_LOG' : 'WP_DEBUG';
			$min_level = ( defined( $const ) && constant( $const ) ) ? $min_level = Logger::DEBUG : Logger::ERROR;
		}

		/**
		 * Filters the default minimum log level.
		 * Any non-numeric value returned is ignored and the value before filters is used.
		 *
		 * @param int $min_level
		 */
		$filtered = apply_filters( 'wonolog.default-min-level', $min_level );

		self::$min_level = $this->check_level( $filtered, $levels ) ? : $min_level;

		return $min_level;
	}

	/**
	 * @param int|string $level
	 * @param array      $levels
	 *
	 * @return int
	 */
	public function check_level( $level, $levels = [] ) {

		if ( ! $level ) {
			return 0;
		}

		is_string( $level ) and $level = strtoupper( trim( $level ) );

		if ( is_numeric( $level ) ) {
			return (int) $level > 0 ? (int) $level : 0;
		}

		$levels or $levels = Logger::getLevels();

		if ( array_key_exists( $level, $levels ) ) {
			return $levels[ $level ];
		}

		return 0;
	}
}
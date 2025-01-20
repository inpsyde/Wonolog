<?php # -*- coding: utf-8 -*-

namespace Inpsyde\Wonolog\Data;

/**
 * Implements the interface doing nothing.
 *
 * @package wonolog
 *
 * @codeCoverageIgnore
 */
final class NullLog implements LogDataInterface {

	const LOG_LEVEL = -1;

	/**
	 * @return int
	 */
	public function level() {

		return self::LOG_LEVEL;
	}

	/**
	 * @return string
	 */
	public function message() {

		return '';
	}

	/**
	 * @return string
	 */
	public function channel() {

		return '';
	}

	/**
	 * @return array
	 */
	public function context() {

		return [];
	}
}

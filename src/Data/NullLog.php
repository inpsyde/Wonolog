<?php # -*- coding: utf-8 -*-
/*
 * This file is part of the Inpsyde wonolog package.
 *
 * (c) Inpsyde GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Inpsyde\Wonolog\Data;

/**
 * Implements the interface doing nothing.
 *
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @package wonolog
 * @license http://opensource.org/licenses/MIT MIT
 *
 * @codeCoverageIgnore
 */
final class NullLog implements LogDataInterface {

	/**
	 * @return int
	 */
	public function level() {

		return - 1;
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

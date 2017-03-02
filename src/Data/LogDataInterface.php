<?php # -*- coding: utf-8 -*-
/*
 * This file is part of the Inpsyde Wonolog package.
 *
 * (c) Inpsyde GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Inpsyde\Wonolog\Data;

/**
 * @package wonolog
 * @license http://opensource.org/licenses/MIT MIT
 */
interface LogDataInterface {

	/**
	 * @return int
	 */
	public function level();

	/**
	 * @return string
	 */
	public function message();

	/**
	 * @return string
	 */
	public function channel();

	/**
	 * @return array
	 */
	public function context();
}
<?php # -*- coding: utf-8 -*-
/*
 * This file is part of the Inpsyde wonolog package.
 *
 * (c) Inpsyde GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Inpsyde\Wonolog\HookListeners;

use Inpsyde\Wonolog\Data\LogDataInterface;

/**
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @package wonolog
 * @license http://opensource.org/licenses/MIT MIT
 */
interface HookListenerInterface {

	/**
	 * @return string|string[]
	 */
	public function listen_to();

	/**
	 * @param array ...$args
	 *
	 * @return LogDataInterface
	 */
	public function update( ...$args );

}
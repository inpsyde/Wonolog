<?php # -*- coding: utf-8 -*-
/*
 * This file is part of the wonolog package.
 *
 * (c) Giuseppe Mazzapica
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Inpsyde\Wonolog\HookListener;

/**
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @package wonolog
 * @license http://opensource.org/licenses/MIT MIT
 */
trait ListenerIdByClassNameTrait {

	public function id() {
		return __CLASS__;
	}

}
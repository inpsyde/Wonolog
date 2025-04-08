<?php # -*- coding: utf-8 -*-

namespace Inpsyde\Wonolog\HookListener;

/**
 * @package wonolog
 */
trait ListenerIdByClassNameTrait {

	/**
	 * @return string
	 */
	public function id() {

		return __CLASS__;
	}
}

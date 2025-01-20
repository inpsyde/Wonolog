<?php # -*- coding: utf-8 -*-

namespace Inpsyde\Wonolog\HookListener;

/**
 * @package wonolog
 */
interface HookListenerInterface {

	/**
	 * @return string
	 */
	public function id();

	/**
	 * @return string|string[]
	 */
	public function listen_to();
}

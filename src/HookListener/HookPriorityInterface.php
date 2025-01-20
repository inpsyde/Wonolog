<?php # -*- coding: utf-8 -*-

namespace Inpsyde\Wonolog\HookListener;

/**
 * Interface PrioritizedHookListenerInterface
 *
 * @package Inpsyde\Wonolog\HookListeners
 */
interface HookPriorityInterface {

	const FILTER_PRIORITY = 'wonolog.hook-listener-priority';

	/**
	 * Returns the priority of the hook callback
	 *
	 * @return int
	 */
	public function priority();
}

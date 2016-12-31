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

/**
 * Interface PrioritizedHookListenerInterface
 *
 * @package Inpsyde\Wonolog\HookListeners
 */
interface HookPriorityInterface {

	/**
	 * Returns the priority of the hook callback
	 *
	 * @return int
	 */
	public function priority();

}
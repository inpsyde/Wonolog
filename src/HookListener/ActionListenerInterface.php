<?php # -*- coding: utf-8 -*-

namespace Inpsyde\Wonolog\HookListener;

use Inpsyde\Wonolog\Data\LogDataInterface;

/**
 * @package wonolog
 */
interface ActionListenerInterface extends HookListenerInterface {

	/**
	 * @param array $args
	 *
	 * @return LogDataInterface
	 */
	public function update( array $args );
}

<?php # -*- coding: utf-8 -*-

namespace Inpsyde\Wonolog\HookListener;

/**
 * @package wonolog
 */
interface FilterListenerInterface extends HookListenerInterface {

	/**
	 * @param array $args
	 *
	 * @return mixed
	 */
	public function filter( array $args );
}

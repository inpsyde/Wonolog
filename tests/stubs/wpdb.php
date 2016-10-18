<?php # -*- coding: utf-8 -*-
/*
 * This file is part of the wonolog package.
 *
 * (c) Inpsyde GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Inpsyde\Wonolog\HookListeners\WpDieHandlerListener;

if ( class_exists( 'wpdb' ) ) {
	return;
}

/**
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @package wonolog
 * @license http://opensource.org/licenses/MIT MIT
 */
class wpdb {

	/**
	 * @var WpDieHandlerListener
	 */
	public $wp_die_listener;

	/**
	 * @param string $message
	 *
	 * @return string
	 */
	public function bail( $message ) {

		$handler = $this->execute_die_listener( $message );

		return $handler( $message, 'Bail' );
	}

	/**
	 * @param string $message
	 *
	 * @return string
	 */
	public function print_error( $message ) {

		$handler = $this->execute_die_listener( $message );

		return $handler( $message, 'Bail' );
	}

	/**
	 * @param string $message
	 *
	 * @return callable
	 */
	private function execute_die_listener( $message ) {

		$handler = function ( $message ) {

			return "Handled: $message";
		};

		$listener = $this->wp_die_listener;
		$handler  = call_user_func( [ $listener, 'filter' ], [ $handler ] );

		return $handler;
	}

}
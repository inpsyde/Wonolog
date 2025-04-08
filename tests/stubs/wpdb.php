<?php # -*- coding: utf-8 -*-

use Inpsyde\Wonolog\HookListener\WpDieHandlerListener;

if ( class_exists( 'wpdb' ) ) {
	return;
}

/**
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @package wonolog
 */
class wpdb {

	/**
	 * @var WpDieHandlerListener
	 */
	public $wp_die_listener;

	/**
	 * @param string $message
	 * @param string $error_code
	 *
	 * @return string
	 */
	public function bail( $message, $error_code = '500' ) {

		$handler = $this->execute_die_listener( $message );

		return $handler( $message, 'Bail' );
	}

	/**
	 * @param string $message
	 *
	 * @return string
	 */
	public function print_error( $message = '' ) {

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
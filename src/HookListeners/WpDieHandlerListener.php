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

use Inpsyde\Wonolog\Channels;
use Inpsyde\Wonolog\Data\Error;
use Inpsyde\Wonolog\Data\LogDataInterface;
use Inpsyde\Wonolog\Data\NullLog;

/**
 * Looks a wp_die() and try to find and log db errors.
 *
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @package wonolog
 */
final class WpDieHandlerListener implements FilterListenerInterface {

	/**
	 * @inheritdoc
	 */
	public function listen_to() {

		return [ 'wp_die_ajax_handler', 'wp_die_handler' ];
	}

	/**
	 * @wp-hook wp_die_ajax_handler
	 * @wp-hook wp_die_handler
	 *
	 * @param array $args
	 *
	 * @return LogDataInterface
	 */
	public function update( array $args ) {

		return new NullLog();
	}

	/**
	 * Run as handler for wp_die() and checks if it was called by
	 * wpdb::bail() or wpdb::print_error() so something gone wrong on db.
	 * After logging error, the method calls original handler.
	 *
	 * @wp-hook wp_die_ajax_handler
	 * @wp-hook wp_die_handler
	 *
	 * @param array $args
	 *
	 * @return mixed
	 */
	public function filter( array $args ) {

		$handler = $args ? reset( $args ) : NULL;

		if ( ! $handler || ! is_callable( $handler ) || ! $this->isDbError() ) {
			return $handler;
		}

		return function ( $message, $title = '', $args = [] ) use ( $handler ) {

			$msg                = filter_var( $message, FILTER_SANITIZE_STRING );
			$context            = $args;
			$context[ 'title' ] = $title;

			do_action( 'wonolog.log', new Error( $msg, Channels::DB, $context ) );

			return call_user_func_array( $handler, func_get_args() );
		};
	}

	/**
	 * @return bool
	 */
	private function isDbError() {

		$stacktrace = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS, 6 );

		$lasts = array_slice( $stacktrace, 2 );

		$is_error = function ( array $last ) {

			return
				isset( $last[ 'function' ] )
				&& isset( $last[ 'class' ] )
				&& ( $last[ 'function' ] === 'bail' || $last[ 'function' ] === 'print_error' )
				&& $last[ 'class' ] === 'wpdb';
		};

		foreach ( $lasts as $last ) {
			if ( $is_error( $last ) ) {
				return TRUE;
			}
		}

		return FALSE;
	}
}
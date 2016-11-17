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
use Inpsyde\Wonolog\Data\Debug;
use Inpsyde\Wonolog\Data\NullLog;

/**
 * Listens to WP Cron requests and logs the performed actions and their performance.
 *
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @package wonolog
 * @license http://opensource.org/licenses/MIT MIT
 */
final class CronDebugListener implements ActionListenerInterface {

	/**
	 * @var bool
	 */
	private static $ran = FALSE;

	/**
	 * @var bool
	 */
	private $is_cli = FALSE;

	private $is_cron = FALSE;

	/**
	 * @param null $is_cli (Optional, by default WP_CLI constant is used)
	 * @param null $is_cron (Optional, by default DOING_CRON constant is used)
	 */
	public function __construct( $is_cli = NULL, $is_cron = NULL ) {

		$this->is_cli = NULL !== $is_cli
			? (bool) $is_cli
			: defined( 'WP_CLI' ) && WP_CLI;
		$this->is_cron = NULL !== $is_cron
			? (bool) $is_cron
			: defined( 'DOING_CRON' ) && DOING_CRON;
	}

	/**
	 * @var array
	 */
	private $done = [];

	/**
	 * @return string|string[]
	 */
	public function listen_to() {

		return 'wp_loaded';
	}


	/**
	 * Logs all the cron hook performed and their performance.
	 *
	 * @wp-hook  wp_loaded
	 *
	 * @param array $args
	 *
	 * @return NullLog
	 */
	public function update( array $args ) {

		if ( self::$ran ) {
			return new NullLog();
		}

		if ( $this->is_cron || $this->is_cli ) {
			$this->register_event_listener();
		}

		return new NullLog();
	}

	/**
	 * Logs all the cron hook performed and their performance.
	 */
	private function register_event_listener() {

		$cron_array = _get_cron_array();
		if ( ! $cron_array ) {
			return;
		}

		$hooks = array_reduce(
			$cron_array,
			function ( $hooks, $crons ) {

				return array_merge( $hooks, array_keys( $crons ) );
			},
			[]
		);

		$profile_cb = function () {
			$this->cron_action_profile();
		};

		array_walk(
			$hooks,
			function ( $hook ) use ( $profile_cb ) {
				add_action( $hook, $profile_cb, '-' . PHP_INT_MAX );
				add_action( $hook, $profile_cb, PHP_INT_MAX );
			}
		);

		self::$ran = TRUE;
	}

	/**
	 * Run before and after that any cron action ran, logging it and its performance.
	 */
	private function cron_action_profile() {

		//Todo: we have to check for DOING_CRON again here, as WP CLI defines it right before the action starts…

		$hook = current_filter();
		if ( ! isset( $this->done[ $hook ] ) ) {
			$this->done[ $hook ][ 'start' ] = microtime( TRUE );

			return;
		}

		if ( ! isset( $this->done[ $hook ][ 'duration' ] ) ) {

			$duration = number_format( microtime( TRUE ) - $this->done[ $hook ][ 'start' ], 2 );

			$this->done[ $hook ][ 'duration' ] = $duration . ' s';

			do_action(
				'wonolog.log',
				new Debug( "Cron action \"{$hook}\" performed.", Channels::DEBUG, $this->done[ $hook ] )
			);
		}
	}
}
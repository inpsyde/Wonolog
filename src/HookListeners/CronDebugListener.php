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
final class CronDebugListener implements FilterListenerInterface {

	use ListenerIdByClassNameTrait;

	private static $ran = FALSE;

	/**
	 * @var array
	 */
	private $done = [];

	/**
	 * @return string|string[]
	 */
	public function listen_to() {

		return 'pre_transient_doing_cron';
	}

	/**
	 * Logs all the cron hook performed and their performance.
	 *
	 * @wp-hook  pre_transient_doing_cron
	 *
	 * @param array $args
	 *
	 * @return bool
	 */
	public function filter( array $args ) {

		$doing_cron = $args ? reset( $args ) : FALSE;

		if ( ! defined( 'DOING_CRON' ) || ! DOING_CRON || self::$ran ) {
			return $doing_cron;
		}

		self::$ran = TRUE;

		$cron_array = _get_cron_array();
		
		if (! $cron_array || ! is_array( $cron_array )) {
			return $doing_cron;
		}

		$hooks = array_reduce(
			$cron_array,
			function ( $hooks, $crons ) {

				return array_merge( $hooks, array_keys( $crons ) );
			},
			[]
		);

		if ( empty( $hooks ) ) {
			return $doing_cron;
		}

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

		return $doing_cron;
	}

	/**
	 * Run before and after that any cron action ran, logging it and its performance.
	 */
	private function cron_action_profile() {

		$hook = current_filter();
		if ( ! isset( $this->done[ $hook ] ) ) {
			$this->done[ $hook ][ 'start' ] = microtime( TRUE );

			return;
		}

		if ( ! isset( $this->done[ $hook ][ 'duration' ] ) ) {

			$duration = number_format( microtime( TRUE ) - $this->done[ $hook ][ 'start' ], 2 );

			$this->done[ $hook ][ 'duration' ] = $duration . ' s';

			do_action(
				\Inpsyde\Wonolog\LOG,
				new Debug( "Cron action \"{$hook}\" performed.", Channels::DEBUG, $this->done[ $hook ] )
			);
		}
	}
}
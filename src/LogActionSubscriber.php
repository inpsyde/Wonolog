<?php # -*- coding: utf-8 -*-
/*
 * This file is part of the Inpsyde wonolog package.
 *
 * (c) Inpsyde GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Inpsyde\Wonolog;

use Inpsyde\Wonolog\Data\Debug;
use Inpsyde\Wonolog\Data\Log;
use Inpsyde\Wonolog\Data\LogDataInterface;
use Inpsyde\Wonolog\Exception\InvalidChannelNameException;
use Monolog\Handler\HandlerInterface;
use Monolog\Logger;

/**
 * Main package objects, where "things happen".
 *
 * It is the object that is used to listed to `wonolog.log` actions, build log data from received arguments and
 * pass them to Monolog for the actual logging.
 *
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @package wonolog
 * @license http://opensource.org/licenses/MIT MIT
 */
class LogActionSubscriber {

	/**
	 * @var Channels
	 */
	private $channels;

	/**
	 * @var HandlerInterface|NULL
	 */
	private $default_handler;

	/**
	 * @var string[]
	 */
	private $setup_loggers = [];

	/**
	 * @var LogLevel
	 */
	private $log_level;

	/**
	 * @param Channels         $channels
	 * @param HandlerInterface $default_handler
	 */
	public function __construct( Channels $channels, HandlerInterface $default_handler = NULL ) {

		$this->channels        = $channels;
		$this->default_handler = $default_handler;
		$this->log_level       = new LogLevel();
	}

	/**
	 * @wp-hook wonolog.log
	 * @wp-hook wonolog.log.debug
	 * @wp-hook wonolog.log.info
	 * @wp-hook wonolog.log.notice
	 * @wp-hook wonolog.log.warning
	 * @wp-hook wonolog.log.error
	 * @wp-hook wonolog.log.critical
	 * @wp-hook wonolog.log.alert
	 * @wp-hook wonolog.log.emergency
	 */
	public function listen() {

		if ( ! did_action( 'wonolog.loaded' ) ) {
			return;
		}

		$args = func_get_args();

		// Seems no args were passed, no much we can do
		if ( ! $args ) {
			$log = new Debug( 'Unknown error.', Channels::DEBUG );
			$this->update( $log );

			return;
		}

		$first_arg  = reset( $args );
		$single_arg = count( $args ) === 1;
		$hook_level = $this->hook_level( current_filter() );

		if ( is_string( $first_arg ) && $single_arg ) {
			$log = new Log( $first_arg, ( $hook_level ? : Logger::DEBUG ), Channels::DEBUG );
			$this->update( $log );

			return;
		}

		// If any log data object is found, log all of them.
		$logged = FALSE;
		foreach ( $args as $arg ) {
			if ( $arg instanceof LogDataInterface ) {
				$this->update( $this->maybe_raise_level( $hook_level, $arg ) );
				$logged = TRUE;
			}
		}

		if ( $logged ) {
			return;
		}

		// If the first argument was a WP_Error, use it to build the log
		if ( is_wp_error( $first_arg ) ) {

			$level = ( isset( $args[ 1 ] ) && is_scalar( $args[ 1 ] ) ) ? $args[ 1 ] : Logger::NOTICE;
			$level < $hook_level and $level = $hook_level;
			$channel = ( isset( $args[ 2 ] ) && is_string( $args[ 2 ] ) ) ? $args[ 2 ] : '';

			$this->update( Log::from_wp_error( $first_arg, $level, $channel ) );

			return;
		}

		// If there was just one argument and it was an array, use it to build the log
		if ( is_array( $first_arg ) && $single_arg ) {

			$level = array_key_exists( 'level', $first_arg ) ? $first_arg[ 'level' ] : 0;
			$level < $hook_level and $first_arg[ 'level' ] = $hook_level;

			$this->update( Log::from_array( $first_arg ) );

			return;
		}

		$hook_level and $args[] = $hook_level;

		// If any other thing failed, let's use all received arguments to build the log data object
		$this->update( Log::from_array( $args ) );
	}

	/**
	 * @param LogDataInterface $log
	 *
	 * @return bool
	 */
	public function update( LogDataInterface $log ) {

		if ( ! did_action( 'wonolog.loaded' ) || ! ( $log->level() > 0 ) ) {
			return FALSE;
		}

		$channel = $log->channel();

		try {
			$logger = $this->channels->logger( $channel );

			if ( ! in_array( $channel, $this->setup_loggers, TRUE ) ) {
				$this->setup_loggers[] = $channel;
				$this->setup_logger( $logger );
			}

			$context = $log->context();
			if ( did_action( 'init' ) ) {
				$context[ 'user_logged' ] = is_user_logged_in() ? 'yes' : 'no';
				$context[ 'user_id' ]     = get_current_user_id();
			}

			return $logger->addRecord( $log->level(), $log->message(), $context );

		}
		catch ( InvalidChannelNameException $e ) {

			do_action( 'wonolog.invalid-channel-name', $log );

			return FALSE;
		}
	}

	/**
	 * @param Logger $logger
	 */
	private function setup_logger( Logger $logger ) {

		$name = $logger->getName();

		$this->setup_loggers[ $name ] = $logger;

		/**
		 * Fires before a logger is used first time.
		 * Can be used to setup the logger, for example adding handlers.
		 */
		do_action( 'wonolog.logger', $logger );

		if (
			$this->default_handler
			&& apply_filters( 'wonolog.use-default-handler', TRUE, $logger )
		) {

			$filter = strtolower( "wonolog.default-{$name}-handler" );

			/**
			 * Fires before the default handler is pushed to a logger.
			 * can be used to customize the handler for a specific logger (for example wrapping with some wrapper)
			 * or even to discard default handler for the specific logger passed as second argument.
			 */
			$default_handler = apply_filters( $filter, $this->default_handler, $logger );
			$default_handler instanceof HandlerInterface and $logger->pushHandler( $this->default_handler );
		}
	}

	/**
	 * @param string $current_filter
	 *
	 * @return int
	 */
	private function hook_level( $current_filter ) {

		if ( $current_filter === 'wonolog.log' ) {
			return 0;
		}

		$parts = explode( '.', $current_filter, 3 );
		if ( isset( $parts[ 2 ] ) ) {
			return $this->log_level->check_level( $parts[ 2 ] );
		}

		return Logger::DEBUG;
	}

	/**
	 * @param int              $hook_level
	 * @param LogDataInterface $log
	 *
	 * @return LogDataInterface
	 */
	private function maybe_raise_level( $hook_level, LogDataInterface $log ) {

		if ( $hook_level > $log->level() ) {
			return new Log( $log->message(), $hook_level, $log->channel(), $log->context() );
		}

		return $log;
	}

}
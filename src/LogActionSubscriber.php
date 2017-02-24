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

use Inpsyde\Wonolog\Data\Log;
use Inpsyde\Wonolog\Data\LogDataInterface;
use Inpsyde\Wonolog\Exception\InvalidChannelNameException;
use Monolog\Handler\HandlerInterface;
use Monolog\Logger;

/**
 * Main package object, where "things happen".
 *
 * It is the object that is used to listed to `wonolog.log` actions, build log data from received arguments and
 * pass them to Monolog for the actual logging.
 *
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @package wonolog
 * @license http://opensource.org/licenses/MIT MIT
 */
class LogActionSubscriber {

	const ACTION_LOGGER = 'wonolog.logger';

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
	 * @param array            $processors
	 */
	public function __construct( Channels $channels, HandlerInterface $default_handler = NULL, array $processors = [] ) {

		$this->channels        = $channels;
		$this->default_handler = $default_handler;
		$this->log_level       = new LogLevel();
		$this->processors      = $processors;
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

		if ( ! did_action( FrontController::ACTION_LOADED ) ) {
			return FALSE;
		}

		$args          = func_get_args();
		$first_arg     = $args ? reset( $args ) : NULL;
		$is_single_arg = count( $args ) === 1;
		$hook_level    = $this->hook_level( current_filter() );

		return
			$this->log_undefined_error( $args, $hook_level )
			|| $this->log_data_from_string( $first_arg, $is_single_arg, $hook_level )
			|| $this->log_objects_in_args( $args, $hook_level )
			|| $this->log_wp_error( $first_arg, $args, $hook_level )
			|| $this->log_data_from_array( $first_arg, $is_single_arg, $hook_level );
	}

	/**
	 * @param LogDataInterface $log
	 *
	 * @return bool
	 */
	public function update( LogDataInterface $log ) {

		if ( ! did_action( FrontController::ACTION_LOADED ) || $log->level() < 1 ) {
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

			return $logger->addRecord( $log->level(), $log->message(), $context );

		}
		catch ( InvalidChannelNameException $e ) {

			do_action( Channels::ACTION_INVALID_CHANNEL_NAME, $log );

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
		do_action( self::ACTION_LOGGER, $logger );

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
			$default_handler instanceof HandlerInterface and $logger->pushHandler( $default_handler );
		}

		$processors = (array) apply_filters( 'wonolog.logger-processors', $this->processors, $logger );

		array_walk(
			$processors,
			function ( $processor ) use ( $logger ) {

				is_callable( $processor ) and $logger->pushProcessor( $processor );
			}
		);

	}

	/**
	 * If no args was passed, log something very ambiguous and return;
	 *
	 * @param array $args
	 * @param int   $hook_level
	 *
	 * @return bool
	 */
	private function log_undefined_error( array $args = [], $hook_level ) {

		if ( $args ) {
			return FALSE;
		}

		$log = new Log( 'Unknown error.', $hook_level ? : Logger::DEBUG, Channels::DEBUG );
		$this->update( $log );

		return TRUE;
	}

	/**
	 * If message was sent as only action param, let's assume it is the message, set default, do the log and return.
	 *
	 * @param string $string
	 * @param bool   $is_single_args
	 * @param int    $hook_level
	 *
	 * @return bool
	 */
	private function log_data_from_string( $string, $is_single_args, $hook_level ) {

		if ( ! is_string( $string ) || ! $is_single_args ) {
			return FALSE;
		}

		$log = new Log( $string, ( $hook_level ? : Logger::DEBUG ), Channels::DEBUG );
		$this->update( $log );

		return TRUE;
	}

	/**
	 * If one or more LogData objects are passed as argument, log all of them and return.
	 *
	 * @param array $args
	 * @param int   $hook_level
	 *
	 * @return bool
	 */
	private function log_objects_in_args( array $args, $hook_level ) {

		$logged = FALSE;
		foreach ( $args as $arg ) {
			if ( $arg instanceof LogDataInterface ) {
				$this->update( $this->maybe_raise_level( $hook_level, $arg ) );
				$logged = TRUE;
			}
		}

		return $logged;
	}

	/**
	 * If a WP_Error instance was passed as first argument, let's use it to log.
	 * Look for level and channel in other arguments or use default.
	 *
	 * @param \WP_Error $error
	 * @param array     $args
	 * @param int       $hook_level
	 *
	 * @return bool
	 */
	private function log_wp_error( $error, array $args, $hook_level ) {

		if ( ! is_wp_error( $error ) ) {
			return FALSE;
		}

		$level = ( isset( $args[ 1 ] ) && is_scalar( $args[ 1 ] ) ) ? $args[ 1 ] : Logger::NOTICE;
		$level = $this->log_level->check_level( $level );
		$level < $hook_level and $level = $hook_level;
		$channel = ( isset( $args[ 2 ] ) && is_string( $args[ 2 ] ) ) ? $args[ 2 ] : '';

		$this->update( Log::from_wp_error( $error, $level, $channel ) );

		return TRUE;
	}

	/**
	 * If there was just one argument and it was an array, build the log from it.
	 *
	 * @param array $array
	 * @param bool  $is_single_arg
	 * @param int   $hook_level
	 *
	 * @return bool
	 */
	private function log_data_from_array( $array, $is_single_arg, $hook_level ) {

		if ( ! is_array( $array ) || ! $is_single_arg ) {
			return FALSE;
		}

		$level = array_key_exists( 'level', $array ) ? $array[ 'level' ] : 0;
		$level < $hook_level and $first_arg[ 'level' ] = $hook_level;

		$this->update( Log::from_array( $array ) );

		return TRUE;
	}

	/**
	 * @param string $current_filter
	 *
	 * @return int
	 */
	private function hook_level( $current_filter ) {

		if ( $current_filter === LOG ) {
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
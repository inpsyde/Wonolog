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
	 * @param Channels         $channels
	 * @param HandlerInterface $default_handler
	 */
	public function __construct( Channels $channels, HandlerInterface $default_handler = NULL ) {

		$this->channels        = $channels;
		$this->default_handler = $default_handler;
	}

	/**
	 * @param array ...$args
	 *
	 * @wp-hook wonolog.log
	 */
	public function listen( ...$args ) {

		if ( ! did_action( 'wonolog.loaded' ) ) {
			return;
		}

		// Seems no args were passed, no much we can do
		if ( ! $args ) {
			$log = new Debug( 'Unknown error.', Channels::DEBUG );
			$this->update( $log );

			return;
		}

		$first_arg  = reset( $args );
		$single_arg = count( $args ) === 1;

		if ( is_string( $first_arg ) && $single_arg ) {
			$log = new Debug( $first_arg, Channels::DEBUG );
			$this->update( $log );

			return;
		}

		// If any log data object is found, log all of them.
		$logged = FALSE;
		foreach ( $args as $arg ) {
			if ( $arg instanceof LogDataInterface ) {
				$this->update( $arg );
				$logged = TRUE;
			}
		}

		if ( $logged ) {
			return;
		}

		// If the first argument was a WP_Error, use it to build the log
		if ( is_wp_error( $first_arg ) ) {

			$level   = ( isset( $args[ 1 ] ) && is_scalar( $args[ 1 ] ) ) ? $args[ 1 ] : Logger::NOTICE;
			$channel = ( isset( $args[ 2 ] ) && is_string( $args[ 2 ] ) ) ? $args[ 2 ] : '';

			$this->update( Log::from_wp_error( $first_arg, $level, $channel ) );

			return;
		}

		// If there was just one argument and it was an array, use it to build the log
		if ( is_array( $first_arg ) && $single_arg ) {
			$this->update( Log::from_array( $first_arg ) );

			return;
		}

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

}
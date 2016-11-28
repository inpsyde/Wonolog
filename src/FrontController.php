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

use Inpsyde\Wonolog\Data\LogDataInterface;
use Inpsyde\Wonolog\HookListeners\ActionListenerInterface;
use Inpsyde\Wonolog\HookListeners\FilterListenerInterface;
use Inpsyde\Wonolog\HookListeners\HookListenerInterface;
use Inpsyde\Wonolog\HookListeners\HookPriorityInterface;
use Monolog\Handler\HandlerInterface;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

/**
 * "Entry point" for package bootstrapping.
 *
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @package wonolog
 * @license http://opensource.org/licenses/MIT MIT
 */
class FrontController {

	/**
	 * @var HandlerInterface
	 */
	private $default_handler;

	/**
	 * Bootstrap the package once per request.
	 */
	public static function boot() {

		$instance = new static();
		$instance->setup();
	}

	/**
	 * FrontController constructor.
	 *
	 * @param HandlerInterface $default_handler
	 */
	public function __construct( HandlerInterface $default_handler = NULL ) {

		$this->default_handler = $default_handler;
	}

	/**
	 * Initialize the package object.
	 */
	public function setup() {

		if ( did_action( 'wonolog.loaded' ) || ! apply_filters( 'wonolog.enable', TRUE ) ) {
			return;
		}

		do_action( 'wonolog.setup' );

		$this->setup_php_error_handler();

		$listener = [ new LogActionSubscriber( new Channels(), $this->setup_default_handler() ), 'listen' ];

		add_action( 'wonolog.log', $listener, 100, 9999 );

		foreach ( Logger::getLevels() as $level => $level_code ) {
			add_action( 'wonolog.log.' . strtolower( $level ), $listener, 100, 9999 );
		}

		$hook_listeners_registry = new HookListenersRegistry();
		do_action( 'wonolog.register-listeners', $hook_listeners_registry );
		$this->setup_hook_listeners( $hook_listeners_registry );
		$hook_listeners_registry->flush();

		do_action( 'wonolog.loaded' );
	}

	/**
	 * Initialize PHP error handler.
	 */
	private function setup_php_error_handler() {

		if ( ! apply_filters( 'wonolog.enable-php-error-handler', TRUE ) ) {
			return;
		}

		$handler = new PhpErrorController();
		$handler->init();

		// Ensure that CHANNEL_PHP_ERROR error is there
		add_filter(
			'wonolog.channels',
			function ( array $channels ) {

				$channels[] = PhpErrorController::CHANNEL;

				return $channels;
			},
			PHP_INT_MAX
		);
	}

	/**
	 * Setup default handler.
	 *
	 * @return HandlerInterface|NULL
	 */
	private function setup_default_handler() {

		$default_handler = apply_filters( 'wonolog.default-handler', NULL );
		if ( $default_handler instanceof HandlerInterface || $default_handler === FALSE ) {
			$default_handler and $this->default_handler = $default_handler;

			return NULL;
		}

		$folder = getenv( 'WONOLOG_HANDLER_FILE_DIR' ) ? : trailingslashit( WP_CONTENT_DIR ) . 'wonolog';
		$folder = apply_filters( 'wonolog.default-handler-folder', $folder );

		$default_format = 'Y/m-d';
		$name_format    = apply_filters( 'wonolog.default-handler-name-format', $default_format );
		( is_string( $name_format ) && $name_format ) or $name_format = $default_format;
		$filename = date( $name_format );
		pathinfo( $filename, PATHINFO_EXTENSION ) or $filename .= '.log';

		$fullpath = "{$folder}/{$filename}";
		$fullpath = apply_filters( 'wonolog.default-handler-filepath', $fullpath );

		$dir = dirname( $fullpath );

		if ( ! wp_mkdir_p( $dir ) ) {
			return NULL;
		}

		$log_level = LogLevel::instance();

		return new StreamHandler( $fullpath, $log_level->default_level() );
	}

	/**
	 * Setup registered hook listeners.
	 *
	 * @param HookListenersRegistry $listeners
	 */
	private function setup_hook_listeners( HookListenersRegistry $listeners ) {

		$hook_listeners = $listeners->listeners();

		array_walk( $hook_listeners, [ $this, 'setup_hook_listener' ] );
	}

	/**
	 * @param HookListenerInterface $listener
	 */
	private function setup_hook_listener( HookListenerInterface $listener ) {

		$hooks = (array) $listener->listen_to();

		array_walk( $hooks, [ $this, 'listen_hook' ], $listener );
	}

	/**
	 * @param string                $hook
	 * @param int                   $i
	 * @param HookListenerInterface $listener
	 */
	private function listen_hook( $hook, $i, HookListenerInterface $listener ) {

		$is_filter = $listener instanceof FilterListenerInterface;
		if ( ! $is_filter && ! $listener instanceof ActionListenerInterface ) {
			return;
		}

		/**
		 * @return null
		 *
		 * @var FilterListenerInterface|ActionListenerInterface $listener
		 * @var bool                                            $is_filter
		 */
		$callback = function () use ( $listener, $is_filter ) {

			$args = func_get_args();
			$log  = $listener->update( $args );
			$log instanceof LogDataInterface and do_action( 'wonolog.log', $log );

			return $is_filter ? $listener->filter( $args ) : NULL;
		};

		$priority = PHP_INT_MAX - 10;
		/* @var HookPriorityInterface $listener */
		$listener instanceof HookPriorityInterface and $priority = (int) $listener->priority();

		$is_filter
			? add_filter( $hook, $callback, $priority, 9999 )
			: add_action( $hook, $callback, $priority, 9999 );
	}

}
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

use Inpsyde\Wonolog\HookListeners\HookListenerInterface;

/**
 * Registry for hook listeners.
 *
 * @author  Giuseppe Mazzapica <giuseppe.mazzapica@gmail.com>
 * @package wonolog
 * @license http://opensource.org/licenses/MIT MIT
 */
class HookListenersRegistry {

	const DEFAULT_LISTENERS = [
		HookListeners\DbErrorListener::class,
		HookListeners\FailedLoginListener::class,
		HookListeners\HttpApiListener::class,
		HookListeners\QueryErrorsListener::class,
		HookListeners\CronDebugListener::class,
		HookListeners\WpDieHandlerListener::class,
	];

	/**
	 * @var bool
	 */
	private $setup = FALSE;

	/**
	 * @var callable[]
	 */
	private $factories = [];

	/**
	 * @var HookListenerInterface[]
	 */
	private $listeners = [];

	/**
	 * @param HookListenerInterface $listener
	 *
	 * @return HookListenersRegistry
	 */
	public function register_listener( HookListenerInterface $listener ) {

		$class = get_class( $listener );

		array_key_exists( $class, $this->listeners ) or $this->listeners[ $class ] = $listener;

		return $this;
	}

	/**
	 * @param callable $listener_factory
	 *
	 * @return HookListenersRegistry
	 */
	public function register_listener_factory( callable $listener_factory ) {

		$this->factories[] = $listener_factory;

		return $this;
	}

	/**
	 * Return all registered listeners, maybe constructing them from factories.
	 *
	 * @return HookListeners\HookListenerInterface[]
	 */
	public function listeners() {

		$this->init_default_listeners();

		if ( ! $this->factories ) {
			return array_values( $this->listeners );
		}

		foreach ( $this->factories as $factory ) {
			$listener = $factory();
			$listener instanceof HookListenerInterface and $this->register_listener( $listener );
		}

		unset( $this->factories );
		$this->factories = [];

		return array_values( $this->listeners );
	}

	/**
	 * Cleanup class properties.
	 */
	public function flush() {

		if ( did_action( 'wonolog.loaded' ) ) {
			unset( $this->factories, $this->listeners );
			$this->factories = [];
			$this->listeners = [];
		}

	}

	/**
	 * Initialize default listeners.
	 */
	private function init_default_listeners() {

		if ( $this->setup ) {
			return;
		}

		$this->setup = TRUE;

		foreach ( self::DEFAULT_LISTENERS as $class ) {
			/** @var HookListenerInterface $listener */
			$listener = new $class();
			if ( apply_filters( 'wonolog.hook-listener-enabled', TRUE, $listener ) ) {
				$this->register_listener( $listener );
			}
		}
	}
}
<?php

/**
 * This file is part of the Wonolog package.
 *
 * (c) Inpsyde GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Inpsyde\Wonolog;

use Inpsyde\Wonolog\HookListener\ActionListener;
use Inpsyde\Wonolog\HookListener\FilterListener;
use Inpsyde\Wonolog\HookListener\HookListener;

class HookListenersRegistry
{
    /**
     * @var array<string, array{FilterListener|ActionListener, int|null}>
     */
    private $listeners = [];

    /**
     * @var bool
     */
    private $booted = false;

    /**
     * @var LogActionUpdater
     */
    private $updater;

    /**
     * @param LogActionUpdater $updater
     * @return HookListenersRegistry
     */
    public static function new(LogActionUpdater $updater): HookListenersRegistry
    {
        return new self($updater);
    }

    /**
     * @param LogActionUpdater $subscriber
     */
    private function __construct(LogActionUpdater $updater)
    {
        $this->updater = $updater;
    }

    /**
     * @param int $defaultPriority
     * @return void
     */
    public function listenAll(int $defaultPriority): void
    {
        if ($this->booted) {
            return;
        }

        $this->booted = true;

        /** @var HookListener $listener */
        foreach ($this->listeners as [$listener, $priority]) {
            $hooks = $listener->listenTo();
            foreach ($hooks as $hook) {
                $this->listenHook($hook, $priority ?? $defaultPriority, $listener);
            }
        }
    }

    /**
     * @param ActionListener $listener
     * @return HookListenersRegistry
     */
    public function addActionListener(
        string $identifier,
        ActionListener $listener
    ): HookListenersRegistry {

        if (!isset($this->listeners[$identifier])) {
            $this->listeners[$identifier] = [$listener, null];
        }

        return $this;
    }

    /**
     * @param FilterListener $listener
     * @return HookListenersRegistry
     */
    public function addFilterListener(
        string $identifier,
        FilterListener $listener
    ): HookListenersRegistry {

        if (!isset($this->listeners[$identifier])) {
            $this->listeners[$identifier] = [$listener, null];
        }

        return $this;
    }

    /**
     * @param string $identifier
     * @param ActionListener $listener
     * @param int $priority
     * @return $this
     */
    public function addActionListenerWithPriority(
        string $identifier,
        ActionListener $listener,
        int $priority
    ): HookListenersRegistry {

        if (!isset($this->listeners[$identifier])) {
            $this->listeners[$identifier] = [$listener, $priority];
        }

        return $this;
    }

    /**
     * @param string $identifier
     * @param FilterListener $listener
     * @param int $priority
     * @return $this
     */
    public function addFilterListenerWithPriority(
        string $identifier,
        FilterListener $listener,
        int $priority
    ): HookListenersRegistry {

        if (!isset($this->listeners[$identifier])) {
            $this->listeners[$identifier] = [$listener, $priority];
        }

        return $this;
    }

    /**
     * @param HookListener $listener
     * @return HookListenersRegistry
     */
    public function removeListener(string $identifier): HookListenersRegistry
    {
        unset($this->listeners[$identifier]);

        return $this;
    }

    /**
     * @param HookListener $listener
     * @return bool
     */
    public function hasListener(string $identifier): bool
    {
        return !empty($this->listeners[$identifier]);
    }

    /**
     * @param string $hook
     * @param HookListener $listener
     * @return void
     */
    private function listenHook(string $hook, int $priority, HookListener $listener): void
    {
        $callback = $this->hookCallback($listener, $hook);
        $listener instanceof FilterListener
            ? add_filter($hook, $callback, $priority, PHP_INT_MAX)
            : add_action($hook, $callback, $priority, PHP_INT_MAX);
    }

    /**
     * @param HookListener $listener
     * @param string $hook
     * @return callable
     */
    private function hookCallback(HookListener $listener, string $hook): callable
    {
        /**
         * @param array $args
         * @return mixed|null
         *
         * @wp-hook
         */
        return function (...$args) use ($listener, $hook) {
            if ($listener instanceof ActionListener) {
                /** @var ActionListener $listener */
                $listener->update($hook, $args, $this->updater);
            } elseif ($listener instanceof FilterListener) {
                return $listener->filter($hook, $args, $this->updater);
            }

            return null;
        };
    }
}

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

namespace Inpsyde\Wonolog\HookListener;

use Inpsyde\Wonolog\Data\LogDataInterface;

/**
 * Registry for hook listeners.
 *
 * @package wonolog
 * @license http://opensource.org/licenses/MIT MIT
 */
class HookListenersRegistry
{
    public const ACTION_REGISTER = 'wonolog.register-listeners';
    public const FILTER_ENABLED = 'wonolog.hook-listener-enabled';
    public const FILTER_PRIORITY = 'wonolog.listened-hook-priority';

    /**
     * @var array<HookListenerInterface>
     */
    private $listeners = [];

    /**
     * Initialize the class, fire an hook to allow listener registration and adds the hook that will
     * make log happen.
     */
    public static function initialize(): void
    {
        $instance = new static();

        /**
         * Fires right before hook listeners are registered.
         *
         * @param HookListenersRegistry $registry
         */
        do_action(self::ACTION_REGISTER, $instance);

        array_walk(
            $instance->listeners,
            static function (HookListenerInterface $listener) use ($instance) {
                /**
                 * Filters whether to enable the hook listener.
                 *
                 * @param bool $enable
                 * @param HookListenerInterface $listener
                 */
                if (!apply_filters(self::FILTER_ENABLED, true, $listener)) {
                    return;
                }

                $hooks = $listener->listenTo();
                foreach ($hooks as $hook) {
                    $instance->listenHook($hook, $listener);
                }
            }
        );

        unset($instance->listeners);
        $instance->listeners = [];
    }

    /**
     * @param HookListenerInterface $listener
     * @return HookListenersRegistry
     */
    public function registerListener(HookListenerInterface $listener): HookListenersRegistry
    {
        $id = (string)$listener->id();

        array_key_exists($id, $this->listeners) or $this->listeners[$id] = $listener;

        return $this;
    }

    /**
     * Return all registered listeners.
     *
     * @return array<HookListenerInterface>
     */
    public function listeners(): array
    {
        return array_values($this->listeners);
    }

    /**
     * @param string $hook
     * @param HookListenerInterface $listener
     * @return bool
     */
    private function listenHook(string $hook, HookListenerInterface $listener): bool
    {
        [$callback, $isFilter] = $this->hookCallback($listener, $hook);
        if (!$callback) {
            return false;
        }

        $priority = $listener instanceof HookPriorityInterface
            ? (int)$listener->priority()
            : PHP_INT_MAX - 10;

        /**
         * Filters the hook listener priority.
         *
         * @param int $priority
         * @param string $hook
         * @param HookListenerInterface $listener
         */
        $filtered = apply_filters(self::FILTER_PRIORITY, $priority, $hook, $listener);
        is_numeric($filtered) and $priority = (int)$filtered;

        return $isFilter
            ? add_filter($hook, $callback, $priority, PHP_INT_MAX)
            : add_action($hook, $callback, $priority, PHP_INT_MAX);
    }

    /**
     * @param HookListenerInterface $listener
     * @param string $hook
     *
     * @return array{0:callable|null, 1:bool|null}
     */
    private function hookCallback(HookListenerInterface $listener, string $hook): array
    {
        $isFilter = $listener instanceof FilterListenerInterface;
        $isAction = $listener instanceof ActionListenerInterface;

        if (!$isFilter && !$isAction) {
            return [null, null];
        }

        /**
         * @param array $args
         *
         * @return mixed|null
         *
         * @wp-hook
         */
        $callback = static function (...$args) use ($listener, $hook, $isFilter) {
            if (!$isFilter) {
                /** @var ActionListenerInterface $listener */
                do_action(\Inpsyde\Wonolog\LOG, $listener->update($hook, $args));

                return null;
            }

            /** @var FilterListenerInterface $listener */

            return $listener->filter($hook, $args);
        };

        return [$callback, $isFilter];
    }
}

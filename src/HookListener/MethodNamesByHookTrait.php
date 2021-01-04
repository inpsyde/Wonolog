<?php

declare(strict_types=1);

namespace Inpsyde\Wonolog\HookListener;

use Inpsyde\Wonolog\Data\LogData;
use Inpsyde\Wonolog\LogActionUpdater;

trait MethodNamesByHookTrait
{
    /**
     * @var string
     */
    private $prefix = '';

    /**
     * @param string $prefix
     * @return void
     */
    public function withHookPrefix(string $prefix): void
    {
        $this->prefix = $prefix;
    }

    /**
     * @param string $hook
     * @param array $args
     * @param LogActionUpdater $subscriber
     * @return void
     */
    public function update(string $hook, array $args, LogActionUpdater $subscriber): void
    {
        $method = $this->methodNameByHook($hook);
        if ($method) {
            $log = $method(...$args);
            if ($log instanceof LogData) {
                $subscriber->update($log);
            }
        }
    }

    /**
     * @param string $hook
     * @return callable|null
     */
    private function methodNameByHook(string $hook): ?callable
    {
        if ($this->prefix && stripos($hook, $this->prefix) === 0) {
            $hook = substr($hook, strlen($this->prefix));
        }

        if (is_callable([$this, $hook])) {
            return [$this, $hook];
        }

        $hookLower = strtolower($hook);
        if (($hookLower !== $hook) && is_callable([$this, $hookLower])) {
            return [$this, $hookLower];
        }

        $parts = preg_split('~[^a-z0-9]+~', $hookLower, -1, PREG_SPLIT_NO_EMPTY);

        $snakeMethod = implode('_', $parts);
        if (is_callable([$this, $snakeMethod])) {
            return [$this, $snakeMethod];
        }

        $camelMethod = array_shift($parts) . implode('', array_map('ucfirst', $parts));

        return is_callable([$this, $camelMethod]) ? [$this, $camelMethod] : null;
    }
}

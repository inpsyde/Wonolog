<?php

declare(strict_types=1);

namespace Inpsyde\Wonolog\HookListener;

use Inpsyde\Wonolog\LogActionUpdater;

trait FilterFromUpdateTrait
{
    /**
     * @param string $hook
     * @param array<mixed> $args
     * @param LogActionUpdater $updater
     * @return void
     */
    abstract public function update(string $hook, array $args, LogActionUpdater $updater): void;

    /**
     * @param string $hook
     * @param array<mixed> $args
     * @param LogActionUpdater $updater
     * @return mixed
     */
    public function filter(string $hook, array $args, LogActionUpdater $updater): mixed
    {
        $value = $args ? reset($args) : null;
        $this->update($hook, $args, $updater);

        return $value;
    }
}

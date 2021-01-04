<?php

declare(strict_types=1);

namespace Inpsyde\Wonolog\HookListener;

use Inpsyde\Wonolog\LogActionUpdater;

/**
 * @method update(string $hook, array $args, LogActionUpdater $updater)
 */
trait FilterFromUpdateTrait
{
    /**
     * @param string $hook
     * @param array $args
     * @param LogActionUpdater $updater
     * @return mixed
     */
    public function filter(string $hook, array $args, LogActionUpdater $updater)
    {
        $value = $args ? reset($args) : null;
        $this->update($hook, $args, $updater);

        return $value;
    }
}

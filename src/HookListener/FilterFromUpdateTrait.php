<?php

declare(strict_types=1);

namespace Inpsyde\Wonolog\HookListener;

use Inpsyde\Wonolog\LogActionUpdater;

/**
 * @method update(string $hook, array $args, LogActionUpdater $subscriber)
 */
trait FilterFromUpdateTrait
{
    /**
     * @param string $hook
     * @param array $args
     * @param LogActionUpdater $subscriber
     * @return mixed
     */
    public function filter(string $hook, array $args, LogActionUpdater $subscriber) {

        $value = $args ? reset($args) : null;
        $this->update($hook, $args, $subscriber);

        return $value;
    }
}

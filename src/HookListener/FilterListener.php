<?php

declare(strict_types=1);

namespace Inpsyde\Wonolog\HookListener;

use Inpsyde\Wonolog\LogActionUpdater;

interface FilterListener extends HookListener
{
    /**
     * @param string $hook
     * @param array $args
     * @param LogActionUpdater $updater
     * @return mixed
     */
    public function filter(string $hook, array $args, LogActionUpdater $updater);
}

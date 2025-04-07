<?php

declare(strict_types=1);

namespace Inpsyde\Wonolog\HookListener;

use Inpsyde\Wonolog\LogActionUpdater;

interface ActionListener extends HookListener
{
    /**
     * @param string $hook
     * @param array $args
     * @param LogActionUpdater $updater
     * @return void
     */
    public function update(string $hook, array $args, LogActionUpdater $updater): void;
}

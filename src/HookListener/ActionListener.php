<?php

/**
 * This file is part of the Wonolog package.
 *
 * (c) Inpsyde GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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

<?php

declare(strict_types=1);

namespace Inpsyde\Wonolog\HookListener;

use Inpsyde\Wonolog\Data\FailedLogin;
use Inpsyde\Wonolog\LogActionUpdater;

/**
 * Listens to failed login attempts and logs them.
 */
final class FailedLoginListener implements ActionListener
{
    /**
     * @return array<string>
     */
    public function listenTo(): array
    {
        return ['wp_login_failed'];
    }

    /**
     * @param string $hook
     * @param array $args
     * @param LogActionUpdater $updater
     * @return void
     *
     * @wp-hook wp_login_failed
     * @see     FailedLogin
     */
    public function update(string $hook, array $args, LogActionUpdater $updater): void
    {
        $username = $args ? reset($args) : 'Unknown user';

        $updater->update(new FailedLogin((string) $username));
    }
}

<?php

declare(strict_types=1);

namespace Inpsyde\Wonolog\HookListener;

use Inpsyde\Wonolog\Data\FailedLogin;
use Inpsyde\Wonolog\LogActionUpdater;
use Inpsyde\Wonolog\Serializer;

/**
 * Listens to failed login attempts and logs them.
 */
final class FailedLoginListener implements ActionListener
{
    /**
     * @return list<string>
     */
    public function listenTo(): array
    {
        return ['wp_login_failed'];
    }

    /**
     * @param string $hook
     * @param array<mixed> $args
     * @param LogActionUpdater $updater
     * @return void
     *
     * @wp-hook wp_login_failed
     * @see FailedLogin
     */
    public function update(string $hook, array $args, LogActionUpdater $updater): void
    {
        $username = $args ? reset($args) : 'Unknown user';
        if (!is_scalar($username)) {
            $username = Serializer::serializeMessage($username);
        }

        $updater->update(new FailedLogin((string) $username));
    }
}

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

use Inpsyde\Wonolog\Data\FailedLogin;
use Inpsyde\Wonolog\Data\LogDataInterface;

/**
 * Listens to failed login attempts and logs them.
 *
 * @package wonolog
 * @license http://opensource.org/licenses/MIT MIT
 */
final class FailedLoginListener implements ActionListenerInterface
{
    use ListenerIdByClassNameTrait;

    /**
     * @return array<string>
     */
    public function listenTo(): array
    {
        return ['wp_login_failed'];
    }

    /**
     * Logs failed login attempts.
     *
     * @param string $hook
     * @param array $args
     *
     * @return LogDataInterface
     *
     * @wp-hook wp_login_failed
     * @see \Inpsyde\Wonolog\Data\FailedLogin
     */
    public function update(string $hook, array $args): LogDataInterface
    {
        $username = $args ? reset($args) : 'Unknown user';

        return new FailedLogin($username);
    }
}

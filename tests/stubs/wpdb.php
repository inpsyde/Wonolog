<?php // phpcs:disable PSR1

/**
 * This file is part of the Wonolog package.
 *
 * (c) Inpsyde GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

// phpcs:disable Inpsyde.CodeQuality.ForbiddenPublicProperty
// phpcs:disable Inpsyde.CodeQuality.ArgumentTypeDeclaration
// phpcs:disable Inpsyde.CodeQuality.ReturnTypeDeclaration

declare(strict_types=1);

use Inpsyde\Wonolog\HookListener\WpDieHandlerListener;
use Inpsyde\Wonolog\LogActionUpdater;

if (class_exists('wpdb')) {
    return;
}

class wpdb // phpcs:ignore
{
    /**
     * @var WpDieHandlerListener
     */
    public $wp_die_listener;

    /**
     * @var LogActionUpdater
     */
    public $logActionUpdater;

    /**
     * @param string $message
     * @param string $code
     * @return string
     */
    public function bail($message, $code = '500')
    {
        $handler = $this->execute_die_listener();

        return $handler($message, 'Bail');
    }

    /**
     * @param string $message
     * @return string
     */
    public function print_error($message = '')
    {
        $handler = $this->execute_die_listener();

        return $handler($message, 'Bail');
    }

    /**
     * @return callable
     */
    private function execute_die_listener()
    {
        $handler = static function ($message): string {
            return "Handled: $message";
        };

        return $this->wp_die_listener->filter('a', [$handler], $this->logActionUpdater);
    }
}

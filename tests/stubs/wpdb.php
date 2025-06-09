<?php

// phpcs:disable PSR1
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
    public WpDieHandlerListener $wp_die_listener;
    public LogActionUpdater $logActionUpdater;

    /**
     * @param string $message
     * @param string $code
     * @return string
     */
    public function bail($message, $code = '500')
    {
        $handler = $this->execute_die_listener();

        return $handler($message);
    }

    /**
     * @param string $message
     * @return string
     */
    public function print_error($message = '')
    {
        $handler = $this->execute_die_listener();

        return $handler($message);
    }

    /**
     * @return callable(string): string
     */
    private function execute_die_listener(): callable
    {
        $handler = static function (string $message): string {
            return "Handled: {$message}";
        };

        return $this->wp_die_listener->filter('a', [$handler], $this->logActionUpdater);
    }
}

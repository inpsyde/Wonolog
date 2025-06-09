<?php

declare(strict_types=1);

// phpcs:disable PSR1

use Inpsyde\Wonolog\HookListener\WpDieHandlerListener;
use Inpsyde\Wonolog\LogActionUpdater;

if (class_exists('wpdb')) {
    return;
}

class wpdb
{
    public WpDieHandlerListener $wp_die_listener;
    public LogActionUpdater $logActionUpdater;

    /**
     * @param string $message
     * @param string $code
     * @return string
     */
    public function bail(string $message, string $code = '500'): string
    {
        $handler = $this->execute_die_listener();

        return $handler($message);
    }

    /**
     * @param string $message
     * @return string
     */
    public function print_error(string $message = ''): string
    {
        $handler = $this->execute_die_listener();

        return $handler($message);
    }

    /**
     * @return callable(string):string
     */
    private function execute_die_listener(): callable
    {
        $handler = static function (string $message): string {
            return "Handled: {$message}";
        };

        return $this->wp_die_listener->filter('a', [$handler], $this->logActionUpdater);
    }
}

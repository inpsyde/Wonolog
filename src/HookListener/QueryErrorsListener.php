<?php

declare(strict_types=1);

namespace Inpsyde\Wonolog\HookListener;

use Inpsyde\Wonolog\Channels;
use Inpsyde\Wonolog\Data\Log;
use Inpsyde\Wonolog\LogActionUpdater;
use Inpsyde\Wonolog\LogLevel;

/**
 * Looks at fronted requests and to find and log any errors.
 */
final class QueryErrorsListener implements ActionListener
{
    private int $logLevel;

    /**
     * @param int $logLevel
     */
    public function __construct(int $logLevel = LogLevel::DEBUG)
    {
        $this->logLevel = LogLevel::normalizeLevel($logLevel) ?? LogLevel::DEBUG;
    }

    /**
     * @return array<string>
     */
    public function listenTo(): array
    {
        return ['wp'];
    }

    /**
     * Checks frontend request for any errors and log them.
     *
     * @param string $hook
     * @param array $args
     * @param LogActionUpdater $updater
     * @return void
     *
     * @wp-hook wp
     */
    public function update(string $hook, array $args, LogActionUpdater $updater): void
    {
        $wp = $args ? reset($args) : null;

        if (!$wp instanceof \WP) {
            return;
        }

        $error = [];
        if (isset($wp->query_vars['error'])) {
            $error[] = $wp->query_vars['error'];
        }

        if (is_404()) {
            $error[] = '404 Page not found';
        }

        if (empty($error)) {
            return;
        }

        $url = filter_var(add_query_arg([]), FILTER_SANITIZE_URL);
        $message = "Error on frontend request for url {$url}.";
        $context = [
            'error' => $error,
            'query_vars' => $wp->query_vars,
            'matched_rule' => $wp->matched_rule,
        ];

        $updater->update(new Log($message, $this->logLevel, Channels::HTTP, $context));
    }
}

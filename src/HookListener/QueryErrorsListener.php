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

use Inpsyde\Wonolog\Channels;
use Inpsyde\Wonolog\Data\Log;
use Inpsyde\Wonolog\LogActionUpdater;
use Inpsyde\Wonolog\LogLevel;

/**
 * Looks at fronted requests and to find and log any errors.
 */
final class QueryErrorsListener implements ActionListener
{
    /**
     * @var int
     */
    private $logLevel;

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
        isset($wp->query_vars['error']) and $error[] = $wp->query_vars['error'];
        is_404() and $error[] = '404 Page not found';

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

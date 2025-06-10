<?php

declare(strict_types=1);

namespace Inpsyde\Wonolog\HookListener;

use Inpsyde\Wonolog\Channels;
use Inpsyde\Wonolog\Data\Log;
use Inpsyde\Wonolog\LogActionUpdater;
use Inpsyde\Wonolog\LogLevel;

/**
 * At the end of any request looks for database errors and logs them if found.
 */
final class DbErrorListener implements ActionListener
{
    private int $logLevel;

    /**
     * @param int $logLevel
     */
    public function __construct(int $logLevel = LogLevel::ERROR)
    {
        $this->logLevel = LogLevel::normalizeLevel($logLevel) ?? LogLevel::ERROR;
    }

    /**
     * @return list<string>
     */
    public function listenTo(): array
    {
        return ['shutdown'];
    }

    /**
     * Most db errors can't be caught up before request exit.
     * This method runs on shutdown and look if there are errors in `$EZSQL_ERROR`
     * global var and log them if so.
     *
     * @param string $hook
     * @param array<mixed> $args
     * @param LogActionUpdater $updater
     * @return void
     *
     * @wp-hook shutdown
     */
    public function update(string $hook, array $args, LogActionUpdater $updater): void
    {
        // phpcs:disable Syde.NamingConventions.VariableName
        global $EZSQL_ERROR;
        if (empty($EZSQL_ERROR)) {
            return;
        }

        /** @var non-empty-array<array> $errors */
        $errors = $EZSQL_ERROR;
        // phpcs:enable Syde.NamingConventions.VariableName

        $last = end($errors);
        $message = is_string($last['error_str'] ?? null) ? $last['error_str'] : 'DB error.';
        $context = ['last_wpdb_query' => $last['query'] ?? '', 'last_wpdb_errors' => $errors];

        $updater->update(new Log($message, $this->logLevel, Channels::DB, $context));
    }
}

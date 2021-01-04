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
 * At the end of any request looks for database errors and logs them if found.
 */
final class DbErrorListener implements ActionListener
{
    /**
     * @var int
     */
    private $logLevel;

    /**
     * @param int $logLevel
     */
    public function __construct(int $logLevel = LogLevel::ERROR)
    {
        $this->logLevel = LogLevel::normalizeLevel($logLevel) ?? LogLevel::ERROR;
    }

    /**
     * @return array<string>
     */
    public function listenTo(): array
    {
        return ['shutdown'];
    }

    /**
     * Most db errors can't be caught up before request exit.
     * This method runs on shutdown and look if there're errors in `$EZSQL_ERROR`
     * global var and log them if so.
     *
     * @param string $hook
     * @param array $args
     * @param LogActionUpdater $updater
     * @return void
     *
     * @wp-hook shutdown
     */
    public function update(string $hook, array $args, LogActionUpdater $updater): void
    {
        // phpcs:disable Inpsyde.CodeQuality.VariablesName.SnakeCaseVar
        global $EZSQL_ERROR;
        if (empty($EZSQL_ERROR)) {
            return;
        }

        /** @var non-empty-array<array> $errors */
        $errors = $EZSQL_ERROR;
        // phpcs:enable Inpsyde.CodeQuality.VariablesName.SnakeCaseVar

        $last = end($errors);
        $message = isset($last['error_str']) ? (string)$last['error_str'] : 'DB error.';
        $context = ['last_wpdb_query' => $last['query'] ?? '', 'last_wpdb_errors' => $errors];

        $updater->update(new Log($message, $this->logLevel, Channels::DB, $context));
    }
}

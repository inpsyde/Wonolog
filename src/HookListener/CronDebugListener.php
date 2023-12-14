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

final class CronDebugListener implements ActionListener
{
    /**
     * @var bool
     */
    private static $ran = false;

    /**
     * @var int
     */
    private $logLevel;

    /**
     * @var array<string, array{float, string|null}>
     */
    private $done = [];

    /**
     * @param int $logLevel
     */
    public function __construct(int $logLevel = LogLevel::INFO)
    {
        $this->logLevel = LogLevel::normalizeLevel($logLevel) ?? LogLevel::INFO;
    }

    /**
     * @return array<string>
     */
    public function listenTo(): array
    {
        return ['wp_loaded'];
    }

    /**
     * Logs all the cron hook performed and their performance.
     *
     * @param string $hook
     * @param array $args
     * @param LogActionUpdater $updater
     * @return void
     *
     * @wp-hook wp_loaded
     */
    public function update(string $hook, array $args, LogActionUpdater $updater): void
    {
        if (!self::$ran && (wp_doing_cron() || (defined('WP_CLI') && WP_CLI))) {
            $this->registerEventListener($updater);
        }

        self::$ran = true;
    }

    /**
     * Logs all the cron hook performed and their performance.
     *
     * @param LogActionUpdater $updater
     * @return void
     */
    private function registerEventListener(LogActionUpdater $updater): void
    {
        if (!wp_doing_cron()) {
            return;
        }

        $cronArray = _get_cron_array();
        /** @psalm-suppress TypeDoesNotContainType,DocblockTypeContradiction  */
        if (!$cronArray || !is_array($cronArray)) {
            return;
        }

        foreach ($cronArray as $cronData) {
            $this->registerEventListenerForHooks((array)$cronData, $updater);
        }

        register_shutdown_function(
            function () use ($updater) {
                $this->logUnfinishedHooks($updater);
            }
        );
    }

    /**
     * @param array $cronData
     * @param LogActionUpdater $updater
     * @return void
     */
    private function registerEventListenerForHooks(array $cronData, LogActionUpdater $updater): void
    {
        foreach ($cronData as $hook => $data) {
            if ($hook && $data && is_string($hook) && is_array($data)) {
                $this->registerEventListenerForHook($hook, $updater);
            }
        }
    }

    /**
     * @param string $hook
     * @param LogActionUpdater $updater
     * @return void
     */
    private function registerEventListenerForHook(string $hook, LogActionUpdater $updater): void
    {
        $profileCallback = function () use ($hook, $updater) {
            $this->cronActionProfile($hook, $updater);
        };

        add_action($hook, $profileCallback, PHP_INT_MIN);
        add_action($hook, $profileCallback, PHP_INT_MAX);
    }

    /**
     * Run before and after that any cron action ran, logging it and its performance.
     *
     * @param string $hook
     * @param LogActionUpdater $updater
     */
    private function cronActionProfile(string $hook, LogActionUpdater $updater): void
    {
        if (!wp_doing_cron()) {
            return;
        }

        if (!isset($this->done[$hook])) {
            $this->done[$hook] = [(float)microtime(true), null];

            return;
        }

        [$start, $duration] = $this->done[$hook] ?? [null, null];
        if (!is_float($start) || ($duration !== null)) {
            return;
        }

        $duration = number_format((float)microtime(true) - $start, 2);
        $this->done[$hook] = [$start, $duration];

        $message = sprintf('Cron action "%s" performed. Duration: %s seconds.', $hook, $duration);

        $updater->update(new Log($message, $this->logLevel, Channels::CRON));
    }

    /**
     * @param LogActionUpdater $updater
     * @return void
     */
    private function logUnfinishedHooks(LogActionUpdater $updater): void
    {
        $unfinished = [];
        foreach ($this->done as $hook => [, $duration]) {
            ($duration === null) and $unfinished[] = $hook;
        }

        if (!$unfinished) {
            return;
        }

        $message = sprintf(
            'Hook action%s "%s" started, but never completed.',
            count($unfinished) === 1 ? '' : 's',
            implode('", "', $unfinished)
        );

        $updater->update(new Log($message, $this->logLevel, Channels::CRON));
    }
}

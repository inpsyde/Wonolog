<?php

declare(strict_types=1);

/*
 * This file is part of the Wonolog package.
 *
 * (c) Inpsyde GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Inpsyde\Wonolog\HookListener;

use Inpsyde\Wonolog\Channels;
use Inpsyde\Wonolog\Data\Info;
use Inpsyde\Wonolog\Data\LogDataInterface;
use Inpsyde\Wonolog\Data\NullLog;

/**
 * Listens to WP Cron requests and logs the performed actions and their performance.
 *
 * @package wonolog
 * @license http://opensource.org/licenses/MIT MIT
 */
final class CronDebugListener implements ActionListenerInterface
{
    use ListenerIdByClassNameTrait;

    public const IS_CLI = 1;
    public const IS_CRON = 2;

    /**
     * @var bool
     */
    private static $ran = false;

    /**
     * @var int
     */
    private $flags = 0;

    /**
     * @var array[]
     */
    private $done = [];

    /**
     * @param int $flags
     */
    public function __construct(int $flags = 0)
    {
        $this->flags = $flags;
    }

    /**
     * @return array<string>
     */
    public function listenTo(): array
    {
        return ['wp_loaded'];
    }

    /**
     * @return bool
     */
    public function isCli(): bool
    {
        return ($this->flags & self::IS_CLI) || (defined('WP_CLI') && WP_CLI);
    }

    /**
     * @return bool
     */
    public function isCron(): bool
    {
        return ($this->flags & self::IS_CRON) || (defined('DOING_CRON') && DOING_CRON);
    }

    /**
     * Logs all the cron hook performed and their performance.
     *
     * @param array $args
     * @return NullLog
     *
     * @wp-hook wp_loaded
     */
    public function update(array $args): LogDataInterface
    {
        if (self::$ran) {
            return new NullLog();
        }

        if ($this->isCron() || $this->isCli()) {
            $this->registerEventListener();
        }

        return new NullLog();
    }

    /**
     * Logs all the cron hook performed and their performance.
     */
    private function registerEventListener(): void
    {
        $cronArray = _get_cron_array();
        if (!$cronArray || !is_array($cronArray)) {
            return;
        }

        $hooks = array_reduce(
            $cronArray,
            static function (array $hooks, array $crons): array {
                return array_merge($hooks, array_keys($crons));
            },
            []
        );

        $profileCallback = function () {
            $this->cronActionProfile();
        };

        array_walk(
            $hooks,
            static function (string $hook) use ($profileCallback) {
                // Please note that "(int)(PHP_INT_MAX +  )" is the lowest possible integer.
                add_action($hook, $profileCallback, (int)(PHP_INT_MAX + 1));
                add_action($hook, $profileCallback, PHP_INT_MAX);
            }
        );

        self::$ran = true;
    }

    /**
     * Run before and after that any cron action ran, logging it and its performance.
     */
    private function cronActionProfile(): void
    {
        if (!defined('DOING_CRON') || !DOING_CRON) {
            return;
        }

        $hook = current_filter();
        if (!isset($this->done[$hook])) {
            $this->done[$hook]['start'] = microtime(true);

            return;
        }

        if (!isset($this->done[$hook]['duration'])) {
            $duration = number_format(microtime(true) - $this->done[$hook]['start'], 2);
            $this->done[$hook]['duration'] = $duration . ' s';

            // Log the cron action performed.
            do_action(
                \Inpsyde\Wonolog\LOG,
                new Info("Cron action \"{$hook}\" performed.", Channels::DEBUG, $this->done[$hook])
            );
        }
    }
}

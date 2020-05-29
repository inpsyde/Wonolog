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

namespace Inpsyde\Wonolog;

use Inpsyde\Wonolog\Data\HookLogFactory;
use Inpsyde\Wonolog\Data\LogDataInterface;
use Monolog\Logger;

/**
 * Main package object, where "things happen".
 *
 * It is the object that is used to listed to `wonolog.log` actions, build log data from received
 * arguments and pass them to Monolog for the actual logging.
 *
 * @package wonolog
 * @license http://opensource.org/licenses/MIT MIT
 */
class LogActionSubscriber
{
    public const ACTION_LOGGER_ERROR = 'wonolog.logger-error';

    /**
     * @var Channels
     */
    private $channels;

    /**
     * @var HookLogFactory
     */
    private $logFactory;

    /**
     * @param Channels $channels
     * @param HookLogFactory|null $factory
     */
    public function __construct(Channels $channels, HookLogFactory $factory = null)
    {
        $this->channels = $channels;
        $this->logFactory = $factory ?: new HookLogFactory();
    }

    /**
     * @wp-hook wonolog.log
     * @wp-hook wonolog.log.debug
     * @wp-hook wonolog.log.info
     * @wp-hook wonolog.log.notice
     * @wp-hook wonolog.log.warning
     * @wp-hook wonolog.log.error
     * @wp-hook wonolog.log.critical
     * @wp-hook wonolog.log.alert
     * @wp-hook wonolog.log.emergency
     */
    public function listen(): void
    {

        if (!did_action(Controller::ACTION_LOADED)) {
            return;
        }

        $logs = $this->logFactory->logsFromHookArguments(func_get_args(), $this->hookLevel());

        array_walk($logs, [$this, 'update']);
    }

    /**
     * @param LogDataInterface $log
     *
     * @return bool
     */
    public function update(LogDataInterface $log): bool
    {
        if (!did_action(Controller::ACTION_LOADED) || $log->level() < 1) {
            return false;
        }

        try {
            return $this->channels
                ->logger($log->channel())
                ->addRecord($log->level(), $log->message(), $log->context());
        } catch (\Throwable $throwable) {
            /**
             * Fires when the logger encounters an error.
             *
             * @param LogDataInterface $log
             * @param \Exception|\Throwable $throwable
             */
            do_action(self::ACTION_LOGGER_ERROR, $log, $throwable);

            return false;
        }
    }

    /**
     * @return int
     */
    private function hookLevel(): int
    {
        $currentFilter = current_filter();
        if ($currentFilter === LOG) {
            return 0;
        }

        $parts = explode('.', $currentFilter, 3);
        if (isset($parts[2])) {
            return LogLevel::instance()->checkLevel($parts[2]);
        }

        return Logger::DEBUG;
    }
}

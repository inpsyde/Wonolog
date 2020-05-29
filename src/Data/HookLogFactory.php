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

namespace Inpsyde\Wonolog\Data;

use Inpsyde\Wonolog\Channels;
use Inpsyde\Wonolog\LogLevel;
use Monolog\Logger;

/**
 * @package wonolog
 * @license http://opensource.org/licenses/MIT MIT
 */
class HookLogFactory
{

    /**
     * @param array $arguments
     * @param int $hookLevel
     *
     * @return LogDataInterface[]
     */
    public function logsFromHookArguments(array $arguments, int $hookLevel = 0): array
    {
        // When no arguments are passed, there's not much we can do
        if (!$arguments) {
            $log = new Log('Unknown error.', Logger::DEBUG, Channels::DEBUG);

            return [$this->maybeRaiseLevel($hookLevel, $log)];
        }

        // First let's see if already formed log objects were passed
        $logs = $this->extractLogObjectsInArgs($arguments, $hookLevel);

        // If so, let's just return them
        if ($logs) {
            return $logs;
        }

        // Let's determine a log object based on first argument
        $firstArg = reset($arguments);

        $otherArgs = array_values(array_slice($arguments, 1, 2, false));

        switch (true) {
            case (is_array($firstArg)):
                $logs[] = $this->maybeRaiseLevel($hookLevel, Log::fromArray($firstArg));
                break;

            case (is_wp_error($firstArg)):
                [$level, $channel] = $this->levelAndChannelFromArgs($otherArgs);
                $log = Log::fromWpError($firstArg, $level, $channel);
                $logs[] = $this->maybeRaiseLevel($hookLevel, $log);
                break;

            case ($firstArg instanceof \Throwable || $firstArg instanceof \Exception):
                [$level, $channel] = $this->levelAndChannelFromArgs($otherArgs);
                $log = Log::fromThrowable($firstArg, $level, $channel);
                $logs[] = $this->maybeRaiseLevel($hookLevel, $log);
                break;

            case (is_string($firstArg)):
                [$level, $channel] = $this->levelAndChannelFromArgs($otherArgs);
                $level or $level = Logger::DEBUG;
                $channel or $channel = Channels::DEBUG;
                $logs[] = $this->maybeRaiseLevel($hookLevel, new Log($firstArg, $level, $channel));
                break;
        }

        return $logs;
    }

    /**
     * @param int $hookLevel
     * @param LogDataInterface $log
     *
     * @return LogDataInterface
     */
    private function maybeRaiseLevel(int $hookLevel, LogDataInterface $log): LogDataInterface
    {
        if ($hookLevel > $log->level()) {
            return new Log($log->message(), $hookLevel, $log->channel(), $log->context());
        }

        return $log;
    }

    /**
     * If one or more LogData objects are passed as argument,
     * extract all of them and return remaining objects.
     *
     * @param array $args
     * @param int $hookLevel
     *
     * @return LogDataInterface[]
     */
    private function extractLogObjectsInArgs(array $args, int $hookLevel): array
    {
        $logs = [];
        foreach ($args as $arg) {
            if ($arg instanceof LogDataInterface) {
                $logs[] = $this->maybeRaiseLevel($hookLevel, $arg);
            }
        }

        return $logs;
    }

    /**
     * @param array $args
     * @return array{0:int, 1:string}
     */
    private function levelAndChannelFromArgs(array $args): array
    {
        if (!$args) {
            return [0, ''];
        }

        $level = 0;
        $channel = '';

        if (!empty($args[0]) && (is_numeric($args[0]) || is_string($args[0]))) {
            $level = (int)LogLevel::instance()->checkLevel($args[0]);
        }

        if (! empty($args[1]) && is_string($args[1])) {
            $channel = $args[1];
        }

        return [$level, $channel];
    }
}

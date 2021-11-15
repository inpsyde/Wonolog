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

namespace Inpsyde\Wonolog;

use Inpsyde\Wonolog\Data\Log;
use Inpsyde\Wonolog\Data\LogData;

class HookLogFactory
{
    /**
     * @return HookLogFactory
     */
    public static function new(): HookLogFactory
    {
        return new self();
    }

    /**
     * @param array $params
     * @param int|null $defaultLevel
     * @param string|null $defaultChannel
     * @return LogData[]
     */
    public function logsFromHookArguments(
        array $params,
        ?int $defaultLevel = null,
        ?string $defaultChannel = null
    ): array {

        ($defaultChannel === null) and $defaultChannel = Channels::DEBUG;
        ($defaultLevel === null) and $defaultLevel = LogLevel::DEBUG;

        // When no arguments are passed, there's not much we can do
        if (!$params) {
            return [new Log('Unknown error.', $defaultLevel, $defaultChannel)];
        }

        // First let's see if already formed log objects were passed, if so, let's just return them.
        $logs = $this->extractLogObjectsInArgs($params, $defaultLevel);
        if ($logs) {
            return $logs;
        }

        // Let's determine a log object based on first argument
        $firstArg = array_shift($params);

        switch (true) {
            case (is_array($firstArg)):
                $logs[] = $this->fromArray($firstArg, $params, $defaultLevel, $defaultChannel);
                break;
            case ($firstArg instanceof \WP_Error):
                $logs[] = $this->fromWpError($firstArg, $params, $defaultLevel, $defaultChannel);
                break;
            case ($firstArg instanceof \Throwable):
                $logs[] = $this->fromThrowable($firstArg, $defaultLevel, $defaultChannel);
                break;
            case (is_string($firstArg)):
                $logs[] = $this->fromString($firstArg, $params, $defaultLevel, $defaultChannel);
                break;
        }

        return $logs;
    }

    /**
     * @param array $value
     * @param array $arguments
     * @param int $defaultLevel
     * @param string $defaultChannel
     * @return LogData
     */
    private function fromArray(
        array $value,
        array $arguments,
        int $defaultLevel,
        string $defaultChannel
    ): LogData {

        if (!isset($value[LogData::CONTEXT])) {
            $value[LogData::CONTEXT] = $arguments;
        }

        $log = Log::fromArray($value, $defaultChannel, $defaultLevel);

        return $this->maybeRaiseLevel($defaultLevel, $log);
    }

    /**
     * @param \WP_Error $value
     * @param array $arguments
     * @param int $defaultLevel
     * @param string $defaultChannel
     * @return LogData
     */
    private function fromWpError(
        \WP_Error $value,
        array $arguments,
        int $defaultLevel,
        string $defaultChannel
    ): LogData {

        $data = $value->get_error_data();
        if ($arguments && (!$data || !is_array($data))) {
            $value->add_data($arguments);
        }

        $log = Log::fromWpError($value, LogLevel::NOTICE, $defaultChannel);

        return $this->maybeRaiseLevel($defaultLevel, $log);
    }

    /**
     * @param \Throwable $value
     * @param int $defaultLevel
     * @param string $defaultChannel
     * @return LogData
     */
    private function fromThrowable(
        \Throwable $value,
        int $defaultLevel,
        string $defaultChannel
    ): LogData {

        $log = Log::fromThrowable($value, LogLevel::ERROR, $defaultChannel);

        return $this->maybeRaiseLevel($defaultLevel, $log);
    }

    /**
     * @param string $value
     * @param array $arguments
     * @param int $defaultLevel
     * @param string $defaultChannel
     * @return LogData
     */
    private function fromString(
        string $value,
        array $arguments,
        int $defaultLevel,
        string $defaultChannel
    ): LogData {

        $log = new Log($value, $defaultLevel, $defaultChannel, $arguments);

        return $this->maybeRaiseLevel($defaultLevel, $log);
    }

    /**
     * @param int $hookLevel
     * @param LogData $log
     * @return LogData
     */
    private function maybeRaiseLevel(int $hookLevel, LogData $log): LogData
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
     * @return LogData[]
     */
    private function extractLogObjectsInArgs(array $args, int $hookLevel): array
    {
        $logs = [];
        foreach ($args as $arg) {
            if ($arg instanceof LogData) {
                $logs[] = $this->maybeRaiseLevel($hookLevel, $arg);
            }
        }

        return $logs;
    }
}

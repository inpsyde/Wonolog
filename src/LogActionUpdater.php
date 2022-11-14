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

use Inpsyde\Wonolog\Data\LogData;

class LogActionUpdater
{
    public const ACTION_LOGGER_ERROR = 'wonolog.logger-error';
    public const FILTER_CONTEXT = 'wonolog.log-data-context';

    /**
     * @var Channels
     */
    private $channels;

    /**
     * @param Channels $channels
     * @return LogActionUpdater
     */
    public static function new(Channels $channels): LogActionUpdater
    {
        return new self($channels);
    }

    /**
     * @param Channels $channels
     */
    private function __construct(Channels $channels)
    {
        $this->channels = $channels;
    }

    /**
     * @param LogData $log
     * @return void
     */
    /**
     * @param LogData $log
     * @return void
     */
    public function update(LogData $log): void
    {
        if (
            !did_action(Configurator::ACTION_LOADED)
            || $log->level() < 1
            || $this->channels->isIgnored($log)
        ) {
            return;
        }

        try {
            $context = $this->parseContext($log);
            $this->channels
                ->logger($log->channel())
                ->log(LogLevel::toPsrLevel($log->level()), $log->message(), $context);
        } catch (\Throwable $throwable) {
            /**
             * Fires when the logger encounters an error.
             *
             * @param LogData $log
             * @param \Exception|\Throwable $throwable
             */
            do_action(self::ACTION_LOGGER_ERROR, $log, $throwable);
        }
    }

    /**
     * @param LogData $log
     * @return array
     */
    private function parseContext(LogData $log): array
    {
        $context = $log->context();

        $filteredContext = apply_filters(self::FILTER_CONTEXT, $context, $log);
        is_array($filteredContext) and $context = $filteredContext;

        return Serializer::serializeContext($context);
    }
}

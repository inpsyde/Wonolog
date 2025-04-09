<?php

declare(strict_types=1);

namespace Inpsyde\Wonolog\MonologV2;

use Inpsyde\Wonolog\Channels;
use Inpsyde\Wonolog\Data\Log;
use Inpsyde\Wonolog\Data\LogData;
use Inpsyde\Wonolog\LogLevel;
use Inpsyde\Wonolog\PsrBridgeAdapterAbstract;
use Inpsyde\Wonolog\Serializer;

/**
 * @phpstan-import-type Record from \Monolog\Logger
 */
class PsrBridgeAdapter extends PsrBridgeAdapterAbstract
{
    /**
     * @param mixed $level
     * @param mixed $message
     * @param array $context
     * @return void
     *
     * phpcs:disable SlevomatCodingStandard.Complexity.Cognitive
     */
    public function log(mixed $level, mixed $message, array $context = []): void
    {
        // phpcs:enable SlevomatCodingStandard.Complexity.Cognitive
        $throwable = null;
        if ($message instanceof \Throwable) {
            $throwable = $message;
            $message = $message->getMessage();
        }
        $throwable = $throwable ?? $context['exception'] ?? null;
        if ($throwable && !($throwable instanceof \Throwable)) {
            $throwable = null;
        }

        $message = Serializer::serializeMessage($message);

        $level = LogLevel::normalizeLevel($level);
        if (!$level) {
            $level = $throwable ? LogLevel::ERROR : LogLevel::DEBUG;
        }

        $channel = $context[LogData::CHANNEL] ?? null;
        if (!$channel || !is_string($channel)) {
            $channel = $throwable
                ? ($this->defaultChannel ?? Channels::PHP_ERROR)
                : ($this->defaultChannel ?? $this->channels->defaultChannel());
        }
        unset($context[LogData::CHANNEL]);

        /** @var Record $record */
        $record = compact('message', 'context', 'level');
        $record = ($this->processor)($record);
        // @phpstan-ignore function.alreadyNarrowedType
        if (array_key_exists('message', $record)) {
            $message = (string) $record['message'];
        }
        // @phpstan-ignore function.alreadyNarrowedType
        if (array_key_exists('context', $record)) {
            $context = (array) $record['context'];
        }

        unset($context['exception']);
        if ($throwable) {
            $context['exception'] = $throwable;
        }

        $this->updater->update(new Log($message, $level, $channel, $context));
    }
}

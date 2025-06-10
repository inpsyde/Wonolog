<?php

declare(strict_types=1);

namespace Inpsyde\Wonolog;

use Inpsyde\Wonolog\Data\Log;
use Inpsyde\Wonolog\Data\LogData;
use Monolog\LogRecord;
use Monolog\Processor\PsrLogMessageProcessor;
use Psr\Log\AbstractLogger;

/**
 * @phpstan-import-type _RecordType from Configurator
 * @phpstan-import-type _RecordArray from Configurator
 */
class PsrBridge extends AbstractLogger
{
    private LogActionUpdater $updater;
    private Channels $channels;
    private ?string $defaultChannel = null;
    private PsrLogMessageProcessor $processor;

    /**
     * @param LogActionUpdater $updater
     * @param Channels $channels
     * @return PsrBridge
     */
    public static function new(LogActionUpdater $updater, Channels $channels): PsrBridge
    {
        return new self($updater, $channels);
    }

    /**
     * @param LogActionUpdater $updater
     * @param Channels $channels
     */
    private function __construct(LogActionUpdater $updater, Channels $channels)
    {
        $this->updater = $updater;
        $this->channels = $channels;
        $this->processor = new PsrLogMessageProcessor(null, true);
    }

    /**
     * @param string $defaultChannel
     * @return static
     */
    public function withDefaultChannel(string $defaultChannel): PsrBridge
    {
        $this->channels->addChannel($defaultChannel);
        $this->defaultChannel = $defaultChannel;

        return $this;
    }

    /**
     * @param mixed $level
     * @param mixed $message
     * @param array<mixed> $context
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
            $level = ($throwable === null) ? LogLevel::DEBUG : LogLevel::ERROR;
        }

        $channel = $context[LogData::CHANNEL] ?? null;
        if (!$channel || !is_string($channel)) {
            $channel = ($throwable instanceof \Throwable)
                ? ($this->defaultChannel ?? Channels::PHP_ERROR)
                : ($this->defaultChannel ?? $this->channels->defaultChannel());
        }
        unset($context[LogData::CHANNEL]);

        $record = RecordFactory::createRecord($message, $level, $channel, $context);
        $record = ($this->processor)($record);
        /** @var \Throwable|null $throwable */
        $this->updater->update($this->createLog($record, $level, $channel, $throwable));
    }

    /**
     * @param _RecordType $record
     * @param mixed $level
     * @param string $channel
     * @param \Throwable|null $throwable
     * @return Log
     */
    protected function createLog(
        array|LogRecord $record,
        mixed $level,
        string $channel,
        ?\Throwable $throwable
    ): Log {

        /** @var _RecordArray $recordData */
        $recordData = ($record instanceof LogRecord) ? $record->toArray() : $record;

        $message = array_key_exists('message', $recordData)
            ? $recordData['message']
            : '';
        if (!is_scalar($message)) {
            $message = Serializer::serializeMessage($message);
        }

        $context = [];

        if (array_key_exists('context', $recordData)) {
            $context = $recordData['context'];
        }

        unset($context['exception']);
        if ($throwable) {
            $context['exception'] = $throwable;
        }

        $level = LogLevel::normalizeLevel($level) ?? LogLevel::DEBUG;

        return new Log((string) $message, $level, $channel, (array) $context);
    }
}

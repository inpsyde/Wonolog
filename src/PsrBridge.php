<?php

declare(strict_types=1);

namespace Inpsyde\Wonolog;

use Inpsyde\Wonolog\Data\Log;
use Inpsyde\Wonolog\Data\LogData;
use Monolog\Processor\PsrLogMessageProcessor;
use Psr\Log\AbstractLogger;

class PsrBridge extends AbstractLogger
{
    /**
     * @var LogActionUpdater
     */
    private $updater;

    /**
     * @var Channels
     */
    private $channels;

    /**
     * @var string|null
     */
    private $defaultChannel;

    /**
     * @var PsrLogMessageProcessor
     */
    private $processor;

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
     * @param array $context
     * @return void
     *
     * phpcs:disable Generic.Metrics.CyclomaticComplexity
     */
    public function log($level, $message, array $context = []): void
    {
        // phpcs:enable Generic.Metrics.CyclomaticComplexity
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

        /** @psalm-suppress InvalidArgument */
        $record = ($this->processor)(compact('message', 'context'));
        array_key_exists('message', $record) and $message = (string)$record['message'];
        array_key_exists('context', $record) and $context = (array)$record['context'];

        unset($context['exception']);
        $throwable and $context['exception'] = $throwable;

        $this->updater->update(new Log($message, $level, $channel, $context));
    }
}

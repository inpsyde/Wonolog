<?php

declare(strict_types=1);

namespace Inpsyde\Wonolog;

use Inpsyde\Wonolog\Data\Log;
use Inpsyde\Wonolog\Data\LogData;
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
     */
    public function log($level, $message, array $context = [])
    {
        /** @see https://www.php-fig.org/psr/psr-3/#13-context */
        $throwable = $context['exception'] ?? null;
        if ($throwable && !($throwable instanceof \Throwable)) {
            $throwable = null;
        }

        $channel = $context[LogData::CHANNEL] ?? null;
        if (!$channel || !is_string($channel)) {
            $channel = $throwable
                ? Channels::PHP_ERROR
                : ($this->defaultChannel ?? $this->channels->defaultChannel());
        }
        unset($context[LogData::CHANNEL]);

        if (!$message || !is_string($message)) {
            if ($throwable) {
                $message = $throwable->getMessage();
            } elseif (!is_object($message) || !is_callable([$message, '__toString'])) {
                $message = var_export($message, true);
            }
        }

        $level = LogLevel::normalizeLevel($level) ?? LogLevel::DEBUG;
        if ($throwable && ($level < LogLevel::ERROR)) {
            $level = LogLevel::ERROR;
        }

        $this->updater->update(new Log((string)$message, $level, $channel, $context));
    }
}

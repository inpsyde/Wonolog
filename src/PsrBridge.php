<?php

declare(strict_types=1);

namespace Inpsyde\Wonolog;

use Inpsyde\Wonolog\Data\Log;
use Inpsyde\Wonolog\Data\LogData;
use Monolog\Logger;
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
    public function withDefaultChannel(string $defaultChannel)
    {
        $this->channels->addChannel($defaultChannel);
        $this->defaultChannel = $defaultChannel;

        return $this;
    }

    /**
     * @param mixed $level
     * @param string $message
     * @param array $context
     * @return void
     */
    public function log($level, $message, array $context = [])
    {
        $channel = $context[LogData::CHANNEL]
            ?? $this->defaultChannel
            ?? $this->channels->defaultChannel();

        unset($context[LogData::CHANNEL]);

        $this->updater->update(
            new Log(
                (string)$message,
                LogLevel::normalizeLevel($level) ?? Logger::DEBUG,
                $channel,
                $context
            )
        );
    }
}

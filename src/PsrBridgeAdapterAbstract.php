<?php

declare(strict_types=1);

namespace Inpsyde\Wonolog;

use Monolog\Processor\PsrLogMessageProcessor;
use Psr\Log\AbstractLogger;

/**
 * @phpstan-import-type Record from \Monolog\Logger
 */
abstract class PsrBridgeAdapterAbstract extends AbstractLogger
{
    protected LogActionUpdater $updater;

    protected Channels $channels;

    protected ?string $defaultChannel = null;

    protected PsrLogMessageProcessor $processor;

    /**
     * @param LogActionUpdater $updater
     * @param Channels $channels
     */
    public function __construct(LogActionUpdater $updater, Channels $channels)
    {
        $this->updater = $updater;
        $this->channels = $channels;
        $this->processor = new PsrLogMessageProcessor(null, true);
    }

    /**
     * @param string $defaultChannel
     * @return static
     */
    public function withDefaultChannel(string $defaultChannel): PsrBridgeAdapterAbstract
    {
        $this->channels->addChannel($defaultChannel);
        $this->defaultChannel = $defaultChannel;

        return $this;
    }
}

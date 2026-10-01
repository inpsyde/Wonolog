<?php

declare(strict_types=1);

namespace Inpsyde\Wonolog;

use Monolog\Processor\PsrLogMessageProcessor;
use Psr\Log\AbstractLogger;

abstract class PsrBridgeAdapterAbstract extends AbstractLogger
{
    protected ?string $defaultChannel = null;

    protected PsrLogMessageProcessor $processor;

    /**
     * @param LogActionUpdater $updater
     * @param Channels $channels
     */
    public function __construct(protected LogActionUpdater $updater, protected Channels $channels)
    {
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

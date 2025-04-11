<?php

declare(strict_types=1);

namespace Inpsyde\Wonolog;

use Inpsyde\Wonolog\MonologV2\PsrBridgeAdapter as MonologV2PsrBridgeAdapter;
use Inpsyde\Wonolog\MonologV3\PsrBridgeAdapter as MonologV3PsrBridgeAdapter;
use Psr\Log\AbstractLogger;

class PsrBridge extends AbstractLogger
{
    private PsrBridgeAdapterAbstract $adapter;

    /**
     * @param LogActionUpdater $updater
     * @param Channels $channels
     * @return PsrBridge
     */
    public static function new(LogActionUpdater $updater, Channels $channels): PsrBridge
    {
        $adapter = MonologUtils::version() === 3
            ? new MonologV3PsrBridgeAdapter($updater, $channels)
            : new MonologV2PsrBridgeAdapter($updater, $channels);
        return new self($adapter);
    }

    private function __construct(PsrBridgeAdapterAbstract $adapter)
    {
        $this->adapter = $adapter;
    }

    /**
     * @param string $defaultChannel
     * @return static
     */
    public function withDefaultChannel(string $defaultChannel): PsrBridge
    {
        $this->adapter->withDefaultChannel($defaultChannel);
        return $this;
    }

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
        $this->adapter->log($level, $message, $context);
    }
}

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

use Psr\Log\LoggerInterface;

class Factory
{
    /**
     * @var Channels|null
     */
    private $channels;

    /**
     * @var HandlersRegistry|null
     */
    private $handlersRegistry;

    /**
     * @var ProcessorsRegistry|null
     */
    private $processorsRegistry;

    /**
     * @var HookListenersRegistry|null
     */
    private $listenersRegistry;

    /**
     * @var HookLogFactory|null
     */
    private $hookLogFactory;

    /**
     * @var LogActionSubscriber|null
     */
    private $logActionSubscriber;

    /**
     * @var LogActionUpdater|null
     */
    private $logActionUpdater;

    /**
     * @var PsrBridge[]
     */
    private $psr3Loggers = [];

    /**
     * @return Factory
     */
    public static function new(): Factory
    {
        return new self();
    }

    private function __construct()
    {
    }

    /**
     * @return Channels
     */
    public function channels(): Channels
    {
        if (!$this->channels) {
            $this->channels = Channels::new($this->handlersRegistry(), $this->processorsRegistry());
        }

        return $this->channels;
    }

    /**
     * @return HandlersRegistry
     */
    public function handlersRegistry(): HandlersRegistry
    {
        if (!$this->handlersRegistry) {
            $this->handlersRegistry = HandlersRegistry::new($this->processorsRegistry());
        }

        return $this->handlersRegistry;
    }

    /**
     * @return ProcessorsRegistry
     */
    public function processorsRegistry(): ProcessorsRegistry
    {
        if (!$this->processorsRegistry) {
            $this->processorsRegistry = ProcessorsRegistry::new();
        }

        return $this->processorsRegistry;
    }

    /**
     * @return HookListenersRegistry
     */
    public function listenersRegistry(): HookListenersRegistry
    {
        if (!$this->listenersRegistry) {
            $this->listenersRegistry = HookListenersRegistry::new($this->logActionUpdater());
        }

        return $this->listenersRegistry;
    }

    /**
     * @return HookLogFactory
     */
    public function hookLogFactory(): HookLogFactory
    {
        if (!$this->hookLogFactory) {
            $this->hookLogFactory = HookLogFactory::new($this->channels());
        }

        return $this->hookLogFactory;
    }

    /**
     * @return LogActionSubscriber
     */
    public function logActionSubscriber(): LogActionSubscriber
    {
        if (!$this->logActionSubscriber) {
            $this->logActionSubscriber = LogActionSubscriber::new(
                $this->logActionUpdater(),
                $this->hookLogFactory()
            );
        }

        return $this->logActionSubscriber;
    }

    /**
     * @return LogActionUpdater
     */
    public function logActionUpdater(): LogActionUpdater
    {
        if (!$this->logActionUpdater) {
            $this->logActionUpdater = LogActionUpdater::new($this->channels());
        }

        return $this->logActionUpdater;
    }

    /**
     * @return PsrBridge
     */
    public function psr3Logger(?string $defaultChannel = null): PsrBridge
    {
        $key = $defaultChannel ?? 1;
        if (!isset($this->psr3Loggers[$key])) {
            $bridge = PsrBridge::new($this->logActionUpdater(), $this->channels());
            $defaultChannel and $bridge = $bridge->withDefaultChannel($defaultChannel);
            $this->psr3Loggers[$key] = $bridge;
        }

        return $this->psr3Loggers[$key];
    }
}

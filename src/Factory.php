<?php

declare(strict_types=1);

namespace Inpsyde\Wonolog;

class Factory
{
    private ?Channels $channels = null;

    private ?Registry\HandlersRegistry $handlersRegistry = null;

    private ?Registry\ProcessorsRegistry $processorsRegistry = null;

    private ?Registry\HookListenersRegistry $listenersRegistry = null;

    private ?HookLogFactory $hookLogFactory = null;

    private ?LogActionSubscriber $logActionSubscriber = null;

    private ?LogActionUpdater $logActionUpdater = null;

    /**
     * @var PsrBridge[]
     */
    private array $psr3Loggers = [];

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
     * @return Registry\HandlersRegistry
     */
    public function handlersRegistry(): Registry\HandlersRegistry
    {
        if (!$this->handlersRegistry) {
            $this->handlersRegistry = Registry\HandlersRegistry::new($this->processorsRegistry());
        }

        return $this->handlersRegistry;
    }

    /**
     * @return Registry\ProcessorsRegistry
     */
    public function processorsRegistry(): Registry\ProcessorsRegistry
    {
        if (!$this->processorsRegistry) {
            $this->processorsRegistry = Registry\ProcessorsRegistry::new();
        }

        return $this->processorsRegistry;
    }

    /**
     * @return Registry\HookListenersRegistry
     */
    public function listenersRegistry(): Registry\HookListenersRegistry
    {
        if (!$this->listenersRegistry) {
            $this->listenersRegistry = Registry\HookListenersRegistry::new($this->logActionUpdater());
        }

        return $this->listenersRegistry;
    }

    /**
     * @return HookLogFactory
     */
    public function hookLogFactory(): HookLogFactory
    {
        if (!$this->hookLogFactory) {
            $this->hookLogFactory = HookLogFactory::new();
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
     * @param string|null $defaultChannel
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

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

namespace Inpsyde\Wonolog\Registry;

use Inpsyde\Wonolog\DefaultHandler;
use Monolog\Handler\HandlerInterface;
use Monolog\Handler\ProcessableHandlerInterface;

class HandlersRegistry implements \Countable
{
    public const ACTION_SETUP = 'wonolog.handler-setup';

    /**
     * @var array<string, array{HandlerInterface, array<string, bool>}>
     */
    private $handlers = [];

    /**
     * @var ProcessorsRegistry
     */
    private $processorsRegistry;

    /**
     * @var array<int, string>
     */
    private $initialized = [];

    /**
     * @var string[]
     */
    private $disabledForDefault = [];

    /**
     * @param ProcessorsRegistry $processorsRegistry
     * @return HandlersRegistry
     */
    public static function new(ProcessorsRegistry $processorsRegistry): HandlersRegistry
    {
        return new self($processorsRegistry);
    }

    /**
     * @return string
     */
    private static function allChannelsName(): string
    {
        static $name;
        $name or $name = '~*~' . bin2hex(random_bytes(8));

        return (string)$name;
    }

    /**
     * @param ProcessorsRegistry $processorsRegistry
     */
    private function __construct(ProcessorsRegistry $processorsRegistry)
    {
        $this->processorsRegistry = $processorsRegistry;
    }

    /**
     * @param HandlerInterface $handler
     * @param string|null $identifier
     * @param string ...$channels
     * @return static
     */
    public function addHandler(
        HandlerInterface $handler,
        ?string $identifier = null,
        string ...$channels
    ): HandlersRegistry {

        if ($identifier === null) {
            $class = get_class($handler);
            $defaultId = DefaultHandler::id();
            $useDefaultId = $class === DefaultHandler::class && empty($this->handlers[$defaultId]);
            $identifier = $useDefaultId ? $defaultId : $class;
        }

        $allChannels = self::allChannelsName();
        $channels or $channels = [$allChannels];

        [$currentHandler, $currentChannels] = $this->handlers[$identifier] ?? [null, []];
        if ($currentHandler && $currentHandler !== $handler) {
            return $this;
        }

        $alreadyAddedForAll = $currentChannels[$allChannels] ?? null;
        unset($currentChannels[$allChannels]);

        $enabledChannels = array_fill_keys($channels, true);
        $newChannels = array_replace($currentChannels, $enabledChannels);
        $alreadyAddedForAll and $newChannels[$allChannels] = $alreadyAddedForAll;

        $this->handlers[$identifier] = [$handler, $newChannels];

        return $this;
    }

    /**
     * @param string $identifier
     * @return static
     */
    public function removeHandler(string $identifier): HandlersRegistry
    {
        unset($this->handlers[$identifier]);

        return $this;
    }

    /**
     * @param string $identifier
     * @param string $channel
     * @param string ...$channels
     * @return static
     */
    public function removeHandlerFromChannels(
        string $identifier,
        string $channel,
        string ...$channels
    ): HandlersRegistry {

        [$handler, $currentChannels] = $this->handlers[$identifier] ?? [null, []];
        if (!$handler) {
            return $this;
        }

        $allChannels = self::allChannelsName();
        $default = $currentChannels[$allChannels] ?? null;
        unset($currentChannels[$allChannels]);

        array_unshift($channels, $channel);
        $disabledChannels = array_fill_keys($channels, false);

        $newChannels = array_replace($currentChannels, $disabledChannels);
        $default and $newChannels[$allChannels] = $default;

        if (!array_filter($newChannels)) {
            unset($this->handlers[$identifier]);

            return $this;
        }

        $this->handlers[$identifier] = [$handler, $newChannels];

        return $this;
    }

    /**
     * @param string $identifier
     * @param string $channel
     * @return bool
     */
    public function hasHandlerForChannel(string $identifier, string $channel): bool
    {
        if (
            $identifier === DefaultHandler::id()
            && in_array($channel, $this->disabledForDefault, true)
        ) {
            return false;
        }

        [$handler, $channels] = $this->handlers[$identifier] ?? [null, []];

        return $handler && ($channels[$channel] ?? $channels[self::allChannelsName()] ?? false);
    }

    /**
     * @param string $channel
     * @return array<int, HandlerInterface>
     */
    public function findForChannel(string $channel): array
    {
        $found = [];
        foreach ($this->handlers as $identifier => [$handler]) {
            if ($this->hasHandlerForChannel($identifier, $channel)) {
                $found[] = $this->initializeHandler($identifier, $handler);
            }
        }

        return $found;
    }

    /**
     * @param string $identifier
     * @return HandlerInterface|null
     */
    public function findById(string $identifier): ?HandlerInterface
    {
        [$handler] = $this->handlers[$identifier] ?? [null];

        return $handler;
    }

    /**
     * @return int
     */
    public function count(): int
    {
        return count($this->handlers);
    }

    /**
     * @param string $identifier
     * @param HandlerInterface $handler
     * @return HandlerInterface
     */
    private function initializeHandler(
        string $identifier,
        HandlerInterface $handler
    ): HandlerInterface {

        if (!in_array($identifier, $this->initialized, true)) {
            $processable = $handler instanceof ProcessableHandlerInterface;

            /**
             * Fires right after a handler has been registered.
             *
             * @param HandlerInterface $handler
             * @param string $identifier
             * @param ProcessorsRegistry|null processorsRegistry
             */
            do_action(
                self::ACTION_SETUP,
                $handler,
                $identifier,
                $processable ? $this->processorsRegistry : null
            );

            $this->initialized[] = $identifier;
        }

        return $handler;
    }
}

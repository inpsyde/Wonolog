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

use Monolog\Handler\AbstractHandler;
use Monolog\Handler\BufferHandler;
use Monolog\Handler\HandlerInterface;
use Monolog\Handler\ProcessableHandlerInterface;

class HandlersRegistry implements \Countable
{
    public const ACTION_SETUP = 'wonolog.handler-setup';
    public const ACTION_SETUP_PROCESSABLE = 'wonolog.processable-handler-setup';
    public const FILTER_BUFFER_HANDLER = 'wonolog.buffer-handler';
    public const FILTER_BUFFER_LIMIT = 'wonolog.handler-buffer-limit';

    /**
     * @var array<string, array{HandlerInterface, array<string, bool>}>
     */
    private $handlers = [];

    /**
     * @var ProcessorsRegistry
     */
    private $processorsRegistry;

    /**
     * @var list<string>
     */
    private $initialized = [];

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
            $identifier = get_class($handler);
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
     * @param string ...$channels
     * @return static
     */
    public function enableHandlerForChannels(
        string $identifier,
        string $channel,
        string ...$channels
    ): HandlersRegistry {

        [$handler, $currentChannels] = $this->handlers[$identifier] ?? [null, null];
        if ($handler && empty($currentChannels[self::allChannelsName()])) {
            $this->addHandler($handler, $identifier, $channel, ...$channels);
        }

        return $this;
    }

    /**
     * @param string $channel
     * @param string $identifier
     * @param string ...$identifiers
     * @return static
     */
    public function enableHandlersForChannel(
        string $channel,
        string $identifier,
        string ...$identifiers
    ): HandlersRegistry {

        array_unshift($identifiers, $identifier);
        foreach ($identifiers as $identifier) {
            $this->enableHandlerForChannels($identifier, $channel);
        }

        return $this;
    }

    /**
     * @param string $identifier
     * @param string $channel
     * @return bool
     */
    public function hasHandlerForChannel(string $identifier, string $channel): bool
    {
        [$handler, $channels] = $this->handlers[$identifier] ?? [null, []];

        return $handler && ($channels[$channel] ?? $channels[self::allChannelsName()] ?? false);
    }

    /**
     * @param string $channel
     * @return bool
     */
    public function hasAnyHandlerForChannel(string $channel): bool
    {
        foreach (array_keys($this->handlers) as $identifier) {
            if ($this->hasHandlerForChannel($identifier, $channel)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param string $identifier
     * @return bool
     */
    public function hasHandlerForAnyChannel(string $identifier): bool
    {
        [$handler, $channels] = $this->handlers[$identifier] ?? [null, null];
        if (!$handler) {
            return false;
        }

        if ($channels[self::allChannelsName()] ?? false) {
            return true;
        }

        foreach ((array)$channels as $enabled) {
            if ($enabled) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param string $channel
     * @return list<HandlerInterface>
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
             * Fires right after a processable handler has been registered.
             *
             * @param HandlerInterface $handler
             * @param string $identifier
             * @param ProcessorsRegistry processorsRegistry
             */
            $processable and do_action(
                self::ACTION_SETUP_PROCESSABLE,
                $handler,
                $identifier,
                $this->processorsRegistry
            );

            /**
             * Fires right after a handler has been registered.
             *
             * @param HandlerInterface $handler
             * @param string $identifier
             */
            do_action(self::ACTION_SETUP, $handler, $identifier);

            $this->initialized[] = $identifier;

            $handler = $this->prepareBuffer($identifier, $handler);
        }

        return $handler;
    }

    /**
     * @param string $identifier
     * @param HandlerInterface $handler
     * @return HandlerInterface
     */
    private function prepareBuffer(string $identifier, HandlerInterface $handler): HandlerInterface
    {
        if (!($handler instanceof AbstractHandler) || ($handler instanceof BufferHandler)) {
            return $handler;
        }

        /**
         * Filter whether an handler has to be buffered or not.
         *
         * @param bool $buffer
         * @param string $identifier
         */
        if (!apply_filters(self::FILTER_BUFFER_HANDLER, true, $identifier)) {
            return $handler;
        }

        /**
         * Filter the buffer limit for a given handler.
         *
         * @param int $limit
         * @param string $identifier
         */
        $bufferLimit = apply_filters(self::FILTER_BUFFER_LIMIT, 20, $identifier);
        if (!is_int($bufferLimit) || ($bufferLimit < 2)) {
            return $handler;
        }

        $handler = new BufferHandler(
            $handler,
            $bufferLimit,
            $handler->getLevel(),
            $handler->getBubble(),
            true
        );

        $this->handlers[$identifier][0] = $handler;

        return $handler;
    }
}

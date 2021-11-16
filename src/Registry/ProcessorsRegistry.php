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

class ProcessorsRegistry implements \Countable
{
    /**
     * @var array<string, array{callable(array):array, array<string, bool>|null}>
     */
    private $processors = [];

    public static function new(): ProcessorsRegistry
    {
        return new self();
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

    private function __construct()
    {
    }

    /**
     * @param callable(array):array $processor
     * @param string $identifier
     * @param string ...$channels
     * @return static
     */
    public function addProcessor(
        callable $processor,
        string $identifier,
        string ...$channels
    ): ProcessorsRegistry {

        $allChannels = self::allChannelsName();
        $channels or $channels = [$allChannels];

        [$currentProcessor, $currentChannels] = $this->processors[$identifier] ?? [null, []];
        if ($currentProcessor && $currentProcessor !== $processor) {
            return $this;
        }

        $currentChannels or $currentChannels = [];
        $alreadyAddedForAll = $currentChannels[$allChannels] ?? null;
        unset($currentChannels[$allChannels]);

        $enabledChannels = array_fill_keys($channels, true);
        $newChannels = array_replace($currentChannels, $enabledChannels);
        $alreadyAddedForAll and $newChannels[$allChannels] = $alreadyAddedForAll;

        $this->processors[$identifier] = [$processor, $newChannels];

        return $this;
    }

    /**
     * @param string $identifier
     * @return static
     */
    public function removeProcessor(string $identifier): ProcessorsRegistry
    {
        unset($this->processors[$identifier]);

        return $this;
    }

    /**
     * @param string $identifier
     * @param string $channel
     * @param string ...$channels
     * @return static
     */
    public function removeProcessorFromChannels(
        string $identifier,
        string $channel,
        string ...$channels
    ): ProcessorsRegistry {

        [$processor, $currentChannels] = $this->processors[$identifier] ?? [null, []];
        if (!$processor) {
            return $this;
        }

        $allChannels = self::allChannelsName();
        $currentChannels or $currentChannels = [];
        $default = $currentChannels[$allChannels] ?? null;
        unset($currentChannels[$allChannels]);

        array_unshift($channels, $channel);
        $disabledChannels = array_fill_keys($channels, false);

        $newChannels = array_replace($currentChannels, $disabledChannels);
        $default and $newChannels[$allChannels] = $default;

        if (!array_filter($newChannels)) {
            unset($this->processors[$identifier]);

            return $this;
        }

        $this->processors[$identifier] = [$processor, $newChannels];

        return $this;
    }

    /**
     * @param string $identifier
     * @param string $channel
     * @param string ...$channels
     * @return static
     */
    public function enableProcessorForChannels(
        string $identifier,
        string $channel,
        string ...$channels
    ): ProcessorsRegistry {

        [$processor, $currentChannels] = $this->processors[$identifier] ?? [null, null];
        if ($processor && empty($currentChannels[self::allChannelsName()])) {
            $this->addProcessor($processor, $identifier, $channel, ...$channels);
        }

        return $this;
    }

    /**
     * @param string $channel
     * @param string $identifier
     * @param string ...$identifiers
     * @return static
     */
    public function enableProcessorsForChannel(
        string $channel,
        string $identifier,
        string ...$identifiers
    ): ProcessorsRegistry {

        array_unshift($identifiers, $identifier);
        foreach ($identifiers as $identifier) {
            $this->enableProcessorForChannels($identifier, $channel);
        }

        return $this;
    }

    /**
     * @param string $identifier
     * @param string $channel
     * @return bool
     */
    public function hasProcessorForChannel(string $identifier, string $channel): bool
    {
        [$processor, $channels] = $this->processors[$identifier] ?? [null, []];

        return $processor && ($channels[$channel] ?? $channels[self::allChannelsName()] ?? false);
    }

    /**
     * @param string $channel
     * @return bool
     */
    public function hasAnyProcessorForChannel(string $channel): bool
    {
        foreach (array_keys($this->processors) as $identifier) {
            if ($this->hasProcessorForChannel($identifier, $channel)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param string $identifier
     * @return bool
     */
    public function hasProcessorForAnyChannel(string $identifier): bool
    {
        [$processor, $channels] = $this->processors[$identifier] ?? [null, null];
        if (!$processor) {
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
     * @return list<callable(array):array>
     */
    public function findForChannel(string $channel): array
    {
        $found = [];
        foreach ($this->processors as $identifier => [$processor]) {
            if ($this->hasProcessorForChannel($identifier, $channel)) {
                $found[] = $processor;
            }
        }

        return $found;
    }

    /**
     * @param string $identifier
     * @return callable|null
     */
    public function findById(string $identifier): ?callable
    {
        [$processor] = $this->processors[$identifier] ?? [null];

        return $processor;
    }

    /**
     * @return int
     */
    public function count(): int
    {
        return count($this->processors);
    }
}

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

class ProcessorsRegistry implements \Countable
{
    /**
     * @var array<string, array{callable(array):array, array<string, bool>|null}>
     */
    private $processors = [];

    public static function new(): ProcessorsRegistry
    {
        return new static();
    }

    private function __construct()
    {
    }

    /**
     * @param callable(array):array $processor
     * @param string $identifier
     * @param string ...$identifiers
     * @return ProcessorsRegistry
     */
    public function addProcessor(
        callable $processor,
        string $identifier,
        string ...$channels
    ): ProcessorsRegistry {

        if (isset($this->processors[$identifier])) {
            return $this;
        }

        $enabled = $channels ? array_fill_keys($channels, true) : null;
        $this->processors[$identifier] = [$processor, $enabled];

        return $this;
    }

    /**
     * @param string $identifier
     * @return ProcessorsRegistry
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
     * @return ProcessorsRegistry
     */
    public function removeProcessorFromLoggers(
        string $identifier,
        string $channel,
        string ...$channels
    ): ProcessorsRegistry {

        [$processor, $current] = $this->processors[$identifier] ?? [null, null];
        if (!$processor) {
            return $this;
        }

        ($current === null) and $current = [];

        array_unshift($channels, $channel);
        $disabled = array_fill_keys($channels, false);

        $this->processors[$identifier] = [$processor, array_replace($current, $disabled)];

        return $this;
    }

    /**
     * @param string $identifier
     * @return bool
     */
    public function hasProcessor(string $identifier): bool
    {
        return !empty($this->processors[$identifier]);
    }

    /**
     * @param string $identifier
     * @param string $handler
     * @return bool
     */
    public function hasProcessorForLogger(string $identifier, string $channel): bool
    {
        [$processor, $enabledFor] = $this->processors[$identifier] ?? [null, null];
        if (!$processor) {
            return false;
        }

        return $enabledFor === null || ($enabledFor[$channel] ?? false);
    }

    /**
     * @param string $channel
     * @return array<int, callable(array):array>
     */
    public function allForLogger(string $channel): array
    {
        $found = [];
        foreach ($this->processors as $id => [$processor]) {
            if ($this->hasProcessorForLogger($id, $channel)) {
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

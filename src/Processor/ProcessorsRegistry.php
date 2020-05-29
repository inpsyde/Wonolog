<?php

declare(strict_types=1);

/*
 * This file is part of the Wonolog package.
 *
 * (c) Inpsyde GmbH
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Inpsyde\Wonolog\Processor;

/**
 * @package wonolog
 * @license http://opensource.org/licenses/MIT MIT
 */
class ProcessorsRegistry implements \Countable
{

    public const ACTION_REGISTER = 'wonolog.register-processors';
    public const DEFAULT_NAME = 'wonolog.default-processor';

    /**
     * @var callable[]
     */
    private $processors = [];

    /**
     * @var bool
     */
    private $initialized = false;

    /**
     * @param callable $processor
     *
     * @param string $name
     *
     * @return ProcessorsRegistry
     */
    public function addProcessor(callable $processor, ?string $name = null): ProcessorsRegistry
    {
        ($name === null) and $name = $this->buildName($processor);
        if (! is_string($name) || array_key_exists($name, $this->processors)) {
            return $this;
        }

        $this->processors[$name] = $processor;

        return $this;
    }

    /**
     * @param callable|string $name
     *
     * @return bool
     *
     * phpcs:disable Inpsyde.CodeQuality.ArgumentTypeDeclaration.NoArgumentType
     */
    public function hasProcessor($name): bool
    {
        if (is_callable($name) && ! is_string($name)) {
            $name = $this->buildName($name);
        }

        return is_string($name) && array_key_exists($name, $this->processors);
    }

    /**
     * @param callable|string $name
     *
     * @return callable|null
     *
     * phpcs:disable Inpsyde.CodeQuality.ArgumentTypeDeclaration.NoArgumentType
     */
    public function find($name): ?callable
    {
        if (! $this->initialized) {
            $this->initialized = true;

            /**
             * Fires right before the first processor is to be registered.
             *
             * @param ProcessorsRegistry $processors_registry
             */
            do_action(self::ACTION_REGISTER, $this);
        }

        if (is_callable($name) && ! is_string($name)) {
            $name = $this->buildName($name);
        }

        return $this->hasProcessor($name)
            ? $this->processors[$name]
            : null;
    }

    /**
     * @return int
     */
    public function count(): int
    {
        return count($this->processors);
    }

    /**
     * @param callable|string $callable
     *
     * @return string
     *
     * phpcs:disable Inpsyde.CodeQuality.ArgumentTypeDeclaration.NoArgumentType
     */
    private function buildName($callable): string
    {
        if (is_string($callable)) {
            return $callable;
        }

        if (is_object($callable)) {
            /** @var object $callable */
            return spl_object_hash($callable);
        }

        $class = $callable[0];
        if (is_object($class)) {
            return spl_object_hash($class) . $callable[1];
        }

        return "{$class}::{$callable[1]}";
    }
}

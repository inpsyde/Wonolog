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

namespace Inpsyde\Wonolog\Handler;

use Inpsyde\Wonolog\Processor\ProcessorsRegistry;
use Monolog\Handler\HandlerInterface;

/**
 * @package wonolog
 * @license http://opensource.org/licenses/MIT MIT
 */
class HandlersRegistry implements \Countable
{

    public const ACTION_REGISTER = 'wonolog.register-handlers';
    public const ACTION_SETUP = 'wonolog.handler-setup';
    public const DEFAULT_NAME = 'wonolog.default-handler';

    /**
     * @var HandlerInterface[]
     */
    private $handlers = [];

    /**
     * @var ProcessorsRegistry
     */
    private $processorsRegistry;

    /**
     * @var string[]
     */
    private $initialized;

    /**
     * @param ProcessorsRegistry $processorsRegistry
     */
    public function __construct(ProcessorsRegistry $processorsRegistry)
    {
        $this->processorsRegistry = $processorsRegistry;
    }

    /**
     * @param HandlerInterface $handler
     * @param string $name
     *
     * @return HandlersRegistry
     */
    public function addHandler(HandlerInterface $handler, ?string $name = null): HandlersRegistry
    {
        ($name === null) and $name = spl_object_hash($handler);
        if (array_key_exists($name, $this->handlers)) {
            return $this;
        }

        $this->handlers[$name] = $handler;

        return $this;
    }

    /**
     * @param string|HandlerInterface $name
     *
     * @return bool
     *
     * phpcs:disable Inpsyde.CodeQuality.ArgumentTypeDeclaration.NoArgumentType
     */
    public function hasHandler($name): bool
    {
        $name instanceof HandlerInterface and $name = spl_object_hash($name);

        return is_string($name) && array_key_exists($name, $this->handlers);
    }

    /**
     * @param string|HandlerInterface $name
     *
     * @return HandlerInterface|null
     *
     * phpcs:disable Inpsyde.CodeQuality.ArgumentTypeDeclaration.NoArgumentType
     */
    public function find($name): ?HandlerInterface
    {
        if (! is_array($this->initialized)) {
            $this->initialized = [];

            /**
             * Fires right before the first handler is to be registered.
             *
             * @param HandlersRegistry $handlersRegistry
             */
            do_action(self::ACTION_REGISTER, $this);
        }

        $name = $name instanceof HandlerInterface
            ? spl_object_hash($name)
            : (string) $name;

        if (! $this->hasHandler($name)) {
            return null;
        }

        $handler = $this->handlers[$name];

        if (! in_array($name, $this->initialized, true)) {
            $this->initialized[] = $name;

            /**
             * Fires right after a handler has been registered.
             *
             * @param HandlerInterface $handler
             * @param string $name
             * @param ProcessorsRegistry $processors_registry
             */
            do_action(self::ACTION_SETUP, $handler, $name, $this->processorsRegistry);
        }

        return $handler;
    }

    /**
     * @return int
     */
    public function count(): int
    {
        return count($this->handlers);
    }
}

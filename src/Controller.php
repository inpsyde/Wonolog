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

namespace Inpsyde\Wonolog;

use Inpsyde\Wonolog\Handler\DefaultHandlerFactory;
use Inpsyde\Wonolog\Handler\HandlersRegistry;
use Inpsyde\Wonolog\HookListener\HookListenerInterface;
use Inpsyde\Wonolog\HookListener\HookListenersRegistry;
use Inpsyde\Wonolog\Processor\ProcessorsRegistry;
use Inpsyde\Wonolog\Processor\WpContextProcessor;
use Monolog\Handler\HandlerInterface;
use Monolog\Logger;

/**
 * "Entry point" for package bootstrapping.
 *
 * @package wonolog
 * @license http://opensource.org/licenses/MIT MIT
 */
class Controller
{

    public const ACTION_LOADED = 'wonolog.loaded';
    public const ACTION_SETUP = 'wonolog.setup';
    public const FILTER_DISABLE = 'wonolog.disable';

    /**
     * Initialize Wonolog.
     *
     * @param int $priority
     * @return Controller
     */
    public function setup(int $priority = 100): Controller
    {
        if (did_action(self::ACTION_SETUP)) {
            return $this;
        }

        // We use WONOLOG_DISABLE instead of WONOLOG_ENABLE so that not defined (default) means enabled.
        $disableByEnv = filter_var(getenv('WONOLOG_DISABLE'), FILTER_VALIDATE_BOOLEAN);

        /**
         * Filters whether to completely disable Wonolog.
         *
         * @param bool $disable
         */
        if (apply_filters(self::FILTER_DISABLE, $disableByEnv)) {
            return $this;
        }

        /**
         * Fires right before Wonolog is set up.
         */
        do_action(self::ACTION_SETUP);

        $processorRegistry = new ProcessorsRegistry();
        $handlersRegistry = new HandlersRegistry($processorRegistry);
        $subscriber = new LogActionSubscriber(new Channels($handlersRegistry, $processorRegistry));
        /** @var callable $listener */
        $listener = [$subscriber, 'listen'];

        add_action(LOG, $listener, $priority, PHP_INT_MAX);

        foreach (Logger::getLevels() as $level => $levelCode) {
            // $levelCode goes from 100 (DEBUG) to 600 (EMERGENCY)
            // `$priority + (601 - $levelCode)` makes hook priority based on level priority
            add_action(
                LOG . '.' . strtolower($level),
                $listener,
                $priority + (601 - $levelCode),
                PHP_INT_MAX
            );
        }

        add_action('muplugins_loaded', [HookListenersRegistry::class, 'initialize'], PHP_INT_MAX);

        /**
         * Fires right after Wonolog has been set up.
         */
        do_action(self::ACTION_LOADED);

        return $this;
    }

    /**
     * Tell Wonolog to use the PHP errors handler.
     *
     * @param int|null $errorTypes bitmask of error types constants, default to E_ALL | E_STRICT
     *
     * @return Controller
     */
    public function logPhpErrors(?int $errorTypes = null): Controller
    {
        static $done = false;
        if ($done) {
            return $this;
        }

        $done = true;
        is_int($errorTypes) or $errorTypes = E_ALL | E_STRICT;

        $controller = new PhpErrorController();
        register_shutdown_function([$controller, 'on_fatal']);
        set_error_handler([$controller, 'on_error'], $errorTypes);
        set_exception_handler([$controller, 'on_exception']);

        // Ensure that channel Channels::PHP_ERROR error is there
        add_filter(
            Channels::FILTER_CHANNELS,
            static function (array $channels): array {
                $channels[] = Channels::PHP_ERROR;

                return $channels;
            },
            PHP_INT_MAX
        );

        return $this;
    }

    /**
     * Tell Wonolog to use a default handler that can be passed as argument or build using settings
     * customizable via hooks.
     *
     * @param HandlerInterface|null $handler
     * @return Controller
     */
    public function useDefaultHandler(?HandlerInterface $handler = null): Controller
    {
        static $done = false;
        if ($done) {
            return $this;
        }

        $done = true;

        add_action(
            HandlersRegistry::ACTION_REGISTER,
            static function (HandlersRegistry $registry) use ($handler): void {
                $handler = DefaultHandlerFactory::new($handler)
                    ->createDefaultHandler();

                $registry->addHandler($handler, HandlersRegistry::DEFAULT_NAME);
            },
            1
        );

        return $this;
    }

    /**
     * Tell Wonolog to make given handler available to loggers with given id.
     * If one or more channels are passed, the handler will be attached to related Monolog loggers.
     *
     * @param HandlerInterface $handler
     * @param string[] $channels
     * @param string|null $handlerId
     *
     * @return Controller
     */
    public function useHandler(
        HandlerInterface $handler,
        array $channels = [],
        ?string $handlerId = null
    ): Controller {

        add_action(
            HandlersRegistry::ACTION_REGISTER,
            static function (HandlersRegistry $registry) use ($handlerId, $handler): void {
                $registry->addHandler($handler, $handlerId);
            },
            1
        );

        ($handlerId === null) and $handlerId = $handler;

        add_action(
            Channels::ACTION_LOGGER,
            static function (
                Logger $logger,
                HandlersRegistry $handlers
            ) use (
                $handlerId,
                $channels
            ): void {

                if ($channels === [] || in_array($logger->getName(), $channels, true)) {
                    $handler = $handlers->find($handlerId);
                    $handler and $logger->pushHandler($handler);
                }
            },
            10,
            2
        );

        return $this;
    }

    /**
     * Tell Wonolog to use default log processor.
     *
     * @param callable|null $processor
     * @return Controller
     */
    public function useDefaultProcessor(callable $processor = null): Controller
    {

        static $done = false;
        if ($done) {
            return $this;
        }

        $done = true;

        add_action(
            ProcessorsRegistry::ACTION_REGISTER,
            static function (ProcessorsRegistry $registry) use ($processor) {
                $processor or $processor = new WpContextProcessor();

                $registry->addProcessor($processor, ProcessorsRegistry::DEFAULT_NAME);
            }
        );

        return $this;
    }

    /**
     * Tell Wonolog to make given processor available to loggers with given id.
     * If one or more channels are passed, the processor will be attached to related Monolog loggers.
     *
     * @param callable $processor
     * @param string[] $channels
     * @param $processorId
     * @return Controller
     */
    public function useProcessor(
        callable $processor,
        array $channels = [],
        ?string $processorId = null
    ): Controller {

        add_action(
            ProcessorsRegistry::ACTION_REGISTER,
            static function (ProcessorsRegistry $registry) use ($processorId, $processor) {

                $registry->addProcessor($processor, $processorId);
            }
        );

        ($processorId === null) and $processorId = $processor;

        add_action(
            Channels::ACTION_LOGGER,
            static function (
                Logger $logger,
                HandlersRegistry $handlers,
                ProcessorsRegistry $processors
            ) use (
                $processorId,
                $channels
            ) {

                if ($channels === [] || in_array($logger->getName(), $channels, true)) {
                    $processor = $processors->find($processorId);
                    $processor and $logger->pushProcessor($processor);
                }
            },
            10,
            3
        );

        return $this;
    }

    /**
     * Tell Wonolog to make given processor available to loggers with given id. If one or more
     * channels are passed the processor will be attached to related Monolog loggers.
     *
     * @param callable $processor
     * @param string[] $handlers
     * @param string|null $processorId
     *
     * @return Controller
     */
    public function useProcessorForHandlers(
        callable $processor,
        array $handlers = [],
        ?string $processorId = null
    ): Controller {

        add_action(
            ProcessorsRegistry::ACTION_REGISTER,
            static function (ProcessorsRegistry $registry) use ($processorId, $processor): void {
                $registry->addProcessor($processor, $processorId);
            }
        );

        ($processorId === null) and $processorId = $processor;

        add_action(
            HandlersRegistry::ACTION_SETUP,
            static function (
                HandlerInterface $handler,
                string $handlerId,
                ProcessorsRegistry $processors
            ) use (
                $processorId,
                $handlers
            ) {
                if ($handlers === [] || in_array($handlerId, $handlers, true)) {
                    $processor = $processors->find($processorId);
                    $processor and $handler->pushProcessor($processor);
                }
            },
            10,
            3
        );

        return $this;
    }

    /**
     * Tell Wonolog to use all default hook listeners.
     *
     * @return Controller
     */
    public function useDefaultHookListeners(): Controller
    {
        static $done = false;
        if ($done) {
            return $this;
        }

        $done = true;

        add_action(
            HookListenersRegistry::ACTION_REGISTER,
            static function (HookListenersRegistry $registry) {
                $registry
                    ->registerListener(new HookListener\DbErrorListener())
                    ->registerListener(new HookListener\FailedLoginListener())
                    ->registerListener(new HookListener\HttpApiListener())
                    ->registerListener(new HookListener\MailerListener())
                    ->registerListener(new HookListener\QueryErrorsListener())
                    ->registerListener(new HookListener\CronDebugListener())
                    ->registerListener(new HookListener\WpDieHandlerListener());
            }
        );

        return $this;
    }

    /**
     * Tell Wonolog to use given hook listener.
     *
     * @param HookListenerInterface $listener
     * @return Controller
     */
    public function useHookListener(HookListenerInterface $listener): Controller
    {
        add_action(
            HookListenersRegistry::ACTION_REGISTER,
            static function (HookListenersRegistry $registry) use ($listener): void {
                $registry->registerListener($listener);
            }
        );

        return $this;
    }
}

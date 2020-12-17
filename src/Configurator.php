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

use Inpsyde\Wonolog\HookListener\ActionListener;
use Inpsyde\Wonolog\HookListener\CronDebugListener;
use Inpsyde\Wonolog\HookListener\DbErrorListener;
use Inpsyde\Wonolog\HookListener\FailedLoginListener;
use Inpsyde\Wonolog\HookListener\FilterListener;
use Inpsyde\Wonolog\HookListener\HttpApiListener;
use Inpsyde\Wonolog\HookListener\MailerListener;
use Inpsyde\Wonolog\HookListener\QueryErrorsListener;
use Inpsyde\Wonolog\HookListener\WpDieHandlerListener;
use Monolog\Handler\HandlerInterface;
use Monolog\Logger;

class Configurator
{
    public const ACTION_LOADED = 'wonolog.loaded';
    public const ACTION_SETUP = 'wonolog.setup';
    public const FILTER_DISABLE = 'wonolog.disable';

    private const CONF_MAIN_HOOK_PRIORITY = 'main-hook-priority';
    private const CONF_ERROR_TYPES = 'php-error-types';
    private const CONF_LOG_EXCEPTIONS = 'php-exceptions';
    private const CONF_DEFAULT_HANDLER = 'use-default-handler';
    private const CONF_WP_CONTEXT_PROCESSOR = 'use-wp-context-processor';
    private const CONF_DEFAULT_LISTENERS = 'use-default-hook-listeners';
    private const CONF_HOOK_ALIASES = 'log-hook-aliases';
    private const CONF_SILENCED_ERRORS = 'log-silenced-errors';
    private const CONF_DEFAULT_CHANNEL = 'default-channel';
    private const ALL = 'all';
    private const ENABLED = 'enabled';
    private const DISABLED = 'disabled';

    private const DEFAULT_HOOK_LISTENERS = [
        DbErrorListener::class,
        FailedLoginListener::class,
        HttpApiListener::class,
        MailerListener::class,
        QueryErrorsListener::class,
        CronDebugListener::class,
        WpDieHandlerListener::class,
    ];

    /**
     * @var Factory
     */
    private $factory;

    /**
     * @var array
     */
    private $config = [
        self::CONF_MAIN_HOOK_PRIORITY => 100,
        self::CONF_DEFAULT_CHANNEL => Channels::DEBUG,
        self::CONF_ERROR_TYPES => null,
        self::CONF_LOG_EXCEPTIONS => true,
        self::CONF_WP_CONTEXT_PROCESSOR => true,
        self::CONF_HOOK_ALIASES => [],
        self::CONF_SILENCED_ERRORS => false,
        self::CONF_DEFAULT_HANDLER => [
            self::ALL => null,
            self::ENABLED => [],
            self::DISABLED => [],
        ],
        self::CONF_DEFAULT_LISTENERS => [
            self::ALL => true,
            self::ENABLED => [],
            self::DISABLED => [],
        ],
    ];

    /**
     * @return Configurator
     */
    public static function new(): Configurator
    {
        return new self(Factory::new());
    }

    /**
     * @param Factory $factory
     */
    private function __construct(Factory $factory)
    {
        $this->factory = $factory;
    }

    /**
     * @param string $channel
     * @param string ...$channels
     * @return static
     */
    public function withChannels(string $channel, string ...$channels): Configurator
    {
        $channel and $this->factory->channels()->addChannel($channel);
        foreach ($channels as $channel) {
            $channel and $this->factory->channels()->addChannel($channel);
        }

        return $this;
    }

    /**
     * @param string $channel
     * @return static
     */
    public function withDefaultChannel(string $channel): Configurator
    {
        if ($channel) {
            $this->factory->channels()->addChannel($channel);
            $this->config[self::CONF_DEFAULT_CHANNEL] = $channel;
        }

        return $this;
    }

    /**
     * @param string $channel
     * @param string ...$channels
     * @return static
     */
    public function withoutChannels(string $channel, string ...$channels): Configurator
    {
        $this->factory->channels()->removeChannel($channel);
        foreach ($channels as $channel) {
            $this->factory->channels()->removeChannel($channel);
        }

        return $this;
    }

    /**
     * @param callable(string):\Psr\Log\LoggerInterface $factory
     * @return $this
     */
    public function withLoggerFactory(callable $factory): Configurator
    {
        $this->factory->channels()->withLoggerFactory($factory);

        return $this;
    }

    /**
     * @param callable(string):\Psr\Log\LoggerInterface $factory
     * @param string $channel
     * @param string ...$channels
     * @return static
     */
    public function withLoggerFactoryForChannels(
        callable $factory,
        string $channel,
        string ...$channels
    ): Configurator {

        $this->factory->channels()->withLoggerFactory($factory, $channel, ...$channels);

        $this->withChannels($channel, ...$channels);

        return $this;
    }

    /**
     * @return static
     */
    public function withoutLoggerFactory(): Configurator
    {
        $this->factory->channels()->withoutLoggerFactory();

        return $this;
    }

    /**
     * @param string $channel
     * @param string ...$channels
     * @return static
     */
    public function withoutLoggerFactoryForChannels(
        string $channel,
        string ...$channels
    ): Configurator {

        $this->factory->channels()->withoutLoggerFactory($channel, ...$channels);

        return $this;
    }

    /**
     * @param HandlerInterface $handler
     * @param string|null $identifier
     * @return static
     */
    public function pushHandler(HandlerInterface $handler, ?string $identifier = null): Configurator
    {
        $this->factory->handlersRegistry()->addHandler($handler, $identifier);

        return $this;
    }

    /**
     * @param HandlerInterface $handler
     * @param string|null $identifier
     * @param string $channel
     * @param string ...$channels
     * @return static
     */
    public function pushHandlerForChannels(
        HandlerInterface $handler,
        ?string $identifier,
        string $channel,
        string ...$channels
    ): Configurator {

        $this->factory->handlersRegistry()->addHandler(
            $handler,
            $identifier,
            $channel,
            ...$channels
        );

        $this->withChannels($channel, ...$channels);

        return $this;
    }

    /**
     * @param string $channel
     * @param string $handlerIdentifier
     * @param string ...$handlerIdentifiers
     * @return static
     */
    public function enableHandlersForChannel(
        string $channel,
        string $handlerIdentifier,
        string ...$handlerIdentifiers
    ): Configurator {

        $this->withChannels($channel);

        $this->factory->channels()
            ->enableHandlersForChannel($channel, $handlerIdentifier, ...$handlerIdentifiers);

        return $this;
    }

    /**
     * @param string $handlerIdentifier
     * @param string $channel
     * @param string ...$channels
     * @return static
     */
    public function enableHandlerForChannels(
        string $handlerIdentifier,
        string $channel,
        string ...$channels
    ): Configurator {

        $this->withChannels($channel, ...$channels);

        $this->factory->channels()
            ->enableHandlerForChannels($handlerIdentifier, $channel, ...$channels);

        return $this;
    }

    /**
     * @param string $identifier
     * @param callable(array):array $processor
     * @return static
     */
    public function pushProcessor(string $identifier, callable $processor): Configurator
    {
        $this->factory->processorsRegistry()->addProcessor($processor, $identifier);

        return $this;
    }

    /**
     * @param string $identifier
     * @param callable(array):array $processor
     * @param string $channel
     * @param string ...$channels
     * @return static
     */
    public function pushProcessorForChannels(
        string $identifier,
        callable $processor,
        string $channel,
        string ...$channels
    ): Configurator {

        $this->factory->processorsRegistry()
            ->addProcessor($processor, $identifier, $channel, ...$channels);

        return $this;
    }

    /**
     * @param string $identifier
     * @param ActionListener $listener
     * @return static
     */
    public function addActionListener(string $identifier, ActionListener $listener): Configurator
    {
        $this->factory->listenersRegistry()->addActionListener($identifier, $listener);

        return $this;
    }

    /**
     * @param string $identifier
     * @param ActionListener $listener
     * @param int $priority
     * @return static
     */
    public function addActionListenerWithPriority(
        string $identifier,
        ActionListener $listener,
        int $priority
    ): Configurator {

        $this->factory->listenersRegistry()
            ->addActionListenerWithPriority($identifier, $listener, $priority);

        return $this;
    }

    /**
     * @param string $identifier
     * @param FilterListener $listener
     * @return static
     */
    public function addFilterListener(string $identifier, FilterListener $listener): Configurator
    {
        $this->factory->listenersRegistry()->addFilterListener($identifier, $listener);

        return $this;
    }

    /**
     * @param string $identifier
     * @param FilterListener $listener
     * @param int $priority
     * @return static
     */
    public function addFilterListenerWithPriority(
        string $identifier,
        FilterListener $listener,
        int $priority
    ): Configurator {

        $this->factory->listenersRegistry()
            ->addFilterListenerWithPriority($identifier, $listener, $priority);

        return $this;
    }

    /**
     * @param string $ignorePattern
     * @param int|null $levelThreshold
     * @param string ...$channels
     * @return static
     */
    public function withIgnorePattern(
        string $ignorePattern,
        ?int $levelThreshold = null,
        string ...$channels
    ): Configurator {

        $this->factory
            ->channels()
            ->withIgnorePattern($ignorePattern, $levelThreshold, ...$channels);

        return $this;
    }

    /**
     * @param \DateTimeZone $zone
     * @return static
     */
    public function useTimezone(\DateTimeZone $zone): Configurator
    {
        $this->factory->channels()->useTimezone($zone);

        return $this;
    }

    /**
     * @param int $priority
     * @return static
     */
    public function withMainHookPriority(int $priority): Configurator
    {
        $this->config[self::CONF_MAIN_HOOK_PRIORITY] = $priority;

        return $this;
    }

    /**
     * @return static
     */
    public function logPhpErrorsAndExceptions(): Configurator
    {
        $this->config[self::CONF_ERROR_TYPES] = E_ALL;
        $this->config[self::CONF_LOG_EXCEPTIONS] = true;

        return $this;
    }

    /**
     * @return static
     */
    public function doNotLogPhpErrorsNorExceptions(): Configurator
    {
        $this->config[self::CONF_ERROR_TYPES] = -1;
        $this->config[self::CONF_LOG_EXCEPTIONS] = false;

        return $this;
    }

    /**
     * @return static
     */
    public function doNotLogPhpErrors(): Configurator
    {
        $this->config[self::CONF_ERROR_TYPES] = -1;

        return $this;
    }

    /**
     * @return static
     */
    public function doNotLogPhpExceptions(): Configurator
    {
        $this->config[self::CONF_LOG_EXCEPTIONS] = false;

        return $this;
    }

    /**
     * @param int $errorTypes
     * @return static
     */
    public function logPhpErrorsTypes(int $errorTypes): Configurator
    {
        $this->config[self::CONF_ERROR_TYPES] = $errorTypes;

        return $this;
    }

    /**
     * @return static
     */
    public function logSilencedPhpErrors(): Configurator
    {
        $this->config[self::CONF_SILENCED_ERRORS] = true;

        return $this;
    }

    /**
     * @return static
     */
    public function dontLogSilencedPhpErrors(): Configurator
    {
        $this->config[self::CONF_SILENCED_ERRORS] = true;

        return $this;
    }

    /**
     * @return static
     */
    public function enableDefaultHandler(): Configurator
    {
        $this->config[self::CONF_DEFAULT_HANDLER] = [
            self::ALL => true,
            self::ENABLED => [],
            self::DISABLED => [],
        ];

        return $this;
    }

    /**
     * @param string $channel
     * @param string ...$channels
     * @return $this
     */
    public function enableDefaultHandlerForChannels(
        string $channel,
        string ...$channels
    ): Configurator {

        return $this->toggleDefaultHandlerForChannels(true, $channel, ...$channels);
    }

    /**
     * @param string $channel
     * @param string ...$channels
     * @return static
     */
    public function disableDefaultHandlerForChannels(
        string $channel,
        string ...$channels
    ): Configurator {

        return $this->toggleDefaultHandlerForChannels(false, $channel, ...$channels);
    }

    /**
     * @return static
     */
    public function disableDefaultHandler(): Configurator
    {
        $this->config[self::CONF_DEFAULT_HANDLER] = [
            self::ALL => false,
            self::ENABLED => [],
            self::DISABLED => [],
        ];

        return $this;
    }

    /**
     * @return static
     */
    public function enableWpContextProcessor(): Configurator
    {
        $this->config[self::CONF_WP_CONTEXT_PROCESSOR] = true;

        return $this;
    }

    /**
     * @return static
     */
    public function disableWpContextProcessor(): Configurator
    {
        $this->config[self::CONF_WP_CONTEXT_PROCESSOR] = false;

        return $this;
    }

    /**
     * @return static
     */
    public function enableAllDefaultHookListeners(): Configurator
    {
        $this->config[self::CONF_DEFAULT_LISTENERS] = [
            self::ALL => true,
            self::ENABLED => [],
            self::DISABLED => [],
        ];

        return $this;
    }

    /**
     * @param class-string<HookListener\HookListener> $listener
     * @param class-string<HookListener\HookListener> ...$listeners
     * @return static
     */
    public function enableDefaultHookListeners(string $listener, string ...$listeners): Configurator
    {
        return $this->toggleDefaultHookListeners(true, $listener, ...$listeners);
    }

    /**
     * @param class-string<HookListener\HookListener> $listener
     * @param class-string<HookListener\HookListener> ...$listeners
     * @return static
     */
    public function disableDefaultHookListeners(
        string $listener,
        string ...$listeners
    ): Configurator {

        return $this->toggleDefaultHookListeners(false, $listener, ...$listeners);
    }

    /**
     * @return static
     */
    public function disableAllDefaultHookListeners(): Configurator
    {
        $this->config[self::CONF_DEFAULT_LISTENERS] = [
            self::ALL => false,
            self::ENABLED => [],
            self::DISABLED => [],
        ];

        return $this;
    }

    /**
     * @param string $alias
     * @param string|null $defaultChannel
     * @return static
     */
    public function registerLogHookAlias(
        string $alias,
        ?string $defaultChannel = null
    ): Configurator {

        $normalized = strtolower(trim($alias));
        if (!$normalized || $normalized === LOG) {
            return $this;
        }

        $aliases = (array)($this->config[self::CONF_HOOK_ALIASES] ?? []);
        $aliases[$normalized] = $defaultChannel;
        $this->config[self::CONF_HOOK_ALIASES] = $aliases;
        if ($defaultChannel) {
            $this->withChannels($defaultChannel);
        }

        return $this;
    }

    /**
     * @return void
     */
    public function setup(): void
    {
        static $doingSetup;
        if ($doingSetup || !$this->shouldSetup()) {
            return;
        }

        // Prevent endless recursion if any callback attached to `ACTION_SETUP` this method.
        $doingSetup = true;

        /** Fires right before Wonolog is set up. */
        do_action(self::ACTION_SETUP, $this);

        $doingSetup = false;

        if (!$this->setupDefaultHandler()) {
            return;
        }

        $this->setupWpContextProcessor();
        $this->setupPhpErrorListener();

        $maxSeverity = (int)max(Logger::getLevels() ?: [Logger::EMERGENCY]);
        $defaultChannel = (string)$this->config[self::CONF_DEFAULT_CHANNEL];
        $this->setupLogActionSubscriberForHook(LOG, $defaultChannel, $maxSeverity);

        foreach ((array)$this->config[self::CONF_HOOK_ALIASES] as $alias => $aliasChannel) {
            $channel = (string)($aliasChannel ?? $defaultChannel);
            $this->setupLogActionSubscriberForHook((string)$alias, $channel, $maxSeverity);
        }

        $this->setupHookListeners();

        /** Fires right after Wonolog has been set up.*/
        do_action(self::ACTION_LOADED, $this->factory);
    }

    /**
     * @return bool
     */
    private function shouldSetup(): bool
    {
        if (did_action(self::ACTION_SETUP)) {
            return false;
        }

        // We use WONOLOG_DISABLE instead of WONOLOG_ENABLE so that enabled is the default.
        $disabled = getenv('WONOLOG_DISABLE');
        defined('WONOLOG_DISABLE') and $disabled = WONOLOG_DISABLE;

        /**
         * Filters whether to completely disable Wonolog.
         *
         * @param bool $disable
         */
        if (apply_filters(self::FILTER_DISABLE, filter_var($disabled, FILTER_VALIDATE_BOOLEAN))) {
            return false;
        }

        return true;
    }

    /**
     * @param bool $trueForEnable
     * @param string $channel
     * @param string ...$channels
     * @return static
     */
    private function toggleDefaultHandlerForChannels(
        bool $trueForEnable,
        string $channel,
        string ...$channels
    ): Configurator {

        $config = (array)($this->config[self::CONF_DEFAULT_HANDLER] ?? []);
        $config[self::ALL] = null;

        $enabled = (array)($config[self::ENABLED] ?? []);
        $disabled = (array)($config[self::DISABLED] ?? []);

        array_unshift($channels, $channel);
        foreach ($channels as $channel) {
            if ($trueForEnable) {
                $enabled[$channel] = 1;
                unset($disabled[$channel]);
                continue;
            }

            $disabled[$channel] = 1;
            unset($enabled[$channel]);
        }

        $config[self::ENABLED] = $enabled;
        $config[self::DISABLED] = $disabled;
        $this->config[self::CONF_DEFAULT_HANDLER] = $config;

        return $this;
    }

    /**
     * @param bool $trueForEnable
     * @param string $listener
     * @param string ...$listeners
     * @return static
     */
    private function toggleDefaultHookListeners(
        bool $trueForEnable,
        string $listener,
        string ...$listeners
    ): Configurator {

        $config = (array)($this->config[self::CONF_DEFAULT_LISTENERS] ?? []);
        $config[self::ALL] = null;

        $enabled = (array)($config[self::ENABLED] ?? []);
        $disabled = (array)($config[self::DISABLED] ?? []);

        array_unshift($listeners, $listener);
        foreach ($listeners as $listener) {
            if ($trueForEnable) {
                $enabled[$listener] = 1;
                unset($disabled[$listener]);
                continue;
            }

            $disabled[$listener] = 1;
            unset($enabled[$listener]);
        }

        $config[self::ENABLED] = $enabled;
        $config[self::DISABLED] = $disabled;
        $this->config[self::CONF_DEFAULT_LISTENERS] = $config;

        return $this;
    }

    /**
     * @return bool
     */
    private function setupDefaultHandler(): bool
    {
        $config = (array)($this->config[self::CONF_DEFAULT_HANDLER] ?? []);

        $enabledForAll = ($config[self::ALL] ?? null) === true;
        $disabledForAll = ($config[self::ALL] ?? null) === false;
        /** @var array<string> $enabledChannels */
        $enabledChannels = array_keys((array)$config[self::ENABLED]);
        /** @var array<string> $disabledChannels */
        $disabledChannels = array_keys((array)$config[self::DISABLED]);

        if (!$enabledForAll && !$disabledForAll && !$enabledChannels && !$disabledChannels) {
            $enabledForAll = true;
        }

        $identifier = DefaultHandler::id();
        $handlers = $this->factory->handlersRegistry();
        $defaultAdded = $handlers->findById($identifier);
        $handlersNum = count($handlers);
        $onlyDefaultAdded = $defaultAdded && ($handlersNum === 1);

        // If the only registered handler is the default one, and default handler is disabled
        // we need to disable Wonolog.
        if ($onlyDefaultAdded && $disabledForAll) {
            return false;
        }

        if ($enabledForAll || $disabledForAll) {
            if ($enabledForAll) {
                $handlers->addHandler(DefaultHandler::new(), $identifier);
                $handlersNum++;
            }

            return $handlersNum > 0;
        }

        // If default handler is already added, we need to make sure that it will not be used for
        // channels that were explicitly disabled or not explicitly enabled.
        if ($defaultAdded) {
            if ($disabledChannels) {
                $handlers->removeHandlerFromChannels($identifier, ...$disabledChannels);
            }
            if ($enabledChannels) {
                $allChannels = $this->factory->channels()->allNames();
                $toDisable = array_diff($allChannels, $enabledChannels, $disabledChannels);
                $toDisable and $handlers->removeHandlerFromChannels($identifier, ...$toDisable);
            }

            /*
             * `$handlers->removeHandlerFromChannels` might remove the handler if there are no
             * channels for it anymore.
             * So in the case default was the only handler we need to be sure it is still added.
             */

            return $onlyDefaultAdded ? count($handlers) > 0 : true;
        }

        // If default handler is not added, and according to settings we can't add it we need to
        // disable Wonolog if there are no other handlers.
        $toEnable = array_diff($enabledChannels, $disabledChannels);
        if (!$toEnable) {
            return $handlersNum > 0;
        }

        $handlers->addHandler(DefaultHandler::new(), $identifier, ...$toEnable);

        return true;
    }

    /**
     * @return void
     */
    private function setupWpContextProcessor(): void
    {
        $processors = $this->factory->processorsRegistry();
        if ($this->config[self::CONF_WP_CONTEXT_PROCESSOR]) {
            $processors->addProcessor(WpContextProcessor::new(), WpContextProcessor::class);
        }
    }

    /**
     * @return void
     */
    private function setupPhpErrorListener(): void
    {
        $errorTypes = (int)($this->config[self::CONF_ERROR_TYPES] ?? (E_ALL | E_STRICT));
        $exceptions = (bool)($this->config[self::CONF_LOG_EXCEPTIONS] ?? false);

        if (($errorTypes <= 0) && !$exceptions) {
            return;
        }

        $this->withChannels(Channels::PHP_ERROR);

        $controller = PhpErrorController::new((bool)$this->config[self::CONF_SILENCED_ERRORS]);

        $exceptions and set_exception_handler([$controller, 'onException']);

        if ($errorTypes <= 0) {
            return;
        }

        set_error_handler([$controller, 'onError'], $errorTypes);
        if (PhpErrorController::typesMaskContainsFatals($errorTypes)) {
            register_shutdown_function([$controller, 'onShutdown']);
        }
    }

    /**
     * @return void
     */
    private function setupHookListeners(): void
    {
        $listeners = $this->factory->listenersRegistry();

        /** @var bool|array $config */
        $config = $this->config[self::CONF_DEFAULT_LISTENERS];
        $allEnabled = ($config[self::ALL] ?? null) === true;
        $allDisabled = ($config[self::ALL] ?? null) === false;
        $enabled = array_keys((array)($config[self::ENABLED] ?? []));
        $disabled = array_keys((array)($config[self::DISABLED] ?? []));

        /** @var array<class-string<HookListener\HookListener>> $enabled */
        $enabled and $enabled = array_intersect($enabled, self::DEFAULT_HOOK_LISTENERS);
        /** @var array<class-string<HookListener\HookListener>> $disabled */
        $disabled and $disabled = array_intersect($disabled, self::DEFAULT_HOOK_LISTENERS);

        /** @var array<class-string<HookListener\HookListener>> $toEnable */
        $toEnable = ($allEnabled || $allDisabled)
            ? ($allEnabled ? self::DEFAULT_HOOK_LISTENERS : [])
            : array_diff(array_intersect(self::DEFAULT_HOOK_LISTENERS, $enabled), $disabled);

        foreach ($toEnable as $class) {
            /** @var ActionListener|FilterListener $listener */
            $listener = new $class();
            if ($listener instanceof ActionListener) {
                $listeners->addActionListener($class, $listener);
                continue;
            }

            $listeners->addFilterListener($class, $listener);
        }

        $listeners->listenAll((int)$this->config[self::CONF_MAIN_HOOK_PRIORITY]);
    }

    /**
     * @param string $hook
     * @param string $defaultChannel
     * @param int $maxSeverity
     * @return void
     */
    private function setupLogActionSubscriberForHook(
        string $hook,
        string $defaultChannel,
        int $maxSeverity
    ): void {

        [$listen, $listenWithLevel] = $this->listenCallbacksData($defaultChannel);

        $basePriority = (int)$this->config[self::CONF_MAIN_HOOK_PRIORITY];
        add_action($hook, $listen, $basePriority + $maxSeverity + 1, PHP_INT_MAX);

        foreach ($listenWithLevel as $levelName => [$callback, $severity]) {
            // `$basePriority + ($maxLevel - $severity)` means higher severity (more urgent)
            //  will be added with a lower hook priority (executed earlier).
            $hookPriority = $basePriority + ($maxSeverity - $severity);
            add_action("{$hook}." . strtolower($levelName), $callback, $hookPriority, PHP_INT_MAX);
        }
    }

    /**
     * @param string $defaultChannel
     * @return array{callable, array<string, array{callable, int}>}
     */
    private function listenCallbacksData(string $defaultChannel): array
    {
        /** @var array<string, array{callable, array<string, array{callable, int}>}> $data */
        static $data;
        if (isset($data[$defaultChannel])) {
            return $data[$defaultChannel];
        }

        $subscriber = $this->factory->logActionSubscriber();

        // phpcs:disable Inpsyde.CodeQuality.ArgumentTypeDeclaration
        $listen = static function (...$args) use ($subscriber, $defaultChannel): void {
            $subscriber->listen($args, null, $defaultChannel);
        };

        $listenWithLevel = [];

        /**
         * @var string $level
         * @var int $severity
         */
        foreach (Logger::getLevels() as $level => $severity) {
            $callable = static function (...$args) use ($subscriber, $level, $defaultChannel) {
                $subscriber->listen($args, $level, $defaultChannel);
            };

            $listenWithLevel[$level] = [$callable, $severity];
        }
        // phpcs:enable Inpsyde.CodeQuality.ArgumentTypeDeclaration

        $data[$defaultChannel] = [$listen, $listenWithLevel];

        return $data[$defaultChannel];
    }
}

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

use Inpsyde\Wonolog\HookListener;
use Monolog\Handler\HandlerInterface;
use Psr\Log\LoggerInterface;
use Inpsyde\Wonolog\Processor\WpContextProcessor;

class Configurator
{
    public const ACTION_LOADED = 'wonolog.loaded';
    public const ACTION_SETUP = 'wonolog.setup';
    public const FILTER_DISABLE = 'wonolog.disable';

    protected const CONF_BASE_HOOK_PRIORITY = 'main-hook-priority';
    protected const CONF_ERROR_TYPES = 'php-error-types';
    protected const CONF_LOG_EXCEPTIONS = 'php-exceptions';
    protected const CONF_FALLBACK_HANDLER = 'use-default-handler';
    protected const CONF_WP_CONTEXT_PROCESSOR = 'use-wp-context-processor';
    protected const CONF_DEFAULT_LISTENERS = 'use-default-hook-listeners';
    protected const CONF_HOOK_ALIASES = 'log-hook-aliases';
    protected const CONF_SILENCED_ERRORS = 'log-silenced-errors';
    protected const ALL = 'all';
    protected const ENABLED = 'enabled';
    protected const DISABLED = 'disabled';

    protected const DEFAULT_HOOK_LISTENERS = [
        HookListener\DbErrorListener::class,
        HookListener\FailedLoginListener::class,
        HookListener\HttpApiListener::class,
        HookListener\MailerListener::class,
        HookListener\QueryErrorsListener::class,
        HookListener\CronDebugListener::class,
        HookListener\WpDieHandlerListener::class,
    ];

    /**
     * @var Factory
     */
    protected $factory;

    /**
     * @var array
     */
    protected $config = [
        self::CONF_BASE_HOOK_PRIORITY => 100,
        self::CONF_ERROR_TYPES => null,
        self::CONF_LOG_EXCEPTIONS => true,
        self::CONF_HOOK_ALIASES => [],
        self::CONF_SILENCED_ERRORS => false,
        self::CONF_WP_CONTEXT_PROCESSOR => [
            self::ALL => true,
            self::ENABLED => [],
            self::DISABLED => [],
        ],
        self::CONF_FALLBACK_HANDLER => [
            self::ALL => true,
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
    protected function __construct(Factory $factory)
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
     * @param string $channel
     * @return static
     */
    public function withDefaultChannel(string $channel): Configurator
    {
        if ($channel) {
            $this->factory->channels()->useDefaultChannel($channel);
        }

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

        $this->factory->handlersRegistry()
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

        $this->factory->handlersRegistry()
            ->enableHandlerForChannels($handlerIdentifier, $channel, ...$channels);

        return $this;
    }

    /**
     * @param string $identifier
     * @return static
     */
    public function removeHandler(string $identifier): Configurator
    {
        $this->factory->handlersRegistry()->removeHandler($identifier);

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
    ): Configurator {

        $this->factory->handlersRegistry()->removeHandlerFromChannels(
            $identifier,
            $channel,
            ...$channels
        );

        return $this;
    }

    /**
     * @return static
     */
    public function enableFallbackHandler(): Configurator
    {
        $this->config[self::CONF_FALLBACK_HANDLER] = [
            self::ALL => true,
            self::ENABLED => [],
            self::DISABLED => [],
        ];

        return $this;
    }

    /**
     * @return static
     */
    public function disableFallbackHandler(): Configurator
    {
        $this->config[self::CONF_FALLBACK_HANDLER] = [
            self::ALL => false,
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
    public function enableFallbackHandlerForChannels(
        string $channel,
        string ...$channels
    ): Configurator {

        $this->withChannels($channel, ...$channels);

        return $this->toggleEnabledDisabledConfig(
            self::CONF_FALLBACK_HANDLER,
            true,
            $channel,
            ...
            $channels
        );
    }

    /**
     * @param string $channel
     * @param string ...$channels
     * @return static
     */
    public function disableFallbackHandlerForChannels(
        string $channel,
        string ...$channels
    ): Configurator {

        return $this->toggleEnabledDisabledConfig(
            self::CONF_FALLBACK_HANDLER,
            false,
            $channel,
            ...
            $channels
        );
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

        $this->withChannels($channel, ...$channels);

        $this->factory->processorsRegistry()
            ->addProcessor($processor, $identifier, $channel, ...$channels);

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
    ): Configurator {

        $this->withChannels($channel);

        $this->factory->processorsRegistry()
            ->enableProcessorsForChannel($channel, $identifier, ...$identifiers);

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
    ): Configurator {

        $this->withChannels($channel, ...$channels);

        $this->factory->processorsRegistry()
            ->enableProcessorForChannels($identifier, $channel, ...$channels);

        return $this;
    }

    /**
     * @param string $identifier
     * @return static
     */
    public function removeProcessor(string $identifier): Configurator
    {
        $this->factory->processorsRegistry()->removeProcessor($identifier);

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
    ): Configurator {

        $this->factory->processorsRegistry()
            ->removeProcessorFromChannels($identifier, $channel, ...$channels);

        return $this;
    }

    /**
     * @return static
     */
    public function enableWpContextProcessor(): Configurator
    {
        $this->config[self::CONF_WP_CONTEXT_PROCESSOR] = [
            self::ALL => true,
            self::ENABLED => [],
            self::DISABLED => [],
        ];

        return $this;
    }

    /**
     * @return static
     */
    public function disableWpContextProcessor(): Configurator
    {
        $this->config[self::CONF_WP_CONTEXT_PROCESSOR] = [
            self::ALL => false,
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
    public function enableWpContextProcessorForChannels(
        string $channel,
        string ...$channels
    ): Configurator {

        $this->withChannels($channel, ...$channels);

        return $this->toggleEnabledDisabledConfig(
            self::CONF_WP_CONTEXT_PROCESSOR,
            true,
            $channel,
            ...
            $channels
        );
    }

    /**
     * @param string $channel
     * @param string ...$channels
     * @return $this
     */
    public function disableWpContextProcessorForChannels(
        string $channel,
        string ...$channels
    ): Configurator {

        return $this->toggleEnabledDisabledConfig(
            self::CONF_WP_CONTEXT_PROCESSOR,
            false,
            $channel,
            ...
            $channels
        );
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
     * @param class-string<HookListener\HookListener> $listener
     * @param class-string<HookListener\HookListener> ...$listeners
     * @return static
     */
    public function enableDefaultHookListeners(string $listener, string ...$listeners): Configurator
    {
        return $this->toggleEnabledDisabledConfig(
            self::CONF_DEFAULT_LISTENERS,
            true,
            $listener,
            ...
            $listeners
        );
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

        return $this->toggleEnabledDisabledConfig(
            self::CONF_DEFAULT_LISTENERS,
            false,
            $listener,
            ...
            $listeners
        );
    }

    /**
     * @param HookListener\ActionListener $listener
     * @param string|null $identifier
     * @return static
     */
    public function addActionListener(
        HookListener\ActionListener $listener,
        ?string $identifier = null
    ): Configurator {

        $identifier = $identifier ?? get_class($listener);
        $this->factory->listenersRegistry()->addActionListener($identifier, $listener);

        return $this;
    }

    /**
     * @param HookListener\ActionListener $listener
     * @param int $priority
     * @param string|null $identifier
     * @return static
     */
    public function addActionListenerWithPriority(
        HookListener\ActionListener $listener,
        int $priority,
        ?string $identifier = null
    ): Configurator {

        $identifier = $identifier ?? get_class($listener);
        $this->factory->listenersRegistry()
            ->addActionListenerWithPriority($identifier, $listener, $priority);

        return $this;
    }

    /**
     * @param HookListener\FilterListener $listener
     * @param string|null $identifier
     * @return static
     */
    public function addFilterListener(
        HookListener\FilterListener $listener,
        ?string $identifier = null
    ): Configurator {

        $identifier = $identifier ?? get_class($listener);
        $this->factory->listenersRegistry()->addFilterListener($identifier, $listener);

        return $this;
    }

    /**
     * @param HookListener\FilterListener $listener
     * @param int $priority
     * @param string|null $identifier
     * @return static
     */
    public function addFilterListenerWithPriority(
        HookListener\FilterListener $listener,
        int $priority,
        ?string $identifier = null
    ): Configurator {

        $identifier = $identifier ?? get_class($listener);
        $this->factory->listenersRegistry()
            ->addFilterListenerWithPriority($identifier, $listener, $priority);

        return $this;
    }

    /**
     * @param string $alias
     * @param string|null $defaultChannel
     * @return static
     */
    public function registerLogHook(
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
     * @param int $priority
     * @return static
     */
    public function withBaseHookPriority(int $priority): Configurator
    {
        $this->config[self::CONF_BASE_HOOK_PRIORITY] = $priority;

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
     * @param \DateTimeZone $zone
     * @return static
     */
    public function useTimezone(\DateTimeZone $zone): Configurator
    {
        $this->factory->channels()->useTimezone($zone);

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

        $channels = $this->factory->channels();

        [$errorTypes, $exceptions] = $this->shouldlogErrorsAndExceptions();
        if (($errorTypes > 0) || $exceptions) {
            $channels->addChannel(Channels::PHP_ERROR);
        }

        if (!$this->setupFallbackHandler($channels)) {
            return;
        }

        $this->setupPhpErrorListener($errorTypes, $exceptions);
        $this->setupWpContextProcessor($channels);

        $maxSeverity = (int)max(LogLevel::allLevels() ?: [LogLevel::EMERGENCY]);
        $defaultChannel = $channels->defaultChannel();
        $this->setupLogActionSubscriberForHook(LOG, $defaultChannel, $maxSeverity);

        foreach ((array)$this->config[self::CONF_HOOK_ALIASES] as $alias => $aliasChannel) {
            $channel = (string)($aliasChannel ?? $defaultChannel);
            $this->setupLogActionSubscriberForHook((string)$alias, $channel, $maxSeverity);
        }

        $this->setupHookListeners();

        // This ensures the `add_action` inside `makeLogger` function is executed.
        makeLogger();

        /** @var callable(?string):LoggerInterface $factoryCb */
        $factoryCb = [$this->factory, 'psr3Logger'];
        $psr3Factory = static function (?string $channel = null) use ($factoryCb): LoggerInterface {
            return $factoryCb($channel);
        };

        /**
         * Fires right after Wonolog has been set up.
         *
         * Passes a factory that creates an PSR-3 LoggerInterface that can be injected into any
         * object that can make use of it, e.g. any object implementing `LoggerAwareInterface`.
         * This hook can only be used in MU plugins.
         * Plugins/themes/etc. can use `makeLogger()` if they need a PSR-3 compliant logger.
         */
        do_action(self::ACTION_LOADED, $psr3Factory);
        remove_all_actions(self::ACTION_LOADED);
    }

    /**
     * @return bool
     */
    protected function shouldSetup(): bool
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
     * @return array{int, bool}
     */
    private function shouldlogErrorsAndExceptions(): array
    {
        $errorTypes = (int)($this->config[self::CONF_ERROR_TYPES] ?? (E_ALL | E_STRICT));
        $exceptions = (bool)($this->config[self::CONF_LOG_EXCEPTIONS] ?? false);

        return [$errorTypes, $exceptions];
    }

    /**
     * @param string $key
     * @param bool $trueForEnable
     * @param string $configValue
     * @param string ...$configValues
     * @return static
     */
    private function toggleEnabledDisabledConfig(
        string $key,
        bool $trueForEnable,
        string $configValue,
        string ...$configValues
    ): Configurator {

        $config = (array)($this->config[$key] ?? []);

        $enabled = (array)($config[self::ENABLED] ?? []);
        $disabled = (array)($config[self::DISABLED] ?? []);
        $config[self::ALL] = null;

        array_unshift($configValues, $configValue);
        foreach ($configValues as $configValue) {
            if ($trueForEnable) {
                $enabled[$configValue] = 1;
                unset($disabled[$configValue]);
                continue;
            }

            $disabled[$configValue] = 1;
            unset($enabled[$configValue]);
        }

        $config[self::ENABLED] = $enabled;
        $config[self::DISABLED] = $disabled;
        $this->config[$key] = $config;

        return $this;
    }

    /**
     * @param string $key
     * @param list<string> $allValues
     * @return list<string>|null
     *
     * phpcs:disable Generic.Metrics.CyclomaticComplexity
     */
    private function parseEnabledDisabledConfig(string $key, array $allValues): ?array
    {
        // phpcs:enable Generic.Metrics.CyclomaticComplexity

        /** @var array $config */
        $config = $this->config[$key];

        if (($config[self::ALL] ?? null) === false) { // all disabled
            return null;
        }

        if (($config[self::ALL] ?? null) === true) { // all enabled
            return $allValues;
        }

        $enabled = array_keys((array)($config[self::ENABLED] ?? []));
        $disabled = array_keys((array)($config[self::DISABLED] ?? []));

        $toEnable = null;
        switch (true) {
            case ($enabled && $disabled):
                /** @var list<string> $toEnable */
                $toEnable = array_intersect(array_diff($enabled, $disabled), $allValues) ?: null;
                break;
            case ($enabled):
                /** @var list<string> $toEnable */
                $toEnable = array_intersect($enabled, $allValues) ?: null;
                break;
            case ($disabled):
                /** @var list<string> $toEnable */
                $toEnable = array_diff($allValues, $disabled) ?: null;
                break;
        }

        return $toEnable;
    }

    /**
     * @param Channels $channels
     * @return bool
     */
    protected function setupFallbackHandler(Channels $channels): bool
    {
        $missing = $this->channelsWithoutHandler($channels);
        if (!$missing) {
            return true;
        }

        $toEnable = $this->parseEnabledDisabledConfig(self::CONF_FALLBACK_HANDLER, $missing);

        if ($toEnable === null) {
            return $missing !== $channels->allNames();
        }

        $id = 'wonolog-def-hander-' . bin2hex(random_bytes(3));
        $this->factory->handlersRegistry()
            ->addHandler(DefaultHandler\FileHandler::new(), $id, ...$toEnable);

        return true;
    }

    /**
     * @param Channels $channels
     * @return void
     */
    protected function setupWpContextProcessor(Channels $channels): void
    {
        $allNames = $channels->allNames();
        $toEnable = $this->parseEnabledDisabledConfig(self::CONF_WP_CONTEXT_PROCESSOR, $allNames);

        if ($toEnable !== null) {
            $this->factory->processorsRegistry()
                ->addProcessor(WpContextProcessor::new(), WpContextProcessor::class, ...$toEnable);
        }
    }

    /**
     * @return void
     */
    protected function setupHookListeners(): void
    {
        $listeners = $this->factory->listenersRegistry();

        $defaults = $this->parseEnabledDisabledConfig(
            self::CONF_DEFAULT_LISTENERS,
            self::DEFAULT_HOOK_LISTENERS
        );

        /** @var class-string<HookListener\HookListener> $class */
        foreach (($defaults ?? []) as $class) {
            /** @var HookListener\ActionListener|HookListener\FilterListener $listener */
            $listener = new $class();
            if ($listener instanceof HookListener\ActionListener) {
                $listeners->addActionListener($class, $listener);
                continue;
            }

            $listeners->addFilterListener($class, $listener);
        }

        $listeners->listenAll((int)$this->config[self::CONF_BASE_HOOK_PRIORITY]);
    }

    /**
     * @param int $errorTypes
     * @param bool $logExceptions
     */
    protected function setupPhpErrorListener(int $errorTypes, bool $logExceptions): void
    {
        $updater = $this->factory->logActionUpdater();
        $logSilenced = (bool)$this->config[self::CONF_SILENCED_ERRORS];
        $controller = PhpErrorController::new($logSilenced, $updater);

        $logExceptions and set_exception_handler([$controller, 'onException']);

        if ($errorTypes <= 0) {
            return;
        }

        set_error_handler([$controller, 'onError'], $errorTypes);
        if (PhpErrorController::typesMaskContainsFatals($errorTypes)) {
            register_shutdown_function([$controller, 'onShutdown']);
        }
    }

    /**
     * @param string $hook
     * @param string $defaultChannel
     * @param int $maxSeverity
     * @return void
     */
    protected function setupLogActionSubscriberForHook(
        string $hook,
        string $defaultChannel,
        int $maxSeverity
    ): void {

        [$listen, $listenWithLevel] = $this->listenCallbacksData($defaultChannel);

        $basePriority = (int)$this->config[self::CONF_BASE_HOOK_PRIORITY];
        add_action($hook, $listen, $basePriority + $maxSeverity + 1, PHP_INT_MAX);

        foreach ($listenWithLevel as $levelName => [$callback, $severity]) {
            // `$basePriority + ($maxLevel - $severity)` means higher severity (more urgent)
            //  will be added with a lower hook priority (executed earlier).
            $hookPriority = $basePriority + ($maxSeverity - $severity);
            add_action("{$hook}." . strtolower($levelName), $callback, $hookPriority, PHP_INT_MAX);
        }
    }

    /**
     * @param Channels $channels
     * @return list<string>
     */
    private function channelsWithoutHandler(Channels $channels): array
    {
        $missing = [];
        foreach ($channels->allNames() as $channel) {
            if (!$channels->canLogChannel($channel)) {
                $missing[] = $channel;
            }
        }

        return $missing;
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

        foreach (LogLevel::allLevels() as $level => $severity) {
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

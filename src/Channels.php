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

use Inpsyde\Wonolog\Data\LogData;
use Monolog\Handler\ProcessableHandlerInterface;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class Channels
{
    public const HTTP = 'HTTP';
    public const DB = 'DB';
    public const PHP_ERROR = 'PHP-ERROR';
    public const SECURITY = 'SECURITY';
    public const DEBUG = 'DEBUG';
    public const CRON = 'CRON';
    public const ACTION_LOGGER = 'wonolog.logger';

    public const DEFAULT_CHANNELS = [
        self::HTTP,
        self::DB,
        self::SECURITY,
        self::DEBUG,
        self::CRON,
    ];

    private const ALL_CHANNELS = '~*~';

    /**
     * @var array<string, int>
     */
    private $channels;

    /**
     * @var HandlersRegistry
     */
    private $handlersRegistry;

    /**
     * @var ProcessorsRegistry
     */
    private $processorsRegistry;

    /**
     * @var \DateTimeZone|null
     */
    private $timezone;

    /**
     * @var array<string, LoggerInterface>|null
     */
    private $loggers = [];

    /**
     * @var array<string, array{callable(string, ?\DateTimeZone):LoggerInterface|null, bool}>
     */
    private $loggerFactoryData = [];

    /**
     * @var array<string, array{string, array<string, int|null>}>
     */
    private $ignoreList = [];

    /**
     * @param HandlersRegistry $handlers
     * @param ProcessorsRegistry $processors
     * @return Channels
     */
    public static function new(HandlersRegistry $handlers, ProcessorsRegistry $processors): Channels
    {
        return new self($handlers, $processors);
    }

    /**
     * @param HandlersRegistry $handlers
     * @param ProcessorsRegistry $processors
     */
    private function __construct(HandlersRegistry $handlers, ProcessorsRegistry $processors)
    {
        $this->handlersRegistry = $handlers;
        $this->processorsRegistry = $processors;
        $this->channels = array_fill_keys(self::DEFAULT_CHANNELS, 1);
    }

    /**
     * @param string $channel
     * @return $this
     */
    public function addChannel(string $channel): Channels
    {
        $this->channels[$channel] = 1;

        return $this;
    }

    /**
     * @param string $channel
     * @return $this
     */
    public function removeChannel(string $channel): Channels
    {
        unset($this->channels[$channel]);

        return $this;
    }

    /**
     * @param callable(string, ?\DateTimeZone):LoggerInterface $factory
     * @param string ...$channels
     * @return $this
     */
    public function withLoggerFactory(callable $factory, string ...$channels): Channels
    {
        if (!$channels) {
            $this->loggerFactoryData[self::ALL_CHANNELS] = [$factory, true];

            return $this;
        }

        foreach ($channels as $channel) {
            $this->loggerFactoryData[$channel] = [$factory, true];
        }

        return $this;
    }

    /**
     * @param string ...$channels
     * @return $this
     */
    public function withoutLoggerFactory(string ...$channels): Channels
    {
        if (!$channels) {
            $this->loggerFactoryData = [];

            return $this;
        }

        foreach ($channels as $channel) {
            $this->loggerFactoryData[$channel] = [null, false];
        }

        return $this;
    }

    /**
     * @param string $ignorePattern Regular XP pattern *without* delimiters
     * @param int|null $levelThreshold If provided, will not blacklist logs with severity above it
     * @param string ...$channels Apply blacklist only to this channels. Not provided means all.
     * @return Channels
     */
    public function withIgnorePattern(
        string $ignorePattern,
        ?int $levelThreshold = null,
        string ...$channels
    ): Channels {

        if (!$ignorePattern) {
            return $this;
        }

        $id = 'L' . substr(md5($ignorePattern), -12, 10) . bin2hex(random_bytes(3));
        $ignorePattern = "(?<{$id}>{$ignorePattern})";

        $channels or $channels = [self::ALL_CHANNELS];
        foreach ($channels as $channel) {
            [$pattern, $levels] = $this->ignoreList[$channel] ?? ['', []];
            $pattern and $pattern .= '|';
            $pattern .= $ignorePattern;
            $levels[$id] = $levelThreshold;

            $this->ignoreList[$channel] = [$pattern, $levels];
        }

        return $this;
    }

    /**
     * @param LogData $log
     * @return bool
     */
    public function isIgnored(LogData $log): bool
    {
        [$pattern, $levels] = $this->ignoreList[$log->channel()]
            ?? $this->ignoreList[self::ALL_CHANNELS]
            ?? [null, []];

        if (!$pattern) {
            return false;
        }

        $matches = [];
        try {
            $pattern = str_replace('~', '\~', $pattern);
            // phpcs:disable WordPress.PHP.NoSilencedErrors
            @preg_match("~{$pattern}~i", $log->message(), $matches, PREG_UNMATCHED_AS_NULL);
            // phpcs:enable WordPress.PHP.NoSilencedErrors
            if (!$matches) {
                return false;
            }
        } catch (\Throwable $throwable) {
            return false;
        }

        $minLevel = null;
        foreach ($levels as $key => $level) {
            if (
                $level !== null
                && !empty($matches[$key])
                && ($minLevel === null || ($level < $minLevel))
            ) {
                $minLevel = $level;
            }
        }

        return ($minLevel === null) || ($log->level() < $minLevel);
    }

    /**
     * @param \DateTimeZone $zone
     * @return $this
     */
    public function useTimezone(\DateTimeZone $zone): Channels
    {
        $this->timezone = $zone;

        return $this;
    }

    /**
     * @param string $channel
     * @return bool
     */
    public function hasChannel(string $channel): bool
    {
        return (bool)($this->channels[$channel] ?? false);
    }

    /**
     * @return array<string>
     */
    public function allNames(): array
    {
        return array_keys($this->channels);
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
    ): Channels {

        array_unshift($handlerIdentifiers, $handlerIdentifier);

        foreach (array_unique($handlerIdentifiers) as $identifier) {
            $handler = $this->handlersRegistry->findById($identifier);
            if ($handler) {
                $this->handlersRegistry->addHandler($handler, $identifier, $channel);
            }
        }

        return $this;
    }

    /**
     * @param string $identifier
     * @param string $channel
     * @param string ...$channels
     * @return $this
     */
    public function enableHandlerForChannels(
        string $identifier,
        string $channel,
        string ...$channels
    ): Channels {

        $handler = $this->handlersRegistry->findById($identifier);
        if ($handler) {
            $this->handlersRegistry->addHandler($handler, $identifier, $channel, ...$channels);
        }

        return $this;
    }

    /**
     * @param string $channel
     * @return LoggerInterface
     */
    public function logger(string $channel): LoggerInterface
    {
        if (!$this->hasChannel($channel)) {
            return new NullLogger();
        }

        $logger = $this->loggers[$channel] ?? null;
        if ($logger) {
            return $logger;
        }

        $loggerProcessors = $this->processorsRegistry->allForLogger($channel);

        [$factory, $factoryEnabled] = $this->loggerFactoryData[$channel]
            ?? $this->loggerFactoryData[self::ALL_CHANNELS]
            ?? [null, false];

        if ($factory && $factoryEnabled) {
            return $this->factoryLoggerFromCallback(
                $factory,
                $channel,
                $loggerProcessors,
                $this->timezone
            );
        }

        $handlers = $this->handlersRegistry->findForChannel($channel);
        if (!$handlers) {
            return new NullLogger();
        }

        $logger = new Logger($channel, $handlers, $loggerProcessors, $this->timezone);

        return $this->initializeLogger($logger, $channel);
    }

    /**
     * @param callable(string, ?\DateTimeZone):LoggerInterface $factory
     * @param string $channel
     * @param array<callable(array):array> $loggerProcessors
     * @param \DateTimeZone|null $timezone
     * @return LoggerInterface
     */
    private function factoryLoggerFromCallback(
        callable $factory,
        string $channel,
        array $loggerProcessors,
        ?\DateTimeZone $timezone
    ): LoggerInterface {

        $logger = $factory($channel, $timezone);

        /** @psalm-suppress DocblockTypeContradiction */
        if (!$logger instanceof LoggerInterface) {
            $logger = new NullLogger();
        }

        if (($logger instanceof ProcessableHandlerInterface) && $loggerProcessors) {
            foreach ($loggerProcessors as $loggerProcessor) {
                $logger->pushProcessor($loggerProcessor);
            }
        }

        $this->loggers[$channel] = $logger;

        return $this->initializeLogger($logger, $channel);
    }

    /**
     * @param LoggerInterface $logger
     * @param string $channel
     * @return LoggerInterface
     */
    private function initializeLogger(LoggerInterface $logger, string $channel): LoggerInterface
    {
        /**
         * Fires right after the Logger is instantiated.
         *
         * Can be used to push handlers and/or processors.
         *
         * @params LoggerInterface $logger
         * @params HandlersRegistry $handlersRegistry
         * @params ProcessorsRegistry $processorsRegistry
         */
        do_action(self::ACTION_LOGGER, $logger, $this->handlersRegistry, $this->processorsRegistry);

        $this->loggers[$channel] = $logger;

        return $logger;
    }
}

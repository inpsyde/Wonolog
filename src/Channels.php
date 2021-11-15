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
    public const ACTION_MONOLOG_LOGGER = 'wonolog.monolog-logger';

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
     * @var Registry\HandlersRegistry
     */
    private $handlersRegistry;

    /**
     * @var Registry\ProcessorsRegistry
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
     * @var array<string, array{string, array<string, int|null>}>
     */
    private $ignoreList = [];

    /**
     * @var string
     */
    private $defaultChannel;

    /**
     * @param Registry\HandlersRegistry $handlers
     * @param Registry\ProcessorsRegistry $processors
     * @return Channels
     */
    public static function new(
        Registry\HandlersRegistry $handlers,
        Registry\ProcessorsRegistry $processors
    ): Channels {

        return new self($handlers, $processors);
    }

    /**
     * @param Registry\HandlersRegistry $handlers
     * @param Registry\ProcessorsRegistry $processors
     */
    private function __construct(
        Registry\HandlersRegistry $handlers,
        Registry\ProcessorsRegistry $processors
    ) {

        $this->handlersRegistry = $handlers;
        $this->processorsRegistry = $processors;
        $this->channels = array_fill_keys(self::DEFAULT_CHANNELS, 1);
        $this->defaultChannel = self::DEBUG;
    }

    /**
     * @param string $channel
     * @return $this
     */
    public function useDefaultChannel(string $channel): Channels
    {
        $this->addChannel($channel);
        $this->defaultChannel = $channel;

        return $this;
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
     * @return string
     */
    public function defaultChannel(): string
    {
        return $this->defaultChannel;
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
     * @return list<string>
     */
    public function allNames(): array
    {
        return array_keys($this->channels);
    }

    /**
     * @param string $channel
     * @return bool
     */
    public function canLogChannel(string $channel): bool
    {
        if (!$this->hasChannel($channel)) {
            return false;
        }

        if (!empty($this->loggers[$channel])) {
            return true;
        }

        return $this->handlersRegistry->hasAnyHandlerForChannel($channel);
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

        $handlers = $this->handlersRegistry->findForChannel($channel);
        if (!$handlers) {
            return new NullLogger();
        }

        $loggerProcessors = $this->processorsRegistry->findForChannel($channel);
        if (!$this->timezone && function_exists('wp_timezone')) {
            $this->timezone = wp_timezone();
        }

        $logger = new Logger($channel, $handlers, $loggerProcessors, $this->timezone);

        return $this->initializeLogger($logger, $channel);
    }

    /**
     * @param Logger $logger
     * @param string $channel
     * @return LoggerInterface
     */
    private function initializeLogger(Logger $logger, string $channel): LoggerInterface
    {
        /**
         * Fires right after a logger is instantiated.
         *
         * Can be used to push processors from the registry.
         *
         * @param Logger $logger
         * @param string $channel
         * @param Registry\HandlersRegistry $handlersRegistry
         * @param Registry\ProcessorsRegistry $processorsRegistry
         */
        do_action(
            self::ACTION_LOGGER,
            $logger,
            $this->handlersRegistry,
            $this->processorsRegistry
        );

        $this->loggers[$channel] = $logger;

        return $logger;
    }
}

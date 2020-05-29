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

use Inpsyde\Wonolog\Exception\InvalidChannelNameException;
use Inpsyde\Wonolog\Handler\HandlersRegistry;
use Inpsyde\Wonolog\Processor\ProcessorsRegistry;
use Monolog\Handler\HandlerInterface;
use Monolog\Logger;

/**
 * Class that acts as a sort of service provider for loggers, creating them first time or just
 * returning on subsequent requests.
 * We don't use Monolog registry to be able to register handle here as constants the list of Wonolog
 * default channels and to initialize via hooks the logger the first time is retrieved.
 *
 * @package wonolog
 * @license http://opensource.org/licenses/MIT MIT
 */
class Channels
{

    public const HTTP = 'HTTP';
    public const DB = 'DB';
    public const PHP_ERROR = 'PHP-ERROR';
    public const SECURITY = 'SECURITY';
    public const DEBUG = 'DEBUG';

    public const FILTER_CHANNELS = 'wonolog.channels';
    public const ACTION_LOGGER = 'wonolog.logger';
    public const FILTER_USE_DEFAULT_HANDLER = 'wonolog.use-default-handler';
    public const FILTER_USE_DEFAULT_PROCESSOR = 'wonolog.use-default-processor';

    public const DEFAULT_CHANNELS = [
        Channels::HTTP,
        Channels::DB,
        Channels::SECURITY,
        Channels::DEBUG,
    ];

    /**
     * @var HandlersRegistry
     */
    private $handlersRegistry;

    /**
     * @var ProcessorsRegistry
     */
    private $processorsRegistry;

    /**
     * @var Logger[]
     */
    private $loggers = [];

    /**
     * @var string[]
     */
    private $channels = [];

    /**
     * @var string[]
     */
    private $channelsInitialized = [];

    /**
     * @return array<string>
     */
    public static function all(): array
    {
        /**
         * Filters the channels to use.
         *
         * @param string[] $channels
         */
        $channels = apply_filters(self::FILTER_CHANNELS, self::DEFAULT_CHANNELS);

        return is_array($channels) ? array_unique(array_filter($channels, 'is_string')) : [];
    }

    /**
     * @param HandlersRegistry $handlers
     * @param ProcessorsRegistry $processors
     */
    public function __construct(HandlersRegistry $handlers, ProcessorsRegistry $processors)
    {
        $this->channels = self::all();
        $this->handlersRegistry = $handlers;
        $this->processorsRegistry = $processors;
    }

    /**
     * @param string $channel
     * @return bool
     *
     * @throws InvalidChannelNameException
     */
    public function hasChannel(string $channel): bool
    {
        return in_array($channel, $this->channels, true);
    }

    /**
     * @param string $channel
     * @return Logger
     *
     * @throws InvalidChannelNameException
     */
    public function logger(string $channel): Logger
    {
        if (!$this->hasChannel($channel)) {
            throw InvalidChannelNameException::forUnregisteredChannel($channel);
        }

        if (!array_key_exists($channel, $this->loggers)) {
            $this->loggers[$channel] = new Logger($channel);
        }

        if (!in_array($channel, $this->channelsInitialized, true)) {
            $this->channelsInitialized[] = $channel;

            return $this->initializeLogger($this->loggers[$channel]);
        }

        return $this->loggers[$channel];
    }

    /**
     * @param Logger $logger
     * @return Logger
     */
    private function initializeLogger(Logger $logger): Logger
    {
        $defaultHandler = $this->useDefaultHandler($logger);
        $defaultHandler and $logger = $logger->pushHandler($defaultHandler);

        $defaultProcessor = $this->useDefaultProcessor($logger);
        $defaultProcessor and $logger = $logger->pushProcessor($defaultProcessor);

        /**
         * Fires right before a logger is used for the first time.
         *
         * Hook here to set up the logger (e.g., add handlers or processors).
         *
         * @param Logger $logger
         * @param HandlersRegistry $handlers_registry
         * @param ProcessorsRegistry $processors_registry
         */
        do_action(
            self::ACTION_LOGGER,
            $logger,
            $this->handlersRegistry,
            $this->processorsRegistry
        );

        return $logger;
    }

    /**
     * @param Logger $logger
     *
     * @return HandlerInterface|null
     */
    private function useDefaultHandler(Logger $logger): ?HandlerInterface
    {
        $handler = $this->handlersRegistry->find(HandlersRegistry::DEFAULT_NAME);
        if (!$handler instanceof HandlerInterface) {
            return null;
        }

        /**
         * Filters whether to use the default handler.
         *
         * @param bool $use_default_handler
         * @param Logger $logger
         * @param HandlerInterface $handler
         */
        if (!apply_filters(self::FILTER_USE_DEFAULT_HANDLER, true, $logger, $handler)) {
            return null;
        }

        return $handler;
    }

    /**
     * @param Logger $logger
     * @return callable|null
     */
    private function useDefaultProcessor(Logger $logger): ?callable
    {
        $processor = $this->processorsRegistry->find(ProcessorsRegistry::DEFAULT_NAME);
        if (!$processor) {
            return null;
        }

        /**
         * Filters whether to use the default processor.
         *
         * @param bool $useDefaultProcessor
         * @param Logger $logger
         * @param callable $processor
         */
        if (apply_filters(self::FILTER_USE_DEFAULT_PROCESSOR, true, $logger, $processor)) {
            return $processor;
        }

        return null;
    }
}

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

namespace Inpsyde\Wonolog\DefaultHandler;

use Inpsyde\Wonolog\Levels;
use Inpsyde\Wonolog\LogLevel;
use Inpsyde\Wonolog\Processor;
use Monolog\Formatter\FormatterInterface;
use Monolog\Handler\FormattableHandlerInterface;
use Monolog\Handler\HandlerInterface;
use Monolog\Handler\NullHandler;
use Monolog\Handler\ProcessableHandlerInterface;
use Monolog\LogRecord;
use Monolog\ResettableInterface;

class FileHandler implements
    HandlerInterface,
    ProcessableHandlerInterface,
    FormattableHandlerInterface,
    ResettableInterface
{
    private ?string $folder = null;

    private ?string $filename = null;

    private ?int $minLevel = null;

    private bool $bubble = true;

    private bool $buffering = true;

    private ?HandlerInterface $handler = null;

    private readonly HandlerFactoryInterface $factory;

    private ?string $logFilePath = null;

    public static function new(
        ?HandlerFactoryInterface $factory = null
    ): FileHandler {

        return new self($factory);
    }

    private function __construct(
        ?HandlerFactoryInterface $factory = null
    ) {

        $this->factory = $factory ?? new HandlerFactory();
    }

    public function __destruct()
    {
        $this->close();
    }

    public function withFolder(string $folder): FileHandler
    {
        $this->folder = wp_normalize_path($folder);

        return $this;
    }

    public function withFilename(string $filename): FileHandler
    {
        $this->filename = $filename;

        return $this;
    }

    public function withDateBasedFileFormat(
        string $format,
        string $extension = 'log'
    ): FileHandler {

        $date = date($format);
        if (!$date) {
            return $this;
        }

        $this->filename = ($extension && $extension !== '.')
            ? "{$date}." . ltrim($extension, '.')
            : $date;

        return $this;
    }

    public function withMinimumLevel(int $level): FileHandler
    {
        $this->minLevel = LogLevel::normalizeLevel($level);

        return $this;
    }

    public function enableBubbling(): FileHandler
    {
        $this->bubble = true;

        return $this;
    }

    public function disableBubbling(): FileHandler
    {
        $this->bubble = false;

        return $this;
    }

    public function enableBuffering(): FileHandler
    {
        $this->buffering = true;

        return $this;
    }

    public function disableBuffering(): FileHandler
    {
        $this->buffering = false;

        return $this;
    }

    public function handle(array|LogRecord $record): bool
    {
        $this->ensureHandler();
        return $this->handler->handle($record);
    }

    public function isHandling(array|LogRecord $record): bool
    {
        $this->ensureHandler();
        return $this->handler->isHandling($record);
    }

    public function handleBatch(array $records): void
    {
        $this->ensureHandler();

        $this->handler->handleBatch($records);
    }

    public function close(): void
    {
        $this->handler?->close();
    }

    public function pushProcessor(callable $callback): HandlerInterface
    {
        $this->ensureHandler();
        if ($this->handler instanceof ProcessableHandlerInterface) {
            $this->handler->pushProcessor($callback);
        }

        return $this;
    }

    public function popProcessor(): callable
    {
        $this->ensureHandler();
        if (!$this->handler instanceof ProcessableHandlerInterface) {
            return new Processor\NullProcessor();
        }

        return $this->handler->popProcessor();
    }

    /**
     * phpcs:disable Syde.Classes.DisallowGetterSetter
     */
    public function setFormatter(FormatterInterface $formatter): HandlerInterface
    {
        // phpcs:enable Syde.Classes.DisallowGetterSetter
        $this->ensureHandler();
        if ($this->handler instanceof FormattableHandlerInterface) {
            $this->handler->setFormatter($formatter);
        }

        return $this;
    }

    /**
     * phpcs:disable Syde.Classes.DisallowGetterSetter
     */
    public function getFormatter(): FormatterInterface
    {
        // phpcs:enable Syde.Classes.DisallowGetterSetter
        $this->ensureHandler();
        if ($this->handler instanceof FormattableHandlerInterface) {
            return $this->handler->getFormatter();
        }

        /** @var FormatterInterface|null $noopFormatter */
        static $noopFormatter;

        return $noopFormatter
            ?? $noopFormatter = new PassthroughFormatter();
    }

    public function reset(): void
    {
        $this->ensureHandler();
        if ($this->handler instanceof ResettableInterface) {
            $this->handler->reset();
        }
    }

    public function logFilePath(): string
    {
        if ($this->logFilePath) {
            return $this->logFilePath;
        }

        $folder = LogsFolder::determineFolder($this->folder);

        if (!$folder) {
            throw new \Exception('Could not determine or create valid log file path.');
        }

        $logFileName = $this->filename ?? (date('Y/m/d') . '.log');
        $logFilePath = $folder . ltrim($logFileName, '/\\');
        $logFileDir = dirname($logFilePath);
//        if ($logFileDir === '.') {
//            throw new \Exception('Could not determine valid log file path.');
//        }

        /**
         * This ensures that the directory for `$logFileName` has the correct permissions.
         * While `LogsFolder::determineFolder` already calls `wp_mkdir_p`, it only applies to `$this->folder`.
         * This second call ensures that `$logFileName` is also properly ?created and writable.
         */
        if (!wp_mkdir_p($logFileDir)) {
            throw new \Exception('Could not create valid log file path.');
        }

        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_is_writable
        if (!is_writable($logFileDir)) {
            throw new \Exception('Could not obtain valid log file path: not writable.');
        }

        return (string) wp_normalize_path($logFilePath);
    }

    private function ensureHandler(): void
    {
        if ($this->handler) {
            return;
        }
        try {
            $this->logFilePath = $this->logFilePath();
            $level = $this->minLevel ?? LogLevel::defaultMinLevel();
            if (!$level) {
                /** @phpstan-ignore-next-line classConstant.deprecated */
                $level = Levels::DEBUG;
            }

            $this->handler = $this->factory->make($this->logFilePath, $level, $this->buffering, $this->bubble);
        } catch (\Throwable) {
            $this->handler = new NullHandler();
        }
    }
}

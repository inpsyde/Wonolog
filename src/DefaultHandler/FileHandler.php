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

use Inpsyde\Wonolog\LogLevel;
use Inpsyde\Wonolog\Processor;
use Monolog\Formatter\FormatterInterface;
use Monolog\Handler\BufferHandler;
use Monolog\Handler\FormattableHandlerInterface;
use Monolog\Handler\HandlerInterface;
use Monolog\Handler\NullHandler;
use Monolog\Handler\ProcessableHandlerInterface;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
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

    private ?string $logFilePath = null;

    /**
     * @return FileHandler
     */
    public static function new(): FileHandler
    {
        return new self();
    }

    /**
     * Empty on purpose.
     */
    private function __construct()
    {
    }

    /**
     * Close on destruct.
     */
    public function __destruct()
    {
        $this->close();
    }

    /**
     * @param string $folder
     * @return static
     */
    public function withFolder(string $folder): FileHandler
    {
        $this->folder = wp_normalize_path($folder);

        return $this;
    }

    /**
     * @param string $filename
     * @return static
     */
    public function withFilename(string $filename): FileHandler
    {
        $this->filename = $filename;

        return $this;
    }

    /**
     * @param string $format
     * @param string $extension
     * @return static
     */
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

    /**
     * @param int $level
     * @return static
     */
    public function withMinimumLevel(int $level): FileHandler
    {
        $this->minLevel = LogLevel::normalizeLevel($level);

        return $this;
    }

    /**
     * @return static
     */
    public function enableBubbling(): FileHandler
    {
        $this->bubble = true;

        return $this;
    }

    /**
     * @return static
     */
    public function disableBubbling(): FileHandler
    {
        $this->bubble = false;

        return $this;
    }

    /**
     * @return static
     */
    public function enableBuffering(): FileHandler
    {
        $this->buffering = true;

        return $this;
    }

    /**
     * @return static
     */
    public function disableBuffering(): FileHandler
    {
        $this->buffering = false;

        return $this;
    }

    /**
     * @param array $record
     * @return bool
     *
     * @psalm-suppress MixedArgumentTypeCoercion
     */
    public function handle(array $record): bool
    {
        $this->ensureHandler();

        return $this->handler->handle($record);
    }

    /**
     * @param array $record
     * @return bool
     *
     * @psalm-suppress MixedArgumentTypeCoercion
     */
    public function isHandling(array $record): bool
    {
        $this->ensureHandler();

        return $this->handler->isHandling($record);
    }

    /**
     * @param array<array> $records
     * @return void
     *
     * @psalm-suppress MixedArgumentTypeCoercion
     */
    public function handleBatch(array $records): void
    {
        $this->ensureHandler();

        $this->handler->handleBatch($records);
    }

    /**
     * @return void
     */
    public function close(): void
    {
        if ($this->handler) {
            $this->handler->close();
        }
    }

    /**
     * @param callable(array):array|\Monolog\Processor\ProcessorInterface $callback
     * @return static
     *
     * @psalm-suppress MixedArgumentTypeCoercion
     * @psalm-suppress MoreSpecificImplementedParamType
     */
    public function pushProcessor(callable $callback): HandlerInterface
    {
        $this->ensureHandler();
        if ($this->handler instanceof ProcessableHandlerInterface) {
            $this->handler->pushProcessor($callback);
        }

        return $this;
    }

    /**
     * @return callable(array):array
     *
     * @psalm-suppress MixedReturnTypeCoercion
     * @psalm-suppress LessSpecificImplementedReturnType
     */
    public function popProcessor(): callable
    {
        $this->ensureHandler();
        if (!$this->handler instanceof ProcessableHandlerInterface) {
            return new Processor\NullProcessor();
        }

        return $this->handler->popProcessor();
    }

    /**
     * @param FormatterInterface $formatter
     * @return static
     *
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
     * @return FormatterInterface
     *
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

        return $noopFormatter ?? $noopFormatter = new PassthroughFormatter();
    }

    /**
     * @return void
     */
    public function reset(): void
    {
        $this->ensureHandler();
        if ($this->handler instanceof ResettableInterface) {
            $this->handler->reset();
        }
    }

    /**
     * @return string
     */
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
        if ($logFileDir === '.') {
            throw new \Exception('Could not determine valid log file path.');
        }

        if (!wp_mkdir_p($logFileDir)) {
            throw new \Exception('Could not create valid log file path.');
        }

        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_is_writable
        if (!is_writable($logFileDir)) {
            throw new \Exception('Could not obtain valid log file path: not writable.');
        }

        return (string) wp_normalize_path($logFilePath);
    }

    /**
     * @return void
     *
     * @psalm-assert HandlerInterface $this->handler
     */
    private function ensureHandler(): void
    {
        if ($this->handler) {
            return;
        }

        try {
            $this->logFilePath = $this->logFilePath();
            $level = $this->minLevel ?? LogLevel::defaultMinLevel();
            if (!$level) {
                $level = Logger::DEBUG;
            }
            $streamBuffer = $this->buffering || $this->bubble;
            $handler = new StreamHandler($this->logFilePath, $level, $streamBuffer, null, true);
            $this->handler = $this->buffering
                ? new BufferHandler($handler, 0, $level, $this->bubble)
                : $handler;
        } catch (\Throwable $throwable) {
            $this->handler = new NullHandler();
        }
    }
}

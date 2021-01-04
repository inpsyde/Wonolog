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

use Monolog\Formatter\FormatterInterface;
use Monolog\Handler\FormattableHandlerInterface;
use Monolog\Handler\HandlerInterface;
use Monolog\Handler\NullHandler;
use Monolog\Handler\ProcessableHandlerInterface;
use Monolog\Handler\StreamHandler;
use Monolog\ResettableInterface;

class WonologFileHandler implements
    HandlerInterface,
    ProcessableHandlerInterface,
    FormattableHandlerInterface,
    ResettableInterface
{
    /**
     * @var string|null
     */
    private $folder;

    /**
     * @var string|null
     */
    private $filename;

    /**
     * @var int|null
     */
    private $minLevel;

    /**
     * @var bool
     */
    private $bubble = true;

    /**
     * @var HandlerInterface|null
     */
    private $handler;

    /**
     * @var string|null
     */
    private $logFilePath;

    /**
     * @return WonologFileHandler
     */
    public static function new(): WonologFileHandler
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
     * @param string $folder
     * @return static
     */
    public function withFolder(string $folder): WonologFileHandler
    {
        $this->folder = wp_normalize_path($folder);

        return $this;
    }

    /**
     * @param string $filename
     * @return static
     */
    public function withFilename(string $filename): WonologFileHandler
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
    ): WonologFileHandler {

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
    public function withMinimumLevel(int $level): WonologFileHandler
    {
        $this->minLevel = LogLevel::normalizeLevel($level);

        return $this;
    }

    /**
     * @return static
     */
    public function enableBubbling(): WonologFileHandler
    {
        $this->bubble = true;

        return $this;
    }

    /**
     * @return static
     */
    public function disableBubbling(): WonologFileHandler
    {
        $this->bubble = false;

        return $this;
    }

    /**
     * @param array $record
     * @return bool
     */
    public function handle(array $record): bool
    {
        $this->ensureHandler();

        return $this->handler->handle($record);
    }

    /**
     * @param array $record
     * @return bool
     */
    public function isHandling(array $record): bool
    {
        $this->ensureHandler();

        return $this->handler->isHandling($record);
    }

    /**
     * @param array $records
     * @return void
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
        $this->ensureHandler();

        $this->handler->close();
    }

    /**
     * @param callable(array):array|\Monolog\Processor\ProcessorInterface $callback
     * @return static
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
     */
    public function popProcessor(): callable
    {
        $this->ensureHandler();
        if ($this->handler instanceof ProcessableHandlerInterface) {
            return $this->handler->popProcessor();
        }

        return new Processor\NullProcessor();
    }

    /**
     * @param FormatterInterface $formatter
     * @return static
     *
     * phpcs:disable Inpsyde.CodeQuality.NoAccessors
     */
    public function setFormatter(FormatterInterface $formatter): HandlerInterface
    {
        // phpcs:enable Inpsyde.CodeQuality.NoAccessors
        $this->ensureHandler();
        if ($this->handler instanceof FormattableHandlerInterface) {
            $this->handler->setFormatter($formatter);
        }

        return $this;
    }

    /**
     * @return FormatterInterface
     *
     * phpcs:disable Inpsyde.CodeQuality.NoAccessors
     */
    public function getFormatter(): FormatterInterface
    {
        // phpcs:disable Inpsyde.CodeQuality.NoAccessors
        $this->ensureHandler();
        if ($this->handler instanceof FormattableHandlerInterface) {
            return $this->handler->getFormatter();
        }

        /** @var FormatterInterface|null $formatter */
        static $formatter;
        // phpcs:disable
        $formatter or $formatter = new class implements FormatterInterface {
            public function format(array $record) { return $record; }
            public function formatBatch(array $records) { return $records; }
        };
        // phpcs:enable

        return $formatter;
    }

    /**
     * @return void
     */
    public function reset()
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
        $logFileFir = dirname($logFilePath);
        if (!$logFileFir || $logFileFir === '.') {
            throw new \Exception('Could not determine valid log file path.');
        }

        if (!wp_mkdir_p($logFileFir)) {
            throw new \Exception('Could not create valid log file path.');
        }

        if (!is_writable($logFileFir)) {
            throw new \Exception('Could not obtain valid log file path: not writable.');
        }

        return (string)wp_normalize_path($logFilePath);
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
            $this->handler = new StreamHandler(
                $this->logFilePath,
                $this->minLevel ?? LogLevel::defaultMinLevel(),
                $this->bubble
            );
        } catch (\Throwable $throwable) {
            $this->handler = new NullHandler();
        }
    }
}

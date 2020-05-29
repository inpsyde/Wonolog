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

namespace Inpsyde\Wonolog\Handler;

use Monolog\Handler\AbstractHandler;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;

/**
 * Similar to Monolog RotatingFileHandler, this class
 * overcomes RotatingFileHandler too stringent date format
 * enforcement.
 *
 * @package wonolog
 * @license http://opensource.org/licenses/MIT MIT
 */
final class DateBasedStreamHandler extends AbstractProcessingHandler
{

    private const VALID_DATE_PLACEHOLDERS = 'dDjlNwzWFMmntoYy';

    /**
     * @var StreamHandler[]
     */
    private $handlers = [];

    /**
     * @var string|callable
     */
    private $fileFormat;

    /**
     * @var string
     */
    private $dateFormat;

    /**
     * @var bool
     */
    private $locking;

    /**
     * @param string|callable $fileFormat
     * @param string $dateFormat
     * @param bool|int $level
     * @param bool $bubble
     * @param bool $locking
     *
     * phpcs:disable Inpsyde.CodeQuality.ArgumentTypeDeclaration.NoArgumentType
     */
    public function __construct(
        $fileFormat,
        string $dateFormat,
        $level = Logger::DEBUG,
        bool $bubble = true,
        bool $locking = true
    ) {

        if (!$this->checkFileFormat($fileFormat) || !$this->checkDateFormat($dateFormat)) {
            throw new \InvalidArgumentException('Invalid file name or date format for ' . __CLASS__);
        }

        $this->fileFormat = $fileFormat;
        $this->dateFormat = (string)$dateFormat;
        $this->locking = (bool)$locking;

        parent::__construct($level, $bubble);
    }

    /**
     * @param array $record
     * @return StreamHandler
     */
    public function streamHandlerForRecord(array $record): StreamHandler
    {
        [$filename, $filePermissions] = $this->fileNameForRecord($record);

        if (isset($this->handlers[$filename])) {
            return $this->handlers[$filename];
        }

        $this->close();

        $handler = new StreamHandler(
            $filename,
            $this->getLevel(),
            $this->getBubble(),
            $filePermissions,
            $this->locking
        );

        $this->handlers[$filename] = $handler;

        return $handler;
    }

    /**
     * @param array $record
     * @return void
     */
    protected function write(array $record): void
    {
        $this->streamHandlerForRecord($record)->write($record);
    }

    /**
     * @return void
     */
    public function close(): void
    {
        $this->handlers and array_walk(
            $this->handlers,
            static function (AbstractHandler $handler): void {
                $handler->close();
            }
        );

        unset($this->handlers);
        $this->handlers = [];
    }

    /**
     * @param string $fileFormat
     * @return bool
     *
     * phpcs:disable Inpsyde.CodeQuality.ArgumentTypeDeclaration.NoArgumentType
     */
    private function checkFileFormat($fileFormat): bool
    {
        if (is_callable($fileFormat)) {
            return true;
        }

        return
            is_string($fileFormat)
            && substr_count($fileFormat, '{date}') === 1;
    }

    /**
     * Checks that a date format contains only valida `date()` placeholder
     * and valid separators, but not only separators
     *
     * @param $dateFormat
     * @return bool
     */
    private function checkDateFormat(string $dateFormat): bool
    {
        if (!is_string($dateFormat)) {
            return false;
        }

        $dateFormatNoSep = str_replace(['-', '_', '/', '.'], '', $dateFormat);

        if (!$dateFormatNoSep) {
            return false;
        }

        return rtrim($dateFormatNoSep, self::VALID_DATE_PLACEHOLDERS) === '';
    }

    /**
     * @param array $record
     * @return array
     *
     * phpcs:disable WordPress.PHP.NoSilencedErrors.Discouraged
     */
    private function fileNameForRecord(array $record): array
    {
        $fileFormat = $this->fileFormat;

        if (is_callable($fileFormat)) {
            $fileFormat = $fileFormat($record);
            is_callable($fileFormat) and $fileFormat = null;
            $this->checkFileFormat($fileFormat) or $fileFormat = '{date}.log';
        }

        $timestamp = $this->recordTimestamp($record);

        $filename = str_replace('{date}', date($this->dateFormat, $timestamp), $fileFormat);
        if (!filter_var(filter_var($filename, FILTER_SANITIZE_URL), FILTER_SANITIZE_URL)) {
            throw new \InvalidArgumentException('Invalid file name format or date format for ' . __CLASS__);
        }

        $dir = @dirname($filename);
        if (!$dir || !wp_mkdir_p($dir)) {
            throw new \RuntimeException('It was not possible to create folder ' . $dir);
        }

        $stat = @stat($dir);
        $dirPerms = isset($stat['mode']) ? $stat['mode'] & 0007777 : 0755;

        return [$filename, $dirPerms];
    }

    /**
     * @param array $record
     * @return int
     *
     * phpcs:disable WordPress.PHP.NoSilencedErrors.Discouraged
     */
    private function recordTimestamp(array $record): int
    {
        static $oldTimestamp;
        $oldTimestamp or $oldTimestamp = strtotime('1 month ago');

        $timestamp = empty($record['datetime']) ? null : $record['datetime'];

        if (is_string($timestamp)) {
            $timestamp = is_numeric($timestamp) ? (int)$timestamp : @strtotime($timestamp);
            ($timestamp && is_int($timestamp)) or $timestamp = null;
        }

        if ($timestamp instanceof \DateTimeInterface) {
            $timestamp = $timestamp->getTimestamp();
        }

        $timestampNow = time();

        // We don't really have a way to see if an integer is a timestamp,
        // but if it's a number that's bigger than
        // 1 month ago timestamp and lower than current timestamp,
        // chances are it is a valid one.
        if (
            is_int($timestamp)
            && $timestamp > $oldTimestamp
            && $timestamp <= $timestampNow
        ) {
            return $timestamp;
        }

        return $timestampNow;
    }
}

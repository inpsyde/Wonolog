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

namespace Inpsyde\Wonolog\Processor;

use Inpsyde\Wonolog\LogLevel;
use Monolog\DateTimeImmutable;
use Psr\Log\AbstractLogger;
use Psr\Log\LoggerInterface;

class ProcessablePsrLogger extends AbstractLogger
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var callable[]
     */
    private $processors = [];

    /**
     * @var \DateTimeZone
     */
    private $timezone;

    /**
     * @var string
     */
    private $channel;

    public function __construct(LoggerInterface $logger, string $channel, \DateTimeZone $timezone = null)
    {
        $this->logger = $logger;
        $this->timezone = $timezone ?: new \DateTimeZone(date_default_timezone_get() ?: 'UTC');
        $this->channel = $channel;
    }

    /**
     * @param callable $callback
     * @return static
     */
    public function pushProcessor(callable $callback): ProcessablePsrLogger
    {
        array_unshift($this->processors, $callback);

        return $this;
    }

    /**
     * @return callable
     */
    public function popProcessor(): callable
    {
        if (!$this->processors) {
            throw new \LogicException('You tried to pop from an empty processor stack.');
        }

        return array_shift($this->processors);
    }

    /**
     * @param mixed $level
     * @param string $message
     * @param array $context
     */
    public function log($level, $message, array $context = [])
    {
        $level = LogLevel::normalizeLevel($level) ?? LogLevel::DEBUG;

        $record = [
            'message' => $message,
            'context' => $context,
            'level' => $level,
            'level_name' => LogLevel::toPsrLevel($level),
            'channel' => $this->channel,
            'datetime' => new DateTimeImmutable(true, $this->timezone),
            'extra' => [],
        ];

        foreach ($this->processors as $processor) {
            $record = (array)$processor($record);
        }

        $message = (string)($record['message'] ?? $message);
        $channel = $record['channel'] ?? null;
        $datetime = $record['datetime'] ?? null;
        $extra = $record['extra'] ?? null;
        $context = (array)($record['context'] ?? []);

        if ($channel) {
            $key = array_key_exists('channel', $context) ? 'processed_channel' : 'channel';
            $context[$key] = $datetime;
        }

        if ($datetime) {
            $key = array_key_exists('datetime', $context) ? 'processed_datetime' : 'datetime';
            $context[$key] = $datetime;
        }

        if ($extra && is_array($extra)) {
            $context = $this->addExtra($extra, $context);
        }

        $newLevel = LogLevel::normalizeLevel($record['level'] ?? $level) ?? LogLevel::DEBUG;
        $psrLevel = LogLevel::toPsrLevel($newLevel);

        $this->logger->log($psrLevel, $message, $context);
    }

    /**
     * @param array $extra
     * @param array $context
     * @return array
     */
    private function addExtra(array $extra, array $context): array
    {
        foreach ($extra as $i => $value) {
            $key = array_key_exists($i, $context) ? "extra_{$i}" : $i;
            $context[$key] = $value;
        }

        return $context;
    }
}

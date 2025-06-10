<?php

declare(strict_types=1);

namespace Inpsyde\Wonolog;

use Monolog\Level;
use Monolog\LogRecord;

/**
 * @phpstan-import-type _RecordType from Configurator
 * @phpstan-import-type _RecordArray from Configurator
 * @phpstan-import-type _RecordObject from Configurator
 */
abstract class RecordFactory
{
    /**
     * @param string $message
     * @param int $level
     * @param string $channel
     * @param array<mixed> $context
     * @return _RecordType
     */
    public static function createRecord(
        string $message,
        int $level,
        string $channel,
        array $context = []
    ): array|LogRecord {

        return (MonologUtils::version() < 3)
            ? static::createRecordArray($message, $level, $context)
            : static::createRecordObject($message, $level, $channel, $context);
    }

    /**
     * @param string $message
     * @param int $level
     * @param array<mixed> $context
     * @return _RecordArray
     */
    protected static function createRecordArray(string $message, int $level, array $context = []): array
    {
        return compact('message', 'context', 'level');
    }

    /**
     * @param string $message
     * @param int $level
     * @param string $channel
     * @param array<mixed> $context
     * @return _RecordObject
     */
    protected static function createRecordObject(
        string $message,
        int $level,
        string $channel,
        array $context = []
    ): LogRecord {

        if (!in_array($level, Level::VALUES, true)) {
            $level = LogLevel::ERROR;
        }

        return new LogRecord(
            new \DateTimeImmutable(),
            $channel,
            Level::fromValue($level),
            $message,
            $context
        );
    }
}

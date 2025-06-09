<?php

declare(strict_types=1);

namespace Inpsyde\Wonolog;

use Monolog\Level;
use Monolog\LogRecord;

class RecordFactory
{
    /**
     * @param string $message
     * @param int $level
     * @param string $channel
     * @param array $context
     * @return array|LogRecord
     */
    public function createRecord(string $message, int $level, string $channel, array $context = []): array|LogRecord
    {
        return (MonologUtils::version() < 3)
            ? $this->createRecordV2($message, $level, $context)
            : $this->createRecordV3($message, $level, $channel, $context);
    }

    /**
     * @param string $message
     * @param int $level
     * @param array $context
     * @return array
     */
    public function createRecordV2(string $message, int $level, array $context = []): array
    {
        return compact('message', 'context', 'level');
    }

    /**
     * @param string $message
     * @param int $level
     * @param string $channel
     * @param array $context
     * @return LogRecord
     */
    public static function createRecordV3(
        string $message,
        int $level,
        string $channel,
        array $context = []
    ): LogRecord {

        if (!in_array($level, Level::VALUES, true)) {
            $level = Levels::ERROR;
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

<?php

declare(strict_types=1);

namespace Inpsyde\Wonolog;

use Monolog\Level;
use Monolog\Logger;
use Monolog\LogRecord;
use Monolog\Processor\PsrLogMessageProcessor;

class RecordFactory
{
    public function __construct(
        private PsrLogMessageProcessor $processor
    ) {
    }

    /**
     * @phpstan-import-type Record from \Monolog\Logger
     */
    public function createRecord(string $message, int $level, string $channel, array $context = []): array|LogRecord
    {
        return (Logger::API < 3)
            ? $this->createRecordV2($message, $level, $context)
            : $this->createRecordV3($message, $level, $channel, $context);
    }

    /**
     * @phpstan-import-type Record from \Monolog\Logger
     */
    public function createRecordV2(string $message, int $level, array $context = []): array
    {
        /** @var Record $record */
        $record = compact('message', 'context', 'level');
        $record = ($this->processor)($record);
        // @phpstan-ignore function.alreadyNarrowedType
        return $record;
    }

    public static function createRecordV3(string $message, int $level, string $channel, array $context = []): LogRecord
    {
        return new LogRecord(
            new \DateTimeImmutable(),
            $channel,
            Level::fromValue($level),
            $message,
            $context
        );
    }
}

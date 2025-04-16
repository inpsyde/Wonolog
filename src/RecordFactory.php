<?php

declare(strict_types=1);

namespace Inpsyde\Wonolog;

use Monolog\Level;
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
/** @phpstan-ignore-next-line */
        return (MonologUtils::version() < 3)
            ? $this->createRecordV2($message, $level, $context)
            : $this->createRecordV3($message, $level, $channel, $context);
    }

    /**
     * @phpstan-import-type Record from \Monolog\Logger
     */
    public function createRecordV2(string $message, int $level, array $context = []): array
    {
        /** @var Record $record */
        /** @phpstan-ignore-next-line */
        $record = compact('message', 'context', 'level');
        /** @phpstan-ignore-next-line argument.type */
        $record = ($this->processor)($record);
        /** @phpstan-ignore-next-line */
        return $record;
    }

    public static function createRecordV3(string $message, int $level, string $channel, array $context = []): LogRecord
    {
        return new LogRecord(
            new \DateTimeImmutable(),
            $channel,
            /** @phpstan-ignore-next-line class.notFound */
            Level::fromValue($level),
            $message,
            $context
        );
    }
}

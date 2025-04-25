<?php

declare(strict_types=1);

namespace Inpsyde\Wonolog\Tests\Unit\DefaultHandler;

use Inpsyde\Wonolog\DefaultHandler\PassthroughFormatter;
use Inpsyde\Wonolog\MonologUtils;
use Inpsyde\Wonolog\Tests\UnitTestCase;
use Monolog\LogRecord;

class PassthroughFormatterTest extends UnitTestCase
{
    public function testFormatReturnsSameArray(): void
    {
        $formatter = new PassthroughFormatter();

        $record = [
            'message' => 'Test log message',
            'context' => ['foo' => 'bar'],
            'level' => 'info',
        ];

        $formatted = $formatter->format($record);

        $this->assertSame($record, $formatted);
    }

    public function testFormatReturnsSameLogRecordObjectIfAvailable(): void
    {
        if (MonologUtils::version() < 3 || !class_exists(LogRecord::class)) {
            $this->markTestSkipped('Monolog\LogRecord not available in this version of Monolog.');
        }

        $formatter = new PassthroughFormatter();

        /** @var LogRecord $logRecord */
        $logRecord = new LogRecord(
            datetime: new \DateTimeImmutable(),
            channel: 'test',
            level: \Monolog\Level::Info,
            message: 'A log message',
            context: ['foo' => 'bar'],
        );

        $formatted = $formatter->format($logRecord);

        $this->assertSame($logRecord, $formatted);
    }

    public function testFormatBatchReturnsSameArray(): void
    {
        $formatter = new PassthroughFormatter();

        $records = [
            ['message' => 'First message'],
            ['message' => 'Second message'],
        ];

        $formattedBatch = $formatter->formatBatch($records);

        $this->assertSame($records, $formattedBatch);
    }
}

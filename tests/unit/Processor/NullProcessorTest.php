<?php

declare(strict_types=1);

namespace Inpsyde\Wonolog\Tests\Unit\Processor;

use Brain\Monkey\Functions;
use Inpsyde\Wonolog\Levels;
use Inpsyde\Wonolog\Processor\NullProcessor;
use Inpsyde\Wonolog\Tests\UnitTestCase;
use Monolog\Level;
use Monolog\LogRecord;

class NullProcessorTest extends UnitTestCase
{
    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();
        Functions\when('get_option')->justReturn();
    }

    public function testProcessesLogRecordCorrectly(): void
    {
        $processor = new NullProcessor();
        $message = 'mymessage';
        $level = Levels::ERROR;
        $channel = 'mychannel';
        $context = [
            'foo' => 'bar',
        ];
        /** @var LogRecord $record */
        $record = new LogRecord(
            new \DateTimeImmutable(),
            $channel,
            /** @phpstan-ignore-next-line class.notFound */
            Level::fromValue($level),
            $message,
            $context
        );
        /** @var LogRecord $processedRecord */
        $processedRecord = $processor($record);

        static::assertInstanceOf(LogRecord::class, $processor($record));
        static::assertEquals($processedRecord->context, $context);
        static::assertEquals($processedRecord->message, $message);
        static::assertEquals($processedRecord->channel, $channel);
        static::assertInstanceOf(Level::class, $record->level);
        static::assertEquals($processedRecord->level->value, $level);
    }

    public function testProcessesArrayCorrectly(): void
    {
        $processor = new NullProcessor();
        $message = 'mymessage';
        $level = Levels::ERROR;
        $channel = 'mychannel'; // TODO: should we add this to the Record? looking for symmetry with LogRecord Model
        $context = [
            'foo' => 'bar',
        ];
        /** @var array $record */
        $record = compact('message', 'context', 'level');
        /** @var array $record */
        $processedRecord = $processor($record);

        static::assertIsArray($processedRecord);
        static::assertEquals($processedRecord['message'], $message);
        static::assertEquals($processedRecord['context'], $context);
        static::assertEquals($processedRecord['level'], $level);
    }
}

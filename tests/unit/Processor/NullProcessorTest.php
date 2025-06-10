<?php

declare(strict_types=1);

namespace Inpsyde\Wonolog\Tests\Unit\Processor;

use Brain\Monkey\Functions;
use Inpsyde\Wonolog\LogLevel;
use Inpsyde\Wonolog\MonologUtils;
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
        if (MonologUtils::version() < 3) {
            $this->markTestSkipped('Monolog\LogRecord not available in this version of Monolog.');
        }
        $processor = new NullProcessor();
        $message = 'mymessage';
        $level = LogLevel::ERROR;
        $channel = 'mychannel';
        $context = [
            'foo' => 'bar',
        ];
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
        $message = 'my-message';
        $level = LogLevel::ERROR;
        $channel = 'my-channel';
        $context = [
            'foo' => 'bar',
        ];
        $record = compact('message', 'context', 'level', 'channel');
        $processedRecord = $processor($record);

        static::assertIsArray($processedRecord);
        static::assertEquals($processedRecord['message'], $message);
        static::assertEquals($processedRecord['context'], $context);
        static::assertEquals($processedRecord['level'], $level);
        static::assertEquals($processedRecord['channel'], $channel);
    }
}

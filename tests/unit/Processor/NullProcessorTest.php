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

final class NullProcessorTest extends UnitTestCase
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

        $this->assertInstanceOf(LogRecord::class, $processor($record));
        $this->assertEquals($processedRecord->context, $context);
        $this->assertEquals($processedRecord->message, $message);
        $this->assertEquals($processedRecord->channel, $channel);
        $this->assertInstanceOf(Level::class, $record->level);
        /** @phpstan-ignore property.nonObject */
        $this->assertEquals($processedRecord->level->value, $level);
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

        $this->assertIsArray($processedRecord);
        $this->assertSame($processedRecord['message'], $message);
        $this->assertSame($processedRecord['context'], $context);
        $this->assertSame($processedRecord['level'], $level);
        $this->assertSame($processedRecord['channel'], $channel);
    }
}

<?php

declare(strict_types=1);

namespace Inpsyde\Wonolog\Tests\Unit;

use Inpsyde\Wonolog\LogLevel;
use Inpsyde\Wonolog\MonologUtils;
use Inpsyde\Wonolog\RecordFactory;
use Inpsyde\Wonolog\Tests\UnitTestCase;
use Monolog\Level;
use Monolog\LogRecord;

final class RecordFactoryTest extends UnitTestCase
{
    public function testRecordFactoryCreatesCorrectRecordType(): void
    {
        $message = 'mymessage';
        $level = LogLevel::ERROR;
        $channel = 'mychannel';
        $context = [
            'foo' => 'bar',
        ];
        $createdRecord = RecordFactory::createRecord($message, $level, $channel, $context);
        if (MonologUtils::version() >= 3) {
            $this->assertInstanceOf(LogRecord::class, $createdRecord);
            $this->assertEquals($createdRecord->context, $context);
            $this->assertSame($createdRecord->message, $message);
            $this->assertSame($createdRecord->channel, $channel);
            $this->assertInstanceOf(Level::class, $createdRecord->level);
            $this->assertEquals($createdRecord->level->value, $level);
            return;
        }
        $this->assertIsArray($createdRecord);
        $this->assertEquals($createdRecord['message'] ?? '', $message);
        $this->assertEquals($createdRecord['context'] ?? '', $context);
        $this->assertEquals($createdRecord['level'] ?? '', $level);
    }
}

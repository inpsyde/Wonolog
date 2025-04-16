<?php

declare(strict_types=1);

namespace Inpsyde\Wonolog\Tests\Unit;

use Inpsyde\Wonolog\Levels;
use Inpsyde\Wonolog\MonologUtils;
use Inpsyde\Wonolog\RecordFactory;
use Inpsyde\Wonolog\Tests\UnitTestCase;
use Monolog\Level;
use Monolog\LogRecord;

class RecordFactoryTest extends UnitTestCase
{
    public function testRecordFactoryCreatesCorrectRecordType(): void
    {
        $message = 'mymessage';
        $level = Levels::ERROR;
        $channel = 'mychannel';
        $context = [
            'foo' => 'bar',
        ];
        $recordFactory = new RecordFactory();
        $createdRecord = $recordFactory->createRecord($message, $level, $channel, $context);
        if (MonologUtils::version() >= 3) {
            static::assertInstanceOf(LogRecord::class, $createdRecord);
            static::assertEquals($createdRecord->context, $context);
            static::assertEquals($createdRecord->message, $message);
            static::assertEquals($createdRecord->channel, $channel);
            static::assertInstanceOf(Level::class, $createdRecord->level);
            static::assertEquals($createdRecord->level->value, $level);
            return;
        }
        static::assertIsArray($createdRecord);
        static::assertEquals($createdRecord['message'], $message);
        static::assertEquals($createdRecord['context'], $context);
        static::assertEquals($createdRecord['level'], $level);
    }
}

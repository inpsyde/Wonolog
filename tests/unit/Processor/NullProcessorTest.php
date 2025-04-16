<?php

namespace Inpsyde\Wonolog\Tests\Unit\Processor;

use Inpsyde\Wonolog\MonologUtils;
use Inpsyde\Wonolog\Processor\NullProcessor;
use Inpsyde\Wonolog\RecordFactory;
use Inpsyde\Wonolog\Tests\UnitTestCase;

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

    public function testProcessesArrayCorrectly(): void
    {
        $processor = new NullProcessor();
        $message = 'mymessage';
        $level = Levels::ERROR;
        $channel = 'mychannel';
        $context = [
            'foo' => 'bar',
        ];
        $recordFactory = new RecordFactory();

        if (MonologUtils::version() < 3) {
            static::
        }

    }

    public function createRecord()
    {

    }
}
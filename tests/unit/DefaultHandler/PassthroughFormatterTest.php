<?php

declare(strict_types=1);

namespace Inpsyde\Wonolog\Tests\Unit\DefaultHandler;

use Inpsyde\Wonolog\DefaultHandler\PassthroughFormatter;
use Inpsyde\Wonolog\Tests\UnitTestCase;

final class PassthroughFormatterTest extends UnitTestCase
{
    /**
     * @test
     */
    public function testFormatReturnsSameArray(): void
    {
        $records = $this->factoryRecords(number: random_int(1, 5));

        $formatter = new PassthroughFormatter();
        foreach ($records as $record) {
            $this->assertSame($record, $formatter->format($record));
        }
    }

    /**
     * @test
     */
    public function testFormatBatchReturnsSameArray(): void
    {
        $records = $this->factoryRecords(number: random_int(3, 7));

        $formatter = new PassthroughFormatter();
        $formattedRecords = $formatter->formatBatch($records);

        foreach ($records as $i => $record) {
            $this->assertSame($record, $formattedRecords[$i]);
        }
    }
}

<?php

declare(strict_types=1);

namespace Inpsyde\Wonolog\MonologV3;

use Monolog\Formatter\FormatterInterface;
use Monolog\LogRecord;

class PassThroughFormatter implements FormatterInterface
{
    /**
     * @param LogRecord $record
     * @return LogRecord
     */
    public function format(LogRecord $record)
    {
        return $record;
    }

    /**
     * @param array<LogRecord> $records
     * @return mixed
     */
    public function formatBatch(array $records)
    {
        return $records;
    }

}
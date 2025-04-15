<?php

declare(strict_types=1);

namespace Inpsyde\Wonolog\DefaultHandler;

use Monolog\Formatter\FormatterInterface;
use Monolog\LogRecord;

class PassthroughFormatter implements FormatterInterface
{
    /**
     * @param array|LogRecord $record
     * @return array
     * @phpstan-ignore-next-line
     */
    public function format(array|LogRecord $record): array|LogRecord
    {
        return $record;
    }

    /**
     * @param array $records
     * @return array
     */
    public function formatBatch(array $records): array
    {
        return $records;
    }
}

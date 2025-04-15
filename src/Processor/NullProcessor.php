<?php

declare(strict_types=1);

namespace Inpsyde\Wonolog\Processor;

use Monolog\LogRecord;

class NullProcessor
{
    /**
     * @param array|LogRecord $record
     * @return array|LogRecord
     * @phpstan-ignore-next-line
     */
    public function __invoke($record)
    {
        return $record;
    }
}

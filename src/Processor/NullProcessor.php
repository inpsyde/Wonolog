<?php

declare(strict_types=1);

namespace Inpsyde\Wonolog\Processor;

use Monolog\LogRecord;

class NullProcessor
{
    public function __invoke(array|LogRecord $record): array|LogRecord
    {
        return $record;
    }
}

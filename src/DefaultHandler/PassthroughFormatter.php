<?php

declare(strict_types=1);

namespace Inpsyde\Wonolog\DefaultHandler;

use Monolog\Formatter\FormatterInterface;

class PassthroughFormatter implements FormatterInterface
{
    /**
     * @param array $record
     * @return array
     */
    public function format(array $record): array
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

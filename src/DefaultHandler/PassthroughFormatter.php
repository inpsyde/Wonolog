<?php

declare(strict_types=1);

namespace Inpsyde\Wonolog\DefaultHandler;

use Monolog\Formatter\FormatterInterface;
use Monolog\LogRecord;

/**
 * @psalm-import-type _RecordType from \Inpsyde\Wonolog\Configurator
 */
class PassthroughFormatter implements FormatterInterface
{
    /**
     * @template T of _RecordType
     *
     * @param T $record
     * @return T
     */
    public function format(array|LogRecord $record): array|LogRecord
    {
        return $record;
    }

    /**
     * @template T of _RecordType
     *
     * @param array<T> $records
     * @return array<T>
     */
    public function formatBatch(array $records): array
    {
        return $records;
    }
}

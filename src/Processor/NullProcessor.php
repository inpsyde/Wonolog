<?php

declare(strict_types=1);

namespace Inpsyde\Wonolog\Processor;

use Monolog\LogRecord;

/**
 * @psalm-import-type _RecordType from \Inpsyde\Wonolog\Configurator
 */
class NullProcessor
{
    /**
     * @template T of _RecordType
     *
     * @param T $record
     * @return T
     */
    public function __invoke(array|LogRecord $record): array|LogRecord
    {
        return $record;
    }
}

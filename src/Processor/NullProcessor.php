<?php

declare(strict_types=1);

namespace Inpsyde\Wonolog\Processor;

class NullProcessor
{
    public function __invoke(array $record): array
    {
        return $record;
    }
}

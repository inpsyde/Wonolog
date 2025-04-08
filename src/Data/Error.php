<?php

declare(strict_types=1);

namespace Inpsyde\Wonolog\Data;

use Inpsyde\Wonolog\LogLevel;

final class Error implements LogData
{
    use LogDataTrait;

    public function level(): int
    {
        return LogLevel::ERROR;
    }
}
